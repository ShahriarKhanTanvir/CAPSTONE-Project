import re

def patch_app_js():
    with open('app.js', 'r') as f:
        content = f.read()
        
    # 1. Add fetchCustomisations to API
    api_addition = """
  async fetchCustomisations(productId = null, categoryId = null) {
    try {
      let url = `${API_BASE}/customisations/customisations.php`;
      let params = [];
      if (productId) params.push(`product_id=${productId}`);
      if (categoryId) params.push(`category_id=${categoryId}`);
      if (params.length) url += '?' + params.join('&');
      const res = await fetch(url);
      const data = await res.json();
      return data.success ? data.data : null;
    } catch { return null; }
  },"""
    if 'fetchCustomisations' not in content:
        content = content.replace("async fetchMenuItems() {", api_addition + "\n  async fetchMenuItems() {")

    # 2. Replace addItemToCart
    old_add_item = """function addItemToCart(item, size, milk, roast, extras, notes, qty) {
  let extraPrice = 0;
  if (size.includes('Medium')) extraPrice += 0.70;
  if (size.includes('Large')) extraPrice += 1.20;
  if (milk.includes('Oat') || milk.includes('Almond')) extraPrice += 0.80;
  if (milk.includes('Soy')) extraPrice += 0.60;
  if (roast.includes('Single Origin')) extraPrice += 1.00;
  if (roast.includes('Decaf')) extraPrice += 0.50;

  extras.forEach(ex => {
    if (ex.includes('Extra Shot')) extraPrice += 1.00;
    if (ex.includes('Syrup')) extraPrice += 0.70;
  });

  const unitPrice = item.price + extraPrice;

  AppState.cart.items.push({
    cartItemId: 'ci-' + Date.now() + Math.random().toString(36).substr(2, 4),
    item: item,
    size: size,
    milk: milk,
    roast: roast,
    extras: extras,
    notes: notes,
    qty: qty,
    unitPrice: unitPrice,
    totalPrice: unitPrice * qty
  });

  renderCartUI();
}"""
    new_add_item = """function addItemToCart(item, customisations, notes, qty) {
  let extraPrice = 0;
  customisations.forEach(c => {
    extraPrice += parseFloat(c.extra_price || 0);
  });

  const unitPrice = parseFloat(item.price) + extraPrice;

  AppState.cart.items.push({
    cartItemId: 'ci-' + Date.now() + Math.random().toString(36).substr(2, 4),
    item: item,
    customisations: customisations,
    notes: notes,
    qty: qty,
    unitPrice: unitPrice,
    totalPrice: unitPrice * qty
  });

  renderCartUI();
}"""
    content = content.replace(old_add_item, new_add_item)

    # 3. Replace openCustomiserModal and related methods
    pattern = re.compile(r"document\.getElementById\('qty-minus'\)\.addEventListener\('click', \(\) => \{.*?const calcEl = document\.getElementById\('customiser-calculated-price'\);\n  if \(calcEl\) calcEl\.textContent = `\$\{\\?total\.toFixed\(2\)\}`;?\n\}", re.DOTALL)
    
    new_customiser_block = """document.getElementById('qty-minus').addEventListener('click', () => {
    let q = parseInt(document.getElementById('customiser-qty').textContent || '1');
    if (q > 1) {
      document.getElementById('customiser-qty').textContent = q - 1;
      recalculateCustomiserPrice();
    }
  });

  document.getElementById('qty-plus').addEventListener('click', () => {
    let q = parseInt(document.getElementById('customiser-qty').textContent || '1');
    document.getElementById('customiser-qty').textContent = q + 1;
    recalculateCustomiserPrice();
  });

  // Confirm Add to Cart
  const addBtn = document.getElementById('add-to-cart-confirm-btn');
  // clone node to remove old listeners
  const newAddBtn = addBtn.cloneNode(true);
  addBtn.parentNode.replaceChild(newAddBtn, addBtn);
  
  newAddBtn.addEventListener('click', () => {
    const customisations = [];
    
    // Gather selections
    document.querySelectorAll('#dynamic-customiser-sections input:checked').forEach(input => {
      customisations.push({
        customisation_id: input.getAttribute('data-id'),
        group_name: input.getAttribute('data-group'),
        option_name: input.getAttribute('data-name'),
        extra_price: parseFloat(input.getAttribute('data-extra'))
      });
    });

    const notes = document.getElementById('customiser-item-notes').value.trim();
    const qty = parseInt(document.getElementById('customiser-qty').textContent || '1');

    addItemToCart(AppState.modalItem, customisations, notes, qty);
    document.getElementById('customiser-modal').classList.add('hidden');
  });
}

async function openCustomiserModal(item) {
  AppState.modalItem = item;
  document.getElementById('customiser-item-name').textContent = item.name || item.product_name;
  document.getElementById('customiser-item-desc').textContent = item.desc || '';
  document.getElementById('customiser-qty').textContent = 1;
  document.getElementById('customiser-item-notes').value = '';

  const imgEl = document.getElementById('customiser-item-img');
  if (imgEl) imgEl.src = getItemImage(item);

  const container = document.getElementById('dynamic-customiser-sections');
  container.innerHTML = '<div style="padding:20px;text-align:center;">Loading options...</div>';
  document.getElementById('customiser-modal').classList.remove('hidden');

  // Fetch customisations
  const cData = await API.fetchCustomisations(item.product_id || item.id, item.category_id);
  container.innerHTML = '';

  if (cData && cData.groups) {
    for (const [group, options] of Object.entries(cData.groups)) {
      // Determine if single choice (radio) or multi (checkbox)
      const isSingle = ['Size', 'Milk', 'Coffee Modifiers'].includes(group);
      const type = isSingle ? 'radio' : 'checkbox';
      const replaceRegex = new RegExp('\\\\s+', 'g');
      const groupNameClean = group.replace(replaceRegex, '');
      
      let html = `<div class="customiser-section">
        <label class="section-label">${group}</label>
        <div class="checkbox-options-grid">`;
        
      options.forEach(opt => {
        const extraText = opt.extra_price > 0 ? ` (+ $${opt.extra_price.toFixed(2)})` : '';
        html += `
          <label class="checkbox-card">
            <input type="${type}" name="group_${groupNameClean}" 
                   value="${opt.customisation_id}" 
                   data-id="${opt.customisation_id}"
                   data-group="${group}"
                   data-name="${opt.option_name}"
                   data-extra="${opt.extra_price}"
                   ${opt.is_default ? 'checked' : ''}
                   onchange="recalculateCustomiserPrice()">
            <span>${opt.option_name}${extraText}</span>
          </label>
        `;
      });
      html += `</div></div>`;
      container.innerHTML += html;
    }
  }

  recalculateCustomiserPrice();
}

function recalculateCustomiserPrice() {
  if (!AppState.modalItem) return;
  let base = parseFloat(AppState.modalItem.price);

  document.querySelectorAll('#dynamic-customiser-sections input:checked').forEach(input => {
    base += parseFloat(input.getAttribute('data-extra') || 0);
  });

  const qty = parseInt(document.getElementById('customiser-qty').textContent || '1');
  const total = base * qty;
  
  const calcEl = document.getElementById('customiser-calculated-price');
  if (calcEl) calcEl.textContent = `$${total.toFixed(2)}`;
}"""
    
    match = pattern.search(content)
    if match:
        content = content[:match.start()] + new_customiser_block + content[match.end():]
    else:
        print("Regex for customiser block did not match. Please verify the file contents.")

    # 4. Modify renderCartUI mapping
    old_cart_render = """      const modPills = [];
      if (ci.size && ci.item.hasModifiers) modPills.push(ci.size);
      if (ci.milk && ci.milk !== 'Full Cream Milk' && ci.item.hasModifiers) modPills.push(ci.milk);
      if (ci.roast && !ci.roast.includes('House Blend') && ci.item.hasModifiers) modPills.push(ci.roast.split(' ')[0]);
      ci.extras.forEach(e => modPills.push(e));"""
      
    new_cart_render = """      const modPills = ci.customisations ? ci.customisations.map(c => c.option_name) : [];"""
    
    content = content.replace(old_cart_render, new_cart_render)
    
    # 5. Modify API.createOrder payload formatting
    old_create_order = """    items: AppState.cart.items.map(i => ({
      name: i.item.name,
      qty: i.qty,
      mods: [i.size, i.milk, i.roast, ...i.extras].filter(Boolean)
    }))"""
    
    new_create_order = """    items: AppState.cart.items.map(i => ({
      product_id: i.item.product_id || i.item.id,
      name: i.item.name || i.item.product_name,
      quantity: i.qty,
      customisations: i.customisations,
      notes: i.notes
    }))"""
    
    content = content.replace(old_create_order, new_create_order)
    
    # Write back
    with open('app.js', 'w') as f:
        f.write(content)
        
if __name__ == "__main__":
    patch_app_js()
