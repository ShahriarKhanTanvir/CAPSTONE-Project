<?php
require_once __DIR__ . '/api/config/db.php';
$db = getDB();

try {
    $db->exec("INSERT IGNORE INTO Roles (role_id, role_name, description) VALUES (6, 'Customer', 'Registered customer with loyalty points and order history')");
    echo "Role inserted. ";
    
    // Add column if it doesn't exist
    $stmt = $db->query("SHOW COLUMNS FROM Customers LIKE 'user_id'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE Customers ADD COLUMN user_id INT NULL AFTER customer_id");
        $db->exec("ALTER TABLE Customers ADD CONSTRAINT fk_customers_user FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE SET NULL");
        echo "Column and FK added. ";
    } else {
        echo "Column already exists. ";
    }
    echo "Migration successful.";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
