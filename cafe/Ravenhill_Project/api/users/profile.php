<?php
/**
 * profile.php
 * FR4: Password Reset & Profile Update
 *
 * GET  /api/users/profile.php             — Retrieve own profile
 * PUT  /api/users/profile.php             — Update own profile fields
 * POST /api/users/profile.php?action=reset_password — Change own password
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

// All actions require a logged-in session
requireAuth();

$db      = getDB();
$session = getSessionUser();
$userId  = $session['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];

// ── GET: Return current user's profile ────────────────────────────────────
if ($method === 'GET') {
    $stmt = $db->prepare("
        SELECT u.user_id, u.username, u.status, u.last_login,
               r.role_name,
               e.employee_id, e.first_name, e.last_name,
               e.phone, e.email, e.position, e.hire_date
        FROM Users u
        INNER JOIN Roles r ON u.role_id = r.role_id
        LEFT JOIN Employees e ON e.user_id = u.user_id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();

    if (!$profile) {
        sendResponse(false, 'User not found.', null, 404);
    }

    sendResponse(true, 'Profile retrieved.', $profile);
}

// ── PUT: Update profile fields ─────────────────────────────────────────────
if ($method === 'PUT') {
    $body = getRequestBody();

    // Update Employees record
    $allowed = ['first_name', 'last_name', 'phone', 'email', 'position'];
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

    $values[] = $userId;
    $db->prepare("UPDATE Employees SET " . implode(', ', $fields) . " WHERE user_id = ?")
       ->execute($values);

    sendResponse(true, 'Profile updated successfully.');
}

// ── POST ?action=reset_password: Securely change password ─────────────────
if ($method === 'POST') {
    $action = $_GET['action'] ?? '';

    if ($action === 'reset_password') {
        $body = getRequestBody();

        if (empty($body['current_password']) || empty($body['new_password'])) {
            sendResponse(false, 'Both current_password and new_password are required.', null, 422);
        }

        if (strlen($body['new_password']) < 8) {
            sendResponse(false, 'New password must be at least 8 characters.', null, 422);
        }

        // Fetch current hash
        $stmt = $db->prepare("SELECT password_hash FROM Users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($body['current_password'], $user['password_hash'])) {
            sendResponse(false, 'Current password is incorrect.', null, 401);
        }

        $newHash = password_hash($body['new_password'], PASSWORD_BCRYPT);
        $db->prepare("UPDATE Users SET password_hash = ? WHERE user_id = ?")
           ->execute([$newHash, $userId]);

        sendResponse(true, 'Password changed successfully.');
    }

    sendResponse(false, 'Unknown action.', null, 400);
}

sendResponse(false, 'Method not allowed.', null, 405);
