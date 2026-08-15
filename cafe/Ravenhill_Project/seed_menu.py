import pymysql
import os

HOST = 'mehedihasan.au'
USER = 'mehedih3_cpro306_g1'
PASSWORD = 'cpro306'
DATABASE = 'mehedih3_cpro306_g1'

# 1. Categories
CATEGORIES = [
    "Coffee", "Hot Drinks", "Tea", "Cold Coffee", "Cold Drinks", 
    "Smoothies", "Juices", "Breakfast", "Toasties", "Sandwiches", 
    "Pastries", "Bakery", "Lunch", "Sides"
]

# 2. Products -> { "Category": [ ("Name", Price), ... ] }
PRODUCTS = {
    "Coffee": [
        ("Espresso / Short Black", 4.00), ("Long Black", 4.80), ("Flat White", 5.20),
        ("Latte", 5.20), ("Cappuccino", 5.20), ("Piccolo Latte", 4.80),
        ("Short Macchiato", 4.50), ("Long Macchiato", 5.20), ("Mocha", 5.80),
        ("Babycino", 2.50)
    ],
    "Hot Drinks": [
        ("Hot Chocolate", 5.50), ("White Hot Chocolate", 5.70), ("Chai Latte", 5.70),
        ("Dirty Chai", 6.20), ("Matcha Latte", 6.20), ("Turmeric Latte", 5.90)
    ],
    "Tea": [
        ("English Breakfast Tea", 4.80), ("Earl Grey Tea", 4.80), ("Green Tea", 4.80),
        ("Peppermint Tea", 4.80), ("Chamomile Tea", 4.80), ("Lemongrass & Ginger Tea", 4.80)
    ],
    "Cold Coffee": [
        ("Iced Latte", 6.80), ("Iced Long Black", 6.20), ("Iced Coffee", 7.80), ("Iced Mocha", 8.20)
    ],
    "Cold Drinks": [
        ("Iced Chocolate", 7.80), ("Iced Chai Latte", 7.20), ("Iced Matcha Latte", 7.80),
        ("Milkshake", 8.50), ("Bottled Still Water", 3.50), ("Sparkling Water", 5.00), ("Soft Drink", 4.50)
    ],
    "Smoothies": [
        ("Smoothie with options such as Banana, Mixed Berry, Mango and Tropical", 9.50)
    ],
    "Juices": [
        ("Fresh Orange Juice", 8.50), ("Fresh Apple Juice", 8.50), ("Green Juice", 9.50)
    ],
    "Breakfast": [
        ("Sourdough Toast", 6.50), ("Eggs on Toast", 15.00), ("Bacon & Egg Roll", 12.00),
        ("Breakfast Wrap", 13.50), ("Avocado Toast", 18.50), ("Granola & Yoghurt", 15.50),
        ("Porridge", 15.00), ("Eggs Benedict", 21.00), ("Breakfast Burger", 16.00)
    ],
    "Toasties": [
        ("Ham & Cheese Toastie", 12.50), ("Cheese & Tomato Toastie", 11.50),
        ("Three Cheese Toastie", 14.00), ("Tuna Melt", 15.50)
    ],
    "Sandwiches": [
        ("BLT Toasted Sandwich", 13.50), ("Chicken & Avocado Sandwich", 16.50)
    ],
    "Pastries": [
        ("Plain Croissant", 6.50), ("Almond Croissant", 8.00), ("Chocolate Croissant", 7.50),
        ("Ham & Cheese Croissant", 9.50), ("Fruit Danish", 7.00)
    ],
    "Bakery": [
        ("Blueberry Muffin", 6.50), ("Chocolate Muffin", 6.50), ("Banana Bread", 7.00),
        ("Blueberry Scone", 6.50)
    ],
    "Lunch": [
        ("Seasonal Salad", 18.00), ("Chicken Caesar Salad", 21.00)
    ],
    "Sides": [
        ("Chips", 8.50), ("Sweet Potato Chips", 10.50)
    ]
}

# 3. Modifiers (Group, Option, Price, Categories)
MODIFIERS = [
    # Milk Modifiers
    ("Milk", "Oat Milk", 0.80, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks", "Smoothies"]),
    ("Milk", "Soy Milk", 0.80, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks", "Smoothies"]),
    ("Milk", "Almond Milk", 0.80, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks", "Smoothies"]),
    ("Milk", "Lactose Free Milk", 0.80, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks", "Smoothies"]),
    
    # Coffee Modifiers
    ("Coffee Modifiers", "Extra Shot", 0.80, ["Coffee", "Cold Coffee"]),
    ("Coffee Modifiers", "Decaf", 0.80, ["Coffee", "Cold Coffee"]),
    
    # Size Modifiers (Standard is default $0)
    ("Size", "Large", 0.80, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks", "Smoothies", "Juices", "Tea"]),
    
    # Flavour Modifiers
    ("Flavours", "Vanilla Syrup", 0.70, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks"]),
    ("Flavours", "Caramel Syrup", 0.70, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks"]),
    ("Flavours", "Hazelnut Syrup", 0.70, ["Coffee", "Hot Drinks", "Cold Coffee", "Cold Drinks"]),
    
    # Other / Add-ons
    ("Add-ons", "Whipped Cream", 0.80, ["Hot Drinks", "Cold Coffee", "Cold Drinks"]),
    ("Add-ons", "Marshmallows", 0.70, ["Hot Drinks", "Cold Drinks"]),
    
    # Food Add-ons
    ("Food Add-ons", "Gluten Free Bread", 2.00, ["Breakfast", "Toasties", "Sandwiches"]),
    ("Food Add-ons", "Extra Cheese", 2.00, ["Breakfast", "Toasties", "Sandwiches", "Lunch"]),
    ("Food Add-ons", "Avocado", 3.00, ["Breakfast", "Toasties", "Sandwiches", "Lunch"]),
    ("Food Add-ons", "Bacon", 4.50, ["Breakfast", "Toasties", "Sandwiches", "Lunch"]),
    ("Food Add-ons", "Extra Egg", 2.50, ["Breakfast", "Toasties", "Lunch"]),
    ("Food Add-ons", "Hash Brown", 2.50, ["Breakfast", "Sides"]),
    ("Food Add-ons", "Smoked Salmon", 7.00, ["Breakfast", "Sandwiches", "Lunch"]),
    ("Food Add-ons", "Chicken", 5.00, ["Sandwiches", "Lunch"]),
    ("Food Add-ons", "Halloumi", 4.00, ["Breakfast", "Sandwiches", "Lunch"]),
]

def seed_database():
    print(f"Connecting to MySQL Database {DATABASE} on {HOST}...")
    try:
        connection = pymysql.connect(
            host=HOST,
            user=USER,
            password=PASSWORD,
            database=DATABASE,
            charset='utf8mb4',
            cursorclass=pymysql.cursors.DictCursor
        )
        print("Connected successfully!")
        
        with connection.cursor() as cursor:
            # Note: We won't delete existing data to be safe, but we will "upsert" or ignore.
            
            # 1. Seed Categories
            print("Seeding Categories...")
            category_id_map = {}
            for cat in CATEGORIES:
                cursor.execute("SELECT category_id FROM Categories WHERE category_name = %s", (cat,))
                row = cursor.fetchone()
                if row:
                    category_id_map[cat] = row['category_id']
                else:
                    cursor.execute("INSERT INTO Categories (category_name) VALUES (%s)", (cat,))
                    category_id_map[cat] = connection.insert_id()
                    
            # 2. Seed Products
            print("Seeding Products...")
            for cat_name, items in PRODUCTS.items():
                cat_id = category_id_map[cat_name]
                for p_name, price in items:
                    cursor.execute("SELECT product_id FROM Products WHERE product_name = %s AND category_id = %s", (p_name, cat_id))
                    if not cursor.fetchone():
                        cursor.execute("INSERT INTO Products (category_id, product_name, price, availability) VALUES (%s, %s, %s, 1)", (cat_id, p_name, price))
                        
            # 3. Seed Customisations (Modifiers)
            print("Seeding Customisations...")
            # Modifiers table has group_name, option_name, extra_price, category_id, product_id, is_default, availability
            for grp, opt, price, cat_names in MODIFIERS:
                for cat_name in cat_names:
                    if cat_name not in category_id_map:
                        continue
                    c_id = category_id_map[cat_name]
                    cursor.execute("""
                        SELECT customisation_id FROM Customisations 
                        WHERE group_name = %s AND option_name = %s AND category_id = %s
                    """, (grp, opt, c_id))
                    
                    if not cursor.fetchone():
                        cursor.execute("""
                            INSERT INTO Customisations (group_name, option_name, extra_price, category_id, availability)
                            VALUES (%s, %s, %s, %s, 1)
                        """, (grp, opt, price, c_id))

            connection.commit()
            print("Seeding completed successfully!")
            
    except pymysql.MySQLError as e:
        print(f"Failed to connect or execute on MySQL: {e}")
    finally:
        if 'connection' in locals() and connection.open:
            connection.close()

if __name__ == '__main__':
    seed_database()
