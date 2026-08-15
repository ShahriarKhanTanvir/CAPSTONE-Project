const express = require('express');
const router = express.Router();
const { allAsync } = require('../db/database');

// GET /api/audit (Retrieve activity & audit logs)
router.get('/', async (req, res) => {
  try {
    const { action, userId, limit } = req.query;
    let sql = 'SELECT * FROM audit_logs ORDER BY timestamp DESC';
    let params = [];

    if (action) {
      sql = 'SELECT * FROM audit_logs WHERE action = ? ORDER BY timestamp DESC';
      params.push(action);
    } else if (userId) {
      sql = 'SELECT * FROM audit_logs WHERE userId = ? ORDER BY timestamp DESC';
      params.push(userId);
    }

    if (limit) {
      sql += ' LIMIT ?';
      params.push(parseInt(limit, 10));
    } else {
      sql += ' LIMIT 100';
    }

    const logs = await allAsync(sql, params);
    res.json({ success: true, data: logs });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
