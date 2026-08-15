<?php
/**
 * logout.php
 * Terminates the active user session.
 *
 * POST /api/users/logout.php
 */

require_once __DIR__ . '/../utils/response.php';

requireMethod('POST');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_unset();
session_destroy();

sendResponse(true, 'Logged out successfully.');
