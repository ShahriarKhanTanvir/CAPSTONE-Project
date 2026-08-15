const express = require('express');
const router = express.Router();
const { allAsync, getAsync, runAsync } = require('../db/database');

// GET /api/menu/categories
router.get('/categories', async (req, res) => {
  try {
    const categories = await allAsync('SELECT * FROM categories');
    res.json({ success: true, data: categories });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// GET /api/menu/items
router.get('/items', async (req, res) => {
  try {
    const { catId } = req.query;
    let sql = 'SELECT * FROM menu_items';
    let params = [];
    if (catId) {
      sql += ' WHERE catId = ?';
      params.push(catId);
    }
    const items = await allAsync(sql, params);
    
    // Parse recipe JSON and standardise format
    const parsedItems = items.map(item => ({
      ...item,
      hasModifiers: Boolean(item.hasModifiers),
      recipe: item.recipe ? JSON.parse(item.recipe) : null
    }));

    res.json({ success: true, data: parsedItems });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// POST /api/menu/items (Add item)
router.post('/items', async (req, res) => {
  try {
    const { id, catId, name, desc, price, icon, badge, hasModifiers, recipe } = req.body;
    const itemId = id || `item-${Date.now()}`;
    const recipeStr = typeof recipe === 'object' ? JSON.stringify(recipe) : recipe;

    await runAsync(
      `INSERT INTO menu_items (id, catId, name, desc, price, icon, badge, hasModifiers, recipe)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [itemId, catId, name, desc, price, icon || 'ri-cup-line', badge || null, hasModifiers ? 1 : 0, recipeStr || null]
    );

    res.status(201).json({ success: true, data: { id: itemId, catId, name, price } });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// PUT /api/menu/items/:id (Update item)
router.put('/items/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { name, desc, price, icon, badge, hasModifiers, recipe } = req.body;
    const recipeStr = typeof recipe === 'object' ? JSON.stringify(recipe) : recipe;

    await runAsync(
      `UPDATE menu_items 
       SET name = ?, desc = ?, price = ?, icon = ?, badge = ?, hasModifiers = ?, recipe = ?
       WHERE id = ?`,
      [name, desc, price, icon, badge, hasModifiers ? 1 : 0, recipeStr, id]
    );

    res.json({ success: true, message: 'Item updated successfully' });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// DELETE /api/menu/items/:id
router.delete('/items/:id', async (req, res) => {
  try {
    const { id } = req.params;
    await runAsync('DELETE FROM menu_items WHERE id = ?', [id]);
    res.json({ success: true, message: 'Item deleted' });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
