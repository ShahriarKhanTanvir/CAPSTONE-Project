import re

def update_htaccess():
    htaccess_content = """# ----------------------------------------------------------------------
# Ravenhill POS Security & Performance Configuration
# (NFR01, NFR06, NFR10, NFR11, NFR12, NFR16)
# ----------------------------------------------------------------------

# Enable Rewrite Engine
<IfModule mod_rewrite.c>
    RewriteEngine On

    # NFR16: Force HTTPS Communication
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Rewrite extensionless API endpoints to .php
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME}.php -f
    RewriteRule ^(.*)$ $1.php [L]
</IfModule>

# Enable Gzip / Deflate Compression for sub-3s loading (NFR01)
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json application/xml image/svg+xml
</IfModule>

# Browser Caching Headers for Static Assets (CSS, JS, Images)
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/webp "access plus 1 month"
    ExpiresByType image/svg+xml "access plus 1 month"
    ExpiresByType application/json "access plus 0 seconds"
</IfModule>

# Security & CORS Headers (NFR11, NFR16)
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, PATCH, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With, X-CSRF-Token"
    
    # Security hardening headers
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    
    # NFR16: HTTP Strict Transport Security (HSTS)
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</IfModule>

# Prevent viewing of sensitive files (NFR18)
<FilesMatch "^(schema\.sql|\.env|\.git|deploy\.py|push_db\.py|generate_sql\.py)">
    Order allow,deny
    Deny from all
</FilesMatch>

# Default Charset
AddDefaultCharset UTF-8
"""
    with open('.htaccess', 'w', encoding='utf-8') as f:
        f.write(htaccess_content)
    print("Updated .htaccess successfully!")

def update_app_js():
    with open('app.js', 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Update API object
    old_api_pattern = re.compile(r"const API = \{.*?\n\};\n", re.DOTALL)
    new_api_block = """function getCategoryIcon(name) {
  const n = (name || '').toLowerCase();
  if (n.includes('hot drink') || n.includes('chocolate') || n.includes('chai')) return 'ri-fire-line';
  if (n.includes('cold coffee') || n.includes('cold brew')) return 'ri-snow-line';
  if (n.includes('coffee') || n.includes('espresso')) return 'ri-cup-line';
  if (n.includes('tea')) return 'ri-leaf-line';
  if (n.includes('cold drink') || n.includes('beverage')) return 'ri-goblet-line';
  if (n.includes('smoothie')) return 'ri-drinks-line';
  if (n.includes('juice')) return 'ri-contrast-drop-2-line';
  if (n.includes('breakfast') || n.includes('egg')) return 'ri-sun-line';
  if (n.includes('toastie') || n.includes('melt')) return 'ri-bread-line';
  if (n.includes('sandwich') || n.includes('blt') || n.includes('wrap')) return 'ri-restaurant-line';
  if (n.includes('pastr') || n.includes('croissant') || n.includes('danish')) return 'ri-cake-3-line';
  if (n.includes('baker') || n.includes('muffin') || n.includes('scone') || n.includes('bread')) return 'ri-cake-2-line';
  if (n.includes('lunch') || n.includes('salad') || n.includes('bowl')) return 'ri-bowl-line';
  if (n.includes('side') || n.includes('chip') || n.includes('fries')) return 'ri-french-fries-line';
  return 'ri-cup-line';
}

const API = {
  async fetchHealth() {
    try {
      const res = await fetch(`${API_BASE}/health`);
      return await res.json();
    } catch { return { status: 'offline' }; }
  },
  async fetchCategories() {
    try {
      const res = await fetch(`${API_BASE}/menu/categories.php`);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch {
      try {
        const res2 = await fetch(`${API_BASE}/menu/categories`);
        const data2 = await res2.json();
        return data2.success ? data2.data : null;
      } catch { return null; }
    }
  },
  async fetchCustomisations(productId = null, categoryId = null) {
    try {
      let url = `${API_BASE}/customisations/customisations.php`;
      let params = [];
      if (productId) params.push(`product_id=${productId}`);
      if (categoryId) params.push(`category_id=${categoryId}`);
      if (params.length) url += '?' + params.join('&');
      const res = await fetch(url);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch { return null; }
  },
  async fetchMenuItems() {
    try {
      const res = await fetch(`${API_BASE}/menu/items.php`);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch {
      try {
        const res2 = await fetch(`${API_BASE}/menu/items`);
        const data2 = await res2.json();
        return data2.success ? data2.data : null;
      } catch { return null; }
    }
  },
  async fetchInventory() {
    try {
      const res = await fetch(`${API_BASE}/inventory/inventory.php`);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch {
      try {
        const res2 = await fetch(`${API_BASE}/inventory`);
        const data2 = await res2.json();
        return data2.success ? data2.data : null;
      } catch { return null; }
    }
  },
  async fetchTables() {
    try {
      const res = await fetch(`${API_BASE}/tables/tables.php`);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch {
      try {
        const res2 = await fetch(`${API_BASE}/tables`);
        const data2 = await res2.json();
        return data2.success ? data2.data : null;
      } catch { return null; }
    }
  },
  async fetchOrders() {
    try {
      const res = await fetch(`${API_BASE}/orders/orders.php`);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch {
      try {
        const res2 = await fetch(`${API_BASE}/orders`);
        const data2 = await res2.json();
        return data2.success ? data2.data : null;
      } catch { return null; }
    }
  },
  async fetchAuditLogs() {
    try {
      const res = await fetch(`${API_BASE}/reports/audit.php`);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch { return null; }
  },
  async createOrder(orderPayload) {
    try {
      const res = await fetch(`${API_BASE}/orders/orders.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(orderPayload)
      });
      return await res.json();
    } catch { return null; }
  },
  async updateOrderStatus(orderId, status) {
    try {
      const res = await fetch(`${API_BASE}/orders/orders.php?id=${encodeURIComponent(orderId)}&action=status`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status })
      });
      return await res.json();
    } catch { return null; }
  },
  async updateTable(id, tableData) {
    try {
      const res = await fetch(`${API_BASE}/tables/tables.php?id=${encodeURIComponent(id)}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(tableData)
      });
      return await res.json();
    } catch { return null; }
  },
  async updateInventoryStock(id, stockQty) {
    try {
      const res = await fetch(`${API_BASE}/inventory/inventory.php?id=${encodeURIComponent(id)}&action=stock`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ stockQty })
      });
      return await res.json();
    } catch { return null; }
  },
  async addInventoryItem(invData) {
    try {
      const res = await fetch(`${API_BASE}/inventory/inventory.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(invData)
      });
      return await res.json();
    } catch { return null; }
  },
  async addMenuItem(itemData) {
    try {
      const res = await fetch(`${API_BASE}/menu/items.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(itemData)
      });
      return await res.json();
    } catch { return null; }
  },
  async updateMenuItem(id, itemData) {
    try {
      const res = await fetch(`${API_BASE}/menu/items.php?id=${encodeURIComponent(id)}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(itemData)
      });
      return await res.json();
    } catch { return null; }
  },
  async deleteMenuItem(id) {
    try {
      const res = await fetch(`${API_BASE}/menu/items.php?id=${encodeURIComponent(id)}`, {
        method: 'DELETE'
      });
      return await res.json();
    } catch { return null; }
  },
  async fetchReservations() {
    try {
      const res = await fetch(`${API_BASE}/reservations/reservations.php`);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch { return null; }
  },
  async createReservation(resData) {
    try {
      const res = await fetch(`${API_BASE}/reservations/reservations.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(resData)
      });
      return await res.json();
    } catch { return null; }
  },
  async deleteReservation(id) {
    try {
      const res = await fetch(`${API_BASE}/reservations/reservations.php?id=${encodeURIComponent(id)}`, {
        method: 'DELETE'
      });
      return await res.json();
    } catch { return null; }
  },
  async fetchCustomers() {
    try {
      const res = await fetch(`${API_BASE}/customers/customers.php`);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch { return null; }
  },
  async createCustomer(custData) {
    try {
      const res = await fetch(`${API_BASE}/customers/customers.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(custData)
      });
      return await res.json();
    } catch { return null; }
  },
  async fetchTransactions() {
    try {
      const res = await fetch(`${API_BASE}/payments/payments.php`);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch { return null; }
  },
  async createTransaction(txnData) {
    try {
      const res = await fetch(`${API_BASE}/payments/payments.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(txnData)
      });
      return await res.json();
    } catch { return null; }
  },
  async fetchNextOrderNum() {
    try {
      const res = await fetch(`${API_BASE}/orders/orders.php?action=next_num`);
      const data = await res.json();
      return data.success ? data.data?.nextNumber : 9045;
    } catch { return 9045; }
  },
  async fetchAnalyticsSummary() {
    try {
      const res = await fetch(`${API_BASE}/reports/dashboard.php`);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch { return null; }
  },
  async addTable(tableData) {
    try {
      const res = await fetch(`${API_BASE}/tables/tables.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(tableData)
      });
      return await res.json();
    } catch { return null; }
  },
  async deleteTable(id) {
    try {
      const res = await fetch(`${API_BASE}/tables/tables.php?id=${encodeURIComponent(id)}`, {
        method: 'DELETE'
      });
      return await res.json();
    } catch { return null; }
  },
  async fetchDiscounts() {
    try {
      const res = await fetch(`${API_BASE}/discounts/discounts.php`);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch { return null; }
  },
  async addDiscount(discData) {
    try {
      const res = await fetch(`${API_BASE}/discounts/discounts.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(discData)
      });
      return await res.json();
    } catch { return null; }
  },
  async deleteDiscount(id) {
    try {
      const res = await fetch(`${API_BASE}/discounts/discounts.php?id=${encodeURIComponent(id)}`, {
        method: 'DELETE'
      });
      return await res.json();
    } catch { return null; }
  },
  async fetchFeedback() {
    try {
      const res = await fetch(`${API_BASE}/feedback/feedback.php`);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch { return null; }
  },
  async addFeedback(fbData) {
    try {
      const res = await fetch(`${API_BASE}/feedback/feedback.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(fbData)
      });
      return await res.json();
    } catch { return null; }
  },
  async deleteFeedback(id) {
    try {
      const res = await fetch(`${API_BASE}/feedback/feedback.php?id=${encodeURIComponent(id)}`, {
        method: 'DELETE'
      });
      return await res.json();
    } catch { return null; }
  },
  async fetchStaff() {
    try {
      const res = await fetch(`${API_BASE}/employees/employees.php`);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch { return null; }
  },
  async addStaff(staffData) {
    try {
      const res = await fetch(`${API_BASE}/employees/employees.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(staffData)
      });
      return await res.json();
    } catch { return null; }
  },
  async updateStaff(id, staffData) {
    try {
      const res = await fetch(`${API_BASE}/employees/employees.php?id=${encodeURIComponent(id)}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(staffData)
      });
      return await res.json();
    } catch { return null; }
  },
  async deleteStaff(id) {
    try {
      const res = await fetch(`${API_BASE}/employees/employees.php?id=${encodeURIComponent(id)}`, {
        method: 'DELETE'
      });
      return await res.json();
    } catch { return null; }
  },
  async updateCustomer(id, custData) {
    try {
      const res = await fetch(`${API_BASE}/customers/customers.php?id=${encodeURIComponent(id)}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(custData)
      });
      return await res.json();
    } catch { return null; }
  },
  async deleteCustomer(id) {
    try {
      const res = await fetch(`${API_BASE}/customers/customers.php?id=${encodeURIComponent(id)}`, {
        method: 'DELETE'
      });
      return await res.json();
    } catch { return null; }
  },
  async fetchState(key) {
    try {
      const res = await fetch(`${API_BASE}/state/${encodeURIComponent(key)}`);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch { return null; }
  },
  async saveState(key, value) {
    try {
      const res = await fetch(`${API_BASE}/state/${encodeURIComponent(key)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ value })
      });
      return await res.json();
    } catch { return null; }
  }
};
"""
    content = old_api_pattern.sub(new_api_block, content)

    # 2. Update default categories and items in DB constant
    default_categories_js = """const defaultMenuCategories = [
  { id: '1', name: 'Coffee', icon: 'ri-cup-line', desc: 'Espresso-based coffees' },
  { id: '2', name: 'Hot Drinks', icon: 'ri-fire-line', desc: 'Chai, chocolates & specialty lattes' },
  { id: '3', name: 'Tea', icon: 'ri-leaf-line', desc: 'Premium loose-leaf and herbal teas' },
  { id: '4', name: 'Cold Coffee', icon: 'ri-snow-line', desc: 'Chilled espresso and iced lattes' },
  { id: '5', name: 'Cold Drinks', icon: 'ri-goblet-line', desc: 'Cold milkshakes, sodas and water' },
  { id: '6', name: 'Smoothies', icon: 'ri-drinks-line', desc: 'Blended real fruit smoothies' },
  { id: '7', name: 'Juices', icon: 'ri-contrast-drop-2-line', desc: 'Fresh cold-pressed juices' },
  { id: '8', name: 'Breakfast', icon: 'ri-sun-line', desc: 'Farm eggs, toast and morning plates' },
  { id: '9', name: 'Toasties', icon: 'ri-bread-line', desc: 'Gourmet melted sourdough toasties' },
  { id: '10', name: 'Sandwiches', icon: 'ri-restaurant-line', desc: 'Fresh deli sandwiches & wraps' },
  { id: '11', name: 'Pastries', icon: 'ri-cake-3-line', desc: 'Flaky artisan butter croissants & danishes' },
  { id: '12', name: 'Bakery', icon: 'ri-cake-2-line', desc: 'Muffins, banana breads and scones' },
  { id: '13', name: 'Lunch', icon: 'ri-bowl-line', desc: 'Fresh seasonal salads & protein bowls' },
  { id: '14', name: 'Sides', icon: 'ri-french-fries-line', desc: 'Crispy fries and snack sides' }
];

const defaultMenuItems = [
  // Coffee
  { id: '1', product_id: 1, catId: '1', category_id: 1, name: 'Espresso / Short Black', desc: 'Intense double-shot extraction of Ravenhill Reserve blend', price: 4.00, hasModifiers: true, image: 'Brand%20Resources/double_espresso_short_black.png' },
  { id: '2', product_id: 2, catId: '1', category_id: 1, name: 'Long Black', desc: 'Double shot poured over hot filtered water preserving crema', price: 4.80, hasModifiers: true, image: 'Brand%20Resources/long_black_coffee.png' },
  { id: '3', product_id: 3, catId: '1', category_id: 1, name: 'Flat White', desc: 'Silky textured microfoam folded over a double shot of espresso', price: 5.20, hasModifiers: true, image: 'Brand%20Resources/flat_white_coffee.png' },
  { id: '4', product_id: 4, catId: '1', category_id: 1, name: 'Latte', desc: 'Smooth espresso with velvety steamed milk and light froth', price: 5.20, hasModifiers: true, image: 'Brand%20Resources/flat_white_coffee.png' },
  { id: '5', product_id: 5, catId: '1', category_id: 1, name: 'Cappuccino', desc: 'Rich espresso with deep velvety foam and dark cocoa dusting', price: 5.20, hasModifiers: true, image: 'Brand%20Resources/cappuccino_coffee.png' },
  { id: '6', product_id: 6, catId: '1', category_id: 1, name: 'Piccolo Latte', desc: 'Concentrated ristretto with warm silky milk in a 4oz glass', price: 4.80, hasModifiers: true, image: 'Brand%20Resources/piccolo_latte.png' },
  { id: '7', product_id: 7, catId: '1', category_id: 1, name: 'Short Macchiato', desc: 'Pure espresso marked with a dash of steamed milk foam', price: 4.50, hasModifiers: true, image: 'Brand%20Resources/double_espresso_short_black.png' },
  { id: '8', product_id: 8, catId: '1', category_id: 1, name: 'Long Macchiato', desc: 'Double shot over hot water stained with steamed milk foam', price: 5.20, hasModifiers: true, image: 'Brand%20Resources/long_black_coffee.png' },
  { id: '9', product_id: 9, catId: '1', category_id: 1, name: 'Mocha', desc: 'Belgian dark chocolate melted with espresso and silky milk', price: 5.80, hasModifiers: true, image: 'Brand%20Resources/cappuccino_coffee.png' },
  { id: '10', product_id: 10, catId: '1', category_id: 1, name: 'Babycino', desc: 'Warm frothed milk with sweet cocoa and two marshmallows', price: 2.50, hasModifiers: false, image: 'Brand%20Resources/cappuccino_coffee.png' },

  // Hot Drinks
  { id: '11', product_id: 11, catId: '2', category_id: 2, name: 'Hot Chocolate', desc: 'Belgian 54% dark chocolate with steamed milk and marshmallows', price: 5.50, hasModifiers: true, image: 'Brand%20Resources/cappuccino_coffee.png' },
  { id: '12', product_id: 12, catId: '2', category_id: 2, name: 'White Hot Chocolate', desc: 'Velvety Swiss white chocolate melted into creamy steamed milk', price: 5.70, hasModifiers: true, image: 'Brand%20Resources/cappuccino_coffee.png' },
  { id: '13', product_id: 13, catId: '2', category_id: 2, name: 'Chai Latte', desc: 'Spiced black tea with cinnamon, cardamom and steamed milk', price: 5.70, hasModifiers: true, image: 'Brand%20Resources/prana_sticky_chai_latte.png' },
  { id: '14', product_id: 14, catId: '2', category_id: 2, name: 'Dirty Chai', desc: 'Traditional spiced chai latte infused with a shot of espresso', price: 6.20, hasModifiers: true, image: 'Brand%20Resources/prana_sticky_chai_latte.png' },
  { id: '15', product_id: 15, catId: '2', category_id: 2, name: 'Matcha Latte', desc: 'Ceremonial grade Japanese Uji matcha with silky steamed milk', price: 6.20, hasModifiers: true, image: 'Brand%20Resources/flat_white_coffee.png' },
  { id: '16', product_id: 16, catId: '2', category_id: 2, name: 'Turmeric Latte', desc: 'Golden spiced blend of organic turmeric, ginger and milk', price: 5.90, hasModifiers: true, image: 'Brand%20Resources/flat_white_coffee.png' },

  // Tea
  { id: '17', product_id: 17, catId: '3', category_id: 3, name: 'English Breakfast Tea', desc: 'Full-bodied organic Ceylon and Assam black tea blend', price: 4.80, hasModifiers: true, image: 'Brand%20Resources/batch_brew_filter.png' },
  { id: '18', product_id: 18, catId: '3', category_id: 3, name: 'Earl Grey Tea', desc: 'Fragrant black tea with cold-pressed Italian bergamot oil', price: 4.80, hasModifiers: true, image: 'Brand%20Resources/batch_brew_filter.png' },
  { id: '19', product_id: 19, catId: '3', category_id: 3, name: 'Green Tea', desc: 'Delicate Japanese Sencha green tea with vibrant clean notes', price: 4.80, hasModifiers: true, image: 'Brand%20Resources/batch_brew_filter.png' },
  { id: '20', product_id: 20, catId: '3', category_id: 3, name: 'Peppermint Tea', desc: 'Refreshing whole organic peppermint leaves, caffeine-free', price: 4.80, hasModifiers: true, image: 'Brand%20Resources/batch_brew_filter.png' },
  { id: '21', product_id: 21, catId: '3', category_id: 3, name: 'Chamomile Tea', desc: 'Calming whole chamomile flower blossoms with apple sweetness', price: 4.80, hasModifiers: true, image: 'Brand%20Resources/batch_brew_filter.png' },
  { id: '22', product_id: 22, catId: '3', category_id: 3, name: 'Lemongrass & Ginger Tea', desc: 'Zesty lemongrass stalks with spicy warming ginger root', price: 4.80, hasModifiers: true, image: 'Brand%20Resources/batch_brew_filter.png' },

  // Cold Coffee
  { id: '23', product_id: 23, catId: '4', category_id: 4, name: 'Iced Latte', desc: 'Double shot of espresso poured over cold milk and ice', price: 6.80, hasModifiers: true, image: 'Brand%20Resources/iced_oat_milk_latte.png' },
  { id: '24', product_id: 24, catId: '4', category_id: 4, name: 'Iced Long Black', desc: 'Double shot of espresso over chilled mineral water and ice', price: 6.20, hasModifiers: true, image: 'Brand%20Resources/cold_brew_coffee.png' },
  { id: '25', product_id: 25, catId: '4', category_id: 4, name: 'Iced Coffee', desc: 'Chilled espresso and milk with vanilla ice cream & whipped cream', price: 7.80, hasModifiers: true, image: 'Brand%20Resources/iced_oat_milk_latte.png' },
  { id: '26', product_id: 26, catId: '4', category_id: 4, name: 'Iced Mocha', desc: 'Belgian melted chocolate, espresso shot, chilled milk and cream', price: 8.20, hasModifiers: true, image: 'Brand%20Resources/iced_oat_milk_latte.png' },

  // Cold Drinks
  { id: '27', product_id: 27, catId: '5', category_id: 5, name: 'Iced Chocolate', desc: 'Cold Belgian chocolate milk with vanilla ice cream & cream', price: 7.80, hasModifiers: true, image: 'Brand%20Resources/iced_oat_milk_latte.png' },
  { id: '28', product_id: 28, catId: '5', category_id: 5, name: 'Iced Chai Latte', desc: 'Chilled aromatic spiced chai infused with cold milk over ice', price: 7.20, hasModifiers: true, image: 'Brand%20Resources/prana_sticky_chai_latte.png' },
  { id: '29', product_id: 29, catId: '5', category_id: 5, name: 'Iced Matcha Latte', desc: 'Ceremonial Japanese matcha whisked with ice-cold milk over ice', price: 7.80, hasModifiers: true, image: 'Brand%20Resources/iced_oat_milk_latte.png' },
  { id: '30', product_id: 30, catId: '5', category_id: 5, name: 'Milkshake', desc: 'Classic thick shake (Chocolate, Vanilla, Strawberry, Caramel)', price: 8.50, hasModifiers: true, image: 'Brand%20Resources/iced_oat_milk_latte.png' },
  { id: '31', product_id: 31, catId: '5', category_id: 5, name: 'Bottled Still Water', desc: 'Pure Australian spring water in recyclable 600ml bottle', price: 3.50, hasModifiers: false, image: 'Brand%20Resources/cold_brew_coffee.png' },
  { id: '32', product_id: 32, catId: '5', category_id: 5, name: 'Sparkling Water', desc: 'Crisp mineral sparkling water with fresh lemon wedge 500ml', price: 5.00, hasModifiers: false, image: 'Brand%20Resources/cold_brew_coffee.png' },
  { id: '33', product_id: 33, catId: '5', category_id: 5, name: 'Soft Drink', desc: 'Classic canned soft drinks (Coke, Coke Zero, Sprite, Fanta)', price: 4.50, hasModifiers: false, image: 'Brand%20Resources/cold_brew_coffee.png' },

  // Smoothies
  { id: '34', product_id: 34, catId: '6', category_id: 6, name: 'Smoothie (Banana / Berry / Mango / Tropical)', desc: 'Blended fruit smoothie with Greek yogurt, honey and chia seeds', price: 9.50, hasModifiers: true, image: 'Brand%20Resources/iced_oat_milk_latte.png' },

  // Juices
  { id: '35', product_id: 35, catId: '7', category_id: 7, name: 'Fresh Orange Juice', desc: '100% freshly cold-pressed sweet Valencia oranges', price: 8.50, hasModifiers: true, image: 'Brand%20Resources/cold_brew_coffee.png' },
  { id: '36', product_id: 36, catId: '7', category_id: 7, name: 'Fresh Apple Juice', desc: 'Crisp cold-pressed Granny Smith and Pink Lady apples', price: 8.50, hasModifiers: true, image: 'Brand%20Resources/cold_brew_coffee.png' },
  { id: '37', product_id: 37, catId: '7', category_id: 7, name: 'Green Juice', desc: 'Celery, cucumber, kale, green apple and fresh mint', price: 9.50, hasModifiers: true, image: 'Brand%20Resources/cold_brew_coffee.png' },

  // Breakfast
  { id: '38', product_id: 38, catId: '8', category_id: 8, name: 'Sourdough Toast', desc: 'Two thick toasted slices of Noisette sourdough with butter & spreads', price: 6.50, hasModifiers: true, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '39', product_id: 39, catId: '8', category_id: 8, name: 'Eggs on Toast', desc: 'Two free-range eggs cooked your way on toasted sourdough', price: 15.00, hasModifiers: true, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '40', product_id: 40, catId: '8', category_id: 8, name: 'Bacon & Egg Roll', desc: 'Smoked streaky bacon, fried egg, relish on a brioche bun', price: 12.00, hasModifiers: true, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '41', product_id: 41, catId: '8', category_id: 8, name: 'Breakfast Wrap', desc: 'Scrambled eggs, bacon, spinach, avocado & chipotle mayo', price: 13.50, hasModifiers: true, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '42', product_id: 42, catId: '8', category_id: 8, name: 'Avocado Toast', desc: 'Smashed Hass avocado, Persian feta, dukkah, radish & lemon', price: 18.50, hasModifiers: true, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '43', product_id: 43, catId: '8', category_id: 8, name: 'Granola & Yoghurt', desc: 'Honey toasted granola with seasonal berries & vanilla yogurt', price: 15.50, hasModifiers: false, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '44', product_id: 44, catId: '8', category_id: 8, name: 'Porridge', desc: 'Rolled oats with almond milk, caramelized banana & maple', price: 15.00, hasModifiers: false, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '45', product_id: 45, catId: '8', category_id: 8, name: 'Eggs Benedict', desc: 'Two poached eggs, smoked ham or bacon, citrus hollandaise on sourdough', price: 21.00, hasModifiers: true, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '46', product_id: 46, catId: '8', category_id: 8, name: 'Breakfast Burger', desc: 'Angus patty, bacon, fried egg, hash brown, cheddar & BBQ relish', price: 16.00, hasModifiers: true, image: 'Brand%20Resources/butter_croissant.png' },

  // Toasties
  { id: '47', product_id: 47, catId: '9', category_id: 9, name: 'Ham & Cheese Toastie', desc: 'Smoked leg ham, melted Gruyère and vintage cheddar on sourdough', price: 12.50, hasModifiers: true, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '48', product_id: 48, catId: '9', category_id: 9, name: 'Cheese & Tomato Toastie', desc: 'Heirloom tomatoes, aged cheddar cheese and basil on sourdough', price: 11.50, hasModifiers: true, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '49', product_id: 49, catId: '9', category_id: 9, name: 'Three Cheese Toastie', desc: 'Mozzarella, vintage cheddar and Gruyère on golden sourdough', price: 14.00, hasModifiers: true, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '50', product_id: 50, catId: '9', category_id: 9, name: 'Tuna Melt', desc: 'Albacore tuna salad, dill, melted provolone and jalapeño', price: 15.50, hasModifiers: true, image: 'Brand%20Resources/butter_croissant.png' },

  // Sandwiches
  { id: '51', product_id: 51, catId: '10', category_id: 10, name: 'BLT Toasted Sandwich', desc: 'Crispy bacon, cos lettuce, vine tomato and aioli on sourdough', price: 13.50, hasModifiers: true, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '52', product_id: 52, catId: '10', category_id: 10, name: 'Chicken & Avocado Sandwich', desc: 'Poached chicken breast, avocado, rocket and herb mayo on baguette', price: 16.50, hasModifiers: true, image: 'Brand%20Resources/butter_croissant.png' },

  // Pastries
  { id: '53', product_id: 53, catId: '11', category_id: 11, name: 'Plain Croissant', desc: 'Traditional flaky French butter croissant baked fresh daily', price: 6.50, hasModifiers: false, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '54', product_id: 54, catId: '11', category_id: 11, name: 'Almond Croissant', desc: 'Double-baked croissant filled with rich almond frangipane', price: 8.00, hasModifiers: false, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '55', product_id: 55, catId: '11', category_id: 11, name: 'Chocolate Croissant', desc: 'Flaky French pastry with two batons of dark Belgian chocolate', price: 7.50, hasModifiers: false, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '56', product_id: 56, catId: '11', category_id: 11, name: 'Ham & Cheese Croissant', desc: 'Warm butter croissant with leg ham, Swiss cheese & bechamel', price: 9.50, hasModifiers: false, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '57', product_id: 57, catId: '11', category_id: 11, name: 'Fruit Danish', desc: 'Crispy pastry rosette with vanilla custard and glazed fruit', price: 7.00, hasModifiers: false, image: 'Brand%20Resources/butter_croissant.png' },

  // Bakery
  { id: '58', product_id: 58, catId: '12', category_id: 12, name: 'Blueberry Muffin', desc: 'Moist vanilla batter with wild blueberries and crumble top', price: 6.50, hasModifiers: false, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '59', product_id: 59, catId: '12', category_id: 12, name: 'Chocolate Muffin', desc: 'Double chocolate chunk muffin with dark and milk chips', price: 6.50, hasModifiers: false, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '60', product_id: 60, catId: '12', category_id: 12, name: 'Banana Bread', desc: 'Toasted spiced banana loaf with whipped honey cinnamon butter', price: 7.00, hasModifiers: false, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '61', product_id: 61, catId: '12', category_id: 12, name: 'Blueberry Scone', desc: 'Traditional scone served warm with strawberry jam & cream', price: 6.50, hasModifiers: false, image: 'Brand%20Resources/butter_croissant.png' },

  // Lunch
  { id: '62', product_id: 62, catId: '13', category_id: 13, name: 'Seasonal Salad', desc: 'Baby spinach, quinoa, roast pumpkin, walnuts & balsamic citrus', price: 18.00, hasModifiers: true, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '63', product_id: 63, catId: '13', category_id: 13, name: 'Chicken Caesar Salad', desc: 'Grilled chicken, bacon, cos lettuce, croutons, parmesan & egg', price: 21.00, hasModifiers: true, image: 'Brand%20Resources/butter_croissant.png' },

  // Sides
  { id: '64', product_id: 64, catId: '14', category_id: 14, name: 'Chips', desc: 'Bowl of crispy golden shoestring potato fries with garlic aioli', price: 8.50, hasModifiers: false, image: 'Brand%20Resources/butter_croissant.png' },
  { id: '65', product_id: 65, catId: '14', category_id: 14, name: 'Sweet Potato Chips', desc: 'Crunchy rosemary salted sweet potato fries with chipotle mayo', price: 10.50, hasModifiers: false, image: 'Brand%20Resources/butter_croissant.png' }
];

const DB = {
  menuCategories: defaultMenuCategories,
  menuItems: defaultMenuItems,"""

    old_db_pattern = re.compile(r"const DB = \{\s*menuCategories:\s*\[\],\s*menuItems:\s*\[\],", re.DOTALL)
    content = old_db_pattern.sub(default_categories_js, content)

    # 3. Update AppState activeCategory
    content = content.replace("activeCategory: 'cat-espresso',", "activeCategory: '1',")

    # 4. Update syncBackendData to normalize categories and items
    old_sync_cat_item = """    if (categories && categories.length) DB.menuCategories = categories;
    if (menuItems && menuItems.length) DB.menuItems = menuItems;"""

    new_sync_cat_item = """    if (categories && categories.length) {
      DB.menuCategories = categories.map(cat => ({
        id: String(cat.category_id !== undefined ? cat.category_id : (cat.id || '')),
        category_id: cat.category_id !== undefined ? cat.category_id : cat.id,
        name: cat.category_name || cat.name,
        icon: cat.icon || getCategoryIcon(cat.category_name || cat.name),
        desc: cat.description || cat.desc || ''
      }));
    }
    if (menuItems && menuItems.length) {
      DB.menuItems = menuItems.map(item => {
        const catIdStr = String(item.category_id !== undefined ? item.category_id : (item.catId || '1'));
        return {
          id: String(item.product_id !== undefined ? item.product_id : (item.id || '')),
          product_id: item.product_id !== undefined ? item.product_id : item.id,
          catId: catIdStr,
          category_id: item.category_id !== undefined ? item.category_id : item.catId,
          name: item.product_name || item.name,
          desc: item.description || item.desc || '',
          price: parseFloat(item.price || 0),
          image: item.image || '',
          availability: item.availability !== undefined ? !!item.availability : true,
          hasModifiers: item.hasModifiers !== undefined ? item.hasModifiers : ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '13'].includes(catIdStr)
        };
      });
    }

    if (!DB.menuCategories.some(c => String(c.id) === String(AppState.activeCategory)) && DB.menuCategories.length > 0) {
      AppState.activeCategory = DB.menuCategories[0].id;
    }"""

    content = content.replace(old_sync_cat_item, new_sync_cat_item)

    # 5. Update renderPOSView filter logic
    old_render_pos_filter = """  let filteredItems = DB.menuItems.filter(item => item.catId === AppState.activeCategory);"""
    new_render_pos_filter = """  if (!DB.menuCategories.some(c => String(c.id) === String(AppState.activeCategory)) && DB.menuCategories.length > 0) {
    AppState.activeCategory = DB.menuCategories[0].id;
  }
  let filteredItems = DB.menuItems.filter(item => String(item.catId) === String(AppState.activeCategory) || String(item.category_id) === String(AppState.activeCategory));"""
    content = content.replace(old_render_pos_filter, new_render_pos_filter)

    with open('app.js', 'w', encoding='utf-8') as f:
        f.write(content)
    print("Updated app.js successfully!")

if __name__ == '__main__':
    update_htaccess()
    update_app_js()
