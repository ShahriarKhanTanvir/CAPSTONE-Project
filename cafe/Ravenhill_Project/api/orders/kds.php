<?php
/**
 * kds.php - Enterprise Multi-Station Kitchen Display & Wait Staff Engine
 * Ravenhill Coffee POS & Management System
 *
 * Workflows:
 *   - Kitchen Dashboard: Food items only (Categories 8-14)
 *   - Barista Dashboard: Beverages only (Categories 1-7)
 *   - Wait Staff Dashboard: Full orders with side-by-side Kitchen & Barista status
 *
 * Routes:
 *   GET  /api/orders/kds.php?station=kitchen   — Food queue for Kitchen Staff
 *   GET  /api/orders/kds.php?station=barista   — Drink queue for Barista
 *   GET  /api/orders/kds.php?station=waitstaff — Full order monitor for Wait Staff
 *   GET  /api/orders/kds.php?station=all       — Master Expo view
 *   POST /api/orders/kds.php?action=set_status — Set specific status (pending | preparing | ready | collected)
 *   POST /api/orders/kds.php?action=bump_ticket— Advance ticket stage
 *   POST /api/orders/kds.php?action=serve_order— Wait staff marks entire order as served/completed
 *   POST /api/orders/kds.php?action=recall_ticket— Recall recently bumped ticket
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : null;
$orderId  = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
$stationParam = isset($_GET['station']) ? strtolower(trim($_GET['station'])) : 'all';
$statusFilter = isset($_GET['status_filter']) ? strtolower(trim($_GET['status_filter'])) : 'active';

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Auto-sync missing StationTickets for any active orders
try {
    $missingStmt = $db->query("
        SELECT o.order_id, o.order_status, o.created_at
        FROM Orders o
        LEFT JOIN StationTickets st ON o.order_id = st.order_id
        WHERE o.order_status IN ('pending', 'preparing', 'ready')
          AND st.ticket_id IS NULL
    ");
    $missingOrders = $missingStmt ? $missingStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($missingOrders as $mo) {
        $moid = (int)$mo['order_id'];
        $mStatus = in_array($mo['order_status'], ['pending', 'preparing', 'ready']) ? $mo['order_status'] : 'pending';

        $itStmt = $db->prepare("
            SELECT DISTINCT CASE WHEN p.category_id BETWEEN 1 AND 7 THEN 'barista' ELSE 'kitchen' END AS station
            FROM OrderItems oi
            JOIN Products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ");
        $itStmt->execute([$moid]);
        $stList = $itStmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($stList)) {
            $stList = ['barista'];
        }

        foreach ($stList as $st) {
            $mins = $st === 'kitchen' ? 12 : 4;
            $ins = $db->prepare("INSERT INTO StationTickets (order_id, station, status, target_prep_minutes, created_at) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$moid, $st, $mStatus, $mins, $mo['created_at']]);
        }
    }
} catch (Exception $e) {}

// ── GET: Active KDS & Wait Staff Queue ──────────────────────────────────────
if ($method === 'GET') {

    // ────────────────────────────────────────────────────────────────────────
    // 1. WAIT STAFF DASHBOARD (Full Orders with Kitchen & Barista Breakdown)
    // ────────────────────────────────────────────────────────────────────────
    if ($stationParam === 'waitstaff') {
        $statusWhere = $statusFilter === 'completed' ? "WHERE o.order_status = 'completed'" : "WHERE o.order_status IN ('pending', 'preparing', 'ready')";

        $sql = "
            SELECT o.order_id, o.order_status AS master_status, o.order_type, o.table_number,
                   o.notes AS order_notes, o.created_at, o.completed_at,
                   TIMESTAMPDIFF(SECOND, o.created_at, NOW()) AS elapsed_seconds,
                   CONCAT(c.first_name, ' ', COALESCE(c.last_name, '')) AS customer_name,
                   CONCAT(e.first_name, ' ', COALESCE(e.last_name, '')) AS server_name
            FROM Orders o
            LEFT JOIN Customers c ON o.customer_id = c.customer_id
            LEFT JOIN Employees e ON o.employee_id = e.employee_id
            $statusWhere
            ORDER BY 
                CASE o.order_status 
                    WHEN 'ready' THEN 1 
                    WHEN 'preparing' THEN 2 
                    WHEN 'pending' THEN 3 
                    ELSE 4 
                END,
                o.created_at ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $readyToServeCount = 0;

        foreach ($orders as &$ord) {
            $oId = (int)$ord['order_id'];

            // Fetch Station Tickets for this order
            $stStmt = $db->prepare("SELECT ticket_id, station, status, started_at, ready_at, collected_at FROM StationTickets WHERE order_id = ?");
            $stStmt->execute([$oId]);
            $tickets = $stStmt->fetchAll(PDO::FETCH_ASSOC);

            $kitchenStatus = 'none';
            $baristaStatus = 'none';
            $kitchenTicketId = null;
            $baristaTicketId = null;

            foreach ($tickets as $t) {
                if ($t['station'] === 'kitchen') {
                    $kitchenStatus = $t['status'];
                    $kitchenTicketId = (int)$t['ticket_id'];
                }
                if ($t['station'] === 'barista') {
                    $baristaStatus = $t['status'];
                    $baristaTicketId = (int)$t['ticket_id'];
                }
            }

            // Fetch all line items with station tagging
            $itemsStmt = $db->prepare("
                SELECT oi.order_item_id, oi.product_id, oi.quantity, oi.item_notes, oi.customisations_json,
                       p.product_name, cat.category_name,
                       CASE WHEN p.category_id BETWEEN 1 AND 7 THEN 'barista' ELSE 'kitchen' END AS station
                FROM OrderItems oi
                LEFT JOIN Products p ON oi.product_id = p.product_id
                LEFT JOIN Categories cat ON p.category_id = cat.category_id
                WHERE oi.order_id = ?
            ");
            $itemsStmt->execute([$oId]);
            $rawItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            $foodItems  = [];
            $drinkItems = [];

            foreach ($rawItems as $item) {
                $customs = !empty($item['customisations_json']) ? json_decode($item['customisations_json'], true) : [];
                $customTags = [];
                if (is_array($customs)) {
                    foreach ($customs as $c) {
                        $customTags[] = $c['option_name'] ?? '';
                    }
                }
                $item['customisations'] = $customs;
                $item['notes_highlight'] = count($customTags) > 0 ? implode(' • ', $customTags) : ($item['item_notes'] ?: 'Standard');

                if ($item['station'] === 'kitchen') {
                    $foodItems[] = $item;
                } else {
                    $drinkItems[] = $item;
                }
            }

            $ord['kitchen_status']    = $kitchenStatus;
            $ord['barista_status']    = $baristaStatus;
            $ord['kitchen_ticket_id'] = $kitchenTicketId;
            $ord['barista_ticket_id'] = $baristaTicketId;
            $ord['food_items']        = $foodItems;
            $ord['drink_items']       = $drinkItems;
            $ord['elapsed_seconds']   = (int)$ord['elapsed_seconds'];
            $ord['elapsed_minutes']   = (int)floor($ord['elapsed_seconds'] / 60);
            $ord['urgency']           = $ord['elapsed_minutes'] > 7 ? 'high' : ($ord['elapsed_minutes'] > 3 ? 'medium' : 'normal');

            // Flag if any part is ready for collection
            $isReadyToServe = ($kitchenStatus === 'ready' || $baristaStatus === 'ready' || $ord['master_status'] === 'ready');
            $ord['is_ready_to_serve'] = $isReadyToServe;
            if ($isReadyToServe) $readyToServeCount++;
        }

        sendResponse(true, 'Wait staff queue retrieved.', [
            'station'              => 'waitstaff',
            'active_orders_count'  => count($orders),
            'ready_to_serve_count' => $readyToServeCount,
            'orders'               => $orders
        ]);
        exit;
    }

    // ────────────────────────────────────────────────────────────────────────
    // 2. KITCHEN OR BARISTA DASHBOARD
    // ────────────────────────────────────────────────────────────────────────
    $whereStation = "";
    $params = [];

    if ($stationParam === 'kitchen' || $stationParam === 'barista') {
        $whereStation = "AND st.station = ?";
        $params[] = $stationParam;
    }

    $statusWhere = "";
    if ($statusFilter === 'pending') {
        $statusWhere = "AND st.status = 'pending'";
    } elseif ($statusFilter === 'preparing') {
        $statusWhere = "AND st.status = 'preparing'";
    } elseif ($statusFilter === 'ready') {
        $statusWhere = "AND st.status = 'ready'";
    } elseif ($statusFilter === 'completed') {
        $statusWhere = "AND st.status = 'collected'";
    } else {
        $statusWhere = "AND st.status IN ('pending', 'preparing', 'ready')";
    }

    $sql = "
        SELECT st.ticket_id, st.order_id, st.station, st.status AS ticket_status, 
               st.target_prep_minutes, st.started_at, st.ready_at, st.collected_at, st.created_at,
               TIMESTAMPDIFF(SECOND, st.created_at, NOW()) AS elapsed_seconds,
               o.order_status AS master_order_status, o.order_type, o.table_number, o.notes AS order_notes,
               CONCAT(c.first_name, ' ', COALESCE(c.last_name, '')) AS customer_name,
               CONCAT(e.first_name, ' ', COALESCE(e.last_name, '')) AS server_name
        FROM StationTickets st
        INNER JOIN Orders o ON st.order_id = o.order_id
        LEFT JOIN Customers c ON o.customer_id = c.customer_id
        LEFT JOIN Employees e ON o.employee_id = e.employee_id
        WHERE 1=1
        $whereStation
        $statusWhere
        ORDER BY 
            CASE st.status 
                WHEN 'preparing' THEN 1 
                WHEN 'pending' THEN 2 
                WHEN 'ready' THEN 3 
                ELSE 4 
            END,
            st.created_at ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $batchSummary = [
        'oat_milk'     => 0,
        'almond_milk'  => 0,
        'soy_milk'     => 0,
        'full_cream'   => 0,
        'decaf'        => 0,
        'extra_shots'  => 0,
        'extra_hot'    => 0
    ];

    foreach ($tickets as &$ticket) {
        $tId = (int)$ticket['ticket_id'];
        $oId = (int)$ticket['order_id'];
        $tStation = $ticket['station'];

        // Strict line items filter by station
        $itemsStmt = $db->prepare("
            SELECT oi.order_item_id, oi.product_id, oi.quantity, oi.item_notes, oi.customisations_json,
                   p.product_name, cat.category_name,
                   CASE WHEN p.category_id BETWEEN 1 AND 7 THEN 'barista' ELSE 'kitchen' END AS station
            FROM OrderItems oi
            LEFT JOIN Products p ON oi.product_id = p.product_id
            LEFT JOIN Categories cat ON p.category_id = cat.category_id
            WHERE (oi.ticket_id = ? OR (oi.order_id = ? AND (CASE WHEN p.category_id BETWEEN 1 AND 7 THEN 'barista' ELSE 'kitchen' END = ?)))
        ");
        $itemsStmt->execute([$tId, $oId, $tStation]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            $customs = !empty($item['customisations_json']) ? json_decode($item['customisations_json'], true) : [];
            $customTags = [];
            $hasSpecialNotes = false;
            
            if (is_array($customs)) {
                foreach ($customs as $c) {
                    $optName = $c['option_name'] ?? '';
                    $customTags[] = $optName;

                    if ($tStation === 'barista') {
                        if (stripos($optName, 'oat') !== false) $batchSummary['oat_milk'] += (int)$item['quantity'];
                        if (stripos($optName, 'almond') !== false) $batchSummary['almond_milk'] += (int)$item['quantity'];
                        if (stripos($optName, 'soy') !== false) $batchSummary['soy_milk'] += (int)$item['quantity'];
                        if (stripos($optName, 'decaf') !== false) $batchSummary['decaf'] += (int)$item['quantity'];
                        if (stripos($optName, 'extra shot') !== false || stripos($optName, 'double') !== false) $batchSummary['extra_shots'] += (int)$item['quantity'];
                        if (stripos($optName, 'extra hot') !== false) $batchSummary['extra_hot'] += (int)$item['quantity'];
                    }
                }
            }

            if (!empty($item['item_notes'])) {
                $hasSpecialNotes = true;
            }

            $item['customisations']      = $customs;
            $item['kds_highlight']       = count($customTags) > 0 ? implode(' • ', $customTags) : 'Standard';
            $item['has_special_notes']   = $hasSpecialNotes;
        }

        $ticket['items']           = $items;
        $ticket['elapsed_seconds'] = (int)$ticket['elapsed_seconds'];
        $ticket['elapsed_minutes'] = (int)floor($ticket['elapsed_seconds'] / 60);
        $ticket['urgency']         = $ticket['elapsed_minutes'] > 7 ? 'high' : ($ticket['elapsed_minutes'] > 3 ? 'medium' : 'normal');

        // Sibling statuses
        $sibStmt = $db->prepare("SELECT station, status FROM StationTickets WHERE order_id = ?");
        $sibStmt->execute([$oId]);
        $ticket['sibling_statuses'] = $sibStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    sendResponse(true, 'KDS queue retrieved.', [
        'station'              => $stationParam,
        'active_tickets_count' => count($tickets),
        'batch_summary'        => $batchSummary,
        'tickets'              => $tickets
    ]);
}

// ── POST: Explicit Set Status (New Order / Preparing / Ready for Collection)
if ($method === 'POST' && ($action === 'set_status' || $action === 'bump_ticket' || $action === 'bump')) {
    $raw = file_get_contents('php://input');
    $b = json_decode($raw, true) ?? [];

    $tId = $ticketId ?: (int)($b['ticket_id'] ?? $_GET['id'] ?? 0);
    $targetStatus = trim($b['status'] ?? $_GET['status'] ?? '');

    if (!$tId) {
        sendResponse(false, 'ticket_id is required.', null, 422);
    }

    $stmt = $db->prepare("SELECT ticket_id, order_id, station, status FROM StationTickets WHERE ticket_id = ?");
    $stmt->execute([$tId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        sendResponse(false, 'Ticket not found.', null, 404);
    }

    $current = $ticket['status'];
    $orderId = (int)$ticket['order_id'];

    if (empty($targetStatus)) {
        // Auto bump progression
        if ($current === 'pending') $next = 'preparing';
        elseif ($current === 'preparing') $next = 'ready';
        elseif ($current === 'ready') $next = 'collected';
        else $next = $current;
    } else {
        $valid = ['pending', 'preparing', 'ready', 'collected'];
        if (!in_array($targetStatus, $valid)) {
            sendResponse(false, "Invalid status: $targetStatus", null, 422);
        }
        $next = $targetStatus;
    }

    if ($next === 'preparing') {
        $db->prepare("UPDATE StationTickets SET status = 'preparing', started_at = COALESCE(started_at, NOW()) WHERE ticket_id = ?")->execute([$tId]);
    } elseif ($next === 'ready') {
        $db->prepare("UPDATE StationTickets SET status = 'ready', ready_at = NOW() WHERE ticket_id = ?")->execute([$tId]);
    } elseif ($next === 'collected') {
        $db->prepare("UPDATE StationTickets SET status = 'collected', collected_at = NOW() WHERE ticket_id = ?")->execute([$tId]);
    } else {
        $db->prepare("UPDATE StationTickets SET status = 'pending' WHERE ticket_id = ?")->execute([$tId]);
    }

    // Recalculate Master Order Status
    $allStmt = $db->prepare("SELECT status FROM StationTickets WHERE order_id = ?");
    $allStmt->execute([$orderId]);
    $allStatuses = $allStmt->fetchAll(PDO::FETCH_COLUMN);

    $masterStatus = 'pending';
    if (count($allStatuses) > 0) {
        $allCollected = true;
        $allReadyOrHigher = true;
        $anyPreparing = false;

        foreach ($allStatuses as $st) {
            if ($st !== 'collected') $allCollected = false;
            if ($st !== 'ready' && $st !== 'collected') $allReadyOrHigher = false;
            if ($st === 'preparing' || $st === 'ready') $anyPreparing = true;
        }

        if ($allCollected) {
            $masterStatus = 'completed';
            $db->prepare("UPDATE Orders SET order_status = 'completed', completed_at = NOW() WHERE order_id = ?")->execute([$orderId]);
        } elseif ($allReadyOrHigher) {
            $masterStatus = 'ready';
            $db->prepare("UPDATE Orders SET order_status = 'ready' WHERE order_id = ?")->execute([$orderId]);
        } elseif ($anyPreparing) {
            $masterStatus = 'preparing';
            $db->prepare("UPDATE Orders SET order_status = 'preparing' WHERE order_id = ?")->execute([$orderId]);
        } else {
            $masterStatus = 'pending';
            $db->prepare("UPDATE Orders SET order_status = 'pending' WHERE order_id = ?")->execute([$orderId]);
        }
    }

    sendResponse(true, "Ticket updated to $next.", [
        'ticket_id'           => $tId,
        'order_id'            => $orderId,
        'station'             => $ticket['station'],
        'previous_status'     => $current,
        'new_status'          => $next,
        'master_order_status' => $masterStatus
    ]);
}

// ── POST: Wait Staff Serve Order ────────────────────────────────────────────
if ($method === 'POST' && ($action === 'serve_order' || $action === 'complete_order')) {
    $raw = file_get_contents('php://input');
    $b = json_decode($raw, true) ?? [];
    $oId = $orderId ?: (int)($b['order_id'] ?? $_GET['id'] ?? 0);

    if (!$oId) {
        sendResponse(false, 'order_id is required.', null, 422);
    }

    // Mark all station tickets as collected
    $db->prepare("UPDATE StationTickets SET status = 'collected', collected_at = NOW() WHERE order_id = ?")->execute([$oId]);
    
    // Mark order completed
    $db->prepare("UPDATE Orders SET order_status = 'completed', completed_at = NOW() WHERE order_id = ?")->execute([$oId]);

    // Free up table if dine-in
    $tCheck = $db->prepare("SELECT table_number FROM Orders WHERE order_id = ?");
    $tCheck->execute([$oId]);
    $tRow = $tCheck->fetch(PDO::FETCH_ASSOC);
    if ($tRow && !empty($tRow['table_number'])) {
        $db->prepare("UPDATE DiningTables SET status = 'available' WHERE table_number = ?")->execute([(int)$tRow['table_number']]);
    }

    sendResponse(true, "Order #$oId marked as Served & Completed by Wait Staff.", [
        'order_id'     => $oId,
        'order_status' => 'completed'
    ]);
}

// ── POST: Recall Ticket ─────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'recall_ticket') {
    $raw = file_get_contents('php://input');
    $b = json_decode($raw, true) ?? [];
    $tId = $ticketId ?: (int)($b['ticket_id'] ?? 0);

    if (!$tId) {
        sendResponse(false, 'ticket_id is required for recall.', null, 422);
    }

    $stmt = $db->prepare("SELECT ticket_id, order_id, station, status FROM StationTickets WHERE ticket_id = ?");
    $stmt->execute([$tId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        sendResponse(false, 'Ticket not found.', null, 404);
    }

    $current = $ticket['status'];
    $orderId = (int)$ticket['order_id'];
    $prev = 'pending';

    if ($current === 'collected') $prev = 'ready';
    elseif ($current === 'ready') $prev = 'preparing';
    elseif ($current === 'preparing') $prev = 'pending';

    $db->prepare("UPDATE StationTickets SET status = ? WHERE ticket_id = ?")->execute([$prev, $tId]);
    $db->prepare("UPDATE Orders SET order_status = 'preparing' WHERE order_id = ?")->execute([$orderId]);

    sendResponse(true, "Ticket recalled to $prev.", [
        'ticket_id'   => $tId,
        'order_id'    => $orderId,
        'new_status'  => $prev
    ]);
}

sendResponse(false, 'Method not allowed or invalid action.', null, 405);
