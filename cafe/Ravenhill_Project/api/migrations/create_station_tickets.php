<?php
/**
 * create_station_tickets.php - Database Migration for Multi-Station KDS & Roles
 * Ravenhill Coffee POS & Management System
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDB();

    // 1. Create StationTickets table
    $db->exec("
        CREATE TABLE IF NOT EXISTS StationTickets (
            ticket_id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            station ENUM('kitchen', 'barista') NOT NULL,
            status ENUM('pending', 'preparing', 'ready', 'collected', 'cancelled') DEFAULT 'pending',
            target_prep_minutes INT DEFAULT 5,
            started_at DATETIME NULL,
            ready_at DATETIME NULL,
            collected_at DATETIME NULL,
            bumped_by_employee_id INT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE,
            FOREIGN KEY (bumped_by_employee_id) REFERENCES Employees(employee_id) ON DELETE SET NULL,
            INDEX idx_station_status (station, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 2. Add ticket_id to OrderItems if not exists
    $colCheck = $db->query("SHOW COLUMNS FROM OrderItems LIKE 'ticket_id'")->fetch();
    if (!$colCheck) {
        $db->exec("
            ALTER TABLE OrderItems 
            ADD COLUMN ticket_id INT NULL AFTER order_id,
            ADD CONSTRAINT fk_orderitems_ticket FOREIGN KEY (ticket_id) REFERENCES StationTickets(ticket_id) ON DELETE SET NULL
        ");
    }

    // 3. Add target_station to Categories if not exists
    $catColCheck = $db->query("SHOW COLUMNS FROM Categories LIKE 'target_station'")->fetch();
    if (!$catColCheck) {
        $db->exec("
            ALTER TABLE Categories 
            ADD COLUMN target_station ENUM('kitchen', 'barista') DEFAULT 'kitchen' AFTER category_name
        ");
    }

    // 4. Update Categories target_station: Categories 1-7 = barista, 8-14 = kitchen
    $db->exec("UPDATE Categories SET target_station = 'barista' WHERE category_id BETWEEN 1 AND 7 OR category_name LIKE '%Coffee%' OR category_name LIKE '%Drink%' OR category_name LIKE '%Tea%' OR category_name LIKE '%Juice%' OR category_name LIKE '%Smoothie%'");
    $db->exec("UPDATE Categories SET target_station = 'kitchen' WHERE category_id >= 8 OR category_name LIKE '%Breakfast%' OR category_name LIKE '%Toast%' OR category_name LIKE '%Sandwich%' OR category_name LIKE '%Bakery%' OR category_name LIKE '%Pastr%' OR category_name LIKE '%Lunch%' OR category_name LIKE '%Side%'");

    // 5. Ensure Roles table has 'kitchen', 'barista', 'customer'
    $rolesToEnsure = [
        ['kitchen', 'Kitchen Staff - Prepares food and bakery orders on Kitchen KDS'],
        ['barista', 'Barista - Prepares coffee, tea, and specialty beverages on Barista KDS'],
        ['customer', 'Customer - Views live order status, self-service tracking, and loyalty']
    ];

    foreach ($rolesToEnsure as $r) {
        $rCheck = $db->prepare("SELECT role_id FROM Roles WHERE role_name = ?");
        $rCheck->execute([$r[0]]);
        if (!$rCheck->fetch()) {
            $db->prepare("INSERT INTO Roles (role_name, description) VALUES (?, ?)")->execute([$r[0], $r[1]]);
        }
    }

    // 6. Generate StationTickets for existing active Orders if missing
    $activeOrders = $db->query("
        SELECT o.order_id, o.order_status, o.created_at 
        FROM Orders o 
        WHERE o.order_status IN ('pending', 'preparing', 'ready')
    ")->fetchAll(PDO::FETCH_ASSOC);

    $migratedCount = 0;
    foreach ($activeOrders as $o) {
        $oid = (int)$o['order_id'];
        
        // Fetch items for order
        $itemsStmt = $db->prepare("
            SELECT oi.order_item_id, oi.product_id, COALESCE(cat.target_station, 'barista') AS station
            FROM OrderItems oi
            LEFT JOIN Products p ON oi.product_id = p.product_id
            LEFT JOIN Categories cat ON p.category_id = cat.category_id
            WHERE oi.order_id = ?
        ");
        $itemsStmt->execute([$oid]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        $stationsNeeded = [];
        foreach ($items as $item) {
            $st = $item['station'] === 'kitchen' ? 'kitchen' : 'barista';
            $stationsNeeded[$st][] = (int)$item['order_item_id'];
        }

        foreach ($stationsNeeded as $st => $itemIds) {
            // Check if ticket already exists
            $tCheck = $db->prepare("SELECT ticket_id FROM StationTickets WHERE order_id = ? AND station = ?");
            $tCheck->execute([$oid, $st]);
            $existing = $tCheck->fetch(PDO::FETCH_ASSOC);

            $ticketId = $existing ? (int)$existing['ticket_id'] : null;
            if (!$ticketId) {
                $status = $o['order_status'] === 'preparing' ? 'preparing' : 'pending';
                $tInsert = $db->prepare("
                    INSERT INTO StationTickets (order_id, station, status, created_at)
                    VALUES (?, ?, ?, ?)
                ");
                $tInsert->execute([$oid, $st, $status, $o['created_at']]);
                $ticketId = (int)$db->lastInsertId();
                $migratedCount++;
            }

            // Link items
            if ($ticketId && count($itemIds) > 0) {
                $inList = implode(',', array_map('intval', $itemIds));
                $db->exec("UPDATE OrderItems SET ticket_id = $ticketId WHERE order_item_id IN ($inList)");
            }
        }
    }

    sendResponse(true, "StationTickets schema migration completed successfully! Migrated $migratedCount station tickets.", [
        'active_orders_checked' => count($activeOrders),
        'tickets_created'       => $migratedCount
    ]);

} catch (Exception $e) {
    sendResponse(false, "Migration failed: " . $e->getMessage(), null, 500);
}
