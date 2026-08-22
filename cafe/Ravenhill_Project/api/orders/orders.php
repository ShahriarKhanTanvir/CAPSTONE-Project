<?php
/**
 * orders.php
 * FR25: Order creation
 * FR26: Order detail recording
 * FR27: Order modification
 * FR28: Order cancellation
 * FR29: Order status tracking
 * FR31: Order history viewing
 *
 * Routes:
 *   GET    /api/orders/orders.php                    — FR31: List / search orders (supports ?status=, ?customer_id=, ?date=, ?type=)
 *   GET    /api/orders/orders.php?id=123             — Get single order with line items & modifiers
 *   POST   /api/orders/orders.php                    — FR25 & FR26: Create order & record items
 *   PUT    /api/orders/orders.php?id=123             — FR27: Modify active order (items, notes, table)
 *   PATCH  /api/orders/orders.php?id=123&action=status — FR29: Update status (pending, preparing, ready, completed)
 *   POST   /api/orders/orders.php?id=123&action=cancel — FR28: Cancel order with reason
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;

// Gracefully ensure extra columns exist in Orders
try { $db->exec("ALTER TABLE Orders ADD COLUMN order_type VARCHAR(50) DEFAULT 'dine-in'"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Orders ADD COLUMN table_number INT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Orders ADD COLUMN discount_code VARCHAR(100) NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Orders ADD COLUMN discount_amount FLOAT DEFAULT 0.0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Orders ADD COLUMN notes TEXT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Orders ADD COLUMN cancellation_reason TEXT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Orders ADD COLUMN completed_at DATETIME NULL"); } catch (Exception $e) {}

// Gracefully ensure extra columns exist in OrderItems
try { $db->exec("ALTER TABLE OrderItems ADD COLUMN customisations_json TEXT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE OrderItems ADD COLUMN item_notes TEXT NULL"); } catch (Exception $e) {}

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET: FR31 — List Orders or Retrieve Single Order ───────────────────────
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("
            SELECT o.*, 
                   CONCAT(c.first_name, ' ', COALESCE(c.last_name, '')) AS customer_name,
                   c.phone AS customer_phone, c.email AS customer_email,
                   CONCAT(e.first_name, ' ', COALESCE(e.last_name, '')) AS employee_name,
                   p.payment_method, p.payment_status
            FROM Orders o
            LEFT JOIN Customers c ON o.customer_id = c.customer_id
            LEFT JOIN Employees e ON o.employee_id = e.employee_id
            LEFT JOIN Payments p ON o.order_id = p.order_id
            WHERE o.order_id = ?
        ");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            sendResponse(false, 'Order not found.', null, 404);
        }

        // Fetch Order Items
        $itemsStmt = $db->prepare("
            SELECT oi.*, pr.product_name, pr.image, pr.category_id, cat.category_name
            FROM OrderItems oi
            LEFT JOIN Products pr ON oi.product_id = pr.product_id
            LEFT JOIN Categories cat ON pr.category_id = cat.category_id
            WHERE oi.order_id = ?
        ");
        $itemsStmt->execute([$id]);
        $items = $itemsStmt->fetchAll();

        foreach ($items as &$item) {
            $item['customisations'] = !empty($item['customisations_json']) ? json_decode($item['customisations_json'], true) : [];
            $item['unit_price']     = (float)$item['unit_price'];
            $item['subtotal']       = (float)$item['subtotal'];
        }

        $order['items'] = $items;
        $order['total_amount'] = (float)$order['total_amount'];
        $order['discount_amount'] = (float)$order['discount_amount'];

        sendResponse(true, 'Order retrieved.', $order);
    }

    // List Orders
    $statusFilter = $_GET['status'] ?? null;
    $custIdFilter = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;
    $dateFilter   = $_GET['date'] ?? null;
    $typeFilter   = $_GET['type'] ?? null;
    $page         = max(1, (int)($_GET['page'] ?? 1));
    $limit        = max(1, min(100, (int)($_GET['limit'] ?? 50)));
    $offset       = ($page - 1) * $limit;

    $sql = "
        SELECT o.*, 
               CONCAT(c.first_name, ' ', COALESCE(c.last_name, '')) AS customer_name,
               c.phone AS customer_phone,
               CONCAT(e.first_name, ' ', COALESCE(e.last_name, '')) AS employee_name,
               p.payment_method, p.payment_status,
               (SELECT COUNT(*) FROM OrderItems WHERE order_id = o.order_id) AS total_items
        FROM Orders o
        LEFT JOIN Customers c ON o.customer_id = c.customer_id
        LEFT JOIN Employees e ON o.employee_id = e.employee_id
        LEFT JOIN Payments p ON o.order_id = p.order_id
        WHERE 1=1
    ";
    $params = [];

    if ($statusFilter) {
        $sql .= " AND LOWER(o.order_status) = LOWER(?)";
        $params[] = $statusFilter;
    }

    if ($custIdFilter) {
        $sql .= " AND o.customer_id = ?";
        $params[] = $custIdFilter;
    }

    if ($dateFilter) {
        $sql .= " AND DATE(o.created_at) = ?";
        $params[] = $dateFilter;
    }

    if ($typeFilter) {
        $sql .= " AND LOWER(o.order_type) = LOWER(?)";
        $params[] = $typeFilter;
    }

    $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$o) {
        $o['total_amount'] = (float)$o['total_amount'];
        $o['discount_amount'] = (float)$o['discount_amount'];
    }

    sendResponse(true, 'Orders retrieved.', [
        'page'   => $page,
        'limit'  => $limit,
        'count'  => count($orders),
        'orders' => $orders
    ]);
}

// ── POST: FR25 & FR26 — Order Creation & Detail Recording ──────────────────
if ($method === 'POST' && !$action) {
    $body = getRequestBody();

    if (empty($body['items']) || !is_array($body['items'])) {
        sendResponse(false, 'Order must contain at least one item.', null, 422);
    }

    $customerId     = !empty($body['customer_id']) ? (int)$body['customer_id'] : null;
    $employeeId     = !empty($body['employee_id']) ? (int)$body['employee_id'] : null;
    $orderType      = !empty($body['order_type']) ? $body['order_type'] : 'dine-in';
    $tableNumber    = !empty($body['table_number']) ? (int)$body['table_number'] : null;
    $discountCode   = $body['discount_code'] ?? null;
    $discountAmount = isset($body['discount_amount']) ? (float)$body['discount_amount'] : 0.0;
    $notes          = $body['notes'] ?? null;
    $initialStatus  = !empty($body['order_status']) ? $body['order_status'] : 'pending';

    $db->beginTransaction();

    try {
        // Calculate items subtotal and prepare items records
        $calculatedTotal = 0.0;
        $orderItemsData  = [];

        foreach ($body['items'] as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $quantity  = max(1, (int)($item['quantity'] ?? 1));
            $customisations = $item['customisations'] ?? [];
            $itemNotes      = $item['notes'] ?? null;

            // Fetch product base price
            $pStmt = $db->prepare("SELECT product_id, product_name, price, availability FROM Products WHERE product_id = ?");
            $pStmt->execute([$productId]);
            $product = $pStmt->fetch();

            if (!$product) {
                throw new Exception("Product ID $productId not found.");
            }

            if (!$product['availability']) {
                throw new Exception("Product '{$product['product_name']}' is currently sold out.");
            }

            $unitPrice = (float)$product['price'];

            // Add customisation costs
            if (!empty($customisations) && is_array($customisations)) {
                foreach ($customisations as $c) {
                    $unitPrice += (float)($c['extra_price'] ?? 0.0);
                }
            }

            $subtotal = $unitPrice * $quantity;
            $calculatedTotal += $subtotal;

            $orderItemsData[] = [
                'product_id'          => $productId,
                'quantity'            => $quantity,
                'unit_price'          => $unitPrice,
                'subtotal'            => $subtotal,
                'customisations_json' => !empty($customisations) ? json_encode($customisations) : null,
                'item_notes'          => $itemNotes
            ];
        }

        $finalTotal = max(0.0, $calculatedTotal - $discountAmount);

        // Insert Order Record
        $orderStmt = $db->prepare("
            INSERT INTO Orders (customer_id, employee_id, total_amount, order_status, order_type, table_number, discount_code, discount_amount, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $orderStmt->execute([
            $customerId,
            $employeeId,
            $finalTotal,
            $initialStatus,
            $orderType,
            $tableNumber,
            $discountCode,
            $discountAmount,
            $notes
        ]);
        $orderId = (int)$db->lastInsertId();

        // Insert Line Items and categorize by Station (FR26 / Multi-Station KDS)
        $stationItems = ['barista' => [], 'kitchen' => []];

        $itemIns = $db->prepare("
            INSERT INTO OrderItems (order_id, product_id, quantity, unit_price, subtotal, customisations_json, item_notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($orderItemsData as $d) {
            $itemIns->execute([
                $orderId,
                $d['product_id'],
                $d['quantity'],
                $d['unit_price'],
                $d['subtotal'],
                $d['customisations_json'],
                $d['item_notes']
            ]);
            $insertedItemId = (int)$db->lastInsertId();

            // Fetch product's category target station
            $catStmt = $db->prepare("
                SELECT COALESCE(cat.target_station, CASE WHEN p.category_id BETWEEN 1 AND 7 THEN 'barista' ELSE 'kitchen' END) AS target_station
                FROM Products p
                LEFT JOIN Categories cat ON p.category_id = cat.category_id
                WHERE p.product_id = ?
            ");
            $catStmt->execute([$d['product_id']]);
            $catRow = $catStmt->fetch(PDO::FETCH_ASSOC);
            $station = ($catRow && $catRow['target_station'] === 'kitchen') ? 'kitchen' : 'barista';

            $stationItems[$station][] = $insertedItemId;
        }

        // Create StationTickets for each station that has items
        foreach ($stationItems as $st => $itemIds) {
            if (count($itemIds) > 0) {
                $targetMinutes = ($st === 'kitchen') ? 12 : 4;
                $tStmt = $db->prepare("
                    INSERT INTO StationTickets (order_id, station, status, target_prep_minutes, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $tStmt->execute([$orderId, $st, $initialStatus, $targetMinutes]);
                $ticketId = (int)$db->lastInsertId();

                $inClause = implode(',', array_map('intval', $itemIds));
                $db->exec("UPDATE OrderItems SET ticket_id = $ticketId WHERE order_item_id IN ($inClause)");
            }
        }

        // If table number given, auto mark table as occupied
        if ($tableNumber) {
            $db->prepare("UPDATE DiningTables SET status = 'occupied', assigned_order_id = ? WHERE table_number = ?")
               ->execute([$orderId, $tableNumber]);
        }

        $db->commit();

        sendResponse(true, 'Order created successfully.', [
            'order_id'        => $orderId,
            'total_amount'    => $finalTotal,
            'order_status'    => $initialStatus,
            'order_type'      => $orderType,
            'table_number'    => $tableNumber,
            'items_count'     => count($orderItemsData),
            'discount_amount' => $discountAmount
        ], 201);

    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(false, 'Failed to create order: ' . $e->getMessage(), null, 400);
    }
}

// ── PUT: FR27 — Order Modification ─────────────────────────────────────────
if ($method === 'PUT') {
    if (!$id) {
        sendResponse(false, 'Order ID is required for modification.', null, 422);
    }

    $body = getRequestBody();

    // Check order exists and is not already completed/cancelled
    $check = $db->prepare("SELECT * FROM Orders WHERE order_id = ?");
    $check->execute([$id]);
    $order = $check->fetch();

    if (!$order) {
        sendResponse(false, 'Order not found.', null, 404);
    }

    if (in_array($order['order_status'], ['completed', 'cancelled'])) {
        sendResponse(false, "Cannot modify an order with status '{$order['order_status']}'.", null, 400);
    }

    $db->beginTransaction();

    try {
        // If items are being replaced/updated
        if (isset($body['items']) && is_array($body['items'])) {
            // Delete existing items
            $db->prepare("DELETE FROM OrderItems WHERE order_id = ?")->execute([$id]);

            $calculatedTotal = 0.0;
            $itemIns = $db->prepare("
                INSERT INTO OrderItems (order_id, product_id, quantity, unit_price, subtotal, customisations_json, item_notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($body['items'] as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $quantity  = max(1, (int)($item['quantity'] ?? 1));
                $customisations = $item['customisations'] ?? [];
                $itemNotes      = $item['notes'] ?? null;

                $pStmt = $db->prepare("SELECT price FROM Products WHERE product_id = ?");
                $pStmt->execute([$productId]);
                $prod = $pStmt->fetch();
                $unitPrice = $prod ? (float)$prod['price'] : 0.0;

                if (!empty($customisations) && is_array($customisations)) {
                    foreach ($customisations as $c) {
                        $unitPrice += (float)($c['extra_price'] ?? 0.0);
                    }
                }

                $subtotal = $unitPrice * $quantity;
                $calculatedTotal += $subtotal;

                $itemIns->execute([
                    $id,
                    $productId,
                    $quantity,
                    $unitPrice,
                    $subtotal,
                    !empty($customisations) ? json_encode($customisations) : null,
                    $itemNotes
                ]);
            }

            $discountAmount = isset($body['discount_amount']) ? (float)$body['discount_amount'] : (float)$order['discount_amount'];
            $finalTotal     = max(0.0, $calculatedTotal - $discountAmount);

            $db->prepare("UPDATE Orders SET total_amount = ?, discount_amount = ? WHERE order_id = ?")
               ->execute([$finalTotal, $discountAmount, $id]);
        }

        // Update other metadata fields if provided
        $allowed = ['order_type', 'table_number', 'notes', 'customer_id'];
        $fields = [];
        $values = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $body)) {
                $fields[] = "$field = ?";
                $values[] = $body[$field];
            }
        }

        if (!empty($fields)) {
            $values[] = $id;
            $db->prepare("UPDATE Orders SET " . implode(', ', $fields) . " WHERE order_id = ?")->execute($values);
        }

        $db->commit();
        sendResponse(true, 'Order modified successfully.');

    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(false, 'Failed to modify order: ' . $e->getMessage(), null, 400);
    }
}

// ── PATCH: FR29 — Order Status Tracking & Completion Actions ───────────────
if ($method === 'PATCH' && $action === 'status') {
    if (!$id) {
        sendResponse(false, 'Order ID is required for status change.', null, 422);
    }

    $body = getRequestBody();
    if (empty($body['status'])) {
        sendResponse(false, 'New status is required.', null, 422);
    }

    $newStatus = strtolower(trim($body['status']));
    $allowed   = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];

    if (!in_array($newStatus, $allowed, true)) {
        sendResponse(false, 'Invalid status. Allowed values: ' . implode(', ', $allowed), null, 422);
    }

    $stmt = $db->prepare("SELECT * FROM Orders WHERE order_id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if (!$order) {
        sendResponse(false, 'Order not found.', null, 404);
    }

    $db->beginTransaction();

    try {
        if ($newStatus === 'completed') {
            $db->prepare("UPDATE Orders SET order_status = 'completed', completed_at = NOW() WHERE order_id = ?")
               ->execute([$id]);

            // Release table if dine-in
            if (!empty($order['table_number'])) {
                $db->prepare("UPDATE DiningTables SET status = 'cleaning', assigned_order_id = NULL WHERE table_number = ?")
                   ->execute([$order['table_number']]);
            }

            // Recipe auto-stock deduction (FR51 / FR52)
            $itemsStmt = $db->prepare("SELECT product_id, quantity FROM OrderItems WHERE order_id = ?");
            $itemsStmt->execute([$id]);
            $orderItems = $itemsStmt->fetchAll();

            foreach ($orderItems as $oi) {
                $rStmt = $db->prepare("SELECT inventory_id, quantity_required FROM Recipes WHERE product_id = ?");
                $rStmt->execute([$oi['product_id']]);
                $recipes = $rStmt->fetchAll();

                foreach ($recipes as $r) {
                    $deductQty = (float)$r['quantity_required'] * (int)$oi['quantity'];
                    $db->prepare("
                        UPDATE Inventory 
                        SET quantity = GREATEST(0, quantity - ?), last_updated = NOW() 
                        WHERE inventory_id = ?
                    ")->execute([$deductQty, $r['inventory_id']]);
                }
            }

            // Loyalty points award (FR42-44): 10 points per $1
            if (!empty($order['customer_id'])) {
                $pointsEarned = (int)floor((float)$order['total_amount'] * 10);
                if ($pointsEarned > 0) {
                    $db->prepare("UPDATE Customers SET loyalty_points = loyalty_points + ? WHERE customer_id = ?")
                       ->execute([$pointsEarned, $order['customer_id']]);

                    $db->prepare("
                        INSERT INTO LoyaltyTransactions (customer_id, order_id, points_earned, points_redeemed, transaction_date)
                        VALUES (?, ?, ?, 0, NOW())
                    ")->execute([$order['customer_id'], $id, $pointsEarned]);
                }
            }

        } else {
            $db->prepare("UPDATE Orders SET order_status = ? WHERE order_id = ?")->execute([$newStatus, $id]);
        }

        $db->commit();

        sendResponse(true, "Order #$id status changed to $newStatus.", [
            'order_id'     => $id,
            'order_status' => $newStatus
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(false, 'Failed to update order status: ' . $e->getMessage(), null, 400);
    }
}

// ── POST: FR28 — Order Cancellation ────────────────────────────────────────
if ($method === 'POST' && $action === 'cancel') {
    if (!$id) {
        sendResponse(false, 'Order ID is required for cancellation.', null, 422);
    }

    $body   = getRequestBody();
    $reason = $body['reason'] ?? 'Customer request / Cancelled by staff';

    $stmt = $db->prepare("SELECT * FROM Orders WHERE order_id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if (!$order) {
        sendResponse(false, 'Order not found.', null, 404);
    }

    $db->prepare("
        UPDATE Orders 
        SET order_status = 'cancelled', cancellation_reason = ? 
        WHERE order_id = ?
    ")->execute([$reason, $id]);

    // Release table if occupied
    if (!empty($order['table_number'])) {
        $db->prepare("UPDATE DiningTables SET status = 'available', assigned_order_id = NULL WHERE table_number = ?")
           ->execute([$order['table_number']]);
    }

    sendResponse(true, "Order #$id has been cancelled.", [
        'order_id'            => $id,
        'order_status'        => 'cancelled',
        'cancellation_reason' => $reason
    ]);
}

sendResponse(false, 'Method not allowed.', null, 405);
