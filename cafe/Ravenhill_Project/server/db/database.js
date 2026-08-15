const sqlite3 = require('sqlite3').verbose();
const path = require('path');
const fs = require('fs');

const dbDir = path.join(__dirname);
if (!fs.existsSync(dbDir)) {
  fs.mkdirSync(dbDir, { recursive: true });
}

const dbPath = path.join(dbDir, 'ravenhill.db');
const db = new sqlite3.Database(dbPath, (err) => {
  if (err) {
    console.error('Error connecting to SQLite database:', err.message);
  } else {
    console.log('Connected to SQLite database at:', dbPath);
  }
});

// Utility helpers for Promises
const runAsync = (sql, params = []) => {
  return new Promise((resolve, reject) => {
    db.run(sql, params, function (err) {
      if (err) reject(err);
      else resolve({ id: this.lastID, changes: this.changes });
    });
  });
};

const allAsync = (sql, params = []) => {
  return new Promise((resolve, reject) => {
    db.all(sql, params, (err, rows) => {
      if (err) reject(err);
      else resolve(rows);
    });
  });
};

const getAsync = (sql, params = []) => {
  return new Promise((resolve, reject) => {
    db.get(sql, params, (err, row) => {
      if (err) reject(err);
      else resolve(row);
    });
  });
};

// Initialize Table Schema
const initDatabase = async () => {
  try {
    await runAsync(`
      CREATE TABLE IF NOT EXISTS categories (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        icon TEXT NOT NULL
      )
    `);

    await runAsync(`
      CREATE TABLE IF NOT EXISTS menu_items (
        id TEXT PRIMARY KEY,
        catId TEXT NOT NULL,
        name TEXT NOT NULL,
        desc TEXT,
        price REAL NOT NULL,
        icon TEXT,
        badge TEXT,
        hasModifiers INTEGER DEFAULT 0,
        recipe TEXT,
        FOREIGN KEY (catId) REFERENCES categories(id)
      )
    `);

    await runAsync(`
      CREATE TABLE IF NOT EXISTS orders (
        id TEXT PRIMARY KEY,
        orderNum TEXT NOT NULL,
        type TEXT DEFAULT 'dine-in',
        tableId TEXT,
        customerName TEXT,
        itemsJson TEXT NOT NULL,
        subtotal REAL NOT NULL,
        tax REAL NOT NULL,
        discount REAL DEFAULT 0,
        total REAL NOT NULL,
        paymentMethod TEXT DEFAULT 'card',
        status TEXT DEFAULT 'pending',
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    `);

    await runAsync(`
      CREATE TABLE IF NOT EXISTS inventory (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        stockQty REAL NOT NULL,
        unit TEXT NOT NULL,
        minThreshold REAL NOT NULL,
        status TEXT DEFAULT 'ok',
        supplier TEXT,
        unitCost REAL
      )
    `);

    await runAsync(`
      CREATE TABLE IF NOT EXISTS tables (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        section TEXT NOT NULL,
        capacity INTEGER NOT NULL,
        status TEXT DEFAULT 'available',
        orderId TEXT,
        timeOccupied TEXT,
        reservedFor TEXT
      )
    `);

    await runAsync(`
      CREATE TABLE IF NOT EXISTS staff (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        role TEXT NOT NULL,
        pin TEXT NOT NULL,
        status TEXT DEFAULT 'Active',
        shiftStart TEXT,
        salesCount INTEGER DEFAULT 0
      )
    `);

    await runAsync(`
      CREATE TABLE IF NOT EXISTS discounts (
        code TEXT PRIMARY KEY,
        description TEXT NOT NULL,
        type TEXT NOT NULL,
        val REAL NOT NULL,
        minSpend REAL DEFAULT 0
      )
    `);

    await runAsync(`
      CREATE TABLE IF NOT EXISTS reservations (
        id TEXT PRIMARY KEY,
        customerName TEXT NOT NULL,
        partySize INTEGER NOT NULL,
        tableId TEXT NOT NULL,
        time TEXT NOT NULL,
        status TEXT DEFAULT 'Confirmed',
        contact TEXT
      )
    `);

    await runAsync(`
      CREATE TABLE IF NOT EXISTS customers (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        mobile TEXT,
        email TEXT,
        points INTEGER DEFAULT 0,
        tier TEXT DEFAULT 'Bean Bronze',
        visits INTEGER DEFAULT 1
      )
    `);

    await runAsync(`
      CREATE TABLE IF NOT EXISTS transactions (
        id TEXT PRIMARY KEY,
        orderId TEXT NOT NULL,
        total REAL NOT NULL,
        paymentMethod TEXT NOT NULL,
        itemsCount INTEGER NOT NULL,
        cashier TEXT,
        timestamp TEXT NOT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    `);

    await runAsync(`
      CREATE TABLE IF NOT EXISTS app_state (
        key TEXT PRIMARY KEY,
        value TEXT NOT NULL
      )
    `);

    await runAsync(`
      CREATE TABLE IF NOT EXISTS audit_logs (
        id TEXT PRIMARY KEY,
        userId TEXT,
        userName TEXT,
        action TEXT NOT NULL,
        details TEXT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    `);

    console.log('Database tables initialized successfully.');
  } catch (error) {
    console.error('Failed to initialize database tables:', error);
  }
};

const crypto = require('crypto');

const hashPassword = (password, salt) => {
  if (!salt) salt = crypto.randomBytes(16).toString('hex');
  const hash = crypto.pbkdf2Sync(password, salt, 1000, 64, 'sha512').toString('hex');
  return { hash, salt };
};

const comparePassword = (password, hash, salt) => {
  const verifyHash = crypto.pbkdf2Sync(password, salt, 1000, 64, 'sha512').toString('hex');
  return hash === verifyHash;
};

const logAudit = async (userId, userName, action, details) => {
  try {
    const id = `AUD-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
    const detailsStr = typeof details === 'object' ? JSON.stringify(details) : String(details || '');
    await runAsync(
      `INSERT INTO audit_logs (id, userId, userName, action, details, timestamp) VALUES (?, ?, ?, ?, ?, ?)`,
      [id, userId || 'SYSTEM', userName || 'System', action, detailsStr, new Date().toISOString()]
    );
  } catch (err) {
    console.error('[Audit Logging Error]:', err.message);
  }
};

module.exports = {
  db,
  runAsync,
  allAsync,
  getAsync,
  initDatabase,
  hashPassword,
  comparePassword,
  logAudit
};

