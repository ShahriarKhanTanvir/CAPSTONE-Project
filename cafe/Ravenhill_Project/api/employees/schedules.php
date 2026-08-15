<?php
/**
 * schedules.php
 * FR58: Employee schedule management
 *
 * Routes:
 *   GET    /api/employees/schedules.php          — List scheduled shifts (supports ?date_from=, ?date_to=, ?employee_id=)
 *   POST   /api/employees/schedules.php          — FR58: Create/assign work shift to employee (Admin/Manager)
 *   PUT    /api/employees/schedules.php?id=123   — Update scheduled shift
 *   DELETE /api/employees/schedules.php?id=123   — Delete shift from roster
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Ensure Schedules table exists
$db->exec("
    CREATE TABLE IF NOT EXISTS Schedules (
        schedule_id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        shift_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        role_station VARCHAR(100) DEFAULT 'Floor / Counter',
        notes VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (employee_id),
        INDEX (shift_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET: View Rostered Shifts ──────────────────────────────────────────────
if ($method === 'GET') {
    $empId    = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo   = $_GET['date_to'] ?? null;

    $sql = "
        SELECT sc.*, 
               CONCAT(e.first_name, ' ', COALESCE(e.last_name, '')) AS employee_name,
               e.position, e.phone
        FROM Schedules sc
        LEFT JOIN Employees e ON sc.employee_id = e.employee_id
        WHERE 1=1
    ";
    $params = [];

    if ($empId) {
        $sql .= " AND sc.employee_id = ?";
        $params[] = $empId;
    }

    if ($dateFrom) {
        $sql .= " AND sc.shift_date >= ?";
        $params[] = $dateFrom;
    }

    if ($dateTo) {
        $sql .= " AND sc.shift_date <= ?";
        $params[] = $dateTo;
    }

    $sql .= " ORDER BY sc.shift_date ASC, sc.start_time ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $shifts = $stmt->fetchAll();

    sendResponse(true, 'Roster shifts retrieved.', [
        'count'  => count($shifts),
        'shifts' => $shifts
    ]);
}

// ── POST: FR58 — Assign Work Shift (Admin/Manager) ─────────────────────────
if ($method === 'POST') {
    requireAuth(['admin', 'manager']);
    $body = getRequestBody();

    $required = ['employee_id', 'shift_date', 'start_time', 'end_time'];
    foreach ($required as $field) {
        if (empty($body[$field])) {
            sendResponse(false, "Missing required field: $field", null, 422);
        }
    }

    $empId   = (int)$body['employee_id'];
    $date    = $body['shift_date'];
    $start   = strlen($body['start_time']) === 5 ? $body['start_time'] . ':00' : $body['start_time'];
    $end     = strlen($body['end_time']) === 5 ? $body['end_time'] . ':00' : $body['end_time'];
    $station = !empty($body['role_station']) ? trim($body['role_station']) : 'Floor / Counter';
    $notes   = $body['notes'] ?? null;

    // Verify employee
    $eStmt = $db->prepare("SELECT employee_id, first_name FROM Employees WHERE employee_id = ?");
    $eStmt->execute([$empId]);
    $emp = $eStmt->fetch();

    if (!$emp) {
        sendResponse(false, 'Employee not found.', null, 404);
    }

    $stmt = $db->prepare("
        INSERT INTO Schedules (employee_id, shift_date, start_time, end_time, role_station, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$empId, $date, $start, $end, $station, $notes]);
    $newId = (int)$db->lastInsertId();

    sendResponse(true, "Shift scheduled for {$emp['first_name']} on $date ($start - $end).", [
        'schedule_id'  => $newId,
        'employee_id'  => $empId,
        'shift_date'   => $date,
        'start_time'   => $start,
        'end_time'     => $end,
        'role_station' => $station
    ], 201);
}

// ── PUT: Update Scheduled Shift (Admin/Manager) ────────────────────────────
if ($method === 'PUT') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Schedule ID is required for update.', null, 422);
    }

    $body = getRequestBody();
    $allowed = ['employee_id', 'shift_date', 'start_time', 'end_time', 'role_station', 'notes'];
    $fields = [];
    $values = [];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $fields[] = "$field = ?";
            if ($field === 'employee_id') {
                $values[] = (int)$body[$field];
            } else {
                $values[] = $body[$field];
            }
        }
    }

    if (empty($fields)) {
        sendResponse(false, 'No valid fields provided for update.', null, 422);
    }

    $values[] = $id;
    $db->prepare("UPDATE Schedules SET " . implode(', ', $fields) . " WHERE schedule_id = ?")->execute($values);

    sendResponse(true, 'Roster shift updated successfully.');
}

// ── DELETE: Remove Shift (Admin/Manager) ───────────────────────────────────
if ($method === 'DELETE') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Schedule ID is required for deletion.', null, 422);
    }

    $db->prepare("DELETE FROM Schedules WHERE schedule_id = ?")->execute([$id]);
    sendResponse(true, 'Roster shift removed.');
}

sendResponse(false, 'Method not allowed.', null, 405);
