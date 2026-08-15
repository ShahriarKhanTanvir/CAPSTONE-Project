<?php
/**
 * tables.php
 * FR17: Table registration
 * FR18: Table information management
 * FR19: Table status management
 * FR20: Table assignment
 *
 * Routes:
 *   GET    /api/tables/tables.php                   — List all tables (supports ?status=, ?location=)
 *   GET    /api/tables/tables.php?id=123            — Get single table details
 *   POST   /api/tables/tables.php                   — FR17: Table registration (Admin/Manager)
 *   PUT    /api/tables/tables.php?id=123            — FR18: Table information management (Admin/Manager)
 *   DELETE /api/tables/tables.php?id=123            — FR18: Delete table (Admin/Manager)
 *   PATCH  /api/tables/tables.php?id=123&action=status — FR19: Table status management (Available, Occupied, Reserved, Cleaning)
 *   POST   /api/tables/tables.php?id=123&action=assign — FR20: Table assignment (Assign to order / guest / server)
 *   POST   /api/tables/tables.php?id=123&action=release— Release table (sets status to Available or Cleaning)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;

// Ensure optional columns exist in DiningTables gracefully
try {
    $db->exec("ALTER TABLE DiningTables ADD COLUMN location VARCHAR(100) DEFAULT 'Main Dining'");
} catch (Exception $e) {}
try {
    $db->exec("ALTER TABLE DiningTables ADD COLUMN assigned_order_id INT NULL");
} catch (Exception $e) {}
try {
    $db->exec("ALTER TABLE DiningTables ADD COLUMN assigned_server_name VARCHAR(100) NULL");
} catch (Exception $e) {}
try {
    $db->exec("ALTER TABLE DiningTables ADD COLUMN guest_name VARCHAR(100) NULL");
} catch (Exception $e) {}

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET: List or Retrieve Dining Tables ─────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM DiningTables WHERE table_id = ?");
        $stmt->execute([$id]);
        $table = $stmt->fetch();

        if (!$table) {
            sendResponse(false, 'Table not found.', null, 404);
        }

        $table['table_id']     = (int)$table['table_id'];
        $table['table_number'] = (int)$table['table_number'];
        $table['capacity']     = (int)$table['capacity'];

        sendResponse(true, 'Table retrieved.', $table);
    }

    $status   = $_GET['status'] ?? null;
    $location = $_GET['location'] ?? null;

    $sql = "SELECT * FROM DiningTables WHERE 1=1";
    $params = [];

    if ($status) {
        $sql .= " AND LOWER(status) = LOWER(?)";
        $params[] = $status;
    }

    if ($location) {
        $sql .= " AND LOWER(location) = LOWER(?)";
        $params[] = $location;
    }

    $sql .= " ORDER BY table_number ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tables = $stmt->fetchAll();

    // Cast numeric fields
    foreach ($tables as &$t) {
        $t['table_id']     = (int)$t['table_id'];
        $t['table_number'] = (int)$t['table_number'];
        $t['capacity']     = (int)$t['capacity'];
    }

    sendResponse(true, 'Dining tables retrieved.', $tables);
}

// ── POST: FR17 — Table Registration (Admin/Manager) ────────────────────────
if ($method === 'POST' && !$action) {
    requireAuth(['admin', 'manager']);
    $body = getRequestBody();

    $required = ['table_number', 'capacity'];
    foreach ($required as $field) {
        if (!isset($body[$field]) || $body[$field] === '') {
            sendResponse(false, "Missing required field: $field", null, 422);
        }
    }

    $tableNumber = (int)$body['table_number'];
    $capacity    = (int)$body['capacity'];
    $status      = !empty($body['status']) ? $body['status'] : 'available';
    $location    = !empty($body['location']) ? $body['location'] : 'Main Dining';

    // Ensure unique table number
    $check = $db->prepare("SELECT table_id FROM DiningTables WHERE table_number = ?");
    $check->execute([$tableNumber]);
    if ($check->fetch()) {
        sendResponse(false, "Table number $tableNumber already exists.", null, 409);
    }

    $stmt = $db->prepare("
        INSERT INTO DiningTables (table_number, capacity, status, location)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$tableNumber, $capacity, $status, $location]);
    $newId = (int)$db->lastInsertId();

    sendResponse(true, 'Table registered successfully.', [
        'table_id'     => $newId,
        'table_number' => $tableNumber,
        'capacity'     => $capacity,
        'status'       => $status,
        'location'     => $location
    ], 201);
}

// ── PATCH: FR19 — Table Status Management ──────────────────────────────────
if ($method === 'PATCH' && $action === 'status') {
    requireAuth(['admin', 'manager', 'waitstaff', 'cashier']);

    if (!$id) {
        sendResponse(false, 'Table ID is required for status update.', null, 422);
    }

    $body = getRequestBody();
    if (empty($body['status'])) {
        sendResponse(false, 'New status is required.', null, 422);
    }

    $newStatus = strtolower(trim($body['status']));
    $allowedStatuses = ['available', 'occupied', 'reserved', 'cleaning', 'maintenance'];

    if (!in_array($newStatus, $allowedStatuses, true)) {
        sendResponse(false, 'Invalid status. Allowed values: ' . implode(', ', $allowedStatuses), null, 422);
    }

    // Verify table exists
    $check = $db->prepare("SELECT table_id, table_number FROM DiningTables WHERE table_id = ?");
    $check->execute([$id]);
    $table = $check->fetch();
    if (!$table) {
        sendResponse(false, 'Table not found.', null, 404);
    }

    // If changing to available or cleaning, reset assignment fields
    if ($newStatus === 'available' || $newStatus === 'cleaning') {
        $stmt = $db->prepare("
            UPDATE DiningTables 
            SET status = ?, assigned_order_id = NULL, assigned_server_name = NULL, guest_name = NULL 
            WHERE table_id = ?
        ");
        $stmt->execute([$newStatus, $id]);
    } else {
        $stmt = $db->prepare("UPDATE DiningTables SET status = ? WHERE table_id = ?");
        $stmt->execute([$newStatus, $id]);
    }

    sendResponse(true, "Table #{$table['table_number']} status updated to {$newStatus}.", [
        'table_id'     => $id,
        'table_number' => (int)$table['table_number'],
        'status'       => $newStatus
    ]);
}

// ── POST: FR20 — Table Assignment (Assign to order/customer/server) ────────
if ($method === 'POST' && $action === 'assign') {
    requireAuth(['admin', 'manager', 'waitstaff', 'cashier']);

    if (!$id) {
        sendResponse(false, 'Table ID is required for assignment.', null, 422);
    }

    $body = getRequestBody();

    // Check table
    $stmt = $db->prepare("SELECT * FROM DiningTables WHERE table_id = ?");
    $stmt->execute([$id]);
    $table = $stmt->fetch();

    if (!$table) {
        sendResponse(false, 'Table not found.', null, 404);
    }

    $orderId    = !empty($body['order_id']) ? (int)$body['order_id'] : null;
    $serverName = !empty($body['server_name']) ? trim($body['server_name']) : ($_SESSION['username'] ?? 'Wait Staff');
    $guestName  = !empty($body['guest_name']) ? trim($body['guest_name']) : null;
    $guestsCount= !empty($body['guests_count']) ? (int)$body['guests_count'] : null;

    if ($guestsCount && $guestsCount > (int)$table['capacity']) {
        sendResponse(false, "Party size ($guestsCount) exceeds table capacity ({$table['capacity']}).", null, 422);
    }

    $update = $db->prepare("
        UPDATE DiningTables 
        SET status = 'occupied',
            assigned_order_id = ?,
            assigned_server_name = ?,
            guest_name = ?
        WHERE table_id = ?
    ");
    $update->execute([$orderId, $serverName, $guestName, $id]);

    sendResponse(true, "Table #{$table['table_number']} assigned successfully.", [
        'table_id'             => $id,
        'table_number'         => (int)$table['table_number'],
        'status'               => 'occupied',
        'assigned_order_id'    => $orderId,
        'assigned_server_name' => $serverName,
        'guest_name'           => $guestName
    ]);
}

// ── POST: Release Table ────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'release') {
    requireAuth(['admin', 'manager', 'waitstaff', 'cashier']);

    if (!$id) {
        sendResponse(false, 'Table ID is required to release table.', null, 422);
    }

    $body = getRequestBody();
    $nextStatus = !empty($body['next_status']) && in_array($body['next_status'], ['available', 'cleaning']) 
        ? $body['next_status'] 
        : 'available';

    $stmt = $db->prepare("
        UPDATE DiningTables 
        SET status = ?, assigned_order_id = NULL, assigned_server_name = NULL, guest_name = NULL 
        WHERE table_id = ?
    ");
    $stmt->execute([$nextStatus, $id]);

    sendResponse(true, "Table released to {$nextStatus} state.", [
        'table_id' => $id,
        'status'   => $nextStatus
    ]);
}

// ── PUT: FR18 — Table Information Management (Admin/Manager) ──────────────
if ($method === 'PUT') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Table ID is required for update.', null, 422);
    }

    $body = getRequestBody();
    $allowed = ['table_number', 'capacity', 'status', 'location'];
    $fields = [];
    $values = [];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $fields[] = "$field = ?";
            if ($field === 'table_number' || $field === 'capacity') {
                $values[] = (int)$body[$field];
            } else {
                $values[] = $body[$field];
            }
        }
    }

    if (empty($fields)) {
        sendResponse(false, 'No valid fields provided for update.', null, 422);
    }

    // Verify table exists
    $check = $db->prepare("SELECT table_id FROM DiningTables WHERE table_id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(false, 'Table not found.', null, 404);
    }

    $values[] = $id;
    $db->prepare("UPDATE DiningTables SET " . implode(', ', $fields) . " WHERE table_id = ?")
       ->execute($values);

    sendResponse(true, 'Table information updated successfully.');
}

// ── DELETE: FR18 — Delete Table (Admin/Manager) ────────────────────────────
if ($method === 'DELETE') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Table ID is required for deletion.', null, 422);
    }

    $check = $db->prepare("SELECT table_id FROM DiningTables WHERE table_id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(false, 'Table not found.', null, 404);
    }

    $stmt = $db->prepare("DELETE FROM DiningTables WHERE table_id = ?");
    $stmt->execute([$id]);

    sendResponse(true, 'Table deleted successfully.');
}

sendResponse(false, 'Method not allowed.', null, 405);
