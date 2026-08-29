import os

def generate_complete_sql():
    sql = """-- ======================================================================
-- Ravenhill Coffee POS & Management System - Complete Database Schema & Seed
-- Character Set: utf8mb4 | Engine: InnoDB | Currency: AUD
-- ======================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ----------------------------------------------------------------------
-- 1. DROP EXISTING TABLES (Clean Slate)
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS LoyaltyTransactions;
DROP TABLE IF EXISTS Feedback;
DROP TABLE IF EXISTS Payments;
DROP TABLE IF EXISTS OrderItems;
DROP TABLE IF EXISTS Reservations;
DROP TABLE IF EXISTS Orders;
DROP TABLE IF EXISTS Timesheets;
DROP TABLE IF EXISTS Schedules;
DROP TABLE IF EXISTS AuditLogs;
DROP TABLE IF EXISTS SalesPredictions;
DROP TABLE IF EXISTS PurchaseOrderItems;
DROP TABLE IF EXISTS PurchaseOrders;
DROP TABLE IF EXISTS Recipes;
DROP TABLE IF EXISTS InventoryTransactions;
DROP TABLE IF EXISTS Inventory;
DROP TABLE IF EXISTS Customisations;
DROP TABLE IF EXISTS Products;
DROP TABLE IF EXISTS Categories;
DROP TABLE IF EXISTS Suppliers;
DROP TABLE IF EXISTS DiningTables;
DROP TABLE IF EXISTS Discounts;
DROP TABLE IF EXISTS Customers;
DROP TABLE IF EXISTS Employees;
DROP TABLE IF EXISTS Users;
DROP TABLE IF EXISTS Roles;
DROP TABLE IF EXISTS LoginAttempts;
DROP TABLE IF EXISTS PhoneVerifications;

-- ----------------------------------------------------------------------
-- 2. TABLE DEFINITIONS
-- ----------------------------------------------------------------------

-- 2.1 Roles
CREATE TABLE Roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.2 Users
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NULL,
    status VARCHAR(50) DEFAULT 'active',
    last_login DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES Roles(role_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.3 Employees
CREATE TABLE Employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    position VARCHAR(100) NULL,
    hire_date DATE NULL,
    hourly_rate FLOAT DEFAULT 28.50,
    pin VARCHAR(10) DEFAULT '1234',
    status VARCHAR(50) DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.4 Categories
CREATE TABLE Categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.5 Products
CREATE TABLE Products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NULL,
    product_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price FLOAT NOT NULL DEFAULT 0.0,
    availability BOOLEAN DEFAULT TRUE,
    image VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES Categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.6 Customisations (Modifiers)
CREATE TABLE Customisations (
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
    INDEX (product_id),
    FOREIGN KEY (category_id) REFERENCES Categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.7 Suppliers
CREATE TABLE Suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    address TEXT NULL,
    payment_terms VARCHAR(100) DEFAULT 'Net 30',
    notes TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.8 DiningTables
CREATE TABLE DiningTables (
    table_id INT AUTO_INCREMENT PRIMARY KEY,
    table_number INT NOT NULL UNIQUE,
    capacity INT NOT NULL DEFAULT 4,
    status VARCHAR(50) DEFAULT 'available',
    location VARCHAR(100) DEFAULT 'Main Dining Hall'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.9 Discounts
CREATE TABLE Discounts (
    discount_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    type VARCHAR(50) DEFAULT 'percentage',
    discount_percentage FLOAT DEFAULT 0.0,
    fixed_amount FLOAT DEFAULT 0.0,
    min_spend FLOAT DEFAULT 0.0,
    start_date DATE NULL,
    end_date DATE NULL,
    applicable_category_id INT NULL,
    usage_limit INT DEFAULT 1000,
    usage_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (applicable_category_id) REFERENCES Categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.10 Customers
CREATE TABLE Customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    user_id INT NULL,
    loyalty_points INT DEFAULT 0,
    loyalty_tier VARCHAR(50) DEFAULT 'Bronze',
    joined_loyalty_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.11 Inventory
CREATE TABLE Inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    product_id INT NULL,
    supplier_id INT NULL,
    quantity INT DEFAULT 0,
    reorder_level INT DEFAULT 10,
    unit VARCHAR(50) DEFAULT 'units',
    unit_cost FLOAT DEFAULT 0.0,
    status VARCHAR(50) DEFAULT 'in_stock',
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES Suppliers(supplier_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.12 InventoryTransactions
CREATE TABLE InventoryTransactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    transaction_type VARCHAR(50) NOT NULL,
    quantity_change INT NOT NULL,
    quantity_after INT NOT NULL,
    reason TEXT NULL,
    performed_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES Inventory(inventory_id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES Users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.13 Recipes
CREATE TABLE Recipes (
    recipe_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    inventory_id INT NOT NULL,
    quantity_required FLOAT NOT NULL,
    unit VARCHAR(50) DEFAULT 'g',
    prep_instructions TEXT NULL,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_id) REFERENCES Inventory(inventory_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.14 PurchaseOrders
CREATE TABLE PurchaseOrders (
    po_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    po_number VARCHAR(100) NOT NULL UNIQUE,
    total_cost FLOAT DEFAULT 0.0,
    status VARCHAR(50) DEFAULT 'draft',
    order_date DATE NULL,
    expected_delivery DATE NULL,
    created_by INT NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES Suppliers(supplier_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES Users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.15 PurchaseOrderItems
CREATE TABLE PurchaseOrderItems (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    inventory_id INT NOT NULL,
    quantity_ordered INT NOT NULL,
    quantity_received INT DEFAULT 0,
    unit_cost FLOAT NOT NULL,
    subtotal FLOAT NOT NULL,
    FOREIGN KEY (po_id) REFERENCES PurchaseOrders(po_id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_id) REFERENCES Inventory(inventory_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.16 Orders
CREATE TABLE Orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    employee_id INT NULL,
    payment_id INT NULL,
    total_amount FLOAT DEFAULT 0.0,
    order_status VARCHAR(50) DEFAULT 'pending',
    order_type VARCHAR(50) DEFAULT 'dine-in',
    table_number INT NULL,
    discount_code VARCHAR(100) NULL,
    discount_amount FLOAT DEFAULT 0.0,
    notes TEXT NULL,
    cancellation_reason TEXT NULL,
    completed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES Customers(customer_id) ON DELETE SET NULL,
    FOREIGN KEY (employee_id) REFERENCES Employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.17 OrderItems
CREATE TABLE OrderItems (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price FLOAT NOT NULL DEFAULT 0.0,
    subtotal FLOAT NOT NULL DEFAULT 0.0,
    customisations_json TEXT NULL,
    item_notes TEXT NULL,
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.18 Payments
CREATE TABLE Payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount FLOAT NOT NULL,
    payment_method VARCHAR(100) DEFAULT 'card',
    payment_status VARCHAR(50) DEFAULT 'completed',
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    tip_amount FLOAT DEFAULT 0.0,
    cash_tendered FLOAT NULL,
    change_due FLOAT NULL,
    split_index INT DEFAULT 1,
    transaction_reference VARCHAR(255) NULL,
    notes TEXT NULL,
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.19 Reservations
CREATE TABLE Reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    table_id INT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    number_of_guests INT NOT NULL DEFAULT 2,
    status VARCHAR(50) DEFAULT 'confirmed',
    special_requests TEXT NULL,
    guest_name VARCHAR(100) NULL,
    guest_phone VARCHAR(50) NULL,
    guest_email VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES Customers(customer_id) ON DELETE SET NULL,
    FOREIGN KEY (table_id) REFERENCES DiningTables(table_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.20 Feedback
CREATE TABLE Feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    order_id INT NULL,
    rating INT NOT NULL DEFAULT 5,
    comments TEXT NULL,
    category VARCHAR(100) DEFAULT 'General',
    guest_name VARCHAR(100) NULL,
    status VARCHAR(50) DEFAULT 'published',
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES Customers(customer_id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.21 LoyaltyTransactions
CREATE TABLE LoyaltyTransactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_id INT NULL,
    points_earned INT DEFAULT 0,
    points_redeemed INT DEFAULT 0,
    description VARCHAR(255) NULL,
    balance_after INT DEFAULT 0,
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES Customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.22 Schedules
CREATE TABLE Schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    shift_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    role_station VARCHAR(100) DEFAULT 'Barista',
    notes TEXT NULL,
    FOREIGN KEY (employee_id) REFERENCES Employees(employee_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.23 Timesheets
CREATE TABLE Timesheets (
    timesheet_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    clock_in DATETIME NOT NULL,
    clock_out DATETIME NULL,
    status VARCHAR(50) DEFAULT 'clocked_in',
    hourly_rate FLOAT DEFAULT 28.50,
    FOREIGN KEY (employee_id) REFERENCES Employees(employee_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.24 AuditLogs
CREATE TABLE AuditLogs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    user_name VARCHAR(100) NULL,
    action VARCHAR(255) NOT NULL,
    table_name VARCHAR(255) NULL,
    details TEXT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(100) NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.25 LoginAttempts
CREATE TABLE LoginAttempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(100) NOT NULL,
    username VARCHAR(255) NOT NULL,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (ip_address),
    INDEX (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.26 PhoneVerifications
CREATE TABLE PhoneVerifications (
    verification_id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(50) NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (phone_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.27 SalesPredictions
CREATE TABLE SalesPredictions (
    prediction_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    prediction_date DATE NOT NULL,
    predicted_sales FLOAT NOT NULL,
    confidence_score FLOAT NOT NULL,
    model_version VARCHAR(50) DEFAULT 'v1.0',
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------
-- 3. INITIAL SEED DATA
-- ----------------------------------------------------------------------

-- 3.1 Roles
INSERT INTO Roles (role_id, role_name, description) VALUES
(1, 'Admin', 'Full administrative access across all modules and settings'),
(2, 'Manager', 'Store operations, inventory management, staff scheduling, and reporting'),
(3, 'Cashier', 'Front-of-house point of sale, order taking, table booking, and customer management'),
(4, 'Barista', 'Beverage preparation and order status management via KDS'),
(5, 'Kitchen', 'Kitchen display system and food preparation management'),
(6, 'Customer', 'Customer ordering portal and loyalty management');

-- 3.2 Users (Passwords: admin123, manager123, cashier123, barista123)
-- Password hashes generated with PASSWORD_BCRYPT
INSERT INTO Users (user_id, username, password_hash, role_id, status) VALUES
(1, 'admin', '$2y$10$QO2sZ7kXq2N9fN9aP8pI2u6U/6gqU0wU5Vw5I0X2M7L9m5A4vO2uK', 1, 'active'),
(2, 'slin', '$2y$10$QO2sZ7kXq2N9fN9aP8pI2u6U/6gqU0wU5Vw5I0X2M7L9m5A4vO2uK', 3, 'active'),
(3, 'loconnor', '$2y$10$QO2sZ7kXq2N9fN9aP8pI2u6U/6gqU0wU5Vw5I0X2M7L9m5A4vO2uK', 4, 'active'),
(4, 'hwright', '$2y$10$QO2sZ7kXq2N9fN9aP8pI2u6U/6gqU0wU5Vw5I0X2M7L9m5A4vO2uK', 4, 'active');

-- 3.3 Employees (Note: O''Connor escaped properly with double single-quote)
INSERT INTO Employees (employee_id, user_id, first_name, last_name, phone, email, position, hire_date, hourly_rate, pin, status) VALUES
(1, 1, 'System', 'Administrator', '0400 000 000', 'admin@ravenhill.au', 'General Manager', '2023-01-15', 45.00, '0000', 'active'),
(2, 2, 'Sarah', 'Lin', '0412 345 678', 'sarah.lin@ravenhill.au', 'Lead Cashier', '2023-03-01', 29.50, '1234', 'active'),
(3, 3, 'Liam', 'O''Connor', '0423 456 789', 'liam.oc@ravenhill.au', 'Head Barista', '2023-02-10', 32.00, '5678', 'active'),
(4, 4, 'Hannah', 'Wright', '0434 567 890', 'hannah.w@ravenhill.au', 'Barista / Floor', '2023-06-20', 28.50, '9900', 'active');

-- 3.4 DiningTables (12 tables)
INSERT INTO DiningTables (table_id, table_number, capacity, status, location) VALUES
(1, 1, 2, 'available', 'Window Side'),
(2, 2, 2, 'available', 'Window Side'),
(3, 3, 4, 'occupied', 'Main Dining Hall'),
(4, 4, 4, 'available', 'Main Dining Hall'),
(5, 5, 6, 'reserved', 'Main Dining Hall'),
(6, 6, 2, 'available', 'Courtyard Patio'),
(7, 7, 4, 'available', 'Courtyard Patio'),
(8, 8, 4, 'reserved', 'Courtyard Patio'),
(9, 9, 2, 'available', 'Bar Counter'),
(10, 10, 6, 'reserved', 'Private Mezzanine'),
(11, 11, 8, 'available', 'Private Mezzanine'),
(12, 12, 4, 'available', 'Main Dining Hall');

-- 3.5 Suppliers
INSERT INTO Suppliers (supplier_id, supplier_name, contact_person, phone, email, address, payment_terms, notes) VALUES
(1, 'Melbourne Coffee Exporters', 'Sam Harris', '(03) 9882 1100', 'orders@melbcoffee.au', '14 Victoria Docks, Melbourne VIC 3000', 'Net 14', 'Specialty Grade 1 Single Origin and House Blend roasted coffee beans'),
(2, 'St David Dairy Victoria', 'Anna Schmidt', '(03) 9419 8820', 'supply@stdavid.com.au', '16 St David St, Fitzroy VIC 3065', 'Net 7', 'Fresh local unhomogenised whole milk, skim milk, and cultured butter'),
(3, 'MilkLab Plant Milks', 'Liam Vance', '1300 645 522', 'orders@milklab.com.au', '88 Southbank Blvd, Southbank VIC 3006', 'Net 30', 'Almond, Oat, Soy, and Lactose-Free specialty barista series milks'),
(4, 'BioPak Sustainable Solutions', 'Support Team', '1300 246 725', 'orders@biopak.com.au', '55 Clarence St, Sydney NSW 2000', 'Net 30', 'FSC-certified plant-based compostable cups, lids, carry trays and straws'),
(5, 'Noisette Bakery Artisans', 'Chef Pierre', '(03) 9555 4321', 'pierre@noisette.com.au', '84 Turner St, Port Melbourne VIC 3207', 'Net 7', 'Daily deliveries of artisan sourdough loaves, plain croissants, and pastries');

-- 3.6 Discounts & Promotions
INSERT INTO Discounts (discount_id, code, description, type, discount_percentage, fixed_amount, min_spend, start_date, end_date, usage_limit, usage_count, is_active) VALUES
(1, 'WELCOME10', '10% off first order for new customer loyalty signups', 'percentage', 10.0, 0.0, 10.0, '2026-01-01', '2026-12-31', 5000, 142, 1),
(2, 'RAVENHILL15', '15% discount promotional code for regulars', 'percentage', 15.0, 0.0, 20.0, '2026-01-01', '2026-12-31', 1000, 68, 1),
(3, 'FLINDERS5', '$5.00 off orders over $25.00', 'fixed', 0.0, 5.0, 25.0, '2026-01-01', '2026-12-31', 1000, 39, 1),
(4, 'STAFF50', '50% staff dining discount', 'percentage', 50.0, 0.0, 0.0, '2026-01-01', '2026-12-31', 9999, 512, 1);

-- 3.7 Customers (Loyalty Members)
INSERT INTO Customers (customer_id, first_name, last_name, phone, email, loyalty_points, loyalty_tier, joined_loyalty_at) VALUES
(1, 'David', 'Kim', '0412 889 201', 'david.kim@gmail.com', 240, 'Gold', '2025-10-12 08:30:00'),
(2, 'Alex', 'Mercer', '0400 998 123', 'alex.mercer@outlook.com', 110, 'Silver', '2025-11-04 14:15:00'),
(3, 'Chloe', 'Lin', '0488 442 109', 'chloe.lin@techcorp.au', 75, 'Bronze', '2026-01-09 09:40:00'),
(4, 'Marcus', 'Vance', '0411 223 344', 'marcus.vance@melbourne.edu.au', 315, 'Platinum', '2025-08-19 11:20:00'),
(5, 'Elena', 'Rostova', '0422 334 455', 'elena.r@designhub.com', 90, 'Silver', '2026-02-01 16:05:00');

-- 3.8 Categories (14 Core Menu Categories)
INSERT INTO Categories (category_id, category_name, description) VALUES
(1, 'Coffee', 'Artisan espresso-based coffees brewed with precision roasted beans'),
(2, 'Hot Drinks', 'Comforting spiced chais, rich chocolates, and soothing specialty lattes'),
(3, 'Tea', 'Premium loose-leaf and organic herbal whole-leaf infusions'),
(4, 'Cold Coffee', 'Chilled iced espresso favorites, single origin iced lattes, and nitro cold brews'),
(5, 'Cold Drinks', 'Refreshing iced teas, rich cold milkshakes, artisan sodas, and sparkling waters'),
(6, 'Smoothies', 'Thick blended natural fruit smoothies made with real yogurt and superfoods'),
(7, 'Juices', 'Freshly pressed fruit and vegetable cold juices packed with vibrant vitamins'),
(8, 'Breakfast', 'Hearty morning classics, farm fresh eggs, sourdough toasts, and breakfast wraps'),
(9, 'Toasties', 'Golden grilled sourdough toasties melted with premium artisan cheeses and fillings'),
(10, 'Sandwiches', 'Fresh daily deli sandwiches, crispy BLTs, and gourmet sourdough baguettes'),
(11, 'Pastries', 'Flaky handmade French butter croissants and sweet seasonal fruit danishes'),
(12, 'Bakery', 'Freshly baked muffins, gourmet banana breads, and traditional scones'),
(13, 'Lunch', 'Fresh seasonal lunchtime salads and gourmet protein bowls'),
(14, 'Sides', 'Crunchy golden potato chips, sweet potato fries, and appetizing snack sides');

-- 3.9 Products (42 Baseline Menu Items in AUD)
INSERT INTO Products (product_id, category_id, product_name, description, price, availability, image) VALUES
-- Category 1: Coffee
(1, 1, 'Espresso / Short Black', 'Intense, aromatic double-shot extraction of our seasonal Ravenhill Reserve espresso blend', 4.00, 1, 'double_espresso_short_black.png'),
(2, 1, 'Long Black', 'Double shot of extraction gently poured over hot filtered water preserving delicate crema', 4.80, 1, 'long_black_coffee.png'),
(3, 1, 'Flat White', 'Silky textured microfoam folded over a rich double shot of Ravenhill espresso blend', 5.20, 1, 'flat_white_coffee.png'),
(4, 1, 'Latte', 'Smooth espresso balanced with velvety steamed milk and a delicate layer of froth', 5.20, 1, 'flat_white_coffee.png'),
(5, 1, 'Cappuccino', 'Rich espresso topped with deep velvety foam and dusted with organic dark cocoa', 5.20, 1, 'cappuccino_coffee.png'),
(6, 1, 'Piccolo Latte', 'A concentrated ristretto shot topped with warm silky milk served in a 4oz glass', 4.80, 1, 'piccolo_latte.png'),
(7, 1, 'Short Macchiato', 'Pure espresso marked with a single dash of velvety steamed milk foam', 4.50, 1, 'double_espresso_short_black.png'),
(8, 1, 'Long Macchiato', 'Double shot espresso over hot water stained with steamed milk foam', 5.20, 1, 'long_black_coffee.png'),
(9, 1, 'Mocha', 'Decadent Belgian melted dark chocolate blended with espresso and silky steamed milk', 5.80, 1, 'cappuccino_coffee.png'),
(10, 1, 'Babycino', 'Warm frothed milk dusted with sweet cocoa powder served with two marshmallows', 2.50, 1, 'cappuccino_coffee.png'),

-- Category 2: Hot Drinks
(11, 2, 'Hot Chocolate', 'Rich Belgian 54% dark chocolate melted into creamy steamed milk with marshmallows', 5.50, 1, 'cappuccino_coffee.png'),
(12, 2, 'White Hot Chocolate', 'Sweet and velvety Swiss white chocolate melted into creamy steamed milk', 5.70, 1, 'cappuccino_coffee.png'),
(13, 2, 'Chai Latte', 'Authentic spiced black tea steeped with whole cinnamon, cardamom, and steamed milk', 5.70, 1, 'prana_sticky_chai_latte.png'),
(14, 2, 'Dirty Chai', 'Traditional spiced chai latte infused with a bold shot of Ravenhill espresso', 6.20, 1, 'prana_sticky_chai_latte.png'),
(15, 2, 'Matcha Latte', 'Ceremonial grade Japanese Uji green tea matcha whisked with silky steamed milk', 6.20, 1, 'flat_white_coffee.png'),
(16, 2, 'Turmeric Latte', 'Golden spiced blend of organic ground turmeric, ginger, black pepper, and warm milk', 5.90, 1, 'flat_white_coffee.png'),

-- Category 3: Tea
(17, 3, 'English Breakfast Tea', 'Full-bodied organic Ceylon and Assam black tea with a brisk, robust malt character', 4.80, 1, 'batch_brew_filter.png'),
(18, 3, 'Earl Grey Tea', 'Fragrant black tea leaves infused with cold-pressed natural Italian bergamot oil', 4.80, 1, 'batch_brew_filter.png'),
(19, 3, 'Green Tea', 'Delicate Japanese Sencha green tea with vibrant grassy notes and a clean finish', 4.80, 1, 'batch_brew_filter.png'),
(20, 3, 'Peppermint Tea', 'Refreshing whole dried organic peppermint leaves offering a naturally caffeine-free lift', 4.80, 1, 'batch_brew_filter.png'),
(21, 3, 'Chamomile Tea', 'Calming whole organic chamomile flower blossoms with subtle floral apple sweetness', 4.80, 1, 'batch_brew_filter.png'),
(22, 3, 'Lemongrass & Ginger Tea', 'Zesty blend of uplifting cut lemongrass stalks and spicy warming organic ginger root', 4.80, 1, 'batch_brew_filter.png'),

-- Category 4: Cold Coffee
(23, 4, 'Iced Latte', 'Double shot of freshly extracted espresso poured over cold milk and crystalline ice', 6.80, 1, 'iced_oat_milk_latte.png'),
(24, 4, 'Iced Long Black', 'Double shot of rich espresso poured over chilled mineral water and ice', 6.20, 1, 'cold_brew_coffee.png'),
(25, 4, 'Iced Coffee', 'Chilled espresso and cold milk served with artisan vanilla bean ice cream and whipped cream', 7.80, 1, 'iced_oat_milk_latte.png'),
(26, 4, 'Iced Mocha', 'Belgian melted chocolate, espresso shot, and chilled milk with chocolate drizzle and cream', 8.20, 1, 'iced_oat_milk_latte.png'),

-- Category 5: Cold Drinks
(27, 5, 'Iced Chocolate', 'Decadent cold Belgian chocolate milk topped with vanilla ice cream and whipped cream', 7.80, 1, 'iced_oat_milk_latte.png'),
(28, 5, 'Iced Chai Latte', 'Chilled aromatic spiced chai infused with cold milk and served over ice', 7.20, 1, 'prana_sticky_chai_latte.png'),
(29, 5, 'Iced Matcha Latte', 'Vibrant ceremonial Japanese matcha whisked with ice-cold milk over ice', 7.80, 1, 'iced_oat_milk_latte.png'),
(30, 5, 'Milkshake', 'Classic thick milkshake churned with gourmet syrup (Chocolate, Vanilla, Strawberry, Caramel)', 8.50, 1, 'iced_oat_milk_latte.png'),
(31, 5, 'Bottled Still Water', 'Pure premium Australian spring water in an eco-friendly recyclable bottle 600ml', 3.50, 1, 'cold_brew_coffee.png'),
(32, 5, 'Sparkling Water', 'Crisp mineral sparkling water served with a fresh wedge of lemon 500ml', 5.00, 1, 'cold_brew_coffee.png'),
(33, 5, 'Soft Drink', 'Assorted classic canned soft drinks (Coca-Cola, Coke Zero, Sprite, Fanta, Ginger Beer)', 4.50, 1, 'cold_brew_coffee.png'),

-- Category 6: Smoothies
(34, 6, 'Smoothie with options such as Banana, Mixed Berry, Mango and Tropical', 'Rich creamy blended fruit smoothie made with Greek yogurt, honey, and chia seeds', 9.50, 1, 'iced_oat_milk_latte.png'),

-- Category 7: Juices
(35, 7, 'Fresh Orange Juice', '100% freshly cold-pressed sweet Valencia oranges with no added sugar or preservatives', 8.50, 1, 'cold_brew_coffee.png'),
(36, 7, 'Fresh Apple Juice', 'Crisp cold-pressed Australian Granny Smith and Pink Lady apples', 8.50, 1, 'cold_brew_coffee.png'),
(37, 7, 'Green Juice', 'Revitalising cold-pressed green juice with celery, cucumber, kale, green apple, and mint', 9.50, 1, 'cold_brew_coffee.png'),

-- Category 8: Breakfast
(38, 8, 'Sourdough Toast', 'Two thick toasted slices of artisan Noisette sourdough with butter and gourmet spreads', 6.50, 1, 'butter_croissant.png'),
(39, 8, 'Eggs on Toast', 'Two free-range Victorian eggs cooked your way (Poached, Scrambled, Fried) on sourdough', 15.00, 1, 'butter_croissant.png'),
(40, 8, 'Bacon & Egg Roll', 'Smoked streaky bacon, sunny-side fried free-range egg, and tomato relish on a brioche bun', 12.00, 1, 'butter_croissant.png'),
(41, 8, 'Breakfast Wrap', 'Scrambled eggs, crispy bacon, baby spinach, avocado, and spicy chipotle mayo in a tortilla', 13.50, 1, 'butter_croissant.png'),
(42, 8, 'Avocado Toast', 'Fresh smashed Hass avocado on sourdough with Persian feta, dukkah, radish, and lemon', 18.50, 1, 'butter_croissant.png'),
(43, 8, 'Granola & Yoghurt', 'House-baked honey toasted almond granola with seasonal berries and Greek vanilla yogurt', 15.50, 1, 'butter_croissant.png'),
(44, 8, 'Porridge', 'Creamy rolled organic oats simmered with almond milk, caramelized banana, and maple syrup', 15.00, 1, 'butter_croissant.png'),
(45, 8, 'Eggs Benedict', 'Two poached eggs, smoked shaved ham or bacon, and rich citrus hollandaise on sourdough', 21.00, 1, 'butter_croissant.png'),
(46, 8, 'Breakfast Burger', 'Angus beef patty, crispy bacon, fried egg, hash brown, cheddar cheese, and BBQ relish', 16.00, 1, 'butter_croissant.png'),

-- Category 9: Toasties
(47, 9, 'Ham & Cheese Toastie', 'Free-range smoked leg ham and melted Gruyère & aged cheddar on toasted sourdough', 12.50, 1, 'butter_croissant.png'),
(48, 9, 'Cheese & Tomato Toastie', 'Heirloom ripe tomatoes, aged vintage cheddar cheese, and fresh basil on sourdough', 11.50, 1, 'butter_croissant.png'),
(49, 9, 'Three Cheese Toastie', 'Mouthwatering blend of mozzarella, aged cheddar, and Swiss Gruyère cheese on sourdough', 14.00, 1, 'butter_croissant.png'),
(50, 9, 'Tuna Melt', 'Gourmet albacore tuna salad with dill, capers, melted provolone cheese, and jalapeño', 15.50, 1, 'butter_croissant.png'),

-- Category 10: Sandwiches
(51, 10, 'BLT Toasted Sandwich', 'Crispy smoked bacon, crunchy cos lettuce, vine ripened tomato, and aioli on sourdough', 13.50, 1, 'butter_croissant.png'),
(52, 10, 'Chicken & Avocado Sandwich', 'Poached free-range chicken breast, fresh avocado, rocket greens, and herb mayo on baguette', 16.50, 1, 'butter_croissant.png'),

-- Category 11: Pastries
(53, 11, 'Plain Croissant', 'Traditional flaky artisan French butter croissant baked fresh daily by Noisette', 6.50, 1, 'butter_croissant.png'),
(54, 11, 'Almond Croissant', 'Double-baked croissant filled with rich almond frangipane and topped with toasted flakes', 8.00, 1, 'butter_croissant.png'),
(55, 11, 'Chocolate Croissant', 'Flaky French pastry rolled around two batons of 54% dark Belgian chocolate', 7.50, 1, 'butter_croissant.png'),
(56, 11, 'Ham & Cheese Croissant', 'Warm butter croissant toasted with free-range leg ham, Swiss cheese, and bechamel', 9.50, 1, 'butter_croissant.png'),
(57, 11, 'Fruit Danish', 'Crispy puff pastry rosette filled with vanilla custard and glazed seasonal fruits', 7.00, 1, 'butter_croissant.png'),

-- Category 12: Bakery
(58, 12, 'Blueberry Muffin', 'Moist vanilla batter folded with wild Australian blueberries and a crumble top', 6.50, 1, 'butter_croissant.png'),
(59, 12, 'Chocolate Muffin', 'Double chocolate chunk muffin with melted dark and milk chocolate chips', 6.50, 1, 'butter_croissant.png'),
(60, 12, 'Banana Bread', 'Toasted spiced banana loaf served warm with whipped honey cinnamon butter', 7.00, 1, 'butter_croissant.png'),
(61, 12, 'Blueberry Scone', 'Freshly baked traditional scone served warm with strawberry conserve and double cream', 6.50, 1, 'butter_croissant.png'),

-- Category 13: Lunch
(62, 13, 'Seasonal Salad', 'Baby spinach, quinoa, roast pumpkin, pomegranate, walnuts, and balsamic citrus dressing', 18.00, 1, 'butter_croissant.png'),
(63, 13, 'Chicken Caesar Salad', 'Grilled chicken tenderloins, crispy bacon, cos lettuce, sourdough croutons, parmesan & egg', 21.00, 1, 'butter_croissant.png'),

-- Category 14: Sides
(64, 14, 'Chips', 'Bowl of crispy golden shoestring potato fries served with garlic aioli', 8.50, 1, 'butter_croissant.png'),
(65, 14, 'Sweet Potato Chips', 'Crunchy rosemary salted sweet potato fries served with chipotle dipping mayo', 10.50, 1, 'butter_croissant.png');

-- 3.10 Customisations (Modifiers & Add-ons mapped to Categories)
INSERT INTO Customisations (customisation_id, group_name, option_name, extra_price, category_id, is_default, availability) VALUES
-- Milk Options (Categories 1: Coffee, 2: Hot Drinks, 4: Cold Coffee, 5: Cold Drinks, 6: Smoothies)
(1, 'Milk', 'Full Cream Milk', 0.00, 1, 1, 1),
(2, 'Milk', 'Skim Milk', 0.00, 1, 0, 1),
(3, 'Milk', 'Oat Milk', 0.80, 1, 0, 1),
(4, 'Milk', 'Soy Milk', 0.80, 1, 0, 1),
(5, 'Milk', 'Almond Milk', 0.80, 1, 0, 1),
(6, 'Milk', 'Lactose Free Milk', 0.80, 1, 0, 1),

(7, 'Milk', 'Full Cream Milk', 0.00, 2, 1, 1),
(8, 'Milk', 'Skim Milk', 0.00, 2, 0, 1),
(9, 'Milk', 'Oat Milk', 0.80, 2, 0, 1),
(10, 'Milk', 'Soy Milk', 0.80, 2, 0, 1),
(11, 'Milk', 'Almond Milk', 0.80, 2, 0, 1),
(12, 'Milk', 'Lactose Free Milk', 0.80, 2, 0, 1),

(13, 'Milk', 'Full Cream Milk', 0.00, 4, 1, 1),
(14, 'Milk', 'Skim Milk', 0.00, 4, 0, 1),
(15, 'Milk', 'Oat Milk', 0.80, 4, 0, 1),
(16, 'Milk', 'Soy Milk', 0.80, 4, 0, 1),
(17, 'Milk', 'Almond Milk', 0.80, 4, 0, 1),
(18, 'Milk', 'Lactose Free Milk', 0.80, 4, 0, 1),

(19, 'Milk', 'Full Cream Milk', 0.00, 5, 1, 1),
(20, 'Milk', 'Skim Milk', 0.00, 5, 0, 1),
(21, 'Milk', 'Oat Milk', 0.80, 5, 0, 1),
(22, 'Milk', 'Soy Milk', 0.80, 5, 0, 1),
(23, 'Milk', 'Almond Milk', 0.80, 5, 0, 1),
(24, 'Milk', 'Lactose Free Milk', 0.80, 5, 0, 1),

(25, 'Milk', 'Oat Milk', 0.80, 6, 0, 1),
(26, 'Milk', 'Soy Milk', 0.80, 6, 0, 1),
(27, 'Milk', 'Almond Milk', 0.80, 6, 0, 1),
(28, 'Milk', 'Lactose Free Milk', 0.80, 6, 0, 1),

-- Coffee Shots & Roast Modifiers (Categories 1: Coffee, 4: Cold Coffee)
(29, 'Coffee Modifiers', 'Extra Shot', 0.80, 1, 0, 1),
(30, 'Coffee Modifiers', 'Decaf', 0.80, 1, 0, 1),
(31, 'Coffee Modifiers', 'Extra Shot', 0.80, 4, 0, 1),
(32, 'Coffee Modifiers', 'Decaf', 0.80, 4, 0, 1),

-- Cup Size Options (Categories 1: Coffee, 2: Hot Drinks, 3: Tea, 4: Cold Coffee, 5: Cold Drinks, 6: Smoothies, 7: Juices)
(33, 'Size', 'Standard / Regular', 0.00, 1, 1, 1),
(34, 'Size', 'Large', 0.80, 1, 0, 1),
(35, 'Size', 'Standard / Regular', 0.00, 2, 1, 1),
(36, 'Size', 'Large', 0.80, 2, 0, 1),
(37, 'Size', 'Standard / Regular', 0.00, 3, 1, 1),
(38, 'Size', 'Large', 0.80, 3, 0, 1),
(39, 'Size', 'Standard / Regular', 0.00, 4, 1, 1),
(40, 'Size', 'Large', 0.80, 4, 0, 1),
(41, 'Size', 'Standard / Regular', 0.00, 5, 1, 1),
(42, 'Size', 'Large', 0.80, 5, 0, 1),
(43, 'Size', 'Standard / Regular', 0.00, 6, 1, 1),
(44, 'Size', 'Large', 0.80, 6, 0, 1),
(45, 'Size', 'Standard / Regular', 0.00, 7, 1, 1),
(46, 'Size', 'Large', 0.80, 7, 0, 1),

-- Flavours & Syrups (Categories 1: Coffee, 2: Hot Drinks, 4: Cold Coffee, 5: Cold Drinks)
(47, 'Flavours', 'Vanilla Syrup', 0.70, 1, 0, 1),
(48, 'Flavours', 'Caramel Syrup', 0.70, 1, 0, 1),
(49, 'Flavours', 'Hazelnut Syrup', 0.70, 1, 0, 1),

(50, 'Flavours', 'Vanilla Syrup', 0.70, 2, 0, 1),
(51, 'Flavours', 'Caramel Syrup', 0.70, 2, 0, 1),
(52, 'Flavours', 'Hazelnut Syrup', 0.70, 2, 0, 1),

(53, 'Flavours', 'Vanilla Syrup', 0.70, 4, 0, 1),
(54, 'Flavours', 'Caramel Syrup', 0.70, 4, 0, 1),
(55, 'Flavours', 'Hazelnut Syrup', 0.70, 4, 0, 1),

(56, 'Flavours', 'Vanilla Syrup', 0.70, 5, 0, 1),
(57, 'Flavours', 'Caramel Syrup', 0.70, 5, 0, 1),
(58, 'Flavours', 'Hazelnut Syrup', 0.70, 5, 0, 1),

-- Drink Add-ons
(59, 'Add-ons', 'Whipped Cream', 0.80, 2, 0, 1),
(60, 'Add-ons', 'Marshmallows', 0.70, 2, 0, 1),
(61, 'Add-ons', 'Whipped Cream', 0.80, 4, 0, 1),
(62, 'Add-ons', 'Whipped Cream', 0.80, 5, 0, 1),
(63, 'Add-ons', 'Marshmallows', 0.70, 5, 0, 1),

-- Food Add-ons & Gluten Free Bread (Categories 8: Breakfast, 9: Toasties, 10: Sandwiches, 13: Lunch, 14: Sides)
(64, 'Food Add-ons', 'Gluten Free Bread', 2.00, 8, 0, 1),
(65, 'Food Add-ons', 'Extra Cheese', 2.00, 8, 0, 1),
(66, 'Food Add-ons', 'Avocado', 3.00, 8, 0, 1),
(67, 'Food Add-ons', 'Bacon', 4.50, 8, 0, 1),
(68, 'Food Add-ons', 'Extra Egg', 2.50, 8, 0, 1),
(69, 'Food Add-ons', 'Hash Brown', 2.50, 8, 0, 1),
(70, 'Food Add-ons', 'Smoked Salmon', 7.00, 8, 0, 1),
(71, 'Food Add-ons', 'Halloumi', 4.00, 8, 0, 1),

(72, 'Food Add-ons', 'Gluten Free Bread', 2.00, 9, 0, 1),
(73, 'Food Add-ons', 'Extra Cheese', 2.00, 9, 0, 1),
(74, 'Food Add-ons', 'Avocado', 3.00, 9, 0, 1),
(75, 'Food Add-ons', 'Bacon', 4.50, 9, 0, 1),
(76, 'Food Add-ons', 'Extra Egg', 2.50, 9, 0, 1),

(77, 'Food Add-ons', 'Gluten Free Bread', 2.00, 10, 0, 1),
(78, 'Food Add-ons', 'Extra Cheese', 2.00, 10, 0, 1),
(79, 'Food Add-ons', 'Avocado', 3.00, 10, 0, 1),
(80, 'Food Add-ons', 'Bacon', 4.50, 10, 0, 1),
(81, 'Food Add-ons', 'Chicken', 5.00, 10, 0, 1),
(82, 'Food Add-ons', 'Halloumi', 4.00, 10, 0, 1),
(83, 'Food Add-ons', 'Smoked Salmon', 7.00, 10, 0, 1),

(84, 'Food Add-ons', 'Extra Cheese', 2.00, 13, 0, 1),
(85, 'Food Add-ons', 'Avocado', 3.00, 13, 0, 1),
(86, 'Food Add-ons', 'Bacon', 4.50, 13, 0, 1),
(87, 'Food Add-ons', 'Extra Egg', 2.50, 13, 0, 1),
(88, 'Food Add-ons', 'Chicken', 5.00, 13, 0, 1),
(89, 'Food Add-ons', 'Halloumi', 4.00, 13, 0, 1),
(90, 'Food Add-ons', 'Smoked Salmon', 7.00, 13, 0, 1),

(91, 'Food Add-ons', 'Hash Brown', 2.50, 14, 0, 1);

-- 3.11 Inventory Base Items
INSERT INTO Inventory (inventory_id, item_name, product_id, supplier_id, quantity, reorder_level, unit, unit_cost, status) VALUES
(1, 'Ravenhill Reserve Espresso Beans (kg)', 1, 1, 45, 15, 'kg', 24.00, 'in_stock'),
(2, 'Single Origin Ethiopia Yirgacheffe (kg)', 2, 1, 18, 8, 'kg', 32.00, 'in_stock'),
(3, 'Decaf Swiss Water Process Beans (kg)', 1, 1, 12, 5, 'kg', 28.00, 'in_stock'),
(4, 'St David Dairy Full Cream Milk 2L', 3, 2, 60, 20, 'bottles', 3.20, 'in_stock'),
(5, 'St David Dairy Skim Milk 2L', 3, 2, 30, 10, 'bottles', 3.20, 'in_stock'),
(6, 'MilkLab Oat Milk 1L', 3, 3, 48, 16, 'cartons', 2.80, 'in_stock'),
(7, 'MilkLab Almond Milk 1L', 3, 3, 36, 12, 'cartons', 2.90, 'in_stock'),
(8, 'MilkLab Soy Milk 1L', 3, 3, 30, 10, 'cartons', 2.70, 'in_stock'),
(9, 'MilkLab Lactose Free Milk 1L', 3, 3, 24, 8, 'cartons', 2.90, 'in_stock'),
(10, 'Belgian Dark Chocolate Drops 54% (kg)', 9, 1, 15, 5, 'kg', 16.50, 'in_stock'),
(11, 'Prana Sticky Chai Blend (kg)', 13, 1, 10, 4, 'kg', 26.00, 'in_stock'),
(12, 'Ceremonial Grade Matcha Powder 500g', 15, 1, 6, 2, 'tins', 45.00, 'in_stock'),
(13, 'Organic English Breakfast Loose Leaf (kg)', 17, 1, 8, 3, 'kg', 22.00, 'in_stock'),
(14, 'BioPak 8oz Single-Wall Coffee Cups (500pk)', NULL, 4, 14, 5, 'boxes', 65.00, 'in_stock'),
(15, 'BioPak 12oz Double-Wall Coffee Cups (500pk)', NULL, 4, 20, 8, 'boxes', 78.00, 'in_stock'),
(16, 'Noisette Sourdough Loaves (Fresh)', 38, 5, 25, 8, 'loaves', 4.50, 'in_stock'),
(17, 'Free Range Eggs (15 dozen carton)', 39, 2, 8, 3, 'cartons', 48.00, 'in_stock'),
(18, 'Smoked Streaky Bacon (kg)', 40, 2, 18, 6, 'kg', 14.50, 'in_stock'),
(19, 'Hass Avocados (Tray 24ct)', 42, 2, 6, 2, 'trays', 28.00, 'in_stock'),
(20, 'Noisette Plain Butter Croissants (24pk)', 53, 5, 10, 4, 'boxes', 52.00, 'in_stock');

-- 3.12 Sample Active Orders
INSERT INTO Orders (order_id, customer_id, employee_id, payment_id, total_amount, order_status, order_type, table_number, discount_code, discount_amount, notes, created_at) VALUES
(1, 1, 2, NULL, 18.20, 'completed', 'dine-in', 3, NULL, 0.00, 'Table 3 dine-in guest', DATE_SUB(NOW(), INTERVAL 45 MINUTE)),
(2, 4, 2, NULL, 12.00, 'preparing', 'dine-in', 5, 'WELCOME10', 1.20, 'Extra hot flat white', DATE_SUB(NOW(), INTERVAL 12 MINUTE)),
(3, NULL, 3, NULL, 23.50, 'pending', 'takeaway', NULL, NULL, 0.00, 'Takeaway for Alex', DATE_SUB(NOW(), INTERVAL 4 MINUTE));

-- 3.13 Sample Order Items
INSERT INTO OrderItems (order_item_id, order_id, product_id, quantity, unit_price, subtotal, customisations_json, item_notes) VALUES
(1, 1, 3, 2, 6.00, 12.00, '[{"group_name":"Milk","option_name":"Oat Milk","extra_price":0.80}]', 'One regular, one oat'),
(2, 1, 53, 1, 6.50, 6.50, NULL, 'Warmed lightly'),
(3, 2, 3, 2, 6.00, 12.00, '[{"group_name":"Milk","option_name":"Oat Milk","extra_price":0.80}]', 'Extra hot 68C'),
(4, 3, 40, 1, 12.00, 12.00, NULL, 'Tomato sauce on side'),
(5, 3, 23, 1, 7.60, 7.60, '[{"group_name":"Milk","option_name":"Oat Milk","extra_price":0.80}]', 'Iced oat latte');

-- 3.14 Sample Feedback
INSERT INTO Feedback (feedback_id, customer_id, order_id, rating, comments, category, guest_name, status, submitted_at) VALUES
(1, 4, 1, 5, 'Best Flat White in Flinders Lane! Microfoam is super silky and beans taste sensational.', 'Coffee Quality', 'Marcus Vance', 'published', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 5, 2, 5, 'Great morning atmosphere, love the oat milk latte and friendly cashier staff.', 'Customer Service', 'Elena R.', 'published', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, NULL, 3, 4, 'Quick turnaround during morning rush hour peak. Will definitely return.', 'Speed of Service', 'Walk-in Guest', 'published', DATE_SUB(NOW(), INTERVAL 3 DAY));

-- 3.15 Sample Reservations
INSERT INTO Reservations (reservation_id, customer_id, table_id, reservation_date, reservation_time, number_of_guests, status, special_requests, guest_name, guest_phone, guest_email) VALUES
(1, 1, 5, CURDATE(), '11:30:00', 6, 'confirmed', 'Window seating preferred for birthday brunch', 'David Kim', '0412 889 201', 'david.kim@gmail.com'),
(2, 2, 8, CURDATE(), '12:30:00', 4, 'confirmed', 'High chair needed for toddler', 'Alex Mercer', '0400 998 123', 'alex.mercer@outlook.com'),
(3, 3, 10, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:15:00', 4, 'pending', 'Business lunch meeting', 'Chloe Lin', '0488 442 109', 'chloe.lin@techcorp.au');

SET FOREIGN_KEY_CHECKS = 1;

-- ======================================================================
-- End of Database Seed Script
-- ======================================================================
"""
    # Replace any unescaped single quotes inside string values:
    # Specifically O'Connor -> O''Connor
    sql = sql.replace("'O\\'Connor'", "'O''Connor'")
    
    with open('schema.sql', 'w', encoding='utf-8') as f:
        f.write(sql)
    print("Regenerated schema.sql successfully!")

    with open('ravenhill_database.sql', 'w', encoding='utf-8') as f:
        f.write(sql)
    print("Regenerated ravenhill_database.sql successfully!")

if __name__ == '__main__':
    generate_complete_sql()
