<?php
/**
 * Ravenhill Coffee POS & Management System
 * Production Entry Point (PHP)
 */
require_once __DIR__ . '/api/utils/csrf.php';
$csrfToken = getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">

  <title>Ravenhill Coffee — POS & Shop Management System</title>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Remixicon Icons -->
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body class="theme-dark">
  <!-- Skip to Main Content Link for Keyboard / Screen Reader Accessibility -->
  <a href="#workspace-container" class="skip-to-content">Skip to main content</a>

  <!-- Mobile Sidebar Backdrop Overlay -->
  <div class="sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>

  <div id="app-container" class="app-layout">
    
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Main Navigation">
      <div class="sidebar-brand">
        <div class="brand-logo" style="overflow:hidden; border:2px solid var(--color-primary-light);">
          <img src="./brand_recources/ravenhill_logo.png" alt="Ravenhill Logo" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
        </div>
        <div class="brand-info">
          <span class="brand-title">RAVENHILL</span>
          <span class="brand-subtitle">Coffee Roasters • Melb CBD</span>
        </div>
        <button class="icon-btn mobile-sidebar-close" id="mobile-sidebar-close-btn" aria-label="Close navigation sidebar">
          <i class="ri-close-line"></i>
        </button>
      </div>

      <div class="sidebar-nav-wrapper">
        <div class="nav-section-title">POS & Operations</div>
        <nav class="nav-menu">
          <a href="#" class="nav-item active" data-module="pos">
            <i class="ri-shopping-bag-3-line"></i>
            <span>Point of Sale</span>
          </a>
          <a href="#" class="nav-item" data-module="kds">
            <i class="ri-fire-line"></i>
            <span>Kitchen / Barista KDS</span>
            <span class="badge badge-warning" id="kds-pending-count">0</span>
          </a>
          <a href="#" class="nav-item" data-module="waitstaff">
            <i class="ri-user-star-line"></i>
            <span>Wait Staff Monitor</span>
            <span class="badge badge-success hidden" id="waitstaff-ready-count">0</span>
          </a>
          <a href="#" class="nav-item" data-module="customer_tracker">
            <i class="ri-smartphone-line"></i>
            <span>Customer Tracker</span>
          </a>
          <a href="#" class="nav-item" data-module="tables">
            <i class="ri-layout-grid-line"></i>
            <span>Table Management</span>
          </a>
          <a href="#" class="nav-item" data-module="reservations">
            <i class="ri-calendar-check-line"></i>
            <span>Reservations</span>
          </a>
        </nav>

        <div class="nav-section-title">Shop & Inventory</div>
        <nav class="nav-menu">
          <a href="#" class="nav-item" data-module="menu">
            <i class="ri-restaurant-menu-line"></i>
            <span>Menu & Modifiers</span>
          </a>
          <a href="#" class="nav-item" data-module="inventory">
            <i class="ri-box-3-line"></i>
            <span>Inventory & Recipes</span>
            <span class="badge badge-danger hidden" id="low-stock-count">0</span>
          </a>
          <a href="#" class="nav-item" data-module="suppliers">
            <i class="ri-truck-line"></i>
            <span>Suppliers & Orders</span>
          </a>
          <a href="#" class="nav-item" data-module="discounts">
            <i class="ri-coupon-3-line"></i>
            <span>Discounts & Promos</span>
          </a>
        </nav>

        <div class="nav-section-title">People & Relations</div>
        <nav class="nav-menu">
          <a href="#" class="nav-item" data-module="customers">
            <i class="ri-user-heart-line"></i>
            <span>Customers & Loyalty</span>
          </a>
          <a href="#" class="nav-item" data-module="employees">
            <i class="ri-team-line"></i>
            <span>Staff & Attendance</span>
          </a>
          <a href="#" class="nav-item" data-module="feedback">
            <i class="ri-chat-smile-2-line"></i>
            <span>Customer Feedback</span>
          </a>
        </nav>

        <div class="nav-section-title">Administration</div>
        <nav class="nav-menu">
          <a href="#" class="nav-item" data-module="dashboard">
            <i class="ri-bar-chart-grouped-line"></i>
            <span>Dashboard & Reports</span>
          </a>
          <a href="#" class="nav-item" data-module="access">
            <i class="ri-shield-user-line"></i>
            <span>Access Control</span>
          </a>
          <a href="#" class="nav-item" data-module="audit">
            <i class="ri-file-list-3-line"></i>
            <span>Audit & Compliance Logs</span>
          </a>
        </nav>

      </div>

      <!-- Quick Session Footer -->
      <div class="sidebar-footer">
        <div class="store-status">
          <span class="status-indicator online"></span>
          <span>Register #01 — Active</span>
        </div>
        <div class="system-time" id="live-clock">10:42 AM • 06 Aug 2026</div>
      </div>
    </aside>

    <!-- Main Content Shell -->
    <main class="main-wrapper" id="main-content" role="main">
      
      <!-- Top Navigation & Role Bar -->
      <header class="topbar" role="banner">
        <div class="topbar-left">
          <button class="icon-btn sidebar-toggle" id="toggle-sidebar" title="Toggle Sidebar" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="sidebar">
            <i class="ri-menu-fold-line" id="sidebar-toggle-icon"></i>
          </button>
          <div class="page-title-group">
            <h1 id="current-module-title">Point of Sale (POS)</h1>
            <span class="page-subtitle" id="current-module-subtitle">Process orders & quick transactions for Melbourne CBD shop</span>
          </div>
        </div>

        <div class="topbar-right">
          <!-- Quick Search -->
          <div class="global-search-box">
            <i class="ri-search-line" aria-hidden="true"></i>
            <input type="text" id="global-search-input" placeholder="Search orders, customers, menu items..." aria-label="Search orders, customers, menu items">
          </div>


          <!-- Active Shift Clock-In Widget -->
          <div class="topbar-widget shift-widget" id="topbar-shift-status" onclick="openClockShiftModal()" style="cursor:pointer;" title="Click to manage staff shift attendance & clock in/out" role="button" tabindex="0" aria-label="Manage Shift Attendance">
            <i class="ri-time-line" aria-hidden="true"></i>
            <span class="shift-timer" id="shift-clock-timer">Shift: 04h 15m</span>
          </div>

          <!-- Role Selector Dropdown -->
          <div class="role-selector-container">
            <label for="user-role-select" class="role-label">Role:</label>
            <select id="user-role-select" class="role-select" aria-label="Select Active Role">
              <option value="cashier" selected>💳 Cashier (POS & Sales)</option>
              <option value="kitchen">🍳 Kitchen Staff (Food KDS)</option>
              <option value="barista">☕ Barista (Beverages KDS)</option>
              <option value="waitstaff">🤵 Wait Staff (Table Service)</option>
              <option value="customer">👤 Customer (Live Tracker)</option>
              <option value="manager">📊 Manager (Operations & Staff)</option>
              <option value="admin">⚡ Admin (Full Control)</option>
            </select>
          </div>


          <!-- User Profile -->
          <div class="user-profile-badge" style="gap:8px;">
            <div class="avatar" id="current-user-avatar" aria-label="User Avatar">SL</div>
            <div class="user-details">
              <span class="user-name" id="current-user-name">Sarah Lin</span>
              <span class="user-role-badge" id="current-user-role-badge">Lead Cashier</span>
            </div>
            <button class="icon-btn-sm" id="lock-user-btn" onclick="openLoginModal()" title="Switch Account / Lock Session" aria-label="Switch Account or Lock Session" style="margin-left:4px;">
              <i class="ri-lock-line"></i>
            </button>
          </div>
        </div>
      </header>

      <!-- Dynamic Workspace View & Permanent Cart Drawer Wrapper -->
      <div class="workspace-wrapper">
        <!-- Dynamic Workspace View Container -->
        <div class="workspace" id="workspace-container" tabindex="-1" role="region" aria-label="Main Workspace Content">
          <!-- Views will be injected dynamically via JS -->
        </div>

        <!-- Floating Mobile Cart Bar (Visible on Mobile/Tablet when POS is active) -->
        <button class="mobile-cart-bar hidden" id="mobile-cart-bar" onclick="openMobileCartDrawer()" aria-label="Open Current Sale Cart Drawer">
          <div class="mobile-cart-bar-left">
            <span class="cart-pill-icon"><i class="ri-shopping-cart-2-line"></i></span>
            <span id="mobile-cart-count">0 items</span>
          </div>
          <div class="mobile-cart-bar-right">
            <span id="mobile-cart-total">$0.00</span>
            <span class="cart-open-hint">View Sale <i class="ri-arrow-up-s-line"></i></span>
          </div>
        </button>

        <!-- Slide-Out Order Cart Drawer Element -->
        <aside class="cart-drawer hidden" id="cart-drawer" role="complementary" aria-label="Current Sale Cart">
          <div class="cart-header">
            <div class="cart-title">
              <i class="ri-shopping-cart-2-line"></i>
              <span>Current Sale</span>
              <span class="cart-order-id" id="cart-order-number">#ORD-9042</span>
            </div>
            <button class="icon-btn close-cart" id="close-cart-btn" aria-label="Close sale cart"><i class="ri-close-line"></i></button>
          </div>

          <!-- Order Context: Order Type & Table Selection -->
          <div class="cart-context-bar">
            <div class="segmented-control">
              <button class="segment-btn active" data-ordertype="dine_in">
                <i class="ri-restaurant-line"></i> Dine In
              </button>
              <button class="segment-btn" data-ordertype="takeaway">
                <i class="ri-takeaway-line"></i> Takeaway
              </button>
            </div>

            <div class="table-select-wrapper" id="cart-table-select-group">
              <span class="table-status-indicator-dot" id="cart-table-status-dot"></span>
              <select id="cart-table-select" class="form-select-sm">
                <!-- Dynamically rendered by renderCartTableSelect() -->
              </select>
            </div>
          </div>

          <!-- Customer Tagging Bar -->
          <div class="cart-customer-tag" id="cart-customer-tag-bar">
            <div class="customer-tag-info" id="cart-customer-info">
              <i class="ri-user-3-line"></i>
              <span>Walk-in Customer</span>
            </div>
            <button class="btn btn-sm btn-ghost" id="attach-customer-btn">
              <i class="ri-user-add-line"></i> Loyalty
            </button>
          </div>

          <!-- Cart Items List -->
          <div class="cart-items-container" id="cart-items-list">
            <div class="empty-cart-state">
              <i class="ri-cup-line"></i>
              <p>No items added yet</p>
              <span>Select items from menu to start order</span>
            </div>
          </div>

          <!-- Discount Code / Voucher Input -->
          <div class="cart-promo-section">
            <div class="promo-input-group">
              <i class="ri-price-tag-3-line"></i>
              <input type="text" id="promo-code-input" placeholder="Promo code...">
              <button class="btn btn-secondary btn-sm" id="apply-promo-btn">Apply</button>
            </div>
            <div id="applied-promo-tag" class="applied-promo hidden">
              <span id="applied-promo-name">MELB10 (10% Off)</span>
              <button class="remove-promo-btn" id="remove-promo-btn"><i class="ri-close-circle-line"></i></button>
            </div>
          </div>

          <!-- Cart Bill Breakdown -->
          <div class="cart-summary">
            <div class="summary-row">
              <span>Subtotal</span>
              <span id="cart-subtotal">$0.00</span>
            </div>
            <div class="summary-row text-muted">
              <span>GST Included (10%)</span>
              <span id="cart-gst">$0.00</span>
            </div>
            <div class="summary-row text-discount hidden" id="cart-discount-row">
              <span>Discount Applied</span>
              <span id="cart-discount">-$0.00</span>
            </div>
            <div class="summary-row total-row">
              <span>Total Payable</span>
              <span id="cart-total">$0.00</span>
            </div>
          </div>

          <!-- Checkout Actions -->
          <div class="cart-actions">
            <button class="btn btn-outline btn-lg" id="clear-cart-btn">Clear</button>
            <button class="btn btn-primary btn-lg flex-1" id="checkout-btn" disabled>
              <i class="ri-money-dollar-circle-line"></i> Charge <span id="checkout-btn-total">$0.00</span>
            </button>
          </div>
        </aside>
      </div>

  </div>

  <!-- MODALS CONTAINER -->
  
  <!-- Item Customiser Modal -->
  <div class="modal-backdrop hidden" id="customiser-modal">
    <div class="modal-card">
      <div class="modal-header" style="gap:16px;">
        <div id="customiser-item-img-wrap" style="width:56px; height:56px; border-radius:10px; overflow:hidden; flex-shrink:0; background:var(--bg-canvas);">
          <img id="customiser-item-img" src="./brand_recources/flat_white_coffee.png" alt="Product" style="width:100%; height:100%; object-fit:cover;">
        </div>
        <div class="modal-title-group" style="flex:1;">
          <h3 id="customiser-item-name">Single Origin Flat White</h3>
          <span class="modal-subtitle" id="customiser-item-desc">House Espresso Blend • Seasonal Bean</span>
        </div>
        <button class="icon-btn modal-close" id="close-customiser-btn"><i class="ri-close-line"></i></button>
      </div>

      <div class="modal-body customiser-body">
        <!-- Dynamic Customiser Sections Injected Here -->
        <div id="dynamic-customiser-sections"></div>

        <!-- Special Instructions -->
        <div class="customiser-section">
          <label class="section-label">Barista Instructions</label>
          <textarea id="customiser-item-notes" class="form-textarea" placeholder="E.g., Half-sweet, double-cupped, separate water..."></textarea>
        </div>

      </div>

      <div class="modal-footer">
        <div class="quantity-picker">
          <button class="icon-btn-sm" id="qty-minus"><i class="ri-subtract-line"></i></button>
          <span id="customiser-qty">1</span>
          <button class="icon-btn-sm" id="qty-plus"><i class="ri-add-line"></i></button>
        </div>
        <button class="btn btn-primary btn-lg flex-1" id="add-to-cart-confirm-btn">
          Add to Sale • <span id="customiser-calculated-price">$4.80</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Payment Modal -->
  <div class="modal-backdrop hidden" id="payment-modal">
    <div class="modal-card modal-lg">
      <div class="modal-header">
        <div class="modal-title-group">
          <h3>Payment Processing</h3>
          <span class="modal-subtitle">Select payment tender type for Sale <strong id="pay-modal-order-id">#ORD-9042</strong></span>
        </div>
        <button class="icon-btn modal-close" id="close-payment-btn"><i class="ri-close-line"></i></button>
      </div>

      <div class="modal-body payment-body">
        <div class="payment-layout">
          <!-- Payment Method Selector -->
          <div class="payment-methods-column">
            <label class="section-label">Payment Method</label>
            <div class="payment-tabs">
              <button class="pay-tab active" data-method="eftpos">
                <i class="ri-bank-card-line"></i>
                <span>EFTPOS / Card</span>
              </button>
              <button class="pay-tab" data-method="cash">
                <i class="ri-cash-line"></i>
                <span>Cash Tender</span>
              </button>
              <button class="pay-tab" data-method="paypal">
                <i class="ri-paypal-line"></i>
                <span>PayPal</span>
              </button>
              <button class="pay-tab" data-method="loyalty">
                <i class="ri-vip-crown-line"></i>
                <span>Loyalty Points</span>
              </button>
            </div>

            <!-- Dynamic Tender Form -->
            <div class="tender-form-wrapper">
              
              <!-- EFTPOS Panel -->
              <div id="tender-panel-eftpos" class="tender-panel">
                <div class="eftpos-terminal-box">
                  <i class="ri-wireless-charging-line pulse"></i>
                  <h4>EFTPOS Terminal Ready</h4>
                  <p>Present card, phone, or smartwatch on terminal unit #01</p>
                  <div class="terminal-status-badge"><i class="ri-check-line"></i> Connected to Tyro EFTPOS</div>
                </div>
              </div>

              <!-- Cash Panel -->
              <div id="tender-panel-cash" class="tender-panel hidden">
                <div class="form-group">
                  <label>Amount Tendered (AUD)</label>
                  <div class="input-prefix-group">
                    <span class="prefix">$</span>
                    <input type="number" id="cash-tendered-input" class="form-input-lg" placeholder="0.00" step="0.05">
                  </div>
                </div>
                <div class="quick-cash-buttons">
                  <button class="btn btn-outline quick-cash-btn" data-val="exact">Exact</button>
                  <button class="btn btn-outline quick-cash-btn" data-val="10">$10.00</button>
                  <button class="btn btn-outline quick-cash-btn" data-val="20">$20.00</button>
                  <button class="btn btn-outline quick-cash-btn" data-val="50">$50.00</button>
                </div>
                <div class="change-due-box">
                  <span>Change Due:</span>
                  <strong id="cash-change-due">$0.00</strong>
                </div>
              </div>

              <!-- PayPal Panel -->
              <div id="tender-panel-paypal" class="tender-panel hidden">
                <div class="paypal-terminal-box">
                  <div class="paypal-brand-row">
                    <div class="paypal-logo-wrap">
                      <i class="ri-paypal-fill"></i>
                    </div>
                    <div>
                      <h4>PayPal Sandbox Checkout</h4>
                      <p>Instant digital wallet & debit/credit card processing</p>
                    </div>
                  </div>
                  <div class="paypal-amount-display">
                    <span>Payable Amount:</span>
                    <strong id="paypal-amount-due">$0.00 AUD</strong>
                  </div>
                  <div id="paypal-button-container" class="paypal-button-container"></div>
                  <div id="paypal-status-box" class="paypal-status-box hidden"></div>
                </div>
              </div>

              <!-- Loyalty Panel -->
              <div id="tender-panel-loyalty" class="tender-panel hidden">
                <div class="loyalty-pay-box">
                  <div class="customer-points-badge">
                    <span class="points-val" id="pay-modal-cust-pts">450 Pts</span>
                    <span class="points-worth" id="pay-modal-pts-worth">Worth $22.50 AUD</span>
                  </div>
                  <p class="text-sm">Loyalty points can be redeemed at 20 Pts = $1.00 AUD.</p>
                  <button class="btn btn-secondary w-100" id="redeem-points-pay-btn">Redeem Points for Sale</button>
                </div>
              </div>

            </div>
          </div>

          <!-- Order Summary Sidebar inside Payment -->
          <div class="payment-summary-column">
            <div class="pay-summary-card">
              <h4>Order Breakdown</h4>
              <div class="pay-items-mini" id="pay-modal-items-list">
                <!-- Mini items list -->
              </div>
              <div class="pay-totals-mini">
                <div class="row"><span>Subtotal:</span> <span id="pay-modal-subtotal">$0.00</span></div>
                <div class="row"><span>GST (10%):</span> <span id="pay-modal-gst">$0.00</span></div>
                <div class="row text-discount" id="pay-modal-discount-row"><span>Discount:</span> <span id="pay-modal-discount">-$0.00</span></div>
                <hr>
                <div class="row total-large"><span>Total Due:</span> <strong id="pay-modal-total">$0.00</strong></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline btn-lg" id="cancel-payment-btn">Cancel</button>
        <button class="btn btn-success btn-lg flex-1" id="confirm-payment-btn">
          <i class="ri-check-double-line"></i> Complete Payment & Print Receipt
        </button>
      </div>
    </div>
  </div>

  <!-- Printable Receipt Modal -->
  <div class="modal-backdrop hidden" id="receipt-modal">
    <div class="modal-card modal-sm">
      <div class="modal-header">
        <h3>Sales Receipt</h3>
        <button class="icon-btn modal-close" id="close-receipt-btn"><i class="ri-close-line"></i></button>
      </div>
      <div class="modal-body">
        <div class="thermal-receipt" id="thermal-receipt-content">
          <div class="receipt-brand">
            <div style="width:48px; height:48px; margin:0 auto 8px; border-radius:50%; overflow:hidden; border:1px solid #333;">
              <img src="./brand_recources/ravenhill_logo.png" alt="Logo" style="width:100%; height:100%; object-fit:cover;">
            </div>
            <h2>RAVENHILL COFFEE</h2>
            <p>142 Flinders Lane, Melbourne CBD VIC 3000</p>
            <p>ABN: 48 920 184 721 • Ph: (03) 9654 8812</p>
          </div>
          <div class="receipt-divider">================================</div>
          <div class="receipt-meta">
            <div>Order: <strong id="rec-order-id">#ORD-9042</strong></div>
            <div>Date: <span id="rec-date">06/08/2026 10:45 AM</span></div>
            <div>Type: <span id="rec-type">Dine In (Table 03)</span></div>
            <div>Cashier: <span id="rec-cashier">Sarah Lin</span></div>
          </div>
          <div class="receipt-divider">--------------------------------</div>
          <div class="receipt-items" id="rec-items-list">
            <!-- Items injected here -->
          </div>
          <div class="receipt-divider">--------------------------------</div>
          <div class="receipt-totals">
            <div class="r-row"><span>Subtotal:</span><span id="rec-subtotal">$0.00</span></div>
            <div class="r-row"><span>GST Included (10%):</span><span id="rec-gst">$0.00</span></div>
            <div class="r-row" id="rec-discount-row"><span>Discount:</span><span id="rec-discount">-$0.00</span></div>
            <div class="r-row r-total"><span>TOTAL AUD:</span><span id="rec-total">$0.00</span></div>
            <div class="r-row"><span>Tender (<span id="rec-tender-type">EFTPOS</span>):</span><span id="rec-tendered">$0.00</span></div>
            <div class="r-row"><span>Change:</span><span id="rec-change">$0.00</span></div>
          </div>
          <div class="receipt-divider">================================</div>
          <div class="receipt-footer">
            <p>Thank you for supporting specialty coffee!</p>
            <p>Beans roasted fresh in Melbourne.</p>
            <div class="barcode-sim">||||| ||||||| |||| |||||||| |||</div>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button class="btn btn-secondary flex-1" id="print-receipt-btn" style="min-width: 130px;"><i class="ri-printer-line"></i> Print Receipt</button>
        <button class="btn btn-outline flex-1" id="download-pdf-receipt-btn" style="min-width: 140px; background: rgba(217, 107, 67, 0.15); border-color: var(--color-primary); color: #fff;"><i class="ri-file-pdf-line"></i> Download PDF</button>
        <button class="btn btn-primary" id="finish-receipt-btn" style="min-width: 80px;">Done</button>
      </div>
    </div>
  </div>

  <!-- Attach Customer Modal -->
  <div class="modal-backdrop hidden" id="customer-modal">
    <div class="modal-card">
      <div class="modal-header">
        <h3>Attach Loyalty Customer</h3>
        <button class="icon-btn modal-close" id="close-customer-modal-btn"><i class="ri-close-line"></i></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <input type="text" id="cust-search-input" class="form-input" placeholder="Search customer by name, mobile (e.g. 0412...), or email...">
        </div>
        <div class="customer-select-list" id="customer-select-list">
          <!-- List of loyalty members -->
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" id="detach-cust-btn">Detach / Walk-in</button>
      </div>
    </div>
  </div>

  <!-- Add Reservation Modal -->
  <div class="modal-backdrop hidden" id="add-reservation-modal">
    <div class="modal-card">
      <div class="modal-header">
        <div class="modal-title-group">
          <h3>Create Table Booking</h3>
          <span class="modal-subtitle">Reserve a table & seating slot for guests</span>
        </div>
        <button class="icon-btn modal-close" onclick="closeAddReservationModal()"><i class="ri-close-line"></i></button>
      </div>
      <div class="modal-body">
        <form id="add-reservation-form" onsubmit="submitNewReservation(event)">
          <div class="form-group" style="margin-bottom:14px;">
            <label style="font-weight:600; margin-bottom:6px; display:block;">Customer Name *</label>
            <input type="text" id="res-cust-name" class="form-input" placeholder="e.g. Alex Mercer" required style="width:100%; padding:10px 12px; border-radius:8px; background:var(--bg-canvas); border:1px solid var(--color-border); color:var(--color-cream);">
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
            <div class="form-group">
              <label style="font-weight:600; margin-bottom:6px; display:block;">Party Size *</label>
              <select id="res-party-size" class="form-select" style="width:100%; padding:10px 12px; border-radius:8px; background:var(--bg-canvas); border:1px solid var(--color-border); color:var(--color-cream);">
                <option value="1">1 Guest</option>
                <option value="2" selected>2 Guests</option>
                <option value="3">3 Guests</option>
                <option value="4">4 Guests</option>
                <option value="5">5 Guests</option>
                <option value="6">6 Guests (Group)</option>
                <option value="8">8 Guests (Large Group)</option>
              </select>
            </div>
            <div class="form-group">
              <label style="font-weight:600; margin-bottom:6px; display:block;">Assigned Table *</label>
              <select id="res-table-select" class="form-select" style="width:100%; padding:10px 12px; border-radius:8px; background:var(--bg-canvas); border:1px solid var(--color-border); color:var(--color-cream);">
                <!-- Populated dynamically -->
              </select>
            </div>
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
            <div class="form-group">
              <label style="font-weight:600; margin-bottom:6px; display:block;">Time Slot *</label>
              <select id="res-time-slot" class="form-select" style="width:100%; padding:10px 12px; border-radius:8px; background:var(--bg-canvas); border:1px solid var(--color-border); color:var(--color-cream);">
                <option value="11:00 AM">11:00 AM</option>
                <option value="11:30 AM" selected>11:30 AM</option>
                <option value="12:00 PM">12:00 PM</option>
                <option value="12:30 PM">12:30 PM</option>
                <option value="01:00 PM">01:00 PM</option>
                <option value="01:30 PM">01:30 PM</option>
                <option value="02:00 PM">02:00 PM</option>
              </select>
            </div>
            <div class="form-group">
              <label style="font-weight:600; margin-bottom:6px; display:block;">Booking Status</label>
              <select id="res-status-select" class="form-select" style="width:100%; padding:10px 12px; border-radius:8px; background:var(--bg-canvas); border:1px solid var(--color-border); color:var(--color-cream);">
                <option value="Confirmed" selected>Confirmed</option>
                <option value="Pending">Pending Confirmation</option>
              </select>
            </div>
          </div>
          <div class="form-group" style="margin-bottom:16px;">
            <label style="font-weight:600; margin-bottom:6px; display:block;">Contact Mobile Phone</label>
            <input type="text" id="res-contact-phone" class="form-input" placeholder="e.g. 0412 889 201" style="width:100%; padding:10px 12px; border-radius:8px; background:var(--bg-canvas); border:1px solid var(--color-border); color:var(--color-cream);">
          </div>
          <div class="modal-footer" style="padding:0; margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" class="btn btn-outline" onclick="closeAddReservationModal()">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="ri-calendar-check-line"></i> Create Reservation</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Add Table Modal -->
  <div class="modal-backdrop hidden" id="add-table-modal">
    <div class="modal-card">
      <div class="modal-header">
        <div class="modal-title-group">
          <h3>Add New Dining Table</h3>
          <span class="modal-subtitle">Configure floor plan seating layout</span>
        </div>
        <button class="icon-btn modal-close" onclick="closeAddTableModal()"><i class="ri-close-line"></i></button>
      </div>
      <div class="modal-body">
        <form id="add-table-form" onsubmit="submitNewTable(event)">
          <div class="form-group" style="margin-bottom:14px;">
            <label style="font-weight:600; margin-bottom:6px; display:block;">Table ID *</label>
            <input type="text" id="tbl-id-input" class="form-input" placeholder="e.g. T-11" required style="width:100%; padding:10px 12px; border-radius:8px; background:var(--bg-canvas); border:1px solid var(--color-border); color:var(--color-cream);">
          </div>
          <div class="form-group" style="margin-bottom:14px;">
            <label style="font-weight:600; margin-bottom:6px; display:block;">Table Display Name *</label>
            <input type="text" id="tbl-name-input" class="form-input" placeholder="e.g. Table 11" required style="width:100%; padding:10px 12px; border-radius:8px; background:var(--bg-canvas); border:1px solid var(--color-border); color:var(--color-cream);">
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
            <div class="form-group">
              <label style="font-weight:600; margin-bottom:6px; display:block;">Dining Section *</label>
              <select id="tbl-section-select" class="form-select" style="width:100%; padding:10px 12px; border-radius:8px; background:var(--bg-canvas); border:1px solid var(--color-border); color:var(--color-cream);">
                <option value="Main Dining" selected>Main Dining</option>
                <option value="Window Bench">Window Bench</option>
                <option value="Courtyard Patio">Courtyard Patio</option>
                <option value="Espresso Bar">Espresso Bar</option>
                <option value="VIP Booth">VIP Booth</option>
              </select>
            </div>
            <div class="form-group">
              <label style="font-weight:600; margin-bottom:6px; display:block;">Seating Capacity *</label>
              <select id="tbl-capacity-select" class="form-select" style="width:100%; padding:10px 12px; border-radius:8px; background:var(--bg-canvas); border:1px solid var(--color-border); color:var(--color-cream);">
                <option value="2">2 Seats</option>
                <option value="4" selected>4 Seats</option>
                <option value="6">6 Seats</option>
                <option value="8">8 Seats (Large Group)</option>
              </select>
            </div>
          </div>
          <div class="modal-footer" style="padding:0; margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" class="btn btn-outline" onclick="closeAddTableModal()">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="ri-add-line"></i> Add Table</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- User & Role Login Modal -->
  <div class="modal-backdrop hidden" id="role-select-modal" style="z-index:9999;">
    <div class="role-select-card">
      <div class="role-select-brand">
        <div class="role-brand-icon" style="width:68px; height:68px; margin:0 auto 12px; border-radius:50%; overflow:hidden; border:2px solid var(--color-primary-light); box-shadow:0 4px 12px rgba(0,0,0,0.4);">
          <img src="./brand_recources/ravenhill_logo.png" alt="Ravenhill Logo" style="width:100%; height:100%; object-fit:cover;">
        </div>
        <h2>RAVENHILL COFFEE</h2>
        <p>User Authentication & Access Management</p>
      </div>
      <div class="role-select-body">
        <h3>User & Dashboard Login</h3>
        <p style="font-size:13px; color:var(--color-cream-muted); margin-bottom:16px;">Enter your password to access your role's dashboard features.</p>
        
        <div class="form-group" style="margin-bottom:16px;">
          <label style="font-weight:600; margin-bottom:6px; display:block;">Select Account / Role</label>
          <select id="role-popup-select" class="form-select" style="width:100%; padding:12px 14px; font-size:15px; border-radius:10px; background:var(--bg-elevated); border:1px solid var(--color-border); color:var(--color-cream); appearance:auto;">
            <option value="admin">⚡ Admin (Full Control)</option>
            <option value="manager">📊 Manager (Operations & Staff)</option>
            <option value="cashier" selected>💳 Cashier (POS & Sales)</option>
            <option value="kitchen">🍳 Kitchen Staff (Food KDS)</option>
            <option value="barista">☕ Barista (Beverages KDS)</option>
            <option value="waitstaff">🤵 Wait Staff (Table Service)</option>
            <option value="customer">👤 Customer (Live Tracker & Menu)</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:16px;">
          <label style="font-weight:600; margin-bottom:6px; display:block;">Password</label>
          <div style="position:relative; display:flex; align-items:center;">
            <input 
              type="text" 
              id="role-password-input" 
              class="form-input" 
              value="#DemoPass" 
              placeholder="#DemoPass"
              onkeydown="if(event.key==='Enter'){ confirmRoleSelection(); }"
              style="padding-right:44px; font-size:16px; font-weight:700; border-radius:10px; background:var(--bg-canvas);"
            />
            <button 
              type="button" 
              onclick="togglePasswordVisibility()"
              style="position:absolute; right:12px; background:none; border:none; color:var(--color-cream-muted); cursor:pointer; font-size:20px; display:flex; align-items:center; justify-content:center;"
              title="Toggle password visibility"
            >
              <i class="ri-eye-line" id="toggle-pass-icon"></i>
            </button>
          </div>
          <div class="demo-pass-hint" style="font-size:12px; color:var(--color-accent-gold); margin-top:8px; display:flex; align-items:center; gap:6px; background:rgba(217, 119, 6, 0.12); padding:8px 12px; border-radius:8px; border:1px solid rgba(217, 119, 6, 0.3);">
            <i class="ri-key-2-line" style="font-size:16px;"></i>
            <span>Password is <strong>#DemoPass</strong> to check the project.</span>
          </div>
        </div>

        <div id="role-pass-error" class="hidden" role="alert" aria-live="assertive" style="color:var(--color-danger); font-size:12px; font-weight:600; margin-top:12px; padding:8px 12px; background:rgba(255,107,107,0.1); border-radius:6px; border:1px solid rgba(255,107,107,0.3);">
          Invalid password! Password is #DemoPass to check the project.
        </div>


        <button class="btn btn-primary btn-lg" style="width:100%; margin-top:16px; padding:14px; font-size:16px; border-radius:12px; font-weight:700;" onclick="confirmRoleSelection()">
          <i class="ri-lock-unlock-line"></i> Log In to Dashboard
        </button>
      </div>
    </div>
  </div>
  <!-- Thermal Printable Receipt Modal (FR36) -->
  <div class="modal-overlay hidden" id="printable-receipt-modal" role="dialog" aria-modal="true" aria-labelledby="receipt-order-id" style="z-index:9999;">
    <div class="modal-content" style="max-width:400px; background:#fff; color:#000; font-family: 'Courier New', Courier, monospace; padding:24px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.5);">
      <div id="thermal-receipt-content" style="text-align:center;">
        <h2 style="font-size:20px; font-weight:800; margin-bottom:4px; text-transform:uppercase;">RAVENHILL COFFEE</h2>
        <p style="font-size:12px; margin:0;">Specialty Coffee Roasters</p>
        <p style="font-size:12px; margin:0;">Shop 4, Prahran Market / Melb CBD</p>
        <p style="font-size:12px; margin-bottom:12px;">ABN: 84 109 238 901 • Ph: (03) 9510 2030</p>
        <div style="border-top:1px dashed #000; border-bottom:1px dashed #000; padding:8px 0; margin-bottom:12px; text-align:left; font-size:12px;">
          <div>Order: <strong id="receipt-order-id">#ORD-9042</strong></div>
          <div>Date: <span id="receipt-date">15 Aug 2026 10:45 AM</span></div>
          <div>Cashier: <span id="receipt-cashier">Sarah Lin</span></div>
          <div>Type: <span id="receipt-type">Dine In (Table 02)</span></div>
        </div>
        <table style="width:100%; text-align:left; font-size:12px; border-collapse:collapse; margin-bottom:12px;" id="receipt-items-table">
          <thead>
            <tr style="border-bottom:1px solid #000;">
              <th style="padding-bottom:4px;">Qty Item</th>
              <th style="text-align:right; padding-bottom:4px;">Amount</th>
            </tr>
          </thead>
          <tbody>
            <!-- Line items injected via JS -->
          </tbody>
        </table>
        <div style="border-top:1px dashed #000; padding-top:8px; font-size:12px; text-align:right;">
          <div>Subtotal: <span id="receipt-subtotal">$0.00</span></div>
          <div>GST (10%): <span id="receipt-tax">$0.00</span></div>
          <div>Discount: <span id="receipt-discount">-$0.00</span></div>
          <div style="font-size:16px; font-weight:bold; margin-top:6px;">TOTAL: <span id="receipt-total">$0.00</span></div>
          <div>Payment Method: <strong id="receipt-pay-method">CARD</strong></div>
        </div>
        <div style="margin-top:16px; border-top:1px dashed #000; padding-top:12px; font-size:11px;">
          <p style="margin:0; font-weight:bold;">Thank you for visiting Ravenhill Coffee!</p>
          <p style="margin:4px 0 0 0;">Follow us @ravenhillcoffee</p>
        </div>
      </div>
      <div style="display:flex; gap:12px; margin-top:20px;">
        <button class="btn btn-secondary" onclick="closePrintableReceiptModal()" style="flex:1; padding:10px; font-weight:bold; cursor:pointer;">Close</button>
        <button class="btn btn-primary" onclick="window.print()" style="flex:1; padding:10px; font-weight:bold; cursor:pointer; background:#22c55e; border:none; color:#fff; border-radius:8px;">
          <i class="ri-printer-line"></i> Print Receipt
        </button>
      </div>
    </div>
  </div>

  <!-- Deferred Non-Blocking Libraries -->
  <script defer src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script defer src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script defer src="https://www.paypal.com/sdk/js?client-id=AYJK7O3QBO-dlC1YlzWd8eujwC_mGTQjgwG2V6UiGgzIh3gdfFa1nviCwQ02LU7q6ZuwIGet0HjVelto&currency=AUD"></script>
  <script src="app.js"></script>
</body>
</html>

