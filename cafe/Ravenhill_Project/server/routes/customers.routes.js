const express = require('express');
const router = express.Router();
const { allAsync, runAsync } = require('../db/database');

// GET /api/customers
router.get('/', async (req, res) => {
  try {
    const customers = await allAsync('SELECT * FROM customers ORDER BY name ASC');
    res.json({ success: true, data: customers });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// POST /api/customers
router.post('/', async (req, res) => {
  try {
    const { name, mobile, email } = req.body;
    const id = `CUST-${Date.now().toString().substr(-3)}`;

    await runAsync(
      `INSERT INTO customers (id, name, mobile, email, points, tier, visits)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [id, name, mobile || '', email || '', 50, 'Bean Bronze', 1]
    );

    res.status(201).json({ success: true, data: { id, name, mobile, email, points: 50, tier: 'Bean Bronze', visits: 1 } });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// PUT /api/customers/:id (Update points, tier, visits)
router.put('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { name, mobile, email, points, tier, visits } = req.body;

    await runAsync(
      `UPDATE customers SET name = COALESCE(?, name), mobile = COALESCE(?, mobile), email = COALESCE(?, email), points = COALESCE(?, points), tier = COALESCE(?, tier), visits = COALESCE(?, visits) WHERE id = ?`,
      [name || null, mobile || null, email || null, points !== undefined ? points : null, tier || null, visits !== undefined ? visits : null, id]
    );

    res.json({ success: true, message: `Customer ${id} updated` });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// DELETE /api/customers/:id
router.delete('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    await runAsync('DELETE FROM customers WHERE id = ?', [id]);
    res.json({ success: true, message: `Customer ${id} deleted` });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
