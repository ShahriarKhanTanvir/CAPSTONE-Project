<?php
/**
 * dashboard.php
 * FR65: Role-based dashboard display
 *
 * Routes:
 *   GET /api/reports/dashboard.php — Get tailored KPIs based on user role (Admin, Manager, Cashier, Barista, Wait Staff)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db   = getDB();
$role = strtolower($_GET['role'] ?? ($_SESSION['role_name'] ?? 'admin'));

// Common metric: Today's date
$today = date('Y-m-d');

// ── 1. Admin & Manager Dashboard Metrics ───────────────────────────────────
if ($role === 'admin' || $role === 'manager') {
    // Total Revenue today
    $revStmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) AS today_revenue, COUNT(*) AS today_orders FROM Orders WHERE DATE(created_at) = ? AND order_status != 'cancelled'");
    $revStmt->execute([$today]);
    $salesRow = $revStmt->fetch();

    $todayRevenue = (float)$salesRow['today_revenue'];
    $todayOrders  = (int)$salesRow['today_orders'];
    $avgTicket    = $todayOrders > 0 ? round($todayRevenue / $todayOrders, 2) : 0.0;

    // Total Customers Count & Total Loyalty Points Issued
    $custRow = $db->query("SELECT COUNT(*) AS total_customers, COALESCE(SUM(loyalty_points), 0) AS total_points FROM Customers")->fetch();

    // Low stock / Out of stock count
    $stockRow = $db->query("SELECT COUNT(*) AS alert_count FROM Inventory WHERE quantity <= reorder_level")->fetch();

    // Table occupancy
    $tableRow = $db->query("SELECT COUNT(*) AS total_tables, SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) AS occupied_tables FROM DiningTables")->fetch();
    $totalTables    = (int)$tableRow['total_tables'];
    $occupiedTables = (int)$tableRow['occupied_tables'];
    $occupancyRate  = $totalTables > 0 ? round(($occupiedTables / $totalTables) * 100, 1) : 0;

    // Top Selling Products Today
    $topStmt = $db->prepare("
        SELECT p.product_name, SUM(oi.quantity) AS total_sold, SUM(oi.subtotal) AS gross_sales
        FROM OrderItems oi
        INNER JOIN Orders o ON oi.order_id = o.order_id
        INNER JOIN Products p ON oi.product_id = p.product_id
        WHERE DATE(o.created_at) = ? AND o.order_status != 'cancelled'
        GROUP BY p.product_id
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $topStmt->execute([$today]);
    $topProducts = $topStmt->fetchAll();

    // Feedback rating summary
    $fbRow = $db->query("SELECT ROUND(AVG(rating), 2) AS avg_rating, COUNT(*) AS count FROM Feedback")->fetch();

    sendResponse(true, 'Executive dashboard metrics retrieved.', [
        'role' => $role,
        'kpis' => [
            'today_revenue'     => $todayRevenue,
            'today_orders'      => $todayOrders,
            'avg_order_value'   => $avgTicket,
            'occupancy_rate'    => $occupancyRate . '%',
            'occupied_tables'   => $occupiedTables,
            'total_tables'      => $totalTables,
            'total_customers'   => (int)$custRow['total_customers'],
            'stock_alerts'      => (int)$stockRow['alert_count'],
            'store_rating'      => (float)($fbRow['avg_rating'] ?? 5.0),
            'reviews_count'     => (int)$fbRow['count']
        ],
        'top_selling_products' => $topProducts
    ]);
}

// ── 2. Cashier Dashboard Metrics ───────────────────────────────────────────
if ($role === 'cashier') {
    $cStmt = $db->prepare("
        SELECT COALESCE(SUM(p.amount), 0) AS total_collected,
               SUM(CASE WHEN LOWER(p.payment_method) LIKE '%cash%' THEN p.amount ELSE 0 END) AS cash_sales,
               SUM(CASE WHEN LOWER(p.payment_method) NOT LIKE '%cash%' THEN p.amount ELSE 0 END) AS electronic_sales,
               COUNT(p.payment_id) AS transactions_count
        FROM Payments p
        WHERE DATE(p.payment_date) = ? AND p.payment_status = 'paid'
    ");
    $cStmt->execute([$today]);
    $drawer = $cStmt->fetch();

    sendResponse(true, 'Cashier register dashboard metrics retrieved.', [
        'role' => 'cashier',
        'kpis' => [
            'shift_sales_total'   => (float)$drawer['total_collected'],
            'cash_in_drawer'      => (float)$drawer['cash_sales'],
            'card_digital_sales'  => (float)$drawer['electronic_sales'],
            'transactions_count'  => (int)$drawer['transactions_count']
        ]
    ]);
}

// ── 3. Barista & Wait Staff Dashboard Metrics ──────────────────────────────
if ($role === 'barista' || $role === 'waitstaff') {
    $qStmt = $db->query("
        SELECT COUNT(*) AS total_active,
               SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
               SUM(CASE WHEN order_status = 'preparing' THEN 1 ELSE 0 END) AS preparing_count,
               SUM(CASE WHEN order_status = 'ready' THEN 1 ELSE 0 END) AS ready_count,
               SUM(CASE WHEN order_status = 'completed' AND DATE(created_at) = '$today' THEN 1 ELSE 0 END) AS completed_today
        FROM Orders
    ");
    $queue = $qStmt->fetch();

    sendResponse(true, 'Operational station dashboard metrics retrieved.', [
        'role' => $role,
        'kpis' => [
            'active_tickets'   => (int)$queue['total_active'],
            'pending_orders'   => (int)$queue['pending_count'],
            'preparing_orders' => (int)$queue['preparing_count'],
            'ready_for_pickup' => (int)$queue['ready_count'],
            'completed_today'  => (int)$queue['completed_today']
        ]
    ]);
}

sendResponse(false, 'Role not recognized.', null, 400);
