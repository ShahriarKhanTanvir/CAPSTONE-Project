<?php
/**
 * items.php
 * FR10: Menu Item Creation
 * FR11: Menu Item Update and Deletion
 * FR12: Menu Item Availability Management
 *
 * Routes:
 *   GET    /api/menu/items.php                  — List all items (filterable by category_id, availability, search)
 *   GET    /api/menu/items.php?id=123           — Get single item with category info
 *   POST   /api/menu/items.php                  — FR10: Create new menu item (Admin/Manager)
 *   PUT    /api/menu/items.php?id=123           — FR11: Update menu item (Admin/Manager)
 *   DELETE /api/menu/items.php?id=123           — FR11: Delete menu item (Admin/Manager)
 *   PATCH  /api/menu/items.php?id=123&action=toggle_availability — FR12: Toggle or set item availability (Admin/Manager/Barista/Cashier)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET: List or Retrieve Menu Items ───────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("
            SELECT p.*, c.category_name
            FROM Products p
            LEFT JOIN Categories c ON p.category_id = c.category_id
            WHERE p.product_id = ?
        ");
        $stmt->execute([$id]);
        $item = $stmt->fetch();

        if (!$item) {
            sendResponse(false, 'Menu item not found.', null, 404);
        }

        // Format availability as boolean
        $item['availability'] = (bool)$item['availability'];
        sendResponse(true, 'Menu item retrieved.', $item);
    }

    $categoryId   = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
    $availability = isset($_GET['available']) ? (int)$_GET['available'] : null;
    $search       = $_GET['search'] ?? '';

    $sql = "
        SELECT p.*, c.category_name
        FROM Products p
        LEFT JOIN Categories c ON p.category_id = c.category_id
        WHERE 1=1
    ";
    $params = [];

    if ($categoryId !== null) {
        $sql .= " AND p.category_id = ?";
        $params[] = $categoryId;
    }

    if ($availability !== null) {
        $sql .= " AND p.availability = ?";
        $params[] = $availability ? 1 : 0;
    }

    if ($search !== '') {
        $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= " ORDER BY p.category_id ASC, p.product_name ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    // Cast boolean types
    foreach ($items as &$item) {
        $item['availability'] = (bool)$item['availability'];
        $item['price'] = (float)$item['price'];
    }

    sendResponse(true, 'Menu items retrieved.', $items);
}

// ── POST: FR10 — Create Menu Item (Admin/Manager) ───────────────────────────
if ($method === 'POST') {
    requireAuth(['admin', 'manager']);
    $body = getRequestBody();

    $required = ['product_name', 'price', 'category_id'];
    foreach ($required as $field) {
        if (!isset($body[$field]) || $body[$field] === '') {
            sendResponse(false, "Missing required field: $field", null, 422);
        }
    }

    // Verify category exists
    $catCheck = $db->prepare("SELECT category_id FROM Categories WHERE category_id = ?");
    $catCheck->execute([(int)$body['category_id']]);
    if (!$catCheck->fetch()) {
        sendResponse(false, 'Selected category does not exist.', null, 404);
    }

    $availability = isset($body['availability']) ? ($body['availability'] ? 1 : 0) : 1;

    $stmt = $db->prepare("
        INSERT INTO Products (category_id, product_name, description, price, availability, image)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        (int)$body['category_id'],
        $body['product_name'],
        $body['description'] ?? null,
        (float)$body['price'],
        $availability,
        $body['image'] ?? null
    ]);

    $newId = (int)$db->lastInsertId();

    sendResponse(true, 'Menu item created successfully.', [
        'product_id'   => $newId,
        'category_id'  => (int)$body['category_id'],
        'product_name' => $body['product_name'],
        'description'  => $body['description'] ?? null,
        'price'        => (float)$body['price'],
        'availability' => (bool)$availability,
        'image'        => $body['image'] ?? null
    ], 201);
}

// ── PUT: FR11 — Update Menu Item (Admin/Manager) ───────────────────────────
if ($method === 'PUT') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Product ID is required for update.', null, 422);
    }

    $body = getRequestBody();
    $allowed = ['category_id', 'product_name', 'description', 'price', 'availability', 'image'];
    $fields = [];
    $values = [];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $fields[] = "$field = ?";
            if ($field === 'availability') {
                $values[] = $body[$field] ? 1 : 0;
            } elseif ($field === 'price') {
                $values[] = (float)$body[$field];
            } elseif ($field === 'category_id') {
                $values[] = (int)$body[$field];
            } else {
                $values[] = $body[$field];
            }
        }
    }

    if (empty($fields)) {
        sendResponse(false, 'No valid fields provided for update.', null, 422);
    }

    // Verify item exists
    $check = $db->prepare("SELECT product_id FROM Products WHERE product_id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(false, 'Menu item not found.', null, 404);
    }

    $values[] = $id;
    $db->prepare("UPDATE Products SET " . implode(', ', $fields) . " WHERE product_id = ?")
       ->execute($values);

    sendResponse(true, 'Menu item updated successfully.');
}

// ── PATCH: FR12 — Manage Menu Item Availability ───────────────────────────
if ($method === 'PATCH') {
    // Allows admin, manager, barista, cashier to quickly toggle availability
    requireAuth(['admin', 'manager', 'barista', 'cashier']);

    if (!$id) {
        sendResponse(false, 'Product ID is required for availability update.', null, 422);
    }

    $body = getRequestBody();

    // Check item exists
    $stmt = $db->prepare("SELECT product_id, availability, product_name FROM Products WHERE product_id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if (!$item) {
        sendResponse(false, 'Menu item not found.', null, 404);
    }

    // If explicit availability passed, use it; otherwise toggle current value
    if (isset($body['availability'])) {
        $newAvailability = $body['availability'] ? 1 : 0;
    } else {
        $newAvailability = $item['availability'] ? 0 : 1;
    }

    $update = $db->prepare("UPDATE Products SET availability = ? WHERE product_id = ?");
    $update->execute([$newAvailability, $id]);

    sendResponse(true, 'Menu item availability updated successfully.', [
        'product_id'   => $id,
        'product_name' => $item['product_name'],
        'availability' => (bool)$newAvailability,
        'status_text'  => $newAvailability ? 'Available' : 'Unavailable (Sold Out)'
    ]);
}

// ── DELETE: FR11 — Delete Menu Item (Admin/Manager) ────────────────────────
if ($method === 'DELETE') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Product ID is required for deletion.', null, 422);
    }

    $check = $db->prepare("SELECT product_id FROM Products WHERE product_id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(false, 'Menu item not found.', null, 404);
    }

    $stmt = $db->prepare("DELETE FROM Products WHERE product_id = ?");
    $stmt->execute([$id]);

    sendResponse(true, 'Menu item deleted successfully.');
}

sendResponse(false, 'Method not allowed.', null, 405);
