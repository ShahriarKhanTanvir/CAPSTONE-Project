-- 1. Insert Customer Role (ID 6) if it doesn't exist
INSERT IGNORE INTO Roles (role_id, role_name, description) 
VALUES (6, 'Customer', 'Registered customer with loyalty points and order history');

-- 2. Add user_id to Customers table
ALTER TABLE Customers ADD COLUMN user_id INT NULL AFTER customer_id;

-- 3. Add foreign key constraint
ALTER TABLE Customers ADD CONSTRAINT fk_customers_user 
FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE SET NULL;
