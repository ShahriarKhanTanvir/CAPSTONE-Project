const express = require('express');
const router = express.Router();
const { allAsync, getAsync, runAsync } = require('../db/database');

// GET /api/inventory
router.get('/', async (req, res) => {
  try {
    const items = await allAsync('SELECT * FROM inventory ORDER BY name ASC');
    res.json({ success: true, data: items });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// PATCH /api/inventory/:id/stock (Update stock quantity)
router.patch('/:id/stock', async (req, res) => {
  try {
    const { id } = req.params;
    const { stockQty } = req.body;

    const item = await getAsync('SELECT * FROM inventory WHERE id = ?', [id]);
    if (!item) {
      return res.status(404).json({ success: false, error: 'Inventory item not found' });
    }

    const newQty = parseFloat(stockQty);
    const newStatus = newQty <= item.minThreshold ? 'low' : 'good';
    const { logAudit } = require('../db/database');

    await runAsync(
      'UPDATE inventory SET stockQty = ?, status = ? WHERE id = ?',
      [newQty, newStatus, id]
    );

    // NFR37: Inventory adjustment logging
    await logAudit('STAFF', 'Inventory Manager', 'INVENTORY_ADJUSTMENT', {
      itemId: id,
      itemName: item.name,
      oldQty: item.stockQty,
      newQty: newQty
    });

    res.json({ success: true, data: { id, stockQty: newQty, status: newStatus } });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});


// POST /api/inventory (Add new raw inventory item)
router.post('/', async (req, res) => {
  try {
    const { name, stockQty, unit, minThreshold, supplier, unitCost } = req.body;
    const id = `INV-${Date.now()}`;
    const status = stockQty <= minThreshold ? 'low' : 'good';

    await runAsync(
      `INSERT INTO inventory (id, name, stockQty, unit, minThreshold, status, supplier, unitCost)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
      [id, name, stockQty, unit, minThreshold, status, supplier || null, unitCost || 0]
    );

    res.status(201).json({ success: true, data: { id, name, stockQty, unit, minThreshold, status } });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// PUT /api/inventory/:id (Update inventory details)
router.put('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { name, stockQty, unit, minThreshold, supplier, unitCost } = req.body;

    const item = await getAsync('SELECT * FROM inventory WHERE id = ?', [id]);
    if (!item) {
      return res.status(404).json({ success: false, error: 'Inventory item not found' });
    }

    const newName = name || item.name;
    const newQty = stockQty !== undefined ? parseFloat(stockQty) : item.stockQty;
    const newUnit = unit || item.unit;
    const newThreshold = minThreshold !== undefined ? parseFloat(minThreshold) : item.minThreshold;
    const newStatus = newQty <= newThreshold ? 'low' : 'good';
    const newSupplier = supplier !== undefined ? supplier : item.supplier;
    const newCost = unitCost !== undefined ? parseFloat(unitCost) : item.unitCost;

    await runAsync(
      `UPDATE inventory SET name = ?, stockQty = ?, unit = ?, minThreshold = ?, status = ?, supplier = ?, unitCost = ? WHERE id = ?`,
      [newName, newQty, newUnit, newThreshold, newStatus, newSupplier, newCost, id]
    );

    res.json({ success: true, data: { id, name: newName, stockQty: newQty, unit: newUnit, minThreshold: newThreshold, status: newStatus, supplier: newSupplier, unitCost: newCost } });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// DELETE /api/inventory/:id
router.delete('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    await runAsync('DELETE FROM inventory WHERE id = ?', [id]);
    res.json({ success: true, message: `Inventory item ${id} deleted` });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
