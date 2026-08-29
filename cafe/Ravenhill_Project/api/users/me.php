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
    'user_id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'role' => $_SESSION['role_name']
]);
