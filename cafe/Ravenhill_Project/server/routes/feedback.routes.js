const express = require('express');
const router = express.Router();
const { allAsync, runAsync } = require('../db/database');

// Create table if not existing dynamically
runAsync(`
  CREATE TABLE IF NOT EXISTS feedback (
    id TEXT PRIMARY KEY,
    customer TEXT NOT NULL,
    rating INTEGER NOT NULL,
    comment TEXT NOT NULL,
    date TEXT NOT NULL
  )
`).catch(err => console.error('Feedback table init err:', err));

// GET /api/feedback
router.get('/', async (req, res) => {
  try {
    const feedback = await allAsync('SELECT * FROM feedback ORDER BY id DESC');
    res.json({ success: true, data: feedback });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// POST /api/feedback (Add review)
router.post('/', async (req, res) => {
  try {
    const { customer, rating, comment, date } = req.body;
    const id = `FB-${Date.now().toString().slice(-3)}`;
    const dateStr = date || 'Today';

    await runAsync(
      `INSERT INTO feedback (id, customer, rating, comment, date)
       VALUES (?, ?, ?, ?, ?)`,
      [id, customer || 'Walk-in Guest', rating || 5, comment || '', dateStr]
    );

    const newFB = { id, customer: customer || 'Walk-in Guest', rating: rating || 5, comment: comment || '', date: dateStr };
    res.status(201).json({ success: true, data: newFB });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// DELETE /api/feedback/:id
router.delete('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    await runAsync('DELETE FROM feedback WHERE id = ?', [id]);
    res.json({ success: true, message: `Feedback ${id} deleted` });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
