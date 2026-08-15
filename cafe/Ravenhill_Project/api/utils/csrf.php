<?php
/**
 * csrf.php
 * NFR12: Cross-site request forgery (CSRF) protection helper
 */

function getCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($token === null) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
    }

    if (empty($_SESSION['csrf_token']) || empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }

    return true;
}
