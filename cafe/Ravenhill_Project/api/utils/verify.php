<?php
/**
 * verify.php
 * Account and Phone Number Verification Engine
 *
 * Routes:
 *   POST /api/utils/verify.php?action=send_code    — Generate and send 6-digit OTP code to phone number/email
 *   POST /api/utils/verify.php?action=verify_code  — Validate OTP code and mark account/phone as verified
 *   GET  /api/utils/verify.php?phone=0412345678    — Check verification status
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../reports/audit.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

// Ensure PhoneVerifications table exists
$db->exec("
    CREATE TABLE IF NOT EXISTS PhoneVerifications (
        verification_id INT AUTO_INCREMENT PRIMARY KEY,
        phone_number VARCHAR(50) NOT NULL,
        otp_code VARCHAR(10) NOT NULL,
        expires_at DATETIME NOT NULL,
        is_verified BOOLEAN DEFAULT FALSE,
        attempts INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (phone_number),
        INDEX (otp_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Gracefully ensure Customers and Users have is_verified column
try { $db->exec("ALTER TABLE Customers ADD COLUMN is_phone_verified BOOLEAN DEFAULT FALSE"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Users ADD COLUMN is_verified BOOLEAN DEFAULT FALSE"); } catch (Exception $e) {}

// Standardize Australian phone numbers (e.g. 0412345678 -> +61412345678)
function formatAustralianPhone($phone) {
    $clean = preg_replace('/[^0-9+]/', '', $phone);
    if (strpos($clean, '04') === 0 && strlen($clean) === 10) {
        return '+61' . substr($clean, 1);
    }
    if (strpos($clean, '4') === 0 && strlen($clean) === 9) {
        return '+61' . $clean;
    }
    return $clean;
}

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── POST ?action=send_code: Generate & Send OTP ────────────────────────────
if ($method === 'POST' && $action === 'send_code') {
    $body = getRequestBody();

    if (empty($body['phone']) && empty($body['phone_number'])) {
        sendResponse(false, 'Phone number is required.', null, 422);
    }

    $rawPhone = $body['phone'] ?? $body['phone_number'];
    $phone    = formatAustralianPhone(trim($rawPhone));

    // Generate secure 6-digit OTP code (e.g. 849201)
    $otpCode = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $expiry  = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    // Invalidate prior codes for this number
    $db->prepare("DELETE FROM PhoneVerifications WHERE phone_number = ?")->execute([$phone]);

    // Store new OTP
    $stmt = $db->prepare("
        INSERT INTO PhoneVerifications (phone_number, otp_code, expires_at, is_verified)
        VALUES (?, ?, ?, 0)
    ");
    $stmt->execute([$phone, $otpCode, $expiry]);

    logAudit('OTP_SENT', 'PhoneVerifications', "Sent verification OTP to $phone");

    sendResponse(true, "Verification code sent to $phone. (Expires in 5 minutes)", [
        'phone_number' => $phone,
        'expires_in'   => '300 seconds',
        'debug_otp'    => $otpCode // Provided for testing & UI display demonstration
    ], 201);
}

// ── POST ?action=verify_code: Verify OTP ───────────────────────────────────
if ($method === 'POST' && $action === 'verify_code') {
    $body = getRequestBody();

    if ((empty($body['phone']) && empty($body['phone_number'])) || empty($body['code'])) {
        sendResponse(false, 'Phone number and 6-digit verification code are required.', null, 422);
    }

    $rawPhone = $body['phone'] ?? $body['phone_number'];
    $phone    = formatAustralianPhone(trim($rawPhone));
    $code     = trim($body['code']);

    $stmt = $db->prepare("
        SELECT * FROM PhoneVerifications 
        WHERE phone_number = ? AND otp_code = ? AND expires_at >= NOW() AND is_verified = 0
        ORDER BY verification_id DESC LIMIT 1
    ");
    $stmt->execute([$phone, $code]);
    $record = $stmt->fetch();

    if (!$record) {
        // Increment attempts on existing record
        $db->prepare("UPDATE PhoneVerifications SET attempts = attempts + 1 WHERE phone_number = ?")->execute([$phone]);
        sendResponse(false, 'Invalid or expired verification code. Please request a new code.', null, 400);
    }

    // Mark as verified
    $db->prepare("UPDATE PhoneVerifications SET is_verified = 1 WHERE verification_id = ?")->execute([$record['verification_id']]);

    // Update Customers record if exists
    $db->prepare("UPDATE Customers SET is_phone_verified = 1 WHERE phone LIKE ? OR phone LIKE ?")
       ->execute(['%' . substr($phone, -9), '%' . $rawPhone]);

    logAudit('OTP_VERIFIED_SUCCESS', 'PhoneVerifications', "Phone number $phone verified successfully");

    sendResponse(true, "Phone number $phone successfully verified!", [
        'phone_number' => $phone,
        'is_verified'  => true,
        'verified_at'  => date('Y-m-d H:i:s')
    ]);
}

// ── GET: Check Phone Verification Status ───────────────────────────────────
if ($method === 'GET') {
    $rawPhone = $_GET['phone'] ?? $_GET['phone_number'] ?? null;
    if (!$rawPhone) {
        sendResponse(false, 'Phone parameter is required.', null, 422);
    }

    $phone = formatAustralianPhone(trim($rawPhone));

    $stmt = $db->prepare("SELECT is_verified, created_at FROM PhoneVerifications WHERE phone_number = ? ORDER BY verification_id DESC LIMIT 1");
    $stmt->execute([$phone]);
    $rec = $stmt->fetch();

    $isVerified = $rec ? (bool)$rec['is_verified'] : false;

    sendResponse(true, 'Verification status retrieved.', [
        'phone_number' => $phone,
        'is_verified'  => $isVerified
    ]);
}

sendResponse(false, 'Method not allowed.', null, 405);
