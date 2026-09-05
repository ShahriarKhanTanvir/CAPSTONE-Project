<?php
/**
 * customer_order.php - Live Customer Order Tracking Engine
 * Ravenhill Coffee POS & Management System
 *
 * Routes:
 *   GET /api/orders/customer_order.php?order_id=123   — Live order tracking state
 *   GET /api/orders/customer_order.php?latest=1       — Latest customer order
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$db = getDB();
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$latest  = isset($_GET['latest']) ? (bool)$_GET['latest'] : false;

if ($orderId <= 0 && $latest) {
    $latestStmt = $db->query("SELECT order_id FROM Orders ORDER BY order_id DESC LIMIT 1");
    $row = $latestStmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $orderId = (int)$row['order_id'];
    }
}

if ($orderId <= 0) {
    sendResponse(false, 'Valid order_id is required.', null, 422);
}

// Fetch Master Order
$stmt = $db->prepare("
    SELECT o.order_id, o.order_status, o.order_type, o.table_number, o.notes, o.total_amount,
           o.created_at, o.completed_at,
           TIMESTAMPDIFF(SECOND, o.created_at, NOW()) AS elapsed_seconds,
           CONCAT(c.first_name, ' ', COALESCE(c.last_name, '')) AS customer_name
    FROM Orders o
    LEFT JOIN Customers c ON o.customer_id = c.customer_id
    WHERE o.order_id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    sendResponse(false, 'Order not found.', null, 404);
}

// Fetch Station Sub-Tickets
$ticketStmt = $db->prepare("
    SELECT ticket_id, station, status, target_prep_minutes, started_at, ready_at, collected_at
    FROM StationTickets
    WHERE order_id = ?
");
$ticketStmt->execute([$orderId]);
$tickets = $ticketStmt->fetchAll(PDO::FETCH_ASSOC);

$baristaStatus = 'none';
$kitchenStatus = 'none';

foreach ($tickets as $t) {
    if ($t['station'] === 'barista') $baristaStatus = $t['status'];
    if ($t['station'] === 'kitchen') $kitchenStatus = $t['status'];
}

if ($baristaStatus === 'none' && $kitchenStatus === 'none') {
    $masterSt = strtolower($order['order_status'] ?? 'pending');
    $baristaStatus = $masterSt;
    $kitchenStatus = $masterSt;
}

// Fetch Line Items with Station tagging
$itemsStmt = $db->prepare("
    SELECT oi.order_item_id, oi.quantity, oi.unit_price, oi.subtotal, oi.customisations_json, oi.item_notes,
           p.product_name, cat.category_name,
           CASE WHEN p.category_id BETWEEN 1 AND 7 THEN 'barista' ELSE 'kitchen' END as station
    FROM OrderItems oi
    LEFT JOIN Products p ON oi.product_id = p.product_id
    LEFT JOIN Categories cat ON p.category_id = cat.category_id
    WHERE oi.order_id = ?
");
$itemsStmt->execute([$orderId]);
$rawItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

$foodItems = [];
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
    $item['mods_text'] = count($customTags) > 0 ? implode(' • ', $customTags) : '';

    if ($item['station'] === 'kitchen') {
        $item['status'] = ($kitchenStatus === 'ready' || $kitchenStatus === 'collected') ? 'Ready for Collection' : ($kitchenStatus === 'preparing' ? 'Preparing' : 'In Queue');
        $item['status_code'] = $kitchenStatus;
        $foodItems[] = $item;
    } else {
        $item['status'] = ($baristaStatus === 'ready' || $baristaStatus === 'collected') ? 'Ready for Collection' : ($baristaStatus === 'preparing' ? 'Preparing' : 'In Queue');
        $item['status_code'] = $baristaStatus;
        $drinkItems[] = $item;
    }
}

// Calculate Customer Overall Message and Progress Stepper
$displayStatus = 'received';
$statusMessage = 'Your order has been received and sent to the bar & kitchen.';
$stepIndex = 1; // 1: Placed, 2: Preparing, 3: Ready for Pickup, 4: Completed
$estimatedWait = 5; // Default minutes

if ($kitchenStatus !== 'none') $estimatedWait = 12;

if ($order['order_status'] === 'completed' || ($baristaStatus === 'collected' && ($kitchenStatus === 'collected' || $kitchenStatus === 'none'))) {
    $displayStatus = 'completed';
    $statusMessage = 'Your order has been served. Enjoy your meal and coffee!';
    $stepIndex = 4;
} elseif ($order['order_status'] === 'ready' || (($baristaStatus === 'ready' || $baristaStatus === 'collected') && ($kitchenStatus === 'ready' || $kitchenStatus === 'none'))) {
    $displayStatus = 'ready_for_pickup';
    $statusMessage = 'All items are ready for pickup! Please collect at the counter.';
    $stepIndex = 3;
} elseif ($baristaStatus === 'ready' && $kitchenStatus === 'preparing') {
    $displayStatus = 'partially_ready';
    $statusMessage = 'Your coffee is ready for pickup! Food is sizzling on the kitchen grill.';
    $stepIndex = 2;
} elseif ($kitchenStatus === 'ready' && $baristaStatus === 'preparing') {
    $displayStatus = 'partially_ready';
    $statusMessage = 'Your food is ready! The barista is finishing your drinks.';
    $stepIndex = 2;
} elseif ($order['order_status'] === 'preparing' || $baristaStatus === 'preparing' || $kitchenStatus === 'preparing') {
    $displayStatus = 'preparing';
    $statusMessage = 'Your order is being prepared fresh right now.';
    $stepIndex = 2;
}

sendResponse(true, 'Customer order tracking state retrieved.', [
    'order_id'          => $orderId,
    'pickup_number'     => '#' . str_pad($orderId, 3, '0', STR_PAD_LEFT),
    'order_type'        => $order['order_type'],
    'table_number'      => $order['table_number'],
    'customer_name'     => $order['customer_name'] ?: 'Guest',
    'created_at'        => $order['created_at'],
    'elapsed_minutes'   => (int)floor((int)$order['elapsed_seconds'] / 60),
    'estimated_wait'    => max(1, $estimatedWait - (int)floor((int)$order['elapsed_seconds'] / 60)),
    'step_index'        => $stepIndex,
    'display_status'    => $displayStatus,
    'status_message'    => $statusMessage,
    'station_breakdown' => [
        'barista' => [
            'has_items' => $baristaStatus !== 'none',
            'status'    => $baristaStatus,
            'label'     => $baristaStatus === 'ready' ? 'Ready for Collection' : ($baristaStatus === 'preparing' ? 'Preparing at Coffee Bar' : 'In Drink Queue')
        ],
        'kitchen' => [
            'has_items' => $kitchenStatus !== 'none',
            'status'    => $kitchenStatus,
            'label'     => $kitchenStatus === 'ready' ? 'Ready for Collection' : ($kitchenStatus === 'preparing' ? 'Preparing in Kitchen' : 'In Food Queue')
        ]
    ],
    'food_items'        => $foodItems,
    'drink_items'       => $drinkItems,
    'total_amount'      => (float)$order['total_amount']
]);
