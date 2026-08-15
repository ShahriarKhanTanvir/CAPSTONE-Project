<?php
/**
 * audit.php
 * FR68: Audit logging helper and log viewer
 *
 * Routes:
 *   GET  /api/reports/audit.php — List / search security and administrative audit logs (Admin/Manager)
 *   POST /api/reports/audit.php — Record a new audit log event
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// Gracefully ensure extra columns exist in AuditLogs
try { $db->exec("ALTER TABLE AuditLogs ADD COLUMN user_name VARCHAR(100) NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE AuditLogs ADD COLUMN details TEXT NULL"); } catch (Exception $e) {}

// Global audit logging helper function
function logAudit($action, $tableName = null, $details = null, $userId = null, $userName = null) {
    global $db;
    try {
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = (int)$_SESSION['user_id'];
        }
        if ($userName === null && isset($_SESSION['username'])) {
            $userName = $_SESSION['username'];
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $stmt = $db->prepare("
            INSERT INTO AuditLogs (user_id, user_name, action, table_name, details, timestamp, ip_address)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([$userId, $userName ?? 'System', $action, $tableName, $details, $ip]);
    } catch (Exception $e) {
        // Suppress audit failure to prevent breaking main operations
    }
}

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET: View Audit Logs (Admin/Manager) ────────────────────────────────────
if ($method === 'GET') {
    requireAuth(['admin', 'manager']);

    $search = $_GET['search'] ?? '';
    $action = $_GET['action_filter'] ?? '';
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 50;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT * FROM AuditLogs WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (action LIKE ? OR table_name LIKE ? OR user_name LIKE ? OR details LIKE ?)";
        $like = '%' . $search . '%';
        $params = [$like, $like, $like, $like];
    }

    if ($action !== '') {
        $sql .= " AND action = ?";
        $params[] = $action;
    }

    $sql .= " ORDER BY timestamp DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    $countQuery = $db->query("SELECT COUNT(*) AS total FROM AuditLogs");
    $totalLogs = (int)$countQuery->fetch()['total'];

    sendResponse(true, 'Audit logs retrieved.', [
        'total' => $totalLogs,
        'page'  => $page,
        'limit' => $limit,
        'logs'  => $logs
    ]);
}

// ── POST: Manual Audit Log Entry ───────────────────────────────────────────
if ($method === 'POST') {
    requireAuth(['admin', 'manager']);
    $body = getRequestBody();

    if (empty($body['action'])) {
        sendResponse(false, 'action is required.', null, 422);
    }

    logAudit(
        $body['action'],
        $body['table_name'] ?? null,
        $body['details'] ?? null,
        $body['user_id'] ?? null,
        $body['user_name'] ?? null
    );

    sendResponse(true, 'Audit log recorded.');
}

sendResponse(false, 'Method not allowed.', null, 405);
