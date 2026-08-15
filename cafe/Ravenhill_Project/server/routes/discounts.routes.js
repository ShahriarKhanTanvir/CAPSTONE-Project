const express = require('express');
const router = express.Router();
const { allAsync, runAsync } = require('../db/database');

// GET /api/discounts
router.get('/', async (req, res) => {
  try {
    const discounts = await allAsync('SELECT * FROM discounts ORDER BY code ASC');
    res.json({ success: true, data: discounts });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// POST /api/discounts (Create discount code)
router.post('/', async (req, res) => {
  try {
    const { code, description, type, val, minSpend } = req.body;
    if (!code) {
      return res.status(400).json({ success: false, error: 'Discount code is required' });
    }

    const cleanCode = code.toUpperCase().trim();
    await runAsync(
      `INSERT INTO discounts (code, description, type, val, minSpend)
       VALUES (?, ?, ?, ?, ?)`,
      [cleanCode, description || 'Promotional Discount', type || 'percent', val || 10, minSpend || 0]
    );

    const newDiscount = { code: cleanCode, description: description || 'Promotional Discount', type: type || 'percent', val: val || 10, minSpend: minSpend || 0 };
    res.status(201).json({ success: true, data: newDiscount });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// DELETE /api/discounts/:code
router.delete('/:code', async (req, res) => {
  try {
    const { code } = req.params;
    await runAsync('DELETE FROM discounts WHERE code = ?', [code.toUpperCase()]);
    res.json({ success: true, message: `Discount code ${code} deleted` });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
