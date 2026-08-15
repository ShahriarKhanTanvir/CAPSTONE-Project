import re

def patch_index_html():
    with open('index.html', 'r', encoding='utf-8') as f:
        html = f.read()

    # Remove http://localhost:5000/socket.io/socket.io.js to prevent mixed-content blocks
    html = html.replace('<script src="http://localhost:5000/socket.io/socket.io.js"></script>', '<!-- socket.io optional -->')
    
    with open('index.html', 'w', encoding='utf-8') as f:
        f.write(html)
    print("Patched index.html!")

def patch_app_js():
    with open('app.js', 'r', encoding='utf-8') as f:
        js = f.read()

    # Robust API_BASE definition
    old_api_base = """const API_BASE = window.location.hostname === 'localhost' && window.location.port === '5000'
  ? 'http://localhost:5000/api'
  : (window.location.origin + window.location.pathname.replace(/\\/[^\\/]*$/, '') + '/api');"""

    new_api_base = """// Auto-detect base API URL supporting direct path and subfolders like /kent/cpro306/g1/
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
const API_BASE = getAPIBase();"""

    js = js.replace(old_api_base, new_api_base)

    # Patch syncBackendData to handle all endpoint response structures and avoid Promise.all failure
    old_sync_func_start = "async function syncBackendData() {"
    
    # We will replace the entire syncBackendData function
    new_sync_func = """async function syncBackendData() {
  try {
    console.log('[Backend Sync] Fetching live data from:', API_BASE);
    
    // Fetch all endpoints with individual fault tolerance
    const [
      categoriesRes, menuItemsRes, inventoryRes, tablesRes, 
      ordersRes, reservationsRes, customersRes, transactionsRes, 
      discountsRes, feedbackRes, staffRes, savedPermissions, nextOrderNum
    ] = await Promise.allSettled([
      API.fetchCategories(),
      API.fetchMenuItems(),
      API.fetchInventory(),
      API.fetchTables(),
      API.fetchOrders(),
      API.fetchReservations(),
      API.fetchCustomers(),
      API.fetchTransactions(),
      API.fetchDiscounts(),
      API.fetchFeedback(),
      API.fetchStaff(),
      API.fetchState('rolePermissions'),
      API.fetchNextOrderNum()
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
}"""

    pattern = re.compile(r"async function syncBackendData\(\)\s*\{.*?\n\}\n", re.DOTALL)
    js = pattern.sub(new_sync_func + "\n", js)

    with open('app.js', 'w', encoding='utf-8') as f:
        f.write(js)
    print("Patched app.js!")

if __name__ == '__main__':
    patch_index_html()
    patch_app_js()
