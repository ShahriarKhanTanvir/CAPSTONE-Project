<?php
/**
 * timesheets.php
 * FR59: Employee clock-in and clock-out
 * FR60: Attendance approval
 *
 * Routes:
 *   POST /api/employees/timesheets.php?action=clock_in  — FR59: Clock in with PIN or Employee ID
 *   POST /api/employees/timesheets.php?action=clock_out — FR59: Clock out, compute shift duration & pay
 *   POST /api/employees/timesheets.php?id=123&action=approve — FR60: Manager approves/rejects timesheet
 *   GET  /api/employees/timesheets.php                 — List timesheets (supports ?status=, ?employee_id=, ?date=)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;

// Gracefully ensure extra columns exist in Timesheets
try { $db->exec("ALTER TABLE Timesheets ADD COLUMN total_hours FLOAT DEFAULT 0.0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Timesheets ADD COLUMN hourly_rate FLOAT DEFAULT 28.50"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Timesheets ADD COLUMN total_pay FLOAT DEFAULT 0.0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Timesheets ADD COLUMN approved_by VARCHAR(100) NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Timesheets ADD COLUMN approved_at DATETIME NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Timesheets ADD COLUMN notes TEXT NULL"); } catch (Exception $e) {}

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── POST ?action=clock_in: FR59 — Employee Clock-in ────────────────────────
if ($method === 'POST' && $action === 'clock_in') {
    $body = getRequestBody();

    $pin        = !empty($body['pin']) ? trim($body['pin']) : null;
    $employeeId = !empty($body['employee_id']) ? (int)$body['employee_id'] : null;

    if (!$employeeId && !$pin) {
        sendResponse(false, 'Please provide employee_id or staff PIN to clock in.', null, 422);
    }

    if ($pin) {
        $eStmt = $db->prepare("SELECT * FROM Employees WHERE pin = ?");
        $eStmt->execute([$pin]);
    } else {
        $eStmt = $db->prepare("SELECT * FROM Employees WHERE employee_id = ?");
        $eStmt->execute([$employeeId]);
    }

    $emp = $eStmt->fetch();
    if (!$emp) {
        sendResponse(false, 'Employee / PIN not recognized.', null, 404);
    }

    $empId = (int)$emp['employee_id'];

    // Check if already clocked in without clocking out
    $activeCheck = $db->prepare("SELECT * FROM Timesheets WHERE employee_id = ? AND clock_out IS NULL");
    $activeCheck->execute([$empId]);
    if ($activeCheck->fetch()) {
        sendResponse(false, "{$emp['first_name']} is already clocked in.", null, 409);
    }

    $hourlyRate = (float)$emp['hourly_rate'];
    $stmt = $db->prepare("
        INSERT INTO Timesheets (employee_id, clock_in, status, hourly_rate)
        VALUES (?, NOW(), 'in_progress', ?)
    ");
    $stmt->execute([$empId, $hourlyRate]);
    $newId = (int)$db->lastInsertId();

    sendResponse(true, "Welcome {$emp['first_name']}! You clocked in successfully.", [
        'timesheet_id' => $newId,
        'employee_id'  => $empId,
        'employee_name'=> $emp['first_name'] . ' ' . $emp['last_name'],
        'position'     => $emp['position'],
        'clock_in'     => date('Y-m-d H:i:s'),
        'status'       => 'in_progress'
    ], 201);
}

// ── POST ?action=clock_out: FR59 — Employee Clock-out ──────────────────────
if ($method === 'POST' && $action === 'clock_out') {
    $body = getRequestBody();

    $pin        = !empty($body['pin']) ? trim($body['pin']) : null;
    $employeeId = !empty($body['employee_id']) ? (int)$body['employee_id'] : null;

    if (!$employeeId && !$pin) {
        sendResponse(false, 'Please provide employee_id or staff PIN to clock out.', null, 422);
    }

    if ($pin) {
        $eStmt = $db->prepare("SELECT * FROM Employees WHERE pin = ?");
        $eStmt->execute([$pin]);
    } else {
        $eStmt = $db->prepare("SELECT * FROM Employees WHERE employee_id = ?");
        $eStmt->execute([$employeeId]);
    }

    $emp = $eStmt->fetch();
    if (!$emp) {
        sendResponse(false, 'Employee / PIN not recognized.', null, 404);
    }

    $empId = (int)$emp['employee_id'];

    // Find active shift
    $tStmt = $db->prepare("SELECT * FROM Timesheets WHERE employee_id = ? AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
    $tStmt->execute([$empId]);
    $active = $tStmt->fetch();

    if (!$active) {
        sendResponse(false, "{$emp['first_name']} does not have an active clock-in session.", null, 404);
    }

    $timesheetId = (int)$active['timesheet_id'];
    $clockInTime = strtotime($active['clock_in']);
    $clockOutTime = time();

    $durationSeconds = max(60, $clockOutTime - $clockInTime);
    $totalHours      = round($durationSeconds / 3600.0, 2);
    $hourlyRate      = (float)$active['hourly_rate'] > 0 ? (float)$active['hourly_rate'] : (float)$emp['hourly_rate'];
    $totalPay        = round($totalHours * $hourlyRate, 2);

    $notes = $body['notes'] ?? null;

    $upd = $db->prepare("
        UPDATE Timesheets 
        SET clock_out = NOW(), total_hours = ?, total_pay = ?, status = 'pending_approval', notes = ?
        WHERE timesheet_id = ?
    ");
    $upd->execute([$totalHours, $totalPay, $notes, $timesheetId]);

    sendResponse(true, "Goodbye {$emp['first_name']}! Shift completed ({$totalHours} hrs).", [
        'timesheet_id' => $timesheetId,
        'employee_id'  => $empId,
        'employee_name'=> $emp['first_name'] . ' ' . $emp['last_name'],
        'clock_in'     => $active['clock_in'],
        'clock_out'    => date('Y-m-d H:i:s'),
        'total_hours'  => $totalHours,
        'hourly_rate'  => $hourlyRate,
        'total_pay'    => $totalPay,
        'status'       => 'pending_approval'
    ]);
}

// ── POST ?action=approve: FR60 — Attendance Approval (Admin/Manager) ───────
if ($method === 'POST' && $action === 'approve') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Timesheet ID is required for approval.', null, 422);
    }

    $body     = getRequestBody();
    $decision = !empty($body['decision']) ? strtolower(trim($body['decision'])) : 'approved'; // 'approved' or 'rejected'
    $manager  = $_SESSION['username'] ?? 'Manager';
    $notes    = $body['notes'] ?? null;

    $stmt = $db->prepare("SELECT * FROM Timesheets WHERE timesheet_id = ?");
    $stmt->execute([$id]);
    $ts = $stmt->fetch();

    if (!$ts) {
        sendResponse(false, 'Timesheet record not found.', null, 404);
    }

    $db->prepare("
        UPDATE Timesheets 
        SET status = ?, approved_by = ?, approved_at = NOW(), notes = COALESCE(?, notes)
        WHERE timesheet_id = ?
    ")->execute([$decision, $manager, $notes, $id]);

    sendResponse(true, "Timesheet #$id marked as $decision by $manager.", [
        'timesheet_id' => $id,
        'status'       => $decision,
        'approved_by'  => $manager,
        'approved_at'  => date('Y-m-d H:i:s')
    ]);
}

// ── GET: List Timesheets ───────────────────────────────────────────────────
if ($method === 'GET') {
    $empFilter    = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
    $statusFilter = $_GET['status'] ?? null;
    $dateFilter   = $_GET['date'] ?? null;

    $sql = "
        SELECT t.*, 
               CONCAT(e.first_name, ' ', COALESCE(e.last_name, '')) AS employee_name,
               e.position, e.phone
        FROM Timesheets t
        LEFT JOIN Employees e ON t.employee_id = e.employee_id
        WHERE 1=1
    ";
    $params = [];

    if ($empFilter) {
        $sql .= " AND t.employee_id = ?";
        $params[] = $empFilter;
    }

    if ($statusFilter) {
        $sql .= " AND LOWER(t.status) = LOWER(?)";
        $params[] = $statusFilter;
    }

    if ($dateFilter) {
        $sql .= " AND DATE(t.clock_in) = ?";
        $params[] = $dateFilter;
    }

    $sql .= " ORDER BY t.clock_in DESC LIMIT 100";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $sheets = $stmt->fetchAll();

    foreach ($sheets as &$s) {
        $s['total_hours'] = (float)$s['total_hours'];
        $s['hourly_rate'] = (float)$s['hourly_rate'];
        $s['total_pay']   = (float)$s['total_pay'];
    }

    sendResponse(true, 'Timesheets retrieved.', [
        'count'      => count($sheets),
        'timesheets' => $sheets
    ]);
}

sendResponse(false, 'Method not allowed.', null, 405);
