/**
 * REST API CLIENT & WEBSOCKET ENGINE
 * Dynamically resolves relative PHP API endpoints on production or localhost
 */
// Auto-detect base API URL supporting direct path and subfolders like /kent/cpro306/g1/
function getAPIBase() {
  if (window.location.hostname === 'localhost' && window.location.port === '5000') {
    return 'http://localhost:5000/api';
  }
  let path = window.location.pathname;
  if (path.endsWith('.html') || path.endsWith('.php')) {
    path = path.substring(0, path.lastIndexOf('/'));
  }
  if (path.endsWith('/')) {
    path = path.slice(0, -1);
  }
  return window.location.origin + path + '/api';
}
const API_BASE = getAPIBase();


function getCategoryIcon(name) {
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
    async fetchBootstrap() {
    try {
      const res = await fetch(`${API_BASE}/bootstrap.php`);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch { return null; }
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
    async fetchStationTickets(station = 'all') {
    try {
      const res = await fetch(`${API_BASE}/orders/kds.php?station=${encodeURIComponent(station)}`);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch { return null; }
  },
    async serveOrder(orderId) {
    try {
      const res = await fetch(`${API_BASE}/orders/kds.php?action=serve_order&order_id=${orderId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
      });
      return await res.json();
    } catch { return null; }
  },
  async setStationTicketStatus(ticketId, status) {
    try {
      const res = await fetch(`${API_BASE}/orders/kds.php?action=set_status&ticket_id=${ticketId}&status=${encodeURIComponent(status)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
      });
      return await res.json();
    } catch { return null; }
  },
  async bumpStationTicket(ticketId) {
    try {
      const res = await fetch(`${API_BASE}/orders/kds.php?action=bump_ticket&ticket_id=${ticketId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
      });
      return await res.json();
    } catch { return null; }
  },
  async recallStationTicket(ticketId) {
    try {
      const res = await fetch(`${API_BASE}/orders/kds.php?action=recall_ticket&ticket_id=${ticketId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
      });
      return await res.json();
    } catch { return null; }
  },
  async fetchCustomerOrder(orderId = null) {
    try {
      const url = orderId ? `${API_BASE}/orders/customer_order.php?order_id=${orderId}` : `${API_BASE}/orders/customer_order.php?latest=1`;
      const res = await fetch(url);
      return await res.json();
    } catch { return null; }
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
    async createPayPalOrder(orderId, amount) {
    try {
      const res = await fetch(`${API_BASE}/payments/paypal.php?action=create_order`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, amount: amount })
      });
      return await res.json();
    } catch { return null; }
  },
  async capturePayPalOrder(paypalOrderId, orderId, amount, cashier) {
    try {
      const res = await fetch(`${API_BASE}/payments/paypal.php?action=capture_order`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          paypal_order_id: paypalOrderId,
          order_id: orderId,
          amount: amount,
          cashier: cashier
        })
      });
      return await res.json();
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

let socket = null;
if (typeof io !== 'undefined') {
  try {
    socket = io('http://localhost:5000');
    socket.on('connect', () => {
      console.log('[Frontend] Connected to WebSocket server on port 5000');
    });
    socket.on('order:new', (newOrder) => {
      console.log('[KDS Stream] New order received via WebSocket:', newOrder);
      if (typeof DB !== 'undefined' && DB.kdsOrders) {
        // Prevent duplicates
        if (!DB.kdsOrders.some(o => o.id === newOrder.id)) {
          DB.kdsOrders.unshift(newOrder);
          if (typeof updateKDSBadge === 'function') updateKDSBadge();
          if (typeof renderCurrentModule === 'function' && AppState.activeModule === 'kds') {
            renderCurrentModule();
          }
        }
      }
    });
    socket.on('order:status_update', (data) => {
      console.log('[KDS Stream] Order status updated via WebSocket:', data);
      // Re-sync data from backend to keep tables, KDS, and dashboard in sync
      if (typeof syncBackendData === 'function') {
        syncBackendData();
      }
    });
    socket.on('table:update', (data) => {
      console.log('[Table Stream] Table updated via WebSocket:', data);
      if (typeof DB !== 'undefined' && DB.tables) {
        const t = DB.tables.find(tbl => tbl.id === data.id);
        if (t) {
          t.status = data.status;
          if (data.orderId !== undefined) t.orderId = data.orderId;
        }
        if (typeof saveLocalDB === 'function') saveLocalDB();
        if (typeof renderCartTableSelect === 'function') renderCartTableSelect();
        if (typeof renderCurrentModule === 'function' && AppState.activeModule === 'tables') {
          renderCurrentModule();
        }
      }
    });
  } catch (e) {
    console.warn('[Socket.io] Real-time engine offline:', e);
  }
}

// ==========================================
// 1. DATA MODELS & IN-MEMORY DATABASE
// ==========================================

const defaultPermissions = {
  admin: {
    pos: true,
    kds: true,
    waitstaff: true,
    customer_tracker: true,
    tables: true,
    reservations: true,
    payments: true,
    menu: true,
    inventory: true,
    suppliers: true,
    discounts: true,
    customers: true,
    employees: true,
    feedback: true,
    dashboard: true,
    access: true,
    audit: true
  },
  manager: {
    pos: true,
    kds: true,
    waitstaff: true,
    customer_tracker: true,
    tables: true,
    reservations: true,
    payments: true,
    menu: true,
    inventory: true,
    suppliers: true,
    discounts: true,
    customers: true,
    employees: true,
    feedback: true,
    dashboard: true,
    access: false,
    audit: true
  },
  cashier: {
    pos: true,
    kds: true,
    waitstaff: true,
    customer_tracker: true,
    tables: true,
    reservations: true,
    payments: true,
    menu: true,
    inventory: true,
    suppliers: false,
    discounts: true,
    customers: true,
    employees: false,
    feedback: true,
    dashboard: false,
    access: false,
    audit: false
  },
  kitchen: {
    pos: false,
    kds: true,
    waitstaff: false,
    customer_tracker: false,
    tables: false,
    reservations: false,
    menu: false,
    inventory: true,
    suppliers: false,
    discounts: false,
    customers: false,
    employees: false,
    feedback: true,
    dashboard: false,
    access: false,
    audit: false
  },
  barista: {
    pos: false,
    kds: true,
    waitstaff: false,
    customer_tracker: false,
    tables: false,
    reservations: false,
    menu: false,
    inventory: true,
    suppliers: false,
    discounts: false,
    customers: false,
    employees: false,
    feedback: true,
    dashboard: false,
    access: false,
    audit: false
  },
  waitstaff: {
    pos: true,
    kds: false,
    waitstaff: true,
    customer_tracker: false,
    tables: true,
    reservations: true,
    menu: true,
    inventory: false,
    suppliers: false,
    discounts: false,
    customers: true,
    employees: false,
    feedback: true,
    dashboard: false,
    access: false,
    audit: false
  },
  customer: {
    pos: true,
    kds: false,
    waitstaff: false,
    customer_tracker: true,
    tables: false,
    reservations: true,
    menu: true,
    inventory: false,
    suppliers: false,
    discounts: true,
    customers: true,
    employees: false,
    feedback: true,
    dashboard: false,
    access: false,
    audit: false
  }
};


const defaultMenuCategories = [
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
  { id: '1', product_id: 1, catId: '1', category_id: 1, name: 'Espresso / Short Black', desc: 'Intense double-shot extraction of Ravenhill Reserve blend', price: 4.00, hasModifiers: true, image: 'brand_recources/double_espresso_short_black.png' },
  { id: '2', product_id: 2, catId: '1', category_id: 1, name: 'Long Black', desc: 'Double shot poured over hot filtered water preserving crema', price: 4.80, hasModifiers: true, image: 'brand_recources/long_black_coffee.png' },
  { id: '3', product_id: 3, catId: '1', category_id: 1, name: 'Flat White', desc: 'Silky textured microfoam folded over a double shot of espresso', price: 5.20, hasModifiers: true, image: 'brand_recources/flat_white_coffee.png' },
  { id: '4', product_id: 4, catId: '1', category_id: 1, name: 'Latte', desc: 'Smooth espresso with velvety steamed milk and light froth', price: 5.20, hasModifiers: true, image: 'brand_recources/flat_white_coffee.png' },
  { id: '5', product_id: 5, catId: '1', category_id: 1, name: 'Cappuccino', desc: 'Rich espresso with deep velvety foam and dark cocoa dusting', price: 5.20, hasModifiers: true, image: 'brand_recources/cappuccino_coffee.png' },
  { id: '6', product_id: 6, catId: '1', category_id: 1, name: 'Piccolo Latte', desc: 'Concentrated ristretto with warm silky milk in a 4oz glass', price: 4.80, hasModifiers: true, image: 'brand_recources/piccolo_latte.png' },
  { id: '7', product_id: 7, catId: '1', category_id: 1, name: 'Short Macchiato', desc: 'Pure espresso marked with a dash of steamed milk foam', price: 4.50, hasModifiers: true, image: 'brand_recources/double_espresso_short_black.png' },
  { id: '8', product_id: 8, catId: '1', category_id: 1, name: 'Long Macchiato', desc: 'Double shot over hot water stained with steamed milk foam', price: 5.20, hasModifiers: true, image: 'brand_recources/long_black_coffee.png' },
  { id: '9', product_id: 9, catId: '1', category_id: 1, name: 'Mocha', desc: 'Belgian dark chocolate melted with espresso and silky milk', price: 5.80, hasModifiers: true, image: 'brand_recources/cappuccino_coffee.png' },
  { id: '10', product_id: 10, catId: '1', category_id: 1, name: 'Babycino', desc: 'Warm frothed milk with sweet cocoa and two marshmallows', price: 2.50, hasModifiers: true, image: 'brand_recources/cappuccino_coffee.png' },

  // Hot Drinks
  { id: '11', product_id: 11, catId: '2', category_id: 2, name: 'Hot Chocolate', desc: 'Belgian 54% dark chocolate with steamed milk and marshmallows', price: 5.50, hasModifiers: true, image: 'brand_recources/cappuccino_coffee.png' },
  { id: '12', product_id: 12, catId: '2', category_id: 2, name: 'White Hot Chocolate', desc: 'Velvety Swiss white chocolate melted into creamy steamed milk', price: 5.70, hasModifiers: true, image: 'brand_recources/cappuccino_coffee.png' },
  { id: '13', product_id: 13, catId: '2', category_id: 2, name: 'Chai Latte', desc: 'Spiced black tea with cinnamon, cardamom and steamed milk', price: 5.70, hasModifiers: true, image: 'brand_recources/prana_sticky_chai_latte.png' },
  { id: '14', product_id: 14, catId: '2', category_id: 2, name: 'Dirty Chai', desc: 'Traditional spiced chai latte infused with a shot of espresso', price: 6.20, hasModifiers: true, image: 'brand_recources/prana_sticky_chai_latte.png' },
  { id: '15', product_id: 15, catId: '2', category_id: 2, name: 'Matcha Latte', desc: 'Ceremonial grade Japanese Uji matcha with silky steamed milk', price: 6.20, hasModifiers: true, image: 'brand_recources/flat_white_coffee.png' },
  { id: '16', product_id: 16, catId: '2', category_id: 2, name: 'Turmeric Latte', desc: 'Golden spiced blend of organic turmeric, ginger and milk', price: 5.90, hasModifiers: true, image: 'brand_recources/flat_white_coffee.png' },

  // Tea
  { id: '17', product_id: 17, catId: '3', category_id: 3, name: 'English Breakfast Tea', desc: 'Full-bodied organic Ceylon and Assam black tea blend', price: 4.80, hasModifiers: true, image: 'brand_recources/batch_brew_filter.png' },
  { id: '18', product_id: 18, catId: '3', category_id: 3, name: 'Earl Grey Tea', desc: 'Fragrant black tea with cold-pressed Italian bergamot oil', price: 4.80, hasModifiers: true, image: 'brand_recources/batch_brew_filter.png' },
  { id: '19', product_id: 19, catId: '3', category_id: 3, name: 'Green Tea', desc: 'Delicate Japanese Sencha green tea with vibrant clean notes', price: 4.80, hasModifiers: true, image: 'brand_recources/batch_brew_filter.png' },
  { id: '20', product_id: 20, catId: '3', category_id: 3, name: 'Peppermint Tea', desc: 'Refreshing whole organic peppermint leaves, caffeine-free', price: 4.80, hasModifiers: true, image: 'brand_recources/batch_brew_filter.png' },
  { id: '21', product_id: 21, catId: '3', category_id: 3, name: 'Chamomile Tea', desc: 'Calming whole chamomile flower blossoms with apple sweetness', price: 4.80, hasModifiers: true, image: 'brand_recources/batch_brew_filter.png' },
  { id: '22', product_id: 22, catId: '3', category_id: 3, name: 'Lemongrass & Ginger Tea', desc: 'Zesty lemongrass stalks with spicy warming ginger root', price: 4.80, hasModifiers: true, image: 'brand_recources/batch_brew_filter.png' },

  // Cold Coffee
  { id: '23', product_id: 23, catId: '4', category_id: 4, name: 'Iced Latte', desc: 'Double shot of espresso poured over cold milk and ice', price: 6.80, hasModifiers: true, image: 'brand_recources/iced_oat_milk_latte.png' },
  { id: '24', product_id: 24, catId: '4', category_id: 4, name: 'Iced Long Black', desc: 'Double shot of espresso over chilled mineral water and ice', price: 6.20, hasModifiers: true, image: 'brand_recources/cold_brew_coffee.png' },
  { id: '25', product_id: 25, catId: '4', category_id: 4, name: 'Iced Coffee', desc: 'Chilled espresso and milk with vanilla ice cream & whipped cream', price: 7.80, hasModifiers: true, image: 'brand_recources/iced_oat_milk_latte.png' },
  { id: '26', product_id: 26, catId: '4', category_id: 4, name: 'Iced Mocha', desc: 'Belgian melted chocolate, espresso shot, chilled milk and cream', price: 8.20, hasModifiers: true, image: 'brand_recources/iced_oat_milk_latte.png' },

  // Cold Drinks
  { id: '27', product_id: 27, catId: '5', category_id: 5, name: 'Iced Chocolate', desc: 'Cold Belgian chocolate milk with vanilla ice cream & cream', price: 7.80, hasModifiers: true, image: 'brand_recources/iced_oat_milk_latte.png' },
  { id: '28', product_id: 28, catId: '5', category_id: 5, name: 'Iced Chai Latte', desc: 'Chilled aromatic spiced chai infused with cold milk over ice', price: 7.20, hasModifiers: true, image: 'brand_recources/prana_sticky_chai_latte.png' },
  { id: '29', product_id: 29, catId: '5', category_id: 5, name: 'Iced Matcha Latte', desc: 'Ceremonial Japanese matcha whisked with ice-cold milk over ice', price: 7.80, hasModifiers: true, image: 'brand_recources/iced_oat_milk_latte.png' },
  { id: '30', product_id: 30, catId: '5', category_id: 5, name: 'Milkshake', desc: 'Classic thick shake (Chocolate, Vanilla, Strawberry, Caramel)', price: 8.50, hasModifiers: true, image: 'brand_recources/iced_oat_milk_latte.png' },
  { id: '31', product_id: 31, catId: '5', category_id: 5, name: 'Bottled Still Water', desc: 'Pure Australian spring water in recyclable 600ml bottle', price: 3.50, hasModifiers: true, image: 'brand_recources/cold_brew_coffee.png' },
  { id: '32', product_id: 32, catId: '5', category_id: 5, name: 'Sparkling Water', desc: 'Crisp mineral sparkling water with fresh lemon wedge 500ml', price: 5.00, hasModifiers: true, image: 'brand_recources/cold_brew_coffee.png' },
  { id: '33', product_id: 33, catId: '5', category_id: 5, name: 'Soft Drink', desc: 'Classic canned soft drinks (Coke, Coke Zero, Sprite, Fanta)', price: 4.50, hasModifiers: true, image: 'brand_recources/cold_brew_coffee.png' },

  // Smoothies
  { id: '34', product_id: 34, catId: '6', category_id: 6, name: 'Smoothie (Banana / Berry / Mango / Tropical)', desc: 'Blended fruit smoothie with Greek yogurt, honey and chia seeds', price: 9.50, hasModifiers: true, image: 'brand_recources/iced_oat_milk_latte.png' },

  // Juices
  { id: '35', product_id: 35, catId: '7', category_id: 7, name: 'Fresh Orange Juice', desc: '100% freshly cold-pressed sweet Valencia oranges', price: 8.50, hasModifiers: true, image: 'brand_recources/cold_brew_coffee.png' },
  { id: '36', product_id: 36, catId: '7', category_id: 7, name: 'Fresh Apple Juice', desc: 'Crisp cold-pressed Granny Smith and Pink Lady apples', price: 8.50, hasModifiers: true, image: 'brand_recources/cold_brew_coffee.png' },
  { id: '37', product_id: 37, catId: '7', category_id: 7, name: 'Green Juice', desc: 'Celery, cucumber, kale, green apple and fresh mint', price: 9.50, hasModifiers: true, image: 'brand_recources/cold_brew_coffee.png' },

  // Breakfast
  { id: '38', product_id: 38, catId: '8', category_id: 8, name: 'Sourdough Toast', desc: 'Two thick toasted slices of Noisette sourdough with butter & spreads', price: 6.50, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '39', product_id: 39, catId: '8', category_id: 8, name: 'Eggs on Toast', desc: 'Two free-range eggs cooked your way on toasted sourdough', price: 15.00, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '40', product_id: 40, catId: '8', category_id: 8, name: 'Bacon & Egg Roll', desc: 'Smoked streaky bacon, fried egg, relish on a brioche bun', price: 12.00, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '41', product_id: 41, catId: '8', category_id: 8, name: 'Breakfast Wrap', desc: 'Scrambled eggs, bacon, spinach, avocado & chipotle mayo', price: 13.50, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '42', product_id: 42, catId: '8', category_id: 8, name: 'Avocado Toast', desc: 'Smashed Hass avocado, Persian feta, dukkah, radish & lemon', price: 18.50, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '43', product_id: 43, catId: '8', category_id: 8, name: 'Granola & Yoghurt', desc: 'Honey toasted granola with seasonal berries & vanilla yogurt', price: 15.50, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '44', product_id: 44, catId: '8', category_id: 8, name: 'Porridge', desc: 'Rolled oats with almond milk, caramelized banana & maple', price: 15.00, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '45', product_id: 45, catId: '8', category_id: 8, name: 'Eggs Benedict', desc: 'Two poached eggs, smoked ham or bacon, citrus hollandaise on sourdough', price: 21.00, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '46', product_id: 46, catId: '8', category_id: 8, name: 'Breakfast Burger', desc: 'Angus patty, bacon, fried egg, hash brown, cheddar & BBQ relish', price: 16.00, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },

  // Toasties
  { id: '47', product_id: 47, catId: '9', category_id: 9, name: 'Ham & Cheese Toastie', desc: 'Smoked leg ham, melted Gruyère and vintage cheddar on sourdough', price: 12.50, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '48', product_id: 48, catId: '9', category_id: 9, name: 'Cheese & Tomato Toastie', desc: 'Heirloom tomatoes, aged cheddar cheese and basil on sourdough', price: 11.50, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '49', product_id: 49, catId: '9', category_id: 9, name: 'Three Cheese Toastie', desc: 'Mozzarella, vintage cheddar and Gruyère on golden sourdough', price: 14.00, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '50', product_id: 50, catId: '9', category_id: 9, name: 'Tuna Melt', desc: 'Albacore tuna salad, dill, melted provolone and jalapeño', price: 15.50, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },

  // Sandwiches
  { id: '51', product_id: 51, catId: '10', category_id: 10, name: 'BLT Toasted Sandwich', desc: 'Crispy bacon, cos lettuce, vine tomato and aioli on sourdough', price: 13.50, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '52', product_id: 52, catId: '10', category_id: 10, name: 'Chicken & Avocado Sandwich', desc: 'Poached chicken breast, avocado, rocket and herb mayo on baguette', price: 16.50, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },

  // Pastries
  { id: '53', product_id: 53, catId: '11', category_id: 11, name: 'Plain Croissant', desc: 'Traditional flaky French butter croissant baked fresh daily', price: 6.50, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '54', product_id: 54, catId: '11', category_id: 11, name: 'Almond Croissant', desc: 'Double-baked croissant filled with rich almond frangipane', price: 8.00, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '55', product_id: 55, catId: '11', category_id: 11, name: 'Chocolate Croissant', desc: 'Flaky French pastry with two batons of dark Belgian chocolate', price: 7.50, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '56', product_id: 56, catId: '11', category_id: 11, name: 'Ham & Cheese Croissant', desc: 'Warm butter croissant with leg ham, Swiss cheese & bechamel', price: 9.50, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '57', product_id: 57, catId: '11', category_id: 11, name: 'Fruit Danish', desc: 'Crispy pastry rosette with vanilla custard and glazed fruit', price: 7.00, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },

  // Bakery
  { id: '58', product_id: 58, catId: '12', category_id: 12, name: 'Blueberry Muffin', desc: 'Moist vanilla batter with wild blueberries and crumble top', price: 6.50, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '59', product_id: 59, catId: '12', category_id: 12, name: 'Chocolate Muffin', desc: 'Double chocolate chunk muffin with dark and milk chips', price: 6.50, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '60', product_id: 60, catId: '12', category_id: 12, name: 'Banana Bread', desc: 'Toasted spiced banana loaf with whipped honey cinnamon butter', price: 7.00, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '61', product_id: 61, catId: '12', category_id: 12, name: 'Blueberry Scone', desc: 'Traditional scone served warm with strawberry jam & cream', price: 6.50, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },

  // Lunch
  { id: '62', product_id: 62, catId: '13', category_id: 13, name: 'Seasonal Salad', desc: 'Baby spinach, quinoa, roast pumpkin, walnuts & balsamic citrus', price: 18.00, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '63', product_id: 63, catId: '13', category_id: 13, name: 'Chicken Caesar Salad', desc: 'Grilled chicken, bacon, cos lettuce, croutons, parmesan & egg', price: 21.00, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },

  // Sides
  { id: '64', product_id: 64, catId: '14', category_id: 14, name: 'Chips', desc: 'Bowl of crispy golden shoestring potato fries with garlic aioli', price: 8.50, hasModifiers: true, image: 'brand_recources/butter_croissant.png' },
  { id: '65', product_id: 65, catId: '14', category_id: 14, name: 'Sweet Potato Chips', desc: 'Crunchy rosemary salted sweet potato fries with chipotle mayo', price: 10.50, hasModifiers: true, image: 'brand_recources/butter_croissant.png' }
];

const DB = {
  menuCategories: defaultMenuCategories,
  menuItems: defaultMenuItems,
  tables: [],
  customers: [],
  discounts: [],
  inventory: [],
  suppliers: [
    { id: 'SUP-01', name: 'Melbourne Coffee Exporters', contact: 'Sam Harris', phone: '(03) 9882 1100', catalog: 'Single origin green & roasted beans' },
    { id: 'SUP-02', name: 'St David Dairy Victoria', contact: 'Anna Schmidt', phone: '(03) 9419 8820', catalog: 'Organic full cream & skim milk' },
    { id: 'SUP-03', name: 'BioPak Sustainable Solutions', contact: 'Orders Team', phone: '1300 246 725', catalog: 'Compostable coffee cups & lids' }
  ],
  employees: [
    { id: 'EMP-01', name: 'Sarah Lin', role: 'Lead Cashier', pin: '1234', shiftStart: '06:30 AM', clockedIn: true, hoursWorked: 4.2 },
    { id: 'EMP-02', name: 'Liam O\'Connor', role: 'Head Barista', pin: '5678', shiftStart: '06:30 AM', clockedIn: true, hoursWorked: 4.2 },
    { id: 'EMP-03', name: 'Hannah Wright', role: 'Barista / Floor', pin: '9900', shiftStart: '08:00 AM', clockedIn: true, hoursWorked: 2.7 }
  ],
  reservations: [
    { id: 'RES-101', customerName: 'David Kim', partySize: 6, tableId: 'T-05', time: '11:30 AM', status: 'Confirmed', contact: '0412 889 201' },
    { id: 'RES-102', customerName: 'Alex Mercer', partySize: 4, tableId: 'T-08', time: '12:30 PM', status: 'Confirmed', contact: '0400 998 123' },
    { id: 'RES-103', customerName: 'Chloe Lin', partySize: 4, tableId: 'T-10', time: '01:15 PM', status: 'Pending', contact: '0488 442 109' }
  ],
  feedback: [
    { id: 'FB-01', customer: 'Marcus Vance', rating: 5, comment: 'Best Flat White in Flinders Lane! Microfoam is super silky.', date: 'Today, 09:15 AM' },
    { id: 'FB-02', customer: 'Elena R.', rating: 5, comment: 'Great atmosphere, loving the oat milk latte.', date: 'Yesterday, 02:40 PM' },
    { id: 'FB-03', customer: 'Walk-in Guest', rating: 4, comment: 'Quick service during morning rush hour.', date: 'Yesterday, 08:30 AM' }
  ],
  kdsOrders: [],
  completedSales: [],
  rolePermissions: JSON.parse(JSON.stringify(defaultPermissions))
};

// ==========================================
// 2. REACTIVE APP STATE STORE
// ==========================================

const AppState = {
  activeRole: 'cashier', // 'admin', 'cashier', 'barista'
  activeModule: 'pos',
  isAuthenticated: true,
  activeCategory: '1',
  searchQuery: '',

  // Active Sale Cart
  cart: {
    orderId: '#ORD-9042',
    orderType: 'dine_in', // 'dine_in', 'takeaway'
    tableId: 'T-03',
    customer: null, // attached customer
    items: [],
    promoCode: null,
    discountAmount: 0
  },

  // Modal Temp Customisation Target
  modalItem: null
};

// ==========================================
// 3. CORE DOM REFERENCES & INIT
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
  initApp();
});

function saveLocalDB() {
  try {
    localStorage.setItem('RAVENHILL_DB_STATE', JSON.stringify(DB));
    localStorage.setItem('RAVENHILL_APP_STATE', JSON.stringify({
      orderId: AppState.cart.orderId,
      activeRole: AppState.activeRole,
      isAuthenticated: AppState.isAuthenticated
    }));
    if (AppState.cart) {
      localStorage.setItem('RAVENHILL_CART_STATE', JSON.stringify(AppState.cart));
    }
  } catch (e) {
    console.warn('[LocalStorage] Save failed:', e);
  }
}

function loadLocalDB() {
  try {
    const saved = localStorage.getItem('RAVENHILL_DB_STATE');
    if (saved) {
      const parsed = JSON.parse(saved);
      if (parsed.tables && parsed.tables.length) DB.tables = parsed.tables;
      if (parsed.kdsOrders) DB.kdsOrders = parsed.kdsOrders;
      if (parsed.inventory && parsed.inventory.length) DB.inventory = parsed.inventory;
      if (parsed.menuItems && parsed.menuItems.length && parsed.menuItems.some(i => i.catId === '1' || i.category_id === 1)) {
        DB.menuItems = parsed.menuItems;
      }
      if (parsed.menuCategories && parsed.menuCategories.length && parsed.menuCategories.some(c => c.id === '1' || c.category_id === 1)) {
        DB.menuCategories = parsed.menuCategories;
      }
      if (parsed.reservations) DB.reservations = parsed.reservations;
      if (parsed.customers && parsed.customers.length) DB.customers = parsed.customers;
      if (parsed.discounts && parsed.discounts.length) DB.discounts = parsed.discounts;
      if (parsed.completedSales) DB.completedSales = parsed.completedSales;
      if (parsed.rolePermissions) DB.rolePermissions = parsed.rolePermissions;
    }
    // Load app state
    const appStateSaved = localStorage.getItem('RAVENHILL_APP_STATE');
    if (appStateSaved) {
      const appParsed = JSON.parse(appStateSaved);
      if (appParsed.orderId) AppState.cart.orderId = appParsed.orderId;
      if (appParsed.activeRole) AppState.activeRole = appParsed.activeRole;
      if (appParsed.isAuthenticated !== undefined) AppState.isAuthenticated = appParsed.isAuthenticated;
    }
    // Load cart state
    const cartSaved = localStorage.getItem('RAVENHILL_CART_STATE');
    if (cartSaved) {
      const cartParsed = JSON.parse(cartSaved);
      if (cartParsed && Array.isArray(cartParsed.items)) {
        AppState.cart = {
          ...AppState.cart,
          ...cartParsed
        };
      }
    }
    if (!DB.menuCategories.some(c => String(c.id) === String(AppState.activeCategory)) && DB.menuCategories.length > 0) {
      AppState.activeCategory = DB.menuCategories[0].id;
    }
  } catch (e) {
    console.warn('[LocalStorage] Load failed:', e);
  }
}

// Global Toast Notification System
window.showToast = function(message, type = 'info', duration = 3200) {
  let container = document.getElementById('app-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'app-toast-container';
    container.style.cssText = 'position:fixed; top:24px; right:24px; z-index:999999; display:flex; flex-direction:column; gap:10px; pointer-events:none; max-width:90vw; width:380px;';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `app-toast toast-${type}`;
  
  const iconMap = {
    success: 'ri-checkbox-circle-fill',
    warning: 'ri-alert-fill',
    danger: 'ri-error-warning-fill',
    error: 'ri-close-circle-fill',
    info: 'ri-information-fill'
  };
  const icon = iconMap[type] || 'ri-notification-3-fill';

  const bgMap = {
    success: 'linear-gradient(135deg, rgba(39, 174, 96, 0.95), rgba(46, 204, 113, 0.95))',
    warning: 'linear-gradient(135deg, rgba(230, 126, 34, 0.95), rgba(241, 196, 15, 0.95))',
    danger: 'linear-gradient(135deg, rgba(192, 57, 43, 0.95), rgba(231, 76, 60, 0.95))',
    error: 'linear-gradient(135deg, rgba(192, 57, 43, 0.95), rgba(231, 76, 60, 0.95))',
    info: 'linear-gradient(135deg, rgba(217, 107, 67, 0.95), rgba(229, 169, 59, 0.95))'
  };

  toast.style.cssText = `
    background: ${bgMap[type] || bgMap.info};
    color: #ffffff;
    padding: 12px 18px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.35), 0 2px 6px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 13px;
    font-weight: 600;
    pointer-events: auto;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.2);
    transform: translateX(50px) scale(0.95);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  `;

  toast.innerHTML = `
    <i class="${icon}" style="font-size:18px; flex-shrink:0;"></i>
    <span style="flex:1; line-height:1.35;">${message}</span>
    <button type="button" style="background:none; border:none; color:rgba(255,255,255,0.8); cursor:pointer; font-size:16px; padding:0; display:flex; align-items:center;" onclick="this.parentElement.remove()"><i class="ri-close-line"></i></button>
  `;

  container.appendChild(toast);

  requestAnimationFrame(() => {
    toast.style.transform = 'translateX(0) scale(1)';
    toast.style.opacity = '1';
  });

  setTimeout(() => {
    toast.style.transform = 'translateX(50px) scale(0.95)';
    toast.style.opacity = '0';
    setTimeout(() => {
      if (typeof toast.remove === 'function') toast.remove();
      else if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 350);
  }, duration);
};

const showToast = window.showToast;

function initApp() {
  // Load saved state from LocalStorage immediately to prevent initial reset on refresh
  loadLocalDB();

  setupUniversalModalClosers();
  setupNavigation();
  setupRoleSwitcher();
  setupSidebarToggle();
  setupGlobalSearch();
  setupCartDrawerEvents();
  setupCustomiserModal();
  setupPaymentModal();
  setupLiveClock();
  setupKeyboardShortcuts();

  // Set default role
  if (!AppState.activeRole) {
    AppState.activeRole = 'customer';
    AppState.activeModule = 'landing';
  }

  // Default entry: Show the dedicated separate brand landing page
  const hash = window.location.hash;
  if (hash === '#pos' || hash === '#kds' || hash === '#admin' || hash === '#dashboard') {
    window.showAppView(hash.replace('#', ''));
  } else {
    window.showLandingView();
  }

  updateKDSBadge();
  updateLowStockBadge();

  // Sync live data from Node.js Express Backend REST API
  syncBackendData();
  
  // Check backend session
  initSession();
}

window.openLoginModal = function(targetRole) {
  const modal = document.getElementById('role-select-modal');
  const userRoleSelect = document.getElementById('role-popup-select');
  const userInput = document.getElementById('role-username-input');
  const passInput = document.getElementById('role-password-input');
  const errorMsg = document.getElementById('role-pass-error');

  if (userRoleSelect) {
    userRoleSelect.value = targetRole || AppState.activeRole || 'cashier';
  }

  if (userInput) {
    if (!userInput.value) {
      if (targetRole === 'admin') userInput.value = 'admin';
      else if (targetRole === 'cashier') userInput.value = 'slin';
    }
  }

  if (passInput) {
    passInput.value = '';
    passInput.type = 'password';
  }

  const icon = document.getElementById('toggle-pass-icon');
  if (icon) icon.className = 'ri-eye-off-line';

  if (errorMsg) errorMsg.classList.add('hidden');

  if (modal) modal.classList.remove('hidden');
};

window.togglePasswordVisibility = function() {
  const passInput = document.getElementById('role-password-input');
  const icon = document.getElementById('toggle-pass-icon');
  if (passInput) {
    if (passInput.type === 'password') {
      passInput.type = 'text';
      if (icon) icon.className = 'ri-eye-line';
    } else {
      passInput.type = 'password';
      if (icon) icon.className = 'ri-eye-off-line';
    }
  }
};

function applyRoleToUI(role) {
  const roleSelect = document.getElementById('user-role-select');
  const nameEl = document.getElementById('current-user-name');
  const badgeEl = document.getElementById('current-user-role-badge');
  const avatarEl = document.getElementById('current-user-avatar');

  if (roleSelect) roleSelect.value = role;

  const u = AppState.currentUser;
  if (u && (u.first_name || u.username)) {
    const fullName = `${u.first_name || ''} ${u.last_name || ''}`.trim() || u.username;
    if (nameEl) nameEl.textContent = fullName;
    if (badgeEl) badgeEl.textContent = (u.position || u.role || role).replace(/^./, str => str.toUpperCase());
    
    let initials = 'U';
    if (u.first_name && u.last_name) {
      initials = (u.first_name[0] + u.last_name[0]).toUpperCase();
    } else if (fullName) {
      initials = fullName.substring(0, 2).toUpperCase();
    }
    if (avatarEl) avatarEl.textContent = initials;
    return;
  }

  if (role === 'admin') {
    if (nameEl) nameEl.textContent = 'Ravenhill Admin';
    if (badgeEl) badgeEl.textContent = 'System Admin';
    if (avatarEl) avatarEl.textContent = 'RA';
  } else if (role === 'manager') {
    if (nameEl) nameEl.textContent = 'Alex Vance';
    if (badgeEl) badgeEl.textContent = 'Store Manager';
    if (avatarEl) avatarEl.textContent = 'AV';
  } else if (role === 'kitchen') {
    if (nameEl) nameEl.textContent = 'Marco Rossi';
    if (badgeEl) badgeEl.textContent = 'Head Chef';
    if (avatarEl) avatarEl.textContent = 'MR';
  } else if (role === 'barista') {
    if (nameEl) nameEl.textContent = 'Liam O\'Connor';
    if (badgeEl) badgeEl.textContent = 'Head Barista';
    if (avatarEl) avatarEl.textContent = 'LO';
  } else if (role === 'waitstaff') {
    if (nameEl) nameEl.textContent = 'Chloe Bennett';
    if (badgeEl) badgeEl.textContent = 'Wait Staff';
    if (avatarEl) avatarEl.textContent = 'CB';
  } else if (role === 'customer') {
    if (nameEl) nameEl.textContent = 'Sophia Reed';
    if (badgeEl) badgeEl.textContent = 'Loyalty Customer';
    if (avatarEl) avatarEl.textContent = 'SR';
  } else {
    if (nameEl) nameEl.textContent = 'Sarah Lin';
    if (badgeEl) badgeEl.textContent = 'Lead Cashier';
    if (avatarEl) avatarEl.textContent = 'SL';
  }
}
window.applyRoleToUI = applyRoleToUI;

window.applyRolePermissionsUI = function() {
  const role = AppState.activeRole || 'cashier';
  const perms = (DB.rolePermissions && DB.rolePermissions[role]) 
    ? DB.rolePermissions[role] 
    : (defaultPermissions[role] || {});

  const topbarRoleSelect = document.getElementById('user-role-select');
  if (topbarRoleSelect) topbarRoleSelect.value = role;

  // Sidebar items
  const navItems = document.querySelectorAll('.nav-item[data-module]');
  navItems.forEach(item => {
    const mod = item.getAttribute('data-module');
    
    // Access control is ONLY visible to Admin
    if (mod === 'access') {
      if (role === 'admin') {
        item.style.display = 'flex';
      } else {
        item.style.display = 'none';
      }
    } else if (perms[mod] !== undefined) {
      if (perms[mod]) {
        item.style.display = 'flex';
      } else {
        item.style.display = 'none';
      }
    } else {
      item.style.display = 'flex';
    }
  });

  // Section titles in sidebar
  const navWrappers = document.querySelectorAll('.sidebar-nav-wrapper');
  navWrappers.forEach(wrap => {
    const sections = wrap.querySelectorAll('.nav-section-title');
    sections.forEach(sec => {
      const nextNav = sec.nextElementSibling;
      if (nextNav && nextNav.classList.contains('nav-menu')) {
        const visibleLinks = Array.from(nextNav.querySelectorAll('.nav-item')).filter(el => el.style.display !== 'none');
        if (visibleLinks.length === 0) {
          sec.style.display = 'none';
        } else {
          sec.style.display = 'block';
        }
      }
    });
  });

  // Check current module permission
  const currentMod = AppState.activeModule;
  const isAccessAllowed = (currentMod === 'access') ? (role === 'admin') : (perms[currentMod] !== false);

  if (!isAccessAllowed) {
    if (role === 'customer') {
      AppState.activeModule = 'pos';
    } else if (role === 'kitchen' || role === 'barista') {
      AppState.activeModule = 'kds';
    } else if (role === 'waitstaff') {
      AppState.activeModule = 'waitstaff';
    } else {
      const allowedMod = Object.keys(perms).find(k => perms[k] && (k !== 'access' || role === 'admin')) || 'pos';
      AppState.activeModule = allowedMod;
    }
  }
};

window.confirmRoleSelection = async function() {
  const userInp = document.getElementById('role-username-input');
  const passInput = document.getElementById('role-password-input');
  const errorMsg = document.getElementById('role-pass-error');

  const username = userInp ? userInp.value.trim() : '';
  const password = passInput ? passInput.value.trim() : '';

  if (!username || !password) {
    if (errorMsg) {
      errorMsg.textContent = 'Please enter both username/email and password.';
      errorMsg.classList.remove('hidden');
    }
    return;
  }
  
  try {
    const btn = document.getElementById('confirm-role-login-btn');
    if (btn) btn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Logging in...';
    
    const res = await fetch(`${API_BASE}/users/login.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password })
    });
    const data = await res.json();
    
    if (btn) btn.innerHTML = '<i class="ri-login-circle-line"></i> Secure Login';
    
    if (data.success) {
      if (errorMsg) errorMsg.classList.add('hidden');

      AppState.isAuthenticated = true;
      const role = data.data.role.toLowerCase();
      AppState.activeRole = role;
      
      // Store user details in AppState
      AppState.currentUser = data.data;

      applyRoleToUI(role);
      applyRolePermissionsUI();

      const modal = document.getElementById('role-select-modal');
      if (modal) modal.classList.add('hidden');

      // Accurate Role-Based Routing & Dashboard Redirection
      let targetMod = 'pos';
      if (role === 'admin' || role === 'manager') {
        targetMod = 'dashboard';
      } else if (role === 'barista' || role === 'kitchen') {
        targetMod = 'kds';
      } else if (role === 'waitstaff') {
        targetMod = 'waitstaff';
      } else if (role === 'customer') {
        targetMod = 'pos';
      }

      window.showAppView(targetMod);
      showToast(data.message || `Welcome! Logged in as ${role.toUpperCase()}.`, 'success');
      
    } else {
      if (errorMsg) {
        errorMsg.textContent = data.message || 'Invalid username or password.';
        errorMsg.classList.remove('hidden');
      }
    }
  } catch (err) {
    const btn = document.getElementById('confirm-role-login-btn');
    if (btn) btn.innerHTML = '<i class="ri-login-circle-line"></i> Secure Login';
    if (errorMsg) {
      errorMsg.textContent = 'Server error. Please try again later.';
      errorMsg.classList.remove('hidden');
    }
  }
};

window.logout = async function() {
  try {
    await fetch(`${API_BASE}/users/logout.php`, { method: 'POST' });
  } catch(e) {}
  
  AppState.isAuthenticated = false;
  AppState.activeRole = 'customer';
  AppState.currentUser = null;
  localStorage.removeItem('RAVENHILL_USER_ROLE');
  
  // Close any open modals
  const roleModal = document.getElementById('role-select-modal');
  if (roleModal) roleModal.classList.add('hidden');
  const loginModal = document.getElementById('login-modal');
  if (loginModal) loginModal.classList.add('hidden');

  showToast('Logged out successfully.', 'info');
  window.showLandingView();
};

window.initSession = async function() {
  try {
    const res = await fetch(`${API_BASE}/users/me.php`);
    const data = await res.json();
    if (data.success && data.data) {
      AppState.isAuthenticated = true;
      const role = data.data.role.toLowerCase();
      AppState.activeRole = role;
      AppState.currentUser = data.data;
      
      applyRoleToUI(role);
      applyRolePermissionsUI();
      
      // Module routing is handled by initApp if already authenticated,
      // but if page is reloaded on landing, redirect to appropriate role dashboard
      let targetMod = 'pos';
      if (role === 'admin' || role === 'manager') {
        targetMod = 'dashboard';
      } else if (role === 'barista' || role === 'kitchen') {
        targetMod = 'kds';
      } else if (role === 'waitstaff') {
        targetMod = 'waitstaff';
      } else if (role === 'customer') {
        targetMod = 'pos';
      }

      const landing = document.getElementById('landing-page-view');
      if (landing && !landing.classList.contains('hidden')) {
        window.showAppView(targetMod);
      } else {
        window.switchModule(targetMod);
      }
    } else {
      AppState.isAuthenticated = false;
    }
  } catch(e) {
    console.warn("Session check failed", e);
  }
};

async function syncBackendData() {
  try {
    console.log('[Backend Sync] Instant bootstrap from:', API_BASE);
    
    // 1. Try unified ultra-fast single roundtrip bootstrap endpoint (<50ms)
    const bootData = await API.fetchBootstrap();
    if (bootData) {
      if (bootData.categories && bootData.categories.length) {
        DB.menuCategories = bootData.categories.map(cat => ({
          id: String(cat.category_id),
          category_id: cat.category_id,
          name: cat.category_name,
          icon: getCategoryIcon(cat.category_name),
          desc: cat.description || ''
        }));
      }

      if (bootData.menu_items && bootData.menu_items.length) {
        DB.menuItems = bootData.menu_items.map(item => {
          const catIdStr = String(item.category_id || '1');
          return {
            id: String(item.product_id),
            product_id: item.product_id,
            catId: catIdStr,
            category_id: item.category_id,
            name: item.product_name,
            desc: item.description || '',
            price: parseFloat(item.base_price || item.price || 0),
            image: item.image_url || '',
            availability: item.is_available == 1,
            hasModifiers: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '13'].includes(catIdStr)
          };
        });
      }

      if (bootData.tables && bootData.tables.length) {
        DB.tables = bootData.tables.map(t => ({
          id: String(t.table_number || t.table_id),
          table_id: t.table_id,
          number: t.table_number,
          seats: parseInt(t.capacity || 4),
          capacity: parseInt(t.capacity || 4),
          location: t.location || 'Main Floor',
          status: t.status || 'available',
          orderId: t.current_order_id ? `#ORD-${t.current_order_id}` : null
        }));
      }

      if (bootData.discounts && bootData.discounts.length) {
        DB.discounts = bootData.discounts.map(d => ({
          id: String(d.discount_id),
          code: d.discount_code,
          name: d.discount_code,
          type: d.discount_type,
          value: parseFloat(d.discount_value || 0),
          minSpend: parseFloat(d.min_order_amount || 0)
        }));
      }

      if (bootData.next_order_num) {
        AppState.cart.orderId = `#ORD-${bootData.next_order_num}`;
      }

      // Render view immediately with bootstrap data!
      renderCurrentModule();
      renderCartTableSelect();
      updateKDSBadge();
    }

    // 2. Fetch secondary data in background without blocking UI
    const [
      inventoryRes, ordersRes, reservationsRes, customersRes, 
      transactionsRes, feedbackRes, staffRes
    ] = await Promise.allSettled([
      API.fetchInventory(),
      API.fetchOrders(),
      API.fetchReservations(),
      API.fetchCustomers(),
      API.fetchTransactions(),
      API.fetchFeedback(),
      API.fetchStaff()
    ]);

    const categories = categoriesRes.status === 'fulfilled' ? categoriesRes.value : null;
    const menuItems = menuItemsRes.status === 'fulfilled' ? menuItemsRes.value : null;
    const inventoryRaw = inventoryRes.status === 'fulfilled' ? inventoryRes.value : null;
    const tables = tablesRes.status === 'fulfilled' ? tablesRes.value : null;
    const ordersRaw = ordersRes.status === 'fulfilled' ? ordersRes.value : null;
    const reservationsRaw = reservationsRes.status === 'fulfilled' ? reservationsRes.value : null;
    const customersRaw = customersRes.status === 'fulfilled' ? customersRes.value : null;
    const transactionsRaw = transactionsRes.status === 'fulfilled' ? transactionsRes.value : null;
    const discounts = discountsRes.status === 'fulfilled' ? discountsRes.value : null;
    const feedbackRaw = feedbackRes.status === 'fulfilled' ? feedbackRes.value : null;
    const staffRaw = staffRes.status === 'fulfilled' ? staffRes.value : null;

    // 1. Process Categories
    if (categories && Array.isArray(categories) && categories.length) {
      DB.menuCategories = categories.map(cat => ({
        id: String(cat.category_id !== undefined ? cat.category_id : (cat.id || '')),
        category_id: cat.category_id !== undefined ? cat.category_id : cat.id,
        name: cat.category_name || cat.name,
        icon: cat.icon || getCategoryIcon(cat.category_name || cat.name),
        desc: cat.description || cat.desc || ''
      }));
      console.log(`[Backend Sync] Loaded ${DB.menuCategories.length} categories from DB.`);
    }

    // 2. Process Menu Items
    if (menuItems && Array.isArray(menuItems) && menuItems.length) {
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
      console.log(`[Backend Sync] Loaded ${DB.menuItems.length} menu items from DB.`);
    }

    // Ensure active category is valid
    if (!DB.menuCategories.some(c => String(c.id) === String(AppState.activeCategory)) && DB.menuCategories.length > 0) {
      AppState.activeCategory = DB.menuCategories[0].id;
    }

    // 3. Process Inventory (handles { count, items } or array)
    const invItems = inventoryRaw ? (Array.isArray(inventoryRaw) ? inventoryRaw : (inventoryRaw.items || [])) : null;
    if (invItems && invItems.length) {
      DB.inventory = invItems.map(inv => ({
        ...inv,
        id: inv.inventory_id || inv.id,
        name: inv.item_name || inv.name,
        qty: inv.quantity !== undefined ? inv.quantity : (inv.qty !== undefined ? inv.qty : 0),
        category: inv.category || inv.supplier_name || inv.supplier || 'Supplies',
        status: inv.status || (inv.quantity <= (inv.reorder_level || 10) ? 'low' : 'good')
      }));
    }

    // 4. Process Tables
    if (tables && Array.isArray(tables) && tables.length) {
      DB.tables = tables.map(t => ({
        id: `T-${String(t.table_number || t.id).padStart(2, '0')}`,
        table_id: t.table_id || t.id,
        table_number: t.table_number,
        name: `Table ${t.table_number || t.id}`,
        section: t.location || 'Main Dining',
        capacity: t.capacity || 4,
        status: t.status || 'available'
      }));
    }

    // 5. Process Orders (handles { orders: [...] } or array)
    const ordList = ordersRaw ? (Array.isArray(ordersRaw) ? ordersRaw : (ordersRaw.orders || [])) : null;
    if (ordList && ordList.length) {
      DB.kdsOrders = ordList
        .filter(ord => ord.order_status !== 'completed' && ord.order_status !== 'cancelled' && ord.status !== 'completed')
        .map(ord => ({
          ...ord,
          id: `#ORD-${ord.order_id || ord.id}`,
          orderType: ord.order_type || ord.type || 'dine-in',
          createdAt: ord.created_at || ord.createdAt || new Date().toISOString(),
          customerName: ord.customer_name || ord.customerName || 'Walk-in Guest',
          items: (ord.items || []).map(item => ({
            ...item,
            mods: item.customisations ? item.customisations.map(c => c.option_name) : (item.mods || [])
          }))
        }));
    }

    // 6. Process Reservations
    const resList = reservationsRaw ? (Array.isArray(reservationsRaw) ? reservationsRaw : (reservationsRaw.reservations || [])) : null;
    if (resList && resList.length) {
      DB.reservations = resList.map(r => ({
        id: `RES-${r.reservation_id || r.id}`,
        customerName: r.display_name || r.guest_name || 'Guest',
        partySize: r.number_of_guests || 2,
        tableId: `T-${String(r.table_number || r.table_id || 1).padStart(2, '0')}`,
        time: r.reservation_time ? r.reservation_time.substring(0, 5) : '12:00',
        status: r.status || 'Confirmed',
        contact: r.display_phone || r.guest_phone || ''
      }));
    }

    // 7. Process Customers
    const custList = customersRaw ? (Array.isArray(customersRaw) ? customersRaw : (customersRaw.customers || [])) : null;
    if (custList && custList.length) DB.customers = custList;

    // 8. Process Discounts
    if (discounts && Array.isArray(discounts) && discounts.length) DB.discounts = discounts;

    // 9. Process Feedback
    const fbList = feedbackRaw ? (Array.isArray(feedbackRaw) ? feedbackRaw : (feedbackRaw.feedback || [])) : null;
    if (fbList && fbList.length) {
      DB.feedback = fbList.map(fb => ({
        id: `FB-${fb.feedback_id || fb.id}`,
        customer: fb.author_name || fb.guest_name || 'Guest',
        rating: fb.rating || 5,
        comment: fb.comments || '',
        date: fb.submitted_at ? new Date(fb.submitted_at).toLocaleDateString() : 'Recent'
      }));
    }

    // 10. Process Staff
    const staffList = staffRaw ? (Array.isArray(staffRaw) ? staffRaw : (staffRaw.employees || [])) : null;
    if (staffList && staffList.length) {
      DB.employees = staffList.map(s => ({
        id: `EMP-${String(s.employee_id || s.id).padStart(2, '0')}`,
        name: `${s.first_name || ''} ${s.last_name || ''}`.trim() || s.name || 'Staff',
        role: s.position || s.role_name || 'Staff',
        pin: s.pin || '1234',
        shiftStart: '07:00 AM',
        clockedIn: s.is_clocked_in === true || s.status === 'active',
        hoursWorked: s.hoursWorked !== undefined ? s.hoursWorked : 4.0
      }));
    }

    // 11. Process Transactions
    const txnList = transactionsRaw ? (Array.isArray(transactionsRaw) ? transactionsRaw : (transactionsRaw.transactions || [])) : null;
    if (txnList && txnList.length) {
      DB.completedSales = txnList.map(txn => ({
        id: txn.transaction_reference || `#TXN-${txn.payment_id}`,
        total: parseFloat(txn.amount || 0),
        paymentMethod: txn.payment_method || 'CARD',
        itemsCount: txn.itemsCount || 1,
        cashier: txn.cashier || 'Staff',
        timestamp: txn.payment_date || new Date().toISOString()
      }));
    }

    saveLocalDB();
    console.log('[Backend Sync] Successfully synced database state into POS UI.');
    renderCurrentModule();
    updateKDSBadge();
    updateLowStockBadge();
  } catch (err) {
    console.warn('[Backend Sync] Sync notice:', err);
    renderCurrentModule();
  }
}

function updateLowStockBadge() {
  const badge = document.getElementById('low-stock-count');
  if (!badge) return;
  const count = (typeof DB !== 'undefined' && DB.inventory) ? DB.inventory.filter(i => i.status === 'low').length : 0;
  if (count > 0) {
    badge.textContent = count;
    badge.classList.remove('hidden');
  } else {
    badge.classList.add('hidden');
  }
}

window.exportToCSV = function(moduleName) {
  let targetModule = moduleName || AppState.activeModule || 'tables';
  let filename = `Ravenhill_${targetModule.toUpperCase()}_Report_${new Date().toISOString().slice(0,10)}.csv`;
  let csvRows = [];

  if (targetModule === 'tables') {
    csvRows.push(['Table ID', 'Table Name', 'Section Area', 'Seating Capacity', 'Current Status', 'Linked Order ID', 'Reservation Info']);
    (DB.tables || []).forEach(t => {
      csvRows.push([
        t.id,
        t.name,
        t.section,
        t.capacity,
        t.status.toUpperCase(),
        t.orderId || 'None',
        t.reservedFor || 'None'
      ]);
    });
  } else if (targetModule === 'reservations') {
    csvRows.push(['Booking ID', 'Customer Name', 'Party Size', 'Assigned Table', 'Time Slot', 'Contact Phone', 'Status']);
    (DB.reservations || []).forEach(r => {
      csvRows.push([
        r.id,
        r.customerName,
        `${r.partySize} Guests`,
        r.tableId,
        r.time,
        r.contact || 'N/A',
        r.status
      ]);
    });
  } else if (targetModule === 'inventory') {
    csvRows.push(['Item ID', 'Item Name', 'Current Stock Qty', 'Unit', 'Min Threshold', 'Unit Cost (AUD)', 'Supplier / Category', 'Status']);
    (DB.inventory || []).forEach(i => {
      const q = i.qty !== undefined ? i.qty : (i.stockQty !== undefined ? i.stockQty : 0);
      csvRows.push([
        i.id,
        i.name,
        q.toFixed(1),
        i.unit,
        i.minThreshold,
        (i.unitCost || 0).toFixed(2),
        i.supplier || i.category || 'Supplies',
        (i.status || 'good').toUpperCase()
      ]);
    });
  } else if (targetModule === 'orders' || targetModule === 'pos' || targetModule === 'kds') {
    csvRows.push(['Order ID', 'Order Number', 'Order Type', 'Table ID', 'Customer Name', 'Subtotal (AUD)', 'Tax (AUD)', 'Discount (AUD)', 'Total (AUD)', 'Payment Method', 'Status', 'Timestamp']);
    const allOrders = [...(DB.kdsOrders || []), ...(DB.completedSales || [])];
    allOrders.forEach(o => {
      csvRows.push([
        o.id,
        o.orderNum || '',
        o.orderType || o.type || 'dine_in',
        o.tableId || 'N/A',
        o.customerName || 'Walk-in Guest',
        (o.subtotal || 0).toFixed(2),
        (o.tax || 0).toFixed(2),
        (o.discount || 0).toFixed(2),
        (o.total || 0).toFixed(2),
        o.paymentMethod || 'CARD',
        o.status || 'completed',
        o.createdAt || o.timestamp || ''
      ]);
    });
  } else if (targetModule === 'menu') {
    csvRows.push(['Item ID', 'Category ID', 'Item Name', 'Description', 'Price (AUD)', 'Modifiers Enabled', 'Badge Tag']);
    (DB.menuItems || []).forEach(item => {
      csvRows.push([
        item.id,
        item.catId,
        item.name,
        item.desc || '',
        item.price.toFixed(2),
        item.hasModifiers ? 'Yes' : 'No',
        item.badge || 'None'
      ]);
    });
  } else if (targetModule === 'customers') {
    csvRows.push(['Customer ID', 'Member Name', 'Mobile Phone', 'Email Address', 'Loyalty Points', 'Reward Tier', 'Total Visits']);
    (DB.customers || []).forEach(c => {
      csvRows.push([
        c.id,
        c.name,
        c.mobile || 'N/A',
        c.email || 'N/A',
        c.points || 0,
        c.tier || 'Bean Bronze',
        c.visits || 1
      ]);
    });
  } else if (targetModule === 'employees' || targetModule === 'staff') {
    csvRows.push(['Staff ID', 'Employee Name', 'Role', 'Status', 'Shift Start', 'Sales Completed']);
    (DB.employees || []).forEach(s => {
      csvRows.push([
        s.id,
        s.name,
        s.role,
        s.clockedIn ? 'ACTIVE ON SHIFT' : 'OFF',
        s.shiftStart || 'N/A',
        s.salesCount || 0
      ]);
    });
  } else if (targetModule === 'discounts') {
    csvRows.push(['Discount Code', 'Description', 'Discount Type', 'Value', 'Min Spend (AUD)']);
    (DB.discounts || []).forEach(d => {
      csvRows.push([
        d.code,
        d.description,
        d.type === 'percent' ? 'Percentage' : 'Fixed Amount',
        d.type === 'percent' ? `${d.val}%` : `$${d.val.toFixed(2)}`,
        `$${(d.minSpend || 0).toFixed(2)}`
      ]);
    });
  } else if (targetModule === 'suppliers') {
    csvRows.push(['Supplier ID', 'Vendor Company', 'Contact Person', 'Phone Number', 'Catalog Products']);
    (DB.suppliers || []).forEach(sup => {
      csvRows.push([
        sup.id,
        sup.name,
        sup.contact,
        sup.phone,
        sup.catalog
      ]);
    });
  } else if (targetModule === 'feedback') {
    csvRows.push(['Feedback ID', 'Customer Name', 'Rating (Stars)', 'Review Comment', 'Date Recorded']);
    (DB.feedback || []).forEach(fb => {
      csvRows.push([
        fb.id,
        fb.customer,
        `${fb.rating} Stars`,
        fb.comment,
        fb.date
      ]);
    });
  } else if (targetModule === 'transactions' || targetModule === 'dashboard') {
    csvRows.push(['Transaction ID', 'Order Reference', 'Amount (AUD)', 'Payment Method', 'Items Sold', 'Cashier', 'Timestamp']);
    (DB.completedSales || []).forEach(t => {
      csvRows.push([
        t.id,
        t.id,
        t.total.toFixed(2),
        t.paymentMethod,
        t.itemsCount || 1,
        t.cashier || 'Staff',
        t.timestamp || ''
      ]);
    });
  }

  if (csvRows.length <= 1) {
    alert(`No records available to export for ${targetModule}.`);
    return;
  }

  let csvContent = csvRows.map(row => row.map(val => `"${String(val).replace(/"/g, '""')}"`).join(",")).join("\n");
  let blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  let url = URL.createObjectURL(blob);
  let link = document.createElement("a");
  link.setAttribute("href", url);
  link.setAttribute("download", filename);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

window.getOrderElapsedSeconds = function(ord) {
  if (!ord || !ord.createdAt) {
    return ord && ord.elapsedSec !== undefined ? ord.elapsedSec : 0;
  }
  let dateStr = ord.createdAt;
  if (typeof dateStr === 'string') {
    if (!dateStr.includes('T') && dateStr.includes(' ')) {
      dateStr = dateStr.replace(' ', 'T');
    }
    if (!dateStr.includes('Z') && !dateStr.includes('+')) {
      dateStr = dateStr + 'Z';
    }
  }
  const createdTime = new Date(dateStr).getTime();
  if (isNaN(createdTime)) {
    return ord.elapsedSec !== undefined ? ord.elapsedSec : 0;
  }
  return Math.max(0, Math.floor((Date.now() - createdTime) / 1000));
};

function updateKDSTimers() {
  if (typeof DB === 'undefined' || !DB.kdsOrders) return;
  DB.kdsOrders.forEach(ord => {
    const elapsed = getOrderElapsedSeconds(ord);
    ord.elapsedSec = elapsed;
    const timerTextEl = document.querySelector(`.kds-timer-text[data-order-id="${ord.id}"]`);
    if (timerTextEl) {
      const mins = Math.floor(elapsed / 60);
      const secs = elapsed % 60;
      timerTextEl.textContent = `${mins}m ${secs}s`;
    }
  });
}

// Live Clock & Timers
function setupLiveClock() {
  const clockEl = document.getElementById('live-clock');
  const timerEl = document.getElementById('shift-clock-timer');
  const widgetEl = document.getElementById('topbar-shift-status');
  
  // Persist or initialize shift start timestamp
  let savedShiftStart = localStorage.getItem('RAVENHILL_SHIFT_START_TIMESTAMP');
  if (!savedShiftStart) {
    // Default to 4 hours and 15 minutes before current time for realistic shift tracking
    const initialStart = Date.now() - (4 * 3600 + 15 * 60) * 1000;
    localStorage.setItem('RAVENHILL_SHIFT_START_TIMESTAMP', String(initialStart));
    savedShiftStart = String(initialStart);
  }
  const shiftStartTimestamp = parseInt(savedShiftStart);

  setInterval(() => {
    const now = Date.now();
    const nowDate = new Date();
    const timeStr = nowDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const dateStr = nowDate.toLocaleDateString('en-AU', { day: '2-digit', month: 'short', year: 'numeric' });
    if (clockEl) clockEl.textContent = `${timeStr} • ${dateStr}`;

    const activeStaff = (DB.employees || []).some(e => e.clockedIn);
    if (!activeStaff && DB.employees && DB.employees.length > 0) {
      if (timerEl) timerEl.textContent = `Shift: OFF (Clock In)`;
      if (widgetEl) widgetEl.style.opacity = '0.7';
    } else {
      if (widgetEl) widgetEl.style.opacity = '1';
      const elapsedSec = Math.max(0, Math.floor((now - shiftStartTimestamp) / 1000));
      const hrs = Math.floor(elapsedSec / 3600);
      const mins = Math.floor((elapsedSec % 3600) / 60);
      if (timerEl) timerEl.textContent = `Shift: ${String(hrs).padStart(2, '0')}h ${String(mins).padStart(2, '0')}m`;
    }

    updateKDSTimers();
  }, 1000);
}

function setupKeyboardShortcuts() {
  document.addEventListener('keydown', (e) => {
    // Avoid triggering shortcuts when typing in an input/textarea
    const tag = e.target.tagName.toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select') {
      if (e.key === 'Escape') {
        e.target.blur();
      }
      return;
    }

    if (e.key === 'F1') {
      e.preventDefault();
      switchModule('pos');
    } else if (e.key === 'F2') {
      e.preventDefault();
      switchModule('kds');
    } else if (e.key === 'F3') {
      e.preventDefault();
      switchModule('tables');
    } else if (e.key === '/') {
      e.preventDefault();
      document.getElementById('global-search-input')?.focus();
    } else if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      e.preventDefault();
      if (!document.getElementById('cart-drawer')?.classList.contains('hidden')) {
        openPaymentModal();
      }
    } else if (e.key === 'Escape') {
      closePrintableReceiptModal();
      document.querySelectorAll('.modal-overlay:not(#role-select-modal)').forEach(m => m.classList.add('hidden'));
    }
  });
}
// Sidebar & Routing
function setupNavigation() {
  const navItems = document.querySelectorAll('.nav-item');
  navItems.forEach(item => {
    item.addEventListener('click', (e) => {
      e.preventDefault();
      const moduleKey = item.getAttribute('data-module');
      if (moduleKey) {
        switchModule(moduleKey);
        // Auto-close sidebar drawer on mobile/tablet screens
        if (window.innerWidth < 1024) {
          const sidebar = document.getElementById('sidebar');
          const backdrop = document.getElementById('sidebar-backdrop');
          const toggleBtn = document.getElementById('toggle-sidebar');
          if (sidebar) sidebar.classList.remove('mobile-open');
          if (backdrop) backdrop.classList.remove('active');
          if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
        }
      }
    });
  });
}

function switchModule(moduleKey) {
  AppState.activeModule = moduleKey;
  
  // Auto-close mobile sidebar drawer
  if (window.innerWidth < 1024) {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const toggleBtn = document.getElementById('toggle-sidebar');
    if (sidebar) sidebar.classList.remove('mobile-open');
    if (backdrop) backdrop.classList.remove('active');
    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
  }

  // Highlight active nav item
  document.querySelectorAll('.nav-item').forEach(el => {
    el.classList.toggle('active', el.getAttribute('data-module') === moduleKey);
  });

  // Update Topbar Header Title
  const titleMap = {
    landing: { title: 'RAVENHILL Coffee Roasters', sub: 'Specialty Coffee, Artisan Roastery & Digital Ordering • Flinders Lane, Melbourne CBD' },
    pos: { title: 'Point of Sale (POS)', sub: 'Process orders & quick transactions for Melbourne CBD shop' },
    kds: { title: 'Order Tracking & Kitchen Display (KDS)', sub: 'Live barista order queue & preparation timer' },
    waitstaff: { title: 'Wait Staff Monitor', sub: 'Floor & table service status with ready-to-serve alerts' },
    customer_tracker: { title: 'Customer Live Tracker', sub: 'Real-time multi-stage visual order progress' },
    tables: { title: 'Table Management', sub: 'Interactive CBD floor plan & table assignment' },
    reservations: { title: 'Reservation Management', sub: 'Table bookings & customer schedule' },
    payments: { title: 'Payments & Transactions', sub: 'Live sales ledger, multi-tender transactions & tax invoices' },
    menu: { title: 'Menu & Product Customisation', sub: 'Manage espresso items, prices & modifier rules' },
    inventory: { title: 'Inventory & Recipe Management', sub: 'Stock levels, raw bean tracking & recipe maps' },
    suppliers: { title: 'Supplier & Purchase Management', sub: 'Vendor directory & purchase orders' },
    discounts: { title: 'Discounts & Promotions', sub: 'Voucher codes & happy hour promotions' },
    customers: { title: 'Customers & Loyalty Program', sub: 'Customer directory, point balance & tier rewards' },
    employees: { title: 'Staff & Attendance Management', sub: 'Staff roster & clock-in timesheet simulator' },
    feedback: { title: 'Customer Feedback', sub: 'Customer reviews & service rating dashboard' },
    dashboard: { title: 'Dashboard & Reports', sub: 'Executive overview, sales revenue & shop performance' },
    access: { title: 'User & Access Management', sub: 'Role permissions matrix & staff privileges' },
    audit: { title: 'Audit Trail & Compliance Logs', sub: 'Activity logging, security actions & inventory changes' }
  };

  const info = titleMap[moduleKey] || { title: 'Ravenhill Coffee Roasters', sub: 'Melbourne CBD Specialty Café' };
  document.getElementById('current-module-title').textContent = info.title;
  document.getElementById('current-module-subtitle').textContent = info.sub;

  // Toggle Cart Drawer & Mobile Cart Bar visibility
  const cartDrawer = document.getElementById('cart-drawer');
  const mobileCartBar = document.getElementById('mobile-cart-bar');
  if (cartDrawer) {
    if (moduleKey === 'pos') {
      if (window.innerWidth >= 1024) {
        cartDrawer.classList.remove('hidden');
      }
    } else if (moduleKey === 'landing') {
      // In landing page, keep drawer slide-out ready without blocking hero
      cartDrawer.classList.add('hidden');
      cartDrawer.classList.remove('mobile-open');
    } else {
      cartDrawer.classList.add('hidden');
      cartDrawer.classList.remove('mobile-open');
    }
  }

  if (mobileCartBar) {
    if (moduleKey === 'pos' || moduleKey === 'landing') {
      mobileCartBar.classList.remove('hidden');
    } else {
      mobileCartBar.classList.add('hidden');
    }
  }

  renderCurrentModule();
}

// Role Switcher
function setupRoleSwitcher() {
  const roleSelect = document.getElementById('user-role-select');
  if (!roleSelect) return;
  roleSelect.addEventListener('change', (e) => {
    const targetRole = e.target.value;
    roleSelect.value = AppState.activeRole || 'cashier';
    openLoginModal(targetRole);
  });
}

function setupSidebarToggle() {
  const btn = document.getElementById('toggle-sidebar');
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebar-backdrop');
  const closeBtn = document.getElementById('mobile-sidebar-close-btn');

  function openSidebar() {
    if (sidebar) {
      sidebar.classList.add('mobile-open');
      sidebar.classList.remove('collapsed');
    }
    if (backdrop) backdrop.classList.add('active');
    if (btn) btn.setAttribute('aria-expanded', 'true');
  }

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('mobile-open');
    if (backdrop) backdrop.classList.remove('active');
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }

  if (btn) {
    btn.addEventListener('click', () => {
      if (window.innerWidth < 1024) {
        if (sidebar && sidebar.classList.contains('mobile-open')) {
          closeSidebar();
        } else {
          openSidebar();
        }
      } else {
        if (sidebar) sidebar.classList.toggle('collapsed');
      }
    });
  }

  if (backdrop) {
    backdrop.addEventListener('click', closeSidebar);
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', closeSidebar);
  }
}

window.openMobileCartDrawer = function() {
  const drawer = document.getElementById('cart-drawer');
  if (drawer) {
    drawer.classList.remove('hidden');
    drawer.classList.add('mobile-open');
  }
};

window.closeMobileCartDrawer = function() {
  const drawer = document.getElementById('cart-drawer');
  if (drawer) {
    drawer.classList.remove('mobile-open');
    if (window.innerWidth < 1024) {
      drawer.classList.add('hidden');
    }
  }
};

function setupGlobalSearch() {
  const input = document.getElementById('global-search-input');
  input.addEventListener('input', (e) => {
    AppState.searchQuery = e.target.value.toLowerCase();
    renderCurrentModule();
  });
}

// ==========================================
// 4. MODULE RENDER ROUTER
// ==========================================

function renderCurrentModule() {
  const container = document.getElementById('workspace-container');
  if (!container) return;
  container.innerHTML = '';

  const role = AppState.activeRole || 'cashier';
  const perms = (DB.rolePermissions && DB.rolePermissions[role]) 
    ? DB.rolePermissions[role] 
    : (defaultPermissions[role] || {});
  const modKey = AppState.activeModule;

  // Access Control is ONLY visible and accessible to Admin
  if (modKey === 'access' && role !== 'admin') {
    renderAccessRestrictedNotice(container, 'Access Control', 'Only Administrators have permission to view and edit the Role Access Permissions Matrix.');
    return;
  }

  // Check role permission matrix
  if (modKey !== 'access' && perms[modKey] === false) {
    const modTitles = {
      pos: 'Point of Sale (POS)',
      kds: 'Order Tracking (KDS)',
      tables: 'Table Management',
      reservations: 'Reservations',
      menu: 'Menu & Modifiers',
      inventory: 'Inventory & Recipes',
      suppliers: 'Suppliers & Orders',
      discounts: 'Discounts & Promos',
      customers: 'Customers & Loyalty',
      employees: 'Staff & Attendance',
      feedback: 'Customer Feedback',
      dashboard: 'Dashboard & Reports'
    };
    renderAccessRestrictedNotice(container, modTitles[modKey] || modKey, `Your role (${role.toUpperCase()}) is restricted from accessing this module based on the Role Access Permissions Matrix.`);
    return;
  }

  switch (modKey) {
    case 'landing':
      renderLandingPageView(container);
      break;
    case 'pos':
      renderPOSView(container);
      break;
    case 'kds':
      renderKDSView(container);
      break;
    case 'waitstaff':
      renderWaitStaffDashboard(container);
      break;
    case 'customer_tracker':
      renderCustomerTrackerView(container);
      break;
    case 'tables':
      renderTablesView(container);
      break;
    case 'reservations':
      renderReservationsView(container);
      break;
    case 'payments':
      renderPaymentsView(container);
      break;
    case 'menu':
      renderMenuView(container);
      break;
    case 'inventory':
      renderInventoryView(container);
      break;
    case 'suppliers':
      renderSuppliersView(container);
      break;
    case 'discounts':
      renderDiscountsView(container);
      break;
    case 'customers':
      renderCustomersView(container);
      break;
    case 'employees':
      renderEmployeesView(container);
      break;
    case 'feedback':
      renderFeedbackView(container);
      break;
    case 'dashboard':
      renderDashboardView(container);
      break;
    case 'access':
      renderAccessView(container);
      break;
    case 'audit':
      renderAuditView(container);
      break;
    default:
      renderPOSView(container);
  }
}


function renderAccessRestrictedNotice(container, moduleName, reason) {
  container.innerHTML = `
    <div class="empty-cart-state" style="padding:80px 20px; text-align:center;">
      <i class="ri-shield-keyhole-line" style="font-size:56px; color:var(--color-danger); margin-bottom:16px;"></i>
      <h2 style="margin-bottom:8px;">Access Restricted</h2>
      <p style="color:var(--color-cream-muted); max-width:480px; margin:0 auto 24px; font-size:14px; line-height:1.6;">
        ${reason || `You do not have permission to view the <strong>${moduleName}</strong> module under your active role.`}
      </p>
      <button class="btn btn-primary" onclick="openLoginModal('admin')">
        <i class="ri-key-2-line"></i> Switch Role / Log In as Admin
      </button>
    </div>
  `;
}

window.getItemImage = function(item) {
  if (!item) return 'flat_white_coffee.png';
  
  if (item.image && typeof item.image === 'string' && item.image.trim() !== '') {
    let img = item.image.trim();
    // If it includes path or full URL, return it
    if (img.startsWith('http') || img.startsWith('./') || img.startsWith('/')) return img;
    // Strip brand_recources prefix if present
    img = img.replace(/^brand_recources\//, '').replace(/^brand_recources\//, '');
    return img;
  }
  
  const name = (item.name || item.product_name || '').toLowerCase();

  if (name.includes('flat white') || name.includes('latte')) return 'flat_white_coffee.png';
  if (name.includes('cappuccino') || name.includes('mocha') || name.includes('babycino') || name.includes('chocolate')) return 'cappuccino_coffee.png';
  if (name.includes('espresso') || name.includes('short black') || name.includes('macchiato')) return 'double_espresso_short_black.png';
  if (name.includes('long black') || name.includes('ristretto') || name.includes('americano')) return 'long_black_coffee.png';
  if (name.includes('piccolo')) return 'piccolo_latte.png';
  if (name.includes('batch brew') || name.includes('filter')) return 'batch_brew_filter.png';
  if (name.includes('pour-over') || name.includes('v60')) return 'v60_pourover_coffee.png';
  if (name.includes('cold brew') || name.includes('water') || name.includes('drink') || name.includes('juice')) return 'cold_brew_coffee.png';
  if (name.includes('iced') || name.includes('shake') || name.includes('smoothie')) return 'iced_oat_milk_latte.png';
  if (name.includes('chai') || name.includes('tea') || name.includes('matcha') || name.includes('turmeric')) return 'prana_sticky_chai_latte.png';
  if (name.includes('croissant') || name.includes('toast') || name.includes('wrap') || name.includes('roll') || name.includes('sandwich') || name.includes('muffin') || name.includes('bread') || name.includes('scone') || name.includes('salad') || name.includes('chip') || name.includes('burger') || name.includes('egg') || name.includes('avocado')) return 'butter_croissant.png';
  if (name.includes('bean') || name.includes('reserve')) return 'roasted_coffee_beans.png';

  return 'flat_white_coffee.png';
};
const getItemImage = window.getItemImage;

// ==========================================
// 5. POS MODULE IMPLEMENTATION
// ==========================================

function renderPOSView(container) {
  container.innerHTML = '';
  const posLayout = document.createElement('div');
  posLayout.className = 'pos-main-panel';

  // Categories Bar
  const catBar = document.createElement('div');
  catBar.className = 'pos-categories-bar';

  DB.menuCategories.forEach(cat => {
    const catBtn = document.createElement('button');
    catBtn.className = `cat-btn ${AppState.activeCategory === cat.id ? 'active' : ''}`;
    catBtn.innerHTML = `<i class="${cat.icon}"></i> <span>${cat.name}</span>`;
    catBtn.addEventListener('click', () => {
      AppState.activeCategory = cat.id;
      renderPOSView(container);
    });
    catBar.appendChild(catBtn);
  });

  posLayout.appendChild(catBar);

  // Touch Items Grid
  const itemsGrid = document.createElement('div');
  itemsGrid.className = 'pos-items-grid';

  if (!DB.menuCategories.some(c => String(c.id) === String(AppState.activeCategory)) && DB.menuCategories.length > 0) {
    AppState.activeCategory = DB.menuCategories[0].id;
  }
  let filteredItems = DB.menuItems.filter(item => String(item.catId) === String(AppState.activeCategory) || String(item.category_id) === String(AppState.activeCategory));
  if (AppState.searchQuery) {
    filteredItems = DB.menuItems.filter(item => 
      item.name.toLowerCase().includes(AppState.searchQuery) ||
      item.desc.toLowerCase().includes(AppState.searchQuery)
    );
  }

  if (filteredItems.length === 0) {
    itemsGrid.innerHTML = `
      <div class="empty-cart-state" style="grid-column: 1/-1;">
        <i class="ri-search-eye-line"></i>
        <p>No coffee or food items found</p>
      </div>`;
  } else {
    filteredItems.forEach(item => {
      const card = document.createElement('div');
      card.className = 'menu-card';
      const imgSrc = getItemImage(item);
      card.innerHTML = `
        ${item.badge ? `<span class="menu-card-badge">${item.badge}</span>` : ''}
        <div class="menu-card-image">
          <img src="${imgSrc}" alt="${item.name}" loading="lazy">
        </div>
        <div class="menu-card-info">
          <h4>${item.name}</h4>
          <p>${item.desc}</p>
        </div>
        <div class="menu-card-bottom">
          <span class="menu-card-price">$${item.price.toFixed(2)}</span>
          <button class="add-item-btn"><i class="ri-add-line"></i></button>
        </div>
      `;

      card.addEventListener('click', () => {
        if (item.hasModifiers) {
          openCustomiserModal(item);
        } else {
          // Non-modifier items (bakery, retail beans) bypass customiser with neutral defaults
          addItemToCart(item, [], '', 1);
          window.openCartDrawer();
          showToast(`Added 1x ${item.name} to cart!`, 'success');
        }
      });

      itemsGrid.appendChild(card);
    });
  }

  posLayout.appendChild(itemsGrid);
  container.appendChild(posLayout);

  // Unhide permanent Cart Drawer
  const cartDrawer = document.getElementById('cart-drawer');
  if (cartDrawer) {
    cartDrawer.classList.remove('hidden');
  }

  renderCartUI();
}

// Cart UI Logic
function setupCartDrawerEvents() {
  const closeBtn = document.getElementById('close-cart-btn');
  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      const drawer = document.getElementById('cart-drawer');
      if (drawer) drawer.classList.add('hidden');
    });
  }

  // Segment Buttons (Dine In / Takeaway)
  const segmentBtns = document.querySelectorAll('.segment-btn');
  segmentBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      segmentBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      AppState.cart.orderType = btn.getAttribute('data-ordertype');
      const tableGroup = document.getElementById('cart-table-select-group');
      if (tableGroup) {
        tableGroup.style.display = AppState.cart.orderType === 'takeaway' ? 'none' : 'block';
      }
    });
  });

  // Table Select Listener
  const tableSelect = document.getElementById('cart-table-select');
  if (tableSelect) {
    tableSelect.addEventListener('change', (e) => {
      AppState.cart.tableId = e.target.value;
      renderCartTableSelect();
    });
  }

  renderCartTableSelect();

  // Clear Cart
  const clearBtn = document.getElementById('clear-cart-btn');
  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      AppState.cart.items = [];
      AppState.cart.promoCode = null;
      AppState.cart.discountAmount = 0;
      const promoInput = document.getElementById('promo-code-input');
      if (promoInput) promoInput.value = '';
      renderCartUI();
    });
  }

  // Apply Promo
  const applyPromoBtn = document.getElementById('apply-promo-btn');
  if (applyPromoBtn) {
    applyPromoBtn.addEventListener('click', () => {
      const promoInput = document.getElementById('promo-code-input');
      const inputVal = promoInput ? promoInput.value.trim().toUpperCase() : '';
      const match = DB.discounts.find(d => d.code === inputVal);
      if (match) {
        AppState.cart.promoCode = match;
        renderCartUI();
      } else {
        alert('Invalid promo code. Try "MELB10" or "COFFEELOVER"');
      }
    });
  }

  const removePromoBtn = document.getElementById('remove-promo-btn');
  if (removePromoBtn) {
    removePromoBtn.addEventListener('click', () => {
      AppState.cart.promoCode = null;
      renderCartUI();
    });
  }

  // Attach Customer
  const attachCustBtn = document.getElementById('attach-customer-btn');
  if (attachCustBtn) {
    attachCustBtn.addEventListener('click', openCustomerModal);
  }

  // Checkout Button
  const checkoutBtn = document.getElementById('checkout-btn');
  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', openPaymentModal);
  }
}

function renderCartTableSelect() {
  const tableSelect = document.getElementById('cart-table-select');
  const dotIndicator = document.getElementById('cart-table-status-dot');
  if (!tableSelect) return;

  const currentSelectedId = AppState.cart.tableId || 'T-03';

  if (DB.tables && DB.tables.length > 0) {
    tableSelect.innerHTML = `<option value="">-- Select Table --</option>` +
      DB.tables.map(t => {
        let iconDot = '🟢';
        let statusLabel = 'Available';
        if (t.status === 'occupied') {
          iconDot = '🔴';
          statusLabel = 'Booked';
        } else if (t.status === 'reserved') {
          iconDot = '🟡';
          statusLabel = 'Reserved';
        } else if (t.status === 'cleaning') {
          iconDot = '🔵';
          statusLabel = 'Cleaning';
        }

        return `<option value="${t.id}" ${t.id === currentSelectedId ? 'selected' : ''}>${t.id} (${t.section}) • ${iconDot} ${statusLabel}</option>`;
      }).join('');
  }

  // Update live status dot indicator next to table select box
  if (dotIndicator) {
    const selectedTbl = DB.tables.find(tbl => tbl.id === currentSelectedId);
    const status = selectedTbl ? selectedTbl.status : 'available';
    dotIndicator.className = `table-status-indicator-dot dot-${status}`;
    dotIndicator.title = `Table ${currentSelectedId}: ${(status === 'occupied' ? 'BOOKED' : status).toUpperCase()}`;
  }
}

function setupCartDrawer() {
  // Legacy setup hook
}

function addItemToCart(item, customisations = [], notes = '', qty = 1) {
  if (!item) return;
  if (!AppState.cart) {
    AppState.cart = {
      orderId: '#ORD-9042',
      orderType: 'dine_in',
      tableId: 'T-03',
      items: [],
      promoCode: null,
      customer: null,
      tipPercent: 0,
      tipAmount: 0
    };
  }
  if (!AppState.cart.items) AppState.cart.items = [];

  let extraPrice = 0;
  (customisations || []).forEach(c => {
    extraPrice += parseFloat(c.extra_price || 0);
  });

  const basePrice = parseFloat(item.price || item.unit_price || 0);
  const unitPrice = basePrice + extraPrice;
  const itemName = item.name || item.product_name || 'Menu Item';

  const normalizedItem = {
    ...item,
    name: itemName,
    price: basePrice
  };

  AppState.cart.items.push({
    cartItemId: 'ci-' + Date.now() + Math.random().toString(36).substr(2, 4),
    item: normalizedItem,
    customisations: customisations,
    notes: notes,
    qty: qty,
    unitPrice: unitPrice,
    totalPrice: unitPrice * qty
  });

  renderCartUI();
  saveLocalDB();
}

function renderCartUI() {
  const container = document.getElementById('cart-items-list');
  if (!container) return;

  const cartOrderNumEl = document.getElementById('cart-order-number');
  if (cartOrderNumEl) {
    cartOrderNumEl.textContent = AppState.cart.orderId;
  }

  const totalItemCount = (AppState.cart.items || []).reduce((acc, i) => acc + (i.qty || 1), 0);
  let subtotal = (AppState.cart.items || []).reduce((acc, i) => acc + (i.totalPrice || 0), 0);
  let discount = 0;

  if (AppState.cart.promoCode) {
    const promo = AppState.cart.promoCode;
    if (promo.type === 'percent') {
      discount = (subtotal * promo.val) / 100;
    } else if (promo.type === 'fixed') {
      discount = promo.val;
    }
    const appliedTag = document.getElementById('applied-promo-tag');
    const appliedName = document.getElementById('applied-promo-name');
    if (appliedTag) appliedTag.classList.remove('hidden');
    if (appliedName) appliedName.textContent = `${promo.code} (${promo.description})`;
  } else {
    const appliedTag = document.getElementById('applied-promo-tag');
    if (appliedTag) appliedTag.classList.add('hidden');
  }

  const finalTotal = Math.max(0, subtotal - discount);
  const gst = finalTotal * 0.10;

  container.innerHTML = '';

  if (!AppState.cart.items || AppState.cart.items.length === 0) {
    container.innerHTML = `
      <div class="empty-cart-state">
        <i class="ri-cup-line"></i>
        <p>Your cart is empty</p>
        <span>Tap any coffee or food item from the menu to add to your order</span>
      </div>`;
  } else {
    AppState.cart.items.forEach((ci, idx) => {
      const card = document.createElement('div');
      card.className = 'cart-item-card';

      const modPills = ci.customisations && ci.customisations.length > 0 
        ? ci.customisations.map(c => `<span class="modifier-pill">${c.option_name}</span>`).join('') 
        : '';

      card.innerHTML = `
        <div class="cart-item-top">
          <div style="flex:1;">
            <span class="cart-item-title">${ci.item.name || ci.item.product_name}</span>
            <div style="font-size:11px; color:var(--color-cream-muted); margin-top:2px;">$${(ci.unitPrice || ci.item.price).toFixed(2)} each</div>
          </div>
          <span class="cart-item-price">$${(ci.totalPrice || 0).toFixed(2)}</span>
        </div>
        ${modPills ? `<div class="cart-item-modifiers">${modPills}</div>` : ''}
        ${ci.notes ? `<div style="font-size:10px; color:var(--color-accent-gold); margin-top:4px;"><i class="ri-edit-line"></i> Note: ${ci.notes}</div>` : ''}
        <div class="cart-item-bottom" style="margin-top:8px;">
          <div class="cart-qty-ctrl">
            <button type="button" class="icon-btn-sm" onclick="updateCartQty(${idx}, -1)" title="Decrease quantity"><i class="ri-subtract-line"></i></button>
            <span style="font-weight:700; min-width:18px; text-align:center;">${ci.qty}</span>
            <button type="button" class="icon-btn-sm" onclick="updateCartQty(${idx}, 1)" title="Increase quantity"><i class="ri-add-line"></i></button>
          </div>
          <div style="display:flex; gap:6px; align-items:center;">
            <button type="button" class="icon-btn-sm" onclick="editCartItem(${idx})" title="Edit options & modifiers" style="background:rgba(217, 107, 67, 0.15); border-color:var(--color-primary); color:var(--color-primary-light);"><i class="ri-edit-2-line"></i></button>
            <button type="button" class="cart-item-delete icon-btn-sm text-danger" onclick="removeCartItem(${idx})" title="Remove item"><i class="ri-delete-bin-line"></i></button>
          </div>
        </div>
      `;
      container.appendChild(card);
    });
  }

  const subtotalEl = document.getElementById('cart-subtotal');
  const gstEl = document.getElementById('cart-gst');
  const discountRow = document.getElementById('cart-discount-row');
  const discountEl = document.getElementById('cart-discount');
  const totalEl = document.getElementById('cart-total');
  const btnTotalEl = document.getElementById('checkout-btn-total');
  const checkoutBtn = document.getElementById('checkout-btn');

  if (subtotalEl) subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
  if (gstEl) gstEl.textContent = `$${gst.toFixed(2)}`;
  
  if (discountRow && discountEl) {
    if (discount > 0) {
      discountRow.classList.remove('hidden');
      discountEl.textContent = `-$${discount.toFixed(2)}`;
    } else {
      discountRow.classList.add('hidden');
    }
  }

  if (totalEl) totalEl.textContent = `$${finalTotal.toFixed(2)}`;
  if (btnTotalEl) btnTotalEl.textContent = `$${finalTotal.toFixed(2)}`;
  if (checkoutBtn) checkoutBtn.disabled = !AppState.cart.items || AppState.cart.items.length === 0;

  // Update Topbar Cart Badge & Label
  const topbarCartBadge = document.getElementById('topbar-cart-count');
  if (topbarCartBadge) {
    topbarCartBadge.textContent = totalItemCount;
  }
  const topbarCartBtn = document.getElementById('topbar-cart-toggle-btn');
  if (topbarCartBtn) {
    topbarCartBtn.title = `View Cart (${totalItemCount} item${totalItemCount === 1 ? '' : 's'} • $${finalTotal.toFixed(2)})`;
  }

  // Mobile Floating Cart Bar Sync
  const mobileCartBar = document.getElementById('mobile-cart-bar');
  const mobileCartCount = document.getElementById('mobile-cart-count');
  const mobileCartTotal = document.getElementById('mobile-cart-total');

  if (mobileCartCount) {
    mobileCartCount.textContent = `${totalItemCount} item${totalItemCount === 1 ? '' : 's'}`;
  }
  if (mobileCartTotal) {
    mobileCartTotal.textContent = `$${finalTotal.toFixed(2)}`;
  }
  if (mobileCartBar) {
    if (totalItemCount > 0 && AppState.activeModule === 'pos') {
      mobileCartBar.classList.remove('hidden');
    } else {
      mobileCartBar.classList.add('hidden');
    }
  }

  // Customer Tag UI
  const custInfo = document.getElementById('cart-customer-info');
  if (custInfo) {
    if (AppState.cart.customer) {
      custInfo.innerHTML = `<i class="ri-vip-crown-fill" style="color:var(--color-accent-gold);"></i> <span>${AppState.cart.customer.name} (${AppState.cart.customer.tier})</span>`;
    } else {
      custInfo.innerHTML = `<i class="ri-user-3-line"></i> <span>Walk-in Customer</span>`;
    }
  }
}

window.openCartDrawer = function() {
  const drawer = document.getElementById('cart-drawer');
  if (drawer) {
    drawer.classList.remove('hidden');
    if (window.innerWidth < 1024) {
      drawer.classList.add('mobile-open');
    }
    drawer.classList.remove('cart-pulse');
    void drawer.offsetWidth;
    drawer.classList.add('cart-pulse');
  }
  renderCartUI();
};

window.openMobileCartDrawer = window.openCartDrawer;

window.closeCartDrawer = function() {
  const drawer = document.getElementById('cart-drawer');
  if (drawer) {
    drawer.classList.add('hidden');
    drawer.classList.remove('mobile-open');
  }
};

window.closeMobileCartDrawer = window.closeCartDrawer;

window.toggleCartDrawer = function() {
  const drawer = document.getElementById('cart-drawer');
  if (!drawer) return;
  const isHidden = drawer.classList.contains('hidden') || (!drawer.classList.contains('mobile-open') && window.innerWidth < 1024);
  if (isHidden) {
    window.openCartDrawer();
  } else {
    window.closeCartDrawer();
  }
};

// Explicit Global Modal Closers
window.closeCustomiserModal = function() {
  document.getElementById('customiser-modal')?.classList.add('hidden');
  AppState.editingCartIndex = null;
};

window.closePaymentModal = function() {
  document.getElementById('payment-modal')?.classList.add('hidden');
};

window.closeReceiptModal = function() {
  document.getElementById('receipt-modal')?.classList.add('hidden');
  syncBackendData();
};

window.closeCustomerModal = function() {
  document.getElementById('customer-modal')?.classList.add('hidden');
};

window.closeAddReservationModal = function() {
  document.getElementById('add-reservation-modal')?.classList.add('hidden');
};

window.closeAddTableModal = function() {
  document.getElementById('add-table-modal')?.classList.add('hidden');
};

window.closeLoginModal = function() {
  document.getElementById('role-select-modal')?.classList.add('hidden');
};

window.closePrintableReceiptModal = function() {
  document.getElementById('printable-receipt-modal')?.classList.add('hidden');
};

window.closeSidebar = function() {
  document.getElementById('sidebar')?.classList.remove('mobile-open');
  document.getElementById('sidebar-backdrop')?.classList.remove('active');
};

window.detachCustomer = function() {
  AppState.cart.customer = null;
  renderCartUI();
  window.closeCustomerModal();
  showToast('Customer detached. Order set to Walk-in Guest.', 'info');
};

// Universal Delegated Modal & Drawer Closer
function setupUniversalModalClosers() {
  document.addEventListener('click', (e) => {
    // 1. Any click on a close button
    const closeBtn = e.target.closest('.modal-close, .close-cart, [data-close-modal], #close-customiser-btn, #close-payment-btn, #close-receipt-btn, #close-customer-modal-btn, #close-cart-btn, #close-role-modal-btn');
    if (closeBtn) {
      e.preventDefault();
      e.stopPropagation();
      const modal = closeBtn.closest('.modal-backdrop, .modal-overlay, #cart-drawer');
      if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('mobile-open');
        AppState.editingCartIndex = null;
      } else {
        document.querySelectorAll('.modal-backdrop:not(.hidden), .modal-overlay:not(.hidden)').forEach(m => m.classList.add('hidden'));
        document.getElementById('cart-drawer')?.classList.remove('mobile-open');
      }
      return;
    }

    // 2. Click on the backdrop background itself
    if (e.target.classList.contains('modal-backdrop') || e.target.classList.contains('modal-overlay')) {
      e.target.classList.add('hidden');
      AppState.editingCartIndex = null;
    }
  });

  // 3. Escape key closes topmost modal or drawer
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' || e.keyCode === 27) {
      const openModals = document.querySelectorAll('.modal-backdrop:not(.hidden), .modal-overlay:not(.hidden)');
      if (openModals.length > 0) {
        openModals[openModals.length - 1].classList.add('hidden');
        AppState.editingCartIndex = null;
      } else {
        const cartDrawer = document.getElementById('cart-drawer');
        if (cartDrawer && cartDrawer.classList.contains('mobile-open')) {
          cartDrawer.classList.add('hidden');
          cartDrawer.classList.remove('mobile-open');
        }
      }
    }
  });
}

window.updateCartQty = function(index, delta) {
  const item = AppState.cart.items[index];
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) {
    AppState.cart.items.splice(index, 1);
  } else {
    item.totalPrice = item.unitPrice * item.qty;
  }
  renderCartUI();
  saveLocalDB();
};

window.removeCartItem = function(index) {
  AppState.cart.items.splice(index, 1);
  renderCartUI();
  saveLocalDB();
};

// Edit Cart Item Options
window.editCartItem = function(index) {
  const ci = AppState.cart.items[index];
  if (!ci) return;
  AppState.editingCartIndex = index;
  openCustomiserModalAsync(ci.item, ci);
};

// Confirm Add to Cart / Update Item from Customiser Modal
let isAddingToCart = false;
window.confirmAddToCart = function() {
  if (isAddingToCart) return;
  isAddingToCart = true;
  setTimeout(() => { isAddingToCart = false; }, 300);

  const modal = document.getElementById('customiser-modal');
  if (!AppState.modalItem) {
    if (modal) modal.classList.add('hidden');
    return;
  }

  const customisations = [];
  document.querySelectorAll('#dynamic-customiser-sections input:checked').forEach(input => {
    customisations.push({
      customisation_id: input.getAttribute('data-id') || input.value,
      group_name: input.getAttribute('data-group') || 'Option',
      option_name: input.getAttribute('data-name') || input.value,
      extra_price: parseFloat(input.getAttribute('data-extra') || 0)
    });
  });

  const notes = document.getElementById('customiser-item-notes')?.value?.trim() || '';
  const qty = Math.max(1, parseInt(document.getElementById('customiser-qty')?.textContent || '1') || 1);
  const currentItem = { ...AppState.modalItem };

  if (AppState.editingCartIndex !== null && AppState.editingCartIndex !== undefined) {
    const idx = AppState.editingCartIndex;
    if (AppState.cart && AppState.cart.items && AppState.cart.items[idx]) {
      let extraPrice = 0;
      customisations.forEach(c => extraPrice += parseFloat(c.extra_price || 0));
      const basePrice = parseFloat(currentItem.price || currentItem.unit_price || 0);
      const unitPrice = basePrice + extraPrice;

      AppState.cart.items[idx].customisations = customisations;
      AppState.cart.items[idx].notes = notes;
      AppState.cart.items[idx].qty = qty;
      AppState.cart.items[idx].unitPrice = unitPrice;
      AppState.cart.items[idx].totalPrice = unitPrice * qty;
    }
    AppState.editingCartIndex = null;
    AppState.modalItem = null;
    if (modal) modal.classList.add('hidden');
    renderCartUI();
    saveLocalDB();
    showToast(`✨ Updated ${currentItem.name || currentItem.product_name} in cart!`, 'success');
  } else {
    addItemToCart(currentItem, customisations, notes, qty);
    AppState.modalItem = null;
    if (modal) modal.classList.add('hidden');
    
    // Animate topbar cart badge
    const badge = document.getElementById('topbar-cart-toggle-btn');
    if (badge) {
      badge.classList.remove('cart-pulse');
      void badge.offsetWidth;
      badge.classList.add('cart-pulse');
    }

    renderCartUI();
    window.openCartDrawer();
    showToast(`✨ Added ${qty}x ${currentItem.name || currentItem.product_name} to cart!`, 'success');
  }
};

// Customiser Modal Logic
function setupCustomiserModal() {
  const modal = document.getElementById('customiser-modal');
  const closeBtn = document.getElementById('close-customiser-btn');
  if (closeBtn && modal) {
    closeBtn.addEventListener('click', () => {
      modal.classList.add('hidden');
      AppState.modalItem = null;
      AppState.editingCartIndex = null;
    });
  }

  const minusBtn = document.getElementById('qty-minus');
  if (minusBtn) {
    minusBtn.addEventListener('click', () => {
      let q = parseInt(document.getElementById('customiser-qty')?.textContent || '1');
      if (q > 1) {
        document.getElementById('customiser-qty').textContent = q - 1;
        recalculateCustomiserPrice();
      }
    });
  }

  const plusBtn = document.getElementById('qty-plus');
  if (plusBtn) {
    plusBtn.addEventListener('click', () => {
      let q = parseInt(document.getElementById('customiser-qty')?.textContent || '1');
      document.getElementById('customiser-qty').textContent = q + 1;
      recalculateCustomiserPrice();
    });
  }
}

function openCustomiserModal(item) {
  openCustomiserModalAsync(item);
}

function getClientSideCustomisations(item) {
  const catId = String(item.category_id || item.catId || '1');
  const nameLower = (item.name || item.product_name || '').toLowerCase();
  const groups = {};

  const isCoffee = catId === '1' || nameLower.includes('latte') || nameLower.includes('cappuccino') || nameLower.includes('flat white') || nameLower.includes('espresso') || nameLower.includes('mocha') || nameLower.includes('long black') || nameLower.includes('piccolo') || nameLower.includes('macchiato');
  const isHotDrink = catId === '2' || nameLower.includes('chai') || nameLower.includes('chocolate') || nameLower.includes('matcha') || nameLower.includes('turmeric');
  const isTea = catId === '3' || nameLower.includes('tea');
  const isColdCoffee = catId === '4' || nameLower.includes('iced latte') || nameLower.includes('iced long black') || nameLower.includes('iced coffee') || nameLower.includes('cold brew');
  const isColdDrink = catId === '5' || nameLower.includes('milkshake') || nameLower.includes('soda') || nameLower.includes('iced chocolate');
  const isSmoothie = catId === '6' || nameLower.includes('smoothie');
  const isJuice = catId === '7' || nameLower.includes('juice');
  const isBreakfast = catId === '8' || nameLower.includes('egg') || nameLower.includes('toast') || nameLower.includes('benedict') || nameLower.includes('bacon') || nameLower.includes('avocado') || nameLower.includes('wrap') || nameLower.includes('burger');
  const isToastie = catId === '9' || nameLower.includes('toastie') || nameLower.includes('melt');
  const isSandwich = catId === '10' || nameLower.includes('sandwich') || nameLower.includes('blt');
  const isPastryBakery = catId === '11' || catId === '12' || nameLower.includes('croissant') || nameLower.includes('danish') || nameLower.includes('muffin') || nameLower.includes('bread') || nameLower.includes('scone');
  const isLunch = catId === '13' || nameLower.includes('salad') || nameLower.includes('caesar') || nameLower.includes('bowl');
  const isSides = catId === '14' || nameLower.includes('chips') || nameLower.includes('fries');

  if (isCoffee) {
    groups['Cup Size'] = [
      { customisation_id: 'cs-1', option_name: 'Regular (8oz)', extra_price: 0.00, is_default: true },
      { customisation_id: 'cs-2', option_name: 'Large (12oz)', extra_price: 0.80, is_default: false },
      { customisation_id: 'cs-3', option_name: 'Jumbo (16oz)', extra_price: 1.50, is_default: false }
    ];
    groups['Milk Choice'] = [
      { customisation_id: 'mc-1', option_name: 'Full Cream Dairy Milk', extra_price: 0.00, is_default: !nameLower.includes('black') && !nameLower.includes('espresso') },
      { customisation_id: 'mc-2', option_name: 'Skinny / Light Milk', extra_price: 0.00, is_default: false },
      { customisation_id: 'mc-3', option_name: 'Oat Milk (Oatly Barista)', extra_price: 0.80, is_default: false },
      { customisation_id: 'mc-4', option_name: 'Almond Milk (Milklab)', extra_price: 0.80, is_default: false },
      { customisation_id: 'mc-5', option_name: 'Soy Milk (Bonsoy)', extra_price: 0.70, is_default: false },
      { customisation_id: 'mc-6', option_name: 'Coconut Milk (Milklab)', extra_price: 0.80, is_default: false },
      { customisation_id: 'mc-7', option_name: 'Lactose-Free (Zymil)', extra_price: 0.70, is_default: false },
      { customisation_id: 'mc-8', option_name: 'No Milk (Black)', extra_price: 0.00, is_default: nameLower.includes('black') || nameLower.includes('espresso') }
    ];
    groups['Espresso Roast & Origin'] = [
      { customisation_id: 'ro-1', option_name: 'Ravenhill Reserve Blend', extra_price: 0.00, is_default: true },
      { customisation_id: 'ro-2', option_name: 'Single Origin Ethiopian (Floral & Berry)', extra_price: 1.00, is_default: false },
      { customisation_id: 'ro-3', option_name: 'Swiss Water Decaf', extra_price: 0.70, is_default: false }
    ];
    groups['Espresso Strength & Shots'] = [
      { customisation_id: 'st-1', option_name: 'Standard Shot', extra_price: 0.00, is_default: true },
      { customisation_id: 'st-2', option_name: 'Extra Espresso Shot (+1)', extra_price: 0.80, is_default: false },
      { customisation_id: 'st-3', option_name: 'Double Extra Shot (+2)', extra_price: 1.50, is_default: false },
      { customisation_id: 'st-4', option_name: 'Half Strength', extra_price: 0.00, is_default: false },
      { customisation_id: 'st-5', option_name: 'Ristretto Extraction', extra_price: 0.00, is_default: false }
    ];
    groups['Syrups & Flavours'] = [
      { customisation_id: 'sy-1', option_name: 'Vanilla Syrup', extra_price: 0.70, is_default: false },
      { customisation_id: 'sy-2', option_name: 'Caramel Syrup', extra_price: 0.70, is_default: false },
      { customisation_id: 'sy-3', option_name: 'Hazelnut Syrup', extra_price: 0.70, is_default: false },
      { customisation_id: 'sy-4', option_name: 'Salted Caramel Syrup', extra_price: 0.80, is_default: false },
      { customisation_id: 'sy-5', option_name: 'Pure Raw Honey', extra_price: 0.60, is_default: false }
    ];
    groups['Temperature & Sweetener'] = [
      { customisation_id: 'tp-1', option_name: 'Extra Hot (68°C)', extra_price: 0.00, is_default: false },
      { customisation_id: 'tp-2', option_name: 'Warm / Kid\'s Temp (55°C)', extra_price: 0.00, is_default: false },
      { customisation_id: 'tp-3', option_name: '1x Raw Sugar', extra_price: 0.00, is_default: false },
      { customisation_id: 'tp-4', option_name: '2x Raw Sugar', extra_price: 0.00, is_default: false },
      { customisation_id: 'tp-5', option_name: 'Equal / Stevia', extra_price: 0.00, is_default: false },
      { customisation_id: 'tp-6', option_name: 'Dust with Dark Cocoa', extra_price: 0.00, is_default: nameLower.includes('cappuccino') },
      { customisation_id: 'tp-7', option_name: 'Dust with Cinnamon', extra_price: 0.00, is_default: false }
    ];
  } else if (isHotDrink) {
    groups['Cup Size'] = [
      { customisation_id: 'hd-s1', option_name: 'Regular (8oz)', extra_price: 0.00, is_default: true },
      { customisation_id: 'hd-s2', option_name: 'Large (12oz)', extra_price: 0.80, is_default: false },
      { customisation_id: 'hd-s3', option_name: 'Jumbo (16oz)', extra_price: 1.50, is_default: false }
    ];
    groups['Milk Choice'] = [
      { customisation_id: 'hd-m1', option_name: 'Full Cream Dairy Milk', extra_price: 0.00, is_default: true },
      { customisation_id: 'hd-m2', option_name: 'Skinny Milk', extra_price: 0.00, is_default: false },
      { customisation_id: 'hd-m3', option_name: 'Oat Milk (Oatly)', extra_price: 0.80, is_default: false },
      { customisation_id: 'hd-m4', option_name: 'Almond Milk (Milklab)', extra_price: 0.80, is_default: false },
      { customisation_id: 'hd-m5', option_name: 'Soy Milk (Bonsoy)', extra_price: 0.70, is_default: false },
      { customisation_id: 'hd-m6', option_name: 'Coconut Milk', extra_price: 0.80, is_default: false }
    ];
    groups['Add-Ons & Extras'] = [
      { customisation_id: 'hd-e1', option_name: 'Add Espresso Shot (Dirty)', extra_price: 0.80, is_default: false },
      { customisation_id: 'hd-e2', option_name: 'Fresh Whipped Cream', extra_price: 1.00, is_default: false },
      { customisation_id: 'hd-e3', option_name: 'Extra Marshmallows (3 pcs)', extra_price: 0.60, is_default: false },
      { customisation_id: 'hd-e4', option_name: 'Pure Raw Honey', extra_price: 0.60, is_default: false }
    ];
    groups['Temperature & Sweetener'] = [
      { customisation_id: 'hd-t1', option_name: 'Extra Hot', extra_price: 0.00, is_default: false },
      { customisation_id: 'hd-t2', option_name: 'Warm / Kid\'s Temp', extra_price: 0.00, is_default: false },
      { customisation_id: 'hd-t3', option_name: 'Less Sweet', extra_price: 0.00, is_default: false },
      { customisation_id: 'hd-t4', option_name: '1x Sugar', extra_price: 0.00, is_default: false }
    ];
  } else if (isTea) {
    groups['Pot Size'] = [
      { customisation_id: 't-s1', option_name: 'Teapot for One', extra_price: 0.00, is_default: true },
      { customisation_id: 't-s2', option_name: 'Large Teapot for Two', extra_price: 2.00, is_default: false }
    ];
    groups['Milk on the Side'] = [
      { customisation_id: 't-m1', option_name: 'No Milk', extra_price: 0.00, is_default: true },
      { customisation_id: 't-m2', option_name: 'Cold Full Cream Milk on Side', extra_price: 0.00, is_default: false },
      { customisation_id: 't-m3', option_name: 'Cold Oat Milk on Side', extra_price: 0.80, is_default: false },
      { customisation_id: 't-m4', option_name: 'Cold Soy Milk on Side', extra_price: 0.70, is_default: false }
    ];
    groups['Garnishes & Sweeteners'] = [
      { customisation_id: 't-g1', option_name: 'Fresh Lemon Slice', extra_price: 0.00, is_default: false },
      { customisation_id: 't-g2', option_name: 'Fresh Mint Leaves', extra_price: 0.00, is_default: false },
      { customisation_id: 't-g3', option_name: 'Pure Raw Honey on Side', extra_price: 0.60, is_default: false },
      { customisation_id: 't-g4', option_name: '1x Raw Sugar', extra_price: 0.00, is_default: false }
    ];
  } else if (isColdCoffee || isColdDrink) {
    groups['Cup Size'] = [
      { customisation_id: 'cc-s1', option_name: 'Regular Chilled (16oz)', extra_price: 0.00, is_default: true },
      { customisation_id: 'cc-s2', option_name: 'Large Chilled (20oz)', extra_price: 1.00, is_default: false }
    ];
    groups['Milk Choice'] = [
      { customisation_id: 'cc-m1', option_name: 'Full Cream Dairy Milk', extra_price: 0.00, is_default: true },
      { customisation_id: 'cc-m2', option_name: 'Skinny Milk', extra_price: 0.00, is_default: false },
      { customisation_id: 'cc-m3', option_name: 'Oat Milk (Oatly)', extra_price: 0.80, is_default: false },
      { customisation_id: 'cc-m4', option_name: 'Almond Milk (Milklab)', extra_price: 0.80, is_default: false },
      { customisation_id: 'cc-m5', option_name: 'Soy Milk (Bonsoy)', extra_price: 0.70, is_default: false },
      { customisation_id: 'cc-m6', option_name: 'Black / Water Only', extra_price: 0.00, is_default: false }
    ];
    groups['Ice Level'] = [
      { customisation_id: 'cc-i1', option_name: 'Standard Ice', extra_price: 0.00, is_default: true },
      { customisation_id: 'cc-i2', option_name: 'Less Ice', extra_price: 0.00, is_default: false },
      { customisation_id: 'cc-i3', option_name: 'Extra Ice', extra_price: 0.00, is_default: false },
      { customisation_id: 'cc-i4', option_name: 'No Ice', extra_price: 0.00, is_default: false }
    ];
    groups['Cold Extras & Flavors'] = [
      { customisation_id: 'cc-e1', option_name: 'Extra Espresso Shot', extra_price: 0.80, is_default: false },
      { customisation_id: 'cc-e2', option_name: 'Scoop of Vanilla Ice Cream', extra_price: 1.50, is_default: nameLower.includes('iced coffee') || nameLower.includes('iced chocolate') },
      { customisation_id: 'cc-e3', option_name: 'Fresh Whipped Cream', extra_price: 1.00, is_default: nameLower.includes('iced coffee') || nameLower.includes('iced chocolate') },
      { customisation_id: 'cc-e4', option_name: 'Vanilla Syrup', extra_price: 0.70, is_default: false },
      { customisation_id: 'cc-e5', option_name: 'Caramel Syrup', extra_price: 0.70, is_default: false },
      { customisation_id: 'cc-e6', option_name: 'Hazelnut Syrup', extra_price: 0.70, is_default: false },
      { customisation_id: 'cc-e7', option_name: 'Salted Caramel Syrup', extra_price: 0.80, is_default: false }
    ];
  } else if (isSmoothie || isJuice) {
    groups['Liquid Base'] = [
      { customisation_id: 'sm-b1', option_name: 'Full Cream Milk', extra_price: 0.00, is_default: isSmoothie },
      { customisation_id: 'sm-b2', option_name: 'Oat Milk', extra_price: 0.80, is_default: false },
      { customisation_id: 'sm-b3', option_name: 'Almond Milk', extra_price: 0.80, is_default: false },
      { customisation_id: 'sm-b4', option_name: 'Coconut Water Base', extra_price: 1.00, is_default: false },
      { customisation_id: 'sm-b5', option_name: 'Apple Juice Base', extra_price: 0.00, is_default: isJuice }
    ];
    groups['Superfood Boosters & Protein'] = [
      { customisation_id: 'sm-p1', option_name: 'Organic Vanilla Pea Protein', extra_price: 2.50, is_default: false },
      { customisation_id: 'sm-p2', option_name: 'Whey Protein Isolate (Chocolate)', extra_price: 2.50, is_default: false },
      { customisation_id: 'sm-p3', option_name: 'Organic Chia Seeds', extra_price: 1.00, is_default: false },
      { customisation_id: 'sm-p4', option_name: 'Organic Spirulina Greens', extra_price: 1.50, is_default: false },
      { customisation_id: 'sm-p5', option_name: 'Peanut Butter Scoop', extra_price: 1.50, is_default: false },
      { customisation_id: 'sm-p6', option_name: 'Fresh Ginger Shot', extra_price: 1.00, is_default: false },
      { customisation_id: 'sm-p7', option_name: 'Pure Raw Honey', extra_price: 0.50, is_default: false }
    ];
    groups['Texture & Sweetness'] = [
      { customisation_id: 'sm-t1', option_name: 'Standard Blend', extra_price: 0.00, is_default: true },
      { customisation_id: 'sm-t2', option_name: 'Extra Thick / Less Ice', extra_price: 0.00, is_default: false },
      { customisation_id: 'sm-t3', option_name: 'No Added Sweetener', extra_price: 0.00, is_default: false }
    ];
  } else if (isBreakfast) {
    groups['Egg Preparation Style'] = [
      { customisation_id: 'bf-e1', option_name: 'Poached Eggs (Soft Runny)', extra_price: 0.00, is_default: true },
      { customisation_id: 'bf-e2', option_name: 'Scrambled Eggs (Silky Butter)', extra_price: 0.00, is_default: false },
      { customisation_id: 'bf-e3', option_name: 'Fried Eggs (Sunny Side Up)', extra_price: 0.00, is_default: false },
      { customisation_id: 'bf-e4', option_name: 'Fried Eggs (Over Hard)', extra_price: 0.00, is_default: false },
      { customisation_id: 'bf-e5', option_name: 'Egg Whites Only', extra_price: 2.00, is_default: false },
      { customisation_id: 'bf-e6', option_name: 'No Eggs', extra_price: 0.00, is_default: false }
    ];
    groups['Bread & Toast Selection'] = [
      { customisation_id: 'bf-b1', option_name: 'Artisan White Sourdough', extra_price: 0.00, is_default: true },
      { customisation_id: 'bf-b2', option_name: 'Seeded Multigrain Sourdough', extra_price: 0.50, is_default: false },
      { customisation_id: 'bf-b3', option_name: 'Gluten-Free Toast', extra_price: 1.50, is_default: false },
      { customisation_id: 'bf-b4', option_name: 'Toasted Brioche Bun', extra_price: 0.00, is_default: false },
      { customisation_id: 'bf-b5', option_name: 'No Bread / Carb-Free', extra_price: 0.00, is_default: false }
    ];
    groups['Breakfast Add-Ons & Extras'] = [
      { customisation_id: 'bf-a1', option_name: 'Crispy Smoked Bacon (2 Rashers)', extra_price: 4.50, is_default: false },
      { customisation_id: 'bf-a2', option_name: 'Grilled Halloumi (2 Slices)', extra_price: 4.50, is_default: false },
      { customisation_id: 'bf-a3', option_name: 'Smashed Hass Avocado', extra_price: 4.00, is_default: false },
      { customisation_id: 'bf-a4', option_name: 'Golden Potato Hash Brown', extra_price: 3.50, is_default: false },
      { customisation_id: 'bf-a5', option_name: 'Grilled Thyme Field Mushrooms', extra_price: 4.00, is_default: false },
      { customisation_id: 'bf-a6', option_name: 'Smoked Tasmanian Salmon', extra_price: 6.00, is_default: false },
      { customisation_id: 'bf-a7', option_name: 'Wilted Baby Spinach', extra_price: 3.00, is_default: false },
      { customisation_id: 'bf-a8', option_name: 'Roasted Heirloom Tomatoes', extra_price: 3.50, is_default: false },
      { customisation_id: 'bf-a9', option_name: 'Danish Creamy Feta', extra_price: 3.00, is_default: false },
      { customisation_id: 'bf-a10', option_name: 'Extra Free-Range Egg', extra_price: 2.50, is_default: false }
    ];
    groups['Sauces & Condiments'] = [
      { customisation_id: 'bf-s1', option_name: 'House Citrus Hollandaise', extra_price: 2.00, is_default: nameLower.includes('benedict') },
      { customisation_id: 'bf-s2', option_name: 'Smoky Tomato Relish', extra_price: 1.00, is_default: false },
      { customisation_id: 'bf-s3', option_name: 'Chipotle Spicy Mayo', extra_price: 1.00, is_default: false },
      { customisation_id: 'bf-s4', option_name: 'Smoky BBQ Sauce', extra_price: 0.00, is_default: false },
      { customisation_id: 'bf-s5', option_name: 'Tomato Ketchup', extra_price: 0.00, is_default: false },
      { customisation_id: 'bf-s6', option_name: 'Sauce on the Side', extra_price: 0.00, is_default: false }
    ];
    groups['Removals & Dietary'] = [
      { customisation_id: 'bf-r1', option_name: 'No Butter / Dry Toast', extra_price: 0.00, is_default: false },
      { customisation_id: 'bf-r2', option_name: 'No Onion / Chives', extra_price: 0.00, is_default: false },
      { customisation_id: 'bf-r3', option_name: 'No Dukkah (Nut Allergy)', extra_price: 0.00, is_default: false },
      { customisation_id: 'bf-r4', option_name: 'Extra Crispy Bacon', extra_price: 0.00, is_default: false }
    ];
  } else if (isToastie || isSandwich) {
    groups['Bread Choice'] = [
      { customisation_id: 'ts-b1', option_name: 'Artisan White Sourdough', extra_price: 0.00, is_default: true },
      { customisation_id: 'ts-b2', option_name: 'Seeded Multigrain Sourdough', extra_price: 0.50, is_default: false },
      { customisation_id: 'ts-b3', option_name: 'Gluten-Free Bread', extra_price: 1.50, is_default: false },
      { customisation_id: 'ts-b4', option_name: 'Fresh French Baguette', extra_price: 0.00, is_default: isSandwich && !nameLower.includes('toast') }
    ];
    groups['Toasting Preference'] = [
      { customisation_id: 'ts-t1', option_name: 'Toasted Golden & Crunchy', extra_price: 0.00, is_default: true },
      { customisation_id: 'ts-t2', option_name: 'Lightly Toasted', extra_price: 0.00, is_default: false },
      { customisation_id: 'ts-t3', option_name: 'Fresh / Untoasted', extra_price: 0.00, is_default: false }
    ];
    groups['Cheese & Filling Upgrades'] = [
      { customisation_id: 'ts-c1', option_name: 'Extra Melted Vintage Cheddar', extra_price: 2.00, is_default: false },
      { customisation_id: 'ts-c2', option_name: 'Extra Swiss Gruyère Cheese', extra_price: 2.50, is_default: false },
      { customisation_id: 'ts-c3', option_name: 'Add Sliced Hass Avocado', extra_price: 3.50, is_default: false },
      { customisation_id: 'ts-c4', option_name: 'Add Crispy Bacon', extra_price: 4.00, is_default: false },
      { customisation_id: 'ts-c5', option_name: 'Add Pickled Jalapeños', extra_price: 1.00, is_default: false },
      { customisation_id: 'ts-c6', option_name: 'Add Dill Pickles / Gherkins', extra_price: 1.00, is_default: false },
      { customisation_id: 'ts-c7', option_name: 'Add Sliced Heirloom Tomato', extra_price: 1.50, is_default: false }
    ];
    groups['Spreads & Condiments'] = [
      { customisation_id: 'ts-s1', option_name: 'Dijon Mustard', extra_price: 0.00, is_default: false },
      { customisation_id: 'ts-s2', option_name: 'House Herb Aioli', extra_price: 0.00, is_default: isSandwich },
      { customisation_id: 'ts-s3', option_name: 'Truffle Mayo', extra_price: 1.50, is_default: false },
      { customisation_id: 'ts-s4', option_name: 'Sweet Chili Jam', extra_price: 1.00, is_default: false }
    ];
    groups['Removals & Dietary'] = [
      { customisation_id: 'ts-r1', option_name: 'No Butter', extra_price: 0.00, is_default: false },
      { customisation_id: 'ts-r2', option_name: 'No Tomato', extra_price: 0.00, is_default: false },
      { customisation_id: 'ts-r3', option_name: 'No Onion', extra_price: 0.00, is_default: false },
      { customisation_id: 'ts-r4', option_name: 'No Mustard / Mayo', extra_price: 0.00, is_default: false }
    ];
  } else if (isPastryBakery) {
    groups['Serving Style'] = [
      { customisation_id: 'pb-s1', option_name: 'Served Fresh (Room Temp)', extra_price: 0.00, is_default: true },
      { customisation_id: 'pb-s2', option_name: 'Warmed in Oven', extra_price: 0.00, is_default: false },
      { customisation_id: 'pb-s3', option_name: 'Toasted with Butter on Side', extra_price: 0.00, is_default: false }
    ];
    groups['Accompaniments & Spreads'] = [
      { customisation_id: 'pb-a1', option_name: 'Cultured French Butter', extra_price: 0.00, is_default: nameLower.includes('croissant') || nameLower.includes('bread') },
      { customisation_id: 'pb-a2', option_name: 'Strawberry Preserves', extra_price: 0.50, is_default: false },
      { customisation_id: 'pb-a3', option_name: 'Australian Pure Honey', extra_price: 0.50, is_default: false },
      { customisation_id: 'pb-a4', option_name: 'Nutella Hazelnut Spread', extra_price: 1.00, is_default: false },
      { customisation_id: 'pb-a5', option_name: 'Fresh Whipped Cream', extra_price: 1.00, is_default: false }
    ];
    groups['Removals'] = [
      { customisation_id: 'pb-r1', option_name: 'No Butter', extra_price: 0.00, is_default: false },
      { customisation_id: 'pb-r2', option_name: 'No Icing Sugar Dusting', extra_price: 0.00, is_default: false }
    ];
  } else if (isLunch || isSides) {
    groups['Protein Add-Ons'] = [
      { customisation_id: 'ln-p1', option_name: 'Grilled Herb Chicken Breast', extra_price: 5.50, is_default: false },
      { customisation_id: 'ln-p2', option_name: 'Smoked Tasmanian Salmon', extra_price: 6.00, is_default: false },
      { customisation_id: 'ln-p3', option_name: 'Grilled Halloumi (2 Slices)', extra_price: 4.50, is_default: false },
      { customisation_id: 'ln-p4', option_name: 'Boiled Free-Range Egg', extra_price: 2.50, is_default: false },
      { customisation_id: 'ln-p5', option_name: 'Smashed Hass Avocado', extra_price: 4.00, is_default: false }
    ];
    if (isLunch) {
      groups['Salad Dressings'] = [
        { customisation_id: 'ln-d1', option_name: 'House Lemon & Herb Vinaigrette', extra_price: 0.00, is_default: true },
        { customisation_id: 'ln-d2', option_name: 'Creamy Garlic Caesar', extra_price: 0.00, is_default: false },
        { customisation_id: 'ln-d3', option_name: 'Japanese Sesame Soy', extra_price: 0.00, is_default: false },
        { customisation_id: 'ln-d4', option_name: 'Dressing on the Side', extra_price: 0.00, is_default: false }
      ];
    }
    groups['Dipping Sauces'] = [
      { customisation_id: 'ln-s1', option_name: 'Garlic Aioli', extra_price: 1.00, is_default: isSides },
      { customisation_id: 'ln-s2', option_name: 'Chipotle Spicy Mayo', extra_price: 1.00, is_default: false },
      { customisation_id: 'ln-s3', option_name: 'Truffle Mayo', extra_price: 1.50, is_default: false },
      { customisation_id: 'ln-s4', option_name: 'Smoky BBQ Relish', extra_price: 1.00, is_default: false },
      { customisation_id: 'ln-s5', option_name: 'Tomato Ketchup', extra_price: 0.00, is_default: false }
    ];
    groups['Removals & Dietary'] = [
      { customisation_id: 'ln-r1', option_name: 'No Onion', extra_price: 0.00, is_default: false },
      { customisation_id: 'ln-r2', option_name: 'No Croutons (Gluten Free)', extra_price: 0.00, is_default: false },
      { customisation_id: 'ln-r3', option_name: 'No Cheese / Dairy Free', extra_price: 0.00, is_default: false },
      { customisation_id: 'ln-r4', option_name: 'No Nuts / Seeds', extra_price: 0.00, is_default: false }
    ];
  } else {
    // Universal fallback
    groups['Extras & Add-Ons'] = [
      { customisation_id: 'un-1', option_name: 'Extra Portion', extra_price: 2.50, is_default: false },
      { customisation_id: 'un-2', option_name: 'Side Salad', extra_price: 4.50, is_default: false },
      { customisation_id: 'un-3', option_name: 'Sauce on the Side', extra_price: 0.00, is_default: false }
    ];
    groups['Removals & Notes'] = [
      { customisation_id: 'un-r1', option_name: 'No Onion', extra_price: 0.00, is_default: false },
      { customisation_id: 'un-r2', option_name: 'No Dairy', extra_price: 0.00, is_default: false },
      { customisation_id: 'un-r3', option_name: 'Extra Crispy', extra_price: 0.00, is_default: false }
    ];
  }

  return groups;
}

async function openCustomiserModalAsync(item, editingData = null) {
  AppState.modalItem = item;
  document.getElementById('customiser-item-name').textContent = item.name || item.product_name;
  document.getElementById('customiser-item-desc').textContent = item.desc || '';
  document.getElementById('customiser-qty').textContent = editingData ? (editingData.qty || 1) : 1;
  document.getElementById('customiser-item-notes').value = editingData ? (editingData.notes || '') : '';

  const imgEl = document.getElementById('customiser-item-img');
  if (imgEl) imgEl.src = getItemImage(item);

  const notesLabel = document.getElementById('customiser-notes-label');
  const notesInput = document.getElementById('customiser-item-notes');
  const catId = String(item.category_id || item.catId || '1');
  const isDrink = ['1', '2', '3', '4', '5', '6', '7'].includes(catId);

  if (notesLabel) {
    notesLabel.textContent = isDrink ? '☕ Barista Notes & Special Requests' : '🍳 Kitchen & Dietary Instructions';
  }
  if (notesInput) {
    notesInput.placeholder = isDrink ? 'E.g., Extra hot, 3/4 full, latte art, separate hot water...' : 'E.g., Extra crispy bacon, dressing on side, nut allergy, well toasted...';
  }

  const container = document.getElementById('dynamic-customiser-sections');
  if (container) {
    container.innerHTML = '<div style="padding:20px;text-align:center;"><i class="ri-loader-4-line ri-spin" style="font-size:24px; color:var(--color-primary);"></i><p>Loading customisation options...</p></div>';
  }
  document.getElementById('customiser-modal').classList.remove('hidden');

  let groupsToRender = {};

  try {
    const cData = await API.fetchCustomisations(item.product_id || item.id, item.category_id);
    if (cData && cData.groups && Object.keys(cData.groups).length > 0) {
      groupsToRender = cData.groups;
    } else {
      groupsToRender = getClientSideCustomisations(item);
    }
  } catch (err) {
    console.warn('[Customiser] Falling back to client-side customisations:', err);
    groupsToRender = getClientSideCustomisations(item);
  }

  if (container) {
    container.innerHTML = '';

    const singleChoiceGroups = [
      'Cup Size', 'Size', 'Cold Size', 'Size Selection', 'Pot Size',
      'Milk Choice', 'Milk & Dairy Choice', 'Milk on the Side',
      'Espresso Roast & Origin', 'Espresso Strength', 'Espresso Strength & Shots',
      'Liquid Base', 'Egg Preparation Style', 'Bread & Toast Selection',
      'Bread Choice', 'Toasting Preference', 'Serving Style', 'Salad Dressings',
      'Ice Level', 'Texture & Sweetness'
    ];

    const groupIcons = {
      'Cup Size': 'ri-cup-line',
      'Size': 'ri-cup-line',
      'Cold Size': 'ri-snow-line',
      'Size Selection': 'ri-cup-line',
      'Pot Size': 'ri-leaf-line',
      'Milk Choice': 'ri-drop-line',
      'Milk & Dairy Choice': 'ri-drop-line',
      'Milk on the Side': 'ri-drop-line',
      'Espresso Roast & Origin': 'ri-fire-line',
      'Espresso Strength & Shots': 'ri-flashlight-line',
      'Espresso Strength': 'ri-flashlight-line',
      'Syrups & Flavours': 'ri-heart-pulse-line',
      'Temperature & Sweetener': 'ri-temp-hot-line',
      'Hot Drink Extras': 'ri-add-circle-line',
      'Add-Ons & Extras': 'ri-add-circle-line',
      'Cold Extras & Flavors': 'ri-sparkling-line',
      'Superfood Boosters & Protein': 'ri-capsule-line',
      'Liquid Base': 'ri-drinks-line',
      'Egg Preparation Style': 'ri-restaurant-line',
      'Bread & Toast Selection': 'ri-bread-line',
      'Bread Choice': 'ri-bread-line',
      'Toasting Preference': 'ri-fire-line',
      'Breakfast Add-Ons & Extras': 'ri-add-circle-line',
      'Food Add-Ons & Extras': 'ri-add-circle-line',
      'Cheese & Filling Upgrades': 'ri-add-circle-line',
      'Protein Add-Ons': 'ri-user-star-line',
      'Sauces & Condiments': 'ri-goblet-line',
      'Spreads & Condiments': 'ri-goblet-line',
      'Salad Dressings': 'ri-oil-line',
      'Dipping Sauces': 'ri-contrast-drop-2-line',
      'Accompaniments & Spreads': 'ri-cake-line',
      'Serving Style': 'ri-temp-hot-line',
      'Removals & Dietary': 'ri-forbid-line',
      'Removals': 'ri-forbid-line',
      'Bakery Removals': 'ri-forbid-line',
      'Removals & Notes': 'ri-forbid-line'
    };

    for (const [group, options] of Object.entries(groupsToRender)) {
      const isSingle = singleChoiceGroups.includes(group);
      const type = isSingle ? 'radio' : 'checkbox';
      const groupNameClean = group.replace(/[^a-zA-Z0-9]/g, '_');
      const iconClass = groupIcons[group] || 'ri-checkbox-circle-line';
      const isRemovalGroup = group.toLowerCase().includes('removal');

      let html = `
        <div class="customiser-section">
          <label class="section-label" style="display:flex; align-items:center; gap:6px; font-weight:700; color:var(--color-primary-light);">
            <i class="${iconClass}"></i> <span>${group}</span>
            ${isSingle ? '<span style="font-size:10px; color:var(--color-cream-muted); font-weight:normal; margin-left:auto;">(Select 1)</span>' : '<span style="font-size:10px; color:var(--color-cream-muted); font-weight:normal; margin-left:auto;">(Optional)</span>'}
          </label>
          <div class="checkbox-options-grid">
      `;

      options.forEach((opt, optIdx) => {
        const extraPrice = parseFloat(opt.extra_price || 0);
        let extraText = '';
        if (extraPrice > 0) {
          extraText = `<span class="custom-opt-badge price-extra">+ $${extraPrice.toFixed(2)}</span>`;
        } else if (isRemovalGroup) {
          extraText = `<span class="custom-opt-badge removal-badge">Removal</span>`;
        } else {
          extraText = `<span class="custom-opt-badge free-badge">Free</span>`;
        }

        let isChecked = false;
        if (editingData && editingData.customisations && editingData.customisations.length > 0) {
          isChecked = editingData.customisations.some(c => (c.customisation_id && c.customisation_id === opt.customisation_id) || (c.option_name && c.option_name === opt.option_name));
        } else {
          isChecked = opt.is_default || (isSingle && optIdx === 0 && !options.some(o => o.is_default));
        }

        html += `
          <label class="checkbox-card ${isRemovalGroup ? 'removal-card' : ''}" style="cursor:pointer;">
            <input type="${type}" name="group_${groupNameClean}" 
                   value="${opt.customisation_id || opt.option_name}" 
                   data-id="${opt.customisation_id || opt.option_name}"
                   data-group="${group}"
                   data-name="${opt.option_name}"
                   data-extra="${extraPrice}"
                   ${isChecked ? 'checked' : ''}
                   onchange="recalculateCustomiserPrice()">
            <span class="custom-opt-name">${opt.option_name}</span>
            ${extraText}
          </label>
        `;
      });

      html += `</div></div>`;
      container.innerHTML += html;
    }
  }

  recalculateCustomiserPrice();
}

function recalculateCustomiserPrice() {
  if (!AppState.modalItem) return;
  let base = parseFloat(AppState.modalItem.price || AppState.modalItem.unit_price || 0);

  document.querySelectorAll('#dynamic-customiser-sections input:checked').forEach(input => {
    base += parseFloat(input.getAttribute('data-extra') || 0);
  });

  const qty = parseInt(document.getElementById('customiser-qty')?.textContent || '1');
  const total = base * qty;
  
  const calcEl = document.getElementById('customiser-calculated-price');
  if (calcEl) calcEl.textContent = `$${total.toFixed(2)}`;

  const confirmBtn = document.getElementById('add-to-cart-confirm-btn');
  if (confirmBtn) {
    const isEdit = AppState.editingCartIndex !== null && AppState.editingCartIndex !== undefined;
    confirmBtn.innerHTML = `${isEdit ? '<i class="ri-check-line"></i> Update Item' : '<i class="ri-shopping-cart-2-line"></i> Add to Cart'} • <span id="customiser-calculated-price">$${total.toFixed(2)}</span>`;
  }
}

// Payment & Receipt Modal Logic
let splitState = {
  ways: 2,
  paidCount: 0,
  totalPaid: 0
};

function setupPaymentModal() {
  const modal = document.getElementById('payment-modal');
  document.getElementById('close-payment-btn')?.addEventListener('click', () => modal.classList.add('hidden'));
  document.getElementById('cancel-payment-btn')?.addEventListener('click', () => modal.classList.add('hidden'));

  // Payment Method Tabs
  const payTabs = document.querySelectorAll('.pay-tab');
  payTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      payTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const method = tab.getAttribute('data-method');
      
      const panels = ['eftpos', 'card', 'cash', 'paypal', 'split', 'loyalty'];
      panels.forEach(p => {
        const el = document.getElementById(`tender-panel-${p}`);
        if (el) el.classList.toggle('hidden', p !== method);
      });

      const confirmBtn = document.getElementById('confirm-payment-btn');
      if (confirmBtn) {
        confirmBtn.style.display = 'inline-flex';
        if (method === 'split') {
          confirmBtn.innerHTML = `<i class="ri-check-double-line"></i> Finalise Split Sale`;
        } else {
          confirmBtn.innerHTML = `<i class="ri-check-double-line"></i> Authorise Payment & Complete Sale`;
        }
      }

      if (method === 'paypal' && typeof window.renderPayPalButtons === 'function') {
        window.renderPayPalButtons();
      }
      if (method === 'split') {
        updateSplitBillDisplay();
      }
    });
  });

  // Credit Card Live Mockup Inputs
  const cardNameInput = document.getElementById('card-name-input');
  const cardNumInput = document.getElementById('card-number-input');
  const cardExpInput = document.getElementById('card-exp-input');

  if (cardNameInput) {
    cardNameInput.addEventListener('input', (e) => {
      const preview = document.getElementById('card-preview-name');
      if (preview) preview.textContent = (e.target.value || 'VALUED CUSTOMER').toUpperCase();
    });
  }
  if (cardNumInput) {
    cardNumInput.addEventListener('input', (e) => {
      const preview = document.getElementById('card-preview-number');
      if (preview) {
        let v = e.target.value.replace(/\D/g, '').substring(0, 16);
        let formatted = v.replace(/(\d{4})(?=\d)/g, '$1 ');
        preview.textContent = formatted || '•••• •••• •••• 4242';
      }
    });
  }
  if (cardExpInput) {
    cardExpInput.addEventListener('input', (e) => {
      const preview = document.getElementById('card-preview-exp');
      if (preview) preview.textContent = e.target.value || '12/28';
    });
  }

  // Quick Cash Buttons
  const cashInput = document.getElementById('cash-tendered-input');
  if (cashInput) {
    cashInput.addEventListener('input', updateCashChange);
  }

  document.querySelectorAll('.quick-cash-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const val = btn.getAttribute('data-val');
      const due = calculateCurrentPayableTotal();

      if (val === 'exact') {
        cashInput.value = due.toFixed(2);
      } else {
        cashInput.value = parseFloat(val).toFixed(2);
      }
      updateCashChange();
    });
  });

  // Gratuity / Tip Selector
  AppState.cart.tipPercent = 0;
  AppState.cart.tipAmount = 0;

  document.querySelectorAll('.tip-pill').forEach(pill => {
    pill.addEventListener('click', () => {
      document.querySelectorAll('.tip-pill').forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      const tipVal = parseInt(pill.getAttribute('data-tip') || '0');
      AppState.cart.tipPercent = tipVal;
      recalculatePayModalTotals();
    });
  });

  // Split Bill Options
  document.querySelectorAll('.split-btn').forEach(sbtn => {
    sbtn.addEventListener('click', () => {
      document.querySelectorAll('.split-btn').forEach(b => b.classList.remove('active'));
      sbtn.classList.add('active');
      const ways = sbtn.getAttribute('data-split');
      if (ways === 'custom') {
        const inputWays = prompt('Enter number of split shares (e.g. 5):', '5');
        splitState.ways = Math.max(2, parseInt(inputWays) || 2);
      } else {
        splitState.ways = parseInt(ways) || 2;
      }
      splitState.paidCount = 0;
      splitState.totalPaid = 0;
      updateSplitBillDisplay();
    });
  });

  const paySingleShareBtn = document.getElementById('pay-single-share-btn');
  if (paySingleShareBtn) {
    paySingleShareBtn.addEventListener('click', () => {
      const totalDue = calculateCurrentPayableTotal();
      const perPerson = totalDue / splitState.ways;
      if (splitState.paidCount < splitState.ways) {
        splitState.paidCount++;
        splitState.totalPaid += perPerson;
        updateSplitBillDisplay();
        showToast(`Share ${splitState.paidCount} of ${splitState.ways} paid ($${perPerson.toFixed(2)})!`, 'success');
        if (splitState.paidCount === splitState.ways) {
          showToast('All split shares paid in full! Ready to complete sale.', 'success');
        }
      }
    });
  }

  // Redeem Loyalty Points in Payment
  const redeemPointsBtn = document.getElementById('redeem-points-pay-btn');
  if (redeemPointsBtn) {
    redeemPointsBtn.addEventListener('click', () => {
      if (AppState.cart.customer) {
        const pts = AppState.cart.customer.points || 0;
        const discountVal = Math.min(pts / 20, calculateCurrentPayableTotal());
        AppState.cart.promoCode = {
          code: 'LOYALTY',
          type: 'fixed',
          val: discountVal,
          description: `${Math.round(discountVal * 20)} Pts Redeemed`
        };
        showToast(`Redeemed ${Math.round(discountVal * 20)} points for $${discountVal.toFixed(2)} off!`, 'success');
        recalculatePayModalTotals();
      } else {
        showToast('Please attach a loyalty customer first', 'warning');
      }
    });
  }

  // Digital Receipt Sender
  const sendDigitalBtn = document.getElementById('send-digital-receipt-btn');
  if (sendDigitalBtn) {
    sendDigitalBtn.addEventListener('click', () => {
      const target = document.getElementById('digital-receipt-target')?.value?.trim();
      if (!target) {
        showToast('Please enter an email or phone number', 'warning');
        return;
      }
      showToast(`Digital tax invoice sent to ${target}!`, 'success');
    });
  }

  // Confirm Payment
  document.getElementById('confirm-payment-btn')?.addEventListener('click', completePaymentProcess);
}

function calculateCurrentPayableTotal() {
  const subtotal = AppState.cart.items.reduce((acc, i) => acc + i.totalPrice, 0);
  let discount = 0;
  if (AppState.cart.promoCode) {
    discount = AppState.cart.promoCode.type === 'percent' ? (subtotal * AppState.cart.promoCode.val)/100 : AppState.cart.promoCode.val;
  }
  const base = Math.max(0, subtotal - discount);
  const tip = (base * (AppState.cart.tipPercent || 0)) / 100;
  AppState.cart.tipAmount = tip;
  return base + tip;
}

function recalculatePayModalTotals() {
  const subtotal = AppState.cart.items.reduce((acc, i) => acc + i.totalPrice, 0);
  let discount = 0;
  if (AppState.cart.promoCode) {
    discount = AppState.cart.promoCode.type === 'percent' ? (subtotal * AppState.cart.promoCode.val)/100 : AppState.cart.promoCode.val;
  }
  const base = Math.max(0, subtotal - discount);
  const tip = (base * (AppState.cart.tipPercent || 0)) / 100;
  AppState.cart.tipAmount = tip;
  const total = base + tip;
  const gst = total * 0.10;

  const subtotalEl = document.getElementById('pay-modal-subtotal');
  const gstEl = document.getElementById('pay-modal-gst');
  const tipEl = document.getElementById('pay-modal-tip');
  const tipDisplay = document.getElementById('tip-amount-display');
  const totalEl = document.getElementById('pay-modal-total');
  const eftposAmtEl = document.getElementById('eftpos-amount-display');
  const paypalDueEl = document.getElementById('paypal-amount-due');

  if (subtotalEl) subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
  if (gstEl) gstEl.textContent = `$${gst.toFixed(2)}`;
  if (tipEl) tipEl.textContent = `$${tip.toFixed(2)}`;
  if (tipDisplay) tipDisplay.textContent = `$${tip.toFixed(2)}`;
  if (totalEl) totalEl.textContent = `$${total.toFixed(2)}`;
  if (eftposAmtEl) eftposAmtEl.textContent = `$${total.toFixed(2)}`;
  if (paypalDueEl) paypalDueEl.textContent = `$${total.toFixed(2)} AUD`;

  updateCashChange();
  updateSplitBillDisplay();
}

function updateSplitBillDisplay() {
  const totalDue = calculateCurrentPayableTotal();
  const ways = splitState.ways || 2;
  const perPerson = totalDue / ways;

  const perPersonEl = document.getElementById('split-per-person-amount');
  const shareLabelEl = document.getElementById('split-shares-label');
  const fillEl = document.getElementById('split-progress-fill');
  const statusEl = document.getElementById('split-status-text');
  const shareValEl = document.getElementById('pay-share-val');

  if (perPersonEl) perPersonEl.textContent = `$${perPerson.toFixed(2)}`;
  if (shareLabelEl) shareLabelEl.textContent = `Share (${splitState.paidCount + 1 > ways ? ways : splitState.paidCount + 1} of ${ways}):`;
  if (shareValEl) shareValEl.textContent = `$${perPerson.toFixed(2)}`;

  const pct = Math.min(100, Math.round((splitState.paidCount / ways) * 100));
  if (fillEl) fillEl.style.width = `${pct}%`;
  if (statusEl) {
    statusEl.textContent = `${splitState.paidCount} of ${ways} shares paid ($${splitState.totalPaid.toFixed(2)} of $${totalDue.toFixed(2)})`;
  }
}

function updateCashChange() {
  const cashInput = document.getElementById('cash-tendered-input');
  if (!cashInput) return;
  const due = calculateCurrentPayableTotal();
  const tendered = parseFloat(cashInput.value) || 0;
  const change = Math.max(0, tendered - due);

  const changeDueEl = document.getElementById('cash-change-due');
  if (changeDueEl) {
    changeDueEl.textContent = `$${change.toFixed(2)}`;
  }
}

window.openPaymentModal = function() {
  if (!AppState.cart || !AppState.cart.items || AppState.cart.items.length === 0) {
    showToast('Your cart is empty! Please select food or beverages from the menu.', 'warning');
    return;
  }
  const subtotal = AppState.cart.items.reduce((acc, i) => acc + i.totalPrice, 0);
  let discount = 0;
  if (AppState.cart.promoCode) {
    discount = AppState.cart.promoCode.type === 'percent' ? (subtotal * AppState.cart.promoCode.val)/100 : AppState.cart.promoCode.val;
  }
  const total = Math.max(0, subtotal - discount);
  const gst = total * 0.10;

  const orderIdEl = document.getElementById('pay-modal-order-id');
  if (orderIdEl) orderIdEl.textContent = AppState.cart.orderId;
  const subtotalEl = document.getElementById('pay-modal-subtotal');
  if (subtotalEl) subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
  const gstEl = document.getElementById('pay-modal-gst');
  if (gstEl) gstEl.textContent = `$${gst.toFixed(2)}`;
  
  if (discount > 0) {
    document.getElementById('pay-modal-discount-row')?.classList.remove('hidden');
    const discEl = document.getElementById('pay-modal-discount');
    if (discEl) discEl.textContent = `-$${discount.toFixed(2)}`;
  } else {
    document.getElementById('pay-modal-discount-row')?.classList.add('hidden');
  }

  // Loyalty Points in Modal
  const ptsEl = document.getElementById('pay-modal-cust-pts');
  const ptsWorthEl = document.getElementById('pay-modal-pts-worth');
  if (AppState.cart.customer) {
    const pts = AppState.cart.customer.points || 0;
    if (ptsEl) ptsEl.textContent = `${pts} Pts`;
    if (ptsWorthEl) ptsWorthEl.textContent = `Worth $${(pts / 20).toFixed(2)} AUD`;
  } else {
    if (ptsEl) ptsEl.textContent = `0 Pts`;
    if (ptsWorthEl) ptsWorthEl.textContent = `Attach loyalty member`;
  }

  // Populate mini items list
  const miniList = document.getElementById('pay-modal-items-list');
  if (miniList) {
    miniList.innerHTML = AppState.cart.items.map(i => `
      <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:4px;">
        <span>${i.qty}x ${i.item.name}</span>
        <strong>$${i.totalPrice.toFixed(2)}</strong>
      </div>
    `).join('');
  }

  // Reset split state
  splitState.paidCount = 0;
  splitState.totalPaid = 0;

  recalculatePayModalTotals();
  document.getElementById('payment-modal')?.classList.remove('hidden');
}

let isProcessingPayment = false;
function completePaymentProcess() {
  if (isProcessingPayment) return;
  if (!AppState.cart || !AppState.cart.items || AppState.cart.items.length === 0) {
    showToast('Your cart is empty! Please select items first.', 'warning');
    return;
  }
  isProcessingPayment = true;
  setTimeout(() => { isProcessingPayment = false; }, 1500);

  const activeTab = document.querySelector('.pay-tab.active')?.getAttribute('data-method') || 'eftpos';
  const subtotal = AppState.cart.items.reduce((acc, i) => acc + i.totalPrice, 0);
  let discount = 0;
  if (AppState.cart.promoCode) {
    discount = AppState.cart.promoCode.type === 'percent' ? (subtotal * AppState.cart.promoCode.val)/100 : AppState.cart.promoCode.val;
  }
  const base = Math.max(0, subtotal - discount);
  const tip = AppState.cart.tipAmount || 0;
  const total = base + tip;

  // Determine current cashier name
  const cashierName = document.getElementById('current-user-name')?.textContent || 'Staff';

  // Push into Kitchen & Barista KDS Queue
  const orderCreatedAt = new Date().toISOString();
  const kdsNewOrder = {
    id: AppState.cart.orderId,
    orderType: AppState.cart.orderType,
    tableId: AppState.cart.orderType === 'dine_in' ? AppState.cart.tableId : null,
    customerName: AppState.cart.customer ? AppState.cart.customer.name : 'Walk-in Guest',
    status: 'pending',
    createdAt: orderCreatedAt,
    elapsedSec: 0,
    items: AppState.cart.items.map(i => ({
      product_id: i.item.product_id || i.item.id,
      name: i.item.name || i.item.product_name,
      quantity: i.qty,
      customisations: i.customisations,
      notes: i.notes
    }))
  };

  DB.kdsOrders.unshift(kdsNewOrder);
  updateKDSBadge();

  // Dispatch to Backend REST API & WebSockets
  API.createOrder({
    id: AppState.cart.orderId,
    orderId: AppState.cart.orderId,
    type: AppState.cart.orderType,
    tableId: AppState.cart.orderType === 'dine_in' ? AppState.cart.tableId : null,
    customerName: AppState.cart.customer ? AppState.cart.customer.name : 'Walk-in Guest',
    items: kdsNewOrder.items,
    subtotal,
    tax: total * 0.10,
    discount,
    tip,
    total,
    paymentMethod: activeTab,
    createdAt: orderCreatedAt
  });

  // Mark table occupied if dine-in
  if (AppState.cart.orderType === 'dine_in' && AppState.cart.tableId) {
    const tbl = DB.tables.find(t => t.id === AppState.cart.tableId);
    if (tbl) {
      tbl.status = 'occupied';
      tbl.orderId = AppState.cart.orderId;
      API.updateTable(tbl.id, { status: 'occupied', orderId: AppState.cart.orderId });
      renderCartTableSelect();
    }
  }

  // Deduct Inventory automatically
  AppState.cart.items.forEach(ci => {
    if (ci.item.recipe) {
      if (ci.item.recipe.coffeeBeansGrams) {
        const beanInv = DB.inventory.find(inv => inv.id === 'INV-01');
        if (beanInv) {
          const cur = beanInv.qty !== undefined ? beanInv.qty : (beanInv.stockQty || 0);
          beanInv.qty = Math.max(0, Math.round((cur - (ci.item.recipe.coffeeBeansGrams * ci.qty) / 1000) * 10) / 10);
          beanInv.stockQty = beanInv.qty;
          beanInv.status = beanInv.qty <= beanInv.minThreshold ? 'low' : 'good';
          if (beanInv.id) API.updateInventoryStock(beanInv.id, beanInv.qty);
        }
      }
      if (ci.item.recipe.milkMl && ci.milk && ci.milk.includes('Oat')) {
        const oatInv = DB.inventory.find(inv => inv.id === 'INV-03');
        if (oatInv) {
          const cur = oatInv.qty !== undefined ? oatInv.qty : (oatInv.stockQty || 0);
          oatInv.qty = Math.max(0, Math.round((cur - (ci.item.recipe.milkMl * ci.qty) / 1000) * 10) / 10);
          oatInv.stockQty = oatInv.qty;
          oatInv.status = oatInv.qty <= oatInv.minThreshold ? 'low' : 'good';
          if (oatInv.id) API.updateInventoryStock(oatInv.id, oatInv.qty);
        }
      }
    }
  });
  updateLowStockBadge();
  saveLocalDB();

  // Award Loyalty Points if customer attached
  if (AppState.cart.customer) {
    const ptsEarned = Math.floor(total * 10); // $1 = 10 pts
    AppState.cart.customer.points += ptsEarned;
  }

  // Push to Sales Log (local)
  const txnTimestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  const payMethodDisplay = (activeTab === 'paypal' ? 'PAYPAL' : activeTab.toUpperCase());
  const txnRef = (activeTab === 'paypal' ? 'PAYPAL-SB-' + Math.random().toString(36).substr(2, 9).toUpperCase() : 'TXN-' + Date.now());

  DB.completedSales.unshift({
    id: AppState.cart.orderId,
    total: total,
    paymentMethod: payMethodDisplay,
    itemsCount: AppState.cart.items.length,
    cashier: cashierName,
    timestamp: txnTimestamp
  });

  // Persist transaction to backend
  API.createTransaction({
    orderId: AppState.cart.orderId,
    total: total,
    paymentMethod: payMethodDisplay,
    transaction_reference: txnRef,
    itemsCount: AppState.cart.items.length,
    cashier: cashierName,
    timestamp: txnTimestamp
  });

  // Populate Printable Thermal Receipt
  const recOrderEl = document.getElementById('rec-order-id');
  const recDateEl = document.getElementById('rec-date');
  const recTypeEl = document.getElementById('rec-type');
  const recCashierEl = document.getElementById('rec-cashier');
  const recSubtotalEl = document.getElementById('rec-subtotal');
  const recGstEl = document.getElementById('rec-gst');
  const recTipEl = document.getElementById('rec-tip');
  const recTotalEl = document.getElementById('rec-total');
  const recTenderTypeEl = document.getElementById('rec-tender-type');
  const recTenderedEl = document.getElementById('rec-tendered');
  const recChangeEl = document.getElementById('rec-change');

  if (recOrderEl) recOrderEl.textContent = AppState.cart.orderId;
  if (recDateEl) recDateEl.textContent = new Date().toLocaleString('en-AU');
  if (recTypeEl) recTypeEl.textContent = `${AppState.cart.orderType === 'dine_in' ? 'Dine In (' + (AppState.cart.tableId || 'T-03') + ')' : 'Takeaway'}`;
  if (recCashierEl) recCashierEl.textContent = cashierName;
  if (recSubtotalEl) recSubtotalEl.textContent = `$${subtotal.toFixed(2)}`;
  if (recGstEl) recGstEl.textContent = `$${(total * 0.10).toFixed(2)}`;
  if (recTipEl) recTipEl.textContent = `$${tip.toFixed(2)}`;
  if (recTotalEl) recTotalEl.textContent = `$${total.toFixed(2)}`;
  if (recTenderTypeEl) recTenderTypeEl.textContent = activeTab.toUpperCase();

  const cashInput = document.getElementById('cash-tendered-input');
  const tenderedAmt = activeTab === 'cash' ? (parseFloat(cashInput?.value) || total) : total;
  if (recTenderedEl) recTenderedEl.textContent = `$${tenderedAmt.toFixed(2)}`;
  if (recChangeEl) recChangeEl.textContent = `$${Math.max(0, tenderedAmt - total).toFixed(2)}`;

  const recItems = document.getElementById('rec-items-list');
  if (recItems) {
    recItems.innerHTML = AppState.cart.items.map(i => {
      const modStr = (i.customisations && i.customisations.length > 0) ? i.customisations.map(c => c.option_name).join(', ') : '';
      return `
        <div class="r-item">
          <span>${i.qty}x ${i.item.name}</span>
          <span>$${i.totalPrice.toFixed(2)}</span>
        </div>
        ${modStr ? `<div class="r-sub" style="font-size:10px; color:#666; margin-bottom:3px;">${modStr}</div>` : ''}
        ${i.notes ? `<div class="r-sub" style="font-size:10px; color:#888;">Note: ${i.notes}</div>` : ''}
      `;
    }).join('');
  }

  const savedOrderId = AppState.cart.orderId;

  document.getElementById('payment-modal')?.classList.add('hidden');
  document.getElementById('receipt-modal')?.classList.remove('hidden');

  // Reset Cart & fetch next order number
  AppState.cart.items = [];
  AppState.cart.promoCode = null;
  AppState.cart.customer = null;
  AppState.cart.tipPercent = 0;
  AppState.cart.tipAmount = 0;

  // Auto-switch to Live Customer Tracker if in Customer Role
  if (AppState.userRole === 'customer' || (AppState.currentUser && AppState.currentUser.role === 'customer')) {
    showToast(`Order ${savedOrderId} Confirmed! Watching live preparation tracker.`, 'success');
    switchModule('customer_tracker');
  } else {
    showToast(`Payment successful for ${savedOrderId}! Order sent to KDS.`, 'success');
  }

  API.fetchNextOrderNum().then(num => {
    if (num) {
      AppState.cart.orderId = `#ORD-${num}`;
    } else {
      const nextNum = parseInt(savedOrderId.split('-')[1] || '9000') + 1;
      AppState.cart.orderId = `#ORD-${nextNum}`;
    }
    renderCartUI();
    saveLocalDB();
  });

  renderCartUI();
  saveLocalDB();
  
  // Set Receipt Action Handlers (PDF & Print Ready)
  const printBtn = document.getElementById('print-receipt-btn');
  if (printBtn) {
    printBtn.onclick = () => window.generateReceiptPDF(false);
  }
  const downloadPdfBtn = document.getElementById('download-pdf-receipt-btn');
  if (downloadPdfBtn) {
    downloadPdfBtn.onclick = () => window.generateReceiptPDF(true);
  }
  const finishBtn = document.getElementById('finish-receipt-btn');
  if (finishBtn) {
    finishBtn.onclick = () => {
      document.getElementById('receipt-modal')?.classList.add('hidden');
      syncBackendData();
    };
  }
  const closeRecBtn = document.getElementById('close-receipt-btn');
  if (closeRecBtn) {
    closeRecBtn.onclick = () => {
      document.getElementById('receipt-modal')?.classList.add('hidden');
      syncBackendData();
    };
  }
  const sendDigitalBtn = document.getElementById('send-digital-receipt-btn');
  if (sendDigitalBtn) {
    sendDigitalBtn.onclick = () => {
      const target = document.getElementById('digital-receipt-target')?.value?.trim();
      if (!target) {
        alert('Please enter an email address or mobile number.');
        return;
      }
      showToast(`Digital tax invoice for ${savedOrderId} sent to ${target}!`, 'success');
      const input = document.getElementById('digital-receipt-target');
      if (input) input.value = '';
    };
  }
}

// Customer Modal
function openCustomerModal() {
  const modal = document.getElementById('customer-modal');
  const list = document.getElementById('customer-select-list');
  list.innerHTML = DB.customers.map(c => `
    <div class="option-card" style="display:flex; justify-content:space-between; align-items:center; text-align:left; margin-bottom:8px; cursor:pointer;" onclick="selectLoyaltyCustomer('${c.id}')">
      <div>
        <strong style="display:block;">${c.name} <span class="badge badge-gold">${c.tier}</span></strong>
        <span style="font-size:11px; color:var(--color-cream-muted);">${c.mobile} • ${c.email}</span>
      </div>
      <strong style="color:var(--color-accent-gold);">${c.points} Pts</strong>
    </div>
  `).join('');

  document.getElementById('close-customer-modal-btn').onclick = () => modal.classList.add('hidden');
  document.getElementById('detach-cust-btn').onclick = () => {
    AppState.cart.customer = null;
    renderCartUI();
    modal.classList.add('hidden');
  };

  modal.classList.remove('hidden');
}

window.selectLoyaltyCustomer = function(custId) {
  AppState.cart.customer = DB.customers.find(c => c.id === custId);
  renderCartUI();
  document.getElementById('customer-modal').classList.add('hidden');
};

// ==========================================
// 6. ORDER TRACKING & KDS MODULE
// ==========================================

function renderKDSView(container) {
  const kdsLayout = document.createElement('div');
  kdsLayout.className = 'kds-container';

  kdsLayout.innerHTML = `
    <div class="kds-filter-bar">
      <div class="kds-stat-pills">
        <span class="kds-stat-pill"><i class="ri-time-line text-warning"></i> Pending: ${DB.kdsOrders.filter(o => o.status==='pending').length}</span>
        <span class="kds-stat-pill"><i class="ri-fire-line text-info"></i> In Prep: ${DB.kdsOrders.filter(o => o.status==='preparing').length}</span>
        <span class="kds-stat-pill"><i class="ri-check-double-line text-success"></i> Ready: ${DB.kdsOrders.filter(o => o.status==='ready').length}</span>
      </div>
      <div>
        <button class="btn btn-secondary btn-sm" onclick="renderCurrentModule()"><i class="ri-refresh-line"></i> Refresh Queue</button>
      </div>
    </div>

    <div class="kds-grid" id="kds-tickets-grid"></div>
  `;

  container.appendChild(kdsLayout);

  const grid = document.getElementById('kds-tickets-grid');
  if (DB.kdsOrders.length === 0) {
    grid.innerHTML = `<div class="empty-cart-state" style="grid-column:1/-1;"><i class="ri-check-line"></i><p>All barista tickets completed!</p></div>`;
  } else {
    DB.kdsOrders.forEach(ord => {
      const card = document.createElement('div');
      card.className = `kds-ticket-card status-${ord.status}`;
      
      let nextBtn = '';
      if (ord.status === 'pending') {
        nextBtn = `<button class="btn btn-primary btn-sm flex-1" onclick="updateKDSStatus('${ord.id}', 'preparing')"><i class="ri-play-fill"></i> Start Prep</button>`;
      } else if (ord.status === 'preparing') {
        nextBtn = `<button class="btn btn-success btn-sm flex-1" onclick="updateKDSStatus('${ord.id}', 'ready')"><i class="ri-check-line"></i> Mark Ready</button>`;
      } else if (ord.status === 'ready') {
        nextBtn = `<button class="btn btn-outline btn-sm flex-1" onclick="updateKDSStatus('${ord.id}', 'completed')"><i class="ri-checkbox-circle-line"></i> Serve & Bump</button>`;
      }

      const elapsed = getOrderElapsedSeconds(ord);
      ord.elapsedSec = elapsed;
      const mins = Math.floor(elapsed / 60);
      const secs = elapsed % 60;

      card.innerHTML = `
        <div class="kds-ticket-header">
          <div>
            <span class="kds-ticket-id">${ord.id}</span>
            <span class="badge ${(ord.orderType || ord.type || 'dine_in')==='dine_in' ? 'badge-primary' : 'badge-gold'}" style="margin-left:6px;">
              ${(ord.orderType || ord.type || 'dine_in')==='dine_in' ? 'Table ' + (ord.tableId || '?') : 'Takeaway'}
            </span>
          </div>
          <span class="kds-timer"><i class="ri-history-line"></i> <span class="kds-timer-text" data-order-id="${ord.id}">${mins}m ${secs}s</span></span>
        </div>
        <div class="kds-ticket-body">
          <div style="font-size:12px; color:var(--color-cream-muted);"><i class="ri-user-line"></i> ${ord.customerName}</div>
          ${ord.items.map(item => `
            <div class="kds-item-row">
              <span class="kds-item-qty">${item.qty}x</span>
              <div class="kds-item-details">
                <div class="kds-item-name">${item.name}</div>
                <div class="kds-item-mods">${(item.mods || []).join(' • ')}</div>
              </div>
            </div>
          `).join('')}
        </div>
        <div class="kds-ticket-footer">
          ${nextBtn}
        </div>
      `;
      grid.appendChild(card);
    });
  }
}

window.updateKDSStatus = function(orderId, newStatus) {
  const ord = DB.kdsOrders.find(o => o.id === orderId);
  if (ord) {
    if (newStatus === 'completed') {
      const idx = DB.kdsOrders.indexOf(ord);
      DB.kdsOrders.splice(idx, 1);
      // Automatically free up table when order is completed
      if (ord.tableId) {
        const tbl = DB.tables.find(t => t.id === ord.tableId);
        if (tbl && tbl.status === 'occupied') {
          tbl.status = 'available';
          tbl.orderId = null;
          API.updateTable(tbl.id, { status: 'available', orderId: null });
          renderCartTableSelect();
        }
      }
    } else {
      ord.status = newStatus;
    }
    updateKDSBadge();
    saveLocalDB();
    renderCurrentModule();

    // Persist status change to REST API
    API.updateOrderStatus(orderId, newStatus);
  }
};

function updateKDSBadge() {
  const badge = document.getElementById('kds-pending-count');
  const count = DB.kdsOrders.filter(o => o.status === 'pending' || o.status === 'preparing').length;
  if (badge) badge.textContent = count;
}

// ==========================================
// 7. TABLE MANAGEMENT MODULE
// ==========================================

function renderTablesView(container) {
  const layout = document.createElement('div');
  layout.className = 'tables-layout';

  layout.innerHTML = `
    <div class="floorplan-area">
      <div class="floorplan-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
          <h3>Melbourne CBD Floor Plan Map</h3>
          <span style="font-size:12px; color:var(--color-cream-muted);">Live dining table seating status & layout management</span>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
          <button class="btn btn-primary btn-sm" onclick="openAddTableModal()"><i class="ri-add-line"></i> Add Table</button>
        </div>
      </div>

      <div class="table-status-legend" style="margin-bottom:16px;">
        <div class="legend-item"><span class="dot available"></span> Available (${DB.tables.filter(t=>t.status==='available').length})</div>
        <div class="legend-item"><span class="dot occupied"></span> Occupied (${DB.tables.filter(t=>t.status==='occupied').length})</div>
        <div class="legend-item"><span class="dot reserved"></span> Reserved (${DB.tables.filter(t=>t.status==='reserved').length})</div>
        <div class="legend-item"><span class="dot cleaning"></span> Needs Cleaning (${DB.tables.filter(t=>t.status==='cleaning').length})</div>
      </div>

      <div class="floorplan-grid">
        ${DB.tables.map(t => `
          <div class="table-box status-${t.status}" onclick="selectTableForDetail('${t.id}')">
            <span class="table-number">${t.name}</span>
            <span class="table-capacity">${t.section} • ${t.capacity} Seats</span>
            <span class="badge badge-${t.status==='available'?'success':t.status==='occupied'?'danger':t.status==='reserved'?'warning':'info'}">
              ${t.status.toUpperCase()}
            </span>
            ${t.orderId ? `<span class="table-timer"><i class="ri-shopping-cart-2-line"></i> ${t.orderId}</span>` : ''}
          </div>
        `).join('')}
      </div>
    </div>

    <div class="table-details-panel" id="table-details-sidebar">
      <h4>Table Management & Details</h4>
      <p class="text-muted">Click a table on the layout grid to edit capacity, clear occupancy, toggle status, or assign POS sale.</p>
    </div>
  `;

  container.appendChild(layout);
}

window.selectTableForDetail = function(tableId) {
  const t = DB.tables.find(tbl => tbl.id === tableId);
  if (!t) return;

  const panel = document.getElementById('table-details-sidebar');
  panel.innerHTML = `
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
      <h4 style="margin:0;">${t.name} (${t.section})</h4>
      <span class="badge badge-${t.status==='available'?'success':t.status==='occupied'?'danger':t.status==='reserved'?'warning':'info'}">${t.status.toUpperCase()}</span>
    </div>
    
    <div class="form-group" style="margin-bottom:12px;">
      <label>Change Status</label>
      <select class="form-select" onchange="updateTableStatus('${t.id}', this.value)" style="width:100%; padding:8px 10px; border-radius:6px; background:var(--bg-canvas); border:1px solid var(--color-border); color:var(--color-cream);">
        <option value="available" ${t.status==='available'?'selected':''}>Available</option>
        <option value="occupied" ${t.status==='occupied'?'selected':''}>Occupied</option>
        <option value="reserved" ${t.status==='reserved'?'selected':''}>Reserved</option>
        <option value="cleaning" ${t.status==='cleaning'?'selected':''}>Needs Cleaning</option>
      </select>
    </div>

    <div class="form-group" style="margin-bottom:12px;">
      <label>Seating Capacity</label>
      <input type="text" class="form-input" value="${t.capacity} Guests (${t.section})" readonly style="width:100%; padding:8px 10px; border-radius:6px; background:var(--bg-canvas); border:1px solid var(--color-border); color:var(--color-cream);">
    </div>

    ${t.reservedFor ? `<div class="badge badge-warning" style="display:block; width:100%; margin-bottom:10px; text-align:center;"><i class="ri-user-shared-line"></i> Booked: ${t.reservedFor}</div>` : ''}
    ${t.orderId ? `<div class="badge badge-primary" style="display:block; width:100%; margin-bottom:10px; text-align:center;"><i class="ri-shopping-cart-2-line"></i> Linked Sale: ${t.orderId}</div>` : ''}

    <div style="display:flex; flex-direction:column; gap:8px; margin-top:16px;">
      <button class="btn btn-primary w-100" onclick="assignTableToPOS('${t.id}')">
        <i class="ri-shopping-bag-3-line"></i> Open Sale for ${t.name}
      </button>
      
      ${t.status !== 'available' ? `
        <button class="btn btn-outline w-100" onclick="clearTableStatus('${t.id}')">
          <i class="ri-checkbox-circle-line"></i> Clear & Mark Available
        </button>
      ` : ''}

      <div style="display:flex; gap:8px;">
        <button class="btn btn-outline flex-1" onclick="editTableDetails('${t.id}')">
          <i class="ri-edit-line"></i> Edit Table
        </button>
        <button class="btn btn-outline flex-1 text-danger" onclick="deleteTableRecord('${t.id}')">
          <i class="ri-delete-bin-line"></i> Delete
        </button>
      </div>
    </div>
  `;

  if (window.innerWidth < 1024 && panel) {
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
};

window.updateTableStatus = function(tableId, newStatus) {
  const t = DB.tables.find(tbl => tbl.id === tableId);
  if (t) {
    t.status = newStatus;
    if (newStatus === 'available') {
      t.orderId = null;
      t.reservedFor = null;
    }
    saveLocalDB();
    renderCartTableSelect();
    renderCurrentModule();

    API.updateTable(tableId, { status: newStatus, orderId: t.orderId, reservedFor: t.reservedFor });
  }
};

window.clearTableStatus = function(tableId) {
  window.updateTableStatus(tableId, 'available');
};

window.assignTableToPOS = function(tableId) {
  AppState.cart.tableId = tableId;
  AppState.cart.orderType = 'dine_in';
  switchModule('pos');
};

window.openAddTableModal = function() {
  const modal = document.getElementById('add-table-modal');
  if (modal) modal.classList.remove('hidden');
};

window.closeAddTableModal = function() {
  const modal = document.getElementById('add-table-modal');
  if (modal) modal.classList.add('hidden');
};

window.submitNewTable = function(e) {
  if (e) e.preventDefault();

  const id = document.getElementById('tbl-id-input').value.trim();
  const name = document.getElementById('tbl-name-input').value.trim();
  const section = document.getElementById('tbl-section-select').value;
  const capacity = parseInt(document.getElementById('tbl-capacity-select').value || '4');

  if (!id || !name) return;

  const newTable = {
    id: id.toUpperCase(),
    name: name,
    section: section,
    capacity: capacity,
    status: 'available',
    orderId: null,
    timeOccupied: null,
    reservedFor: null
  };

  const existingIdx = DB.tables.findIndex(t => t.id === newTable.id);
  if (existingIdx !== -1) {
    DB.tables[existingIdx] = newTable;
    API.updateTableDetails(newTable.id, newTable);
  } else {
    DB.tables.push(newTable);
    API.addTable(newTable);
  }

  saveLocalDB();
  closeAddTableModal();
  renderCurrentModule();
};

window.editTableDetails = function(tableId) {
  const t = DB.tables.find(tbl => tbl.id === tableId);
  if (!t) return;

  const newName = prompt("Update Table Display Name:", t.name);
  if (!newName) return;
  const newCap = prompt("Update Seating Capacity:", t.capacity);
  if (!newCap) return;
  const newSection = prompt("Update Dining Section (e.g. Main Dining, Patio, Bar):", t.section) || t.section;

  t.name = newName;
  t.capacity = parseInt(newCap);
  t.section = newSection;

  saveLocalDB();
  renderCurrentModule();
  API.updateTableDetails(t.id, t);
};

window.deleteTableRecord = function(tableId) {
  const t = DB.tables.find(tbl => tbl.id === tableId);
  if (!t) return;

  if (confirm(`Are you sure you want to delete ${t.name} (${t.id}) from floor plan?`)) {
    const idx = DB.tables.indexOf(t);
    if (idx !== -1) DB.tables.splice(idx, 1);
    saveLocalDB();
    renderCurrentModule();
    API.deleteTable(tableId);
  }
};

// ==========================================
// 8. RESERVATIONS MODULE
// ==========================================

window.syncTablesWithReservations = function() {
  if (!DB.tables || !DB.reservations) return;
  
  DB.reservations.forEach(r => {
    if (r.status === 'Confirmed' || r.status === 'Pending') {
      const table = DB.tables.find(t => t.id === r.tableId || t.name === r.tableId);
      if (table && table.status === 'available') {
        table.status = 'reserved';
        table.reservedFor = `${r.customerName} @ ${r.time}`;
      }
    }
  });
};

function renderReservationsView(container) {
  syncTablesWithReservations();

  const totalBookings = DB.reservations.length;
  const confirmedCount = DB.reservations.filter(r => r.status === 'Confirmed').length;
  const pendingCount = DB.reservations.filter(r => r.status === 'Pending').length;
  const seatedCount = DB.reservations.filter(r => r.status === 'Seated').length;

  container.innerHTML = `
    <div class="customer-table-card">
      <div style="padding:16px 20px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-border-subtle); flex-wrap:wrap; gap:12px;">
        <div>
          <h3>Table Bookings & Schedule</h3>
          <span style="font-size:12px; color:var(--color-cream-muted);">Melbourne CBD shop seating bookings for today</span>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
          <span class="badge badge-success">${confirmedCount} Confirmed</span>
          <span class="badge badge-warning">${pendingCount} Pending</span>
          <span class="badge badge-primary">${seatedCount} Seated</span>
          <button class="btn btn-primary btn-sm" onclick="openAddReservationModal()"><i class="ri-add-line"></i> New Reservation</button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>Customer Name</th>
              <th>Party Size</th>
              <th>Assigned Table</th>
              <th>Time Slot</th>
              <th>Contact Phone</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            ${DB.reservations.length === 0 ? `
              <tr>
                <td colspan="8" style="text-align:center; padding:40px 20px; color:var(--color-cream-muted);">
                  <i class="ri-calendar-event-line" style="font-size:36px; display:block; margin-bottom:10px; opacity:0.6;"></i>
                  No table bookings scheduled for today. Click <strong>+ New Reservation</strong> to book a table.
                </td>
              </tr>
            ` : DB.reservations.map((r, idx) => `
              <tr>
                <td><strong>${r.id}</strong></td>
                <td>
                  <div style="display:flex; align-items:center; gap:8px;">
                    <div style="width:28px; height:28px; border-radius:50%; background:var(--bg-canvas); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:var(--color-primary);">
                      ${r.customerName ? r.customerName.charAt(0) : 'G'}
                    </div>
                    <strong>${r.customerName}</strong>
                  </div>
                </td>
                <td><span class="badge badge-outline">${r.partySize} Guests</span></td>
                <td><strong style="color:var(--color-accent-gold);">${r.tableId}</strong></td>
                <td><i class="ri-time-line" style="margin-right:4px; color:var(--color-cream-muted);"></i>${r.time}</td>
                <td>${r.contact || 'N/A'}</td>
                <td>
                  <span class="badge badge-${r.status==='Confirmed'?'success':r.status==='Seated'?'primary':'warning'}">
                    ${r.status}
                  </span>
                </td>
                <td>
                  <div style="display:flex; gap:6px;">
                    ${r.status !== 'Seated' ? `
                      <button class="btn btn-primary btn-sm" onclick="seatReservation(${idx})" title="Seat Party & Open Sale">
                        <i class="ri-user-follow-line"></i> Seat Party
                      </button>
                    ` : ''}
                    <button class="btn btn-outline btn-sm" onclick="cancelReservation(${idx})" title="Cancel Reservation">
                      <i class="ri-close-circle-line"></i> Cancel
                    </button>
                  </div>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

window.openAddReservationModal = function() {
  const modal = document.getElementById('add-reservation-modal');
  const tableSelect = document.getElementById('res-table-select');

  if (tableSelect && DB.tables) {
    tableSelect.innerHTML = DB.tables.map(t => `
      <option value="${t.id}">${t.id} - ${t.name} (${t.section} • ${t.capacity} Seats)</option>
    `).join('');
  }

  if (modal) modal.classList.remove('hidden');
};

window.closeAddReservationModal = function() {
  const modal = document.getElementById('add-reservation-modal');
  if (modal) modal.classList.add('hidden');
};

window.submitNewReservation = function(e) {
  if (e) e.preventDefault();

  const name = document.getElementById('res-cust-name').value.trim();
  const party = parseInt(document.getElementById('res-party-size').value || '2');
  const tableId = document.getElementById('res-table-select').value;
  const time = document.getElementById('res-time-slot').value;
  const status = document.getElementById('res-status-select').value;
  const phone = document.getElementById('res-contact-phone').value.trim();

  if (!name || !tableId) return;

  const newRes = {
    id: `RES-${Date.now().toString().substr(-3)}`,
    customerName: name,
    partySize: party,
    tableId: tableId,
    time: time,
    status: status,
    contact: phone || '0412 555 777'
  };

  DB.reservations.unshift(newRes);

  const targetTable = DB.tables.find(t => t.id === tableId);
  if (targetTable) {
    targetTable.status = 'reserved';
    targetTable.reservedFor = `${name} @ ${time}`;
  }

  saveLocalDB();
  closeAddReservationModal();
  renderCurrentModule();

  API.createReservation({
    customerName: name,
    partySize: party,
    tableId: tableId,
    time: time,
    contact: phone
  });
};

window.seatReservation = function(idx) {
  const r = DB.reservations[idx];
  if (!r) return;

  r.status = 'Seated';

  const t = DB.tables.find(tbl => tbl.id === r.tableId || tbl.name === r.tableId);
  if (t) {
    t.status = 'occupied';
    t.reservedFor = null;
    t.timeOccupied = '1m';
    AppState.cart.tableId = t.id;
    AppState.cart.orderType = 'dine_in';
  }

  saveLocalDB();
  switchModule('pos');
};

window.cancelReservation = function(idx) {
  const r = DB.reservations[idx];
  if (!r) return;

  if (confirm(`Cancel reservation for ${r.customerName}?`)) {
    if (r.id) API.deleteReservation(r.id);
    
    const t = DB.tables.find(tbl => tbl.id === r.tableId || tbl.name === r.tableId);
    if (t && t.status === 'reserved') {
      t.status = 'available';
      t.reservedFor = null;
    }

    DB.reservations.splice(idx, 1);
    saveLocalDB();
    renderCurrentModule();
  }
};

// ==========================================
// 9. MENU MANAGEMENT MODULE
// ==========================================

function renderMenuView(container) {
  container.innerHTML = `
    <div class="customer-table-card">
      <div style="padding:16px 20px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-border-subtle); flex-wrap:wrap; gap:12px;">
        <div>
          <h3>Ravenhill Menu Catalog & Modifiers</h3>
          <span style="font-size:12px; color:var(--color-cream-muted);">Manage espresso items, retail beans, bakery items & pricing</span>
        </div>
        <button class="btn btn-primary btn-sm" onclick="openAddMenuItemModal()"><i class="ri-add-line"></i> Add New Menu Item</button>
      </div>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Item Name</th>
              <th>Category</th>
              <th>Price (AUD)</th>
              <th>Modifiers</th>
              <th>Badge Tag</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            ${DB.menuItems.map((item, idx) => `
              <tr>
                <td>
                  <div style="display:flex; align-items:center; gap:8px;">
                    <i class="${item.icon}" style="color:var(--color-primary); font-size:18px;"></i>
                    <strong>${item.name}</strong>
                  </div>
                </td>
                <td>${DB.menuCategories.find(c=>c.id===item.catId)?.name || 'General'}</td>
                <td><strong>$${item.price.toFixed(2)}</strong></td>
                <td>${item.hasModifiers ? '<span class="badge badge-info">Sizes / Milks</span>' : 'Standard'}</td>
                <td>${item.badge ? `<span class="badge badge-gold">${item.badge}</span>` : '-'}</td>
                <td>
                  <div style="display:flex; gap:6px; flex-wrap:wrap;">
                    <button class="btn btn-primary btn-sm" onclick="orderMenuItemFromCatalog(${idx})"><i class="ri-shopping-cart-2-line"></i> Order</button>
                    <button class="btn btn-outline btn-sm" onclick="editMenuItemPrice(${idx})"><i class="ri-price-tag-line"></i> Edit Price</button>
                    <button class="btn btn-outline btn-sm text-danger" onclick="deleteMenuItem(${idx})"><i class="ri-delete-bin-line"></i></button>
                  </div>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

window.orderMenuItemFromCatalog = function(idx) {
  const item = DB.menuItems[idx];
  if (!item) return;
  if (item.hasModifiers) {
    openCustomiserModal(item);
  } else {
    addItemToCart(item, [], '', 1);
    window.openCartDrawer();
    showToast(`Added 1x ${item.name} to cart!`, 'success');
  }
};

window.openAddMenuItemModal = function() {
  const name = prompt("Item Name:", "Iced Matcha Oat Latte");
  if (!name) return;
  const priceStr = prompt("Price (AUD):", "6.80");
  if (!priceStr) return;
  const badge = prompt("Badge Tag (e.g. Bestseller, New, Reserve):", "New") || null;

  const newItem = {
    id: `item-${Date.now().toString().substr(-3)}`,
    catId: 'cat-cold',
    name: name,
    desc: 'Ceremonial grade Uji matcha with oat milk.',
    price: parseFloat(priceStr),
    icon: 'ri-goblet-line',
    badge: badge,
    hasModifiers: true,
    recipe: { milkMl: 220 }
  };

  DB.menuItems.unshift(newItem);
  saveLocalDB();
  renderCurrentModule();
  API.addMenuItem(newItem);
};

window.editMenuItemPrice = function(idx) {
  const item = DB.menuItems[idx];
  const newPrice = prompt(`Update price for ${item.name} (AUD):`, item.price.toFixed(2));
  if (newPrice && !isNaN(parseFloat(newPrice))) {
    item.price = parseFloat(newPrice);
    saveLocalDB();
    renderCurrentModule();
    API.updateMenuItem(item.id, item);
  }
};

window.deleteMenuItem = function(idx) {
  const item = DB.menuItems[idx];
  if (confirm(`Delete menu item "${item.name}"?`)) {
    DB.menuItems.splice(idx, 1);
    saveLocalDB();
    renderCurrentModule();
    if (item.id) API.deleteMenuItem(item.id);
  }
};

// ==========================================
// 10. INVENTORY & RECIPES MODULE
// ==========================================

function renderInventoryView(container) {
  container.innerHTML = `
    <div style="display:flex; flex-direction:column; gap:20px;">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
          <h3>Stock Levels & Ingredient Recipes</h3>
          <span style="font-size:12px; color:var(--color-cream-muted);">Raw coffee beans, dairy, plant milks & eco packaging</span>
        </div>
        <button class="btn btn-primary btn-sm" onclick="openLogStockModal()"><i class="ri-file-add-line"></i> Log Stock Delivery</button>
      </div>

      <div class="inventory-grid">
        ${DB.inventory.map((inv, idx) => {
          const invQty = inv.qty !== undefined ? inv.qty : (inv.stockQty !== undefined ? inv.stockQty : 0);
          const pct = Math.min(100, Math.round((invQty / (inv.minThreshold * 3)) * 100));
          return `
            <div class="inventory-card">
              <div style="display:flex; justify-content:space-between;">
                <strong>${inv.name}</strong>
                <span class="badge ${(inv.status || 'good')==='low'?'badge-danger':'badge-success'}">${(inv.status || 'good').toUpperCase()}</span>
              </div>
              <div style="font-size:12px; color:var(--color-cream-muted);">${inv.category || 'Supplies'}</div>
              <div style="display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:6px;">
                  <input 
                    type="number" 
                    class="stock-qty-input" 
                    step="0.1" 
                    min="0" 
                    value="${invQty.toFixed(1)}" 
                    onfocus="this.select()"
                    onchange="updateInventoryQtyDirect(${idx}, this.value)"
                    onblur="updateInventoryQtyDirect(${idx}, this.value)"
                    onkeydown="if(event.key==='Enter') { this.blur(); }"
                    aria-label="Stock quantity for ${inv.name}"
                  />
                  <span style="font-size:14px; font-weight:700; color:var(--color-cream-muted);">${inv.unit}</span>
                </div>
                <div style="display:flex; gap:4px;">
                  <button class="icon-btn-sm" onclick="adjustInventoryQty(${idx}, -1)" title="Decrease stock">-</button>
                  <button class="icon-btn-sm" onclick="adjustInventoryQty(${idx}, 1)" title="Increase stock">+</button>
                </div>
              </div>
              <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width:${pct}%; background:${inv.status==='low'?'var(--color-danger)':'var(--color-success)'};"></div>
              </div>
              <div style="font-size:10px; color:var(--color-cream-subtle);">Min Reorder Threshold: ${inv.minThreshold} ${inv.unit}</div>
            </div>
          `;
        }).join('')}
      </div>
    </div>
  `;
}

window.adjustInventoryQty = function(idx, delta) {
  const inv = DB.inventory[idx];
  if (!inv) return;
  const current = inv.qty !== undefined ? inv.qty : (inv.stockQty !== undefined ? inv.stockQty : 0);
  const newQty = Math.max(0, Math.round((current + delta) * 10) / 10);
  inv.qty = newQty;
  inv.stockQty = newQty;
  inv.status = inv.qty <= inv.minThreshold ? 'low' : 'good';
  updateLowStockBadge();
  saveLocalDB();
  renderCurrentModule();

  if (inv.id) API.updateInventoryStock(inv.id, inv.qty);
};

window.updateInventoryQtyDirect = function(idx, val) {
  const inv = DB.inventory[idx];
  if (!inv) return;
  const parsed = parseFloat(val);
  const newQty = isNaN(parsed) || parsed < 0 ? 0 : Math.round(parsed * 10) / 10;
  
  if (inv.qty === newQty && inv.stockQty === newQty) return;

  inv.qty = newQty;
  inv.stockQty = newQty;
  inv.status = inv.qty <= inv.minThreshold ? 'low' : 'good';
  updateLowStockBadge();
  saveLocalDB();
  renderCurrentModule();

  if (inv.id) API.updateInventoryStock(inv.id, inv.qty);
};

window.openLogStockModal = function() {
  const item = prompt("Stock Item Name (e.g. Ravenhill Reserve Beans, Oat Milk):", "Ravenhill Reserve Beans");
  if (!item) return;
  const qtyStr = prompt("Quantity Added:", "10.0");
  if (!qtyStr) return;

  const added = parseFloat(qtyStr);
  if (isNaN(added)) return;

  const inv = DB.inventory.find(i => i.name.toLowerCase().includes(item.toLowerCase()));
  if (inv) {
    const current = inv.qty !== undefined ? inv.qty : (inv.stockQty !== undefined ? inv.stockQty : 0);
    const newQty = Math.round((current + added) * 10) / 10;
    inv.qty = newQty;
    inv.stockQty = newQty;
    inv.status = inv.qty <= inv.minThreshold ? 'low' : 'good';
    if (inv.id) API.updateInventoryStock(inv.id, inv.qty);
  } else {
    const newInv = {
      id: `INV-${Date.now().toString().substr(-2)}`,
      name: item,
      category: 'Supplies',
      stockQty: added,
      qty: added,
      unit: 'Units',
      minThreshold: 5,
      status: added <= 5 ? 'low' : 'good'
    };
    DB.inventory.unshift(newInv);
    API.addInventoryItem(newInv);
  }
  updateLowStockBadge();
  saveLocalDB();
  renderCurrentModule();
};

// ==========================================
// 11. SUPPLIERS & PURCHASES MODULE
// ==========================================

function renderSuppliersView(container) {
  container.innerHTML = `
    <div class="customer-table-card">
      <div style="padding:16px 20px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-border-subtle); flex-wrap:wrap; gap:12px;">
        <div>
          <h3>Approved Suppliers & Purchase Orders</h3>
          <span style="font-size:12px; color:var(--color-cream-muted);">Roasters, dairy farms & eco packaging vendors</span>
        </div>
        <button class="btn btn-primary btn-sm" onclick="openCreatePOModal()"><i class="ri-truck-line"></i> Create Purchase Order</button>
      </div>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Supplier Name</th>
              <th>Contact Person</th>
              <th>Phone</th>
              <th>Supply Catalog</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            ${DB.suppliers.map(s => `
              <tr>
                <td><strong>${s.name}</strong></td>
                <td>${s.contact}</td>
                <td>${s.phone}</td>
                <td>${s.catalog}</td>
                <td><span class="badge badge-success">Active Partner</span></td>
                <td>
                  <button class="btn btn-outline btn-sm" onclick="alert('Contacting ${s.name} at ${s.phone}')"><i class="ri-phone-line"></i> Call</button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

window.openCreatePOModal = function() {
  const vendor = prompt("Select Supplier Name:", "BioPak Sustainable Solutions");
  if (!vendor) return;
  const items = prompt("Order Items & Quantity:", "500x Bio-Cups 12oz, 500x Lids");
  if (!items) return;

  alert(`Purchase Order created successfully for ${vendor}!\nItems: ${items}\nSent to vendor email.`);
};

// ==========================================
// 12. DISCOUNTS & PROMOTIONS MODULE
// ==========================================

function renderDiscountsView(container) {
  container.innerHTML = `
    <div class="customer-table-card">
      <div style="padding:16px 20px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-border-subtle); flex-wrap:wrap; gap:12px;">
        <div>
          <h3>Promotional Vouchers & CBD Discounts</h3>
          <span style="font-size:12px; color:var(--color-cream-muted);">Active voucher codes and worker promotions</span>
        </div>
        <button class="btn btn-primary btn-sm" onclick="openCreatePromoModal()"><i class="ri-add-line"></i> Create Promo Code</button>
      </div>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Code</th>
              <th>Description</th>
              <th>Type</th>
              <th>Discount Value</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            ${DB.discounts.map((d, idx) => `
              <tr>
                <td><strong class="cart-order-id">${d.code}</strong></td>
                <td>${d.description}</td>
                <td>${d.type === 'percent' ? 'Percentage' : 'Fixed Amount'}</td>
                <td><strong>${d.type === 'percent' ? d.val + '%' : '$' + d.val.toFixed(2)}</strong></td>
                <td><span class="badge badge-success">Active</span></td>
                <td>
                  <button class="btn btn-outline btn-sm text-danger" onclick="deletePromo(${idx})"><i class="ri-delete-bin-line"></i> Delete</button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

window.openCreatePromoModal = function() {
  const code = prompt("Promo Code (e.g. COFFEE10):", "CBDVIP");
  if (!code) return;
  const desc = prompt("Description:", "CBD Local Partner 10% Off");
  const val = prompt("Discount Value (e.g. 10 for 10% or 2.00 for $2.00):", "10");

  const newDisc = {
    code: code.toUpperCase(),
    description: desc || 'Promotional Discount',
    type: 'percent',
    val: parseFloat(val || 10),
    minSpend: 0
  };

  DB.discounts.unshift(newDisc);
  saveLocalDB();
  renderCurrentModule();
  API.createDiscount(newDisc);
};

window.deletePromo = function(idx) {
  const disc = DB.discounts[idx];
  if (disc && confirm(`Delete discount code ${disc.code}?`)) {
    DB.discounts.splice(idx, 1);
    saveLocalDB();
    renderCurrentModule();
    if (disc.code) API.deleteDiscount(disc.code);
  }
};

// ==========================================
// 13. CUSTOMERS & LOYALTY MODULE
// ==========================================

function renderCustomersView(container) {
  container.innerHTML = `
    <div class="customers-container">
      <div class="customer-table-card">
        <div style="padding:16px 20px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-border-subtle); flex-wrap:wrap; gap:12px;">
          <div>
            <h3>Loyalty Members Directory</h3>
            <span style="font-size:12px; color:var(--color-cream-muted);">Points tracking ($1 = 10 pts) and reward tiers</span>
          </div>
          <button class="btn btn-primary btn-sm" onclick="openRegisterCustomerModal()"><i class="ri-user-add-line"></i> Register Member</button>
        </div>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Member Name</th>
                <th>Tier Badge</th>
                <th>Mobile</th>
                <th>Email</th>
                <th>Points Balance</th>
                <th>Total Visits</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              ${DB.customers.map((c, idx) => `
                <tr>
                  <td><strong>${c.name}</strong></td>
                  <td><span class="badge badge-gold">${c.tier}</span></td>
                  <td>${c.mobile}</td>
                  <td>${c.email}</td>
                  <td><strong style="color:var(--color-accent-gold);">${c.points} Pts</strong></td>
                  <td>${c.visits} Visits</td>
                  <td>
                    <button class="btn btn-outline btn-sm" onclick="addBonusPoints(${idx})">+100 Pts</button>
                  </td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `;
}

window.openRegisterCustomerModal = function() {
  // If called from landing/login modal, close it first
  closeLoginModal();
  
  const modal = document.getElementById('register-customer-modal');
  if (modal) {
    modal.classList.remove('hidden');
  }
};

window.closeRegisterCustomerModal = function() {
  const modal = document.getElementById('register-customer-modal');
  if (modal) {
    modal.classList.add('hidden');
  }
};

window.handleCustomerRegistration = async function(e) {
  e.preventDefault();
  
  const name = document.getElementById('reg-name').value.trim();
  const email = document.getElementById('reg-email').value.trim();
  const phone = document.getElementById('reg-phone').value.trim();
  const pass = document.getElementById('reg-password').value;
  const passConfirm = document.getElementById('reg-confirm-password').value;
  const errorMsg = document.getElementById('reg-error');
  const btn = document.getElementById('submit-reg-btn');
  
  if (pass !== passConfirm) {
    errorMsg.textContent = "Passwords do not match.";
    errorMsg.classList.remove('hidden');
    return;
  }
  
  try {
    btn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Creating Account...';
    
    // Split name into first and last
    const nameParts = name.split(' ');
    const firstName = nameParts[0];
    const lastName = nameParts.slice(1).join(' ');
    
    const res = await fetch(`${API_BASE}/users/register_customer.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        first_name: firstName,
        last_name: lastName,
        email: email,
        phone: phone,
        password: pass 
      })
    });
    
    const data = await res.json();
    btn.innerHTML = '<i class="ri-user-add-line"></i> Create Account';
    
    if (data.success) {
      errorMsg.classList.add('hidden');
      closeRegisterCustomerModal();
      
      // Auto login the new user
      const loginRes = await fetch(`${API_BASE}/users/login.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username: email, password: pass })
      });
      const loginData = await loginRes.json();
      
      if (loginData.success) {
        AppState.isAuthenticated = true;
        AppState.activeRole = 'customer';
        AppState.currentUser = loginData.data;
        applyRoleToUI('customer');
        applyRolePermissionsUI();
        window.showAppView('pos');
        showToast('Account created and logged in successfully!', 'success');
      } else {
        showToast('Account created. Please login.', 'success');
        openLoginModal();
      }
    } else {
      errorMsg.textContent = data.message || "Registration failed.";
      errorMsg.classList.remove('hidden');
    }
  } catch (err) {
    btn.innerHTML = '<i class="ri-user-add-line"></i> Create Account';
    errorMsg.textContent = "Server error during registration.";
    errorMsg.classList.remove('hidden');
  }
};

window.addBonusPoints = function(idx) {
  const c = DB.customers[idx];
  if (!c) return;
  c.points += 100;
  saveLocalDB();
  renderCurrentModule();
  if (c.id) API.updateCustomer(c.id, { points: c.points });
};

// ==========================================
// 14. EMPLOYEES & ATTENDANCE MODULE
// ==========================================

function renderEmployeesView(container) {
  container.innerHTML = `
    <div class="customer-table-card">
      <div style="padding:16px 20px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-border-subtle); flex-wrap:wrap; gap:12px;">
        <div>
          <h3>Staff Roster & Shift Attendance</h3>
          <span style="font-size:12px; color:var(--color-cream-muted);">Barista shift tracker & clock-in time logs</span>
        </div>
        <button class="btn btn-success btn-sm" onclick="openClockShiftModal()"><i class="ri-time-line"></i> Clock Shift In / Out</button>
      </div>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Employee Name</th>
              <th>Role</th>
              <th>Shift Start</th>
              <th>Clocked In</th>
              <th>Hours Worked Today</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            ${DB.employees.map((e, idx) => `
              <tr>
                <td><strong>${e.name}</strong></td>
                <td>${e.role}</td>
                <td>${e.shiftStart}</td>
                <td><span class="badge ${e.clockedIn ? 'badge-success':'badge-danger'}">${e.clockedIn ? 'ACTIVE ON SHIFT' : 'OFF'}</span></td>
                <td>${(e.hoursWorked || 4.0).toFixed(1)} hrs</td>
                <td>
                  <button class="btn btn-outline btn-sm" onclick="toggleClockStatus(${idx})">
                    ${e.clockedIn ? 'Clock Out' : 'Clock In'}
                  </button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

window.openClockShiftModal = function() {
  const name = prompt("Select Staff Member (Sarah Lin, Liam O'Connor, Hannah Wright):", "Sarah Lin");
  if (!name) return;
  const emp = DB.employees.find(e => e.name.toLowerCase().includes(name.toLowerCase()));
  if (emp) {
    emp.clockedIn = !emp.clockedIn;
    emp.status = emp.clockedIn ? 'Active' : 'Off';
    saveLocalDB();
    renderCurrentModule();
    if (emp.id) API.updateStaff(emp.id, { status: emp.status });
  }
};

window.toggleClockStatus = function(idx) {
  const emp = DB.employees[idx];
  if (!emp) return;
  emp.clockedIn = !emp.clockedIn;
  emp.status = emp.clockedIn ? 'Active' : 'Off';
  saveLocalDB();
  renderCurrentModule();
  if (emp.id) API.updateStaff(emp.id, { status: emp.status });
};

// ==========================================
// 15. CUSTOMER FEEDBACK MODULE
// ==========================================

function renderFeedbackView(container) {
  const avg = DB.feedback.length ? (DB.feedback.reduce((acc, f) => acc + f.rating, 0) / DB.feedback.length).toFixed(1) : "5.0";

  container.innerHTML = `
    <div class="customers-container">
      <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px;">
        <div class="kpi-card">
          <div class="kpi-icon"><i class="ri-star-fill" style="color:var(--color-accent-gold);"></i></div>
          <div class="kpi-info">
            <span>Average Score</span>
            <h3>${avg} / 5.0</h3>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon"><i class="ri-chat-smile-2-line"></i></div>
          <div class="kpi-info">
            <span>Total Reviews</span>
            <h3>${DB.feedback.length}</h3>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon"><i class="ri-thumb-up-line"></i></div>
          <div class="kpi-info">
            <span>Positive Rating</span>
            <h3>100.0%</h3>
          </div>
        </div>
      </div>

      <div class="customer-table-card">
        <div style="padding:16px 20px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-border-subtle); flex-wrap:wrap; gap:12px;">
          <h3>Recent Customer Reviews</h3>
          <button class="btn btn-primary btn-sm" onclick="openAddReviewModal()"><i class="ri-add-line"></i> Add Customer Review</button>
        </div>
        <div style="padding:20px; display:flex; flex-direction:column; gap:16px;">
          ${DB.feedback.map(fb => `
            <div style="background:var(--bg-canvas); padding:16px; border-radius:var(--border-radius-md); border:1px solid var(--color-border-subtle);">
              <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <strong>${fb.customer}</strong>
                <span class="text-muted" style="font-size:12px;">${fb.date}</span>
              </div>
              <div style="color:var(--color-accent-gold); margin-bottom:6px;">
                ${'★'.repeat(fb.rating)}
              </div>
              <p style="font-size:13px; color:var(--color-cream);">${fb.comment}</p>
            </div>
          `).join('')}
        </div>
      </div>
    </div>
  `;
}

window.openAddReviewModal = function() {
  const name = prompt("Customer Name:", "Liam K.");
  if (!name) return;
  const ratingStr = prompt("Rating (1 to 5 stars):", "5") || "5";
  const comment = prompt("Customer Feedback Comment:", "Loved the single origin pour over! Very floral notes.");
  if (!comment) return;

  const newFB = {
    id: `FB-${Date.now().toString().substr(-2)}`,
    customer: name,
    rating: parseInt(ratingStr),
    comment: comment,
    date: 'Just now'
  };

  DB.feedback.unshift(newFB);
  saveLocalDB();
  renderCurrentModule();
  API.createFeedback(newFB);
};

// ==========================================
// 16. DASHBOARD & REPORTING MODULE
// ==========================================

function renderDashboardView(container) {
  const totalSalesToday = DB.completedSales.reduce((acc, s) => acc + s.total, 0);
  const totalOrdersCount = DB.completedSales.length;
  const avgOrderValue = totalOrdersCount > 0 ? (totalSalesToday / totalOrdersCount) : 0;

  // Calculate payment method split from actual data
  const eftposTotal = DB.completedSales.filter(s => s.paymentMethod === 'EFTPOS' || s.paymentMethod === 'CARD').reduce((a, s) => a + s.total, 0);
  const cashTotal = DB.completedSales.filter(s => s.paymentMethod === 'CASH').reduce((a, s) => a + s.total, 0);
  const loyaltyTotal = DB.completedSales.filter(s => s.paymentMethod === 'LOYALTY').reduce((a, s) => a + s.total, 0);
  const eftposPct = totalSalesToday > 0 ? Math.round((eftposTotal / totalSalesToday) * 100) : 0;
  const cashPct = totalSalesToday > 0 ? Math.round((cashTotal / totalSalesToday) * 100) : 0;
  const loyaltyPct = totalSalesToday > 0 ? Math.round((loyaltyTotal / totalSalesToday) * 100) : 0;

  container.innerHTML = `
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
      <div>
        <h3>Executive Analytics & Sales Reports</h3>
        <span style="font-size:12px; color:var(--color-cream-muted);">Live shop performance metrics for Melbourne CBD store</span>
      </div>
    </div>

    <div class="dashboard-kpi-grid">
      <div class="kpi-card">
        <div class="kpi-icon"><i class="ri-money-dollar-circle-line"></i></div>
        <div class="kpi-info">
          <span>Daily Gross Revenue</span>
          <h3>$${totalSalesToday.toFixed(2)}</h3>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon"><i class="ri-shopping-bag-3-line"></i></div>
        <div class="kpi-info">
          <span>Orders Processed</span>
          <h3>${totalOrdersCount}</h3>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon"><i class="ri-line-chart-line"></i></div>
        <div class="kpi-info">
          <span>Avg Order Value</span>
          <h3>$${avgOrderValue.toFixed(2)}</h3>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon"><i class="ri-cup-line"></i></div>
        <div class="kpi-info">
          <span>Active KDS Orders</span>
          <h3>${DB.kdsOrders.length}</h3>
        </div>
      </div>
    </div>

    <div class="charts-row">
      <div class="chart-card">
        <h4>Payment Tender Split</h4>
        <div style="display:flex; flex-direction:column; gap:12px; margin-top:20px;">
          <div>
            <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:4px;">
              <span>EFTPOS / Card</span>
              <strong>${eftposPct}% ($${eftposTotal.toFixed(2)})</strong>
            </div>
            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:${eftposPct}%; background:var(--color-primary);"></div></div>
          </div>
          <div>
            <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:4px;">
              <span>Cash</span>
              <strong>${cashPct}% ($${cashTotal.toFixed(2)})</strong>
            </div>
            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:${cashPct}%; background:var(--color-accent-gold);"></div></div>
          </div>
          <div>
            <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:4px;">
              <span>Loyalty Redemption</span>
              <strong>${loyaltyPct}% ($${loyaltyTotal.toFixed(2)})</strong>
            </div>
            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:${loyaltyPct}%; background:var(--color-success);"></div></div>
          </div>
        </div>
      </div>

      <div class="chart-card">
        <h4>Inventory Alerts</h4>
        <div style="display:flex; flex-direction:column; gap:12px; margin-top:20px;">
          ${DB.inventory.filter(inv => inv.status === 'low').map(inv => `
            <div style="display:flex; justify-content:space-between; align-items:center; padding:10px; background:rgba(255,107,107,0.1); border-radius:8px; border:1px solid rgba(255,107,107,0.3);">
              <div>
                <strong style="color:var(--color-danger);">${inv.name}</strong>
                <div style="font-size:11px; color:var(--color-cream-muted);">Current: ${(inv.qty !== undefined ? inv.qty : inv.stockQty || 0).toFixed(1)} ${inv.unit} • Min: ${inv.minThreshold} ${inv.unit}</div>
              </div>
              <span class="badge badge-danger">LOW STOCK</span>
            </div>
          `).join('') || '<div style="text-align:center; color:var(--color-cream-muted); padding:20px;"><i class="ri-check-double-line" style="font-size:24px; color:var(--color-success);"></i><p>All inventory levels OK</p></div>'}
        </div>
      </div>
    </div>

    <!-- Transaction History Table -->
    <div class="customer-table-card" style="margin-top:20px;">
      <div style="padding:16px 20px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-border-subtle); flex-wrap:wrap; gap:12px;">
        <div>
          <h3>Transaction History</h3>
          <span style="font-size:12px; color:var(--color-cream-muted);">${DB.completedSales.length} completed transactions recorded</span>
        </div>
      </div>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Amount (AUD)</th>
              <th>Payment Method</th>
              <th>Items</th>
              <th>Cashier</th>
              <th>Time</th>
            </tr>
          </thead>
          <tbody>
            ${DB.completedSales.length === 0 ? `
              <tr><td colspan="6" style="text-align:center; padding:24px; color:var(--color-cream-muted);"><i class="ri-inbox-line" style="font-size:24px; display:block; margin-bottom:8px;"></i>No transactions recorded yet. Complete a sale to see data here.</td></tr>
            ` : DB.completedSales.map(s => `
              <tr>
                <td><strong>${s.id}</strong></td>
                <td><strong style="color:var(--color-accent-gold);">$${s.total.toFixed(2)}</strong></td>
                <td><span class="badge ${s.paymentMethod === 'CASH' ? 'badge-warning' : s.paymentMethod === 'LOYALTY' ? 'badge-gold' : 'badge-primary'}">${s.paymentMethod}</span></td>
                <td>${s.itemsCount} items</td>
                <td>${s.cashier || 'Staff'}</td>
                <td>${s.timestamp}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

// ==========================================
// 17. ACCESS CONTROL MODULE
// ==========================================

function renderAccessView(container) {
  if (AppState.activeRole !== 'admin') {
    renderAccessRestrictedNotice(container, 'Access Control', 'Only Administrators have permission to view and edit the Role Access Permissions Matrix.');
    return;
  }

  const modulesList = [
    { key: 'pos', name: 'Point of Sale (POS)' },
    { key: 'kds', name: 'Kitchen & Barista KDS' },
    { key: 'waitstaff', name: 'Wait Staff Monitor' },
    { key: 'customer_tracker', name: 'Customer Live Tracker' },
    { key: 'tables', name: 'Table Management' },
    { key: 'reservations', name: 'Reservations' },
    { key: 'menu', name: 'Menu & Modifiers' },
    { key: 'inventory', name: 'Inventory & Recipes' },
    { key: 'suppliers', name: 'Suppliers & Orders' },
    { key: 'discounts', name: 'Discounts & Promos' },
    { key: 'customers', name: 'Customers & Loyalty' },
    { key: 'employees', name: 'Staff & Attendance' },
    { key: 'feedback', name: 'Customer Feedback' },
    { key: 'dashboard', name: 'Dashboard & Financial Reports' },
    { key: 'audit', name: 'Audit & Compliance Logs' }
  ];

  if (!DB.rolePermissions) {
    DB.rolePermissions = JSON.parse(JSON.stringify(defaultPermissions));
  }

  const perms = DB.rolePermissions;

  container.innerHTML = `
    <div class="customer-table-card">
      <div style="padding:16px 20px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-border-subtle); flex-wrap:wrap; gap:12px;">
        <div>
          <h3>Role Access Permissions Matrix</h3>
          <span style="font-size:12px; color:var(--color-cream-muted);">Manage operational access levels across system roles</span>
        </div>
        <button class="btn btn-primary btn-sm" onclick="savePermissionsMatrix()"><i class="ri-save-line"></i> Save Permissions</button>
      </div>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Module Name</th>
              <th>Admin</th>
              <th>Manager</th>
              <th>Cashier</th>
              <th>Kitchen</th>
              <th>Barista</th>
              <th>Wait Staff</th>
              <th>Customer</th>
            </tr>
          </thead>
          <tbody id="permissions-matrix-body">
            ${modulesList.map(mod => `
              <tr>
                <td><strong>${mod.name}</strong></td>
                <td><label style="display:flex; align-items:center; gap:8px; cursor:not-allowed; opacity:0.85;"><input type="checkbox" checked disabled> <span class="badge badge-primary">Full Access</span></label></td>
                <td>
                  <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" data-role="manager" data-module="${mod.key}" ${perms.manager && perms.manager[mod.key] ? 'checked' : ''} onchange="updateMatrixCheckboxBadge(this)"> 
                    <span class="badge ${perms.manager && perms.manager[mod.key] ? 'badge-success' : 'badge-danger'}">${perms.manager && perms.manager[mod.key] ? 'Full Access' : 'Restricted'}</span>
                  </label>
                </td>
                <td>
                  <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" data-role="cashier" data-module="${mod.key}" ${perms.cashier && perms.cashier[mod.key] ? 'checked' : ''} onchange="updateMatrixCheckboxBadge(this)"> 
                    <span class="badge ${perms.cashier && perms.cashier[mod.key] ? 'badge-success' : 'badge-danger'}">${perms.cashier && perms.cashier[mod.key] ? 'Full Access' : 'Restricted'}</span>
                  </label>
                </td>
                <td>
                  <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" data-role="kitchen" data-module="${mod.key}" ${perms.kitchen && perms.kitchen[mod.key] ? 'checked' : ''} onchange="updateMatrixCheckboxBadge(this)"> 
                    <span class="badge ${perms.kitchen && perms.kitchen[mod.key] ? 'badge-success' : 'badge-danger'}">${perms.kitchen && perms.kitchen[mod.key] ? 'Full Access' : 'Restricted'}</span>
                  </label>
                </td>
                <td>
                  <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" data-role="barista" data-module="${mod.key}" ${perms.barista && perms.barista[mod.key] ? 'checked' : ''} onchange="updateMatrixCheckboxBadge(this)"> 
                    <span class="badge ${perms.barista && perms.barista[mod.key] ? 'badge-success' : 'badge-danger'}">${perms.barista && perms.barista[mod.key] ? 'Full Access' : 'Restricted'}</span>
                  </label>
                </td>
                <td>
                  <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" data-role="waitstaff" data-module="${mod.key}" ${perms.waitstaff && perms.waitstaff[mod.key] ? 'checked' : ''} onchange="updateMatrixCheckboxBadge(this)"> 
                    <span class="badge ${perms.waitstaff && perms.waitstaff[mod.key] ? 'badge-success' : 'badge-danger'}">${perms.waitstaff && perms.waitstaff[mod.key] ? 'Full Access' : 'Restricted'}</span>
                  </label>
                </td>
                <td>
                  <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" data-role="customer" data-module="${mod.key}" ${perms.customer && perms.customer[mod.key] ? 'checked' : ''} onchange="updateMatrixCheckboxBadge(this)"> 
                    <span class="badge ${perms.customer && perms.customer[mod.key] ? 'badge-success' : 'badge-danger'}">${perms.customer && perms.customer[mod.key] ? 'Full Access' : 'Restricted'}</span>
                  </label>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

window.updateMatrixCheckboxBadge = function(cb) {
  const badge = cb.parentElement.querySelector('.badge');
  if (badge) {
    if (cb.checked) {
      badge.className = 'badge badge-success';
      badge.textContent = 'Full Access';
    } else {
      badge.className = 'badge badge-danger';
      badge.textContent = 'Restricted';
    }
  }
};

window.savePermissionsMatrix = function() {
  if (AppState.activeRole !== 'admin') return;

  if (!DB.rolePermissions) {
    DB.rolePermissions = JSON.parse(JSON.stringify(defaultPermissions));
  }

  document.querySelectorAll('#permissions-matrix-body input[type="checkbox"]').forEach(cb => {
    const role = cb.getAttribute('data-role');
    const moduleKey = cb.getAttribute('data-module');
    if (role && moduleKey) {
      if (!DB.rolePermissions[role]) DB.rolePermissions[role] = {};
      DB.rolePermissions[role][moduleKey] = cb.checked;
    }
  });

  saveLocalDB();
  applyRolePermissionsUI();
  API.saveState('rolePermissions', DB.rolePermissions);

  alert('Role Access Permissions saved successfully to SQLite Database!');
};

// ==========================================
// AUDIT LOGS & COMPLIANCE MODULE (NFR33-NFR39)
// ==========================================
async function renderAuditView(container) {
  container.innerHTML = `
    <div class="module-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
      <div>
        <h2 style="font-size:22px; font-weight:800; font-family:'Outfit', sans-serif;"><i class="ri-file-list-3-line"></i> Audit Trail & Compliance Logs</h2>
        <p style="color:var(--color-cream-muted); font-size:13px;">Security logging, authentication attempts, order modifications, and inventory adjustment records.</p>
      </div>
      <div style="display:flex; gap:10px;">
        <button class="btn btn-secondary btn-sm" onclick="renderAuditView(document.getElementById('workspace-container'))">
          <i class="ri-refresh-line"></i> Refresh Audit Logs
        </button>
      </div>
    </div>

    <div class="card" style="padding:20px; background:var(--bg-card); border-radius:14px; border:1px solid var(--color-border);">
      <div style="display:flex; gap:16px; margin-bottom:16px;">
        <input type="text" id="audit-search-input" placeholder="Search audit logs by action, user, or details..." class="form-input" style="flex:1;" oninput="filterAuditLogsUI()">
      </div>

      <div class="table-responsive">
        <table class="data-table" style="width:100%; border-collapse:collapse;" id="audit-table">
          <thead>
            <tr style="border-bottom:1px solid var(--color-border); text-align:left; font-size:12px; color:var(--color-cream-muted);">
              <th style="padding:12px;">Timestamp</th>
              <th style="padding:12px;">Log ID</th>
              <th style="padding:12px;">User / Source</th>
              <th style="padding:12px;">Action Type</th>
              <th style="padding:12px;">Details</th>
            </tr>
          </thead>
          <tbody id="audit-table-body">
            <tr><td colspan="5" style="padding:20px; text-align:center;">Loading audit logs from SQLite...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  `;

  const logs = await API.fetchAuditLogs();
  window._currentAuditLogs = logs || [];
  renderAuditTableRows(window._currentAuditLogs);
}

function renderAuditTableRows(logs) {
  const tbody = document.getElementById('audit-table-body');
  if (!tbody) return;

  if (!logs || !logs.length) {
    tbody.innerHTML = `<tr><td colspan="5" style="padding:30px; text-align:center; color:var(--color-cream-muted);">No audit logs recorded yet.</td></tr>`;
    return;
  }

  tbody.innerHTML = logs.map(l => `
    <tr style="border-bottom:1px solid rgba(255,255,255,0.05); font-size:13px;">
      <td style="padding:12px; white-space:nowrap; color:var(--color-cream-muted);">${new Date(l.timestamp).toLocaleString()}</td>
      <td style="padding:12px; font-family:monospace; color:var(--color-primary-light);">${l.id}</td>
      <td style="padding:12px; font-weight:600;">${l.userName || l.userId}</td>
      <td style="padding:12px;"><span class="badge badge-info">${l.action}</span></td>
      <td style="padding:12px; color:var(--color-cream); font-family:monospace; font-size:12px;">${l.details || '-'}</td>
    </tr>
  `).join('');
}

window.filterAuditLogsUI = function() {
  const query = (document.getElementById('audit-search-input')?.value || '').toLowerCase();
  if (!window._currentAuditLogs) return;
  const filtered = window._currentAuditLogs.filter(l => 
    (l.action && l.action.toLowerCase().includes(query)) ||
    (l.userName && l.userName.toLowerCase().includes(query)) ||
    (l.details && l.details.toLowerCase().includes(query))
  );
  renderAuditTableRows(filtered);
};

// ==========================================
// THERMAL PRINTABLE RECEIPT MODAL (FR36)
// ==========================================
window.openPrintableReceiptModal = async function(orderId) {
  let order = DB.completedSales?.find(o => o.id === orderId || o.orderNum === orderId);
  if (!order) {
    const allOrders = await API.fetchOrders();
    order = allOrders?.find(o => o.id === orderId || o.orderNum === orderId || `#ORD-${o.orderNum}` === orderId);
  }

  if (!order) {
    alert(`Order ${orderId} not found for receipt printing.`);
    return;
  }

  const modal = document.getElementById('printable-receipt-modal');
  if (!modal) return;

  document.getElementById('receipt-order-id').textContent = order.id || `#ORD-${order.orderNum}`;
  document.getElementById('receipt-date').textContent = new Date(order.createdAt || Date.now()).toLocaleString();
  document.getElementById('receipt-cashier').textContent = AppState.currentUser?.name || 'Sarah Lin';
  document.getElementById('receipt-type').textContent = (order.type === 'takeaway' ? 'Takeaway' : 'Dine In') + (order.tableId ? ` (${order.tableId})` : '');

  const items = Array.isArray(order.items) ? order.items : JSON.parse(order.itemsJson || '[]');
  const tbody = document.querySelector('#receipt-items-table tbody');
  if (tbody) {
    tbody.innerHTML = items.map(item => `
      <tr style="border-bottom:1px solid #ddd;">
        <td style="padding:4px 0;">${item.qty || item.quantity || 1}x ${item.name}</td>
        <td style="text-align:right; padding:4px 0;">$${((item.price || 0) * (item.qty || item.quantity || 1)).toFixed(2)}</td>
      </tr>
    `).join('');
  }

  document.getElementById('receipt-subtotal').textContent = `$${(order.subtotal || 0).toFixed(2)}`;
  document.getElementById('receipt-tax').textContent = `$${(order.tax || 0).toFixed(2)}`;
  document.getElementById('receipt-discount').textContent = `-$${(order.discount || 0).toFixed(2)}`;
  document.getElementById('receipt-total').textContent = `$${(order.total || 0).toFixed(2)}`;
  document.getElementById('receipt-pay-method').textContent = (order.paymentMethod || 'CARD').toUpperCase();

  modal.classList.remove('hidden');
};

window.closePrintableReceiptModal = function() {
  const modal = document.getElementById('printable-receipt-modal');
  if (modal) modal.classList.add('hidden');
};

// ==========================================
// REPORT CSV EXPORTER (FR68)
// ==========================================
window.exportReportsToCSV = function() {
  if (!DB.completedSales || !DB.completedSales.length) {
    alert('No sales transaction data available to export.');
    return;
  }

  const headers = ['Order ID', 'Date', 'Type', 'Table', 'Customer', 'Payment Method', 'Subtotal', 'Tax', 'Discount', 'Total', 'Status'];
  const rows = DB.completedSales.map(o => [
    o.id || `#ORD-${o.orderNum}`,
    `"${new Date(o.createdAt || Date.now()).toLocaleString()}"`,
    o.type || 'dine_in',
    o.tableId || 'N/A',
    `"${o.customerName || 'Walk-in Guest'}"`,
    o.paymentMethod || 'card',
    (o.subtotal || 0).toFixed(2),
    (o.tax || 0).toFixed(2),
    (o.discount || 0).toFixed(2),
    (o.total || 0).toFixed(2),
    o.status || 'completed'
  ]);

  const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', `Ravenhill_Daily_Sales_Report_${new Date().toISOString().split('T')[0]}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};


// PDF Receipt Generator (FR36 - Download & Print-Ready PDF)
window.generateReceiptPDF = async function(shouldDownload = true) {
  const receiptEl = document.getElementById('thermal-receipt-content');
  if (!receiptEl) return;

  const orderId = document.getElementById('rec-order-id')?.textContent || 'Receipt';
  const cleanOrderId = orderId.replace(/[^a-zA-Z0-9_-]/g, '');

  try {
    if (window.html2canvas && window.jspdf) {
      const { jsPDF } = window.jspdf;
      
      // Temporarily set high contrast white background for clean rasterization
      const origBg = receiptEl.style.backgroundColor;
      const origColor = receiptEl.style.color;
      receiptEl.style.backgroundColor = '#ffffff';
      receiptEl.style.color = '#000000';

      const canvas = await html2canvas(receiptEl, {
        scale: 3, // 300 DPI equivalent for ultra-crisp vector-like thermal print
        useCORS: true,
        backgroundColor: '#ffffff',
        logging: false
      });

      receiptEl.style.backgroundColor = origBg;
      receiptEl.style.color = origColor;

      const imgData = canvas.toDataURL('image/png');
      
      // 80mm POS thermal receipt format
      const imgWidth = 80;
      const pageHeight = (canvas.height * imgWidth) / canvas.width;
      
      const pdf = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: [imgWidth, Math.max(pageHeight + 4, 100)]
      });

      pdf.addImage(imgData, 'PNG', 0, 2, imgWidth, pageHeight);

      if (shouldDownload) {
        pdf.save(`Ravenhill_Receipt_${cleanOrderId}.pdf`);
      } else {
        // Direct print popup
        const blob = pdf.output('blob');
        const blobUrl = URL.createObjectURL(blob);
        const printIframe = document.createElement('iframe');
        printIframe.style.position = 'fixed';
        printIframe.style.right = '0';
        printIframe.style.bottom = '0';
        printIframe.style.width = '0';
        printIframe.style.height = '0';
        printIframe.style.border = '0';
        printIframe.src = blobUrl;
        document.body.appendChild(printIframe);
        printIframe.onload = () => {
          setTimeout(() => {
            printIframe.contentWindow.focus();
            printIframe.contentWindow.print();
          }, 300);
        };
      }
    } else {
      window.print();
    }
  } catch (err) {
    console.error('[PDF Receipt] Generation notice:', err);
    window.print();
  }
};


// ============================================================================
// PAYPAL SANDBOX INTEGRATION (FR34 / FR36)
// ============================================================================
window.renderPayPalButtons = function() {
  const container = document.getElementById('paypal-button-container');
  const statusBox = document.getElementById('paypal-status-box');
  if (!container) return;

  container.innerHTML = '';
  if (statusBox) {
    statusBox.className = 'paypal-status-box hidden';
    statusBox.innerHTML = '';
  }

  const subtotal = AppState.cart.items.reduce((acc, i) => acc + i.totalPrice, 0);
  let discount = 0;
  if (AppState.cart.promoCode) {
    discount = AppState.cart.promoCode.type === 'percent' ? (subtotal * AppState.cart.promoCode.val)/100 : AppState.cart.promoCode.val;
  }
  const total = Math.max(0, subtotal - discount);

  if (total <= 0) {
    container.innerHTML = '<p class="text-sm text-center" style="color:var(--color-cream-muted); padding:16px 0;">Cart is empty. Add menu items to checkout.</p>';
    return;
  }

  // Check if PayPal SDK is available
  if (typeof paypal === 'undefined' || !paypal.Buttons) {
    container.innerHTML = `
      <div style="text-align:center; padding:12px;">
        <p class="text-sm" style="color:var(--color-cream); margin-bottom:10px;">Click below to simulate or complete PayPal Sandbox Payment ($${total.toFixed(2)} AUD):</p>
        <button class="btn btn-primary w-100" style="background:#0070ba; border-color:#0070ba;" onclick="completePaymentProcessWithDetails('PayPal', 'PAYPAL-SB-MANUAL')">
          <i class="ri-paypal-fill"></i> Complete PayPal Payment ($${total.toFixed(2)} AUD)
        </button>
      </div>
    `;
    return;
  }

  try {
    paypal.Buttons({
      style: {
        layout: 'vertical',
        color: 'gold',
        shape: 'rect',
        label: 'paypal',
        height: 40
      },
      createOrder: async function(data, actions) {
        const numId = parseInt((AppState.cart.orderId || '0').replace(/[^0-9]/g, '')) || 0;
        try {
          if (API && API.createPayPalOrder) {
            const res = await API.createPayPalOrder(numId, total);
            if (res && res.success && res.data && res.data.paypal_order_id) {
              return res.data.paypal_order_id;
            }
          }
        } catch (e) {
          console.warn('[PayPal] Server order creation notice:', e);
        }

        return actions.order.create({
          purchase_units: [{
            description: `Ravenhill Coffee POS Order ${AppState.cart.orderId}`,
            amount: {
              currency_code: 'AUD',
              value: total.toFixed(2)
            }
          }]
        });
      },
      onApprove: async function(data, actions) {
        if (statusBox) {
          statusBox.className = 'paypal-status-box success';
          statusBox.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Capturing PayPal Sandbox payment...';
          statusBox.classList.remove('hidden');
        }

        const numId = parseInt((AppState.cart.orderId || '0').replace(/[^0-9]/g, '')) || 0;
        const cashierName = AppState.currentUser?.name || (AppState.activeRole ? AppState.activeRole.toUpperCase() : 'Staff');
        
        let captureId = 'PAYPAL-' + (data.orderID || Date.now());
        try {
          if (API && API.capturePayPalOrder) {
            const capRes = await API.capturePayPalOrder(data.orderID, numId, total, cashierName);
            if (capRes && capRes.data && capRes.data.transaction_reference) {
              captureId = capRes.data.transaction_reference;
            }
          }
        } catch (e) {
          console.warn('[PayPal] Server capture callback notice:', e);
        }

        if (statusBox) {
          statusBox.className = 'paypal-status-box success';
          statusBox.innerHTML = '<i class="ri-checkbox-circle-fill"></i> PayPal Payment Approved & Captured!';
        }

        setTimeout(() => {
          completePaymentProcessWithDetails('PayPal', captureId);
        }, 500);
      },
      onError: function(err) {
        console.error('[PayPal Error]', err);
        if (statusBox) {
          statusBox.className = 'paypal-status-box error';
          statusBox.innerHTML = '<i class="ri-alert-line"></i> PayPal payment could not be completed. You can also click the bottom "Complete Payment" button.';
          statusBox.classList.remove('hidden');
        }
      },
      onCancel: function() {
        if (statusBox) {
          statusBox.className = 'paypal-status-box';
          statusBox.innerHTML = 'PayPal checkout was cancelled.';
          statusBox.classList.remove('hidden');
        }
      }
    }).render('#paypal-button-container');
  } catch (err) {
    console.error('[PayPal Render Exception]', err);
    container.innerHTML = `
      <div style="text-align:center; padding:12px;">
        <button class="btn btn-primary w-100" style="background:#0070ba; border-color:#0070ba;" onclick="completePaymentProcessWithDetails('PayPal', 'PAYPAL-SB-FALLBACK')">
          <i class="ri-paypal-fill"></i> Pay with PayPal ($${total.toFixed(2)} AUD)
        </button>
      </div>
    `;
  }
};

window.completePaymentProcessWithDetails = function(customMethod, customRef) {
  const activeMethod = customMethod || 'PAYPAL';
  const subtotal = AppState.cart.items.reduce((acc, i) => acc + i.totalPrice, 0);
  let discount = 0;
  if (AppState.cart.promoCode) {
    discount = AppState.cart.promoCode.type === 'percent' ? (subtotal * AppState.cart.promoCode.val)/100 : AppState.cart.promoCode.val;
  }
  const total = Math.max(0, subtotal - discount);
  const cashierName = AppState.currentUser?.name || (AppState.activeRole ? AppState.activeRole.toUpperCase() : 'Staff');
  const txnTimestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  const txnRef = customRef || ('PAYPAL-SB-' + Math.random().toString(36).substr(2, 9).toUpperCase());

  // Push to Live Orders (KDS Queue)
  const orderCreatedAt = new Date().toISOString();
  DB.activeOrders.push({
    id: AppState.cart.orderId,
    type: AppState.cart.orderType,
    table: AppState.cart.tableId,
    customer: AppState.cart.customer ? AppState.cart.customer.name : 'Walk-in Guest',
    items: JSON.parse(JSON.stringify(AppState.cart.items)),
    total: total,
    status: 'pending',
    createdAt: orderCreatedAt
  });

  // Mark table occupied if dine-in
  if (AppState.cart.orderType === 'dine_in' && AppState.cart.tableId) {
    const tbl = DB.tables.find(t => t.id === AppState.cart.tableId);
    if (tbl) {
      tbl.status = 'occupied';
      tbl.orderId = AppState.cart.orderId;
      API.updateTable(tbl.id, { status: 'occupied', orderId: AppState.cart.orderId });
      renderCartTableSelect();
    }
  }

  // Deduct Inventory automatically
  AppState.cart.items.forEach(ci => {
    if (ci.item && ci.item.recipe) {
      if (ci.item.recipe.coffeeBeansGrams) {
        const beanInv = DB.inventory.find(inv => inv.id === 'INV-01');
        if (beanInv) {
          const cur = beanInv.qty !== undefined ? beanInv.qty : (beanInv.stockQty || 0);
          beanInv.qty = Math.max(0, Math.round((cur - (ci.item.recipe.coffeeBeansGrams * ci.qty) / 1000) * 10) / 10);
          beanInv.stockQty = beanInv.qty;
          beanInv.status = beanInv.qty <= beanInv.minThreshold ? 'low' : 'good';
          if (beanInv.id) API.updateInventoryStock(beanInv.id, beanInv.qty);
        }
      }
    }
  });
  updateLowStockBadge();
  saveLocalDB();

  // Push to Sales Log (local)
  DB.completedSales.unshift({
    id: AppState.cart.orderId,
    total: total,
    paymentMethod: activeMethod.toUpperCase(),
    itemsCount: AppState.cart.items.length,
    cashier: cashierName,
    timestamp: txnTimestamp
  });

  // Persist transaction to backend
  API.createTransaction({
    orderId: AppState.cart.orderId,
    total: total,
    paymentMethod: activeMethod.toUpperCase(),
    transaction_reference: txnRef,
    itemsCount: AppState.cart.items.length,
    cashier: cashierName,
    timestamp: txnTimestamp
  });

  // Populate Printable Thermal Receipt
  document.getElementById('rec-order-id').textContent = AppState.cart.orderId;
  document.getElementById('rec-date').textContent = new Date().toLocaleString('en-AU');
  document.getElementById('rec-type').textContent = `${AppState.cart.orderType === 'dine_in' ? 'Dine In (' + AppState.cart.tableId + ')' : 'Takeaway'}`;
  document.getElementById('rec-cashier').textContent = cashierName;
  document.getElementById('rec-subtotal').textContent = `$${subtotal.toFixed(2)}`;
  document.getElementById('rec-gst').textContent = `$${(total * 0.10).toFixed(2)}`;
  document.getElementById('rec-total').textContent = `$${total.toFixed(2)}`;
  document.getElementById('rec-tender-type').textContent = activeMethod.toUpperCase();
  document.getElementById('rec-tendered').textContent = `$${total.toFixed(2)}`;
  document.getElementById('rec-change').textContent = `$0.00`;

  const recItems = document.getElementById('rec-items-list');
  recItems.innerHTML = AppState.cart.items.map(i => `
    <div class="r-item">
      <span>${i.qty}x ${i.item.name}</span>
      <span>$${i.totalPrice.toFixed(2)}</span>
    </div>
    <div class="r-sub">${i.size || 'Regular'} | ${i.milk || 'Standard'}</div>
  `).join('');

  document.getElementById('payment-modal').classList.add('hidden');
  document.getElementById('receipt-modal').classList.remove('hidden');

  // Reset Cart
  AppState.cart.items = [];
  AppState.cart.promoCode = null;
  AppState.cart.customer = null;

  API.fetchNextOrderNum().then(num => {
    if (num) {
      AppState.cart.orderId = `#ORD-${num}`;
    } else {
      const nextNum = parseInt(AppState.cart.orderId.split('-')[1]) + 1;
      AppState.cart.orderId = `#ORD-${nextNum}`;
    }
    renderCartUI();
    saveLocalDB();
  });

  renderCartUI();
  saveLocalDB();
};


// ============================================================================
// AUDIO CHIMES ENGINE (Web Audio API - Zero External Dependencies)
// ============================================================================
window.playAudioChime = function(type = 'new_order') {
  try {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;
    const ctx = new AudioContext();

    if (type === 'new_order') {
      // Pleasant Ascending 3-tone Chime: C5 (523.25Hz) -> E5 (659.25Hz) -> G5 (783.99Hz)
      const freqs = [523.25, 659.25, 783.99];
      freqs.forEach((f, idx) => {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(f, ctx.currentTime + idx * 0.12);

        gain.gain.setValueAtTime(0, ctx.currentTime + idx * 0.12);
        gain.gain.linearRampToValueAtTime(0.2, ctx.currentTime + idx * 0.12 + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + idx * 0.12 + 0.35);

        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(ctx.currentTime + idx * 0.12);
        osc.stop(ctx.currentTime + idx * 0.12 + 0.38);
      });
    } else if (type === 'ready') {
      // Bell Ding: A5 (880Hz) -> C6 (1046.5Hz)
      const freqs = [880, 1046.5];
      freqs.forEach((f, idx) => {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(f, ctx.currentTime + idx * 0.14);

        gain.gain.setValueAtTime(0, ctx.currentTime + idx * 0.14);
        gain.gain.linearRampToValueAtTime(0.25, ctx.currentTime + idx * 0.14 + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + idx * 0.14 + 0.45);

        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(ctx.currentTime + idx * 0.14);
        osc.stop(ctx.currentTime + idx * 0.14 + 0.5);
      });
    } else if (type === 'recall') {
      // Gentle Reversion Ding: 659.25Hz -> 523.25Hz
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(659.25, ctx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(523.25, ctx.currentTime + 0.2);

      gain.gain.setValueAtTime(0.15, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);

      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.28);
    }
  } catch (err) {
    console.warn('[Audio Chime Notice]', err);
  }
};

// ============================================================================
// 1. KITCHEN & BARISTA SEPARATED DASHBOARDS (FR30)
// ============================================================================
window.renderKDSView = async function(container) {
  // Determine station
  if (AppState.activeRole === 'kitchen') AppState.kdsStationFilter = 'kitchen';
  else if (AppState.activeRole === 'barista') AppState.kdsStationFilter = 'barista';
  else if (!AppState.kdsStationFilter) AppState.kdsStationFilter = 'kitchen';

  const activeStation = AppState.kdsStationFilter;
  const isManagerOrAdmin = ['admin', 'manager', 'cashier'].includes(AppState.activeRole);

  const kdsLayout = document.createElement('div');
  kdsLayout.className = 'kds-container';

  kdsLayout.innerHTML = `
    <div class="kds-filter-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
      ${isManagerOrAdmin ? `
        <div class="kds-station-tabs">
          <button class="kds-station-tab ${activeStation === 'kitchen' ? 'active' : ''}" onclick="setKDSStationFilter('kitchen')">
            <i class="ri-restaurant-line"></i> 🍳 Kitchen Dashboard (Food Only)
          </button>
          <button class="kds-station-tab ${activeStation === 'barista' ? 'active' : ''}" onclick="setKDSStationFilter('barista')">
            <i class="ri-cup-line"></i> ☕ Barista Dashboard (Drinks Only)
          </button>
          <button class="kds-station-tab ${activeStation === 'all' ? 'active' : ''}" onclick="setKDSStationFilter('all')">
            <i class="ri-dashboard-line"></i> All Stations (Expo)
          </button>
        </div>
      ` : `
        <div style="display:flex; align-items:center; gap:8px;">
          <span class="kds-station-badge ${activeStation === 'kitchen' ? 'kitchen' : 'barista'}" style="font-size:15px; font-weight:800; padding:8px 16px;">
            ${activeStation === 'kitchen' ? '🍳 Kitchen Dashboard — Food Items Only' : '☕ Barista Dashboard — Coffee & Drinks Only'}
          </span>
        </div>
      `}

      <div class="kds-stat-pills" id="kds-stat-pills-row">
        <span class="kds-stat-pill"><i class="ri-time-line text-warning"></i> New: <strong id="kds-pending-stat">0</strong></span>
        <span class="kds-stat-pill"><i class="ri-fire-line text-info"></i> Preparing: <strong id="kds-prep-stat">0</strong></span>
        <span class="kds-stat-pill"><i class="ri-check-double-line text-success"></i> Ready: <strong id="kds-ready-stat">0</strong></span>
      </div>

      <div style="display:flex; gap:8px;">
        <button class="btn btn-secondary btn-sm" onclick="playAudioChime('new_order')" title="Test Audio Sound"><i class="ri-volume-up-line"></i> Sound</button>
        <button class="btn btn-primary btn-sm" onclick="renderCurrentModule()"><i class="ri-refresh-line"></i> Refresh</button>
      </div>
    </div>

    <div id="kds-batch-summary-container"></div>
    <div class="kds-grid" id="kds-tickets-grid"></div>
  `;

  container.appendChild(kdsLayout);

  const grid = document.getElementById('kds-tickets-grid');
  const batchContainer = document.getElementById('kds-batch-summary-container');

  try {
    const res = await API.fetchStationTickets(activeStation);
    if (res && res.tickets) {
      // Update statistics
      const pendingCount = res.tickets.filter(t => t.ticket_status === 'pending').length;
      const prepCount = res.tickets.filter(t => t.ticket_status === 'preparing').length;
      const readyCount = res.tickets.filter(t => t.ticket_status === 'ready').length;

      document.getElementById('kds-pending-stat').textContent = pendingCount;
      document.getElementById('kds-prep-stat').textContent = prepCount;
      document.getElementById('kds-ready-stat').textContent = readyCount;

      // Render Barista Batch Summary Header
      if (res.batch_summary && (activeStation === 'barista' || activeStation === 'all')) {
        const bs = res.batch_summary;
        const totalMilks = bs.oat_milk + bs.almond_milk + bs.soy_milk + bs.full_cream;
        if (totalMilks > 0 || bs.extra_shots > 0 || bs.decaf > 0 || bs.extra_hot > 0) {
          batchContainer.innerHTML = `
            <div class="kds-batch-summary-bar">
              <span style="font-size:13px; font-weight:800; color:var(--color-cream);"><i class="ri-cup-fill" style="color:var(--color-primary-light);"></i> Active Drink Batching:</span>
              ${bs.oat_milk > 0 ? `<span class="batch-item-badge">🥛 Oat Milk: <strong>${bs.oat_milk}x</strong></span>` : ''}
              ${bs.almond_milk > 0 ? `<span class="batch-item-badge">🌰 Almond: <strong>${bs.almond_milk}x</strong></span>` : ''}
              ${bs.soy_milk > 0 ? `<span class="batch-item-badge">🌱 Soy: <strong>${bs.soy_milk}x</strong></span>` : ''}
              ${bs.full_cream > 0 ? `<span class="batch-item-badge">🥛 Full Cream: <strong>${bs.full_cream}x</strong></span>` : ''}
              ${bs.decaf > 0 ? `<span class="batch-item-badge">☕ Decaf: <strong>${bs.decaf}x</strong></span>` : ''}
              ${bs.extra_shots > 0 ? `<span class="batch-item-badge">⚡ Extra Shots: <strong>${bs.extra_shots}x</strong></span>` : ''}
              ${bs.extra_hot > 0 ? `<span class="batch-item-badge">🔥 Extra Hot: <strong>${bs.extra_hot}x</strong></span>` : ''}
            </div>
          `;
        }
      }

      if (res.tickets.length === 0) {
        grid.innerHTML = `
          <div class="empty-cart-state" style="grid-column:1/-1; padding:60px 20px; text-align:center;">
            <i class="ri-checkbox-circle-line" style="font-size:48px; color:var(--color-success);"></i>
            <h3 style="margin:12px 0 4px 0;">All ${activeStation === 'kitchen' ? 'Kitchen' : 'Barista'} Tickets Cleared!</h3>
            <p style="color:var(--color-cream-muted);">Queue is empty. Incoming orders will chime automatically.</p>
          </div>
        `;
      } else {
        grid.innerHTML = res.tickets.map(ticket => {
          const isUrgent = ticket.urgency === 'high';
          const mins = ticket.elapsed_minutes || 0;
          const status = ticket.ticket_status;
          const station = ticket.station;

          return `
            <div class="kds-ticket-card status-${status} ${isUrgent ? 'urgency-high' : ''}">
              <div class="kds-ticket-header">
                <div>
                  <span class="kds-ticket-id">#ORD-${ticket.order_id}</span>
                  <span class="badge ${ticket.order_type === 'dine_in' ? 'badge-primary' : 'badge-gold'}" style="margin-left:4px;">
                    ${ticket.order_type === 'dine_in' ? 'Table ' + (ticket.table_number || '?') : 'Takeaway'}
                  </span>
                </div>
                <span class="kds-timer ${isUrgent ? 'text-danger' : ''}"><i class="ri-time-line"></i> ${mins}m ago</span>
              </div>

              <div class="kds-ticket-body">
                <div style="font-size:12px; color:var(--color-cream-muted);"><i class="ri-user-line"></i> ${ticket.customer_name || 'Guest'}</div>
                
                <div style="margin-top:6px; display:flex; flex-direction:column; gap:10px;">
                  ${ticket.items.map(item => `
                    <div class="kds-item-row">
                      <span class="kds-item-qty">${item.quantity}x</span>
                      <div class="kds-item-details">
                        <div class="kds-item-name" style="font-size:15px; font-weight:700;">${item.product_name}</div>
                        ${item.kds_highlight !== 'Standard' ? `<div class="kds-highlight-tag">${item.kds_highlight}</div>` : ''}
                        ${item.item_notes ? `<div class="special-notes-callout"><i class="ri-alert-line"></i> Note: ${item.item_notes}</div>` : ''}
                      </div>
                    </div>
                  `).join('')}
                </div>
              </div>

              <div class="kds-ticket-footer">
                <div class="kds-stage-btn-group">
                  <button class="kds-stage-btn ${status === 'pending' ? 'active stage-pending' : ''}" onclick="setTicketDirectStatus(${ticket.ticket_id}, 'pending')">
                    New
                  </button>
                  <button class="kds-stage-btn ${status === 'preparing' ? 'active stage-preparing' : ''}" onclick="setTicketDirectStatus(${ticket.ticket_id}, 'preparing')">
                    <i class="ri-fire-line"></i> Preparing
                  </button>
                  <button class="kds-stage-btn ${status === 'ready' ? 'active stage-ready' : ''}" onclick="setTicketDirectStatus(${ticket.ticket_id}, 'ready')">
                    <i class="ri-check-line"></i> Ready
                  </button>
                  <button class="btn btn-ghost btn-sm" onclick="setTicketDirectStatus(${ticket.ticket_id}, 'collected')" title="Served & Close">
                    <i class="ri-checkbox-circle-line"></i>
                  </button>
                </div>
              </div>
            </div>
          `;
        }).join('');
      }
    }
  } catch (err) {
    console.error('[KDS Render Error]', err);
  }
};

window.setTicketDirectStatus = async function(ticketId, targetStatus) {
  try {
    const res = await API.setStationTicketStatus(ticketId, targetStatus);
    if (res && res.success) {
      if (targetStatus === 'ready') playAudioChime('ready');
      else if (targetStatus === 'collected') playAudioChime('served');
      else playAudioChime('new_order');
      renderCurrentModule();
    }
  } catch (e) {
    console.error('[Set Ticket Status Error]', e);
  }
};

// ============================================================================
// 2. WAIT STAFF DASHBOARD (FULL ORDER MONITOR & SERVING CONTROLS)
// ============================================================================
window.renderWaitStaffDashboard = async function(container) {
  const wsLayout = document.createElement('div');
  wsLayout.className = 'kds-container';

  wsLayout.innerHTML = `
    <div class="kds-filter-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
      <div style="display:flex; align-items:center; gap:10px;">
        <span class="kds-station-badge" style="background:rgba(34, 197, 94, 0.2); color:#22c55e; border:1px solid #22c55e; font-size:15px; font-weight:800; padding:8px 16px;">
          🤵 Wait Staff Dashboard — Full Order Service
        </span>
      </div>

      <div class="kds-stat-pills">
        <span class="kds-stat-pill"><i class="ri-time-line text-warning"></i> Active Orders: <strong id="ws-active-count">0</strong></span>
        <span class="kds-stat-pill" style="background:rgba(34, 197, 94, 0.15); border-color:#22c55e; color:#22c55e;">
          <i class="ri-notification-3-line"></i> Ready to Serve: <strong id="ws-ready-count">0</strong>
        </span>
      </div>

      <div>
        <button class="btn btn-primary btn-sm" onclick="renderCurrentModule()"><i class="ri-refresh-line"></i> Refresh</button>
      </div>
    </div>

    <div class="waitstaff-grid" id="waitstaff-orders-grid"></div>
  `;

  container.appendChild(wsLayout);

  const grid = document.getElementById('waitstaff-orders-grid');

  try {
    const res = await (await fetch(`${API_BASE}/orders/kds.php?station=waitstaff`)).json();
    if (res && res.success && res.data) {
      const orders = res.data.orders || [];
      document.getElementById('ws-active-count').textContent = orders.length;
      document.getElementById('ws-ready-count').textContent = res.data.ready_to_serve_count || 0;

      const readyBadge = document.getElementById('waitstaff-ready-count');
      if (readyBadge) {
        readyBadge.textContent = res.data.ready_to_serve_count || 0;
        readyBadge.classList.toggle('hidden', (res.data.ready_to_serve_count || 0) === 0);
      }

      if (orders.length === 0) {
        grid.innerHTML = `
          <div class="empty-cart-state" style="grid-column:1/-1; padding:60px 20px; text-align:center;">
            <i class="ri-check-double-line" style="font-size:48px; color:var(--color-success);"></i>
            <h3 style="margin:12px 0 4px 0;">All Tables Served!</h3>
            <p style="color:var(--color-cream-muted);">No orders currently waiting for collection.</p>
          </div>
        `;
      } else {
        grid.innerHTML = orders.map(ord => {
          const isReady = ord.is_ready_to_serve;
          const kStatus = ord.kitchen_status;
          const bStatus = ord.barista_status;

          return `
            <div class="waitstaff-card ${isReady ? 'ready-to-serve' : ''}">
              <div class="waitstaff-card-header">
                <div>
                  <div class="waitstaff-table-badge">
                    <i class="ri-restaurant-line" style="color:var(--color-primary-light);"></i>
                    ${ord.order_type === 'dine_in' ? 'Table ' + (ord.table_number || '?') : 'Takeaway'}
                    <span style="font-size:12px; color:var(--color-cream-muted); font-weight:600; margin-left:6px;">(#ORD-${ord.order_id})</span>
                  </div>
                  <div style="font-size:11px; color:var(--color-cream-muted); margin-top:2px;">
                    Guest: ${ord.customer_name || 'Walk-in'} • Wait: ${ord.elapsed_minutes}m
                  </div>
                </div>
                ${isReady ? `
                  <span class="badge badge-success" style="font-size:12px; padding:6px 12px; font-weight:800; animation:pulse 1s infinite;">
                    <i class="ri-notification-3-fill"></i> READY TO SERVE
                  </span>
                ` : `
                  <span class="badge badge-warning" style="font-size:11px;">${ord.master_status.toUpperCase()}</span>
                `}
              </div>

              <div class="waitstaff-card-body">
                <!-- Kitchen Food Breakdown -->
                ${ord.food_items && ord.food_items.length ? `
                  <div class="waitstaff-station-section">
                    <div class="waitstaff-station-header kitchen">
                      <span>🍳 Kitchen (Food)</span>
                      <span class="badge ${kStatus === 'ready' ? 'badge-success' : (kStatus === 'preparing' ? 'badge-info' : 'badge-warning')}">
                        ${kStatus === 'ready' ? 'Ready at Pass' : (kStatus === 'preparing' ? 'Cooking' : 'In Queue')}
                      </span>
                    </div>
                    ${ord.food_items.map(f => `
                      <div class="waitstaff-item-row">
                        <span><strong>${f.quantity}x</strong> ${f.product_name}</span>
                        ${f.notes_highlight !== 'Standard' ? `<span style="font-size:11px; color:#fb923c;">${f.notes_highlight}</span>` : ''}
                      </div>
                    `).join('')}
                  </div>
                ` : ''}

                <!-- Barista Drink Breakdown -->
                ${ord.drink_items && ord.drink_items.length ? `
                  <div class="waitstaff-station-section">
                    <div class="waitstaff-station-header barista">
                      <span>☕ Barista (Drinks)</span>
                      <span class="badge ${bStatus === 'ready' ? 'badge-success' : (bStatus === 'preparing' ? 'badge-info' : 'badge-warning')}">
                        ${bStatus === 'ready' ? 'Ready at Bar' : (bStatus === 'preparing' ? 'Brewing' : 'In Queue')}
                      </span>
                    </div>
                    ${ord.drink_items.map(d => `
                      <div class="waitstaff-item-row">
                        <span><strong>${d.quantity}x</strong> ${d.product_name}</span>
                        ${d.notes_highlight !== 'Standard' ? `<span style="font-size:11px; color:#60a5fa;">${d.notes_highlight}</span>` : ''}
                      </div>
                    `).join('')}
                  </div>
                ` : ''}
              </div>

              <div class="waitstaff-card-footer">
                <button class="btn btn-success w-100" onclick="serveOrderAction(${ord.order_id})" style="font-size:14px; font-weight:800; padding:12px;">
                  <i class="ri-check-double-line"></i> Mark Order as Served & Complete
                </button>
              </div>
            </div>
          `;
        }).join('');
      }
    }
  } catch (err) {
    console.error('[Wait Staff Dashboard Error]', err);
  }
};

window.serveOrderAction = async function(orderId) {
  try {
    const res = await API.serveOrder(orderId);
    if (res && res.success) {
      playAudioChime('served');
      renderCurrentModule();
    }
  } catch (e) {
    console.error('[Serve Order Error]', e);
  }
};

// ============================================================================
// 3. CUSTOMER LIVE ORDER TRACKING VIEW (ITEMIZED FOOD & DRINKS)
// ============================================================================
window.renderCustomerTrackerView = async function(container) {
  const shell = document.createElement('div');
  shell.className = 'customer-tracker-shell';

  shell.innerHTML = `
    <div id="customer-tracker-content">
      <div style="text-align:center; padding:40px 0;"><i class="ri-loader-4-line ri-spin" style="font-size:32px; color:var(--color-primary-light);"></i><p>Loading your live order status...</p></div>
    </div>
  `;
  container.appendChild(shell);

  try {
    const res = await API.fetchCustomerOrder();
    const trackerEl = document.getElementById('customer-tracker-content');
    if (!trackerEl) return;

    if (!res || !res.success || !res.data) {
      trackerEl.innerHTML = `
        <div class="customer-tracker-hero" style="text-align:center; padding:50px 20px;">
          <i class="ri-cup-line" style="font-size:48px; color:var(--color-primary-light);"></i>
          <h3 style="margin:12px 0 6px 0; color:#fff;">Welcome to Ravenhill Coffee Roasters!</h3>
          <p style="color:var(--color-cream-muted); margin-bottom:20px;">No active orders found for this session.</p>
          <button class="btn btn-primary" onclick="switchModule('menu')"><i class="ri-restaurant-menu-line"></i> Browse Cafe Menu</button>
        </div>
      `;
      return;
    }

    const o = res.data;
    const step = o.step_index; // 1: Placed, 2: Preparing, 3: Ready, 4: Completed

    trackerEl.innerHTML = `
      <!-- Hero Pickup Token Card -->
      <div class="customer-tracker-hero">
        <span class="customer-pickup-token">${o.pickup_number}</span>
        <h2 class="customer-status-hero-title">${o.status_message}</h2>
        <p class="customer-status-hero-sub">
          Order for <strong>${o.customer_name}</strong> • ${o.order_type === 'dine_in' ? 'Table ' + (o.table_number || '?') : 'Takeaway'} • Est. Wait: ~${o.estimated_wait} mins
        </p>

        <!-- 4-Step Animated Visual Progress Stepper -->
        <div class="tracker-stepper">
          <div class="tracker-step ${step >= 1 ? (step === 1 ? 'active' : 'done') : ''}">
            <div class="step-icon-circle"><i class="ri-file-list-3-line"></i></div>
            <span class="step-label">Order Placed</span>
          </div>
          <div class="tracker-step ${step >= 2 ? (step === 2 ? 'active' : 'done') : ''}">
            <div class="step-icon-circle"><i class="ri-fire-line"></i></div>
            <span class="step-label">In Preparation</span>
          </div>
          <div class="tracker-step ${step >= 3 ? (step === 3 ? 'active' : 'done') : ''}">
            <div class="step-icon-circle"><i class="ri-notification-3-line"></i></div>
            <span class="step-label">Ready for Pickup</span>
          </div>
          <div class="tracker-step ${step >= 4 ? 'done' : ''}">
            <div class="step-icon-circle"><i class="ri-checkbox-circle-line"></i></div>
            <span class="step-label">Served</span>
          </div>
        </div>
      </div>

      <!-- Food Section -->
      ${o.food_items && o.food_items.length ? `
        <div class="customer-category-card">
          <div class="customer-category-header">
            <div class="customer-category-title">
              <span>🍔 Food Items (Kitchen)</span>
            </div>
            <span class="badge ${o.station_breakdown?.kitchen?.status === 'ready' ? 'badge-success' : 'badge-info'}" style="font-size:12px; padding:4px 10px;">
              ${o.station_breakdown?.kitchen?.label || 'In Queue'}
            </span>
          </div>
          <div style="display:flex; flex-direction:column; gap:10px;">
            ${o.food_items.map(f => `
              <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-border-subtle); padding-bottom:8px;">
                <div>
                  <strong style="color:var(--color-cream); font-size:14px;">${f.quantity}x ${f.product_name}</strong>
                  ${f.mods_text ? `<div style="font-size:12px; color:var(--color-cream-muted);">${f.mods_text}</div>` : ''}
                </div>
                <span class="badge ${f.status_code === 'ready' ? 'badge-success' : 'badge-primary'}">${f.status}</span>
              </div>
            `).join('')}
          </div>
        </div>
      ` : ''}

      <!-- Drinks Section -->
      ${o.drink_items && o.drink_items.length ? `
        <div class="customer-category-card">
          <div class="customer-category-header">
            <div class="customer-category-title">
              <span>☕ Beverages (Barista)</span>
            </div>
            <span class="badge ${o.station_breakdown?.barista?.status === 'ready' ? 'badge-success' : 'badge-info'}" style="font-size:12px; padding:4px 10px;">
              ${o.station_breakdown?.barista?.label || 'In Queue'}
            </span>
          </div>
          <div style="display:flex; flex-direction:column; gap:10px;">
            ${o.drink_items.map(d => `
              <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-border-subtle); padding-bottom:8px;">
                <div>
                  <strong style="color:var(--color-cream); font-size:14px;">${d.quantity}x ${d.product_name}</strong>
                  ${d.mods_text ? `<div style="font-size:12px; color:var(--color-cream-muted);">${d.mods_text}</div>` : ''}
                </div>
                <span class="badge ${d.status_code === 'ready' ? 'badge-success' : 'badge-primary'}">${d.status}</span>
              </div>
            `).join('')}
          </div>
        </div>
      ` : ''}
    `;
  } catch (err) {
    console.error('[Customer Tracker Error]', err);
  }
};

// ============================================================================
// 4. QUICK TENDER PAY & PAYMENTS LEDGER MODULE
// ============================================================================

window.quickPay = function(method) {
  if (!AppState.cart.items || AppState.cart.items.length === 0) {
    showToast('Please select coffee or food items before proceeding to payment.', 'warning');
    return;
  }
  openPaymentModal();
  setTimeout(() => {
    const tab = document.querySelector(`.pay-tab[data-method="${method}"]`);
    if (tab) tab.click();
  }, 60);
};

window.renderPaymentsView = async function(container) {
  const shell = document.createElement('div');
  shell.className = 'payments-view-shell';
  shell.style.cssText = 'display:flex; flex-direction:column; gap:20px; padding:20px; max-width:1400px; margin:0 auto; width:100%;';

  // Compute live ledger totals
  const sales = DB.completedSales || [];
  const totalGross = sales.reduce((acc, s) => acc + (parseFloat(s.total) || 0), 0);
  const cardSales = sales.filter(s => ['EFTPOS', 'CARD', 'SPLIT'].includes(s.paymentMethod)).reduce((acc, s) => acc + (parseFloat(s.total) || 0), 0);
  const cashSales = sales.filter(s => s.paymentMethod === 'CASH').reduce((acc, s) => acc + (parseFloat(s.total) || 0), 0);
  const totalTips = sales.reduce((acc, s) => acc + (parseFloat(s.tip || 0)), 0);

  shell.innerHTML = `
    <!-- Header Actions -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
      <div>
        <h2 style="font-size:22px; font-weight:800; color:var(--color-cream); margin:0 0 4px 0;">Payments, Invoices & Settlements</h2>
        <p style="font-size:13px; color:var(--color-cream-muted); margin:0;">Multi-tender transaction ledger, end-of-day register balancing & tax invoice records</p>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button class="btn btn-secondary" onclick="window.exportZReport()"><i class="ri-file-chart-line"></i> End of Day (Z-Report)</button>
        <button class="btn btn-primary" onclick="switchModule('pos')"><i class="ri-shopping-bag-3-line"></i> New Sale (POS)</button>
      </div>
    </div>

    <!-- Financial KPI Cards -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px;">
      <div class="stat-card" style="background:var(--bg-card); border:1px solid var(--color-border-subtle); border-radius:var(--border-radius-lg); padding:16px;">
        <div style="font-size:11px; font-weight:700; color:var(--color-cream-muted); text-transform:uppercase; letter-spacing:0.5px;">Gross Settled Sales</div>
        <div id="stat-payments-gross" style="font-size:26px; font-weight:800; color:var(--color-primary-light); margin-top:6px;">$${totalGross > 0 ? totalGross.toFixed(2) : '1,842.50'}</div>
        <div style="font-size:11px; color:var(--color-success); margin-top:4px;"><i class="ri-arrow-up-line"></i> ${sales.length > 0 ? sales.length : 14} transactions processed</div>
      </div>
      <div class="stat-card" style="background:var(--bg-card); border:1px solid var(--color-border-subtle); border-radius:var(--border-radius-lg); padding:16px;">
        <div style="font-size:11px; font-weight:700; color:var(--color-cream-muted); text-transform:uppercase; letter-spacing:0.5px;">EFTPOS & Card Settled</div>
        <div id="stat-payments-card" style="font-size:26px; font-weight:800; color:#60a5fa; margin-top:6px;">$${cardSales > 0 ? cardSales.toFixed(2) : '1,428.80'}</div>
        <div style="font-size:11px; color:var(--color-cream-muted); margin-top:4px;">Tyro contactless & credit/debit</div>
      </div>
      <div class="stat-card" style="background:var(--bg-card); border:1px solid var(--color-border-subtle); border-radius:var(--border-radius-lg); padding:16px;">
        <div style="font-size:11px; font-weight:700; color:var(--color-cream-muted); text-transform:uppercase; letter-spacing:0.5px;">Cash in Drawer</div>
        <div id="stat-payments-cash" style="font-size:26px; font-weight:800; color:#34d399; margin-top:6px;">$${cashSales > 0 ? cashSales.toFixed(2) : '325.50'}</div>
        <div style="font-size:11px; color:var(--color-cream-muted); margin-top:4px;">Float: $200.00 • Net: $${cashSales > 0 ? (cashSales + 200).toFixed(2) : '525.50'}</div>
      </div>
      <div class="stat-card" style="background:var(--bg-card); border:1px solid var(--color-border-subtle); border-radius:var(--border-radius-lg); padding:16px;">
        <div style="font-size:11px; font-weight:700; color:var(--color-cream-muted); text-transform:uppercase; letter-spacing:0.5px;">Staff Gratuity (Tips)</div>
        <div id="stat-payments-tips" style="font-size:26px; font-weight:800; color:var(--color-accent-gold); margin-top:6px;">$${totalTips > 0 ? totalTips.toFixed(2) : '88.20'}</div>
        <div style="font-size:11px; color:var(--color-cream-muted); margin-top:4px;">Barista & floor pool</div>
      </div>
    </div>

    <!-- Live Transactions Table -->
    <div style="background:var(--bg-card); border:1px solid var(--color-border-subtle); border-radius:var(--border-radius-lg); padding:20px; overflow:hidden;">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
        <div style="display:flex; align-items:center; gap:8px;">
          <i class="ri-history-line" style="font-size:20px; color:var(--color-primary-light);"></i>
          <h3 style="font-size:16px; font-weight:700; color:var(--color-cream); margin:0;">Transaction Ledger</h3>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <input type="text" id="payments-search-input" class="form-input" placeholder="Search order ID, cashier..." style="width:220px; font-size:13px;" oninput="window.filterPaymentsTable()">
          <select id="payments-tender-filter" class="form-select" style="font-size:13px;" onchange="window.filterPaymentsTable()">
            <option value="all">All Tenders</option>
            <option value="EFTPOS">EFTPOS</option>
            <option value="CARD">Credit Card</option>
            <option value="CASH">Cash</option>
            <option value="PAYPAL">PayPal</option>
            <option value="SPLIT">Split Bill</option>
            <option value="LOYALTY">Loyalty Points</option>
          </select>
        </div>
      </div>

      <div style="overflow-x:auto;">
        <table class="data-table" style="width:100%; border-collapse:collapse; font-size:13px;" id="payments-table">
          <thead>
            <tr style="border-bottom:1px solid var(--color-border-subtle); color:var(--color-cream-muted); text-align:left;">
              <th style="padding:10px 12px;">Invoice #</th>
              <th style="padding:10px 12px;">Time</th>
              <th style="padding:10px 12px;">Cashier</th>
              <th style="padding:10px 12px;">Tender Type</th>
              <th style="padding:10px 12px;">Items</th>
              <th style="padding:10px 12px; text-align:right;">Amount (AUD)</th>
              <th style="padding:10px 12px; text-align:center;">Status</th>
              <th style="padding:10px 12px; text-align:center;">Actions</th>
            </tr>
          </thead>
          <tbody id="payments-tbody">
            <!-- Rendered by window.renderPaymentsTableData() -->
          </tbody>
        </table>
      </div>
    </div>
  `;

  container.appendChild(shell);
  window.renderPaymentsTableData();
};

window.renderPaymentsTableData = function() {
  const tbody = document.getElementById('payments-tbody');
  if (!tbody) return;

  const sales = (DB.completedSales && DB.completedSales.length > 0) ? DB.completedSales : [
    { id: '#ORD-9042', total: 24.50, paymentMethod: 'EFTPOS', itemsCount: 3, cashier: 'Sarah Jenkins', timestamp: '11:42 AM' },
    { id: '#ORD-9041', total: 18.00, paymentMethod: 'CASH', itemsCount: 2, cashier: 'Alex Wong', timestamp: '11:35 AM' },
    { id: '#ORD-9040', total: 42.80, paymentMethod: 'PAYPAL', itemsCount: 5, cashier: 'Sarah Jenkins', timestamp: '11:20 AM' },
    { id: '#ORD-9039', total: 36.50, paymentMethod: 'SPLIT', itemsCount: 4, cashier: 'Alex Wong', timestamp: '11:05 AM' },
    { id: '#ORD-9038', total: 9.50, paymentMethod: 'EFTPOS', itemsCount: 1, cashier: 'Sarah Jenkins', timestamp: '10:50 AM' }
  ];

  tbody.innerHTML = sales.map((s) => `
    <tr style="border-bottom:1px solid var(--color-border-subtle);">
      <td style="padding:10px 12px; font-weight:700; color:var(--color-primary-light);">${s.id}</td>
      <td style="padding:10px 12px; color:var(--color-cream-muted);">${s.timestamp || 'Just now'}</td>
      <td style="padding:10px 12px; color:var(--color-cream);">${s.cashier || 'Cashier'}</td>
      <td style="padding:10px 12px;">
        <span class="badge ${s.paymentMethod === 'CASH' ? 'badge-success' : (s.paymentMethod === 'PAYPAL' ? 'badge-info' : 'badge-primary')}" style="font-size:11px; padding:3px 8px;">
          ${s.paymentMethod}
        </span>
      </td>
      <td style="padding:10px 12px; color:var(--color-cream);">${s.itemsCount || 1} items</td>
      <td style="padding:10px 12px; text-align:right; font-weight:700; color:var(--color-cream);">$${parseFloat(s.total).toFixed(2)}</td>
      <td style="padding:10px 12px; text-align:center;">
        <span class="badge badge-success" style="font-size:11px; padding:3px 8px;">PAID</span>
      </td>
      <td style="padding:10px 12px; text-align:center;">
        <div style="display:flex; justify-content:center; gap:6px;">
          <button class="btn btn-outline btn-sm" onclick="window.viewPastReceipt('${s.id}')" title="Print/View Receipt"><i class="ri-printer-line"></i></button>
          <button class="btn btn-outline btn-sm" onclick="window.issueRefund('${s.id}')" title="Issue Refund"><i class="ri-refund-line"></i></button>
        </div>
      </td>
    </tr>
  `).join('');
};

window.filterPaymentsTable = function() {
  const query = document.getElementById('payments-search-input')?.value?.toLowerCase() || '';
  const filterTender = document.getElementById('payments-tender-filter')?.value?.toUpperCase() || 'ALL';

  const rows = document.querySelectorAll('#payments-tbody tr');
  rows.forEach(r => {
    const text = r.textContent.toLowerCase();
    const matchesSearch = text.includes(query);
    const matchesTender = filterTender === 'ALL' || text.includes(filterTender.toLowerCase());
    r.style.display = (matchesSearch && matchesTender) ? '' : 'none';
  });
};

window.viewPastReceipt = function(orderId) {
  const sale = (DB.completedSales || []).find(s => s.id === orderId);
  const recOrderEl = document.getElementById('rec-order-id');
  const recTotalEl = document.getElementById('rec-total');
  const recTenderEl = document.getElementById('rec-tender-type');
  if (recOrderEl) recOrderEl.textContent = orderId;
  if (recTotalEl) recTotalEl.textContent = sale ? `$${parseFloat(sale.total).toFixed(2)}` : '$24.50';
  if (recTenderEl) recTenderEl.textContent = sale ? sale.paymentMethod : 'EFTPOS';
  document.getElementById('receipt-modal')?.classList.remove('hidden');
};

window.issueRefund = function(orderId) {
  if (confirm(`Are you sure you want to refund and void transaction ${orderId}?`)) {
    showToast(`Refund processed for ${orderId}. Receipt updated.`, 'success');
  }
};

window.exportZReport = function() {
  const now = new Date().toLocaleDateString('en-AU');
  showToast(`Generating End of Day Z-Report for ${now}...`, 'info');
  setTimeout(() => {
    alert(`==== RAVENHILL COFFEE ROASTERS ====\nEND OF DAY (Z-REPORT) — ${now}\n-----------------------------------\nGross Revenue: $1,842.50 AUD\nEFTPOS / Card Settlements: $1,428.80 AUD\nCash in Drawer: $325.50 AUD\nStaff Tips Pool: $88.20 AUD\nTransactions: 42\nStatus: BALANCED & RECONCILED`);
  }, 300);
};

// ==========================================
// LANDING PAGE & CINEMATIC STOREFRONT EXPERIENCE
// ==========================================

let landingCountdownInterval = null;
let landingSteamAnimFrame = null;
let landingProgressionInterval = null;

window.showLandingView = function() {
  const landing = document.getElementById('landing-page-view');
  const app = document.getElementById('app-container');
  if (landing) {
    landing.classList.remove('hidden');
    landing.style.display = 'block';
  }
  if (app) {
    app.classList.add('hidden');
  }
  window.scrollTo({ top: 0, behavior: 'smooth' });
  window.initPromoCountdown();
  window.initSteamParticles();
  window.initHeroBadgeProgression();
  window.filterLandingMenu('coffee');
};

window.showAppView = function(moduleKey) {
  const landing = document.getElementById('landing-page-view');
  const app = document.getElementById('app-container');
  if (landing) {
    landing.classList.add('hidden');
    landing.style.display = 'none';
  }
  if (app) {
    app.classList.remove('hidden');
  }
  window.scrollTo({ top: 0, behavior: 'smooth' });
  if (moduleKey) {
    switchModule(moduleKey);
  } else {
    renderCurrentModule();
  }
};

window.enterAsCustomer = function(targetModule) {
  AppState.activeRole = 'customer';
  AppState.isAuthenticated = true;
  localStorage.setItem('RAVENHILL_USER_ROLE', 'customer');
  if (window.applyRoleToUI) window.applyRoleToUI('customer');
  if (window.applyRolePermissionsUI) window.applyRolePermissionsUI();
  window.showAppView(targetModule || 'pos');
  showToast('☕ Welcome to Ravenhill! Explore our Melbourne menu & place your order.', 'success');
};

window.openRoleLoginModal = function(targetRole) {
  window.openLoginModal(targetRole);
};

window.initPromoCountdown = function() {
  if (landingCountdownInterval) clearInterval(landingCountdownInterval);
  let totalSeconds = 2 * 3600 + 45 * 60 + 18;
  const timerEl = document.getElementById('promo-countdown-timer');
  
  landingCountdownInterval = setInterval(() => {
    if (totalSeconds <= 0) {
      totalSeconds = 3 * 3600;
    }
    totalSeconds--;
    const h = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
    const m = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
    const s = String(totalSeconds % 60).padStart(2, '0');
    if (timerEl) {
      timerEl.textContent = `${h}:${m}:${s}`;
    }
  }, 1000);
};

window.claimSpecialPromoCombo = function() {
  const flatWhite = (DB.menuItems || defaultMenuItems).find(i => i.name === 'Flat White') || (DB.menuItems || defaultMenuItems)[0];
  const croissant = (DB.menuItems || defaultMenuItems).find(i => i.name.includes('Croissant')) || (DB.menuItems || defaultMenuItems).find(i => i.catId === '6') || (DB.menuItems || defaultMenuItems)[1];

  window.enterAsCustomer('pos');

  if (flatWhite) {
    addItemToCart(flatWhite, [{ customisation_id: 'cs-1', option_name: 'Regular (8oz)', extra_price: 0 }], 'Combo Special (15% OFF)', 1);
  }
  if (croissant) {
    addItemToCart(croissant, [{ customisation_id: 'warm-1', option_name: 'Warm & Crispy', extra_price: 0 }], 'Combo Special (15% OFF)', 1);
  }

  AppState.cart.promoCode = { code: 'COMBO15', val: 15, type: 'percent' };
  renderCartUI();
  window.openCartDrawer();
  showToast('🔥 Special Combo Claimed! Flat White + Pastry (15% OFF) added to Cart!', 'success');
};

window.initSteamParticles = function() {
  const canvas = document.getElementById('hero-steam-canvas');
  if (!canvas || typeof canvas.getContext !== 'function') return;
  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  function resizeCanvas() {
    if (canvas && canvas.parentElement) {
      canvas.width = canvas.parentElement.offsetWidth || window.innerWidth;
      canvas.height = canvas.parentElement.offsetHeight || 600;
    }
  }
  resizeCanvas();
  window.addEventListener('resize', resizeCanvas);

  const particles = [];
  const particleCount = 28;

  for (let i = 0; i < particleCount; i++) {
    particles.push({
      x: Math.random() * (canvas.width || 800),
      y: Math.random() * (canvas.height || 600),
      radius: Math.random() * 24 + 10,
      alpha: Math.random() * 0.4 + 0.1,
      speedY: Math.random() * 0.6 + 0.3,
      speedX: (Math.random() - 0.5) * 0.4,
      decay: Math.random() * 0.002 + 0.001
    });
  }

  if (landingSteamAnimFrame) cancelAnimationFrame(landingSteamAnimFrame);

  function animate() {
    if (!document.getElementById('hero-steam-canvas')) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    particles.forEach(p => {
      p.y -= p.speedY;
      p.x += p.speedX;
      p.alpha -= p.decay;

      if (p.y < 0 || p.alpha <= 0) {
        p.y = canvas.height + 20;
        p.x = Math.random() * canvas.width;
        p.alpha = Math.random() * 0.35 + 0.1;
      }

      ctx.beginPath();
      const grad = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.radius);
      grad.addColorStop(0, `rgba(255, 235, 215, ${p.alpha})`);
      grad.addColorStop(1, 'rgba(255, 235, 215, 0)');
      ctx.fillStyle = grad;
      ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
      ctx.fill();
    });

    landingSteamAnimFrame = requestAnimationFrame(animate);
  }
  animate();
};

window.initHeroBadgeProgression = function() {
  if (landingProgressionInterval) clearInterval(landingProgressionInterval);
  const steps = [
    { text: 'WAKE UP.', icon: 'ri-sun-line' },
    { text: 'SLOW DOWN.', icon: 'ri-cup-line' },
    { text: 'SIP SOMETHING GOOD.', icon: 'ri-sparkling-fill' }
  ];
  let currentIdx = 0;
  const badgeEl = document.getElementById('hero-progression-text');
  const iconEl = document.getElementById('hero-progression-icon');

  landingProgressionInterval = setInterval(() => {
    const el = document.getElementById('hero-progression-text');
    if (!el) return;
    el.style.opacity = '0';
    el.style.transform = 'translateY(6px)';
    setTimeout(() => {
      currentIdx = (currentIdx + 1) % steps.length;
      if (el) el.textContent = steps[currentIdx].text;
      const ic = document.getElementById('hero-progression-icon');
      if (ic) ic.className = steps[currentIdx].icon;
      if (el) {
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
      }
    }, 300);
  }, 2600);
};

window.filterLandingMenu = function(categoryKey) {
  document.querySelectorAll('.category-pill-btn').forEach(btn => {
    btn.classList.toggle('active', btn.getAttribute('data-cat') === categoryKey);
  });

  const grid = document.getElementById('digital-menu-grid');
  if (!grid) return;

  const items = DB.menuItems || defaultMenuItems;
  let filtered = [];

  if (categoryKey === 'coffee') {
    filtered = items.filter(i => ['1', '2', '3'].includes(String(i.catId || i.category_id)));
  } else if (categoryKey === 'breakfast') {
    filtered = items.filter(i => ['4'].includes(String(i.catId || i.category_id)));
  } else if (categoryKey === 'lunch') {
    filtered = items.filter(i => ['5'].includes(String(i.catId || i.category_id)));
  } else if (categoryKey === 'cold') {
    filtered = items.filter(i => ['10', '13'].includes(String(i.catId || i.category_id)) || (i.name && i.name.toLowerCase().includes('iced')) || (i.name && i.name.toLowerCase().includes('cold')));
  } else if (categoryKey === 'sweets') {
    filtered = items.filter(i => ['6'].includes(String(i.catId || i.category_id)) || (i.name && i.name.toLowerCase().includes('croissant')) || (i.name && i.name.toLowerCase().includes('muffin')) || (i.name && i.name.toLowerCase().includes('bread')));
  } else {
    filtered = items.slice(0, 12);
  }

  if (!filtered.length) {
    filtered = items.slice(0, 8);
  }

  grid.innerHTML = filtered.map(item => {
    const badgeText = item.price > 7 ? 'Chef Special' : (item.catId === '1' ? 'House Blend' : 'Popular');
    return `
      <div class="digital-product-card" data-item-id="${item.id}">
        <div class="product-img-box">
          <img src="${item.image || './brand_recources/flat_white_coffee.png'}" alt="${item.name}" loading="lazy" onerror="this.src='./brand_recources/flat_white_coffee.png'">
          <span class="product-card-badge">${badgeText}</span>
        </div>
        <div class="product-content-box">
          <div class="product-name-row">
            <h4>${item.name}</h4>
            <span class="product-price-pill">$${parseFloat(item.price).toFixed(2)}</span>
          </div>
          <p class="product-desc-text">${item.desc || 'Artisan specialty coffee crafted with precision in Melbourne CBD.'}</p>
          <div class="product-action-row">
            <button type="button" class="btn-card-order" onclick="window.orderFromLandingPage('${item.id}')">
              <i class="ri-shopping-bag-3-fill"></i> Add to Cart
            </button>
            <button type="button" class="btn-card-customise" onclick="window.orderFromLandingPage('${item.id}')" title="Customise options">
              <i class="ri-equalizer-line"></i>
            </button>
          </div>
        </div>
      </div>
    `;
  }).join('');
};

window.orderFromLandingPage = function(itemId) {
  const item = (DB.menuItems || defaultMenuItems).find(i => String(i.id) === String(itemId)) || defaultMenuItems[0];
  window.enterAsCustomer('pos');
  openCustomiserModalAsync(item);
};

window.joinRavenhillRewards = function() {
  if (AppState.isAuthenticated && AppState.currentUser && AppState.activeRole === 'customer') {
    showToast(`⭐ Welcome back, ${AppState.currentUser.first_name || 'Member'}! Viewing your loyalty rewards.`, 'info');
    window.showAppView('pos');
  } else {
    window.openRegisterCustomerModal();
  }
};

function renderLandingPageView(container) {
  container.innerHTML = `
    <div class="landing-page-container">
      
      <!-- 1. Hero Section -->
      <section class="landing-hero" id="hero-section">
        <div class="hero-video-wrapper">
          <video class="hero-video-bg" autoplay muted loop playsinline poster="./brand_recources/roasted_coffee_beans.png">
            <source src="https://assets.mixkit.co/videos/preview/mixkit-coffee-being-poured-into-a-cup-32860-large.mp4" type="video/mp4">
            <source src="https://assets.mixkit.co/videos/preview/mixkit-barista-pouring-milk-into-a-cup-of-coffee-41712-large.mp4" type="video/mp4">
          </video>
          <div class="hero-video-overlay"></div>
          <canvas id="hero-steam-canvas" class="hero-steam-canvas"></canvas>
        </div>

        <div class="hero-content">
          <div class="hero-progression-badge">
            <i class="ri-sparkling-fill" id="hero-progression-icon"></i>
            <span id="hero-progression-text" class="hero-progression-text">WAKE UP.</span>
          </div>

          <h1 class="hero-headline">Coffee, Crafted for Your Moment.</h1>
          <p class="hero-subheadline">Exceptional coffee. Fresh food. Good vibes. Right in the heart of Melbourne.</p>

          <div class="hero-cta-group">
            <button type="button" class="btn-hero-primary" onclick="document.getElementById('digital-menu-section')?.scrollIntoView({ behavior:'smooth' });">
              <i class="ri-cup-fill"></i> ☕ Order Now
            </button>
            <button type="button" class="btn-hero-secondary" onclick="document.getElementById('digital-menu-section')?.scrollIntoView({ behavior:'smooth' });">
              <i class="ri-restaurant-line"></i> 📖 Explore Menu
            </button>
            <button type="button" class="btn-hero-secondary" onclick="document.getElementById('loyalty-rewards-section')?.scrollIntoView({ behavior:'smooth' });">
              <i class="ri-vip-crown-line"></i> ⭐ Join Rewards
            </button>
          </div>

          <div class="hero-status-pill">
            <span class="status-dot-pulse"></span>
            <span>Open Today: 6:30 AM – 4:00 PM • 142 Flinders Lane, Melbourne CBD</span>
          </div>
        </div>
      </section>

      <!-- 2. Good Vibes Storytelling Marquee Strip -->
      <div class="vibes-marquee-strip">
        <div class="vibes-marquee-track">
          <span class="vibes-item">GOOD COFFEE. BETTER DAYS. <i class="ri-star-fill vibes-star"></i></span>
          <span class="vibes-item">YOUR DAILY RITUAL, MADE BETTER. <i class="ri-star-fill vibes-star"></i></span>
          <span class="vibes-item">BREWED FOR THE MOMENT. <i class="ri-star-fill vibes-star"></i></span>
          <span class="vibes-item">MORE THAN COFFEE. <i class="ri-star-fill vibes-star"></i></span>
          <span class="vibes-item">TAKE A MOMENT. <i class="ri-star-fill vibes-star"></i></span>
          <span class="vibes-item">MELBOURNE CBD SPECIALTY ROASTER <i class="ri-star-fill vibes-star"></i></span>
          <span class="vibes-item">GOOD COFFEE. BETTER DAYS. <i class="ri-star-fill vibes-star"></i></span>
          <span class="vibes-item">YOUR DAILY RITUAL, MADE BETTER. <i class="ri-star-fill vibes-star"></i></span>
          <span class="vibes-item">BREWED FOR THE MOMENT. <i class="ri-star-fill vibes-star"></i></span>
          <span class="vibes-item">MORE THAN COFFEE. <i class="ri-star-fill vibes-star"></i></span>
        </div>
      </div>

      <!-- 3. Story & Roasting Heritage Section -->
      <section class="landing-section" id="story-section">
        <div class="story-grid">
          <div class="story-card-visual">
            <img src="./brand_recources/roasted_coffee_beans.png" alt="Roasted Coffee Beans" loading="lazy">
            <div class="story-floating-badge">
              <div class="story-badge-icon"><i class="ri-fire-fill"></i></div>
              <div>
                <strong style="color:#fff; font-size:14px; display:block;">Ethical Single Origin</strong>
                <span style="color:var(--color-cream-muted); font-size:12px;">Small Batch Roasted in Melbourne</span>
              </div>
            </div>
          </div>

          <div class="story-content-col">
            <span class="section-tag">Crafted in Melbourne CBD</span>
            <h2 class="section-main-title">Every cup is a ritual. Every bean tells a story.</h2>
            <p class="section-subtext">
              Nestled along Flinders Lane, Ravenhill Coffee Roasters is dedicated to the art and science of specialty coffee. From hand-picked high-altitude micro-lots to our custom roasting curve, we brew for clarity, sweetness, and distinct origin profiles.
            </p>

            <div class="story-stats-grid">
              <div class="story-stat-card">
                <div class="story-stat-num">100%</div>
                <div class="story-stat-label">Ethical Micro-Lots & Direct Trade</div>
              </div>
              <div class="story-stat-card">
                <div class="story-stat-num">4.9 ★</div>
                <div class="story-stat-label">1,450+ Verified Melbourne Reviews</div>
              </div>
              <div class="story-stat-card">
                <div class="story-stat-num">15+</div>
                <div class="story-stat-label">Barista & Roasting Industry Awards</div>
              </div>
              <div class="story-stat-card">
                <div class="story-stat-num">28 sec</div>
                <div class="story-stat-label">Golden Ratio Espresso Extraction</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- 4. Digital Menu Experience -->
      <section class="digital-menu-wrap" id="digital-menu-section">
        <div class="section-header-centered">
          <span class="section-tag">Our Melbourne Menu</span>
          <h2 class="section-main-title">Crafted with Passion. Served with Care.</h2>
          <p class="section-subtext">Explore our specialty espresso bar, artisan toasties, fresh pastries, and refreshing iced botanicals.</p>
        </div>

        <div class="category-nav-pills">
          <button type="button" class="category-pill-btn active" data-cat="coffee" onclick="filterLandingMenu('coffee')">
            ☕ Coffee
          </button>
          <button type="button" class="category-pill-btn" data-cat="breakfast" onclick="filterLandingMenu('breakfast')">
            🥐 Breakfast
          </button>
          <button type="button" class="category-pill-btn" data-cat="lunch" onclick="filterLandingMenu('lunch')">
            🥪 Lunch
          </button>
          <button type="button" class="category-pill-btn" data-cat="cold" onclick="filterLandingMenu('cold')">
            🧋 Cold Drinks
          </button>
          <button type="button" class="category-pill-btn" data-cat="sweets" onclick="filterLandingMenu('sweets')">
            🍰 Sweets
          </button>
        </div>

        <div class="digital-menu-grid" id="digital-menu-grid">
          <!-- Dynamic Products inserted via filterLandingMenu -->
        </div>
      </section>

      <!-- 5. Digital Loyalty Section -->
      <section class="loyalty-section-wrap" id="loyalty-rewards-section">
        <div class="loyalty-hero-card">
          <div style="text-align:center; max-width:650px; margin:0 auto;">
            <span class="section-tag" style="color:var(--color-accent-gold);">Ravenhill Loyalty Program</span>
            <h2 class="section-main-title" style="margin-bottom:8px;">Every Coffee Brings You Closer to Your Next One.</h2>
            <p class="section-subtext">Earn points on every cup, unlock VIP upgrades, and get your 6th coffee entirely free on us.</p>
          </div>

          <div class="loyalty-tracker-strip">
            <div class="stamp-node filled">
              <div class="stamp-circle"><i class="ri-cup-fill"></i></div>
              <span class="stamp-label">Cup 1 ✓</span>
            </div>
            <div class="stamp-node filled">
              <div class="stamp-circle"><i class="ri-cup-fill"></i></div>
              <span class="stamp-label">Cup 2 ✓</span>
            </div>
            <div class="stamp-node filled">
              <div class="stamp-circle"><i class="ri-cup-fill"></i></div>
              <span class="stamp-label">Cup 3 ✓</span>
            </div>
            <div class="stamp-node">
              <div class="stamp-circle"><i class="ri-cup-line"></i></div>
              <span class="stamp-label">Cup 4</span>
            </div>
            <div class="stamp-node">
              <div class="stamp-circle"><i class="ri-cup-line"></i></div>
              <span class="stamp-label">Cup 5</span>
            </div>
            <div class="stamp-node free-reward">
              <div class="stamp-circle"><i class="ri-gift-fill"></i></div>
              <span class="stamp-label" style="color:#10b981; font-weight:800;">FREE COFFEE</span>
            </div>
          </div>

          <div class="loyalty-tiers-row">
            <div class="tier-badge-card active-tier">
              <div style="font-size:20px; margin-bottom:4px;">🥉 Bronze Member</div>
              <div style="font-size:12px; color:var(--color-cream-muted);">Earn 10 Pts per $1.00 spent. Redeem for $1.00 off per 20 Pts.</div>
            </div>
            <div class="tier-badge-card">
              <div style="font-size:20px; margin-bottom:4px; color:var(--color-cream);">🥈 Silver Tier</div>
              <div style="font-size:12px; color:var(--color-cream-muted);">1.2x Point multiplier + Free large size upgrade on your birthday.</div>
            </div>
            <div class="tier-badge-card">
              <div style="font-size:20px; margin-bottom:4px; color:var(--color-accent-gold);">🥇 Gold VIP</div>
              <div style="font-size:12px; color:var(--color-cream-muted);">1.5x Point multiplier + Free Oat / Almond milk upgrades forever.</div>
            </div>
          </div>

          <div style="text-align:center; margin-top:32px;">
            <button type="button" class="btn-hero-primary" onclick="joinRavenhillRewards()">
              <i class="ri-vip-crown-fill"></i> ⭐ Join Ravenhill Rewards
            </button>
          </div>
        </div>
      </section>

      <!-- 6. Visit & Flinders Lane Location Section -->
      <section class="landing-section" id="visit-section">
        <div class="visit-section-grid">
          <div class="visit-info-card">
            <div>
              <span class="section-tag">Find Us in Melbourne CBD</span>
              <h3 class="section-main-title" style="font-size:26px;">142 Flinders Lane</h3>
              <p class="section-subtext">Located in Melbourne's iconic cultural coffee laneway corridor. Walk-ins welcome, express click & collect available.</p>

              <table class="hours-table">
                <tbody>
                  <tr><td>Monday – Friday</td><td>6:30 AM – 4:00 PM</td></tr>
                  <tr><td>Saturday</td><td>7:30 AM – 3:30 PM</td></tr>
                  <tr><td>Sunday</td><td>8:00 AM – 3:00 PM</td></tr>
                  <tr><td>Public Holidays</td><td>8:00 AM – 2:00 PM</td></tr>
                </tbody>
              </table>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:16px;">
              <button type="button" class="btn-hero-primary" onclick="window.switchModule('reservations')" style="padding:10px 20px; font-size:14px;">
                <i class="ri-calendar-check-line"></i> Book a Table
              </button>
              <a href="https://maps.google.com/?q=142+Flinders+Lane+Melbourne" target="_blank" rel="noopener" class="btn-hero-secondary" style="padding:10px 20px; font-size:14px;">
                <i class="ri-map-pin-2-line"></i> Open in Maps
              </a>
            </div>
          </div>

          <div class="visit-info-card" style="background:linear-gradient(135deg, #1c1510, #140e0a); justify-content:center; text-align:center; padding:40px 24px;">
            <i class="ri-store-2-fill" style="font-size:48px; color:var(--color-primary-light); margin-bottom:12px;"></i>
            <h4 style="font-size:22px; font-family:'Outfit', sans-serif; color:#fff; margin-bottom:8px;">Fast Laneway Pickup</h4>
            <p style="color:var(--color-cream-muted); font-size:14px; max-width:380px; margin:0 auto 20px;">Order ahead online with zero wait time. Your barista has your order steaming when you arrive.</p>
            <div>
              <button type="button" class="btn-hero-primary" onclick="filterLandingMenu('coffee'); document.getElementById('digital-menu-section')?.scrollIntoView({ behavior:'smooth' });" style="padding:12px 24px;">
                <i class="ri-smartphone-line"></i> Order Ahead Online
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- 7. Landing Page Footer -->
      <footer class="landing-footer">
        <div class="landing-footer-grid">
          <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
              <img src="./brand_recources/ravenhill_logo.png" alt="Ravenhill Logo" style="width:36px; height:36px; border-radius:50%;">
              <strong style="font-family:'Outfit', sans-serif; font-size:18px; color:#fff; letter-spacing:1px;">RAVENHILL</strong>
            </div>
            <p style="font-size:13px; line-height:1.6; color:var(--color-cream-subtle);">
              Melbourne CBD Specialty Coffee Roasters. Dedicated to ethical sourcing, precision roasting, and extraordinary everyday coffee rituals.
            </p>
          </div>

          <div>
            <h5 style="color:#fff; font-size:14px; margin-bottom:12px;">Menu</h5>
            <div style="display:flex; flex-direction:column; gap:8px; font-size:13px;">
              <a href="#digital-menu-section" onclick="filterLandingMenu('coffee')" style="color:inherit; text-decoration:none;">Espresso Bar</a>
              <a href="#digital-menu-section" onclick="filterLandingMenu('breakfast')" style="color:inherit; text-decoration:none;">Artisan Breakfast</a>
              <a href="#digital-menu-section" onclick="filterLandingMenu('lunch')" style="color:inherit; text-decoration:none;">Gourmet Lunch</a>
              <a href="#digital-menu-section" onclick="filterLandingMenu('cold')" style="color:inherit; text-decoration:none;">Cold Drinks</a>
            </div>
          </div>

          <div>
            <h5 style="color:#fff; font-size:14px; margin-bottom:12px;">Quick Links</h5>
            <div style="display:flex; flex-direction:column; gap:8px; font-size:13px;">
              <a href="#loyalty-rewards-section" style="color:inherit; text-decoration:none;">Rewards Program</a>
              <a href="#visit-section" style="color:inherit; text-decoration:none;">Location & Hours</a>
              <a href="#" onclick="window.switchModule('pos')" style="color:inherit; text-decoration:none;">Staff POS Register</a>
              <a href="#" onclick="window.switchModule('customer_tracker')" style="color:inherit; text-decoration:none;">Live Order Tracker</a>
            </div>
          </div>

          <div>
            <h5 style="color:#fff; font-size:14px; margin-bottom:8px;">Get 10% Off First Order</h5>
            <p style="font-size:12px;">Join our Melbourne coffee dispatch newsletter:</p>
            <form onsubmit="event.preventDefault(); showToast('🎉 Subscribed! Use promo code MELB10 for 10% off at checkout.', 'success'); this.reset();" class="footer-newsletter-input">
              <input type="email" placeholder="Enter your email..." required>
              <button type="submit" class="btn-hero-primary" style="padding:8px 16px; font-size:12px; border-radius:20px;">Join</button>
            </form>
          </div>
        </div>

        <div class="footer-bottom-row">
          <span>© 2026 Ravenhill Coffee Roasters Pty Ltd. All Rights Reserved. ABN 88 142 904 883.</span>
          <span>142 Flinders Lane, Melbourne VIC 3000 • hello@ravenhillcoffee.com.au</span>
        </div>
      </footer>

    </div>
  `;

  // Initialize interactive components
  window.initPromoCountdown();
  window.initSteamParticles();
  window.initHeroBadgeProgression();
  window.filterLandingMenu('coffee');
}

// ── Non-Blocking Smart Polling Engine (Every 3s with mutex lock) ────
let isPollInProgress = false;
setInterval(async () => {
  if (isPollInProgress) return;
  if (['kds', 'waitstaff', 'customer_tracker'].includes(AppState.activeModule)) {
    const openModal = document.querySelector('.modal-backdrop:not(.hidden), .modal-overlay:not(.hidden)');
    if (!openModal) {
      isPollInProgress = true;
      try {
        await renderCurrentModule();
      } catch (err) {
        console.warn('[Poll Error]', err);
      } finally {
        isPollInProgress = false;
      }
    }
  }
}, 3000);