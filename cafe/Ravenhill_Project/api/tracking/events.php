<?php
/**
 * events.php
 * NFR03: Real-time order status updates via Server-Sent Events (SSE)
 *
 * Route:
 *   GET /api/tracking/events.php — Real-time event stream for KDS, POS, and Order Status screens
 */

require_once __DIR__ . '/../config/db.php';

// Set SSE Headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable FastCGI buffer for Nginx/Apache

// Turn off output buffering
while (ob_get_level()) ob_end_flush();
flush();

$db = getDB();
$lastCheck = time();

// Keep SSE connection alive and stream active orders
for ($i = 0; $i < 10; $i++) {
    // Fetch live active orders
    $stmt = $db->query("
        SELECT order_id, order_status, order_type, table_number, total_amount, created_at,
               TIMESTAMPDIFF(SECOND, created_at, NOW()) AS elapsed_seconds
        FROM Orders
        WHERE order_status IN ('pending', 'preparing', 'ready')
        ORDER BY created_at ASC
    ");
    $activeOrders = $stmt->fetchAll();

    $data = json_encode([
        'timestamp'      => date('Y-m-d H:i:s'),
        'active_count'   => count($activeOrders),
        'orders'         => $activeOrders
    ]);

    echo "event: order_update\n";
    echo "data: {$data}\n\n";

    flush();
    sleep(2); // Broadcast every 2 seconds
}
