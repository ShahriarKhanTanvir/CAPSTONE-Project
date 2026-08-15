<?php
/**
 * suppliers.php
 * 14. Supplier and Purchase Management
 * FR53: Supplier registration
 *
 * Routes:
 *   GET    /api/suppliers/suppliers.php        — List all registered suppliers with inventory counts
 *   GET    /api/suppliers/suppliers.php?id=123 — Get single supplier with their supplied items
 *   POST   /api/suppliers/suppliers.php        — FR53: Register new supplier (Admin/Manager)
 *   PUT    /api/suppliers/suppliers.php?id=123 — Update supplier details (Admin/Manager)
 *   DELETE /api/suppliers/suppliers.php?id=123 — Delete supplier (Admin/Manager)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Gracefully ensure extra columns exist in Suppliers
try { $db->exec("ALTER TABLE Suppliers ADD COLUMN payment_terms VARCHAR(100) DEFAULT 'Net 30'"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Suppliers ADD COLUMN notes TEXT NULL"); } catch (Exception $e) {}

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET: List or Retrieve Suppliers ────────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM Suppliers WHERE supplier_id = ?");
        $stmt->execute([$id]);
        $supplier = $stmt->fetch();

        if (!$supplier) {
            sendResponse(false, 'Supplier not found.', null, 404);
        }

        // Fetch items supplied by this vendor
        $itemsStmt = $db->prepare("
            SELECT inventory_id, item_name, quantity, unit, reorder_level, unit_cost, status
            FROM Inventory
            WHERE supplier_id = ?
        ");
        $itemsStmt->execute([$id]);
        $supplier['supplied_inventory'] = $itemsStmt->fetchAll();

        sendResponse(true, 'Supplier details retrieved.', $supplier);
    }

    $search = $_GET['search'] ?? '';
    $sql = "
        SELECT s.*, 
               (SELECT COUNT(*) FROM Inventory WHERE supplier_id = s.supplier_id) AS items_count
        FROM Suppliers s
        WHERE 1=1
    ";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (s.supplier_name LIKE ? OR s.contact_person LIKE ? OR s.email LIKE ? OR s.phone LIKE ?)";
        $like = '%' . $search . '%';
        $params = [$like, $like, $like, $like];
    }

    $sql .= " ORDER BY s.supplier_name ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $suppliers = $stmt->fetchAll();

    sendResponse(true, 'Suppliers retrieved.', [
        'count'     => count($suppliers),
        'suppliers' => $suppliers
    ]);
}

// ── POST: FR53 — Supplier Registration (Admin/Manager) ─────────────────────
if ($method === 'POST') {
    requireAuth(['admin', 'manager']);
    $body = getRequestBody();

    if (empty($body['supplier_name'])) {
        sendResponse(false, 'supplier_name is required.', null, 422);
    }

    $name    = trim($body['supplier_name']);
    $contact = $body['contact_person'] ?? null;
    $phone   = $body['phone'] ?? null;
    $email   = $body['email'] ?? null;
    $address = $body['address'] ?? null;
    $terms   = $body['payment_terms'] ?? 'Net 30';
    $notes   = $body['notes'] ?? null;

    // Check duplicate name
    $check = $db->prepare("SELECT supplier_id FROM Suppliers WHERE supplier_name = ?");
    $check->execute([$name]);
    if ($check->fetch()) {
        sendResponse(false, "Supplier '$name' is already registered.", null, 409);
    }

    $stmt = $db->prepare("
        INSERT INTO Suppliers (supplier_name, contact_person, phone, email, address, payment_terms, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $contact, $phone, $email, $address, $terms, $notes]);
    $newId = (int)$db->lastInsertId();

    sendResponse(true, "Supplier '$name' registered successfully.", [
        'supplier_id'   => $newId,
        'supplier_name' => $name,
        'contact_person'=> $contact,
        'phone'         => $phone,
        'email'         => $email,
        'payment_terms' => $terms
    ], 201);
}

// ── PUT: Update Supplier (Admin/Manager) ───────────────────────────────────
if ($method === 'PUT') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Supplier ID is required for update.', null, 422);
    }

    $body = getRequestBody();
    $allowed = ['supplier_name', 'contact_person', 'phone', 'email', 'address', 'payment_terms', 'notes'];
    $fields = [];
    $values = [];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $fields[] = "$field = ?";
            $values[] = $body[$field];
        }
    }

    if (empty($fields)) {
        sendResponse(false, 'No valid fields provided for update.', null, 422);
    }

    $check = $db->prepare("SELECT supplier_id FROM Suppliers WHERE supplier_id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(false, 'Supplier not found.', null, 404);
    }

    $values[] = $id;
    $db->prepare("UPDATE Suppliers SET " . implode(', ', $fields) . " WHERE supplier_id = ?")->execute($values);

    sendResponse(true, 'Supplier details updated successfully.');
}

// ── DELETE: Delete Supplier (Admin/Manager) ────────────────────────────────
if ($method === 'DELETE') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Supplier ID is required for deletion.', null, 422);
    }

    $check = $db->prepare("SELECT supplier_id FROM Suppliers WHERE supplier_id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(false, 'Supplier not found.', null, 404);
    }

    $stmt = $db->prepare("DELETE FROM Suppliers WHERE supplier_id = ?");
    $stmt->execute([$id]);

    sendResponse(true, 'Supplier deleted successfully.');
}

sendResponse(false, 'Method not allowed.', null, 405);
