<?php
/**
 * employees.php
 * 15. Employee and Attendance Management
 * FR57: Employee registration
 *
 * Routes:
 *   GET    /api/employees/employees.php        — List all employees with active on-duty status
 *   GET    /api/employees/employees.php?id=123 — Get single employee profile
 *   POST   /api/employees/employees.php        — FR57: Register new employee (Admin/Manager)
 *   PUT    /api/employees/employees.php?id=123 — Update employee profile / wage / PIN (Admin/Manager)
 *   DELETE /api/employees/employees.php?id=123 — Delete employee record (Admin/Manager)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Gracefully ensure extra columns exist in Employees
try { $db->exec("ALTER TABLE Employees ADD COLUMN hourly_rate FLOAT DEFAULT 28.50"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Employees ADD COLUMN pin VARCHAR(10) DEFAULT '1234'"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Employees ADD COLUMN status VARCHAR(50) DEFAULT 'Active'"); } catch (Exception $e) {}

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET: List or View Employee Profiles ────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("
            SELECT e.*, u.username, u.status AS user_status, r.role_name
            FROM Employees e
            LEFT JOIN Users u ON e.user_id = u.user_id
            LEFT JOIN Roles r ON u.role_id = r.role_id
            WHERE e.employee_id = ?
        ");
        $stmt->execute([$id]);
        $emp = $stmt->fetch();

        if (!$emp) {
            sendResponse(false, 'Employee not found.', null, 404);
        }

        // Check if currently clocked in
        $tStmt = $db->prepare("SELECT timesheet_id, clock_in FROM Timesheets WHERE employee_id = ? AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
        $tStmt->execute([$id]);
        $activeShift = $tStmt->fetch();

        $emp['is_clocked_in'] = (bool)$activeShift;
        $emp['active_shift']  = $activeShift;
        $emp['hourly_rate']   = (float)$emp['hourly_rate'];

        sendResponse(true, 'Employee retrieved.', $emp);
    }

    $search = $_GET['search'] ?? '';
    $sql = "
        SELECT e.*, u.username, r.role_name,
               (SELECT COUNT(*) FROM Timesheets WHERE employee_id = e.employee_id AND clock_out IS NULL) AS on_duty
        FROM Employees e
        LEFT JOIN Users u ON e.user_id = u.user_id
        LEFT JOIN Roles r ON u.role_id = r.role_id
        WHERE 1=1
    ";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.email LIKE ? OR e.position LIKE ?)";
        $like = '%' . $search . '%';
        $params = [$like, $like, $like, $like];
    }

    $sql .= " ORDER BY e.employee_id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $employees = $stmt->fetchAll();

    foreach ($employees as &$emp) {
        $emp['is_clocked_in'] = (int)$emp['on_duty'] > 0;
        $emp['hourly_rate']   = (float)$emp['hourly_rate'];
    }

    sendResponse(true, 'Employees retrieved.', [
        'count'     => count($employees),
        'employees' => $employees
    ]);
}

// ── POST: FR57 — Employee Registration (Admin/Manager) ─────────────────────
if ($method === 'POST') {
    requireAuth(['admin', 'manager']);
    $body = getRequestBody();

    $required = ['first_name', 'position'];
    foreach ($required as $field) {
        if (empty($body[$field])) {
            sendResponse(false, "Missing required field: $field", null, 422);
        }
    }

    $firstName  = trim($body['first_name']);
    $lastName   = $body['last_name'] ?? '';
    $phone      = $body['phone'] ?? null;
    $email      = $body['email'] ?? null;
    $position   = trim($body['position']);
    $hireDate   = !empty($body['hire_date']) ? $body['hire_date'] : date('Y-m-d');
    $hourlyRate = isset($body['hourly_rate']) ? (float)$body['hourly_rate'] : 28.50;
    $pin        = !empty($body['pin']) ? trim($body['pin']) : '1234';
    $userId     = !empty($body['user_id']) ? (int)$body['user_id'] : null;

    $stmt = $db->prepare("
        INSERT INTO Employees (first_name, last_name, phone, email, position, hire_date, hourly_rate, pin, user_id, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
    ");
    $stmt->execute([
        $firstName,
        $lastName,
        $phone,
        $email,
        $position,
        $hireDate,
        $hourlyRate,
        $pin,
        $userId
    ]);
    $newId = (int)$db->lastInsertId();

    sendResponse(true, "Employee $firstName $lastName registered successfully.", [
        'employee_id' => $newId,
        'first_name'  => $firstName,
        'last_name'   => $lastName,
        'position'    => $position,
        'hourly_rate' => $hourlyRate,
        'pin'         => $pin
    ], 201);
}

// ── PUT: Update Employee (Admin/Manager) ───────────────────────────────────
if ($method === 'PUT') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Employee ID is required for update.', null, 422);
    }

    $body = getRequestBody();
    $allowed = ['first_name', 'last_name', 'phone', 'email', 'position', 'hire_date', 'hourly_rate', 'pin', 'status', 'user_id'];
    $fields = [];
    $values = [];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $fields[] = "$field = ?";
            if ($field === 'hourly_rate') {
                $values[] = (float)$body[$field];
            } elseif ($field === 'user_id') {
                $values[] = !empty($body[$field]) ? (int)$body[$field] : null;
            } else {
                $values[] = $body[$field];
            }
        }
    }

    if (empty($fields)) {
        sendResponse(false, 'No valid fields provided for update.', null, 422);
    }

    $check = $db->prepare("SELECT employee_id FROM Employees WHERE employee_id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(false, 'Employee not found.', null, 404);
    }

    $values[] = $id;
    $db->prepare("UPDATE Employees SET " . implode(', ', $fields) . " WHERE employee_id = ?")->execute($values);

    sendResponse(true, 'Employee details updated successfully.');
}

// ── DELETE: Delete Employee (Admin/Manager) ────────────────────────────────
if ($method === 'DELETE') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Employee ID is required for deletion.', null, 422);
    }

    $stmt = $db->prepare("DELETE FROM Employees WHERE employee_id = ?");
    $stmt->execute([$id]);

    sendResponse(true, 'Employee record deleted successfully.');
}

sendResponse(false, 'Method not allowed.', null, 405);
