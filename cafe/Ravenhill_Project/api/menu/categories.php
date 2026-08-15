<?php
/**
 * categories.php
 * FR9: Menu Category Creation & Category Management
 *
 * Routes:
 *   GET    /api/menu/categories.php          — List all categories (with product count)
 *   GET    /api/menu/categories.php?id=123   — Get single category
 *   POST   /api/menu/categories.php          — FR9: Create new category (Admin/Manager)
 *   PUT    /api/menu/categories.php?id=123   — Update category (Admin/Manager)
 *   DELETE /api/menu/categories.php?id=123   — Delete category (Admin/Manager)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET: List or Retrieve Categories ───────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM Categories WHERE category_id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch();

        if (!$category) {
            sendResponse(false, 'Category not found.', null, 404);
        }

        sendResponse(true, 'Category retrieved.', $category);
    }

    $stmt = $db->prepare("
        SELECT c.*, COUNT(p.product_id) AS product_count
        FROM Categories c
        LEFT JOIN Products p ON c.category_id = p.category_id
        GROUP BY c.category_id
        ORDER BY c.category_name ASC
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll();

    sendResponse(true, 'Categories retrieved.', $categories);
}

// ── POST: FR9 — Create New Category (Admin/Manager) ─────────────────────────
if ($method === 'POST') {
    requireAuth(['admin', 'manager']);
    $body = getRequestBody();

    if (empty($body['category_name'])) {
        sendResponse(false, 'category_name is required.', null, 422);
    }

    // Check for duplicate category name
    $check = $db->prepare("SELECT category_id FROM Categories WHERE category_name = ?");
    $check->execute([$body['category_name']]);
    if ($check->fetch()) {
        sendResponse(false, 'A category with this name already exists.', null, 409);
    }

    $stmt = $db->prepare("
        INSERT INTO Categories (category_name, description)
        VALUES (?, ?)
    ");
    $stmt->execute([
        $body['category_name'],
        $body['description'] ?? null
    ]);

    $newId = (int)$db->lastInsertId();

    sendResponse(true, 'Menu category created successfully.', [
        'category_id'   => $newId,
        'category_name' => $body['category_name'],
        'description'   => $body['description'] ?? null
    ], 201);
}

// ── PUT: Update Category (Admin/Manager) ───────────────────────────────────
if ($method === 'PUT') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Category ID is required for update.', null, 422);
    }

    $body = getRequestBody();
    $allowed = ['category_name', 'description'];
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

    $check = $db->prepare("SELECT category_id FROM Categories WHERE category_id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(false, 'Category not found.', null, 404);
    }

    $values[] = $id;
    $db->prepare("UPDATE Categories SET " . implode(', ', $fields) . " WHERE category_id = ?")
       ->execute($values);

    sendResponse(true, 'Category updated successfully.');
}

// ── DELETE: Delete Category (Admin/Manager) ────────────────────────────────
if ($method === 'DELETE') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Category ID is required for deletion.', null, 422);
    }

    $check = $db->prepare("SELECT category_id FROM Categories WHERE category_id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(false, 'Category not found.', null, 404);
    }

    $stmt = $db->prepare("DELETE FROM Categories WHERE category_id = ?");
    $stmt->execute([$id]);

    sendResponse(true, 'Category deleted successfully.');
}

sendResponse(false, 'Method not allowed.', null, 405);
