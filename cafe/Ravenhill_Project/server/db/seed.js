const { runAsync, initDatabase } = require('./database');

const seedData = async () => {
  console.log('Seeding SQLite database with initial Ravenhill Coffee data...');

  await initDatabase();

  // Clear existing tables
  await runAsync('DELETE FROM categories');
  await runAsync('DELETE FROM menu_items');
  await runAsync('DELETE FROM inventory');
  await runAsync('DELETE FROM tables');
  await runAsync('DELETE FROM staff');
  await runAsync('DELETE FROM discounts');
  await runAsync('DELETE FROM orders');

  // Categories
  const categories = [
    { id: 'cat-espresso', name: 'Espresso & Coffee', icon: 'ri-cup-line' },
    { id: 'cat-filter', name: 'Filter & Single Origin', icon: 'ri-drop-line' },
    { id: 'cat-cold', name: 'Cold Coffee & Drinks', icon: 'ri-goblet-line' },
    { id: 'cat-tea', name: 'Artisan Teas & Chai', icon: 'ri-plant-line' },
    { id: 'cat-bakery', name: 'Pastries & Food', icon: 'ri-cake-3-line' },
    { id: 'cat-beans', name: 'Retail Beans & Merch', icon: 'ri-box-line' },
    { id: 'cat-containers', name: 'Water Jars & Containers', icon: 'ri-flask-line' }
  ];


  for (const c of categories) {
    await runAsync(
      'INSERT INTO categories (id, name, icon) VALUES (?, ?, ?)',
      [c.id, c.name, c.icon]
    );
  }

  // Menu Items
  const menuItems = [
    {
      id: 'item-101',
      catId: 'cat-espresso',
      name: 'Single Origin Flat White',
      desc: 'Silky micro-foam poured over seasonal espresso.',
      price: 4.80,
      icon: 'ri-cup-fill',
      badge: 'Bestseller',
      hasModifiers: 1,
      recipe: JSON.stringify({ coffeeBeansGrams: 18, milkMl: 180, cup: 'cup-8oz' })
    },
    {
      id: 'item-102',
      catId: 'cat-espresso',
      name: 'Ravenhill House Latte',
      desc: 'Rich Ravenhill Reserve espresso with steamed milk.',
      price: 4.80,
      icon: 'ri-cup-line',
      badge: null,
      hasModifiers: 1,
      recipe: JSON.stringify({ coffeeBeansGrams: 18, milkMl: 220, cup: 'cup-8oz' })
    },
    {
      id: 'item-103',
      catId: 'cat-espresso',
      name: 'Double Espresso (Short Black)',
      desc: 'Concentrated 36g extract of single origin roast.',
      price: 4.20,
      icon: 'ri-cup-line',
      badge: 'Pure',
      hasModifiers: 1,
      recipe: JSON.stringify({ coffeeBeansGrams: 18, milkMl: 0, cup: 'cup-demo' })
    },
    {
      id: 'item-104',
      catId: 'cat-espresso',
      name: 'Long Black (Double Ristretto)',
      desc: 'Hot filtered water topped with fresh espresso crema.',
      price: 4.50,
      icon: 'ri-cup-line',
      badge: null,
      hasModifiers: 1,
      recipe: JSON.stringify({ coffeeBeansGrams: 18, milkMl: 0, cup: 'cup-8oz' })
    },
    {
      id: 'item-105',
      catId: 'cat-espresso',
      name: 'Cappuccino (Dark Chocolate)',
      desc: 'Espresso & textured milk dusted with Belgian cocoa.',
      price: 4.80,
      icon: 'ri-cup-fill',
      badge: null,
      hasModifiers: 1,
      recipe: JSON.stringify({ coffeeBeansGrams: 18, milkMl: 200, cup: 'cup-8oz' })
    },
    {
      id: 'item-106',
      catId: 'cat-espresso',
      name: 'Piccolo Latte',
      desc: 'Ristretto shot topped with warm silky milk in 90ml glass.',
      price: 4.20,
      icon: 'ri-cup-line',
      badge: null,
      hasModifiers: 1,
      recipe: JSON.stringify({ coffeeBeansGrams: 15, milkMl: 70, cup: 'cup-demo' })
    },
    {
      id: 'item-201',
      catId: 'cat-filter',
      name: 'Batch Brew Filter (Ethiopia Yirgacheffe)',
      desc: 'Floral, bergamot, lemon zest, sweet honey cup.',
      price: 5.50,
      icon: 'ri-drop-fill',
      badge: 'Single Origin',
      hasModifiers: 0,
      recipe: JSON.stringify({ coffeeBeansGrams: 20, milkMl: 0, cup: 'cup-12oz' })
    },
    {
      id: 'item-202',
      catId: 'cat-filter',
      name: 'V60 Pour-over (Colombia Pink Bourbon)',
      desc: 'Hand-poured filter. Pink grapefruit, peach & caramel notes.',
      price: 7.50,
      icon: 'ri-bubble-chart-line',
      badge: 'Reserve',
      hasModifiers: 0,
      recipe: JSON.stringify({ coffeeBeansGrams: 22, milkMl: 0, cup: 'cup-12oz' })
    },
    {
      id: 'item-301',
      catId: 'cat-cold',
      name: '16hr Cold Brew Coffee',
      desc: 'Steeped for 16 hours. Chocolate finish over sphere ice.',
      price: 6.00,
      icon: 'ri-goblet-fill',
      badge: 'Popular',
      hasModifiers: 1,
      recipe: JSON.stringify({ coffeeBeansGrams: 25, milkMl: 0, cup: 'cup-16oz' })
    },
    {
      id: 'item-302',
      catId: 'cat-cold',
      name: 'Iced Oat Milk Latte',
      desc: 'Double shot over happy happy soy boy oat milk & ice.',
      price: 6.50,
      icon: 'ri-goblet-line',
      badge: 'CBD Fav',
      hasModifiers: 1,
      recipe: JSON.stringify({ coffeeBeansGrams: 18, milkMl: 250, cup: 'cup-16oz' })
    },
    {
      id: 'item-401',
      catId: 'cat-tea',
      name: 'Prana Sticky Chai Latte',
      desc: 'Fresh black tea steeped with whole spices & Victorian honey.',
      price: 5.80,
      icon: 'ri-plant-fill',
      badge: null,
      hasModifiers: 1,
      recipe: JSON.stringify({ coffeeBeansGrams: 0, milkMl: 220, cup: 'cup-8oz' })
    },
    {
      id: 'item-501',
      catId: 'cat-bakery',
      name: 'French Butter Croissant',
      desc: 'Flaky 81-layer sourdough butter croissant baked daily.',
      price: 6.00,
      icon: 'ri-cake-3-line',
      badge: 'Fresh Daily',
      hasModifiers: 0,
      recipe: JSON.stringify({ croissantQty: 1 })
    },
    {
      id: 'item-502',
      catId: 'cat-bakery',
      name: 'Almond & Frangipane Toastie',
      desc: 'Twice-baked butter croissant filled with almond cream.',
      price: 7.50,
      icon: 'ri-cake-3-fill',
      badge: null,
      hasModifiers: 0,
      recipe: JSON.stringify({ croissantQty: 1 })
    },
    {
      id: 'item-601',
      catId: 'cat-beans',
      name: 'Ravenhill Reserve Beans (250g)',
      desc: 'Signature espresso blend. Dark cocoa, hazelnut, plum.',
      price: 22.00,
      icon: 'ri-box-3-fill',
      badge: 'Retail Pack',
      hasModifiers: 0,
      recipe: JSON.stringify({ beansPackQty: 1 })
    },
    {
      id: 'item-water-01',
      catId: 'cat-containers',
      name: '1L Glass Water Carafe',
      desc: 'Ergonomic borosilicate glass carafe for table serving.',
      price: 14.50,
      icon: 'ri-flask-line',
      badge: 'Essential',
      hasModifiers: 0,
      recipe: null
    },
    {
      id: 'item-water-02',
      catId: 'cat-containers',
      name: 'Ravenhill Stainless Flask 500ml',
      desc: 'Double-walled insulated thermal flask with laser etched logo.',
      price: 28.00,
      icon: 'ri-copper-coin-line',
      badge: 'Merch',
      hasModifiers: 0,
      recipe: null
    },
    {
      id: 'item-water-03',
      catId: 'cat-containers',
      name: 'Amber Glass Brew Jar 2L',
      desc: 'UV-protective amber glass jar for cold brew steeping & water.',
      price: 22.00,
      icon: 'ri-goblet-line',
      badge: null,
      hasModifiers: 0,
      recipe: null
    },
    {
      id: 'item-water-04',
      catId: 'cat-containers',
      name: 'Roastery Cold Brew Growler 1.5L',
      desc: 'Heavy duty glass growler with swing-top seal.',
      price: 35.00,
      icon: 'ri-repeat-line',
      badge: 'Reusable',
      hasModifiers: 0,
      recipe: null
    }
  ];


  for (const item of menuItems) {
    await runAsync(
      `INSERT INTO menu_items (id, catId, name, desc, price, icon, badge, hasModifiers, recipe) 
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [item.id, item.catId, item.name, item.desc, item.price, item.icon, item.badge, item.hasModifiers, item.recipe]
    );
  }

  // Inventory
  const inventory = [
    { id: 'INV-01', name: 'Ravenhill Reserve Beans', stockQty: 18.5, unit: 'kg', minThreshold: 5.0, status: 'good', supplier: 'Melbourne Coffee Exporters', unitCost: 18.00 },
    { id: 'INV-02', name: 'Ethiopia Yirgacheffe SO', stockQty: 3.2, unit: 'kg', minThreshold: 4.0, status: 'low', supplier: 'Melbourne Coffee Exporters', unitCost: 22.00 },
    { id: 'INV-03', name: 'Happy Happy Soy Boy Oat Milk', stockQty: 32.0, unit: 'Liters', minThreshold: 15.0, status: 'good', supplier: 'St David Dairy Victoria', unitCost: 2.40 },
    { id: 'INV-04', name: 'Milk Lab Almond Milk', stockQty: 14.0, unit: 'Liters', minThreshold: 10.0, status: 'good', supplier: 'St David Dairy Victoria', unitCost: 2.50 },
    { id: 'INV-05', name: 'Full Cream Organic Milk', stockQty: 45.0, unit: 'Liters', minThreshold: 20.0, status: 'good', supplier: 'St David Dairy Victoria', unitCost: 1.80 },
    { id: 'INV-06', name: 'Eco Bio-Cups (8oz)', stockQty: 420.0, unit: 'Units', minThreshold: 100.0, status: 'good', supplier: 'BioPak Sustainable Solutions', unitCost: 0.12 },
    { id: 'INV-07', name: 'Fresh Butter Croissants', stockQty: 8.0, unit: 'Units', minThreshold: 12.0, status: 'low', supplier: 'Local Artisan Bakery', unitCost: 2.80 }
  ];

  for (const inv of inventory) {
    await runAsync(
      `INSERT INTO inventory (id, name, stockQty, unit, minThreshold, status, supplier, unitCost)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
      [inv.id, inv.name, inv.stockQty, inv.unit, inv.minThreshold, inv.status, inv.supplier, inv.unitCost]
    );
  }

  // Tables
  const tables = [
    { id: 'T-01', name: 'Table 01', section: 'Patio Lane', capacity: 2, status: 'available', orderId: null, timeOccupied: null, reservedFor: null },
    { id: 'T-02', name: 'Table 02', section: 'Patio Lane', capacity: 2, status: 'occupied', orderId: '#ORD-9038', timeOccupied: '22m', reservedFor: null },
    { id: 'T-03', name: 'Table 03', section: 'Window Bar', capacity: 4, status: 'occupied', orderId: '#ORD-9040', timeOccupied: '10m', reservedFor: null },
    { id: 'T-04', name: 'Table 04', section: 'Main Hall', capacity: 4, status: 'available', orderId: null, timeOccupied: null, reservedFor: null },
    { id: 'T-05', name: 'Table 05', section: 'Main Hall', capacity: 6, status: 'reserved', orderId: null, timeOccupied: null, reservedFor: 'David K. @ 11:30 AM' },
    { id: 'T-06', name: 'Table 06', section: 'Espresso Bar', capacity: 2, status: 'available', orderId: null, timeOccupied: null, reservedFor: null },
    { id: 'T-07', name: 'Table 07', section: 'Espresso Bar', capacity: 2, status: 'cleaning', orderId: null, timeOccupied: null, reservedFor: null },
    { id: 'T-08', name: 'Table 08', section: 'Booth Corner', capacity: 4, status: 'available', orderId: null, timeOccupied: null, reservedFor: null },
    { id: 'T-09', name: 'Table 09', section: 'Mezzanine', capacity: 4, status: 'available', orderId: null, timeOccupied: null, reservedFor: null },
    { id: 'T-10', name: 'Table 10', section: 'Alfresco Garden', capacity: 6, status: 'available', orderId: null, timeOccupied: null, reservedFor: null }
  ];

  for (const t of tables) {
    await runAsync(
      `INSERT INTO tables (id, name, section, capacity, status, orderId, timeOccupied, reservedFor)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
      [t.id, t.name, t.section, t.capacity, t.status, t.orderId, t.timeOccupied, t.reservedFor]
    );
  }

  // Staff
  const staff = [
    { id: 'EMP-01', name: 'Sarah Lin', role: 'Lead Cashier', pin: '1234', status: 'Active', shiftStart: '06:30 AM', salesCount: 42 },
    { id: 'EMP-02', name: 'Liam O\'Connor', role: 'Head Barista', pin: '5678', status: 'Active', shiftStart: '06:30 AM', salesCount: 58 },
    { id: 'EMP-03', name: 'Alex Vance', role: 'Store Manager', pin: '4455', status: 'Active', shiftStart: '06:00 AM', salesCount: 84 },
    { id: 'EMP-04', name: 'Chloe Bennett', role: 'Wait Staff / Floor', pin: '7788', status: 'Active', shiftStart: '07:30 AM', salesCount: 31 }
  ];


  for (const s of staff) {
    await runAsync(
      `INSERT INTO staff (id, name, role, pin, status, shiftStart, salesCount)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [s.id, s.name, s.role, s.pin, s.status, s.shiftStart, s.salesCount]
    );
  }

  // Discounts
  const discounts = [
    { code: 'MELB10', description: '10% CBD Worker Discount', type: 'percent', val: 10, minSpend: 0 },
    { code: 'RAVEN20', description: '$2.00 Off Retail Beans', type: 'fixed', val: 2.00, minSpend: 15.00 },
    { code: 'COFFEELOVER', description: '15% Off Total Order', type: 'percent', val: 15, minSpend: 20.00 }
  ];

  for (const d of discounts) {
    await runAsync(
      `INSERT INTO discounts (code, description, type, val, minSpend)
       VALUES (?, ?, ?, ?, ?)`,
      [d.code, d.description, d.type, d.val, d.minSpend]
    );
  }

  // Clear and seed reservations
  await runAsync('DELETE FROM reservations');
  const reservations = [
    { id: 'RES-101', customerName: 'David Kim', partySize: 6, tableId: 'T-05', time: '11:30 AM', status: 'Confirmed', contact: '0412 889 201' },
    { id: 'RES-102', customerName: 'Alex Mercer', partySize: 4, tableId: 'T-08', time: '12:30 PM', status: 'Confirmed', contact: '0400 998 123' },
    { id: 'RES-103', customerName: 'Chloe Lin', partySize: 4, tableId: 'T-10', time: '01:15 PM', status: 'Pending', contact: '0488 442 109' }
  ];

  for (const r of reservations) {
    await runAsync(
      `INSERT INTO reservations (id, customerName, partySize, tableId, time, status, contact)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [r.id, r.customerName, r.partySize, r.tableId, r.time, r.status, r.contact]
    );
  }

  // Orders
  const initialOrders = [
    {
      id: '#ORD-9038',
      orderNum: '9038',
      type: 'dine_in',
      tableId: 'T-02',
      customerName: 'Marcus Vance',
      itemsJson: JSON.stringify([
        { name: 'Single Origin Flat White', qty: 2, price: 4.80, mods: ['Medium (12oz)', 'Oat Milk'] },
        { name: 'French Butter Croissant', qty: 1, price: 6.00, mods: [] }
      ]),
      subtotal: 15.60,
      tax: 1.56,
      discount: 0,
      total: 15.60,
      paymentMethod: 'card',
      status: 'preparing',
      createdAt: new Date(Date.now() - 14 * 60 * 1000).toISOString()
    },
    {
      id: '#ORD-9040',
      orderNum: '9040',
      type: 'dine_in',
      tableId: 'T-03',
      customerName: 'Elena Rostova',
      itemsJson: JSON.stringify([
        { name: '16hr Cold Brew Coffee', qty: 1, price: 6.00, mods: ['Sphere Ice'] },
        { name: 'Almond & Frangipane Toastie', qty: 1, price: 7.50, mods: [] }
      ]),
      subtotal: 13.50,
      tax: 1.35,
      discount: 0,
      total: 13.50,
      paymentMethod: 'card',
      status: 'pending',
      createdAt: new Date(Date.now() - 6 * 60 * 1000).toISOString()
    }
  ];

  for (const o of initialOrders) {
    await runAsync(
      `INSERT INTO orders (id, orderNum, type, tableId, customerName, itemsJson, subtotal, tax, discount, total, paymentMethod, status, createdAt)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [o.id, o.orderNum, o.type, o.tableId, o.customerName, o.itemsJson, o.subtotal, o.tax, o.discount, o.total, o.paymentMethod, o.status, o.createdAt]
    );
  }

  // Clear and seed transactions
  await runAsync('DELETE FROM transactions');
  const initialTransactions = [
    { id: 'TXN-001', orderId: '#ORD-9035', total: 18.50, paymentMethod: 'EFTPOS', itemsCount: 3, cashier: 'Sarah Lin', timestamp: '08:12 AM' },
    { id: 'TXN-002', orderId: '#ORD-9036', total: 9.60, paymentMethod: 'Cash', itemsCount: 2, cashier: 'Sarah Lin', timestamp: '08:45 AM' },
    { id: 'TXN-003', orderId: '#ORD-9037', total: 28.00, paymentMethod: 'EFTPOS', itemsCount: 4, cashier: 'Liam O\'Connor', timestamp: '09:20 AM' }
  ];

  for (const txn of initialTransactions) {
    await runAsync(
      `INSERT INTO transactions (id, orderId, total, paymentMethod, itemsCount, cashier, timestamp)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [txn.id, txn.orderId, txn.total, txn.paymentMethod, txn.itemsCount, txn.cashier, txn.timestamp]
    );
  }

  // Seed app_state (next order number)
  await runAsync('DELETE FROM app_state');
  await runAsync("INSERT INTO app_state (key, value) VALUES ('next_order_num', '9043')");

  // Seed customers
  await runAsync('DELETE FROM customers');
  const initialCustomers = [
    { id: 'CUST-101', name: 'Marcus Vance', mobile: '0412 889 012', email: 'marcus.v@melbtech.com', points: 450, tier: 'Reserve Gold', visits: 38 },
    { id: 'CUST-102', name: 'Elena Rostova', mobile: '0433 711 904', email: 'elena.r@designstudio.au', points: 180, tier: 'Roast Silver', visits: 16 },
    { id: 'CUST-103', name: 'James Thornton', mobile: '0400 998 123', email: 'j.thornton@legal.com.au', points: 620, tier: 'Reserve Gold', visits: 52 },
    { id: 'CUST-104', name: 'Chloe Lin', mobile: '0488 442 109', email: 'chloe@flindersarts.vic.gov', points: 90, tier: 'Bean Bronze', visits: 8 }
  ];

  for (const cust of initialCustomers) {
    await runAsync(
      `INSERT INTO customers (id, name, mobile, email, points, tier, visits)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [cust.id, cust.name, cust.mobile, cust.email, cust.points, cust.tier, cust.visits]
    );
  }

  console.log('Database seeding complete!');
};

if (require.main === module) {
  seedData()
    .then(() => process.exit(0))
    .catch((err) => {
      console.error('Seeding failed:', err);
      process.exit(1);
    });
}

module.exports = seedData;
