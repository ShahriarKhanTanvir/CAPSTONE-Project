// Ravenhill Coffee POS - Frontend Logic

document.addEventListener('DOMContentLoaded', () => {
    // --- Global State ---
    let currentTab = 'dashboard';
    let currentRole = 'Admin';
    let cart = [];
    let currentCategory = 1;
    let selectedProductForCustomization = null;

    // --- DOM Elements ---
    const navItems = document.querySelectorAll('.nav-item');
    const tabPanels = document.querySelectorAll('.tab-panel');
    const roleSelect = document.getElementById('role-select');
    const pageTitle = document.getElementById('page-title');
    const userNameEl = document.querySelector('.user-name');
    const userRoleEl = document.querySelector('.user-role');
    const avatarEl = document.querySelector('.avatar');

    // POS Elements
    const categoryContainer = document.getElementById('pos-categories');
    const productGrid = document.getElementById('product-grid');
    const cartItemsContainer = document.getElementById('cart-items');
    const cartSubtotalEl = document.getElementById('cart-subtotal');
    const cartTaxEl = document.getElementById('cart-tax');
    const cartTotalEl = document.getElementById('cart-total');

    // --- Initialization ---
    init();

    function init() {
        setupNavigation();
        setupRoleSwitcher();
        renderDashboardStats();
        renderCategories();
        renderProducts();
        renderKDS();
        renderTables();
        
        // Initial user setup
        updateUserInfo();
    }

    // --- Navigation ---
    function setupNavigation() {
        navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                const targetTab = e.currentTarget.getAttribute('data-tab');
                if (targetTab) {
                    switchTab(targetTab);
                }
            });
        });
    }

    function switchTab(tabId) {
        // Update nav UI
        navItems.forEach(item => item.classList.remove('active'));
        const activeNav = document.querySelector(`.nav-item[data-tab="${tabId}"]`);
        if (activeNav) activeNav.classList.add('active');

        // Update panels
        tabPanels.forEach(panel => panel.classList.remove('active'));
        document.getElementById(`tab-${tabId}`).classList.add('active');

        // Update Title
        if (activeNav) {
            pageTitle.textContent = activeNav.querySelector('span').textContent;
        }

        currentTab = tabId;
    }

    // --- Role Management (Demo) ---
    function setupRoleSwitcher() {
        APP_DATA.roles.forEach(role => {
            const option = document.createElement('option');
            option.value = role.name;
            option.textContent = role.name;
            if (role.name === currentRole) option.selected = true;
            roleSelect.appendChild(option);
        });

        roleSelect.addEventListener('change', (e) => {
            currentRole = e.target.value;
            updateUserInfo();
            // In a real app, this would filter visible tabs based on permissions
        });
    }

    function updateUserInfo() {
        APP_DATA.currentUser.role = currentRole;
        userRoleEl.textContent = currentRole;
    }

    // --- Dashboard ---
    function renderDashboardStats() {
        const stats = APP_DATA.dashboardStats;
        document.getElementById('stat-sales').textContent = stats.dailySales;
        document.getElementById('stat-sales-trend').textContent = stats.salesTrend;
        document.getElementById('stat-orders').textContent = stats.ordersToday;
        document.getElementById('stat-orders-trend').textContent = stats.ordersTrend;
        document.getElementById('stat-wait').textContent = stats.avgWaitTime;
        document.getElementById('stat-wait-trend').textContent = stats.waitTrend;
        document.getElementById('stat-top').textContent = stats.topSelling;
    }

    // --- POS Module ---
    function renderCategories() {
        if (!categoryContainer) return;
        categoryContainer.innerHTML = '';
        
        // "All" category
        const allChip = document.createElement('div');
        allChip.className = `category-chip ${currentCategory === null ? 'active' : ''}`;
        allChip.textContent = 'All Items';
        allChip.addEventListener('click', () => {
            currentCategory = null;
            updateCategoryUI();
            renderProducts();
        });
        categoryContainer.appendChild(allChip);

        APP_DATA.categories.forEach(cat => {
            const chip = document.createElement('div');
            chip.className = `category-chip ${currentCategory === cat.id ? 'active' : ''}`;
            chip.textContent = `${cat.icon} ${cat.name}`;
            chip.dataset.id = cat.id;
            chip.addEventListener('click', () => {
                currentCategory = cat.id;
                updateCategoryUI();
                renderProducts();
            });
            categoryContainer.appendChild(chip);
        });
    }

    function updateCategoryUI() {
        document.querySelectorAll('.category-chip').forEach(chip => {
            if (chip.dataset.id == currentCategory || (currentCategory === null && !chip.dataset.id)) {
                chip.classList.add('active');
            } else {
                chip.classList.remove('active');
            }
        });
    }

    function renderProducts() {
        if (!productGrid) return;
        productGrid.innerHTML = '';
        
        const filtered = currentCategory 
            ? APP_DATA.products.filter(p => p.categoryId === currentCategory)
            : APP_DATA.products;

        filtered.forEach(product => {
            const card = document.createElement('div');
            card.className = 'product-card';
            card.innerHTML = `
                <img src="${product.image}" alt="${product.name}" class="product-img">
                <div class="product-title">${product.name}</div>
                <div class="product-price">$${product.price.toFixed(2)}</div>
            `;
            card.addEventListener('click', () => openCustomizationModal(product));
            productGrid.appendChild(card);
        });
    }

    // --- Cart & Customization ---
    const customModal = document.getElementById('customization-modal');
    const customCloseBtn = document.getElementById('close-customization');
    const addToCartBtn = document.getElementById('btn-add-to-cart');
    
    // State for current customization
    let currentSelections = {};

    function openCustomizationModal(product) {
        selectedProductForCustomization = product;
        currentSelections = { milk: 'regular', size: 'regular', extras: [] }; // Defaults
        
        document.getElementById('custom-product-name').textContent = `Customize: ${product.name}`;
        
        const body = document.getElementById('customization-body');
        body.innerHTML = '';

        APP_DATA.customizations.forEach(group => {
            const section = document.createElement('div');
            section.className = 'customization-section';
            section.innerHTML = `<div class="customization-title">${group.name}</div>`;
            
            const optionsContainer = document.createElement('div');
            optionsContainer.className = 'customization-options';

            group.options.forEach(opt => {
                const isSelected = (group.id === 'extras' && currentSelections.extras.includes(opt.id)) || currentSelections[group.id] === opt.id;
                const optionEl = document.createElement('div');
                optionEl.className = `custom-option ${isSelected ? 'selected' : ''}`;
                optionEl.innerHTML = `
                    <span>${opt.name}</span>
                    <span>${opt.price > 0 ? '+$' + opt.price.toFixed(2) : ''}</span>
                `;
                
                optionEl.addEventListener('click', () => {
                    if (group.id === 'extras') {
                        const idx = currentSelections.extras.indexOf(opt.id);
                        if (idx > -1) currentSelections.extras.splice(idx, 1);
                        else currentSelections.extras.push(opt.id);
                    } else {
                        currentSelections[group.id] = opt.id;
                    }
                    
                    // Update UI internally to avoid full re-render
                    Array.from(optionsContainer.children).forEach(c => c.classList.remove('selected'));
                    if (group.id !== 'extras') {
                        optionEl.classList.add('selected');
                    } else {
                        // For multiselect (extras), re-evaluate all
                        Array.from(optionsContainer.children).forEach((c, index) => {
                            const o = group.options[index];
                            if (currentSelections.extras.includes(o.id)) c.classList.add('selected');
                        });
                    }
                });
                
                optionsContainer.appendChild(optionEl);
            });
            
            section.appendChild(optionsContainer);
            body.appendChild(section);
        });

        customModal.classList.add('show');
    }

    if (customCloseBtn) {
        customCloseBtn.addEventListener('click', () => customModal.classList.remove('show'));
    }
    
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', () => {
            addToCart(selectedProductForCustomization, currentSelections);
            customModal.classList.remove('show');
        });
    }

    function addToCart(product, selections) {
        // Calculate extra cost
        let extraCost = 0;
        let mods = [];
        
        APP_DATA.customizations.forEach(group => {
            if (group.id === 'extras') {
                selections.extras.forEach(extId => {
                    const opt = group.options.find(o => o.id === extId);
                    if (opt) {
                        extraCost += opt.price;
                        mods.push(opt.name);
                    }
                });
            } else {
                const opt = group.options.find(o => o.id === selections[group.id]);
                if (opt && opt.id !== 'regular') {
                    extraCost += opt.price;
                    mods.push(opt.name);
                }
            }
        });

        // Check if identical item exists
        const existingIdx = cart.findIndex(item => 
            item.product.id === product.id && 
            JSON.stringify(item.mods) === JSON.stringify(mods)
        );

        if (existingIdx > -1) {
            cart[existingIdx].qty += 1;
        } else {
            cart.push({
                id: Date.now(),
                product,
                qty: 1,
                basePrice: product.price,
                extraCost: extraCost,
                mods: mods
            });
        }
        
        updateCartUI();
    }

    function updateCartUI() {
        if (!cartItemsContainer) return;
        cartItemsContainer.innerHTML = '';
        
        let subtotal = 0;

        cart.forEach((item, index) => {
            const itemTotal = (item.basePrice + item.extraCost) * item.qty;
            subtotal += itemTotal;

            const el = document.createElement('div');
            el.className = 'cart-item';
            el.innerHTML = `
                <div class="cart-item-info">
                    <div class="cart-item-title">${item.product.name}</div>
                    <div class="cart-item-customizations">${item.mods.join(', ')}</div>
                    <div class="cart-item-actions">
                        <button class="qty-btn" onclick="updateQty(${index}, -1)">-</button>
                        <span style="font-size: 14px; font-weight: 600;">${item.qty}</span>
                        <button class="qty-btn" onclick="updateQty(${index}, 1)">+</button>
                    </div>
                </div>
                <div class="cart-item-price">$${itemTotal.toFixed(2)}</div>
            `;
            cartItemsContainer.appendChild(el);
        });

        const tax = subtotal * 0.10; // 10% tax example
        const total = subtotal + tax;

        if (cartSubtotalEl) cartSubtotalEl.textContent = `$${subtotal.toFixed(2)}`;
        if (cartTaxEl) cartTaxEl.textContent = `$${tax.toFixed(2)}`;
        if (cartTotalEl) cartTotalEl.textContent = `$${total.toFixed(2)}`;
    }

    // Expose for inline onclick
    window.updateQty = function(index, change) {
        if (cart[index]) {
            cart[index].qty += change;
            if (cart[index].qty <= 0) {
                cart.splice(index, 1);
            }
            updateCartUI();
        }
    };
    
    const checkoutBtn = document.getElementById('btn-checkout');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => {
            if (cart.length === 0) return alert('Cart is empty!');
            alert('Checkout process initiated!');
            // Here you would open payment modal, generate receipt, etc.
            cart = [];
            updateCartUI();
        });
    }

    // --- KDS (Kitchen Display System) ---
    function renderKDS() {
        const pendingContainer = document.getElementById('kds-pending');
        const brewingContainer = document.getElementById('kds-brewing');
        const readyContainer = document.getElementById('kds-ready');
        
        if (!pendingContainer) return;
        
        pendingContainer.innerHTML = '';
        brewingContainer.innerHTML = '';
        readyContainer.innerHTML = '';
        
        APP_DATA.activeOrders.forEach(order => {
            const ticket = document.createElement('div');
            ticket.className = 'kds-ticket';
            
            let itemsHtml = order.items.map(i => `
                <div class="ticket-item">
                    ${i.qty}x ${i.name}
                    ${i.mods.length ? `<div class="ticket-mods">${i.mods.join(', ')}</div>` : ''}
                </div>
            `).join('');
            
            ticket.innerHTML = `
                <div class="ticket-header">
                    <span>#${order.id}</span>
                    <span>${order.time}</span>
                </div>
                <div style="font-size: 12px; margin-bottom: 8px; color: var(--text-muted);">${order.type}</div>
                <div class="ticket-items">
                    ${itemsHtml}
                </div>
                <button class="btn btn-primary" style="width: 100%; padding: 4px;">
                    ${order.status === 'pending' ? 'Start Brewing' : order.status === 'brewing' ? 'Mark Ready' : 'Serve'}
                </button>
            `;
            
            if (order.status === 'pending') pendingContainer.appendChild(ticket);
            if (order.status === 'brewing') brewingContainer.appendChild(ticket);
            if (order.status === 'ready') readyContainer.appendChild(ticket);
        });
    }

    // --- Tables / Floor Plan ---
    function renderTables() {
        const fp = document.getElementById('floor-plan-container');
        if (!fp) return;
        
        fp.innerHTML = '';
        
        APP_DATA.tables.forEach(table => {
            const t = document.createElement('div');
            t.className = `dining-table ${table.status}`;
            t.style.left = `${table.x}px`;
            t.style.top = `${table.y}px`;
            t.style.width = `${table.w}px`;
            t.style.height = `${table.h}px`;
            
            t.innerHTML = `
                <div style="font-weight: 700;">T${table.number}</div>
                <div style="font-size: 10px; color: var(--text-muted);">${table.capacity} pax</div>
            `;
            
            // Add chairs (simplified visualization)
            if (table.status !== 'available') {
                const chair = document.createElement('div');
                chair.className = 'chair';
                chair.style.top = '-6px';
                chair.style.left = '50%';
                chair.style.transform = 'translateX(-50%)';
                if (table.status === 'occupied') chair.style.backgroundColor = 'var(--danger)';
                if (table.status === 'reserved') chair.style.backgroundColor = 'var(--warning)';
                t.appendChild(chair);
            }
            
            t.addEventListener('click', () => {
                alert(`Table ${table.number} selected. Status: ${table.status}`);
                // In real app, open modal to assign table, take order for table, or view reservation
            });
            
            fp.appendChild(t);
        });
    }

});
