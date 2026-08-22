<?php
/**
 * db.php - Resilient Database Connection Handler
 * Character Set: utf8mb4
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'mehedih3_cpro306_g1');
define('DB_USER', 'mehedih3_cpro306_g1');
define('DB_PASS', 'cpro306');
define('DB_CHARSET', 'utf8mb4');

function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // 1. Try standard production / cPanel internal 127.0.0.1 connection first for zero DNS latency
        $hosts = ['127.0.0.1', 'localhost'];
        $lastError = '';

        foreach ($hosts as $host) {
            try {
                $dsn = "mysql:host=$host;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                break;
            } catch (PDOException $e) {
                $lastError = $e->getMessage();
            }
        }

        // 2. If running on local XAMPP with root user
        if ($pdo === null) {
            try {
                $dsn = "mysql:host=127.0.0.1;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $pdo = new PDO($dsn, 'root', '', $options);
            } catch (PDOException $e) {
                $lastError = $e->getMessage();
            }
        }

        if ($pdo === null) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed: ' . $lastError
            ]);
            exit;
        }
    }

    return $pdo;
}
