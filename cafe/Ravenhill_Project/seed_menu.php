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
    // Coffee & Hot Drinks Size Modifiers
    ["Cup Size", "Regular (8oz)", 0.00, ["Coffee", "Hot Drinks", "Tea"]],
    ["Cup Size", "Large (12oz)", 0.80, ["Coffee", "Hot Drinks", "Tea"]],
    ["Cup Size", "Jumbo / Extra Large (16oz)", 1.50, ["Coffee", "Hot Drinks"]],
    
    // Cold Drinks Size Modifiers
    ["Cold Size", "Regular Chilled (16oz)", 0.00, ["Cold Coffee", "Cold Drinks", "Smoothies", "Juices"]],
    ["Cold Size", "Large Chilled (20oz)", 1.00, ["Cold Coffee", "Cold Drinks", "Smoothies", "Juices"]],

    // Milk & Dairy Alternatives
    ["Milk & Dairy Choice", "Full Cream Dairy Milk", 0.00, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks", "Smoothies"]],
    ["Milk & Dairy Choice", "Skinny / Light Milk", 0.00, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks", "Smoothies"]],
    ["Milk & Dairy Choice", "Oat Milk (Oatly Barista)", 0.80, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks", "Smoothies"]],
    ["Milk & Dairy Choice", "Almond Milk (Milklab)", 0.80, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks", "Smoothies"]],
    ["Milk & Dairy Choice", "Soy Milk (Bonsoy)", 0.70, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks", "Smoothies"]],
    ["Milk & Dairy Choice", "Coconut Milk (Milklab)", 0.80, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks", "Smoothies"]],
    ["Milk & Dairy Choice", "Lactose-Free Milk (Zymil)", 0.70, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks", "Smoothies"]],
    ["Milk & Dairy Choice", "No Milk (Black)", 0.00, ["Coffee", "Cold Coffee"]],

    // Espresso Roasts & Strength
    ["Espresso Roast & Origin", "Ravenhill Reserve Blend", 0.00, ["Coffee", "Cold Coffee"]],
    ["Espresso Roast & Origin", "Single Origin Ethiopian", 1.00, ["Coffee", "Cold Coffee"]],
    ["Espresso Roast & Origin", "Swiss Water Decaf", 0.70, ["Coffee", "Cold Coffee"]],
    ["Espresso Strength", "Standard Shot", 0.00, ["Coffee", "Cold Coffee"]],
    ["Espresso Strength", "Extra Espresso Shot (+1)", 0.80, ["Coffee", "Hot Drinks", "Cold Coffee"]],
    ["Espresso Strength", "Double Extra Shot (+2)", 1.50, ["Coffee", "Cold Coffee"]],
    ["Espresso Strength", "Half Strength", 0.00, ["Coffee", "Cold Coffee"]],
    ["Espresso Strength", "Ristretto Extraction", 0.00, ["Coffee"]],

    // Syrups & Flavours
    ["Syrups & Flavours", "Vanilla Syrup", 0.70, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks"]],
    ["Syrups & Flavours", "Caramel Syrup", 0.70, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks"]],
    ["Syrups & Flavours", "Hazelnut Syrup", 0.70, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks"]],
    ["Syrups & Flavours", "Salted Caramel Syrup", 0.80, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks"]],
    ["Syrups & Flavours", "Pure Raw Honey", 0.60, ["Coffee", "Hot Drinks", "Tea", "Smoothies"]],

    // Temperature & Sweetener
    ["Temperature & Sweetener", "Extra Hot (68°C)", 0.00, ["Coffee", "Hot Drinks"]],
    ["Temperature & Sweetener", "Warm / Kid's Temp (55°C)", 0.00, ["Coffee", "Hot Drinks"]],
    ["Temperature & Sweetener", "1x Raw Sugar", 0.00, ["Coffee", "Hot Drinks", "Tea", "Cold Coffee"]],
    ["Temperature & Sweetener", "2x Raw Sugar", 0.00, ["Coffee", "Hot Drinks", "Tea", "Cold Coffee"]],
    ["Temperature & Sweetener", "Equal / Stevia", 0.00, ["Coffee", "Hot Drinks", "Tea", "Cold Coffee"]],
    ["Temperature & Sweetener", "Dust with Dark Cocoa", 0.00, ["Coffee", "Hot Drinks"]],
    ["Temperature & Sweetener", "Dust with Cinnamon", 0.00, ["Coffee", "Hot Drinks"]],

    // Cold Add-ons & Ice
    ["Ice & Indulgence", "Standard Ice", 0.00, ["Cold Coffee", "Cold Drinks", "Juices"]],
    ["Ice & Indulgence", "Less Ice", 0.00, ["Cold Coffee", "Cold Drinks", "Juices"]],
    ["Ice & Indulgence", "Extra Ice", 0.00, ["Cold Coffee", "Cold Drinks", "Juices"]],
    ["Ice & Indulgence", "Scoop of Vanilla Ice Cream", 1.50, ["Cold Coffee", "Cold Drinks"]],
    ["Ice & Indulgence", "Fresh Whipped Cream", 1.00, ["Hot Drinks", "Cold Coffee", "Cold Drinks", "Pastries", "Bakery"]],
    ["Ice & Indulgence", "Extra Marshmallows (3 pcs)", 0.60, ["Hot Drinks", "Cold Drinks"]],

    // Smoothie & Juice Boosters
    ["Smoothie & Juice Boosters", "Organic Pea Protein (Vanilla)", 2.50, ["Smoothies"]],
    ["Smoothie & Juice Boosters", "Whey Protein Isolate (Chocolate)", 2.50, ["Smoothies"]],
    ["Smoothie & Juice Boosters", "Organic Chia Seeds", 1.00, ["Smoothies", "Juices"]],
    ["Smoothie & Juice Boosters", "Organic Spirulina Greens", 1.50, ["Smoothies"]],
    ["Smoothie & Juice Boosters", "Peanut Butter Scoop", 1.50, ["Smoothies"]],
    ["Smoothie & Juice Boosters", "Fresh Ginger Shot", 1.00, ["Juices", "Smoothies"]],

    // Breakfast & Egg Cooking Style
    ["Egg Preparation Style", "Poached Eggs (Soft Runny)", 0.00, ["Breakfast"]],
    ["Egg Preparation Style", "Scrambled Eggs (Silky Butter)", 0.00, ["Breakfast"]],
    ["Egg Preparation Style", "Fried Eggs (Sunny Side Up)", 0.00, ["Breakfast"]],
    ["Egg Preparation Style", "Fried Eggs (Over Hard)", 0.00, ["Breakfast"]],
    ["Egg Preparation Style", "Egg Whites Only", 2.00, ["Breakfast"]],

    // Bread & Toast Selection
    ["Bread & Toast Selection", "Artisan White Sourdough", 0.00, ["Breakfast", "Toasties", "Sandwiches"]],
    ["Bread & Toast Selection", "Seeded Multigrain Sourdough", 0.50, ["Breakfast", "Toasties", "Sandwiches"]],
    ["Bread & Toast Selection", "Gluten-Free Bread / Toast", 1.50, ["Breakfast", "Toasties", "Sandwiches"]],
    ["Bread & Toast Selection", "Toasted Brioche Bun", 0.00, ["Breakfast", "Sandwiches"]],
    ["Bread & Toast Selection", "No Bread / Carb-Free", 0.00, ["Breakfast"]],

    // Food Add-Ons & Savory Extras
    ["Food Add-Ons & Extras", "Crispy Smoked Bacon (2 Rashers)", 4.50, ["Breakfast", "Toasties", "Sandwiches", "Lunch"]],
    ["Food Add-Ons & Extras", "Grilled Halloumi Cheese (2 Slices)", 4.50, ["Breakfast", "Toasties", "Sandwiches", "Lunch"]],
    ["Food Add-Ons & Extras", "Smashed Hass Avocado", 4.00, ["Breakfast", "Toasties", "Sandwiches", "Lunch"]],
    ["Food Add-Ons & Extras", "Golden Potato Hash Brown", 3.50, ["Breakfast", "Toasties", "Sandwiches", "Sides"]],
    ["Food Add-Ons & Extras", "Grilled Thyme Mushrooms", 4.00, ["Breakfast", "Toasties", "Lunch"]],
    ["Food Add-Ons & Extras", "Smoked Tasmanian Salmon", 6.00, ["Breakfast", "Sandwiches", "Lunch"]],
    ["Food Add-Ons & Extras", "Grilled Herb Chicken Breast", 5.50, ["Sandwiches", "Lunch"]],
    ["Food Add-Ons & Extras", "Wilted Baby Spinach", 3.00, ["Breakfast", "Lunch"]],
    ["Food Add-Ons & Extras", "Roasted Heirloom Tomatoes", 3.50, ["Breakfast", "Toasties", "Lunch"]],
    ["Food Add-Ons & Extras", "Danish Creamy Feta", 3.00, ["Breakfast", "Toasties", "Lunch"]],
    ["Food Add-Ons & Extras", "Extra Free-Range Egg", 2.50, ["Breakfast", "Toasties", "Sandwiches", "Lunch"]],
    ["Food Add-Ons & Extras", "Extra Melted Vintage Cheddar", 2.00, ["Breakfast", "Toasties", "Sandwiches"]],
    ["Food Add-Ons & Extras", "Extra Swiss Gruyère Cheese", 2.50, ["Toasties", "Sandwiches"]],
    ["Food Add-Ons & Extras", "Pickled Jalapeños", 1.00, ["Toasties", "Sandwiches", "Lunch"]],

    // Sauces, Condiments & Spreads
    ["Sauces & Condiments", "House Citrus Hollandaise", 2.00, ["Breakfast"]],
    ["Sauces & Condiments", "Smoky Tomato Relish", 1.00, ["Breakfast", "Toasties", "Sandwiches", "Sides"]],
    ["Sauces & Condiments", "Chipotle Spicy Mayo", 1.00, ["Breakfast", "Toasties", "Sandwiches", "Sides"]],
    ["Sauces & Condiments", "Garlic Aioli", 1.00, ["Sandwiches", "Sides", "Lunch"]],
    ["Sauces & Condiments", "Truffle Mayo", 1.50, ["Toasties", "Sandwiches", "Sides"]],
    ["Sauces & Condiments", "Dijon Mustard", 0.00, ["Toasties", "Sandwiches"]],
    ["Sauces & Condiments", "Strawberry Jam", 0.50, ["Pastries", "Bakery", "Breakfast"]],
    ["Sauces & Condiments", "Nutella Spread", 1.00, ["Pastries", "Bakery"]],
    ["Sauces & Condiments", "Sauce on the Side", 0.00, ["Breakfast", "Toasties", "Sandwiches", "Sides", "Lunch"]],

    // Removals & Dietary Preferences
    ["Removals & Dietary", "No Butter / Dry Toast", 0.00, ["Breakfast", "Toasties", "Sandwiches", "Pastries", "Bakery"]],
    ["Removals & Dietary", "No Onion / Chives", 0.00, ["Breakfast", "Toasties", "Sandwiches", "Lunch"]],
    ["Removals & Dietary", "No Tomato", 0.00, ["Toasties", "Sandwiches", "Breakfast"]],
    ["Removals & Dietary", "No Dukkah (Nut Allergy)", 0.00, ["Breakfast", "Lunch"]],
    ["Removals & Dietary", "No Mayo / Dressing", 0.00, ["Sandwiches", "Lunch", "Sides"]],
    ["Removals & Dietary", "Extra Crispy Bacon", 0.00, ["Breakfast", "Toasties", "Sandwiches"]]
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
