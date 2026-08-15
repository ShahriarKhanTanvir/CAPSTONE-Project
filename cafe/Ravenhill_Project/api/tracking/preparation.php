<?php
/**
 * preparation.php
 * 8. Order Preparation and Tracking
 * FR29: Barista order display
 * FR30: Kitchen order display
 * FR31: Order status update
 * FR32: Order status tracking
 *
 * Routes:
 *   GET  /api/tracking/preparation.php?station=barista           — FR29: Barista beverage queue (coffee, customisations, shots, milk)
 *   GET  /api/tracking/preparation.php?station=kitchen           — FR30: Kitchen food prep queue (pastries, toasties, food items)
 *   POST /api/tracking/preparation.php?action=update_status      — FR31: Order status update (pending -> preparing -> ready -> completed)
 *   GET  /api/tracking/preparation.php?action=track&order_id=123 — FR32: Order status tracking for customer & front-of-house
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$station= $_GET['station'] ?? null;

// Gracefully ensure timestamps exist in Orders
try { $db->exec("ALTER TABLE Orders ADD COLUMN preparing_at DATETIME NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Orders ADD COLUMN ready_at DATETIME NULL"); } catch (Exception $e) {}

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET: FR29 (Barista), FR30 (Kitchen), or FR32 (Order Tracking) ──────────
if ($method === 'GET') {

    // FR32: Real-time Order Status Tracking (for Customer / Pickup Display / FOH)
    if ($action === 'track') {
        $orderId     = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
        $tableNumber = isset($_GET['table_number']) ? (int)$_GET['table_number'] : null;

        if (!$orderId && !$tableNumber) {
            sendResponse(false, 'Please provide order_id or table_number to track.', null, 422);
        }

        $sql = "
            SELECT o.order_id, o.order_status, o.order_type, o.table_number, o.created_at, o.preparing_at, o.ready_at, o.completed_at,
                   TIMESTAMPDIFF(SECOND, o.created_at, NOW()) AS elapsed_seconds,
                   CONCAT(c.first_name, ' ', COALESCE(c.last_name, '')) AS customer_name
            FROM Orders o
            LEFT JOIN Customers c ON o.customer_id = c.customer_id
            WHERE 1=1
        ";
        $params = [];

        if ($orderId) {
            $sql .= " AND o.order_id = ?";
            $params[] = $orderId;
        } elseif ($tableNumber) {
            $sql .= " AND o.table_number = ? AND o.order_status NOT IN ('completed', 'cancelled') ORDER BY o.created_at DESC LIMIT 1";
            $params[] = $tableNumber;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $order = $stmt->fetch();

        if (!$order) {
            sendResponse(false, 'Order not found or already completed.', null, 404);
        }

        // Compute progress stage
        $stages = [
            'pending'   => ['step' => 1, 'percent' => 25,  'label' => 'Order Received / In Queue', 'badge' => 'badge-warning'],
            'preparing' => ['step' => 2, 'percent' => 60,  'label' => 'Currently Being Prepared', 'badge' => 'badge-info'],
            'ready'     => ['step' => 3, 'percent' => 90,  'label' => 'Ready for Pickup / Table Service', 'badge' => 'badge-success'],
            'completed' => ['step' => 4, 'percent' => 100, 'label' => 'Order Completed', 'badge' => 'badge-primary'],
            'cancelled' => ['step' => 0, 'percent' => 0,   'label' => 'Order Cancelled', 'badge' => 'badge-danger']
        ];

        $status = strtolower($order['order_status']);
        $progress = $stages[$status] ?? ['step' => 1, 'percent' => 20, 'label' => ucfirst($status), 'badge' => 'badge-secondary'];

        // Fetch Items
        $itemsStmt = $db->prepare("
            SELECT oi.quantity, oi.unit_price, oi.customisations_json, oi.item_notes, p.product_name
            FROM OrderItems oi
            LEFT JOIN Products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ");
        $itemsStmt->execute([$order['order_id']]);
        $order['items'] = $itemsStmt->fetchAll();

        sendResponse(true, 'Live order tracking retrieved.', [
            'order_id'       => (int)$order['order_id'],
            'status'         => $status,
            'progress'       => $progress,
            'order_type'     => $order['order_type'],
            'table_number'   => $order['table_number'],
            'customer_name'  => $order['customer_name'] ?? 'Guest',
            'created_at'     => $order['created_at'],
            'elapsed_mins'   => (int)floor($order['elapsed_seconds'] / 60),
            'items'          => $order['items']
        ]);
    }

    // FR29 & FR30: Station Displays (Barista or Kitchen)
    $activeStatuses = ['pending', 'preparing', 'ready'];
    $inClause = implode(',', array_fill(0, count($activeStatuses), '?'));

    $ordersStmt = $db->prepare("
        SELECT o.order_id, o.order_status, o.order_type, o.table_number, o.notes, o.created_at,
               TIMESTAMPDIFF(SECOND, o.created_at, NOW()) AS elapsed_seconds,
               CONCAT(c.first_name, ' ', COALESCE(c.last_name, '')) AS customer_name,
               CONCAT(e.first_name, ' ', COALESCE(e.last_name, '')) AS server_name
        FROM Orders o
        LEFT JOIN Customers c ON o.customer_id = c.customer_id
        LEFT JOIN Employees e ON o.employee_id = e.employee_id
        WHERE o.order_status IN ($inClause)
        ORDER BY 
            CASE o.order_status
                WHEN 'preparing' THEN 1
                WHEN 'pending' THEN 2
                WHEN 'ready' THEN 3
                ELSE 4
            END,
            o.created_at ASC
    ");
    $ordersStmt->execute($activeStatuses);
    $allOrders = $ordersStmt->fetchAll();

    $stationTickets = [];

    foreach ($allOrders as $order) {
        // Fetch items matching the station type
        $itemSql = "
            SELECT oi.order_item_id, oi.product_id, oi.quantity, oi.customisations_json, oi.item_notes,
                   p.product_name, c.category_name
            FROM OrderItems oi
            LEFT JOIN Products p ON oi.product_id = p.product_id
            LEFT JOIN Categories c ON p.category_id = c.category_id
            WHERE oi.order_id = ?
        ";

        if ($station === 'barista') {
            // FR29: Filter coffee, tea, cold brew, beverage categories
            $itemSql .= " AND (c.category_name LIKE '%Coffee%' OR c.category_name LIKE '%Drink%' OR c.category_name LIKE '%Beverage%' OR c.category_name LIKE '%Brew%' OR c.category_name LIKE '%Espresso%' OR c.category_name IS NULL)";
        } elseif ($station === 'kitchen') {
            // FR30: Filter food, pastry, sandwich, breakfast categories
            $itemSql .= " AND (c.category_name LIKE '%Food%' OR c.category_name LIKE '%Pastry%' OR c.category_name LIKE '%Bakery%' OR c.category_name LIKE '%Toast%' OR c.category_name LIKE '%Breakfast%' OR c.category_name LIKE '%Kitchen%')";
        }

        $itemsQuery = $db->prepare($itemSql);
        $itemsQuery->execute([$order['order_id']]);
        $matchedItems = $itemsQuery->fetchAll();

        // If station filter active and no items matched this station, skip ticket
        if ($station && count($matchedItems) === 0) {
            continue;
        }

        foreach ($matchedItems as &$item) {
            $customs = !empty($item['customisations_json']) ? json_decode($item['customisations_json'], true) : [];
            $highlightTags = [];
            foreach ($customs as $c) {
                $highlightTags[] = $c['option_name'];
            }
            $item['customisations'] = $customs;
            $item['barista_highlight'] = count($highlightTags) > 0 ? implode(' • ', $highlightTags) : 'Standard';
        }

        $elapsedSeconds = (int)$order['elapsed_seconds'];
        $elapsedMins    = (int)floor($elapsedSeconds / 60);

        // Urgency flags
        $urgency = 'normal';
        if ($elapsedMins >= 8) {
            $urgency = 'critical'; // Red
        } elseif ($elapsedMins >= 4) {
            $urgency = 'warning';  // Amber
        }

        $stationTickets[] = [
            'order_id'        => (int)$order['order_id'],
            'order_status'    => $order['order_status'],
            'order_type'      => $order['order_type'],
            'table_number'    => $order['table_number'],
            'notes'           => $order['notes'],
            'customer_name'   => $order['customer_name'] ?? 'Walk-in',
            'server_name'     => $order['server_name'] ?? 'Staff',
            'created_at'      => $order['created_at'],
            'elapsed_seconds' => $elapsedSeconds,
            'elapsed_minutes' => $elapsedMins,
            'urgency'         => $urgency,
            'items'           => $matchedItems
        ];
    }

    $stationName = $station ? ucfirst($station) . ' Display' : 'Full KDS Queue';
    sendResponse(true, "$stationName tickets retrieved.", [
        'station'        => $station ?? 'all',
        'active_tickets' => count($stationTickets),
        'tickets'        => $stationTickets
    ]);
}

// ── POST: FR31 — Order Status Update (Barista/Kitchen/Staff) ───────────────
if ($method === 'POST' && $action === 'update_status') {
    $body = getRequestBody();

    $orderId   = isset($body['order_id']) ? (int)$body['order_id'] : (isset($_GET['order_id']) ? (int)$_GET['order_id'] : null);
    $newStatus = isset($body['status']) ? strtolower(trim($body['status'])) : null;

    if (!$orderId || !$newStatus) {
        sendResponse(false, 'Both order_id and status are required.', null, 422);
    }

    $allowed = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
    if (!in_array($newStatus, $allowed, true)) {
        sendResponse(false, 'Invalid status. Allowed values: ' . implode(', ', $allowed), null, 422);
    }

    $stmt = $db->prepare("SELECT * FROM Orders WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        sendResponse(false, 'Order not found.', null, 404);
    }

    // Set specific transition timestamps
    if ($newStatus === 'preparing') {
        $db->prepare("UPDATE Orders SET order_status = 'preparing', preparing_at = NOW() WHERE order_id = ?")->execute([$orderId]);
    } elseif ($newStatus === 'ready') {
        $db->prepare("UPDATE Orders SET order_status = 'ready', ready_at = NOW() WHERE order_id = ?")->execute([$orderId]);
    } elseif ($newStatus === 'completed') {
        $db->prepare("UPDATE Orders SET order_status = 'completed', completed_at = NOW() WHERE order_id = ?")->execute([$orderId]);
    } else {
        $db->prepare("UPDATE Orders SET order_status = ? WHERE order_id = ?")->execute([$newStatus, $orderId]);
    }

    sendResponse(true, "Order #$orderId status transitioned to $newStatus.", [
        'order_id'        => $orderId,
        'previous_status' => $order['order_status'],
        'new_status'      => $newStatus,
        'updated_at'      => date('Y-m-d H:i:s')
    ]);
}

sendResponse(false, 'Method not allowed.', null, 405);
