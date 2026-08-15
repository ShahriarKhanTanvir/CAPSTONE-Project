const express = require('express');
const router = express.Router();
const { allAsync, getAsync, runAsync } = require('../db/database');

// GET /api/staff
router.get('/', async (req, res) => {
  try {
    const staffMembers = await allAsync('SELECT id, name, role, pin, status, shiftStart, salesCount FROM staff');
    res.json({ success: true, data: staffMembers });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// POST /api/staff/verify-pin (PIN Authentication for POS / Manager Override)
router.post('/verify-pin', async (req, res) => {
  try {
    const { pin } = req.body;
    if (!pin) {
      return res.status(400).json({ success: false, error: 'PIN is required' });
    }

    const member = await getAsync('SELECT id, name, role, status FROM staff WHERE pin = ?', [pin]);
    if (!member) {
      return res.status(401).json({ success: false, error: 'Invalid PIN entered' });
    }

    res.json({ success: true, data: member });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// POST /api/staff (Add staff member)
router.post('/', async (req, res) => {
  try {
    const { name, role, pin, shiftStart } = req.body;
    const id = `EMP-${Date.now().toString().slice(-3)}`;

    await runAsync(
      `INSERT INTO staff (id, name, role, pin, status, shiftStart, salesCount)
       VALUES (?, ?, ?, ?, 'Active', ?, 0)`,
      [id, name, role || 'Barista', pin || '1234', shiftStart || '08:00 AM']
    );

    res.status(201).json({
      success: true,
      data: { id, name, role: role || 'Barista', status: 'Active', shiftStart: shiftStart || '08:00 AM', salesCount: 0 }
    });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// PATCH /api/staff/:id (Update status / shift / PIN / profile - FR4)
router.patch('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { status, shiftStart, salesCount, pin, name, role } = req.body;
    const { logAudit } = require('../db/database');

    const emp = await getAsync('SELECT * FROM staff WHERE id = ?', [id]);
    if (!emp) {
      return res.status(404).json({ success: false, error: 'Staff member not found' });
    }

    const newStatus = status !== undefined ? status : emp.status;
    const newShift = shiftStart !== undefined ? shiftStart : emp.shiftStart;
    const newSales = salesCount !== undefined ? salesCount : emp.salesCount;
    const newPin = pin !== undefined ? pin : emp.pin;
    const newName = name !== undefined ? name : emp.name;
    const newRole = role !== undefined ? role : emp.role;

    await runAsync(
      `UPDATE staff SET name = ?, role = ?, pin = ?, status = ?, shiftStart = ?, salesCount = ? WHERE id = ?`,
      [newName, newRole, newPin, newStatus, newShift, newSales, id]
    );

    if (pin !== undefined && pin !== emp.pin) {
      await logAudit('ADMIN', 'System Admin', 'STAFF_PIN_RESET', `PIN reset for staff ${emp.name} (${id})`);
    }

    res.json({ success: true, data: { id, name: newName, role: newRole, status: newStatus, shiftStart: newShift, salesCount: newSales } });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});


// DELETE /api/staff/:id
router.delete('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    await runAsync('DELETE FROM staff WHERE id = ?', [id]);
    res.json({ success: true, message: `Staff member ${id} deleted` });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
