const express = require('express');
const router = express.Router();
const { getAsync, runAsync } = require('../db/database');

// GET /api/state/:key
router.get('/:key', async (req, res) => {
  try {
    const { key } = req.params;
    const row = await getAsync('SELECT value FROM app_state WHERE key = ?', [key]);
    if (!row) {
      return res.status(404).json({ success: false, error: 'State key not found' });
    }
    let parsedValue = row.value;
    try {
      parsedValue = JSON.parse(row.value);
    } catch (e) {
      // String value
    }
    res.json({ success: true, key, data: parsedValue });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// POST /api/state/:key (Save key/value state in app_state)
router.post('/:key', async (req, res) => {
  try {
    const { key } = req.params;
    const { value } = req.body;
    if (value === undefined) {
      return res.status(400).json({ success: false, error: 'Value is required' });
    }

    const valueStr = typeof value === 'object' ? JSON.stringify(value) : String(value);

    const existing = await getAsync('SELECT key FROM app_state WHERE key = ?', [key]);
    if (existing) {
      await runAsync('UPDATE app_state SET value = ? WHERE key = ?', [valueStr, key]);
    } else {
      await runAsync('INSERT INTO app_state (key, value) VALUES (?, ?)', [key, valueStr]);
    }

    res.json({ success: true, key, value });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
