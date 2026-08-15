<?php
/**
 * reports.php
 * FR66: Report generation
 * FR67: Report filtering
 *
 * Routes:
 *   GET /api/reports/reports.php?type=sales     — Sales summary report (filterable by ?date_from=, ?date_to=, ?period=)
 *   GET /api/reports/reports.php?type=products  — Product performance / items sold report
 *   GET /api/reports/reports.php?type=hourly    — Hourly sales & order density report
 *   GET /api/reports/reports.php?type=payments  — Payment method breakdown report
 *   GET /api/reports/reports.php?type=inventory — Inventory stock valuation & usage report
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db   = getDB();
$type = $_GET['type'] ?? 'sales';

// ── FR67: Report Filtering (Date Ranges) ───────────────────────────────────
$period   = $_GET['period'] ?? null;
$dateFrom = $_GET['date_from'] ?? null;
$dateTo   = $_GET['date_to'] ?? null;

if ($period === 'today') {
    $dateFrom = date('Y-m-d');
    $dateTo   = date('Y-m-d');
} elseif ($period === 'yesterday') {
    $dateFrom = date('Y-m-d', strtotime('-1 day'));
    $dateTo   = date('Y-m-d', strtotime('-1 day'));
} elseif ($period === 'this_week') {
    $dateFrom = date('Y-m-d', strtotime('monday this week'));
    $dateTo   = date('Y-m-d');
} elseif ($period === 'this_month') {
    $dateFrom = date('Y-m-01');
    $dateTo   = date('Y-m-d');
} elseif (!$dateFrom && !$dateTo) {
    // Default to last 30 days
    $dateFrom = date('Y-m-d', strtotime('-30 days'));
    $dateTo   = date('Y-m-d');
}

// ── 1. Sales Summary Report ────────────────────────────────────────────────
if ($type === 'sales') {
    $sql = "
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
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$dateFrom, $dateTo]);
    $dailySales = $stmt->fetchAll();

    $totalRevenue   = 0.0;
    $totalOrdersSum = 0;
    $totalDiscounts = 0.0;

    foreach ($dailySales as &$d) {
        $d['gross_sales']     = (float)$d['gross_sales'];
        $d['total_discounts'] = (float)$d['total_discounts'];
        $d['gst_portion']     = (float)$d['gst_portion'];
        $d['total_orders']    = (int)$d['total_orders'];

        $totalRevenue   += $d['gross_sales'];
        $totalOrdersSum += $d['total_orders'];
        $totalDiscounts += $d['total_discounts'];
    }

    sendResponse(true, 'Sales report generated.', [
        'report_type'  => 'sales',
        'date_range'   => ['from' => $dateFrom, 'to' => $dateTo],
        'totals'       => [
            'total_revenue'   => round($totalRevenue, 2),
            'total_orders'    => $totalOrdersSum,
            'total_discounts' => round($totalDiscounts, 2),
            'total_gst'       => round($totalRevenue / 11.0, 2),
            'avg_order_value' => $totalOrdersSum > 0 ? round($totalRevenue / $totalOrdersSum, 2) : 0.0
        ],
        'daily_breakdown' => $dailySales
    ]);
}

// ── 2. Product Performance Report ──────────────────────────────────────────
if ($type === 'products') {
    $catFilter = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;

    $sql = "
        SELECT p.product_id, p.product_name, c.category_name, p.price AS current_price,
               COALESCE(SUM(oi.quantity), 0) AS total_units_sold,
               COALESCE(SUM(oi.subtotal), 0) AS total_revenue
        FROM Products p
        LEFT JOIN Categories c ON p.category_id = c.category_id
        LEFT JOIN OrderItems oi ON p.product_id = oi.product_id
        LEFT JOIN Orders o ON oi.order_id = o.order_id AND o.order_status != 'cancelled' AND (DATE(o.created_at) >= ? AND DATE(o.created_at) <= ?)
        WHERE 1=1
    ";
    $params = [$dateFrom, $dateTo];

    if ($catFilter) {
        $sql .= " AND p.category_id = ?";
        $params[] = $catFilter;
    }

    $sql .= " GROUP BY p.product_id ORDER BY total_revenue DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    foreach ($products as &$p) {
        $p['total_units_sold'] = (int)$p['total_units_sold'];
        $p['total_revenue']    = round((float)$p['total_revenue'], 2);
    }

    sendResponse(true, 'Product sales performance report generated.', [
        'report_type' => 'products',
        'date_range'  => ['from' => $dateFrom, 'to' => $dateTo],
        'products'    => $products
    ]);
}

// ── 3. Hourly Sales Density Report ─────────────────────────────────────────
if ($type === 'hourly') {
    $stmt = $db->prepare("
        SELECT HOUR(created_at) AS hour_of_day,
               COUNT(*) AS orders_count,
               ROUND(COALESCE(SUM(total_amount), 0), 2) AS hourly_sales
        FROM Orders
        WHERE order_status != 'cancelled'
          AND DATE(created_at) >= ? AND DATE(created_at) <= ?
        GROUP BY HOUR(created_at)
        ORDER BY hour_of_day ASC
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $hourly = $stmt->fetchAll();

    sendResponse(true, 'Hourly sales density report generated.', [
        'report_type' => 'hourly',
        'date_range'  => ['from' => $dateFrom, 'to' => $dateTo],
        'hourly'      => $hourly
    ]);
}

// ── 4. Payment Methods Report ──────────────────────────────────────────────
if ($type === 'payments') {
    $stmt = $db->prepare("
        SELECT p.payment_method,
               COUNT(*) AS transaction_count,
               ROUND(COALESCE(SUM(p.amount), 0), 2) AS total_collected,
               ROUND(COALESCE(SUM(p.tip_amount), 0), 2) AS total_tips
        FROM Payments p
        WHERE p.payment_status = 'paid'
          AND DATE(p.payment_date) >= ? AND DATE(p.payment_date) <= ?
        GROUP BY p.payment_method
        ORDER BY total_collected DESC
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $payments = $stmt->fetchAll();

    sendResponse(true, 'Payment methods report generated.', [
        'report_type' => 'payments',
        'date_range'  => ['from' => $dateFrom, 'to' => $dateTo],
        'payments'    => $payments
    ]);
}

// ── 5. Inventory Stock Valuation Report ────────────────────────────────────
if ($type === 'inventory') {
    $stmt = $db->query("
        SELECT i.inventory_id, COALESCE(i.item_name, p.product_name, 'Raw Item') AS item_name,
               i.quantity, i.unit, i.unit_cost, i.reorder_level, i.status,
               ROUND(i.quantity * i.unit_cost, 2) AS total_asset_value,
               s.supplier_name
        FROM Inventory i
        LEFT JOIN Products p ON i.product_id = p.product_id
        LEFT JOIN Suppliers s ON i.supplier_id = s.supplier_id
        ORDER BY total_asset_value DESC
    ");
    $inv = $stmt->fetchAll();

    $totalValuation = 0.0;
    foreach ($inv as $row) {
        $totalValuation += (float)$row['total_asset_value'];
    }

    sendResponse(true, 'Inventory valuation report generated.', [
        'report_type'     => 'inventory',
        'total_valuation' => round($totalValuation, 2),
        'items_count'     => count($inv),
        'inventory'       => $inv
    ]);
}

sendResponse(false, 'Unknown report type requested.', null, 400);
