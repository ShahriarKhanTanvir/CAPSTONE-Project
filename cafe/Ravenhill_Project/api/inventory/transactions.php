<?php
/**
 * transactions.php
 * FR47: Stock transaction recording
 *
 * Routes:
 *   POST /api/inventory/transactions.php                   — FR47: Record stock transaction (Restock, Waste/Spoilage, Manual Adjustment)
 *   GET  /api/inventory/transactions.php?inventory_id=123  — FR47: View stock transaction history for an item
 *   GET  /api/inventory/transactions.php                   — View recent stock transactions across all items
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$invId  = isset($_GET['inventory_id']) ? (int)$_GET['inventory_id'] : null;

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── POST: FR47 — Record Stock Transaction ──────────────────────────────────
if ($method === 'POST') {
    requireAuth(['admin', 'manager', 'barista']);
    $body = getRequestBody();

    $required = ['inventory_id', 'transaction_type', 'quantity_change'];
    foreach ($required as $field) {
        if (!isset($body[$field]) || $body[$field] === '') {
            sendResponse(false, "Missing required field: $field", null, 422);
        }
    }

    $inventoryId = (int)$body['inventory_id'];
    $type        = strtolower(trim($body['transaction_type'])); // 'restock', 'waste', 'manual_adjustment'
    $qtyChange   = (float)$body['quantity_change'];
    $reason      = $body['reason'] ?? ($type === 'restock' ? 'Supplier Delivery / Restock' : ($type === 'waste' ? 'Spoilage / Spill Waste' : 'Stocktake Adjustment'));
    $performedBy = !empty($body['performed_by']) ? trim($body['performed_by']) : ($_SESSION['username'] ?? 'Staff');

    // Fetch existing item
    $stmt = $db->prepare("SELECT * FROM Inventory WHERE inventory_id = ?");
    $stmt->execute([$inventoryId]);
    $item = $stmt->fetch();

    if (!$item) {
        sendResponse(false, 'Inventory item not found.', null, 404);
    }

    $currentQty = (float)$item['quantity'];

    // Adjust quantity based on type
    if ($type === 'waste') {
        $actualChange = -abs($qtyChange);
    } elseif ($type === 'restock') {
        $actualChange = abs($qtyChange);
    } else {
        $actualChange = $qtyChange;
    }

    $newQty = max(0.0, round($currentQty + $actualChange, 2));

    // Recompute stock status
    $minThreshold = (float)$item['reorder_level'];
    $newStatus = ($newQty <= 0) ? 'out_of_stock' : (($newQty <= $minThreshold) ? 'low' : 'good');

    $db->beginTransaction();

    try {
        // Update Inventory quantity and status
        $db->prepare("
            UPDATE Inventory 
            SET quantity = ?, status = ?, last_updated = NOW() 
            WHERE inventory_id = ?
        ")->execute([$newQty, $newStatus, $inventoryId]);

        // Insert Transaction Record
        $ins = $db->prepare("
            INSERT INTO InventoryTransactions (inventory_id, transaction_type, quantity_change, quantity_after, reason, performed_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $ins->execute([
            $inventoryId,
            $type,
            $actualChange,
            $newQty,
            $reason,
            $performedBy
        ]);
        $txnId = (int)$db->lastInsertId();

        $db->commit();

        sendResponse(true, "Stock transaction recorded for {$item['item_name']}.", [
            'transaction_id'  => $txnId,
            'inventory_id'    => $inventoryId,
            'item_name'       => $item['item_name'] ?? 'Ingredient',
            'type'            => $type,
            'quantity_change' => $actualChange,
            'quantity_before' => $currentQty,
            'quantity_after'  => $newQty,
            'unit'            => $item['unit'],
            'status'          => $newStatus,
            'reason'          => $reason,
            'performed_by'    => $performedBy,
            'timestamp'       => date('Y-m-d H:i:s')
        ], 201);

    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(false, 'Failed to record stock transaction: ' . $e->getMessage(), null, 400);
    }
}

// ── GET: View Stock Transactions ───────────────────────────────────────────
if ($method === 'GET') {
    $typeFilter = $_GET['type'] ?? null;
    $dateFilter = $_GET['date'] ?? null;

    $sql = "
        SELECT it.*, 
               COALESCE(i.item_name, p.product_name, 'Raw Ingredient') AS item_name,
               i.unit, i.unit_cost
        FROM InventoryTransactions it
        LEFT JOIN Inventory i ON it.inventory_id = i.inventory_id
        LEFT JOIN Products p ON i.product_id = p.product_id
        WHERE 1=1
    ";
    $params = [];

    if ($invId) {
        $sql .= " AND it.inventory_id = ?";
        $params[] = $invId;
    }

    if ($typeFilter) {
        $sql .= " AND LOWER(it.transaction_type) = LOWER(?)";
        $params[] = $typeFilter;
    }

    if ($dateFilter) {
        $sql .= " AND DATE(it.created_at) = ?";
        $params[] = $dateFilter;
    }

    $sql .= " ORDER BY it.created_at DESC LIMIT 100";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $txns = $stmt->fetchAll();

    foreach ($txns as &$t) {
        $t['quantity_change'] = (float)$t['quantity_change'];
        $t['quantity_after']  = (float)$t['quantity_after'];
    }

    sendResponse(true, 'Stock transactions retrieved.', [
        'count'        => count($txns),
        'transactions' => $txns
    ]);
}

sendResponse(false, 'Method not allowed.', null, 405);
