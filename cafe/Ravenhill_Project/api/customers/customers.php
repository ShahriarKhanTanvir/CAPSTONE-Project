<?php
/**
 * customers.php
 * FR5: Customer Registration
 * FR6: Customer Information Storage
 * FR7: Customer Search and Update
 * FR8: Customer History Viewing
 *
 * Routes:
 *   POST   /api/customers/customers.php                    — FR5/FR6: Register new customer
 *   GET    /api/customers/customers.php                    — FR7: List / search customers
 *   GET    /api/customers/customers.php?id=123            — FR6: Get single customer
 *   PUT    /api/customers/customers.php?id=123            — FR7: Update customer info
 *   GET    /api/customers/customers.php?id=123&view=history — FR8: Order & loyalty history
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$view   = $_GET['view'] ?? null;

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── POST: FR5 & FR6 — Register a new customer ─────────────────────────────
if ($method === 'POST') {
    $body = getRequestBody();

    if (empty($body['first_name'])) {
        sendResponse(false, 'first_name is required.', null, 422);
    }

    // Prevent duplicate by email if provided
    if (!empty($body['email'])) {
        $check = $db->prepare("SELECT customer_id FROM Customers WHERE email = ?");
        $check->execute([$body['email']]);
        if ($check->fetch()) {
            sendResponse(false, 'A customer with this email already exists.', null, 409);
        }
    }

    $stmt = $db->prepare("
        INSERT INTO Customers (first_name, last_name, phone, email, loyalty_points)
        VALUES (?, ?, ?, ?, 0)
    ");
    $stmt->execute([
        $body['first_name'],
        $body['last_name'] ?? null,
        $body['phone']     ?? null,
        $body['email']     ?? null,
    ]);

    $newId = (int)$db->lastInsertId();

    sendResponse(true, 'Customer registered successfully.', [
        'customer_id' => $newId,
        'first_name'  => $body['first_name'],
        'last_name'   => $body['last_name'] ?? null,
        'email'       => $body['email']     ?? null,
        'phone'       => $body['phone']     ?? null,
        'loyalty_points' => 0,
    ], 201);
}

// ── GET: various read operations ───────────────────────────────────────────
if ($method === 'GET') {

    // FR8: Customer order + loyalty history
    if ($id && $view === 'history') {
        // Order history
        $orders = $db->prepare("
            SELECT o.order_id, o.total_amount, o.order_status, o.created_at,
                   p.payment_method, p.payment_status
            FROM Orders o
            LEFT JOIN Payments p ON p.order_id = o.order_id
            WHERE o.customer_id = ?
            ORDER BY o.created_at DESC
        ");
        $orders->execute([$id]);
        $orderList = $orders->fetchAll();

        // Loyalty transaction history
        $loyalty = $db->prepare("
            SELECT transaction_id, order_id, points_earned, points_redeemed, transaction_date
            FROM LoyaltyTransactions
            WHERE customer_id = ?
            ORDER BY transaction_date DESC
        ");
        $loyalty->execute([$id]);
        $loyaltyList = $loyalty->fetchAll();

        // Current points balance
        $balance = $db->prepare("SELECT loyalty_points FROM Customers WHERE customer_id = ?");
        $balance->execute([$id]);
        $customer = $balance->fetch();

        sendResponse(true, 'Customer history retrieved.', [
            'loyalty_points'      => $customer['loyalty_points'] ?? 0,
            'orders'              => $orderList,
            'loyalty_transactions'=> $loyaltyList,
        ]);
    }

    // FR6: Get a single customer by ID
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM Customers WHERE customer_id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();

        if (!$customer) {
            sendResponse(false, 'Customer not found.', null, 404);
        }

        sendResponse(true, 'Customer retrieved.', $customer);
    }

    // FR7: Search or list all customers
    $search = $_GET['search'] ?? '';
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 20;
    $offset = ($page - 1) * $limit;

    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt = $db->prepare("
            SELECT * FROM Customers
            WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?
            ORDER BY customer_id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$like, $like, $like, $like, $limit, $offset]);
    } else {
        $stmt = $db->prepare("SELECT * FROM Customers ORDER BY customer_id DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
    }

    $customers = $stmt->fetchAll();

    // Total count for pagination
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM Customers");
    $countStmt->execute();
    $total = $countStmt->fetch()['total'];

    sendResponse(true, 'Customers retrieved.', [
        'customers' => $customers,
        'total'     => (int)$total,
        'page'      => $page,
        'limit'     => $limit,
    ]);
}

// ── PUT: FR7 — Update customer information ─────────────────────────────────
if ($method === 'PUT') {
    if (!$id) {
        sendResponse(false, 'Customer ID is required for update.', null, 422);
    }

    $body = getRequestBody();

    $allowed = ['first_name', 'last_name', 'phone', 'email', 'loyalty_points'];
    $fields  = [];
    $values  = [];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $fields[] = "$field = ?";
            $values[] = $body[$field];
        }
    }

    if (empty($fields)) {
        sendResponse(false, 'No valid fields provided for update.', null, 422);
    }

    // Confirm customer exists
    $check = $db->prepare("SELECT customer_id FROM Customers WHERE customer_id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(false, 'Customer not found.', null, 404);
    }

    $values[] = $id;
    $db->prepare("UPDATE Customers SET " . implode(', ', $fields) . " WHERE customer_id = ?")
       ->execute($values);

    sendResponse(true, 'Customer information updated successfully.');
}

sendResponse(false, 'Method not allowed.', null, 405);
