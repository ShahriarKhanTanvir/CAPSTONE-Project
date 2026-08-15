<?php
/**
 * recipes.php
 * 13. Recipe Management
 * FR49: Recipe creation
 * FR50: Ingredient assignment
 * FR51: Automatic stock deduction
 * FR52: Out-of-stock menu restriction
 *
 * Routes:
 *   GET  /api/recipes/recipes.php                               — List all recipes with assigned ingredients
 *   GET  /api/recipes/recipes.php?product_id=123               — Get recipe card & max portions available for a product
 *   POST /api/recipes/recipes.php                              — FR49 & FR50: Create/Assign recipe ingredients to product (Admin/Manager)
 *   PUT  /api/recipes/recipes.php?product_id=123               — Update recipe ingredients (Admin/Manager)
 *   DELETE /api/recipes/recipes.php?product_id=123             — Delete recipe (Admin/Manager)
 *   POST /api/recipes/recipes.php?action=deduct_stock          — FR51: Automatic stock deduction for order
 *   GET  /api/recipes/recipes.php?action=check_availability    — FR52: Audit & auto-restrict out-of-stock menu items
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db        = getDB();
$method    = $_SERVER['REQUEST_METHOD'];
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
$action    = $_GET['action'] ?? null;

// Gracefully ensure extra columns exist in Recipes
try { $db->exec("ALTER TABLE Recipes ADD COLUMN unit VARCHAR(50) NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Recipes ADD COLUMN prep_instructions TEXT NULL"); } catch (Exception $e) {}

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET ?action=check_availability: FR52 — Out-of-Stock Menu Restriction ───
if ($method === 'GET' && $action === 'check_availability') {
    // Audit all products that have recipes
    $prodStmt = $db->prepare("SELECT product_id, product_name, availability FROM Products");
    $prodStmt->execute();
    $products = $prodStmt->fetchAll();

    $restrictedProducts = [];
    $availableProducts  = [];

    foreach ($products as $p) {
        $pId = (int)$p['product_id'];

        // Get ingredients for this product
        $ingStmt = $db->prepare("
            SELECT r.quantity_required, i.inventory_id, COALESCE(i.item_name, 'Raw Stock') AS item_name, 
                   i.quantity AS current_stock, i.unit
            FROM Recipes r
            INNER JOIN Inventory i ON r.inventory_id = i.inventory_id
            WHERE r.product_id = ?
        ");
        $ingStmt->execute([$pId]);
        $ingredients = $ingStmt->fetchAll();

        $isOutOfStock = false;
        $missingReason = [];

        if (count($ingredients) > 0) {
            foreach ($ingredients as $ing) {
                $stock = (float)$ing['current_stock'];
                $req   = (float)$ing['quantity_required'];

                if ($stock < $req || $stock <= 0) {
                    $isOutOfStock = true;
                    $missingReason[] = "{$ing['item_name']} stock ($stock {$ing['unit']}) is below recipe requirement ($req {$ing['unit']})";
                }
            }
        }

        if ($isOutOfStock) {
            // Auto update product availability to 0
            $db->prepare("UPDATE Products SET availability = 0 WHERE product_id = ?")->execute([$pId]);
            $restrictedProducts[] = [
                'product_id'   => $pId,
                'product_name' => $p['product_name'],
                'status'       => 'Sold Out (Restricted)',
                'reasons'      => $missingReason
            ];
        } else {
            $availableProducts[] = [
                'product_id'   => $pId,
                'product_name' => $p['product_name'],
                'status'       => 'Available'
            ];
        }
    }

    sendResponse(true, 'Menu item availability audited against inventory.', [
        'restricted_out_of_stock_count' => count($restrictedProducts),
        'available_products_count'      => count($availableProducts),
        'restricted_products'           => $restrictedProducts,
        'available_products'            => $availableProducts
    ]);
}

// ── GET: List Recipes OR Get Product Recipe Card ───────────────────────────
if ($method === 'GET') {
    if ($productId) {
        $pStmt = $db->prepare("SELECT product_id, product_name, price, availability FROM Products WHERE product_id = ?");
        $pStmt->execute([$productId]);
        $product = $pStmt->fetch();

        if (!$product) {
            sendResponse(false, 'Product not found.', null, 404);
        }

        $rStmt = $db->prepare("
            SELECT r.recipe_id, r.quantity_required, r.unit AS recipe_unit, r.prep_instructions,
                   i.inventory_id, COALESCE(i.item_name, p.product_name) AS ingredient_name, 
                   i.quantity AS current_stock, i.unit AS stock_unit, i.unit_cost
            FROM Recipes r
            INNER JOIN Inventory i ON r.inventory_id = i.inventory_id
            LEFT JOIN Products p ON i.product_id = p.product_id
            WHERE r.product_id = ?
        ");
        $rStmt->execute([$productId]);
        $ingredients = $rStmt->fetchAll();

        // Compute recipe cost & max portions possible to make
        $totalRecipeCost = 0.0;
        $maxPortions = PHP_INT_MAX;

        foreach ($ingredients as &$ing) {
            $req = (float)$ing['quantity_required'];
            $cost = (float)$ing['unit_cost'];
            $stock = (float)$ing['current_stock'];

            $ing['quantity_required'] = $req;
            $ing['current_stock']     = $stock;
            $ing['unit_cost']         = $cost;
            $ing['line_cost']         = round($req * $cost, 3);
            $totalRecipeCost += $ing['line_cost'];

            if ($req > 0) {
                $portionsFromThis = floor($stock / $req);
                if ($portionsFromThis < $maxPortions) {
                    $maxPortions = $portionsFromThis;
                }
            }
        }

        if (count($ingredients) === 0) {
            $maxPortions = 0;
        }

        sendResponse(true, 'Recipe card retrieved.', [
            'product'              => $product,
            'total_recipe_cost'    => round($totalRecipeCost, 2),
            'profit_margin'        => round((float)$product['price'] - $totalRecipeCost, 2),
            'max_portions_available'=> max(0, $maxPortions),
            'ingredients_count'    => count($ingredients),
            'ingredients'          => $ingredients
        ]);
    }

    // List all recipes
    $stmt = $db->prepare("
        SELECT p.product_id, p.product_name, p.price, p.availability, c.category_name,
               COUNT(r.recipe_id) AS ingredient_count
        FROM Products p
        LEFT JOIN Categories c ON p.category_id = c.category_id
        LEFT JOIN Recipes r ON p.product_id = r.product_id
        GROUP BY p.product_id
        ORDER BY p.category_id ASC, p.product_name ASC
    ");
    $stmt->execute();
    $list = $stmt->fetchAll();

    sendResponse(true, 'Recipes summary retrieved.', [
        'count'   => count($list),
        'recipes' => $list
    ]);
}

// ── POST: FR49 & FR50 — Recipe Creation & Ingredient Assignment ────────────
if ($method === 'POST' && !$action) {
    requireAuth(['admin', 'manager']);
    $body = getRequestBody();

    if (empty($body['product_id']) || empty($body['ingredients']) || !is_array($body['ingredients'])) {
        sendResponse(false, 'product_id and an array of ingredients are required.', null, 422);
    }

    $pId = (int)$body['product_id'];

    // Verify product exists
    $pCheck = $db->prepare("SELECT product_id, product_name FROM Products WHERE product_id = ?");
    $pCheck->execute([$pId]);
    $prod = $pCheck->fetch();
    if (!$prod) {
        sendResponse(false, 'Product not found.', null, 404);
    }

    $db->beginTransaction();

    try {
        // Clear previous recipe items for this product
        $db->prepare("DELETE FROM Recipes WHERE product_id = ?")->execute([$pId]);

        $ins = $db->prepare("
            INSERT INTO Recipes (product_id, inventory_id, quantity_required, unit, prep_instructions)
            VALUES (?, ?, ?, ?, ?)
        ");

        $assignedCount = 0;
        foreach ($body['ingredients'] as $ing) {
            $invId   = (int)($ing['inventory_id'] ?? 0);
            $qtyReq  = (float)($ing['quantity_required'] ?? 0.0);
            $unit    = $ing['unit'] ?? 'kg';
            $prepIns = $ing['prep_instructions'] ?? ($body['prep_instructions'] ?? null);

            if ($invId > 0 && $qtyReq > 0) {
                $ins->execute([$pId, $invId, $qtyReq, $unit, $prepIns]);
                $assignedCount++;
            }
        }

        $db->commit();

        sendResponse(true, "Recipe created with $assignedCount ingredients for {$prod['product_name']}.", [
            'product_id'        => $pId,
            'product_name'      => $prod['product_name'],
            'ingredients_count' => $assignedCount
        ], 201);

    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(false, 'Failed to create recipe: ' . $e->getMessage(), null, 400);
    }
}

// ── POST ?action=deduct_stock: FR51 — Automatic Stock Deduction ────────────
if ($method === 'POST' && $action === 'deduct_stock') {
    $body = getRequestBody();

    if (empty($body['items']) && empty($body['order_id'])) {
        sendResponse(false, 'Provide order_id or items array for stock deduction.', null, 422);
    }

    $orderId = !empty($body['order_id']) ? (int)$body['order_id'] : null;
    $items   = $body['items'] ?? [];

    if ($orderId && empty($items)) {
        $oiStmt = $db->prepare("SELECT product_id, quantity FROM OrderItems WHERE order_id = ?");
        $oiStmt->execute([$orderId]);
        $items = $oiStmt->fetchAll();
    }

    $db->beginTransaction();
    $deductions = [];

    try {
        foreach ($items as $item) {
            $pId = (int)$item['product_id'];
            $qty = max(1, (int)$item['quantity']);

            // Get recipe ingredients
            $rStmt = $db->prepare("
                SELECT r.inventory_id, r.quantity_required, i.quantity AS current_stock, i.reorder_level,
                       COALESCE(i.item_name, 'Raw Ingredient') AS item_name, i.unit
                FROM Recipes r
                INNER JOIN Inventory i ON r.inventory_id = i.inventory_id
                WHERE r.product_id = ?
            ");
            $rStmt->execute([$pId]);
            $ingredients = $rStmt->fetchAll();

            foreach ($ingredients as $ing) {
                $invId     = (int)$ing['inventory_id'];
                $deductAmt = (float)$ing['quantity_required'] * $qty;
                $current   = (float)$ing['current_stock'];
                $newQty    = max(0.0, round($current - $deductAmt, 3));
                $minLevel  = (float)$ing['reorder_level'];
                $newStatus = ($newQty <= 0) ? 'out_of_stock' : (($newQty <= $minLevel) ? 'low' : 'good');

                // Update inventory
                $db->prepare("
                    UPDATE Inventory 
                    SET quantity = ?, status = ?, last_updated = NOW() 
                    WHERE inventory_id = ?
                ")->execute([$newQty, $newStatus, $invId]);

                // Log to InventoryTransactions
                $reason = $orderId ? "Auto-deduction for Order #$orderId" : "Auto recipe stock deduction";
                $db->prepare("
                    INSERT INTO InventoryTransactions (inventory_id, transaction_type, quantity_change, quantity_after, reason, performed_by, created_at)
                    VALUES (?, 'order_deduction', ?, ?, ?, 'System Recipe Engine', NOW())
                ")->execute([$invId, -$deductAmt, $newQty, $reason]);

                $deductions[] = [
                    'inventory_id'    => $invId,
                    'item_name'       => $ing['item_name'],
                    'deducted_qty'    => $deductAmt,
                    'unit'            => $ing['unit'],
                    'remaining_stock' => $newQty,
                    'stock_status'    => $newStatus
                ];
            }
        }

        $db->commit();

        sendResponse(true, 'Automatic recipe stock deduction executed successfully.', [
            'deductions_count' => count($deductions),
            'deductions'       => $deductions
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(false, 'Stock deduction failed: ' . $e->getMessage(), null, 400);
    }
}

// ── DELETE: Delete Recipe for Product (Admin/Manager) ──────────────────────
if ($method === 'DELETE') {
    requireAuth(['admin', 'manager']);

    if (!$productId) {
        sendResponse(false, 'product_id is required for deletion.', null, 422);
    }

    $db->prepare("DELETE FROM Recipes WHERE product_id = ?")->execute([$productId]);
    sendResponse(true, 'Recipe deleted successfully.');
}

sendResponse(false, 'Method not allowed.', null, 405);
