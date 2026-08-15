const express = require('express');
const router = express.Router();
const { allAsync, runAsync } = require('../db/database');

// GET /api/reservations
router.get('/', async (req, res) => {
  try {
    const reservations = await allAsync('SELECT * FROM reservations ORDER BY id DESC');
    res.json({ success: true, data: reservations });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// POST /api/reservations
router.post('/', async (req, res) => {
  try {
    const { customerName, partySize, tableId, time, contact } = req.body;
    const id = `RES-${Date.now().toString().substr(-4)}`;

    await runAsync(
      `INSERT INTO reservations (id, customerName, partySize, tableId, time, status, contact)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [id, customerName, partySize, tableId, time, 'Confirmed', contact || '']
    );

    // Link reservation to table
    await runAsync(
      `UPDATE tables SET status = 'reserved', reservedFor = ? WHERE id = ?`,
      [`${customerName} @ ${time}`, tableId]
    );

    const newRes = { id, customerName, partySize, tableId, time, status: 'Confirmed', contact };
    res.status(201).json({ success: true, data: newRes });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// DELETE /api/reservations/:id
router.delete('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    await runAsync('DELETE FROM reservations WHERE id = ?', [id]);
    res.json({ success: true, message: 'Reservation cancelled' });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
