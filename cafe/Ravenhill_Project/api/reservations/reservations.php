<?php
/**
 * reservations.php
 * FR21: Reservation creation
 * FR22: Reservation detail recording
 * FR23: Table availability checking
 * FR24: Reservation update and cancellation
 *
 * Routes:
 *   GET    /api/reservations/reservations.php                        — List reservations (supports ?date=, ?status=, ?customer_id=)
 *   GET    /api/reservations/reservations.php?id=123                 — Get single reservation details
 *   GET    /api/reservations/reservations.php?action=availability    — FR23: Check table availability for date, time, & guest count
 *   POST   /api/reservations/reservations.php                        — FR21 & FR22: Create reservation & record details
 *   PUT    /api/reservations/reservations.php?id=123                 — FR24: Update reservation details
 *   PATCH  /api/reservations/reservations.php?id=123&action=status   — FR24: Update status (confirmed, seated, cancelled, completed, no_show)
 *   DELETE /api/reservations/reservations.php?id=123                 — FR24: Cancel / delete reservation
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;

// Gracefully ensure extra helpful columns exist in Reservations
try {
    $db->exec("ALTER TABLE Reservations ADD COLUMN special_requests TEXT NULL");
} catch (Exception $e) {}
try {
    $db->exec("ALTER TABLE Reservations ADD COLUMN guest_name VARCHAR(255) NULL");
} catch (Exception $e) {}
try {
    $db->exec("ALTER TABLE Reservations ADD COLUMN guest_phone VARCHAR(50) NULL");
} catch (Exception $e) {}
try {
    $db->exec("ALTER TABLE Reservations ADD COLUMN guest_email VARCHAR(255) NULL");
} catch (Exception $e) {}
try {
    $db->exec("ALTER TABLE Reservations ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
} catch (Exception $e) {}

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET: Check Availability OR List Reservations ───────────────────────────
if ($method === 'GET') {

    // FR23: Table Availability Checking
    if ($action === 'availability') {
        $date        = $_GET['date'] ?? date('Y-m-d');
        $time        = $_GET['time'] ?? '12:00:00';
        $guestCount  = (int)($_GET['guests'] ?? 2);

        // Normalize time format to HH:MM:SS
        if (strlen($time) === 5) {
            $time .= ':00';
        }

        // Get all tables capable of holding this party size
        $tableStmt = $db->prepare("
            SELECT table_id, table_number, capacity, location, status AS current_status
            FROM DiningTables
            WHERE capacity >= ?
            ORDER BY capacity ASC, table_number ASC
        ");
        $tableStmt->execute([$guestCount]);
        $suitableTables = $tableStmt->fetchAll();

        // Check which tables are already booked around this time window (+/- 90 mins)
        $bookedStmt = $db->prepare("
            SELECT r.table_id, r.reservation_time, r.number_of_guests, r.status
            FROM Reservations r
            WHERE r.reservation_date = ?
              AND r.status IN ('confirmed', 'seated')
              AND ABS(TIME_TO_SEC(TIMEDIFF(r.reservation_time, ?))) < 5400
        ");
        $bookedStmt->execute([$date, $time]);
        $bookedMap = [];
        foreach ($bookedStmt->fetchAll() as $b) {
            $bookedMap[(int)$b['table_id']] = $b;
        }

        $availableTables = [];
        $conflictedTables = [];

        foreach ($suitableTables as $t) {
            $tId = (int)$t['table_id'];
            if (!isset($bookedMap[$tId])) {
                $availableTables[] = [
                    'table_id'     => $tId,
                    'table_number' => (int)$t['table_number'],
                    'capacity'     => (int)$t['capacity'],
                    'location'     => $t['location'],
                    'is_available' => true
                ];
            } else {
                $conflictedTables[] = [
                    'table_id'         => $tId,
                    'table_number'     => (int)$t['table_number'],
                    'capacity'         => (int)$t['capacity'],
                    'location'         => $t['location'],
                    'is_available'     => false,
                    'existing_booking' => $bookedMap[$tId]
                ];
            }
        }

        sendResponse(true, 'Table availability checked.', [
            'query'             => [
                'date'          => $date,
                'time'          => $time,
                'guests'        => $guestCount
            ],
            'available_count'   => count($availableTables),
            'available_tables'  => $availableTables,
            'conflicted_tables' => $conflictedTables
        ]);
    }

    // Get Single Reservation
    if ($id) {
        $stmt = $db->prepare("
            SELECT r.*, 
                   c.first_name AS customer_first_name, c.last_name AS customer_last_name, 
                   c.email AS customer_email, c.phone AS customer_phone,
                   t.table_number, t.capacity AS table_capacity, t.location AS table_location
            FROM Reservations r
            LEFT JOIN Customers c ON r.customer_id = c.customer_id
            LEFT JOIN DiningTables t ON r.table_id = t.table_id
            WHERE r.reservation_id = ?
        ");
        $stmt->execute([$id]);
        $res = $stmt->fetch();

        if (!$res) {
            sendResponse(false, 'Reservation not found.', null, 404);
        }

        sendResponse(true, 'Reservation retrieved.', $res);
    }

    // List Reservations (with filters)
    $dateFilter   = $_GET['date'] ?? null;
    $statusFilter = $_GET['status'] ?? null;
    $custIdFilter = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;

    $sql = "
        SELECT r.*, 
               COALESCE(CONCAT(c.first_name, ' ', COALESCE(c.last_name, '')), r.guest_name, 'Guest') AS display_name,
               COALESCE(c.phone, r.guest_phone) AS display_phone,
               COALESCE(c.email, r.guest_email) AS display_email,
               t.table_number, t.capacity AS table_capacity, t.location AS table_location
        FROM Reservations r
        LEFT JOIN Customers c ON r.customer_id = c.customer_id
        LEFT JOIN DiningTables t ON r.table_id = t.table_id
        WHERE 1=1
    ";
    $params = [];

    if ($dateFilter) {
        $sql .= " AND r.reservation_date = ?";
        $params[] = $dateFilter;
    }

    if ($statusFilter) {
        $sql .= " AND LOWER(r.status) = LOWER(?)";
        $params[] = $statusFilter;
    }

    if ($custIdFilter) {
        $sql .= " AND r.customer_id = ?";
        $params[] = $custIdFilter;
    }

    $sql .= " ORDER BY r.reservation_date ASC, r.reservation_time ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $reservations = $stmt->fetchAll();

    sendResponse(true, 'Reservations retrieved.', [
        'total'        => count($reservations),
        'reservations' => $reservations
    ]);
}

// ── POST: FR21 & FR22 — Create Reservation & Record Details ────────────────
if ($method === 'POST' && !$action) {
    $body = getRequestBody();

    $required = ['reservation_date', 'reservation_time', 'number_of_guests'];
    foreach ($required as $field) {
        if (empty($body[$field])) {
            sendResponse(false, "Missing required field: $field", null, 422);
        }
    }

    $date        = $body['reservation_date'];
    $time        = strlen($body['reservation_time']) === 5 ? $body['reservation_time'] . ':00' : $body['reservation_time'];
    $guests      = (int)$body['number_of_guests'];
    $tableId     = !empty($body['table_id']) ? (int)$body['table_id'] : null;
    $customerId  = !empty($body['customer_id']) ? (int)$body['customer_id'] : null;
    $guestName   = $body['guest_name'] ?? null;
    $guestPhone  = $body['guest_phone'] ?? null;
    $guestEmail  = $body['guest_email'] ?? null;
    $requests    = $body['special_requests'] ?? null;
    $status      = !empty($body['status']) ? $body['status'] : 'confirmed';

    // If customer_id not provided but email/phone provided, try to find or create customer record
    if (!$customerId && !empty($guestEmail)) {
        $cCheck = $db->prepare("SELECT customer_id FROM Customers WHERE email = ?");
        $cCheck->execute([$guestEmail]);
        $cRow = $cCheck->fetch();
        if ($cRow) {
            $customerId = (int)$cRow['customer_id'];
        } else if (!empty($guestName)) {
            // Auto register customer
            $parts = explode(' ', trim($guestName), 2);
            $fn = $parts[0];
            $ln = $parts[1] ?? '';
            $insC = $db->prepare("INSERT INTO Customers (first_name, last_name, phone, email, loyalty_points) VALUES (?, ?, ?, ?, 0)");
            $insC->execute([$fn, $ln, $guestPhone, $guestEmail]);
            $customerId = (int)$db->lastInsertId();
        }
    }

    // If table_id is provided, validate capacity and availability
    if ($tableId) {
        $tStmt = $db->prepare("SELECT table_id, table_number, capacity FROM DiningTables WHERE table_id = ?");
        $tStmt->execute([$tableId]);
        $table = $tStmt->fetch();

        if (!$table) {
            sendResponse(false, 'Selected table not found.', null, 404);
        }

        if ((int)$table['capacity'] < $guests) {
            sendResponse(false, "Party size ($guests) exceeds table capacity ({$table['capacity']}).", null, 422);
        }

        // Check for time conflicts on this table
        $conflictStmt = $db->prepare("
            SELECT reservation_id FROM Reservations
            WHERE table_id = ?
              AND reservation_date = ?
              AND status IN ('confirmed', 'seated')
              AND ABS(TIME_TO_SEC(TIMEDIFF(reservation_time, ?))) < 5400
        ");
        $conflictStmt->execute([$tableId, $date, $time]);
        if ($conflictStmt->fetch()) {
            sendResponse(false, "Table #{$table['table_number']} is already booked for that time window.", null, 409);
        }
    } else {
        // Auto-assign first available table that fits party size
        $autoStmt = $db->prepare("
            SELECT t.table_id FROM DiningTables t
            WHERE t.capacity >= ?
              AND t.table_id NOT IN (
                  SELECT r.table_id FROM Reservations r
                  WHERE r.reservation_date = ?
                    AND r.status IN ('confirmed', 'seated')
                    AND r.table_id IS NOT NULL
                    AND ABS(TIME_TO_SEC(TIMEDIFF(r.reservation_time, ?))) < 5400
              )
            ORDER BY t.capacity ASC
            LIMIT 1
        ");
        $autoStmt->execute([$guests, $date, $time]);
        $autoTable = $autoStmt->fetch();
        if ($autoTable) {
            $tableId = (int)$autoTable['table_id'];
        }
    }

    // Insert Reservation
    $stmt = $db->prepare("
        INSERT INTO Reservations (customer_id, table_id, reservation_date, reservation_time, number_of_guests, status, special_requests, guest_name, guest_phone, guest_email)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $customerId,
        $tableId,
        $date,
        $time,
        $guests,
        $status,
        $requests,
        $guestName,
        $guestPhone,
        $guestEmail
    ]);

    $newId = (int)$db->lastInsertId();

    sendResponse(true, 'Reservation created and recorded successfully.', [
        'reservation_id'   => $newId,
        'customer_id'      => $customerId,
        'table_id'         => $tableId,
        'reservation_date' => $date,
        'reservation_time' => $time,
        'number_of_guests' => $guests,
        'status'           => $status,
        'special_requests' => $requests,
        'guest_name'       => $guestName
    ], 201);
}

// ── PUT: FR24 — Update Reservation ─────────────────────────────────────────
if ($method === 'PUT') {
    if (!$id) {
        sendResponse(false, 'Reservation ID is required for update.', null, 422);
    }

    $body = getRequestBody();
    $allowed = ['table_id', 'reservation_date', 'reservation_time', 'number_of_guests', 'status', 'special_requests', 'guest_name', 'guest_phone', 'guest_email'];
    $fields = [];
    $values = [];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $fields[] = "$field = ?";
            if ($field === 'table_id' || $field === 'number_of_guests') {
                $values[] = !empty($body[$field]) ? (int)$body[$field] : null;
            } else {
                $values[] = $body[$field];
            }
        }
    }

    if (empty($fields)) {
        sendResponse(false, 'No valid fields provided for update.', null, 422);
    }

    // Check reservation exists
    $check = $db->prepare("SELECT reservation_id FROM Reservations WHERE reservation_id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(false, 'Reservation not found.', null, 404);
    }

    $values[] = $id;
    $db->prepare("UPDATE Reservations SET " . implode(', ', $fields) . " WHERE reservation_id = ?")
       ->execute($values);

    sendResponse(true, 'Reservation updated successfully.');
}

// ── PATCH: FR24 — Status Management (Cancellation / Seating) ───────────────
if ($method === 'PATCH' && $action === 'status') {
    if (!$id) {
        sendResponse(false, 'Reservation ID is required for status change.', null, 422);
    }

    $body = getRequestBody();
    if (empty($body['status'])) {
        sendResponse(false, 'New status is required.', null, 422);
    }

    $newStatus = strtolower(trim($body['status']));
    $allowedStatuses = ['confirmed', 'seated', 'cancelled', 'completed', 'no_show'];

    if (!in_array($newStatus, $allowedStatuses, true)) {
        sendResponse(false, 'Invalid status. Allowed values: ' . implode(', ', $allowedStatuses), null, 422);
    }

    $check = $db->prepare("SELECT reservation_id, table_id FROM Reservations WHERE reservation_id = ?");
    $check->execute([$id]);
    $res = $check->fetch();

    if (!$res) {
        sendResponse(false, 'Reservation not found.', null, 404);
    }

    $stmt = $db->prepare("UPDATE Reservations SET status = ? WHERE reservation_id = ?");
    $stmt->execute([$newStatus, $id]);

    // If marked seated, optionally update table status to occupied
    if ($newStatus === 'seated' && !empty($res['table_id'])) {
        $db->prepare("UPDATE DiningTables SET status = 'occupied' WHERE table_id = ?")->execute([$res['table_id']]);
    }

    sendResponse(true, "Reservation status changed to {$newStatus}.", [
        'reservation_id' => $id,
        'status'         => $newStatus
    ]);
}

// ── DELETE: FR24 — Delete / Cancel Reservation ─────────────────────────────
if ($method === 'DELETE') {
    if (!$id) {
        sendResponse(false, 'Reservation ID is required for deletion.', null, 422);
    }

    $check = $db->prepare("SELECT reservation_id FROM Reservations WHERE reservation_id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(false, 'Reservation not found.', null, 404);
    }

    $stmt = $db->prepare("DELETE FROM Reservations WHERE reservation_id = ?");
    $stmt->execute([$id]);

    sendResponse(true, 'Reservation deleted / cancelled successfully.');
}

sendResponse(false, 'Method not allowed.', null, 405);
