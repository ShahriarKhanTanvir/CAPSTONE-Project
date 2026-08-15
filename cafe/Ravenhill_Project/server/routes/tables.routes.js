const express = require('express');
const router = express.Router();
const { allAsync, getAsync, runAsync } = require('../db/database');

// GET /api/tables
router.get('/', async (req, res) => {
  try {
    const tables = await allAsync('SELECT * FROM tables ORDER BY id ASC');
    res.json({ success: true, data: tables });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// PATCH /api/tables/:id (Update table status, order, reservation)
router.patch('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { status, orderId, timeOccupied, reservedFor } = req.body;

    const table = await getAsync('SELECT * FROM tables WHERE id = ?', [id]);
    if (!table) {
      return res.status(404).json({ success: false, error: 'Table not found' });
    }

    const updatedStatus = status || table.status;
    const updatedOrderId = orderId !== undefined ? orderId : table.orderId;
    const updatedTime = timeOccupied !== undefined ? timeOccupied : table.timeOccupied;
    const updatedReserved = reservedFor !== undefined ? reservedFor : table.reservedFor;

    await runAsync(
      `UPDATE tables 
       SET status = ?, orderId = ?, timeOccupied = ?, reservedFor = ?
       WHERE id = ?`,
      [updatedStatus, updatedOrderId, updatedTime, updatedReserved, id]
    );

    const io = req.app.get('io');
    if (io) {
      io.emit('table:update', { id, status: updatedStatus, orderId: updatedOrderId });
    }

    res.json({ success: true, data: { id, status: updatedStatus, orderId: updatedOrderId, timeOccupied: updatedTime, reservedFor: updatedReserved } });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// POST /api/tables (Add new table)
router.post('/', async (req, res) => {
  try {
    const { id, name, section, capacity, status } = req.body;
    const tableId = id || `T-${Date.now().toString().slice(-2)}`;
    const tableName = name || `Table ${tableId}`;
    const tableSection = section || 'Main Dining';
    const tableCapacity = capacity || 4;
    const tableStatus = status || 'available';

    await runAsync(
      `INSERT INTO tables (id, name, section, capacity, status, orderId, timeOccupied, reservedFor)
       VALUES (?, ?, ?, ?, ?, NULL, NULL, NULL)`,
      [tableId, tableName, tableSection, tableCapacity, tableStatus]
    );

    const io = req.app.get('io');
    if (io) {
      io.emit('table:create', { id: tableId, name: tableName, section: tableSection, capacity: tableCapacity, status: tableStatus });
    }

    res.status(201).json({
      success: true,
      data: { id: tableId, name: tableName, section: tableSection, capacity: tableCapacity, status: tableStatus }
    });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// PUT /api/tables/:id (Update table configuration - name, section, capacity)
router.put('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { name, section, capacity, status } = req.body;

    const table = await getAsync('SELECT * FROM tables WHERE id = ?', [id]);
    if (!table) {
      return res.status(404).json({ success: false, error: 'Table not found' });
    }

    const newName = name || table.name;
    const newSection = section || table.section;
    const newCapacity = capacity || table.capacity;
    const newStatus = status || table.status;

    await runAsync(
      `UPDATE tables SET name = ?, section = ?, capacity = ?, status = ? WHERE id = ?`,
      [newName, newSection, newCapacity, newStatus, id]
    );

    res.json({
      success: true,
      data: { id, name: newName, section: newSection, capacity: newCapacity, status: newStatus }
    });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// DELETE /api/tables/:id (Delete table)
router.delete('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    await runAsync('DELETE FROM tables WHERE id = ?', [id]);

    const io = req.app.get('io');
    if (io) {
      io.emit('table:delete', { id });
    }

    res.json({ success: true, message: `Table ${id} deleted successfully` });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
