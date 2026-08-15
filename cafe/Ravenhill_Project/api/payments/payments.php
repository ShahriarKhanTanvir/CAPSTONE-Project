<?php
/**
 * payments.php
 * 9. Payment and Receipt Management
 * FR33: Payment processing
 * FR34: Payment method selection
 * FR35: Payment status management
 * FR36: Receipt generation (via receipt.php)
 *
 * Routes:
 *   POST   /api/payments/payments.php                  — FR33: Process payment (Cash, Card, Digital Wallet, Loyalty)
 *   PATCH  /api/payments/payments.php?id=123&action=status — FR35: Manage payment status (paid, refunded, voided, partially_paid)
 *   POST   /api/payments/payments.php?id=123&action=refund — FR35: Refund transaction with reason
 *   GET    /api/payments/payments.php                  — List payments (supports ?order_id=, ?date=, ?status=)
 *   GET    /api/payments/payments.php?id=123           — Get single payment record
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';
require_once __DIR__ . '/../reports/audit.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;

// Gracefully ensure extra columns exist in Payments
try { $db->exec("ALTER TABLE Payments ADD COLUMN refund_reason TEXT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Payments ADD COLUMN refunded_at DATETIME NULL"); } catch (Exception $e) {}

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── POST: FR33 — Payment Processing ────────────────────────────────────────
if ($method === 'POST' && !$action) {
    $body = getRequestBody();

    $required = ['order_id', 'payment_method', 'amount'];
    foreach ($required as $field) {
        if (!isset($body[$field]) || $body[$field] === '') {
            sendResponse(false, "Missing required field: $field", null, 422);
        }
    }

    $orderId       = (int)$body['order_id'];
    $methodName    = trim($body['payment_method']);
    $amount        = (float)$body['amount'];
    $tipAmount     = isset($body['tip_amount']) ? (float)$body['tip_amount'] : 0.0;
    $cashTendered  = isset($body['cash_tendered']) ? (float)$body['cash_tendered'] : null;
    $splitIndex    = isset($body['split_index']) ? (int)$body['split_index'] : 1;
    $notes         = $body['notes'] ?? null;
    $paymentStatus = !empty($body['payment_status']) ? $body['payment_status'] : 'paid';

    // Verify order
    $oStmt = $db->prepare("SELECT * FROM Orders WHERE order_id = ?");
    $oStmt->execute([$orderId]);
    $order = $oStmt->fetch();

    if (!$order) {
        sendResponse(false, 'Order not found.', null, 404);
    }

    // Cash change calculation
    $changeDue = null;
    if (stripos($methodName, 'cash') !== false && $cashTendered !== null) {
        if ($cashTendered < ($amount + $tipAmount)) {
            sendResponse(false, 'Cash tendered is less than the payable amount.', null, 422);
        }
        $changeDue = round($cashTendered - ($amount + $tipAmount), 2);
    }

    $txnRef = !empty($body['transaction_reference']) 
        ? trim($body['transaction_reference']) 
        : 'TXN-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));

    $db->beginTransaction();

    try {
        $stmt = $db->prepare("
            INSERT INTO Payments (order_id, amount, payment_method, payment_status, payment_date, tip_amount, cash_tendered, change_due, split_index, transaction_reference, notes)
            VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $orderId,
            $amount,
            $methodName,
            $paymentStatus,
            $tipAmount,
            $cashTendered,
            $changeDue,
            $splitIndex,
            $txnRef,
            $notes
        ]);
        $paymentId = (int)$db->lastInsertId();

        // Check if order is fully paid
        $sumStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) AS paid_total FROM Payments WHERE order_id = ? AND payment_status = 'paid'");
        $sumStmt->execute([$orderId]);
        $paidTotal = (float)$sumStmt->fetch()['paid_total'];

        $orderTotal = (float)$order['total_amount'];
        $isFullyPaid = $paidTotal >= $orderTotal;

        if ($isFullyPaid) {
            $db->prepare("UPDATE Orders SET payment_status = 'paid' WHERE order_id = ?")->execute([$orderId]);
        }

        $db->commit();

        logAudit('PAYMENT_PROCESSED', 'Payments', "Processed payment of $$amount for Order #$orderId via $methodName ($txnRef)");

        sendResponse(true, 'Payment processed successfully.', [
            'payment_id'            => $paymentId,
            'order_id'              => $orderId,
            'amount_paid'           => $amount,
            'total_order_amount'    => $orderTotal,
            'total_paid_so_far'     => $paidTotal,
            'is_fully_paid'         => $isFullyPaid,
            'payment_method'        => $methodName,
            'payment_status'        => $paymentStatus,
            'tip_amount'            => $tipAmount,
            'cash_tendered'         => $cashTendered,
            'change_due'            => $changeDue,
            'transaction_reference' => $txnRef,
            'payment_date'          => date('Y-m-d H:i:s')
        ], 201);

    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(false, 'Payment processing failed: ' . $e->getMessage(), null, 400);
    }
}

// ── PATCH: FR35 — Payment Status Management ────────────────────────────────
if ($method === 'PATCH' && $action === 'status') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Payment ID is required.', null, 422);
    }

    $body = getRequestBody();
    if (empty($body['payment_status'])) {
        sendResponse(false, 'New payment_status is required.', null, 422);
    }

    $newStatus = strtolower(trim($body['payment_status']));
    $allowed   = ['pending', 'paid', 'partially_paid', 'refunded', 'voided'];

    if (!in_array($newStatus, $allowed, true)) {
        sendResponse(false, 'Invalid payment status. Allowed: ' . implode(', ', $allowed), null, 422);
    }

    $check = $db->prepare("SELECT * FROM Payments WHERE payment_id = ?");
    $check->execute([$id]);
    $payment = $check->fetch();

    if (!$payment) {
        sendResponse(false, 'Payment record not found.', null, 404);
    }

    $db->prepare("UPDATE Payments SET payment_status = ? WHERE payment_id = ?")->execute([$newStatus, $id]);

    logAudit('PAYMENT_STATUS_UPDATE', 'Payments', "Updated Payment #$id status to $newStatus");

    sendResponse(true, "Payment status updated to $newStatus.", [
        'payment_id'     => $id,
        'payment_status' => $newStatus
    ]);
}

// ── POST: FR35 — Process Refund ────────────────────────────────────────────
if ($method === 'POST' && $action === 'refund') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Payment ID is required for refund.', null, 422);
    }

    $body   = getRequestBody();
    $reason = $body['refund_reason'] ?? 'Customer request / Item issue';

    $check = $db->prepare("SELECT * FROM Payments WHERE payment_id = ?");
    $check->execute([$id]);
    $payment = $check->fetch();

    if (!$payment) {
        sendResponse(false, 'Payment record not found.', null, 404);
    }

    if ($payment['payment_status'] === 'refunded') {
        sendResponse(false, 'Payment is already marked as refunded.', null, 400);
    }

    $db->prepare("
        UPDATE Payments 
        SET payment_status = 'refunded', refund_reason = ?, refunded_at = NOW() 
        WHERE payment_id = ?
    ")->execute([$reason, $id]);

    logAudit('PAYMENT_REFUND', 'Payments', "Refunded Payment #$id ($" . $payment['amount'] . "). Reason: $reason");

    sendResponse(true, "Payment #$id refunded successfully.", [
        'payment_id'     => $id,
        'amount_refunded'=> (float)$payment['amount'],
        'payment_status' => 'refunded',
        'refund_reason'  => $reason,
        'refunded_at'    => date('Y-m-d H:i:s')
    ]);
}

// ── GET: List or Retrieve Payment Transactions ─────────────────────────────
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("
            SELECT p.*, o.total_amount, o.order_type, o.table_number, o.created_at AS order_created_at
            FROM Payments p
            LEFT JOIN Orders o ON p.order_id = o.order_id
            WHERE p.payment_id = ?
        ");
        $stmt->execute([$id]);
        $pay = $stmt->fetch();

        if (!$pay) {
            sendResponse(false, 'Payment transaction not found.', null, 404);
        }

        sendResponse(true, 'Payment transaction retrieved.', $pay);
    }

    $orderFilter  = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
    $statusFilter = $_GET['status'] ?? null;
    $methodFilter = $_GET['method'] ?? null;
    $dateFilter   = $_GET['date'] ?? null;

    $sql = "
        SELECT p.*, o.order_type, o.table_number,
               CONCAT(c.first_name, ' ', COALESCE(c.last_name, '')) AS customer_name
        FROM Payments p
        LEFT JOIN Orders o ON p.order_id = o.order_id
        LEFT JOIN Customers c ON o.customer_id = c.customer_id
        WHERE 1=1
    ";
    $params = [];

    if ($orderFilter) {
        $sql .= " AND p.order_id = ?";
        $params[] = $orderFilter;
    }

    if ($statusFilter) {
        $sql .= " AND LOWER(p.payment_status) = LOWER(?)";
        $params[] = $statusFilter;
    }

    if ($methodFilter) {
        $sql .= " AND LOWER(p.payment_method) LIKE LOWER(?)";
        $params[] = '%' . $methodFilter . '%';
    }

    if ($dateFilter) {
        $sql .= " AND DATE(p.payment_date) = ?";
        $params[] = $dateFilter;
    }

    $sql .= " ORDER BY p.payment_date DESC LIMIT 100";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();

    sendResponse(true, 'Payment transactions retrieved.', [
        'count'        => count($payments),
        'transactions' => $payments
    ]);
}

sendResponse(false, 'Method not allowed.', null, 405);
