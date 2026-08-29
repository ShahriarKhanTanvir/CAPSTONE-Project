<?php
/**
 * register_customer.php
 * Handles new customer registration
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

requireMethod('POST');

$body = getRequestBody();

// Validate required fields
$required = ['first_name', 'email', 'password'];
foreach ($required as $field) {
    if (empty($body[$field])) {
        sendResponse(false, "Missing required field: $field", null, 422);
    }
}

$db = getDB();

// Ensure email/username is unique (we use email as username for customers)
$stmt = $db->prepare("SELECT user_id FROM Users WHERE username = ?");
$stmt->execute([$body['email']]);
if ($stmt->fetch()) {
    sendResponse(false, 'Email already registered. Please login.', null, 409);
}

// Hash the password securely
$passwordHash = password_hash($body['password'], PASSWORD_BCRYPT);

try {
    $db->beginTransaction();

    // 1. Insert into Users table (Role ID 6 for Customer)
    $stmt = $db->prepare("
        INSERT INTO Users (username, password_hash, role_id, status, last_login)
        VALUES (?, ?, 6, 'active', NULL)
    ");
    $stmt->execute([
        $body['email'],
        $passwordHash
    ]);
    $newUserId = (int)$db->lastInsertId();

    // 2. Insert into Customers table
    $stmt = $db->prepare("
        INSERT INTO Customers (user_id, first_name, last_name, phone, email, loyalty_points, loyalty_tier)
        VALUES (?, ?, ?, ?, ?, 0, 'Bronze')
    ");
    $stmt->execute([
        $newUserId,
        $body['first_name'],
        $body['last_name'] ?? null,
        $body['phone'] ?? null,
        $body['email']
    ]);
    $newCustomerId = (int)$db->lastInsertId();

    $db->commit();

    sendResponse(true, 'Account created successfully.', [
        'user_id' => $newUserId,
        'customer_id' => $newCustomerId,
        'username' => $body['email'],
        'role' => 'Customer'
    ], 201);

} catch (Exception $e) {
    $db->rollBack();
    sendResponse(false, 'Registration failed: ' . $e->getMessage(), null, 500);
}
