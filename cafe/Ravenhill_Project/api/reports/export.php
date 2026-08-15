<?php
/**
 * export.php
 * FR68: Report export
 *
 * Routes:
 *   GET /api/reports/export.php?type=sales     — Download Sales CSV Report
 *   GET /api/reports/export.php?type=products  — Download Products Performance CSV
 *   GET /api/reports/export.php?type=inventory — Download Inventory Stock CSV
 *   GET /api/reports/export.php?type=audit     — Download System Audit Log CSV
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db   = getDB();
$type = $_GET['type'] ?? 'sales';

$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo   = $_GET['date_to'] ?? date('Y-m-d');

$filename = "ravenhill_{$type}_report_" . date('Ymd_His') . ".csv";

// Set CSV download headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// ── 1. Export Sales Report ─────────────────────────────────────────────────
if ($type === 'sales') {
    fputcsv($output, ['Date', 'Total Orders', 'Dine-In Orders', 'Takeaway Orders', 'Discounts (AUD)', 'Gross Sales (AUD)', 'GST Included (10%)']);

    $stmt = $db->prepare("
        SELECT DATE(created_at) AS sale_date,
               COUNT(*) AS total_orders,
               SUM(CASE WHEN order_type = 'dine-in' THEN 1 ELSE 0 END) AS dine_in_orders,
               SUM(CASE WHEN order_type = 'takeaway' THEN 1 ELSE 0 END) AS takeaway_orders,
               COALESCE(SUM(discount_amount), 0) AS total_discounts,
               COALESCE(SUM(total_amount), 0) AS gross_sales,
               ROUND(COALESCE(SUM(total_amount), 0) / 11.0, 2) AS gst_portion
        FROM Orders
        WHERE order_status != 'cancelled'
          AND DATE(created_at) >= ? AND DATE(created_at) <= ?
        GROUP BY DATE(created_at)
        ORDER BY sale_date DESC
    ");
    $stmt->execute([$dateFrom, $dateTo]);

    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        fputcsv($output, $row);
    }
}

// ── 2. Export Products Performance ─────────────────────────────────────────
elseif ($type === 'products') {
    fputcsv($output, ['Product ID', 'Product Name', 'Category', 'Current Price (AUD)', 'Units Sold', 'Total Revenue (AUD)']);

    $stmt = $db->prepare("
        SELECT p.product_id, p.product_name, c.category_name, p.price,
               COALESCE(SUM(oi.quantity), 0) AS total_units_sold,
               COALESCE(SUM(oi.subtotal), 0) AS total_revenue
        FROM Products p
        LEFT JOIN Categories c ON p.category_id = c.category_id
        LEFT JOIN OrderItems oi ON p.product_id = oi.product_id
        LEFT JOIN Orders o ON oi.order_id = o.order_id AND o.order_status != 'cancelled' AND (DATE(o.created_at) >= ? AND DATE(o.created_at) <= ?)
        GROUP BY p.product_id
        ORDER BY total_revenue DESC
    ");
    $stmt->execute([$dateFrom, $dateTo]);

    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        fputcsv($output, $row);
    }
}

// ── 3. Export Inventory Stock ──────────────────────────────────────────────
elseif ($type === 'inventory') {
    fputcsv($output, ['Inventory ID', 'Item Name', 'Current Stock', 'Unit', 'Unit Cost (AUD)', 'Reorder Level', 'Status', 'Asset Value (AUD)', 'Supplier']);

    $stmt = $db->query("
        SELECT i.inventory_id, COALESCE(i.item_name, p.product_name, 'Raw Item') AS item_name,
               i.quantity, i.unit, i.unit_cost, i.reorder_level, i.status,
               ROUND(i.quantity * i.unit_cost, 2) AS total_asset_value,
               s.supplier_name
        FROM Inventory i
        LEFT JOIN Products p ON i.product_id = p.product_id
        LEFT JOIN Suppliers s ON i.supplier_id = s.supplier_id
        ORDER BY i.inventory_id ASC
    ");

    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        fputcsv($output, $row);
    }
}

// ── 4. Export Audit Logs ───────────────────────────────────────────────────
elseif ($type === 'audit') {
    fputcsv($output, ['Log ID', 'User ID', 'User Name', 'Action', 'Table Affected', 'Details', 'Timestamp', 'IP Address']);

    $stmt = $db->query("SELECT log_id, user_id, user_name, action, table_name, details, timestamp, ip_address FROM AuditLogs ORDER BY timestamp DESC");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit;
