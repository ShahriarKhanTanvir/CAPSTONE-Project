<?php
/**
 * inventory.php
 * 12. Inventory Management
 * FR45: Inventory item registration
 * FR46: Inventory detail management
 * FR48: Stock alert generation
 *
 * Routes:
 *   GET    /api/inventory/inventory.php                — List all stock items (supports ?status=, ?supplier_id=, ?search=)
 *   GET    /api/inventory/inventory.php?id=123         — Get single inventory item
 *   GET    /api/inventory/inventory.php?action=alerts  — FR48: Generate stock alerts (low stock & out-of-stock items)
 *   POST   /api/inventory/inventory.php                — FR45: Register new inventory item (Admin/Manager)
 *   PUT    /api/inventory/inventory.php?id=123         — FR46: Update inventory details (Admin/Manager)
 *   DELETE /api/inventory/inventory.php?id=123         — FR46: Delete inventory item (Admin/Manager)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;

// Gracefully ensure extra columns exist in Inventory
try { $db->exec("ALTER TABLE Inventory ADD COLUMN item_name VARCHAR(255) NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Inventory ADD COLUMN unit VARCHAR(50) DEFAULT 'kg'"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Inventory ADD COLUMN unit_cost FLOAT DEFAULT 0.0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Inventory ADD COLUMN status VARCHAR(50) DEFAULT 'good'"); } catch (Exception $e) {}

// Ensure InventoryTransactions table exists
$db->exec("
    CREATE TABLE IF NOT EXISTS InventoryTransactions (
        transaction_id INT AUTO_INCREMENT PRIMARY KEY,
        inventory_id INT NOT NULL,
        transaction_type VARCHAR(50) NOT NULL,
        quantity_change FLOAT NOT NULL,
        quantity_after FLOAT NOT NULL,
        reason VARCHAR(255) NULL,
        performed_by VARCHAR(100) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (inventory_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Helper to compute item stock status
function computeStockStatus($qty, $minThreshold) {
    if ($qty <= 0) return 'out_of_stock';
    if ($qty <= $minThreshold) return 'low';
    return 'good';
}

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET: FR48 (Alerts) OR List / View Inventory Items ──────────────────────
if ($method === 'GET') {

    // FR48: Stock Alert Generation
    if ($action === 'alerts') {
        $stmt = $db->prepare("
            SELECT i.*, 
                   COALESCE(i.item_name, p.product_name, 'Raw Ingredient') AS display_name,
                   s.supplier_name, s.contact_person, s.phone AS supplier_phone, s.email AS supplier_email
            FROM Inventory i
            LEFT JOIN Products p ON i.product_id = p.product_id
            LEFT JOIN Suppliers s ON i.supplier_id = s.supplier_id
            WHERE i.quantity <= i.reorder_level OR i.quantity <= 0
            ORDER BY (i.quantity / GREATEST(i.reorder_level, 1)) ASC
        ");
        $stmt->execute();
        $alertItems = $stmt->fetchAll();

        $outOfStock = [];
        $lowStock   = [];

        foreach ($alertItems as $item) {
            $qty = (float)$item['quantity'];
            $min = (float)$item['reorder_level'];
            $status = computeStockStatus($qty, $min);

            $alertData = [
                'inventory_id'  => (int)$item['inventory_id'],
                'item_name'     => $item['display_name'],
                'current_qty'   => $qty,
                'reorder_level' => $min,
                'unit'          => $item['unit'],
                'unit_cost'     => (float)$item['unit_cost'],
                'shortage_qty'  => max(0, $min - $qty),
                'supplier'      => [
                    'supplier_id'   => $item['supplier_id'],
                    'name'          => $item['supplier_name'],
                    'contact'       => $item['contact_person'],
                    'phone'         => $item['supplier_phone'],
                    'email'         => $item['supplier_email']
                ],
                'status'        => $status,
                'urgency'       => $qty <= 0 ? 'CRITICAL (Out of Stock)' : 'WARNING (Low Stock)'
            ];

            if ($qty <= 0) {
                $outOfStock[] = $alertData;
            } else {
                $lowStock[] = $alertData;
            }
        }

        sendResponse(true, 'Stock alerts generated.', [
            'total_alerts_count' => count($alertItems),
            'out_of_stock_count' => count($outOfStock),
            'low_stock_count'    => count($lowStock),
            'out_of_stock_items' => $outOfStock,
            'low_stock_items'    => $lowStock
        ]);
    }

    // Get Single Inventory Item
    if ($id) {
        $stmt = $db->prepare("
            SELECT i.*, 
                   COALESCE(i.item_name, p.product_name, 'Raw Ingredient') AS display_name,
                   p.product_name, s.supplier_name, s.phone AS supplier_phone, s.email AS supplier_email
            FROM Inventory i
            LEFT JOIN Products p ON i.product_id = p.product_id
            LEFT JOIN Suppliers s ON i.supplier_id = s.supplier_id
            WHERE i.inventory_id = ?
        ");
        $stmt->execute([$id]);
        $item = $stmt->fetch();

        if (!$item) {
            sendResponse(false, 'Inventory item not found.', null, 404);
        }

        $item['quantity']      = (float)$item['quantity'];
        $item['reorder_level'] = (float)$item['reorder_level'];
        $item['unit_cost']     = (float)$item['unit_cost'];
        $item['status']        = computeStockStatus($item['quantity'], $item['reorder_level']);

        sendResponse(true, 'Inventory item retrieved.', $item);
    }

    // List Inventory Items
    $statusFilter = $_GET['status'] ?? null;
    $supFilter    = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : null;
    $search       = $_GET['search'] ?? '';

    $sql = "
        SELECT i.*, 
               COALESCE(i.item_name, p.product_name, 'Raw Ingredient') AS display_name,
               p.product_name, s.supplier_name
        FROM Inventory i
        LEFT JOIN Products p ON i.product_id = p.product_id
        LEFT JOIN Suppliers s ON i.supplier_id = s.supplier_id
        WHERE 1=1
    ";
    $params = [];

    if ($statusFilter) {
        if ($statusFilter === 'low') {
            $sql .= " AND i.quantity <= i.reorder_level AND i.quantity > 0";
        } elseif ($statusFilter === 'out_of_stock') {
            $sql .= " AND i.quantity <= 0";
        } elseif ($statusFilter === 'good') {
            $sql .= " AND i.quantity > i.reorder_level";
        }
    }

    if ($supFilter) {
        $sql .= " AND i.supplier_id = ?";
        $params[] = $supFilter;
    }

    if ($search !== '') {
        $sql .= " AND (i.item_name LIKE ? OR p.product_name LIKE ? OR s.supplier_name LIKE ?)";
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= " ORDER BY i.inventory_id ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    foreach ($items as &$item) {
        $item['quantity']      = (float)$item['quantity'];
        $item['reorder_level'] = (float)$item['reorder_level'];
        $item['unit_cost']     = (float)$item['unit_cost'];
        $item['status']        = computeStockStatus($item['quantity'], $item['reorder_level']);
    }

    sendResponse(true, 'Inventory items retrieved.', [
        'count' => count($items),
        'items' => $items
    ]);
}

// ── POST: FR45 — Inventory Item Registration (Admin/Manager) ───────────────
if ($method === 'POST' && !$action) {
    requireAuth(['admin', 'manager']);
    $body = getRequestBody();

    $name = !empty($body['item_name']) ? trim($body['item_name']) : (!empty($body['name']) ? trim($body['name']) : '');
    if (empty($name)) {
        sendResponse(false, 'item_name is required.', null, 422);
    }

    $qty        = isset($body['quantity']) ? (float)$body['quantity'] : 0.0;
    $minLevel   = isset($body['reorder_level']) ? (float)$body['reorder_level'] : 5.0;
    $unit       = !empty($body['unit']) ? trim($body['unit']) : 'kg';
    $unitCost   = isset($body['unit_cost']) ? (float)$body['unit_cost'] : 0.0;
    $supplierId = !empty($body['supplier_id']) ? (int)$body['supplier_id'] : null;
    $productId  = !empty($body['product_id']) ? (int)$body['product_id'] : null;
    $status     = computeStockStatus($qty, $minLevel);

    $stmt = $db->prepare("
        INSERT INTO Inventory (item_name, product_id, supplier_id, quantity, reorder_level, unit, unit_cost, status, last_updated)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $name,
        $productId,
        $supplierId,
        $qty,
        $minLevel,
        $unit,
        $unitCost,
        $status
    ]);
    $newId = (int)$db->lastInsertId();

    // Record initial stock transaction
    if ($qty > 0) {
        $db->prepare("
            INSERT INTO InventoryTransactions (inventory_id, transaction_type, quantity_change, quantity_after, reason, performed_by, created_at)
            VALUES (?, 'initial_stock', ?, ?, 'Initial inventory stock registration', ?, NOW())
        ")->execute([$newId, $qty, $qty, $_SESSION['username'] ?? 'Admin']);
    }

    sendResponse(true, 'Inventory item registered successfully.', [
        'inventory_id'  => $newId,
        'item_name'     => $name,
        'quantity'      => $qty,
        'unit'          => $unit,
        'reorder_level' => $minLevel,
        'unit_cost'     => $unitCost,
        'status'        => $status
    ], 201);
}

// ── PUT: FR46 — Inventory Detail Management (Admin/Manager) ────────────────
if ($method === 'PUT') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Inventory ID is required for update.', null, 422);
    }

    $body = getRequestBody();
    $allowed = ['item_name', 'supplier_id', 'product_id', 'quantity', 'reorder_level', 'unit', 'unit_cost', 'status'];
    $fields = [];
    $values = [];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $fields[] = "$field = ?";
            if ($field === 'quantity' || $field === 'reorder_level' || $field === 'unit_cost') {
                $values[] = (float)$body[$field];
            } elseif ($field === 'supplier_id' || $field === 'product_id') {
                $values[] = !empty($body[$field]) ? (int)$body[$field] : null;
            } else {
                $values[] = $body[$field];
            }
        }
    }

    if (empty($fields)) {
        sendResponse(false, 'No valid fields provided for update.', null, 422);
    }

    // Verify item exists
    $check = $db->prepare("SELECT * FROM Inventory WHERE inventory_id = ?");
    $check->execute([$id]);
    $existing = $check->fetch();

    if (!$existing) {
        sendResponse(false, 'Inventory item not found.', null, 404);
    }

    // Auto update status if quantity or reorder_level changed
    $newQty = isset($body['quantity']) ? (float)$body['quantity'] : (float)$existing['quantity'];
    $newMin = isset($body['reorder_level']) ? (float)$body['reorder_level'] : (float)$existing['reorder_level'];
    $fields[] = "status = ?";
    $values[] = computeStockStatus($newQty, $newMin);

    $fields[] = "last_updated = NOW()";

    $values[] = $id;
    $db->prepare("UPDATE Inventory SET " . implode(', ', $fields) . " WHERE inventory_id = ?")->execute($values);

    sendResponse(true, 'Inventory details updated successfully.');
}

// ── DELETE: FR46 — Delete Inventory Item (Admin/Manager) ───────────────────
if ($method === 'DELETE') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Inventory ID is required for deletion.', null, 422);
    }

    $check = $db->prepare("SELECT inventory_id FROM Inventory WHERE inventory_id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(false, 'Inventory item not found.', null, 404);
    }

    $stmt = $db->prepare("DELETE FROM Inventory WHERE inventory_id = ?");
    $stmt->execute([$id]);

    sendResponse(true, 'Inventory item deleted successfully.');
}

sendResponse(false, 'Method not allowed.', null, 405);
