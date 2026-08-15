<?php
/**
 * purchase_orders.php
 * FR54: Purchase order creation
 * FR55: Purchase order tracking
 * FR56: Purchased stock receiving
 *
 * Routes:
 *   GET    /api/suppliers/purchase_orders.php                  — FR55: List & track POs (supports ?status=, ?supplier_id=)
 *   GET    /api/suppliers/purchase_orders.php?id=123           — Get single PO with line items and fulfillment breakdown
 *   POST   /api/suppliers/purchase_orders.php                  — FR54: Create purchase order with items (Admin/Manager)
 *   PUT    /api/suppliers/purchase_orders.php?id=123           — Update PO details / delivery date
 *   POST   /api/suppliers/purchase_orders.php?id=123&action=receive — FR56: Receive stock delivery, auto-increment inventory & log transactions
 *   POST   /api/suppliers/purchase_orders.php?id=123&action=cancel  — Cancel purchase order
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;

// Ensure PurchaseOrders & PurchaseOrderItems tables exist
$db->exec("
    CREATE TABLE IF NOT EXISTS PurchaseOrders (
        po_id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT NOT NULL,
        po_number VARCHAR(100) NOT NULL UNIQUE,
        total_cost FLOAT DEFAULT 0.0,
        status VARCHAR(50) DEFAULT 'ordered',
        order_date DATE,
        expected_delivery DATE,
        received_date DATETIME NULL,
        created_by VARCHAR(100) NULL,
        notes TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (supplier_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$db->exec("
    CREATE TABLE IF NOT EXISTS PurchaseOrderItems (
        po_item_id INT AUTO_INCREMENT PRIMARY KEY,
        po_id INT NOT NULL,
        inventory_id INT NOT NULL,
        quantity_ordered FLOAT NOT NULL,
        quantity_received FLOAT DEFAULT 0.0,
        unit_cost FLOAT NOT NULL,
        subtotal FLOAT NOT NULL,
        INDEX (po_id),
        INDEX (inventory_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET: FR55 — Purchase Order Tracking & History ──────────────────────────
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("
            SELECT po.*, s.supplier_name, s.contact_person, s.phone AS supplier_phone, s.email AS supplier_email
            FROM PurchaseOrders po
            LEFT JOIN Suppliers s ON po.supplier_id = s.supplier_id
            WHERE po.po_id = ?
        ");
        $stmt->execute([$id]);
        $po = $stmt->fetch();

        if (!$po) {
            sendResponse(false, 'Purchase order not found.', null, 404);
        }

        // Fetch line items
        $itemsStmt = $db->prepare("
            SELECT poi.*, COALESCE(i.item_name, 'Stock Item') AS item_name, i.unit, i.quantity AS current_stock
            FROM PurchaseOrderItems poi
            LEFT JOIN Inventory i ON poi.inventory_id = i.inventory_id
            WHERE poi.po_id = ?
        ");
        $itemsStmt->execute([$id]);
        $po['items'] = $itemsStmt->fetchAll();
        $po['total_cost'] = (float)$po['total_cost'];

        sendResponse(true, 'Purchase order retrieved.', $po);
    }

    $statusFilter = $_GET['status'] ?? null;
    $supFilter    = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : null;

    $sql = "
        SELECT po.*, s.supplier_name,
               (SELECT COUNT(*) FROM PurchaseOrderItems WHERE po_id = po.po_id) AS items_count
        FROM PurchaseOrders po
        LEFT JOIN Suppliers s ON po.supplier_id = s.supplier_id
        WHERE 1=1
    ";
    $params = [];

    if ($statusFilter) {
        $sql .= " AND LOWER(po.status) = LOWER(?)";
        $params[] = $statusFilter;
    }

    if ($supFilter) {
        $sql .= " AND po.supplier_id = ?";
        $params[] = $supFilter;
    }

    $sql .= " ORDER BY po.po_id DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$o) {
        $o['total_cost'] = (float)$o['total_cost'];
    }

    sendResponse(true, 'Purchase orders retrieved.', [
        'count'  => count($orders),
        'orders' => $orders
    ]);
}

// ── POST: FR54 — Purchase Order Creation ───────────────────────────────────
if ($method === 'POST' && !$action) {
    requireAuth(['admin', 'manager']);
    $body = getRequestBody();

    if (empty($body['supplier_id']) || empty($body['items']) || !is_array($body['items'])) {
        sendResponse(false, 'supplier_id and items array are required.', null, 422);
    }

    $supplierId = (int)$body['supplier_id'];
    $orderDate  = !empty($body['order_date']) ? $body['order_date'] : date('Y-m-d');
    $delivery   = !empty($body['expected_delivery']) ? $body['expected_delivery'] : date('Y-m-d', strtotime('+3 days'));
    $notes      = $body['notes'] ?? null;
    $createdBy  = $_SESSION['username'] ?? 'Manager';

    // Verify supplier
    $sCheck = $db->prepare("SELECT supplier_id, supplier_name FROM Suppliers WHERE supplier_id = ?");
    $sCheck->execute([$supplierId]);
    $supplier = $sCheck->fetch();
    if (!$supplier) {
        sendResponse(false, 'Supplier not found.', null, 404);
    }

    // Generate unique PO Number (e.g. PO-20260815-4821)
    $poNumber = 'PO-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 4));

    $db->beginTransaction();

    try {
        $totalCost = 0.0;
        $orderItemsData = [];

        foreach ($body['items'] as $item) {
            $invId   = (int)($item['inventory_id'] ?? 0);
            $qty     = (float)($item['quantity_ordered'] ?? ($item['quantity'] ?? 0));
            $unitCost= (float)($item['unit_cost'] ?? 0);

            // Fetch current inventory item
            $iStmt = $db->prepare("SELECT inventory_id, item_name, unit_cost FROM Inventory WHERE inventory_id = ?");
            $iStmt->execute([$invId]);
            $inv = $iStmt->fetch();

            if (!$inv) {
                throw new Exception("Inventory item ID $invId not found.");
            }

            if ($unitCost <= 0) {
                $unitCost = (float)$inv['unit_cost'];
            }

            $subtotal = $qty * $unitCost;
            $totalCost += $subtotal;

            $orderItemsData[] = [
                'inventory_id'     => $invId,
                'quantity_ordered' => $qty,
                'unit_cost'        => $unitCost,
                'subtotal'         => $subtotal
            ];
        }

        $poStmt = $db->prepare("
            INSERT INTO PurchaseOrders (supplier_id, po_number, total_cost, status, order_date, expected_delivery, created_by, notes)
            VALUES (?, ?, ?, 'ordered', ?, ?, ?, ?)
        ");
        $poStmt->execute([$supplierId, $poNumber, $totalCost, $orderDate, $delivery, $createdBy, $notes]);
        $poId = (int)$db->lastInsertId();

        $poiStmt = $db->prepare("
            INSERT INTO PurchaseOrderItems (po_id, inventory_id, quantity_ordered, quantity_received, unit_cost, subtotal)
            VALUES (?, ?, ?, 0, ?, ?)
        ");
        foreach ($orderItemsData as $d) {
            $poiStmt->execute([$poId, $d['inventory_id'], $d['quantity_ordered'], $d['unit_cost'], $d['subtotal']]);
        }

        $db->commit();

        sendResponse(true, "Purchase order $poNumber created successfully.", [
            'po_id'             => $poId,
            'po_number'         => $poNumber,
            'supplier_name'     => $supplier['supplier_name'],
            'total_cost'        => round($totalCost, 2),
            'status'            => 'ordered',
            'expected_delivery' => $delivery,
            'items_count'       => count($orderItemsData)
        ], 201);

    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(false, 'Failed to create purchase order: ' . $e->getMessage(), null, 400);
    }
}

// ── POST ?action=receive: FR56 — Purchased Stock Receiving ─────────────────
if ($method === 'POST' && $action === 'receive') {
    requireAuth(['admin', 'manager', 'barista']);

    if (!$id) {
        sendResponse(false, 'PO ID is required to receive shipment.', null, 422);
    }

    $body = getRequestBody();

    $stmt = $db->prepare("SELECT * FROM PurchaseOrders WHERE po_id = ?");
    $stmt->execute([$id]);
    $po = $stmt->fetch();

    if (!$po) {
        sendResponse(false, 'Purchase order not found.', null, 404);
    }

    if ($po['status'] === 'received') {
        sendResponse(false, 'This purchase order has already been fully received.', null, 400);
    }

    // Fetch PO items
    $itemsStmt = $db->prepare("SELECT * FROM PurchaseOrderItems WHERE po_id = ?");
    $itemsStmt->execute([$id]);
    $items = $itemsStmt->fetchAll();

    $receivedItems = $body['received_items'] ?? null; // Optional custom received quantities array [{inventory_id, quantity_received}]

    $db->beginTransaction();
    $receivedSummary = [];

    try {
        foreach ($items as $item) {
            $invId = (int)$item['inventory_id'];
            $orderedQty = (float)$item['quantity_ordered'];

            // Determine received quantity
            $receivedQty = $orderedQty;
            if ($receivedItems && is_array($receivedItems)) {
                foreach ($receivedItems as $ri) {
                    if ((int)$ri['inventory_id'] === $invId && isset($ri['quantity_received'])) {
                        $receivedQty = (float)$ri['quantity_received'];
                        break;
                    }
                }
            }

            // Update PO Item quantity_received
            $db->prepare("UPDATE PurchaseOrderItems SET quantity_received = ? WHERE po_item_id = ?")
               ->execute([$receivedQty, $item['po_item_id']]);

            // Auto-increment Inventory stock
            $invStmt = $db->prepare("SELECT quantity, reorder_level, item_name, unit FROM Inventory WHERE inventory_id = ?");
            $invStmt->execute([$invId]);
            $inv = $invStmt->fetch();

            if ($inv) {
                $newQty = (float)$inv['quantity'] + $receivedQty;
                $minLevel = (float)$inv['reorder_level'];
                $newStatus = ($newQty <= 0) ? 'out_of_stock' : (($newQty <= $minLevel) ? 'low' : 'good');

                $db->prepare("
                    UPDATE Inventory 
                    SET quantity = ?, status = ?, last_updated = NOW() 
                    WHERE inventory_id = ?
                ")->execute([$newQty, $newStatus, $invId]);

                // Log Restock Transaction in InventoryTransactions
                $db->prepare("
                    INSERT INTO InventoryTransactions (inventory_id, transaction_type, quantity_change, quantity_after, reason, performed_by, created_at)
                    VALUES (?, 'restock', ?, ?, ?, ?, NOW())
                ")->execute([
                    $invId,
                    $receivedQty,
                    $newQty,
                    "Received Delivery from {$po['po_number']}",
                    $_SESSION['username'] ?? 'Manager'
                ]);

                $receivedSummary[] = [
                    'inventory_id'  => $invId,
                    'item_name'     => $inv['item_name'],
                    'qty_received'  => $receivedQty,
                    'unit'          => $inv['unit'],
                    'new_stock_qty' => $newQty,
                    'status'        => $newStatus
                ];
            }
        }

        // Mark Purchase Order as received
        $db->prepare("UPDATE PurchaseOrders SET status = 'received', received_date = NOW() WHERE po_id = ?")->execute([$id]);

        $db->commit();

        sendResponse(true, "Purchase order {$po['po_number']} received and stock successfully updated.", [
            'po_id'           => $id,
            'po_number'       => $po['po_number'],
            'status'          => 'received',
            'received_date'   => date('Y-m-d H:i:s'),
            'received_summary'=> $receivedSummary
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(false, 'Failed to receive stock: ' . $e->getMessage(), null, 400);
    }
}

// ── POST ?action=cancel: Cancel PO ─────────────────────────────────────────
if ($method === 'POST' && $action === 'cancel') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'PO ID is required for cancellation.', null, 422);
    }

    $db->prepare("UPDATE PurchaseOrders SET status = 'cancelled' WHERE po_id = ?")->execute([$id]);
    sendResponse(true, 'Purchase order cancelled.');
}

sendResponse(false, 'Method not allowed.', null, 405);
