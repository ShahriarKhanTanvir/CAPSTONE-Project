<?php
/**
 * login.php
 * NFR07: Secure user authentication
 * NFR08: Password hashing (Bcrypt with password_verify)
 * NFR15: Login attempt restriction (Rate limiting / Lockout)
 *
 * Routes:
 *   POST /api/users/login.php
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';
require_once __DIR__ . '/../reports/audit.php';

requireMethod('POST');

$db = getDB();

// Ensure LoginAttempts table exists for rate limiting
$db->exec("
    CREATE TABLE IF NOT EXISTS LoginAttempts (
        attempt_id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(100) NOT NULL,
        username VARCHAR(255) NOT NULL,
        attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (ip_address),
        INDEX (attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$body = getRequestBody();

if (empty($body['username']) || empty($body['password'])) {
    sendResponse(false, 'Username and password are required.', null, 422);
}

$username = trim($body['username']);
$ip       = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// ── NFR15: Check Failed Login Attempt Restrictions (Max 5 within 15 minutes) ─
$lockoutTimeMinutes = 15;
$maxAllowedAttempts = 5;

$stmt = $db->prepare("
    SELECT COUNT(*) AS failed_count 
    FROM LoginAttempts 
    WHERE (ip_address = ? OR username = ?) 
      AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
");
$stmt->execute([$ip, $username, $lockoutTimeMinutes]);
$failedCount = (int)$stmt->fetch()['failed_count'];

if ($failedCount >= $maxAllowedAttempts) {
    logAudit('LOGIN_LOCKED', 'Users', "IP/User locked out after $failedCount failed attempts: $username ($ip)");
    sendResponse(false, "Too many failed login attempts. Account temporarily locked for $lockoutTimeMinutes minutes for security.", [
        'is_locked'       => true,
        'lockout_minutes' => $lockoutTimeMinutes,
        'failed_attempts' => $failedCount
    ], 429); // 429 Too Many Requests
}

// ── NFR07 & NFR10: Secure parameter-bound authentication query ──────────────
$stmt = $db->prepare("
    SELECT u.user_id, u.username, u.password_hash, u.status,
           r.role_id, r.role_name
    FROM Users u
    INNER JOIN Roles r ON u.role_id = r.role_id
    WHERE u.username = ?
    LIMIT 1
");
$stmt->execute([$username]);
$user = $stmt->fetch();

// ── NFR08: Password verification using native Bcrypt algorithm ─────────────
if (!$user || !password_verify($body['password'], $user['password_hash'])) {
    // Record failed attempt
    $db->prepare("INSERT INTO LoginAttempts (ip_address, username, attempted_at) VALUES (?, ?, NOW())")
       ->execute([$ip, $username]);

    logAudit('LOGIN_FAILED', 'Users', "Failed login attempt for username: $username from IP: $ip");

    $remaining = max(0, $maxAllowedAttempts - ($failedCount + 1));
    sendResponse(false, "Invalid username or password. ($remaining attempts remaining before lockout)", [
        'remaining_attempts' => $remaining
    ], 401);
}

if ($user['status'] !== 'active') {
    sendResponse(false, 'Account is inactive. Please contact your system administrator.', null, 403);
}

// Clear previous failed attempts upon successful login
$db->prepare("DELETE FROM LoginAttempts WHERE ip_address = ? OR username = ?")->execute([$ip, $username]);

// Update last login
$db->prepare("UPDATE Users SET last_login = NOW() WHERE user_id = ?")->execute([$user['user_id']]);

// NFR34: Log successful login audit
logAudit('LOGIN_SUCCESS', 'Users', "User '{$user['username']}' logged in as {$user['role_name']}", (int)$user['user_id'], $user['username']);

// NFR13: Start secure session
startSecureSession();
session_regenerate_id(true); // Prevent session fixation

$_SESSION['user_id']       = (int)$user['user_id'];
$_SESSION['username']      = $user['username'];
$_SESSION['role_id']       = (int)$user['role_id'];
$_SESSION['role_name']     = $user['role_name'];
$_SESSION['last_activity'] = time();


// Fetch user details based on role
$userDetails = [];
if (strtolower($user['role_name']) === 'customer') {
    $custStmt = $db->prepare("SELECT customer_id, first_name, last_name, email, phone FROM Customers WHERE user_id = ?");
    $custStmt->execute([$user['user_id']]);
    $userDetails = $custStmt->fetch();
} else {
    // Employee
    $empStmt = $db->prepare("SELECT first_name, last_name, email, phone, position FROM Employees WHERE user_id = ?");
    $empStmt->execute([$user['user_id']]);
    $userDetails = $empStmt->fetch();
}

sendResponse(true, 'Login successful.', [
    'user_id'     => (int)$user['user_id'],
    'username'    => $user['username'],
    'role'        => strtolower($user['role_name']), // normalized to lowercase
    'first_name'  => $userDetails['first_name'] ?? '',
    'last_name'   => $userDetails['last_name']  ?? '',
    'email'       => $userDetails['email']      ?? '',
    'customer_id' => $userDetails['customer_id'] ?? null,
    'position'    => $userDetails['position']   ?? ''
]);
