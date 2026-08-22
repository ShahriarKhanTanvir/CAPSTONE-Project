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

$format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : 'json';

if ($format === 'print' || $format === 'html') {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Receipt #<?php echo str_pad($orderId, 5, '0', STR_PAD_LEFT); ?> - Ravenhill Coffee</title>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
      <style>
        @page { size: 80mm auto; margin: 0; }
        body { margin: 0; padding: 15px; font-family: 'Courier New', Courier, monospace; background: #f3f4f6; color: #000; display: flex; flex-direction: column; align-items: center; }
        .receipt-card { background: #fff; width: 80mm; padding: 16px 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-radius: 4px; box-sizing: border-box; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .divider { margin: 8px 0; border-top: 1px dashed #000; }
        .r-row { display: flex; justify-content: space-between; font-size: 13px; margin: 3px 0; }
        .r-total { font-weight: bold; font-size: 15px; margin-top: 4px; }
        .action-bar { margin-top: 15px; display: flex; gap: 10px; }
        .btn { padding: 10px 18px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #d96b43; color: #fff; }
        .btn-secondary { background: #22c55e; color: #fff; }
        @media print {
          body { background: #fff; padding: 0; }
          .action-bar { display: none !important; }
          .receipt-card { box-shadow: none; width: 80mm; padding: 8px 4px; }
        }
      </style>
    </head>
    <body>
      <div class="receipt-card" id="receipt-area">
        <div class="text-center">
          <h2 style="margin: 0 0 4px 0; font-size: 18px;">RAVENHILL COFFEE</h2>
          <div style="font-size: 11px;"><?php echo htmlspecialchars($storeInfo['address']); ?></div>
          <div style="font-size: 11px;">ABN: <?php echo htmlspecialchars($storeInfo['abn']); ?> • Ph: <?php echo htmlspecialchars($storeInfo['phone']); ?></div>
        </div>
        <div class="divider"></div>
        <div style="font-size: 12px;">
          <div><strong>Order:</strong> #ORD-<?php echo str_pad($orderId, 5, '0', STR_PAD_LEFT); ?></div>
          <div><strong>Date:</strong> <?php echo date('d/m/Y h:i A', strtotime($order['created_at'])); ?></div>
          <div><strong>Type:</strong> <?php echo strtoupper($order['order_type']); ?><?php if (!empty($order['table_number'])) echo " (Table {$order['table_number']})"; ?></div>
          <div><strong>Server:</strong> <?php echo htmlspecialchars(!empty($order['server_name']) ? $order['server_name'] : 'Ravenhill Staff'); ?></div>
        </div>
        <div class="divider"></div>
        <?php foreach ($itemizedLines as $item): ?>
          <div class="r-row">
            <span><?php echo (int)$item['quantity']; ?>x <?php echo htmlspecialchars($item['product_name']); ?></span>
            <span>$<?php echo number_format($item['subtotal'], 2); ?></span>
          </div>
          <?php foreach ($item['customisations'] as $c): ?>
            <div style="font-size: 11px; color: #555; padding-left: 12px;"><?php echo htmlspecialchars($c); ?></div>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <div class="divider"></div>
        <div class="r-row"><span>Subtotal:</span><span>$<?php echo number_format($subtotal, 2); ?></span></div>
        <?php if ($discountAmount > 0): ?>
          <div class="r-row" style="color: #c00;"><span>Discount:</span><span>-$<?php echo number_format($discountAmount, 2); ?></span></div>
        <?php endif; ?>
        <div class="r-row"><span>GST Included (10%):</span><span>$<?php echo number_format($gstAmount, 2); ?></span></div>
        <div class="r-row r-total"><span>TOTAL AUD:</span><span>$<?php echo number_format($finalTotal, 2); ?></span></div>
        <div class="divider"></div>
        <?php if ($payment): ?>
          <div class="r-row"><span>Payment Method:</span><span><?php echo htmlspecialchars($payment['payment_method']); ?></span></div>
          <?php if ($payment['cash_tendered'] !== null): ?>
            <div class="r-row"><span>Cash Tendered:</span><span>$<?php echo number_format((float)$payment['cash_tendered'], 2); ?></span></div>
            <div class="r-row"><span>Change:</span><span>$<?php echo number_format((float)$payment['change_due'], 2); ?></span></div>
          <?php endif; ?>
        <?php endif; ?>
        <div class="divider"></div>
        <div class="text-center" style="font-size: 11px; margin-top: 10px;">
          <div>Thank you for visiting Ravenhill Coffee!</div>
          <div>Guest WiFi: <?php echo htmlspecialchars($storeInfo['wifi_code']); ?></div>
          <div style="margin-top: 8px; letter-spacing: 2px;">||||| ||||||| |||| |||||||| |||</div>
        </div>
      </div>

      <div class="action-bar">
        <button class="btn btn-primary" onclick="window.print()">Print Receipt</button>
        <button class="btn btn-secondary" onclick="downloadPDF()">Download PDF</button>
      </div>

      <script>
        async function downloadPDF() {
          const { jsPDF } = window.jspdf;
          const el = document.getElementById('receipt-area');
          const canvas = await html2canvas(el, { scale: 3, backgroundColor: '#ffffff' });
          const imgData = canvas.toDataURL('image/png');
          const imgWidth = 80;
          const pageHeight = (canvas.height * imgWidth) / canvas.width;
          const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: [imgWidth, pageHeight + 4] });
          pdf.addImage(imgData, 'PNG', 0, 2, imgWidth, pageHeight);
          pdf.save('Ravenhill_Receipt_ORD-<?php echo $orderId; ?>.pdf');
        }
      </script>
    </body>
    </html>
    <?php
    exit;
}

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
