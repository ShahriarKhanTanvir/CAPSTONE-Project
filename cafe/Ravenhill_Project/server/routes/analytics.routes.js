const express = require('express');
const router = express.Router();
const { allAsync, getAsync } = require('../db/database');

// GET /api/analytics/summary
router.get('/summary', async (req, res) => {
  try {
    const orders = await allAsync('SELECT * FROM orders');
    const inventory = await allAsync('SELECT * FROM inventory');
    const tables = await allAsync('SELECT * FROM tables');

    const totalOrders = orders.length;
    const totalRevenue = orders.reduce((sum, o) => sum + (o.total || 0), 0);
    const lowStockCount = inventory.filter(i => i.status === 'low').length;
    const occupiedTables = tables.filter(t => t.status === 'occupied').length;

    res.json({
      success: true,
      data: {
        totalRevenue: parseFloat(totalRevenue.toFixed(2)),
        totalOrders,
        avgOrderValue: totalOrders > 0 ? parseFloat((totalRevenue / totalOrders).toFixed(2)) : 0,
        lowStockCount,
        occupiedTables,
        totalTables: tables.length
      }
    });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
