<?php
/**
 * kds.php
 * FR30: Kitchen display integration
 *
 * Routes:
 *   GET    /api/orders/kds.php                   — Fetch active kitchen queue (pending & preparing orders with items, elapsed seconds, modifiers)
 *   POST   /api/orders/kds.php?id=123&action=bump— Bump/advance order to next stage (pending -> preparing -> ready -> completed)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET: Active KDS Queue ──────────────────────────────────────────────────
if ($method === 'GET') {
    // Fetch pending, preparing, and ready orders
    $stmt = $db->prepare("
        SELECT o.order_id, o.order_status, o.order_type, o.table_number, o.notes, o.created_at,
               TIMESTAMPDIFF(SECOND, o.created_at, NOW()) AS elapsed_seconds,
               CONCAT(c.first_name, ' ', COALESCE(c.last_name, '')) AS customer_name,
               CONCAT(e.first_name, ' ', COALESCE(e.last_name, '')) AS server_name
        FROM Orders o
        LEFT JOIN Customers c ON o.customer_id = c.customer_id
        LEFT JOIN Employees e ON o.employee_id = e.employee_id
        WHERE o.order_status IN ('pending', 'preparing', 'ready')
        ORDER BY 
            CASE o.order_status 
                WHEN 'preparing' THEN 1 
                WHEN 'pending' THEN 2 
                WHEN 'ready' THEN 3 
                ELSE 4 
            END,
            o.created_at ASC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll();

    // Fetch items for each active order
    foreach ($orders as &$order) {
        $itemsStmt = $db->prepare("
            SELECT oi.order_item_id, oi.product_id, oi.quantity, oi.item_notes, oi.customisations_json,
                   p.product_name, cat.category_name
            FROM OrderItems oi
            LEFT JOIN Products p ON oi.product_id = p.product_id
            LEFT JOIN Categories cat ON p.category_id = cat.category_id
            WHERE oi.order_id = ?
        ");
        $itemsStmt->execute([$order['order_id']]);
        $items = $itemsStmt->fetchAll();

        foreach ($items as &$item) {
            $customs = !empty($item['customisations_json']) ? json_decode($item['customisations_json'], true) : [];
            $customTags = [];
            foreach ($customs as $c) {
                $customTags[] = $c['option_name'];
            }
            $item['customisations'] = $customs;
            $item['kds_highlight']  = count($customTags) > 0 ? implode(' • ', $customTags) : 'Standard';
        }

        $order['items']           = $items;
        $order['elapsed_seconds'] = (int)$order['elapsed_seconds'];
        $order['elapsed_minutes'] = (int)floor($order['elapsed_seconds'] / 60);
        $order['urgency']         = $order['elapsed_minutes'] > 10 ? 'high' : ($order['elapsed_minutes'] > 5 ? 'medium' : 'normal');
    }

    sendResponse(true, 'KDS queue retrieved.', [
        'active_tickets_count' => count($orders),
        'tickets'              => $orders
    ]);
}

// ── POST: Bump Order to Next State ─────────────────────────────────────────
if ($method === 'POST' && $action === 'bump') {
    if (!$id) {
        sendResponse(false, 'Order ID is required to bump ticket.', null, 422);
    }

    $stmt = $db->prepare("SELECT order_id, order_status FROM Orders WHERE order_id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if (!$order) {
        sendResponse(false, 'Order not found.', null, 404);
    }

    $current = $order['order_status'];
    $next = 'pending';

    if ($current === 'pending') {
        $next = 'preparing';
    } elseif ($current === 'preparing') {
        $next = 'ready';
    } elseif ($current === 'ready') {
        $next = 'completed';
    } else {
        sendResponse(false, "Order is already in state: $current", null, 400);
    }

    // Call orders.php logic / update status
    if ($next === 'completed') {
        $db->prepare("UPDATE Orders SET order_status = 'completed', completed_at = NOW() WHERE order_id = ?")->execute([$id]);
    } else {
        $db->prepare("UPDATE Orders SET order_status = ? WHERE order_id = ?")->execute([$next, $id]);
    }

    sendResponse(true, "Ticket bumped to $next.", [
        'order_id'        => $id,
        'previous_status' => $current,
        'new_status'      => $next
    ]);
}

sendResponse(false, 'Method not allowed.', null, 405);
