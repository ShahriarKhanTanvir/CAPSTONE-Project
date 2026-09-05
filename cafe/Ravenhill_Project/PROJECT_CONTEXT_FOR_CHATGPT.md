# Ravenhill Coffee POS & Management System — Technical Project Context

> **Document Purpose**: This comprehensive briefing document contains the entire architectural context, codebase structure, recent bug fixes, customisation engine specifications, promotions system, database schemas, and API documentation for **Ravenhill Coffee Roasters**. It can be shared directly with ChatGPT or any AI assistant to immediately understand the project state.

---

## 1. Project Overview & Environment

- **System**: **Ravenhill Coffee Roasters — POS & Cafe Management System**
- **Live Production URL**: [https://mehedihasan.au/kent/cpro306/g1/](https://mehedihasan.au/kent/cpro306/g1/)
- **Target Deployment**:
  - **Host**: `mehedihasan.au` (`116.255.43.78`)
  - **Port**: `2222` (SFTP)
  - **Username**: `mehedih3_cpro306_g1`
  - **Deployment Tool**: `python3 -u deploy.py --auto` from `Ravenhill_Project`
- **Git Repositories (Main Branch)**:
  - `origin`: [https://github.com/ShahriarKhanTanvir/CAPSTONE-Project.git](https://github.com/ShahriarKhanTanvir/CAPSTONE-Project.git)
  - `cafe-public`: [https://github.com/ShahriarKhanTanvir/cafe.git](https://github.com/ShahriarKhanTanvir/cafe.git)
- **Technology Stack**:
  - **Frontend**: Vanilla JavaScript (ES6+), HTML5 Semantic Structure, Custom CSS3 Design System (Dark-luxe coffee aesthetic, Remix Icons).
  - **Backend**: Native PHP 8.x REST APIs (`/api/...`).
  - **Database**: MySQL / MariaDB (`utf8mb4`).
  - **Auth**: Role-based access control (Admin, Manager, Barista, Kitchen, Cashier, Customer) with session & token validation.

---

## 2. Core Codebase Directory Structure

```
Ravenhill_Project/
├── index.php                      # Main application UI, POS terminal, navigation & modal containers
├── app.js                         # Core client application logic, state, cart, customiser, POS, events
├── styles.css                     # Complete design system tokens, responsive styles & animations
├── deploy.py                      # Automated SFTP deployment script
├── phpmyadmin_import.sql          # Clean phpMyAdmin database import script
├── ravenhill_database.sql         # Full SQL database backup
├── schema.sql                     # Production DDL and seed data
└── api/                           # Backend RESTful API endpoints
    ├── config/
    │   ├── db.php                 # Resilient PDO connection handler (production 127.0.0.1 / local XAMPP)
    │   └── paypal.php             # Payment sandbox configurations
    ├── utils/
    │   ├── response.php           # Standardized JSON response emitter (sendResponse)
    │   ├── auth_check.php         # Token/Session RBAC authorization guard
    │   └── csrf.php               # CSRF token generator & validator
    ├── customisations/
    │   └── customisations.php     # FR13-FR16: Customisation retrieval, CRUD, and live pricing calculation
    ├── discounts/
    │   └── discounts.php          # Complete Discount & Promotion CRUD, auto-apply & voucher verification
    ├── users/
    │   ├── login.php              # Multi-role authentication & credential verification
    │   ├── me.php                 # Current authenticated user session inspector
    │   ├── logout.php             # Session destroyer
    │   └── register.php           # User onboarding
    ├── menu/
    │   ├── items.php              # Menu catalog retrieval & item availability
    │   └── categories.php         # Menu category endpoints
    ├── orders/
    │   ├── orders.php             # POS order creation, checkout & state handling
    │   ├── customer_order.php     # Customer self-ordering API
    │   └── kds.php                # Kitchen Display System real-time ticket router
    ├── inventory/
    │   ├── inventory.php          # Stock levels, reorder thresholds & low-stock alerts
    │   └── transactions.php       # Stock deduction and audit logs
    ├── ai/
    │   └── forecast.php           # AI RAG Demand & Inventory 2-Year historical predictive model
    └── reports/
        ├── dashboard.php          # Managerial KPI aggregation (gross revenue, GST, AOV)
        ├── audit.php              # Comprehensive staff actions, sales & security audit log
        └── export.php             # CSV/PDF data reporting
```

---

## 3. Product Customisation & Modifier System Specification

### Architectural Rules & Implementation (`app.js`, `index.php`, `styles.css`)

1. **Single-Select Radio Button Mutual Exclusivity**:
   - Groups classified as single-choice (`Milk`, `Milk Choice`, `Milk & Dairy Choice`, `Cup Size`, `Size`, `Cold Size`, `Pot Size`, `Bread Choice`, `Egg Preparation Style`) are rendered with native HTML `<input type="radio" name="customiser_group_${groupClean}" ...>`.
   - If a customer chooses **Oat Milk** and then clicks **Almond Milk**, Oat Milk is automatically unselected and only Almond Milk remains selected.

2. **Mandatory Selection & Validation**:
   - When opening a product requiring milk (e.g. Babycino, Latte, Flat White, Cappuccino, Mocha, Chai, Hot Chocolate), **all milk options start unselected** (`checked = false`).
   - The header displays `MILK *` with a prominent `* REQUIRED (Select 1)` badge.
   - The **Add to Cart** button starts in a **disabled state** (`disabled = true`, dimmed opacity, `cursor: not-allowed`) displaying: `<i class="ri-lock-line"></i> Please select 1 Milk option`.
   - An alert banner displays: `⚠️ Please select one milk option to continue.`
   - Once the customer selects a milk option, the button immediately activates with: `🛒 Add to Cart • $X.XX`.

3. **Optional Modifiers Start Unselected**:
   - Optional modifier groups (`Coffee Modifiers` like Decaf & Extra Shot, `Flavours` like Caramel, Hazelnut & Vanilla syrups, `Extras`) **always start unchecked** (`checked = false`).
   - Customers are never unintentionally charged for optional add-ons.

4. **Product-Specific Filtering Rules**:
   - **Babycino** (`product_id: 10`):
     - Child-friendly drink: Steamed frothed milk with marshmallows and cocoa dust.
     - Milk: Required Single Select (Full Cream, Skim, Almond, Lactose Free, Oat, Soy).
     - Size: Required Single Select (Standard / Regular Free, Large +$0.80).
     - Syrups / Flavours: Optional (Caramel, Hazelnut, Vanilla).
     - Adult coffee modifiers (Extra Shot, Decaf, Espresso Roasts) are automatically **suppressed/hidden**.
   - **Espresso / Short Black** (`product_id: 1`):
     - Pure black coffee extraction: Milk options are **suppressed/hidden**.
     - Coffee Modifiers: Extra Shot, Decaf (Optional).
   - **Latte / Flat White / Cappuccino / Mocha**:
     - Milk: Required Single Select.
     - Size: Required Single Select.
     - Coffee Modifiers & Syrups: Optional Multi Select.

5. **Live Reactive Price Calculation**:
   $$\text{Final Price} = \left(\text{Base Unit Price} + \sum \text{Selected Extras}\right) \times \text{Quantity}$$
   - Example: Base Babycino ($2.50) + Oat Milk (+$0.80) + Large (+$0.80) = **$4.10**.
   - Validated against backend calculation endpoint: `POST /api/customisations/customisations.php?action=calculate`.

6. **Cart Persistence & Re-Editing**:
   - Items added to the cart store all selected options (`customisation_id`, `group_name`, `option_name`, `extra_price`).
   - The Cart Drawer displays the breakdown clearly (e.g., `Babycino — $4.10`, with badges `Oat Milk +$0.80` and `Large +$0.80`).
   - Clicking **Edit** (`editCartItem(index)`) reopens the customiser with the exact previously saved selections pre-populated for full modification.

---

## 4. Discounts & Promotions Engine (`api/discounts/discounts.php`)

### Key Capabilities:
- **Promotion Types**:
  - `percentage`: Percentage off order (e.g. 15% OFF).
  - `fixed_amount`: Fixed dollar deduction (e.g. $5.00 OFF).
  - `bogo`: Buy One Get One Free / 25% bundle deduction.
  - `free_shipping`: Waives delivery or fee.
- **Trigger Modes**:
  - `voucher`: Requires manual promo code entry at checkout (e.g. `WELCOME10`, `ROASTER20`).
  - `automatic`: Applies automatically if order meets criteria (e.g. `HAPPYHOUR`, `AUTOSPRING`).
- **Targeting & Safeguards**:
  - Minimum order value threshold (`min_order_value`).
  - Applicable categories (`applicable_categories`) or products (`applicable_products`).
  - Usage limits (`usage_limit`) vs current usage count (`current_usage`).
  - Scheduled date ranges (`start_date` and `end_date`).
- **Manager Dashboard UI**:
  - Interactive table with Status badges (`🟢 Active`, `🟡 Scheduled`, `⚪ Expired`, `🔴 Disabled`).
  - Modal form for creating/editing promotions.
  - 1-click Toggle Activation/Deactivation and Delete.

---

## 5. Primary Database Tables (`schema.sql` / `phpmyadmin_import.sql`)

### 1. `Customisations`
```sql
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
    FOREIGN KEY (category_id) REFERENCES Categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE SET NULL
);
```

### 2. `Discounts`
```sql
CREATE TABLE Discounts (
    discount_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    type ENUM('percentage', 'fixed_amount', 'bogo', 'free_shipping') NOT NULL DEFAULT 'percentage',
    discount_percentage DECIMAL(5,2) DEFAULT 0.00,
    fixed_amount DECIMAL(10,2) DEFAULT 0.00,
    min_order_value DECIMAL(10,2) DEFAULT 0.00,
    max_discount_cap DECIMAL(10,2) NULL,
    start_date DATETIME NULL,
    end_date DATETIME NULL,
    usage_limit INT NULL,
    current_usage INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    is_automatic TINYINT(1) DEFAULT 0,
    applicable_categories JSON NULL,
    applicable_products JSON NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 3. `Products` & `Categories`
- `Products`: `product_id`, `category_id`, `product_name`, `description`, `price`, `availability`, `image_url`.
- `Categories`: `category_id`, `category_name`, `icon`, `display_order`.

### 4. `Orders` & `OrderItems`
- `Orders`: `order_id`, `customer_id`, `table_number`, `order_type`, `subtotal`, `discount_amount`, `gst_amount`, `total_amount`, `payment_status`, `order_status`, `promo_code_applied`, `created_at`.
- `OrderItems`: `item_id`, `order_id`, `product_id`, `quantity`, `unit_price`, `total_price`, `customisations_json`, `notes`.

---

## 6. Key API Endpoints Reference

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/customisations/customisations.php` | List available customisation groups (`?product_id=`, `?category_id=`) |
| `POST` | `/api/customisations/customisations.php?action=calculate` | Server-side modifier pricing & summary calculation |
| `GET` | `/api/discounts/discounts.php` | List all promotions / filter active |
| `POST` | `/api/discounts/discounts.php?action=verify` | Validate promo code or automatic discount against cart |
| `POST` | `/api/discounts/discounts.php` | Admin: Create new promotion |
| `PUT` | `/api/discounts/discounts.php?id=123` | Admin: Update promotion |
| `PATCH`| `/api/discounts/discounts.php?id=123&action=toggle` | Admin: Toggle active/disabled status |
| `DELETE`| `/api/discounts/discounts.php?id=123` | Admin: Delete promotion |
| `POST` | `/api/users/login.php` | Authenticate user (Admin/Manager/Barista/Kitchen/Customer) |
| `GET` | `/api/users/me.php` | Current session verification |
| `POST` | `/api/orders/orders.php` | Submit POS order and broadcast to KDS |
| `GET` | `/api/ai/forecast.php` | 2-Year RAG predictive inventory demand forecast |
| `GET` | `/api/reports/audit.php` | Full operational audit trail & logs |

---

## 7. Working Rules & Guidelines for Antigravity & AI Assistants

1. **Git Workflow Rule**:
   - **DO NOT** run `git push` to remote GitHub repositories unless the user explicitly asks to push.
2. **Deployment Rule**:
   - Whenever deploying or asked to update the live server, **ALWAYS** run `python3 -u deploy.py --auto` from `Ravenhill_Project` so `https://mehedihasan.au/kent/cpro306/g1/` stays updated.
3. **Customisation Rule**:
   - Milk and Size options **MUST** strictly remain single-select radio button behavior.
   - Milk **MUST** remain compulsory for milky drinks, with Add-to-Cart disabled until selected.
   - Optional modifiers **MUST** start unchecked.
   - Prices **MUST** update reactively and match backend calculation.
