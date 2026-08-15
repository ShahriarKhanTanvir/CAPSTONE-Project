<?php
/**
 * customisations.php
 * FR13: Customisation option creation
 * FR14: Product customisation selection
 * FR15: Additional customisation pricing
 * FR16: Customisation summary display
 *
 * Routes:
 *   GET    /api/customisations/customisations.php                    — FR14: List customisations (supports ?product_id=, ?category_id=, ?group=)
 *   GET    /api/customisations/customisations.php?id=123             — Get single customisation
 *   POST   /api/customisations/customisations.php                    — FR13: Create customisation option (Admin/Manager)
 *   PUT    /api/customisations/customisations.php?id=123             — Update customisation option (Admin/Manager)
 *   DELETE /api/customisations/customisations.php?id=123             — Delete customisation option (Admin/Manager)
 *   POST   /api/customisations/customisations.php?action=calculate   — FR15 & FR16: Calculate customisation pricing and return summary
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;

// Ensure Customisations table exists
$db->exec("
    CREATE TABLE IF NOT EXISTS Customisations (
        customisation_id INT AUTO_INCREMENT PRIMARY KEY,
        group_name VARCHAR(100) NOT NULL,
        option_name VARCHAR(100) NOT NULL,
        extra_price FLOAT DEFAULT 0.0,
        category_id INT NULL,
        product_id INT NULL,
        is_default BOOLEAN DEFAULT FALSE,
        availability BOOLEAN DEFAULT TRUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (category_id),
        INDEX (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET: FR14 — Retrieve & Select Customisation Options ────────────────────
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM Customisations WHERE customisation_id = ?");
        $stmt->execute([$id]);
        $customisation = $stmt->fetch();

        if (!$customisation) {
            sendResponse(false, 'Customisation option not found.', null, 404);
        }

        $customisation['extra_price'] = (float)$customisation['extra_price'];
        $customisation['availability'] = (bool)$customisation['availability'];
        $customisation['is_default'] = (bool)$customisation['is_default'];

        sendResponse(true, 'Customisation option retrieved.', $customisation);
    }

    $productId   = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
    $categoryId  = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
    $groupName   = $_GET['group'] ?? null;

    // If product_id given, resolve its category_id as well
    if ($productId && !$categoryId) {
        $prodStmt = $db->prepare("SELECT category_id FROM Products WHERE product_id = ?");
        $prodStmt->execute([$productId]);
        $prod = $prodStmt->fetch();
        if ($prod) {
            $categoryId = (int)$prod['category_id'];
        }
    }

    $sql = "SELECT * FROM Customisations WHERE availability = 1";
    $params = [];

    if ($productId || $categoryId) {
        $sql .= " AND (
            (product_id IS NULL AND category_id IS NULL)
            " . ($categoryId ? "OR category_id = ?" : "") . "
            " . ($productId ? "OR product_id = ?" : "") . "
        )";
        if ($categoryId) $params[] = $categoryId;
        if ($productId) $params[] = $productId;
    }

    if ($groupName) {
        $sql .= " AND group_name = ?";
        $params[] = $groupName;
    }

    $sql .= " ORDER BY group_name ASC, extra_price ASC, option_name ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Group by group_name for seamless UI rendering
    $grouped = [];
    foreach ($rows as $row) {
        $g = $row['group_name'];
        if (!isset($grouped[$g])) {
            $grouped[$g] = [];
        }
        $grouped[$g][] = [
            'customisation_id' => (int)$row['customisation_id'],
            'group_name'       => $row['group_name'],
            'option_name'      => $row['option_name'],
            'extra_price'      => (float)$row['extra_price'],
            'is_default'       => (bool)$row['is_default'],
            'category_id'      => $row['category_id'] ? (int)$row['category_id'] : null,
            'product_id'       => $row['product_id'] ? (int)$row['product_id'] : null,
        ];
    }

    sendResponse(true, 'Customisation options retrieved.', [
        'total_options' => count($rows),
        'groups'        => $grouped,
        'all_options'   => $rows
    ]);
}

// ── POST: FR13 — Create Customisation Option (Admin/Manager) ───────────────
if ($method === 'POST' && $action !== 'calculate') {
    requireAuth(['admin', 'manager']);
    $body = getRequestBody();

    $required = ['group_name', 'option_name'];
    foreach ($required as $field) {
        if (empty($body[$field])) {
            sendResponse(false, "Missing required field: $field", null, 422);
        }
    }

    $extraPrice   = isset($body['extra_price']) ? (float)$body['extra_price'] : 0.0;
    $categoryId   = !empty($body['category_id']) ? (int)$body['category_id'] : null;
    $productId    = !empty($body['product_id']) ? (int)$body['product_id'] : null;
    $isDefault    = !empty($body['is_default']) ? 1 : 0;
    $availability = isset($body['availability']) ? ($body['availability'] ? 1 : 0) : 1;

    $stmt = $db->prepare("
        INSERT INTO Customisations (group_name, option_name, extra_price, category_id, product_id, is_default, availability)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $body['group_name'],
        $body['option_name'],
        $extraPrice,
        $categoryId,
        $productId,
        $isDefault,
        $availability
    ]);

    $newId = (int)$db->lastInsertId();

    sendResponse(true, 'Customisation option created successfully.', [
        'customisation_id' => $newId,
        'group_name'       => $body['group_name'],
        'option_name'      => $body['option_name'],
        'extra_price'      => $extraPrice,
        'category_id'      => $categoryId,
        'product_id'       => $productId,
        'is_default'       => (bool)$isDefault,
        'availability'     => (bool)$availability
    ], 201);
}

// ── POST ?action=calculate: FR15 & FR16 — Pricing & Summary Calculation ────
if ($method === 'POST' && $action === 'calculate') {
    $body = getRequestBody();

    if (empty($body['product_id'])) {
        sendResponse(false, 'product_id is required.', null, 422);
    }

    $productId = (int)$body['product_id'];
    $quantity  = max(1, (int)($body['quantity'] ?? 1));
    $customisationIds = $body['customisation_ids'] ?? [];

    // Fetch base product
    $stmt = $db->prepare("SELECT product_id, product_name, price, availability FROM Products WHERE product_id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        sendResponse(false, 'Product not found.', null, 404);
    }

    $basePrice = (float)$product['price'];
    $extraTotal = 0.0;
    $selectedDetails = [];
    $summaryTags = [];

    if (!empty($customisationIds) && is_array($customisationIds)) {
        // Clean ids
        $cleanIds = array_map('intval', $customisationIds);
        $inQuery  = implode(',', array_fill(0, count($cleanIds), '?'));
        
        $cStmt = $db->prepare("SELECT customisation_id, group_name, option_name, extra_price FROM Customisations WHERE customisation_id IN ($inQuery)");
        $cStmt->execute($cleanIds);
        $customs = $cStmt->fetchAll();

        foreach ($customs as $c) {
            $extra = (float)$c['extra_price'];
            $extraTotal += $extra;
            $selectedDetails[] = [
                'customisation_id' => (int)$c['customisation_id'],
                'group_name'       => $c['group_name'],
                'option_name'      => $c['option_name'],
                'extra_price'      => $extra,
                'formatted_price'  => $extra > 0 ? '+$' . number_format($extra, 2) : 'Free'
            ];
            $summaryTags[] = $c['option_name'] . ($extra > 0 ? " (+$" . number_format($extra, 2) . ")" : "");
        }
    }

    $unitPriceTotal = $basePrice + $extraTotal;
    $lineTotal      = $unitPriceTotal * $quantity;

    // FR16 Summary display
    $summaryDisplay = [
        'product_name'       => $product['product_name'],
        'quantity'           => $quantity,
        'base_unit_price'    => $basePrice,
        'customisations_cost'=> $extraTotal,
        'unit_price_total'   => $unitPriceTotal,
        'line_total'         => $lineTotal,
        'formatted_summary'  => count($summaryTags) > 0 ? implode(', ', $summaryTags) : 'Standard / No customisations',
        'kitchen_ticket_note'=> count($summaryTags) > 0 ? implode(' • ', $summaryTags) : 'Standard'
    ];

    sendResponse(true, 'Customisation pricing and summary calculated.', [
        'product'             => $product,
        'selected_options'    => $selectedDetails,
        'pricing_breakdown'   => [
            'base_unit_price'     => $basePrice,
            'customisations_unit' => $extraTotal,
            'unit_price'          => $unitPriceTotal,
            'quantity'            => $quantity,
            'subtotal'            => $lineTotal
        ],
        'summary_display'     => $summaryDisplay
    ]);
}

// ── PUT: Update Customisation Option (Admin/Manager) ───────────────────────
if ($method === 'PUT') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Customisation ID is required for update.', null, 422);
    }

    $body = getRequestBody();
    $allowed = ['group_name', 'option_name', 'extra_price', 'category_id', 'product_id', 'is_default', 'availability'];
    $fields = [];
    $values = [];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $fields[] = "$field = ?";
            if ($field === 'extra_price') {
                $values[] = (float)$body[$field];
            } elseif ($field === 'is_default' || $field === 'availability') {
                $values[] = $body[$field] ? 1 : 0;
            } elseif ($field === 'category_id' || $field === 'product_id') {
                $values[] = !empty($body[$field]) ? (int)$body[$field] : null;
            } else {
                $values[] = $body[$field];
            }
        }
    }

    if (empty($fields)) {
        sendResponse(false, 'No valid fields provided for update.', null, 422);
    }

    $check = $db->prepare("SELECT customisation_id FROM Customisations WHERE customisation_id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(false, 'Customisation option not found.', null, 404);
    }

    $values[] = $id;
    $db->prepare("UPDATE Customisations SET " . implode(', ', $fields) . " WHERE customisation_id = ?")
       ->execute($values);

    sendResponse(true, 'Customisation option updated successfully.');
}

// ── DELETE: Delete Customisation Option (Admin/Manager) ────────────────────
if ($method === 'DELETE') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Customisation ID is required for deletion.', null, 422);
    }

    $check = $db->prepare("SELECT customisation_id FROM Customisations WHERE customisation_id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(false, 'Customisation option not found.', null, 404);
    }

    $stmt = $db->prepare("DELETE FROM Customisations WHERE customisation_id = ?");
    $stmt->execute([$id]);

    sendResponse(true, 'Customisation option deleted successfully.');
}

sendResponse(false, 'Method not allowed.', null, 405);
