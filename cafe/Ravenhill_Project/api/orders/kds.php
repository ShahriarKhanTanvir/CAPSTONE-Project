<?php
/**
 * kds.php - Role-Based Multi-Station Kitchen Display System Engine
 * Ravenhill Coffee POS & Management System
 *
 * Routes:
 *   GET  /api/orders/kds.php?station=kitchen   — Food queue for Kitchen Staff
 *   GET  /api/orders/kds.php?station=barista   — Beverage queue for Barista with batch counts
 *   GET  /api/orders/kds.php?station=all       — Full queue for Expo / Cashier / Manager
 *   POST /api/orders/kds.php?action=bump_ticket&ticket_id=123 — Bump ticket (pending -> preparing -> ready -> collected)
 *   POST /api/orders/kds.php?action=recall_ticket&ticket_id=123 — Recall recently bumped ticket
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
$stationParam = isset($_GET['station']) ? strtolower(trim($_GET['station'])) : 'all';

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET: Active KDS Station Queue ──────────────────────────────────────────
if ($method === 'GET') {
    $whereStation = "";
    $params = [];

    if ($stationParam === 'kitchen' || $stationParam === 'barista') {
        $whereStation = "AND st.station = ?";
        $params[] = $stationParam;
    }

    // Fetch active station tickets (pending, preparing, ready)
    $sql = "
        SELECT st.ticket_id, st.order_id, st.station, st.status AS ticket_status, 
               st.target_prep_minutes, st.started_at, st.ready_at, st.created_at,
               TIMESTAMPDIFF(SECOND, st.created_at, NOW()) AS elapsed_seconds,
               o.order_status AS master_order_status, o.order_type, o.table_number, o.notes AS order_notes,
               CONCAT(c.first_name, ' ', COALESCE(c.last_name, '')) AS customer_name,
               CONCAT(e.first_name, ' ', COALESCE(e.last_name, '')) AS server_name
        FROM StationTickets st
        INNER JOIN Orders o ON st.order_id = o.order_id
        LEFT JOIN Customers c ON o.customer_id = c.customer_id
        LEFT JOIN Employees e ON o.employee_id = e.employee_id
        WHERE st.status IN ('pending', 'preparing', 'ready')
        $whereStation
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
        'extra_shots'  => 0
    ];

    foreach ($tickets as &$ticket) {
        $tId = (int)$ticket['ticket_id'];
        $oId = (int)$ticket['order_id'];
        $tStation = $ticket['station'];

        // Fetch line items for this ticket
        $itemsStmt = $db->prepare("
            SELECT oi.order_item_id, oi.product_id, oi.quantity, oi.item_notes, oi.customisations_json,
                   p.product_name, cat.category_name, COALESCE(cat.target_station, 'barista') AS target_station
            FROM OrderItems oi
            LEFT JOIN Products p ON oi.product_id = p.product_id
            LEFT JOIN Categories cat ON p.category_id = cat.category_id
            WHERE (oi.ticket_id = ? OR (oi.ticket_id IS NULL AND oi.order_id = ? AND (cat.target_station = ? OR (cat.target_station IS NULL AND ? = 'barista'))))
        ");
        $itemsStmt->execute([$tId, $oId, $tStation, $tStation]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            $customs = !empty($item['customisations_json']) ? json_decode($item['customisations_json'], true) : [];
            $customTags = [];
            
            if (is_array($customs)) {
                foreach ($customs as $c) {
                    $optName = $c['option_name'] ?? '';
                    $customTags[] = $optName;

                    // Barista batch counters
                    if ($tStation === 'barista') {
                        if (stripos($optName, 'oat') !== false) $batchSummary['oat_milk'] += (int)$item['quantity'];
                        if (stripos($optName, 'almond') !== false) $batchSummary['almond_milk'] += (int)$item['quantity'];
                        if (stripos($optName, 'soy') !== false) $batchSummary['soy_milk'] += (int)$item['quantity'];
                        if (stripos($optName, 'decaf') !== false) $batchSummary['decaf'] += (int)$item['quantity'];
                        if (stripos($optName, 'extra shot') !== false || stripos($optName, 'double') !== false) $batchSummary['extra_shots'] += (int)$item['quantity'];
                    }
                }
            }

            $item['customisations'] = $customs;
            $item['kds_highlight']  = count($customTags) > 0 ? implode(' • ', $customTags) : 'Standard';
        }

        $ticket['items']           = $items;
        $ticket['elapsed_seconds'] = (int)$ticket['elapsed_seconds'];
        $ticket['elapsed_minutes'] = (int)floor($ticket['elapsed_seconds'] / 60);
        $ticket['urgency']         = $ticket['elapsed_minutes'] > 7 ? 'high' : ($ticket['elapsed_minutes'] > 3 ? 'medium' : 'normal');

        // Also fetch sibling ticket statuses for master status indicator
        $sibStmt = $db->prepare("SELECT station, status FROM StationTickets WHERE order_id = ?");
        $sibStmt->execute([$oId]);
        $ticket['station_statuses'] = $sibStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    sendResponse(true, 'KDS queue retrieved.', [
        'station'              => $stationParam,
        'active_tickets_count' => count($tickets),
        'batch_summary'        => $batchSummary,
        'tickets'              => $tickets
    ]);
}

// ── POST: Bump Ticket ───────────────────────────────────────────────────────
if ($method === 'POST' && ($action === 'bump_ticket' || $action === 'bump')) {
    if (!$ticketId) {
        $raw = file_get_contents('php://input');
        $b = json_decode($raw, true) ?? [];
        $ticketId = (int)($b['ticket_id'] ?? $_GET['id'] ?? 0);
    }

    if (!$ticketId) {
        sendResponse(false, 'ticket_id is required.', null, 422);
    }

    $stmt = $db->prepare("SELECT ticket_id, order_id, station, status FROM StationTickets WHERE ticket_id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        sendResponse(false, 'Ticket not found.', null, 404);
    }

    $current = $ticket['status'];
    $orderId = (int)$ticket['order_id'];
    $next = 'pending';

    if ($current === 'pending') {
        $next = 'preparing';
        $db->prepare("UPDATE StationTickets SET status = 'preparing', started_at = NOW() WHERE ticket_id = ?")->execute([$ticketId]);
    } elseif ($current === 'preparing') {
        $next = 'ready';
        $db->prepare("UPDATE StationTickets SET status = 'ready', ready_at = NOW() WHERE ticket_id = ?")->execute([$ticketId]);
    } elseif ($current === 'ready') {
        $next = 'collected';
        $db->prepare("UPDATE StationTickets SET status = 'collected', collected_at = NOW() WHERE ticket_id = ?")->execute([$ticketId]);
    } else {
        sendResponse(false, "Ticket is already in state: $current", null, 400);
    }

    // Recalculate Master Order Status from all sibling tickets
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

    sendResponse(true, "Ticket bumped to $next.", [
        'ticket_id'           => $ticketId,
        'order_id'            => $orderId,
        'station'             => $ticket['station'],
        'previous_status'     => $current,
        'new_status'          => $next,
        'master_order_status' => $masterStatus
    ]);
}

// ── POST: Recall Ticket ─────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'recall_ticket') {
    if (!$ticketId) {
        $raw = file_get_contents('php://input');
        $b = json_decode($raw, true) ?? [];
        $ticketId = (int)($b['ticket_id'] ?? 0);
    }

    if (!$ticketId) {
        sendResponse(false, 'ticket_id is required for recall.', null, 422);
    }

    $stmt = $db->prepare("SELECT ticket_id, order_id, station, status FROM StationTickets WHERE ticket_id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        sendResponse(false, 'Ticket not found.', null, 404);
    }

    $current = $ticket['status'];
    $orderId = (int)$ticket['order_id'];
    $prev = 'pending';

    if ($current === 'collected') {
        $prev = 'ready';
    } elseif ($current === 'ready') {
        $prev = 'preparing';
    } elseif ($current === 'preparing') {
        $prev = 'pending';
    } else {
        sendResponse(false, "Cannot recall ticket from state: $current", null, 400);
    }

    $db->prepare("UPDATE StationTickets SET status = ? WHERE ticket_id = ?")->execute([$prev, $ticketId]);
    $db->prepare("UPDATE Orders SET order_status = 'preparing' WHERE order_id = ?")->execute([$orderId]);

    sendResponse(true, "Ticket recalled to $prev.", [
        'ticket_id'   => $ticketId,
        'order_id'    => $orderId,
        'new_status'  => $prev
    ]);
}

sendResponse(false, 'Method not allowed or invalid action.', null, 405);
