<?php
/**
 * seed_user_credentials.php
 * Sets up the requested simplified credentials:
 *   admin/admin
 *   manager/manager
 *   cashier/cashier
 *   barista/barista
 *   kitchen/kitchen
 *   waiter/waiter
 *   customer/customer
 */

require_once __DIR__ . '/api/config/db.php';

try {
    $db = getDB();

    // 1. Ensure Roles table has all 7 roles
    $roles = [
        [1, 'Admin', 'Full administrative access across all modules and settings'],
        [2, 'Manager', 'Store operations, inventory management, staff scheduling, and reporting'],
        [3, 'Cashier', 'Front-of-house point of sale, order taking, table booking, and customer management'],
        [4, 'Barista', 'Beverage preparation and order status management via KDS'],
        [5, 'Kitchen', 'Kitchen display system and food preparation management'],
        [6, 'Customer', 'Customer ordering portal and loyalty management'],
        [7, 'Waitstaff', 'Floor map, table seating management, and waitstaff orders']
    ];

    foreach ($roles as $r) {
        $stmt = $db->prepare("INSERT INTO Roles (role_id, role_name, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE role_name = ?, description = ?");
        $stmt->execute([$r[0], $r[1], $r[2], $r[1], $r[2]]);
    }

    // 2. Setup user accounts
    $accounts = [
        ['username' => 'admin',    'password' => 'admin',    'role_id' => 1, 'first_name' => 'Ravenhill', 'last_name' => 'Admin',    'position' => 'System Admin',     'email' => 'admin@ravenhill.au',    'type' => 'employee'],
        ['username' => 'manager',  'password' => 'manager',  'role_id' => 2, 'first_name' => 'Alex',      'last_name' => 'Vance',    'position' => 'Store Manager',    'email' => 'manager@ravenhill.au',  'type' => 'employee'],
        ['username' => 'cashier',  'password' => 'cashier',  'role_id' => 3, 'first_name' => 'Sarah',     'last_name' => 'Lin',      'position' => 'Lead Cashier',     'email' => 'cashier@ravenhill.au',  'type' => 'employee'],
        ['username' => 'barista',  'password' => 'barista',  'role_id' => 4, 'first_name' => 'Liam',      'last_name' => 'O\'Connor','position' => 'Head Barista',     'email' => 'barista@ravenhill.au',  'type' => 'employee'],
        ['username' => 'kitchen',  'password' => 'kitchen',  'role_id' => 5, 'first_name' => 'Marco',     'last_name' => 'Rossi',    'position' => 'Head Chef',        'email' => 'kitchen@ravenhill.au',  'type' => 'employee'],
        ['username' => 'waiter',   'password' => 'waiter',   'role_id' => 7, 'first_name' => 'Chloe',     'last_name' => 'Bennett',  'position' => 'Wait Staff',       'email' => 'waiter@ravenhill.au',   'type' => 'employee'],
        ['username' => 'customer', 'password' => 'customer', 'role_id' => 6, 'first_name' => 'Sophia',    'last_name' => 'Reed',     'position' => 'Loyalty Member',   'email' => 'customer@ravenhill.au', 'type' => 'customer']
    ];

    foreach ($accounts as $acc) {
        $hash = password_hash($acc['password'], PASSWORD_BCRYPT);

        // Check if user exists
        $stmt = $db->prepare("SELECT user_id FROM Users WHERE username = ?");
        $stmt->execute([$acc['username']]);
        $existing = $stmt->fetch();

        if ($existing) {
            $userId = (int)$existing['user_id'];
            $upd = $db->prepare("UPDATE Users SET password_hash = ?, role_id = ?, status = 'active' WHERE user_id = ?");
            $upd->execute([$hash, $acc['role_id'], $userId]);
        } else {
            $ins = $db->prepare("INSERT INTO Users (username, password_hash, role_id, status) VALUES (?, ?, ?, 'active')");
            $ins->execute([$acc['username'], $hash, $acc['role_id']]);
            $userId = (int)$db->lastInsertId();
        }

        if ($acc['type'] === 'employee') {
            // Check if employee record exists for this user_id
            $empStmt = $db->prepare("SELECT employee_id FROM Employees WHERE user_id = ?");
            $empStmt->execute([$userId]);
            $emp = $empStmt->fetch();

            if ($emp) {
                $updEmp = $db->prepare("UPDATE Employees SET first_name = ?, last_name = ?, position = ?, email = ? WHERE employee_id = ?");
                $updEmp->execute([$acc['first_name'], $acc['last_name'], $acc['position'], $acc['email'], $emp['employee_id']]);
            } else {
                $insEmp = $db->prepare("
                    INSERT INTO Employees (user_id, first_name, last_name, phone, email, position, hire_date, hourly_rate, pin, status)
                    VALUES (?, ?, ?, '0400 123 456', ?, ?, '2023-01-01', 32.00, '1234', 'active')
                ");
                $insEmp->execute([$userId, $acc['first_name'], $acc['last_name'], $acc['email'], $acc['position']]);
            }
        } else if ($acc['type'] === 'customer') {
            // Check customer record
            $custStmt = $db->prepare("SELECT customer_id FROM Customers WHERE user_id = ? OR email = ?");
            $custStmt->execute([$userId, $acc['email']]);
            $cust = $custStmt->fetch();

            if ($cust) {
                $updCust = $db->prepare("UPDATE Customers SET user_id = ?, first_name = ?, last_name = ?, email = ?, loyalty_tier = 'Gold', loyalty_points = 250 WHERE customer_id = ?");
                $updCust->execute([$userId, $acc['first_name'], $acc['last_name'], $acc['email'], $cust['customer_id']]);
            } else {
                $insCust = $db->prepare("
                    INSERT INTO Customers (user_id, first_name, last_name, phone, email, loyalty_points, loyalty_tier)
                    VALUES (?, ?, ?, '0411 223 344', ?, 250, 'Gold')
                ");
                $insCust->execute([$userId, $acc['first_name'], $acc['last_name'], $acc['email']]);
            }
        }
    }

    // Also update legacy usernames slin, loconnor, hwright to have password 'cashier', 'barista', 'barista' as convenience
    $legacy = [
        ['slin', 'cashier'],
        ['loconnor', 'barista'],
        ['hwright', 'kitchen']
    ];
    foreach ($legacy as $leg) {
        $h = password_hash($leg[1], PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE Users SET password_hash = ? WHERE username = ?");
        $stmt->execute([$h, $leg[0]]);
    }

    // 3. Clear any login attempts / lockouts
    $db->exec("DELETE FROM LoginAttempts");

    echo "SUCCESS: All 7 role accounts configured successfully!\n";
    echo "• admin / admin\n";
    echo "• manager / manager\n";
    echo "• cashier / cashier\n";
    echo "• barista / barista\n";
    echo "• kitchen / kitchen\n";
    echo "• waiter / waiter\n";
    echo "• customer / customer\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
