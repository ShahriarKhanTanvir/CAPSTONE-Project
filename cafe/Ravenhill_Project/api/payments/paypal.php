<?php
/**
 * paypal.php - PayPal Payment Processing REST Endpoints
 * Ravenhill Coffee POS System
 *
 * Endpoints:
 *   GET  /api/payments/paypal.php?action=config        - Public config for frontend SDK
 *   POST /api/payments/paypal.php?action=create_order   - Create PayPal transaction order
 *   POST /api/payments/paypal.php?action=capture_order  - Capture approved PayPal payment & persist to DB
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/paypal.php';
require_once __DIR__ . '/../utils/response.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET: Config ─────────────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'config') {
    sendResponse(true, 'PayPal configuration.', [
        'client_id' => PAYPAL_CLIENT_ID,
        'currency'  => PAYPAL_CURRENCY,
        'mode'      => 'sandbox'
    ]);
}

// ── POST: Create Order ──────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'create_order') {
    $rawInput = file_get_contents('php://input');
    $body = json_decode($rawInput, true) ?? [];

    $orderId = isset($body['order_id']) ? (int)$body['order_id'] : 0;
    $amount  = isset($body['amount']) ? (float)$body['amount'] : 0.0;

    if ($amount <= 0) {
        sendResponse(false, 'Valid payment amount is required.', null, 422);
    }

    $res = createPayPalOrder($orderId, $amount, 'Ravenhill Coffee POS Order');
    if (!$res['success']) {
        sendResponse(false, $res['message'], null, 500);
    }

    sendResponse(true, 'PayPal Order Created.', [
        'paypal_order_id' => $res['paypal_order_id'],
        'status'          => $res['status'],
        'links'           => $res['links']
    ]);
}

// ── POST: Capture Order & Save Payment ──────────────────────────────────────
if ($method === 'POST' && $action === 'capture_order') {
    $rawInput = file_get_contents('php://input');
    $body = json_decode($rawInput, true) ?? [];

    $paypalOrderId = trim($body['paypal_order_id'] ?? '');
    $orderId       = isset($body['order_id']) ? (int)$body['order_id'] : 0;
    $amount        = isset($body['amount']) ? (float)$body['amount'] : 0.0;
    $cashierName   = trim($body['cashier'] ?? 'Staff');

    if (empty($paypalOrderId)) {
        sendResponse(false, 'paypal_order_id is required for capture.', null, 422);
    }

    $captureRes = capturePayPalOrder($paypalOrderId);
    if (!$captureRes['success']) {
        sendResponse(false, $captureRes['message'], null, 500);
    }

    $captureId = $captureRes['capture_id'];
    $capturedAmount = $captureRes['amount'] ? (float)$captureRes['amount'] : $amount;

    $db = getDB();

    // Persist Payment Record in MySQL
    try {
        // 1. Verify or create order record so foreign key is always satisfied
        if ($orderId > 0) {
            $checkStmt = $db->prepare("SELECT order_id FROM Orders WHERE order_id = ?");
            $checkStmt->execute([$orderId]);
            if (!$checkStmt->fetch()) {
                $orderId = 0; // Trigger creation below
            }
        }

        if ($orderId <= 0) {
            $createOrderStmt = $db->prepare("
                INSERT INTO Orders (total_amount, order_status, order_type, notes)
                VALUES (?, 'preparing', 'dine-in', 'PayPal Online Sandbox Order')
            ");
            $createOrderStmt->execute([$capturedAmount]);
            $orderId = (int)$db->lastInsertId();
        } else {
            $stmt = $db->prepare("UPDATE Orders SET order_status = 'preparing' WHERE order_id = ?");
            $stmt->execute([$orderId]);
        }

        // 2. Insert into Payments table
        $payStmt = $db->prepare("
            INSERT INTO Payments (order_id, amount, payment_method, payment_status, transaction_reference, notes)
            VALUES (?, ?, 'PayPal', 'completed', ?, ?)
        ");
        $payStmt->execute([
            $orderId,
            $capturedAmount,
            $captureId,
            "PayPal Sandbox Capture (Order: $paypalOrderId)"
        ]);
        $paymentId = (int)$db->lastInsertId();

        // 3. Link payment_id to Orders table
        $db->prepare("UPDATE Orders SET payment_id = ? WHERE order_id = ?")->execute([$paymentId, $orderId]);

        // 3. Record Audit Log if AuditLogs table exists
        try {
            $auditStmt = $db->prepare("
                INSERT INTO AuditLogs (user_id, action, table_affected, record_id, details)
                VALUES (1, 'PAYPAL_PAYMENT_CAPTURED', 'Payments', ?, ?)
            ");
            $auditStmt->execute([
                $paymentId,
                "Captured $$capturedAmount AUD via PayPal (Ref: $captureId)"
            ]);
        } catch (Exception $e) {
            // Ignore if AuditLogs not enabled
        }

        sendResponse(true, 'Payment captured successfully via PayPal Sandbox.', [
            'payment_id'            => $paymentId,
            'order_id'              => $orderId,
            'payment_method'        => 'PayPal',
            'transaction_reference' => $captureId,
            'amount'                => $capturedAmount,
            'paypal_order_id'       => $paypalOrderId,
            'status'                => 'COMPLETED'
        ]);

    } catch (Exception $e) {
        // Even if DB update errors, payment was captured on PayPal
        sendResponse(true, 'PayPal payment captured, DB sync note: ' . $e->getMessage(), [
            'transaction_reference' => $captureId,
            'amount'                => $capturedAmount,
            'paypal_order_id'       => $paypalOrderId,
            'status'                => 'COMPLETED'
        ]);
    }
}

sendResponse(false, 'Invalid action or endpoint.', null, 400);
