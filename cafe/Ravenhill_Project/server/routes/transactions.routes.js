const express = require('express');
const router = express.Router();
const { allAsync, runAsync, getAsync } = require('../db/database');

// GET /api/transactions
router.get('/', async (req, res) => {
  try {
    const transactions = await allAsync('SELECT * FROM transactions ORDER BY createdAt DESC');
    res.json({ success: true, data: transactions });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// POST /api/transactions (Record a completed sale)
router.post('/', async (req, res) => {
  try {
    const { orderId, total, paymentMethod, itemsCount, cashier, timestamp } = req.body;
    const id = `TXN-${Date.now()}`;

    await runAsync(
      `INSERT INTO transactions (id, orderId, total, paymentMethod, itemsCount, cashier, timestamp)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [id, orderId, total, paymentMethod || 'card', itemsCount || 0, cashier || 'Staff', timestamp || new Date().toLocaleTimeString()]
    );

    res.status(201).json({ success: true, data: { id, orderId, total, paymentMethod, itemsCount, cashier, timestamp } });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// GET /api/transactions/next-order-num (Get and increment the next order number)
router.get('/next-order-num', async (req, res) => {
  try {
    let row = await getAsync("SELECT value FROM app_state WHERE key = 'next_order_num'");
    if (!row) {
      // Initialize from highest existing order
      const maxOrder = await getAsync("SELECT MAX(CAST(orderNum AS INTEGER)) as maxNum FROM orders");
      const nextNum = (maxOrder && maxOrder.maxNum ? maxOrder.maxNum : 9042) + 1;
      await runAsync("INSERT INTO app_state (key, value) VALUES ('next_order_num', ?)", [String(nextNum)]);
      row = { value: String(nextNum) };
    }

    const currentNum = parseInt(row.value);
    // Increment for next call
    await runAsync("UPDATE app_state SET value = ? WHERE key = 'next_order_num'", [String(currentNum + 1)]);

    res.json({ success: true, data: { orderNum: currentNum } });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
