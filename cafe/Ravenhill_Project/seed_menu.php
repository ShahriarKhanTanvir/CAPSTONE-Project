<?php
require_once __DIR__ . '/api/config/db.php';

$db = getDB();

$categories = [
    "Coffee", "Hot Drinks", "Tea", "Cold Coffee", "Cold Drinks", 
    "Smoothies", "Juices", "Breakfast", "Toasties", "Sandwiches", 
    "Pastries", "Bakery", "Lunch", "Sides"
];

$products = [
    "Coffee" => [
        ["Espresso / Short Black", 4.00], ["Long Black", 4.80], ["Flat White", 5.20],
        ["Latte", 5.20], ["Cappuccino", 5.20], ["Piccolo Latte", 4.80],
        ["Short Macchiato", 4.50], ["Long Macchiato", 5.20], ["Mocha", 5.80],
        ["Babycino", 2.50]
    ],
    "Hot Drinks" => [
        ["Hot Chocolate", 5.50], ["White Hot Chocolate", 5.70], ["Chai Latte", 5.70],
        ["Dirty Chai", 6.20], ["Matcha Latte", 6.20], ["Turmeric Latte", 5.90]
    ],
    "Tea" => [
        ["English Breakfast Tea", 4.80], ["Earl Grey Tea", 4.80], ["Green Tea", 4.80],
        ["Peppermint Tea", 4.80], ["Chamomile Tea", 4.80], ["Lemongrass & Ginger Tea", 4.80]
    ],
    "Cold Coffee" => [
        ["Iced Latte", 6.80], ["Iced Long Black", 6.20], ["Iced Coffee", 7.80], ["Iced Mocha", 8.20]
    ],
    "Cold Drinks" => [
        ["Iced Chocolate", 7.80], ["Iced Chai Latte", 7.20], ["Iced Matcha Latte", 7.80],
        ["Milkshake", 8.50], ["Bottled Still Water", 3.50], ["Sparkling Water", 5.00], ["Soft Drink", 4.50]
    ],
    "Smoothies" => [
        ["Smoothie with options such as Banana, Mixed Berry, Mango and Tropical", 9.50]
    ],
    "Juices" => [
        ["Fresh Orange Juice", 8.50], ["Fresh Apple Juice", 8.50], ["Green Juice", 9.50]
    ],
    "Breakfast" => [
        ["Sourdough Toast", 6.50], ["Eggs on Toast", 15.00], ["Bacon & Egg Roll", 12.00],
        ["Breakfast Wrap", 13.50], ["Avocado Toast", 18.50], ["Granola & Yoghurt", 15.50],
        ["Porridge", 15.00], ["Eggs Benedict", 21.00], ["Breakfast Burger", 16.00]
    ],
    "Toasties" => [
        ["Ham & Cheese Toastie", 12.50], ["Cheese & Tomato Toastie", 11.50],
        ["Three Cheese Toastie", 14.00], ["Tuna Melt", 15.50]
    ],
    "Sandwiches" => [
        ["BLT Toasted Sandwich", 13.50], ["Chicken & Avocado Sandwich", 16.50]
    ],
    "Pastries" => [
        ["Plain Croissant", 6.50], ["Almond Croissant", 8.00], ["Chocolate Croissant", 7.50],
        ["Ham & Cheese Croissant", 9.50], ["Fruit Danish", 7.00]
    ],
    "Bakery" => [
        ["Blueberry Muffin", 6.50], ["Chocolate Muffin", 6.50], ["Banana Bread", 7.00],
        ["Blueberry Scone", 6.50]
    ],
    "Lunch" => [
        ["Seasonal Salad", 18.00], ["Chicken Caesar Salad", 21.00]
    ],
    "Sides" => [
        ["Chips", 8.50], ["Sweet Potato Chips", 10.50]
    ]
];

$modifiers = [
    // Milk Modifiers
    ["Milk", "Oat Milk", 0.80, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks", "Smoothies"]],
    ["Milk", "Soy Milk", 0.80, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks", "Smoothies"]],
    ["Milk", "Almond Milk", 0.80, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks", "Smoothies"]],
    ["Milk", "Lactose Free Milk", 0.80, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks", "Smoothies"]],
    
    // Coffee Modifiers
    ["Coffee Modifiers", "Extra Shot", 0.80, ["Coffee", "Cold Coffee"]],
    ["Coffee Modifiers", "Decaf", 0.80, ["Coffee", "Cold Coffee"]],
    
    // Size Modifiers
    ["Size", "Large", 0.80, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks", "Smoothies", "Juices", "Tea"]],
    
    // Flavour Modifiers
    ["Flavours", "Vanilla Syrup", 0.70, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks"]],
    ["Flavours", "Caramel Syrup", 0.70, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks"]],
    ["Flavours", "Hazelnut Syrup", 0.70, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks"]],
    
    // Other / Add-ons
    ["Add-ons", "Whipped Cream", 0.80, ["Hot Drinks", "Cold Coffee", "Cold Drinks"]],
    ["Add-ons", "Marshmallows", 0.70, ["Hot Drinks", "Cold Drinks"]],
    
    // Food Add-ons
    ["Food Add-ons", "Gluten Free Bread", 2.00, ["Breakfast", "Toasties", "Sandwiches"]],
    ["Food Add-ons", "Extra Cheese", 2.00, ["Breakfast", "Toasties", "Sandwiches", "Lunch"]],
    ["Food Add-ons", "Avocado", 3.00, ["Breakfast", "Toasties", "Sandwiches", "Lunch"]],
    ["Food Add-ons", "Bacon", 4.50, ["Breakfast", "Toasties", "Sandwiches", "Lunch"]],
    ["Food Add-ons", "Extra Egg", 2.50, ["Breakfast", "Toasties", "Lunch"]],
    ["Food Add-ons", "Hash Brown", 2.50, ["Breakfast", "Sides"]],
    ["Food Add-ons", "Smoked Salmon", 7.00, ["Breakfast", "Sandwiches", "Lunch"]],
    ["Food Add-ons", "Chicken", 5.00, ["Sandwiches", "Lunch"]],
    ["Food Add-ons", "Halloumi", 4.00, ["Breakfast", "Sandwiches", "Lunch"]],
];

try {
    echo "Starting DB Seeding...\n";
    $db->beginTransaction();
    
    // Ensure tables exist
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

    $category_map = [];

    // 1. Seed Categories
    echo "Seeding Categories...\n";
    foreach ($categories as $cat) {
        $stmt = $db->prepare("SELECT category_id FROM Categories WHERE category_name = ?");
        $stmt->execute([$cat]);
        if ($row = $stmt->fetch()) {
            $category_map[$cat] = $row['category_id'];
        } else {
            $ins = $db->prepare("INSERT INTO Categories (category_name) VALUES (?)");
            $ins->execute([$cat]);
            $category_map[$cat] = $db->lastInsertId();
        }
    }

    // 2. Seed Products
    echo "Seeding Products...\n";
    foreach ($products as $cat_name => $items) {
        $cat_id = $category_map[$cat_name];
        foreach ($items as $item) {
            $p_name = $item[0];
            $price = $item[1];

            $stmt = $db->prepare("SELECT product_id FROM Products WHERE product_name = ? AND category_id = ?");
            $stmt->execute([$p_name, $cat_id]);
            if (!$stmt->fetch()) {
                $ins = $db->prepare("INSERT INTO Products (category_id, product_name, price, availability) VALUES (?, ?, ?, 1)");
                $ins->execute([$cat_id, $p_name, $price]);
            }
        }
    }

    // 3. Seed Customisations
    echo "Seeding Customisations...\n";
    foreach ($modifiers as $mod) {
        $grp = $mod[0];
        $opt = $mod[1];
        $price = $mod[2];
        $cat_names = $mod[3];

        foreach ($cat_names as $cat_name) {
            if (!isset($category_map[$cat_name])) continue;
            $c_id = $category_map[$cat_name];

            $stmt = $db->prepare("SELECT customisation_id FROM Customisations WHERE group_name = ? AND option_name = ? AND category_id = ?");
            $stmt->execute([$grp, $opt, $c_id]);
            if (!$stmt->fetch()) {
                $ins = $db->prepare("INSERT INTO Customisations (group_name, option_name, extra_price, category_id, availability) VALUES (?, ?, ?, ?, 1)");
                $ins->execute([$grp, $opt, $price, $c_id]);
            }
        }
    }

    $db->commit();
    echo "Seeding completed successfully!\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "Failed: " . $e->getMessage() . "\n";
}
