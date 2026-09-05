<?php
/**
 * me.php
 * Validates the current session and returns user info
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

requireMethod('GET');
startSecureSession();

if (empty($_SESSION['user_id'])) {
    sendResponse(false, 'Not authenticated', null, 401);
}

sendResponse(true, 'Authenticated', [
    'user_id'     => (int)$_SESSION['user_id'],
    'username'    => $_SESSION['username'],
    'role'        => strtolower($_SESSION['role_name'] ?? $_SESSION['role'] ?? 'customer'),
    'role_name'   => $_SESSION['role_name'] ?? 'Customer',
    'first_name'  => $_SESSION['first_name'] ?? '',
    'last_name'   => $_SESSION['last_name'] ?? '',
    'email'       => $_SESSION['email'] ?? '',
    'position'    => $_SESSION['position'] ?? '',
    'customer_id' => $_SESSION['customer_id'] ?? null
]);
