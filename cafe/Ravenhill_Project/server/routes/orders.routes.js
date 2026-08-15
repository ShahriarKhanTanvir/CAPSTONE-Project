const express = require('express');
const router = express.Router();
const { allAsync, getAsync, runAsync } = require('../db/database');

// GET /api/orders
router.get('/', async (req, res) => {
  try {
    const { status } = req.query;
    let sql = 'SELECT * FROM orders ORDER BY createdAt DESC';
    let params = [];

    if (status) {
      sql = 'SELECT * FROM orders WHERE status = ? ORDER BY createdAt DESC';
      params.push(status);
    }

    const orders = await allAsync(sql, params);
    const parsedOrders = orders.map(o => ({
      ...o,
      createdAt: o.createdAt || new Date().toISOString(),
      items: JSON.parse(o.itemsJson || '[]')
    }));

    res.json({ success: true, data: parsedOrders });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// POST /api/orders (Create order from POS)
router.post('/', async (req, res) => {
  try {
    const { id, orderId: bodyOrderId, type, tableId, customerName, items, subtotal, tax, discount, total, paymentMethod, createdAt: bodyCreatedAt } = req.body;
    
    const io = req.app.get('io');
    
    let orderId = id || bodyOrderId;
    let orderNum = '';

    if (orderId) {
      orderNum = orderId.replace('#ORD-', '').replace('ORD-', '').replace('#', '');
      if (!orderId.startsWith('#')) {
        orderId = `#${orderId}`;
      }
    } else {
      let row = await getAsync("SELECT value FROM app_state WHERE key = 'next_order_num'");
      if (!row) {
        const maxOrder = await getAsync("SELECT MAX(CAST(orderNum AS INTEGER)) as maxNum FROM orders");
        const nextNum = (maxOrder && maxOrder.maxNum ? maxOrder.maxNum : 9042) + 1;
        await runAsync("INSERT INTO app_state (key, value) VALUES ('next_order_num', ?)", [String(nextNum)]);
        row = { value: String(nextNum) };
      }
      orderNum = row.value;
      orderId = `#ORD-${orderNum}`;
      await runAsync("UPDATE app_state SET value = ? WHERE key = 'next_order_num'", [String(parseInt(orderNum) + 1)]);
    }

    const itemsJson = JSON.stringify(items || []);
    const createdAt = bodyCreatedAt || new Date().toISOString();

    await runAsync(
      `INSERT INTO orders (id, orderNum, type, tableId, customerName, itemsJson, subtotal, tax, discount, total, paymentMethod, status, createdAt)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [orderId, orderNum, type || 'dine_in', tableId || null, customerName || 'Walk-in Guest', itemsJson, subtotal, tax, discount || 0, total, paymentMethod || 'card', 'pending', createdAt]
    );

    // If assigned to a table, update table status
    if (tableId) {
      await runAsync(
        `UPDATE tables SET status = 'occupied', orderId = ?, timeOccupied = '01m' WHERE id = ?`,
        [orderId, tableId]
      );
    }

    const createdOrder = {
      id: orderId,
      orderNum,
      type: type || 'dine_in',
      tableId,
      customerName: customerName || 'Walk-in Guest',
      items: items || [],
      subtotal,
      tax,
      discount: discount || 0,
      total,
      paymentMethod: paymentMethod || 'card',
      status: 'pending',
      createdAt: new Date().toISOString()
    };

    // Emit socket event to active KDS screens
    if (io) {
      io.emit('order:new', createdOrder);
    }

    res.status(201).json({ success: true, data: createdOrder });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// PATCH /api/orders/:id/status (KDS order status transition)
router.patch('/:id/status', async (req, res) => {
  try {
    const { id } = req.params;
    const { status } = req.body; // 'pending', 'preparing', 'ready', 'completed', 'cancelled'
    const { logAudit } = require('../db/database');
    const io = req.app.get('io');

    const order = await getAsync('SELECT * FROM orders WHERE id = ?', [id]);
    if (!order) {
      return res.status(404).json({ success: false, error: 'Order not found' });
    }

    const previousStatus = order.status;
    await runAsync('UPDATE orders SET status = ? WHERE id = ?', [status, id]);

    // Audit Log for Order Modification (NFR35)
    await logAudit('STAFF', 'POS/KDS User', 'ORDER_STATUS_CHANGE', {
      orderId: id,
      fromStatus: previousStatus,
      toStatus: status
    });

    // If order is completed or cancelled, release associated table
    if ((status === 'completed' || status === 'cancelled') && order.tableId) {
      await runAsync('UPDATE tables SET status = "available", orderId = NULL, timeOccupied = NULL WHERE id = ?', [order.tableId]);
    }

    // FR51: Automatic Stock Deduction & FR42: Loyalty Point Allocation when order completes
    if (status === 'completed' && previousStatus !== 'completed') {
      const items = JSON.parse(order.itemsJson || '[]');

      // 1. Deduct Inventory Stock based on Recipes (FR51)
      for (const item of items) {
        const menuItem = await getAsync('SELECT recipe FROM menu_items WHERE id = ?', [item.id]);
        if (menuItem && menuItem.recipe) {
          try {
            const recipeList = typeof menuItem.recipe === 'string' ? JSON.parse(menuItem.recipe) : menuItem.recipe;
            if (Array.isArray(recipeList)) {
              for (const ing of recipeList) {
                const ingId = ing.id || ing.ingredientId;
                const ingQty = parseFloat(ing.qty || ing.amount || 0) * (item.qty || item.quantity || 1);
                if (ingId && ingQty > 0) {
                  await runAsync(
                    'UPDATE inventory SET stockQty = MAX(0, stockQty - ?) WHERE id = ?',
                    [ingQty, ingId]
                  );
                }
              }
            }
          } catch (e) {
            console.warn(`[Recipe Deduction Warning] Item ${item.id}:`, e.message);
          }
        }
      }

      // FR52: Check for out-of-stock items and flag menu items
      const outOfStockItems = await allAsync('SELECT id FROM inventory WHERE stockQty <= 0');
      if (outOfStockItems && outOfStockItems.length) {
        const outIds = outOfStockItems.map(i => i.id);
        const allMenuItems = await allAsync('SELECT id, recipe FROM menu_items');
        for (const mi of allMenuItems) {
          if (mi.recipe) {
            try {
              const recipeList = typeof mi.recipe === 'string' ? JSON.parse(mi.recipe) : mi.recipe;
              const isDepleted = recipeList.some(ing => outIds.includes(ing.id || ing.ingredientId));
              if (isDepleted) {
                await runAsync('UPDATE menu_items SET badge = "SOLD OUT" WHERE id = ?', [mi.id]);
              }
            } catch (e) {}
          }
        }
      }

      // FR42: Loyalty Point Allocation (10 points per $1 spent)
      if (order.customerName && order.customerName !== 'Walk-in Guest') {
        const pointsEarned = Math.floor((order.total || 0) * 10);
        await runAsync(
          'UPDATE customers SET points = points + ? WHERE name = ? OR id = ?',
          [pointsEarned, order.customerName, order.customerName]
        );
      }

      // Notify clients via Sockets of updated inventory & order state
      if (io) {
        io.emit('inventory:updated', { orderId: id });
      }
    }

    const updatedOrder = {
      ...order,
      status,
      items: JSON.parse(order.itemsJson || '[]')
    };

    if (io) {
      io.emit('order:status_update', { orderId: id, status, order: updatedOrder });
    }

    res.json({ success: true, data: updatedOrder });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;

