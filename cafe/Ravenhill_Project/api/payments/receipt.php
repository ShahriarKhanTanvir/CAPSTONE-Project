<?php
/**
 * receipt.php
 * FR36: Receipt generation
 *
 * Routes:
 *   GET /api/payments/receipt.php?order_id=123 — Generate formatted digital & thermal receipt
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db      = getDB();
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;

if (!$orderId) {
    sendResponse(false, 'order_id is required to generate receipt.', null, 422);
}

// Fetch order
$stmt = $db->prepare("
    SELECT o.*, 
           CONCAT(c.first_name, ' ', COALESCE(c.last_name, '')) AS customer_name,
           c.phone AS customer_phone, c.email AS customer_email,
           CONCAT(e.first_name, ' ', COALESCE(e.last_name, '')) AS server_name
    FROM Orders o
    LEFT JOIN Customers c ON o.customer_id = c.customer_id
    LEFT JOIN Employees e ON o.employee_id = e.employee_id
    WHERE o.order_id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    sendResponse(false, 'Order not found.', null, 404);
}

// Fetch line items
$itemsStmt = $db->prepare("
    SELECT oi.*, p.product_name
    FROM OrderItems oi
    LEFT JOIN Products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

// Fetch payment info
$payStmt = $db->prepare("SELECT * FROM Payments WHERE order_id = ? ORDER BY payment_id DESC LIMIT 1");
$payStmt->execute([$orderId]);
$payment = $payStmt->fetch();

$storeInfo = [
    'name'     => 'Ravenhill Coffee Roasters & Cafe',
    'address'  => '142 Flinders Lane, Melbourne VIC 3000',
    'abn'      => '88 123 456 789',
    'phone'    => '(03) 9876 5432',
    'wifi_code'=> 'RavenhillSpecialty2026'
];

$subtotal       = 0.0;
$itemizedLines  = [];
$thermalLines   = [];

$thermalLines[] = "================================";
$thermalLines[] = "   RAVENHILL COFFEE ROASTERS    ";
$thermalLines[] = " 142 Flinders Lane, Melbourne   ";
$thermalLines[] = "        ABN 88 123 456 789      ";
$thermalLines[] = "================================";
$thermalLines[] = "Order #: #" . str_pad($orderId, 5, '0', STR_PAD_LEFT);
$thermalLines[] = "Date:    " . date('d/m/Y H:i', strtotime($order['created_at']));
$thermalLines[] = "Type:    " . strtoupper($order['order_type']) . (!empty($order['table_number']) ? " (Table {$order['table_number']})" : "");
$thermalLines[] = "Server:  " . (!empty($order['server_name']) ? $order['server_name'] : 'Ravenhill Staff');
$thermalLines[] = "--------------------------------";

foreach ($items as $item) {
    $qty       = (int)$item['quantity'];
    $unitPrice = (float)$item['unit_price'];
    $lineTotal = (float)$item['subtotal'];
    $subtotal += $lineTotal;
    $customs   = !empty($item['customisations_json']) ? json_decode($item['customisations_json'], true) : [];

    $modText = [];
    foreach ($customs as $c) {
        $modText[] = "+ " . $c['option_name'] . ($c['extra_price'] > 0 ? " ($" . number_format($c['extra_price'], 2) . ")" : "");
    }

    $itemizedLines[] = [
        'product_name'   => $item['product_name'],
        'quantity'       => $qty,
        'unit_price'     => $unitPrice,
        'subtotal'       => $lineTotal,
        'customisations' => $modText,
        'item_notes'     => $item['item_notes']
    ];

    $lineStr = sprintf("%-2d %-19s %6.2f", $qty, substr($item['product_name'], 0, 19), $lineTotal);
    $thermalLines[] = $lineStr;
    foreach ($modText as $m) {
        $thermalLines[] = "   " . substr($m, 0, 28);
    }
}

$discountAmount = (float)$order['discount_amount'];
$finalTotal     = (float)$order['total_amount'];
$gstAmount      = round($finalTotal / 11.0, 2);

$thermalLines[] = "--------------------------------";
$thermalLines[] = sprintf("%-22s %8.2f", "Subtotal:", $subtotal);
if ($discountAmount > 0) {
    $thermalLines[] = sprintf("%-22s -%7.2f", "Discount ({$order['discount_code']}):", $discountAmount);
}
$thermalLines[] = sprintf("%-22s %8.2f", "TOTAL (AUD):", $finalTotal);
$thermalLines[] = sprintf("%-22s %8.2f", "Includes GST (10%):", $gstAmount);
$thermalLines[] = "--------------------------------";

if ($payment) {
    $thermalLines[] = "Payment: " . $payment['payment_method'];
    $thermalLines[] = "Ref:     " . $payment['transaction_reference'];
    if ($payment['cash_tendered'] !== null) {
        $thermalLines[] = sprintf("%-22s %8.2f", "Cash Tendered:", (float)$payment['cash_tendered']);
        $thermalLines[] = sprintf("%-22s %8.2f", "Change Due:", (float)$payment['change_due']);
    }
} else {
    $thermalLines[] = "Payment: PENDING AT REGISTER";
}

$thermalLines[] = "================================";
$thermalLines[] = "    Thank you for visiting!     ";
$thermalLines[] = " Guest WiFi: {$storeInfo['wifi_code']} ";
$thermalLines[] = "================================";

sendResponse(true, 'Receipt generated.', [
    'receipt_number' => 'REC-' . str_pad($orderId, 6, '0', STR_PAD_LEFT),
    'store'          => $storeInfo,
    'order'          => [
        'order_id'        => $orderId,
        'order_type'      => $order['order_type'],
        'table_number'    => $order['table_number'],
        'created_at'      => $order['created_at'],
        'customer_name'   => $order['customer_name'] ?? 'Walk-in Guest',
        'server_name'     => $order['server_name'] ?? 'Staff'
    ],
    'line_items'     => $itemizedLines,
    'totals'         => [
        'subtotal'        => round($subtotal, 2),
        'discount_code'   => $order['discount_code'],
        'discount_amount' => round($discountAmount, 2),
        'total_amount'    => round($finalTotal, 2),
        'gst_included'    => $gstAmount
    ],
    'payment'        => $payment,
    'thermal_text'   => implode("\n", $thermalLines)
]);
