<?php
/**
 * register.php
 * FR1: User Account Creation
 *
 * POST /api/users/register.php
 * Body (JSON): {
 *   "username": "jdoe",
 *   "password": "Secret123",
 *   "first_name": "John",
 *   "last_name": "Doe",
 *   "email": "jdoe@ravenhill.au",
 *   "phone": "0412345678",
 *   "position": "Cashier",
 *   "role_id": 3
 * }
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

// Only accessible by admin or manager
requireMethod('POST');
requireAuth(['admin', 'manager']);

$body = getRequestBody();

// Validate required fields
$required = ['username', 'password', 'first_name', 'role_id'];
foreach ($required as $field) {
    if (empty($body[$field])) {
        sendResponse(false, "Missing required field: $field", null, 422);
    }
}

$db = getDB();

// Ensure username is unique
$stmt = $db->prepare("SELECT user_id FROM Users WHERE username = ?");
$stmt->execute([$body['username']]);
if ($stmt->fetch()) {
    sendResponse(false, 'Username already exists. Please choose another.', null, 409);
}

// Hash the password securely
$passwordHash = password_hash($body['password'], PASSWORD_BCRYPT);

// Insert into Users table
$stmt = $db->prepare("
    INSERT INTO Users (username, password_hash, role_id, status, last_login)
    VALUES (?, ?, ?, 'active', NULL)
");
$stmt->execute([
    $body['username'],
    $passwordHash,
    (int)$body['role_id'],
]);
$newUserId = (int)$db->lastInsertId();

// Insert corresponding employee record
$stmt = $db->prepare("
    INSERT INTO Employees (user_id, first_name, last_name, phone, email, position, hire_date)
    VALUES (?, ?, ?, ?, ?, ?, CURDATE())
");
$stmt->execute([
    $newUserId,
    $body['first_name'],
    $body['last_name']  ?? null,
    $body['phone']      ?? null,
    $body['email']      ?? null,
    $body['position']   ?? null,
]);
$newEmployeeId = (int)$db->lastInsertId();

sendResponse(true, 'User account created successfully.', [
    'user_id'     => $newUserId,
    'employee_id' => $newEmployeeId,
    'username'    => $body['username'],
], 201);
