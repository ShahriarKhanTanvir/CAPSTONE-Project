<?php
/**
 * auth_check.php
 * NFR09: Role-Based Access Control
 * NFR13: Secure Session Management
 * NFR14: Automatic Session Timeout (30 mins inactivity)
 */

require_once __DIR__ . '/../utils/response.php';

function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

        $cookieParams = [
            'lifetime' => 86400, // 24 hours
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true, // Protect from XSS cookie theft
            'samesite' => 'Lax' // Protect from CSRF while supporting page reloads
        ];
        session_set_cookie_params($cookieParams);
        session_start();
    }
}

function requireAuth(array $allowedRoles = []) {
    startSecureSession();

    if (empty($_SESSION['user_id'])) {
        sendResponse(false, 'Unauthorized. Please log in.', null, 401);
    }

    // NFR14: Automatic Session Timeout Check (1800 seconds = 30 minutes)
    $timeoutDuration = 1800;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeoutDuration)) {
        session_unset();
        session_destroy();
        sendResponse(false, 'Session expired due to 30 minutes of inactivity. Please log in again.', [
            'session_expired' => true
        ], 401);
    }
    $_SESSION['last_activity'] = time();

    // NFR09: Role-based access check
    if (!empty($allowedRoles)) {
        $userRole = strtolower($_SESSION['role_name'] ?? '');
        $allowedLower = array_map('strtolower', $allowedRoles);
        if (!in_array($userRole, $allowedLower, true)) {
            sendResponse(false, 'Forbidden. You do not have permission to access this resource.', [
                'required_roles' => $allowedRoles,
                'current_role'   => $userRole
            ], 403);
        }
    }
}

function getSessionUser() {
    startSecureSession();
    return [
        'user_id'   => $_SESSION['user_id']   ?? null,
        'username'  => $_SESSION['username']  ?? null,
        'role_id'   => $_SESSION['role_id']   ?? null,
        'role_name' => $_SESSION['role_name'] ?? null,
    ];
}
