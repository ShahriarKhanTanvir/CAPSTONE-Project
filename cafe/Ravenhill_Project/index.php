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
  <!-- Google Fonts: Luxury Typography Suite -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,400&family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Remixicon Icons -->
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css?v=<?php echo filemtime(__DIR__ . '/styles.css'); ?>">
</head>
<body class="theme-dark">
  <!-- =========================================================================
       DEDICATED SEPARATE BRAND LANDING PAGE (Main Entry Point)
       ========================================================================= -->
  <div id="landing-page-view" class="landing-page-container">
    
    <!-- Top Animated Promotional Banner -->
    <div class="promo-top-banner" id="top-promo-banner">
      <div class="promo-banner-content">
        <span class="promo-tag"><i class="ri-fire-fill"></i> Today's Special</span>
        <span class="promo-text">COFFEE + PASTRY COMBO ☕🥐 | SAVE 15%</span>
        <span class="promo-timer" id="promo-countdown-timer">02:45:18</span>
      </div>
      <button type="button" class="promo-claim-btn" onclick="window.claimSpecialPromoCombo ? window.claimSpecialPromoCombo() : window.enterAsCustomer()">
        <i class="ri-gift-line"></i> Claim Special Combo
      </button>
    </div>

    <!-- Floating Glassmorphic Top Navigation Bar -->
    <div class="landing-navbar-wrapper">
      <header class="landing-navbar" id="landing-navbar">
        <a href="#hero-section" class="landing-nav-brand" onclick="window.scrollTo({ top:0, behavior:'smooth' })">
          <div class="brand-avatar-glow">
            <img src="./brand_recources/ravenhill_logo.png" alt="Ravenhill Coffee Roasters Logo">
          </div>
          <div class="landing-brand-text">
            <span class="landing-brand-title">RAVENHILL</span>
            <span class="landing-brand-sub">Coffee Roasters • Melb CBD</span>
          </div>
        </a>

        <nav class="landing-nav-menu">
          <ul class="landing-nav-links">
            <li><a href="#hero-section" class="landing-nav-link active"><i class="ri-home-4-line"></i> Home</a></li>
            <li><a href="#story-section" class="landing-nav-link"><i class="ri-book-open-line"></i> Our Story</a></li>
            <li><a href="#menu-preview-section" class="landing-nav-link"><i class="ri-cup-line"></i> Menu</a></li>
            <li><a href="#specials-section" class="landing-nav-link"><i class="ri-fire-line"></i> Specials</a></li>
            <li><a href="#roles-section" class="landing-nav-link highlight-gold"><i class="ri-user-star-line"></i> Choose Role</a></li>
            <li><a href="#location-section" class="landing-nav-link"><i class="ri-map-pin-line"></i> Location</a></li>
            <li><a href="#gallery-section" class="landing-nav-link"><i class="ri-camera-lens-line"></i> Gallery</a></li>
          </ul>
        </nav>

        <div class="landing-nav-actions">
          <button type="button" class="btn-nav-role" onclick="document.getElementById('roles-section')?.scrollIntoView({ behavior:'smooth' })">
            <i class="ri-user-star-fill"></i> <span>Choose Your Role</span>
          </button>
          <button type="button" class="btn-nav-login" onclick="window.openRoleLoginModal ? window.openRoleLoginModal('cashier') : window.openLoginModal('cashier')">
            <i class="ri-lock-line"></i> <span>Login</span>
          </button>
        </div>
      </header>
    </div>

    <!-- 1. Hero Section -->
    <section class="landing-hero" id="hero-section">
      <div class="hero-video-wrapper">
        <video class="hero-video-bg" autoplay muted loop playsinline poster="./brand_recources/roasted_coffee_beans.png">
          <source src="https://assets.mixkit.co/videos/preview/mixkit-coffee-being-poured-into-a-cup-32860-large.mp4" type="video/mp4">
          <source src="https://assets.mixkit.co/videos/preview/mixkit-barista-pouring-milk-into-a-cup-of-coffee-41712-large.mp4" type="video/mp4">
        </video>
        <div class="hero-video-overlay"></div>
        <canvas id="hero-steam-canvas" class="hero-steam-canvas"></canvas>
      </div>

      <div class="hero-content">
        <div class="hero-top-badges-row">
          <div class="hero-progression-badge">
            <i class="ri-sparkling-fill" id="hero-progression-icon"></i>
            <span id="hero-progression-text" class="hero-progression-text">WAKE UP.</span>
          </div>
          <div class="hero-status-pill">
            <span class="status-dot-pulse"></span>
            <span>Open Today • 6:30 AM – 4:00 PM • 142 Flinders Lane</span>
          </div>
        </div>

        <h1 class="hero-headline">
          Coffee, Crafted for <span class="text-gold-gradient">Your Moment.</span>
        </h1>
        <p class="hero-subheadline">
          Exceptional specialty coffee. Fresh daily artisan food. Good Melbourne vibes. Roasted and brewed with passion in the heart of Flinders Lane.
        </p>

        <div class="hero-cta-group">
          <button type="button" class="btn-hero-primary" onclick="document.getElementById('menu-preview-section')?.scrollIntoView({ behavior:'smooth' })">
            <i class="ri-cup-fill"></i> Explore Menu & Order Now <i class="ri-arrow-right-line"></i>
          </button>
          <button type="button" class="btn-hero-secondary" onclick="document.getElementById('roles-section')?.scrollIntoView({ behavior:'smooth' })">
            <i class="ri-user-star-line"></i> Choose Your Role
          </button>
        </div>

        <!-- Hero Metric Highlights Bar -->
        <div class="hero-highlights-bar">
          <div class="hero-highlight-pill">
            <i class="ri-leaf-fill"></i> 100% Ethical Micro-Lots
          </div>
          <div class="hero-highlight-pill">
            <i class="ri-fire-fill"></i> Small-Batch Roasted Daily
          </div>
          <div class="hero-highlight-pill">
            <i class="ri-star-fill"></i> 4.9 ★ (1,450+ Reviews)
          </div>
          <div class="hero-highlight-pill">
            <i class="ri-flashlight-fill"></i> Fast Laneway Table & Pickup
          </div>
        </div>
      </div>
    </section>

    <!-- 2. Good Vibes Storytelling Marquee Strip -->
    <div class="vibes-marquee-strip">
      <div class="vibes-marquee-track">
        <span class="vibes-item">GOOD COFFEE. BETTER DAYS. <i class="ri-star-fill vibes-star"></i></span>
        <span class="vibes-item">YOUR DAILY RITUAL, MADE BETTER. <i class="ri-star-fill vibes-star"></i></span>
        <span class="vibes-item">MORE THAN COFFEE. <i class="ri-star-fill vibes-star"></i></span>
        <span class="vibes-item">TAKE A MOMENT. <i class="ri-star-fill vibes-star"></i></span>
        <span class="vibes-item">MELBOURNE CBD SPECIALTY ROASTER <i class="ri-star-fill vibes-star"></i></span>
        <span class="vibes-item">GOOD COFFEE. BETTER DAYS. <i class="ri-star-fill vibes-star"></i></span>
        <span class="vibes-item">YOUR DAILY RITUAL, MADE BETTER. <i class="ri-star-fill vibes-star"></i></span>
        <span class="vibes-item">MORE THAN COFFEE. <i class="ri-star-fill vibes-star"></i></span>
        <span class="vibes-item">TAKE A MOMENT. <i class="ri-star-fill vibes-star"></i></span>
      </div>
    </div>

    <!-- 3. Story & Roasting Heritage Section (Bento Grid Architecture) -->
    <section class="landing-section" id="story-section">
      <div class="section-header-centered">
        <span class="section-tag">Roasting Craft & Heritage</span>
        <h2 class="section-main-title">Every cup is a ritual. Every bean tells a story.</h2>
        <p class="section-subtext">Nestled along iconic Flinders Lane, Ravenhill Coffee Roasters is dedicated to the art and science of Melbourne specialty coffee.</p>
      </div>

      <div class="story-bento-grid">
        <!-- Big Spotlight Card -->
        <div class="bento-card bento-card-spotlight">
          <div class="bento-visual-box">
            <img src="./brand_recources/roasted_coffee_beans.png" alt="Ravenhill Roasted Coffee Beans" loading="lazy">
            <div class="bento-badge-floating">
              <i class="ri-fire-fill"></i>
              <div>
                <strong>Batch #142 Roasted Fresh</strong>
                <span>Dialed In Daily at 6:00 AM</span>
              </div>
            </div>
          </div>
          <div class="bento-text-box">
            <span class="section-tag" style="color:var(--color-accent-gold);">Our Roasting Philosophy</span>
            <h3 style="font-family:'Outfit', sans-serif; font-size:24px; color:#fff; margin:6px 0 10px;">Precision Sourcing & Custom Curves</h3>
            <p style="color:var(--color-cream-muted); font-size:14px; line-height:1.6; margin-bottom:16px;">
              From high-altitude Ethiopian and Colombian estates to our gentle drum roasting profile, we accentuate floral aromatics, caramel sweetness, and silky mouthfeel.
            </p>
            <div class="tasting-notes-tags">
              <span class="tasting-tag">🍫 Dark Cacao</span>
              <span class="tasting-tag">🍯 Toffee & Caramel</span>
              <span class="tasting-tag">🍊 Bergamot & Citrus</span>
              <span class="tasting-tag">🫐 Wild Blueberries</span>
            </div>
          </div>
        </div>

        <!-- Bento Card 2: 100% Ethical -->
        <div class="bento-card bento-card-stat">
          <div class="bento-icon-glow"><i class="ri-earth-line"></i></div>
          <div class="bento-stat-num">100%</div>
          <h4 class="bento-stat-title">Ethical Direct Trade</h4>
          <p class="bento-stat-desc">Fair compensation & long-term partnerships directly with independent micro-lot coffee farming families.</p>
        </div>

        <!-- Bento Card 3: 28-Sec Extraction -->
        <div class="bento-card bento-card-stat">
          <div class="bento-icon-glow"><i class="ri-timer-flash-line"></i></div>
          <div class="bento-stat-num">28 sec</div>
          <h4 class="bento-stat-title">Golden Extraction Ratio</h4>
          <p class="bento-stat-desc">Calibrated 1:2 extraction pressure at 93.5°C for the ultimate balance of crema, body, and brightness.</p>
        </div>

        <!-- Bento Card 4: Verified Reviews -->
        <div class="bento-card bento-card-stat">
          <div class="bento-icon-glow"><i class="ri-medal-fill"></i></div>
          <div class="bento-stat-num">4.9 ★</div>
          <h4 class="bento-stat-title">1,450+ Verified Reviews</h4>
          <p class="bento-stat-desc">Voted among Melbourne CBD's finest laneway espresso destinations and roasting pioneers.</p>
        </div>
      </div>
    </section>

    <!-- 4. Menu & Our Coffee Showcase -->
    <section class="digital-menu-wrap" id="menu-preview-section">
      <div class="section-header-centered">
        <span class="section-tag">Our Melbourne Menu</span>
        <h2 class="section-main-title">Crafted with Passion. Served with Care.</h2>
        <p class="section-subtext">Explore our specialty espresso bar, artisan toasties, fresh pastries, and refreshing iced botanicals.</p>
      </div>

      <div class="category-nav-pills">
        <button type="button" class="category-pill-btn active" data-cat="coffee" onclick="filterLandingMenu('coffee')">
          <i class="ri-cup-line"></i> Coffee
        </button>
        <button type="button" class="category-pill-btn" data-cat="breakfast" onclick="filterLandingMenu('breakfast')">
          <i class="ri-bread-line"></i> Breakfast
        </button>
        <button type="button" class="category-pill-btn" data-cat="lunch" onclick="filterLandingMenu('lunch')">
          <i class="ri-restaurant-line"></i> Lunch
        </button>
        <button type="button" class="category-pill-btn" data-cat="cold" onclick="filterLandingMenu('cold')">
          <i class="ri-goblet-line"></i> Cold Drinks
        </button>
        <button type="button" class="category-pill-btn" data-cat="sweets" onclick="filterLandingMenu('sweets')">
          <i class="ri-cake-3-line"></i> Sweets
        </button>
      </div>

      <div class="digital-menu-grid" id="digital-menu-grid">
        <!-- Dynamically rendered via filterLandingMenu -->
      </div>

      <div style="text-align:center; margin-top:36px;">
        <button type="button" class="btn-hero-primary" onclick="window.enterAsCustomer()">
          <i class="ri-shopping-bag-3-fill"></i> View Full Menu & Order Online →
        </button>
      </div>
    </section>

    <!-- 5. Today's Special Combo Section -->
    <section class="landing-section" id="specials-section">
      <div class="section-header-centered">
        <span class="section-tag">Featured Promotion</span>
        <h2 class="section-main-title">Today's Special Deal</h2>
        <p class="section-subtext">Enjoy Melbourne's favorite morning ritual pairing with an exclusive promotional discount.</p>
      </div>

      <div class="specials-featured-card">
        <div class="specials-visual">
          <img src="./brand_recources/butter_croissant.png" alt="Coffee and Croissant Special Combo" loading="lazy">
          <span class="product-card-badge" style="position:absolute; top:12px; left:12px;">SAVE 15%</span>
        </div>
        <div>
          <span class="section-tag" style="color:var(--color-accent-gold);">Morning Combo Deal</span>
          <h3 style="font-family:'Outfit', sans-serif; font-size:28px; color:#fff; margin:8px 0 12px;">Specialty Coffee + Warm Pastry</h3>
          <p style="color:var(--color-cream-muted); font-size:14px; line-height:1.6; margin-bottom:20px;">
            Pair any handcrafted house blend or single origin coffee with our fresh daily-baked French butter croissant or almond pastry.
          </p>
          <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px; flex-wrap:wrap;">
            <div>
              <span style="font-size:12px; color:var(--color-cream-subtle); display:block;">Combo Price</span>
              <strong style="font-family:'Outfit', sans-serif; font-size:26px; color:var(--color-accent-gold);">$11.20 AUD</strong>
              <span style="font-size:13px; text-decoration:line-through; color:var(--color-cream-subtle); margin-left:6px;">$13.20</span>
            </div>
            <span class="promo-timer" style="font-size:14px; padding:6px 12px;"><i class="ri-time-line"></i> Limited Time Offer</span>
          </div>
          <button type="button" class="btn-hero-primary" onclick="window.claimSpecialPromoCombo()">
            <i class="ri-gift-line"></i> Claim Special Combo (15% OFF)
          </button>
        </div>
      </div>
    </section>

    <!-- 6. Choose Your Role Experience Section -->
    <section class="roles-section-wrap" id="roles-section">
      <div class="section-header-centered">
        <span class="section-tag">Explore the Experience</span>
        <h2 class="section-main-title">Choose Your Role</h2>
        <p class="section-subtext">Select how you would like to interact with the Ravenhill Coffee platform.</p>
      </div>

      <div class="roles-grid">
        <!-- Customer Role Card -->
        <div class="role-card-item featured-customer">
          <span class="role-card-badge">Guest & Member</span>
          <div class="role-card-icon">☕</div>
          <h3 class="role-card-title">Customer</h3>
          <p class="role-card-desc">Browse the menu, order food and drinks, make payments and receive an order number with real-time preparation tracking.</p>
          <ul class="role-card-features">
            <li><i class="ri-check-line"></i> Digital Menu & Customisations</li>
            <li><i class="ri-check-line"></i> 6 Active Payment Gateways</li>
            <li><i class="ri-check-line"></i> Unique Receipt & Order Tracker</li>
            <li><i class="ri-check-line"></i> Loyalty Points & Free Coffee</li>
          </ul>
          <button type="button" class="role-card-btn btn-role-customer" onclick="window.enterAsCustomer()">
            Continue as Customer <i class="ri-arrow-right-line"></i>
          </button>
        </div>

        <!-- Staff Role Card -->
        <div class="role-card-item role-card-staff">
          <span class="role-card-badge staff-badge">Staff & Crew</span>
          <div class="role-card-icon staff-icon">👨‍🍳</div>
          <h3 class="role-card-title">Staff / Operations</h3>
          <p class="role-card-desc">Access staff-related functions, manage kitchen and barista orders, floor tables and perform daily operational tasks.</p>
          <ul class="role-card-features">
            <li><i class="ri-check-line"></i> POS Register & Rapid Billing</li>
            <li><i class="ri-check-line"></i> Barista & Kitchen KDS Display</li>
            <li><i class="ri-check-line"></i> Wait Staff Floor Table Monitor</li>
            <li><i class="ri-check-line"></i> Shift Attendance & Clock-In</li>
          </ul>
          <button type="button" class="role-card-btn btn-role-staff" onclick="window.openRoleLoginModal('cashier')">
            Staff Login <i class="ri-lock-line"></i>
          </button>
        </div>

        <!-- Manager / Admin Role Card -->
        <div class="role-card-item role-card-admin">
          <span class="role-card-badge admin-badge">Executive</span>
          <div class="role-card-icon admin-icon">👨‍💼</div>
          <h3 class="role-card-title">Manager / Admin</h3>
          <p class="role-card-desc">Access executive management, sales analytics, inventory recipe tracking, staff rosters and system administration.</p>
          <ul class="role-card-features">
            <li><i class="ri-check-line"></i> Sales Revenue & Z-Reports</li>
            <li><i class="ri-check-line"></i> Bean Stock & Recipe Maps</li>
            <li><i class="ri-check-line"></i> Supplier Purchase Orders</li>
            <li><i class="ri-check-line"></i> Full Role Permission Controls</li>
          </ul>
          <button type="button" class="role-card-btn btn-role-admin" onclick="window.openRoleLoginModal('admin')">
            Admin Login <i class="ri-shield-keyhole-line"></i>
          </button>
        </div>
      </div>
    </section>

    <!-- 7. Digital Loyalty Program Section -->
    <section class="loyalty-section-wrap" id="rewards-section">
      <div class="loyalty-hero-card">
        <div style="text-align:center; max-width:650px; margin:0 auto;">
          <span class="section-tag" style="color:var(--color-accent-gold);">Ravenhill Loyalty Program</span>
          <h2 class="section-main-title" style="margin-bottom:8px;">Every Coffee Brings You Closer to Your Next One.</h2>
          <p class="section-subtext">Earn points on every cup, unlock VIP upgrades, and get your 6th coffee entirely free on us.</p>
        </div>

        <div class="loyalty-tracker-strip">
          <div class="stamp-node filled">
            <div class="stamp-circle"><i class="ri-cup-fill"></i></div>
            <span class="stamp-label">Cup 1 ✓</span>
          </div>
          <div class="stamp-node filled">
            <div class="stamp-circle"><i class="ri-cup-fill"></i></div>
            <span class="stamp-label">Cup 2 ✓</span>
          </div>
          <div class="stamp-node filled">
            <div class="stamp-circle"><i class="ri-cup-fill"></i></div>
            <span class="stamp-label">Cup 3 ✓</span>
          </div>
          <div class="stamp-node">
            <div class="stamp-circle"><i class="ri-cup-line"></i></div>
            <span class="stamp-label">Cup 4</span>
          </div>
          <div class="stamp-node">
            <div class="stamp-circle"><i class="ri-cup-line"></i></div>
            <span class="stamp-label">Cup 5</span>
          </div>
          <div class="stamp-node free-reward">
            <div class="stamp-circle"><i class="ri-gift-fill"></i></div>
            <span class="stamp-label" style="color:#10b981; font-weight:800;">FREE COFFEE</span>
          </div>
        </div>

        <div class="loyalty-tiers-row">
          <div class="tier-badge-card active-tier">
            <div style="font-size:18px; font-weight:700; margin-bottom:4px;">🥉 Bronze Member</div>
            <div style="font-size:12px; color:var(--color-cream-muted);">Earn 10 Pts per $1.00 spent. Redeem for $1.00 off per 20 Pts.</div>
          </div>
          <div class="tier-badge-card">
            <div style="font-size:18px; font-weight:700; margin-bottom:4px; color:var(--color-cream);">🥈 Silver Tier</div>
            <div style="font-size:12px; color:var(--color-cream-muted);">1.2x Point multiplier + Free large size upgrade on your birthday.</div>
          </div>
          <div class="tier-badge-card">
            <div style="font-size:18px; font-weight:700; margin-bottom:4px; color:var(--color-accent-gold);">🥇 Gold VIP</div>
            <div style="font-size:12px; color:var(--color-cream-muted);">1.5x Point multiplier + Free Oat / Almond milk upgrades forever.</div>
          </div>
        </div>

        <div style="text-align:center; margin-top:32px;">
          <button type="button" class="btn-hero-primary" onclick="joinRavenhillRewards()">
            <i class="ri-vip-crown-fill"></i> ⭐ Join Ravenhill Rewards
          </button>
        </div>
      </div>
    </section>

    <!-- 8. Location & Opening Hours Section -->
    <section class="landing-section" id="location-section">
      <div class="visit-section-grid">
        <div class="visit-info-card">
          <div>
            <span class="section-tag">Find Us in Melbourne CBD</span>
            <h3 class="section-main-title" style="font-size:26px;">142 Flinders Lane</h3>
            <p class="section-subtext">Located in Melbourne's iconic cultural coffee laneway corridor. Walk-ins welcome, express click & collect available.</p>

            <table class="hours-table">
              <tbody>
                <tr><td>Monday – Friday</td><td>6:30 AM – 4:00 PM</td></tr>
                <tr><td>Saturday</td><td>7:30 AM – 3:30 PM</td></tr>
                <tr><td>Sunday</td><td>8:00 AM – 3:00 PM</td></tr>
                <tr><td>Public Holidays</td><td>8:00 AM – 2:00 PM</td></tr>
              </tbody>
            </table>
          </div>

          <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:16px;">
            <button type="button" class="btn-hero-primary" onclick="window.enterAsCustomer('reservations')" style="padding:10px 20px; font-size:14px;">
              <i class="ri-calendar-check-line"></i> Book a Table
            </button>
            <a href="https://maps.google.com/?q=142+Flinders+Lane+Melbourne" target="_blank" rel="noopener" class="btn-hero-secondary" style="padding:10px 20px; font-size:14px;">
              <i class="ri-map-pin-2-line"></i> Open in Maps
            </a>
          </div>
        </div>

        <div class="visit-info-card" style="background:linear-gradient(135deg, #1c1510, #140e0a); justify-content:center; text-align:center; padding:40px 24px;">
          <i class="ri-store-2-fill" style="font-size:48px; color:var(--color-primary-light); margin-bottom:12px;"></i>
          <h4 style="font-size:22px; font-family:'Outfit', sans-serif; color:#fff; margin-bottom:8px;">Fast Laneway Pickup</h4>
          <p style="color:var(--color-cream-muted); font-size:14px; max-width:380px; margin:0 auto 20px;">Order ahead online with zero wait time. Your barista has your order steaming when you arrive.</p>
          <div>
            <button type="button" class="btn-hero-primary" onclick="window.enterAsCustomer('pos')" style="padding:12px 24px;">
              <i class="ri-smartphone-line"></i> Order Ahead Online
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- 9. Social & Melbourne Laneway Gallery -->
    <section class="gallery-section-wrap" id="gallery-section">
      <div class="section-header-centered">
        <span class="section-tag">Café Atmosphere</span>
        <h2 class="section-main-title">Melbourne Laneway Moments</h2>
        <p class="section-subtext">A glimpse into our daily craft, artisan barista pours, and Flinders Lane café energy.</p>
      </div>

      <div class="gallery-grid">
        <div class="gallery-card">
          <img src="./brand_recources/flat_white_coffee.png" alt="Melbourne Flat White" loading="lazy">
          <div class="gallery-overlay"><span>Velvety Micro-Foam Flat White</span></div>
        </div>
        <div class="gallery-card">
          <img src="./brand_recources/cappuccino_coffee.png" alt="Artisan Cappuccino" loading="lazy">
          <div class="gallery-overlay"><span>Cocoa-Dusted Cappuccino</span></div>
        </div>
        <div class="gallery-card">
          <img src="./brand_recources/double_espresso_short_black.png" alt="Double Espresso" loading="lazy">
          <div class="gallery-overlay"><span>Crema-Rich Double Espresso</span></div>
        </div>
        <div class="gallery-card">
          <img src="./brand_recources/butter_croissant.png" alt="Butter Croissant" loading="lazy">
          <div class="gallery-overlay"><span>Fresh Daily French Butter Pastries</span></div>
        </div>
        <div class="gallery-card">
          <img src="./brand_recources/iced_oat_milk_latte.png" alt="Iced Oat Milk Latte" loading="lazy">
          <div class="gallery-overlay"><span>Chilled Single Origin Iced Latte</span></div>
        </div>
        <div class="gallery-card">
          <img src="./brand_recources/v60_pourover_coffee.png" alt="V60 Single Origin Pourover" loading="lazy">
          <div class="gallery-overlay"><span>Precision Handcrafted V60 Pourover</span></div>
        </div>
      </div>
    </section>

    <!-- 10. Landing Page Footer -->
    <footer class="landing-footer" id="landing-footer">
      <div class="landing-footer-grid">
        <div>
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
            <img src="./brand_recources/ravenhill_logo.png" alt="Ravenhill Logo" style="width:36px; height:36px; border-radius:50%;">
            <strong style="font-family:'Outfit', sans-serif; font-size:18px; color:#fff; letter-spacing:1px;">RAVENHILL</strong>
          </div>
          <p style="font-size:13px; line-height:1.6; color:var(--color-cream-subtle);">
            Melbourne CBD Specialty Coffee Roasters. Dedicated to ethical sourcing, precision roasting, and extraordinary everyday coffee rituals.
          </p>
        </div>

        <div>
          <h5 style="color:#fff; font-size:14px; margin-bottom:12px;">Navigation</h5>
          <div style="display:flex; flex-direction:column; gap:8px; font-size:13px;">
            <a href="#hero-section" style="color:inherit; text-decoration:none;">Home</a>
            <a href="#story-section" style="color:inherit; text-decoration:none;">Our Story</a>
            <a href="#menu-preview-section" style="color:inherit; text-decoration:none;">Menu</a>
            <a href="#specials-section" style="color:inherit; text-decoration:none;">Specials</a>
            <a href="#location-section" style="color:inherit; text-decoration:none;">Location & Hours</a>
          </div>
        </div>

        <div>
          <h5 style="color:#fff; font-size:14px; margin-bottom:12px;">Access Portal</h5>
          <div style="display:flex; flex-direction:column; gap:8px; font-size:13px;">
            <a href="#roles-section" style="color:inherit; text-decoration:none;">Choose Your Role</a>
            <a href="#" onclick="window.enterAsCustomer()" style="color:inherit; text-decoration:none;">Customer Storefront</a>
            <a href="#" onclick="window.openRoleLoginModal('cashier')" style="color:inherit; text-decoration:none;">Staff Login</a>
            <a href="#" onclick="window.openRoleLoginModal('admin')" style="color:inherit; text-decoration:none;">Manager / Admin</a>
          </div>
        </div>

        <div>
          <h5 style="color:#fff; font-size:14px; margin-bottom:8px;">Get 10% Off First Order</h5>
          <p style="font-size:12px;">Join our Melbourne coffee dispatch newsletter:</p>
          <form onsubmit="event.preventDefault(); showToast('🎉 Subscribed! Use promo code MELB10 for 10% off at checkout.', 'success'); this.reset();" class="footer-newsletter-input">
            <input type="email" placeholder="Enter your email..." required>
            <button type="submit" class="btn-hero-primary" style="padding:8px 16px; font-size:12px; border-radius:20px;">Join</button>
          </form>
        </div>
      </div>

      <div class="footer-bottom-row">
        <span>© 2026 Ravenhill Coffee Roasters Pty Ltd. All Rights Reserved. ABN 88 142 904 883.</span>
        <span>142 Flinders Lane, Melbourne VIC 3000 • hello@ravenhillcoffee.com.au</span>
      </div>
    </footer>

  </div>

  <!-- Skip to Main Content Link for Keyboard / Screen Reader Accessibility -->
  <a href="#workspace-container" class="skip-to-content">Skip to main content</a>

  <!-- Mobile Sidebar Backdrop Overlay -->
  <div class="sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>

  <!-- =========================================================================
       INTERNAL POS, OPERATIONS & STOREFRONT APPLICATION SHELL
       ========================================================================= -->
  <div id="app-container" class="app-layout hidden">
    
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
        <div class="nav-section-title">Navigation & Storefront</div>
        <nav class="nav-menu">
          <a href="#" class="nav-item" onclick="window.showLandingView(); return false;" data-module="landing">
            <i class="ri-home-4-line"></i>
            <span>Brand Landing Page</span>
          </a>
          <a href="#" class="nav-item active" data-module="pos">
            <i class="ri-shopping-bag-3-line"></i>
            <span>Point of Sale (POS)</span>
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
          <a href="#" class="nav-item" data-module="payments">
            <i class="ri-bank-card-line"></i>
            <span>Payments & Invoices</span>
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
          <!-- Return to Dedicated Brand Landing Page -->
          <button type="button" class="btn-return-landing" onclick="window.showLandingView ? window.showLandingView() : window.switchModule('landing')" title="Back to Dedicated Brand Landing Page">
            <i class="ri-home-4-line"></i>
            <span>Home</span>
          </button>

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

          <!-- Topbar Cart Toggle Button -->
          <button class="topbar-cart-btn" id="topbar-cart-toggle-btn" onclick="toggleCartDrawer()" title="View Current Order Cart & Checkout" aria-label="View Current Order Cart">
            <i class="ri-shopping-cart-2-line"></i>
            <span class="topbar-cart-text">Cart</span>
            <span class="topbar-cart-badge" id="topbar-cart-count">0</span>
          </button>

          <!-- Role Selector Dropdown -->
          <div class="role-selector-container">
            <label for="user-role-select" class="role-label">Role:</label>
            <select id="user-role-select" class="role-select" aria-label="Select Active Role">
              <option value="customer" selected>☕ Customer (Storefront & Order)</option>
              <option value="cashier">💳 Cashier (POS & Sales)</option>
              <option value="kitchen">🍳 Kitchen Staff (Food KDS)</option>
              <option value="barista">☕ Barista (Beverages KDS)</option>
              <option value="waitstaff">🤵 Wait Staff (Table Service)</option>
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
            <button class="icon-btn-sm" id="lock-user-btn" onclick="logout()" title="Logout" aria-label="Logout" style="margin-left:4px;">
              <i class="ri-logout-box-r-line"></i>
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
            <button type="button" class="icon-btn close-cart" id="close-cart-btn" onclick="closeCartDrawer()" aria-label="Close sale cart"><i class="ri-close-line"></i></button>
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

          <!-- Quick Tender Shortcuts -->
          <div class="cart-quick-pay-row" style="display:grid; grid-template-columns: repeat(3, 1fr); gap:6px; margin-bottom:8px;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="quickPay('eftpos')" title="Instant EFTPOS Tap Payment">
              <i class="ri-wireless-charging-line"></i> Tap
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="quickPay('cash')" title="Cash Payment">
              <i class="ri-cash-line"></i> Cash
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="quickPay('paypal')" title="PayPal Checkout">
              <i class="ri-paypal-line"></i> PayPal
            </button>
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
        <button type="button" class="icon-btn modal-close" id="close-customiser-btn" onclick="closeCustomiserModal()" aria-label="Close customiser"><i class="ri-close-line"></i></button>
      </div>

      <div class="modal-body customiser-body">
        <!-- Dynamic Customiser Sections Injected Here -->
        <div id="dynamic-customiser-sections"></div>

        <!-- Special Instructions -->
        <div class="customiser-section">
          <label class="section-label" id="customiser-notes-label">Special Instructions & Dietary Notes</label>
          <textarea id="customiser-item-notes" class="form-textarea" placeholder="E.g., Extra hot, sauce on the side, nut allergy..."></textarea>
        </div>

      </div>

      <div class="modal-footer">
        <div class="quantity-picker">
          <button type="button" class="icon-btn-sm" id="qty-minus"><i class="ri-subtract-line"></i></button>
          <span id="customiser-qty">1</span>
          <button type="button" class="icon-btn-sm" id="qty-plus"><i class="ri-add-line"></i></button>
        </div>
        <button type="button" class="btn btn-primary btn-lg flex-1" id="add-to-cart-confirm-btn" onclick="confirmAddToCart()">
          Add to Cart • <span id="customiser-calculated-price">$4.80</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Payment Modal -->
  <div class="modal-backdrop hidden" id="payment-modal">
    <div class="modal-card modal-lg">
      <div class="modal-header">
        <div class="modal-title-group">
          <h3>Payment Processing & Checkout</h3>
          <span class="modal-subtitle">Select payment tender type for Sale <strong id="pay-modal-order-id">#ORD-9042</strong></span>
        </div>
        <button type="button" class="icon-btn modal-close" id="close-payment-btn" onclick="closePaymentModal()" aria-label="Close payment"><i class="ri-close-line"></i></button>
      </div>

      <div class="modal-body payment-body">
        <div class="payment-layout">
          <!-- Payment Method Selector -->
          <div class="payment-methods-column">
            <label class="section-label">Select Tender Method</label>
            <div class="payment-tabs">
              <button class="pay-tab active" data-method="eftpos">
                <i class="ri-wireless-charging-line"></i>
                <span>EFTPOS Tap</span>
              </button>
              <button class="pay-tab" data-method="card">
                <i class="ri-bank-card-line"></i>
                <span>Credit / Debit</span>
              </button>
              <button class="pay-tab" data-method="cash">
                <i class="ri-cash-line"></i>
                <span>Cash Tender</span>
              </button>
              <button class="pay-tab" data-method="paypal">
                <i class="ri-paypal-line"></i>
                <span>PayPal</span>
              </button>
              <button class="pay-tab" data-method="split">
                <i class="ri-pie-chart-line"></i>
                <span>Split Bill</span>
              </button>
              <button class="pay-tab" data-method="loyalty">
                <i class="ri-vip-crown-line"></i>
                <span>Loyalty Pts</span>
              </button>
            </div>

            <!-- Dynamic Tender Form -->
            <div class="tender-form-wrapper">
              
              <!-- EFTPOS Panel -->
              <div id="tender-panel-eftpos" class="tender-panel">
                <div class="eftpos-terminal-box">
                  <div class="terminal-screen-sim">
                    <div class="terminal-spinner"><i class="ri-wireless-charging-line pulse" style="font-size:36px; color:var(--color-primary);"></i></div>
                    <h4>EFTPOS Terminal Ready</h4>
                    <p>Present contactless card, Apple Pay, or Google Pay on terminal #01</p>
                    <div class="terminal-amount-tag">AUD <span id="eftpos-amount-display">$0.00</span></div>
                  </div>
                  <div class="terminal-status-badge"><i class="ri-check-line"></i> Integrated Tyro EFTPOS (Lane 1 Online)</div>
                </div>
              </div>

              <!-- Credit / Debit Card Panel -->
              <div id="tender-panel-card" class="tender-panel hidden">
                <div class="card-checkout-box">
                  <div class="credit-card-preview">
                    <div class="card-chip"><i class="ri-sim-card-line"></i></div>
                    <div class="card-number-display" id="card-preview-number">•••• •••• •••• 4242</div>
                    <div class="card-bottom-row">
                      <div>
                        <span class="card-label">CARDHOLDER</span>
                        <div class="card-name-display" id="card-preview-name">VALUED CUSTOMER</div>
                      </div>
                      <div>
                        <span class="card-label">EXPIRES</span>
                        <div class="card-expiry-display" id="card-preview-exp">12/28</div>
                      </div>
                    </div>
                  </div>

                  <div class="card-form-grid" style="margin-top:14px; display:grid; gap:10px;">
                    <div class="form-group">
                      <label style="font-size:12px;">Cardholder Name</label>
                      <input type="text" id="card-name-input" class="form-input" placeholder="Name on card" value="Sophia Reed">
                    </div>
                    <div class="form-group">
                      <label style="font-size:12px;">Card Number</label>
                      <div class="input-prefix-group">
                        <span class="prefix"><i class="ri-bank-card-fill"></i></span>
                        <input type="text" id="card-number-input" class="form-input" placeholder="4532 •••• •••• 4242" value="4532 8821 9012 4242">
                      </div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                      <div class="form-group">
                        <label style="font-size:12px;">Expiry (MM/YY)</label>
                        <input type="text" id="card-exp-input" class="form-input" placeholder="MM/YY" value="09/28">
                      </div>
                      <div class="form-group">
                        <label style="font-size:12px;">CVV / CVC</label>
                        <input type="password" id="card-cvv-input" class="form-input" placeholder="•••" maxlength="4" value="882">
                      </div>
                    </div>
                  </div>
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
                  <button type="button" class="btn btn-outline quick-cash-btn" data-val="exact">Exact</button>
                  <button type="button" class="btn btn-outline quick-cash-btn" data-val="5">$5.00</button>
                  <button type="button" class="btn btn-outline quick-cash-btn" data-val="10">$10.00</button>
                  <button type="button" class="btn btn-outline quick-cash-btn" data-val="20">$20.00</button>
                  <button type="button" class="btn btn-outline quick-cash-btn" data-val="50">$50.00</button>
                  <button type="button" class="btn btn-outline quick-cash-btn" data-val="100">$100.00</button>
                </div>
                <div class="change-due-box">
                  <span>Change Due to Customer:</span>
                  <strong id="cash-change-due">$0.00</strong>
                </div>
              </div>

              <!-- Split Bill Panel -->
              <div id="tender-panel-split" class="tender-panel hidden">
                <div class="split-bill-box">
                  <label class="section-label">Split Evenly Among Guests</label>
                  <div class="split-count-selector">
                    <button type="button" class="split-btn active" data-split="2">2 Ways</button>
                    <button type="button" class="split-btn" data-split="3">3 Ways</button>
                    <button type="button" class="split-btn" data-split="4">4 Ways</button>
                    <button type="button" class="split-btn" data-split="custom">Custom</button>
                  </div>
                  <div class="split-breakdown-card">
                    <div class="split-per-person">
                      <span id="split-shares-label">Share (1 of 2):</span>
                      <strong id="split-per-person-amount">$0.00</strong>
                    </div>
                    <div class="split-progress-bar">
                      <div class="split-progress-fill" id="split-progress-fill" style="width:0%;"></div>
                    </div>
                    <div class="split-status-text" id="split-status-text">0 of 2 shares paid ($0.00 of $0.00)</div>
                  </div>
                  <button type="button" class="btn btn-secondary w-100" id="pay-single-share-btn">
                    <i class="ri-check-line"></i> Pay Current Share (<span id="pay-share-val">$0.00</span>)
                  </button>
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
                      <h4>PayPal Instant Checkout</h4>
                      <p>Digital wallet, debit/credit cards, and Pay in 4</p>
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
                  <button type="button" class="btn btn-secondary w-100" id="redeem-points-pay-btn">Redeem Points for Sale</button>
                </div>
              </div>

            </div>
          </div>

          <!-- Order Summary Sidebar inside Payment -->
          <div class="payment-summary-column">
            <div class="pay-summary-card">
              <h4>Order Summary</h4>
              <div class="pay-items-mini" id="pay-modal-items-list">
                <!-- Mini items list -->
              </div>

              <!-- Gratuity / Tip Selection -->
              <div class="tip-selector-wrapper" style="margin-top:12px; padding-top:10px; border-top:1px solid var(--color-border-subtle);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                  <span style="font-size:11px; font-weight:700; color:var(--color-cream-muted);">ADD GRATUITY / TIP</span>
                  <span id="tip-amount-display" style="font-size:12px; font-weight:800; color:var(--color-accent-gold);">$0.00</span>
                </div>
                <div class="tip-pills-row" style="display:grid; grid-template-columns:repeat(5, 1fr); gap:4px;">
                  <button type="button" class="tip-pill active" data-tip="0">0%</button>
                  <button type="button" class="tip-pill" data-tip="5">5%</button>
                  <button type="button" class="tip-pill" data-tip="10">10%</button>
                  <button type="button" class="tip-pill" data-tip="15">15%</button>
                  <button type="button" class="tip-pill" data-tip="20">20%</button>
                </div>
              </div>

              <div class="pay-totals-mini" style="margin-top:12px;">
                <div class="row"><span>Subtotal:</span> <span id="pay-modal-subtotal">$0.00</span></div>
                <div class="row"><span>GST (10% Included):</span> <span id="pay-modal-gst">$0.00</span></div>
                <div class="row text-discount" id="pay-modal-discount-row"><span>Discount Applied:</span> <span id="pay-modal-discount">-$0.00</span></div>
                <div class="row" id="pay-modal-tip-row" style="color:var(--color-accent-gold);"><span>Tip / Gratuity:</span> <span id="pay-modal-tip">$0.00</span></div>
                <hr>
                <div class="row total-large"><span>Total Payable:</span> <strong id="pay-modal-total">$0.00</strong></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline btn-lg" id="cancel-payment-btn">Cancel</button>
        <button type="button" class="btn btn-success btn-lg flex-1" id="confirm-payment-btn">
          <i class="ri-check-double-line"></i> Authorise Payment & Complete Sale
        </button>
      </div>
    </div>
  </div>

  <!-- Printable Receipt Modal -->
  <div class="modal-backdrop hidden" id="receipt-modal">
    <div class="modal-card modal-sm">
      <div class="modal-header">
        <h3>Sales Receipt & Tax Invoice</h3>
        <button type="button" class="icon-btn modal-close" id="close-receipt-btn" onclick="closeReceiptModal()" aria-label="Close receipt"><i class="ri-close-line"></i></button>
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
            <div class="r-row" id="rec-tip-row"><span>Tip / Gratuity:</span><span id="rec-tip">$0.00</span></div>
            <div class="r-row r-total"><span>TOTAL AUD:</span><span id="rec-total">$0.00</span></div>
            <div class="r-row"><span>Tender (<span id="rec-tender-type">EFTPOS</span>):</span><span id="rec-tendered">$0.00</span></div>
            <div class="r-row"><span>Change Due:</span><span id="rec-change">$0.00</span></div>
          </div>
          <div class="receipt-divider">================================</div>
          <div class="receipt-footer">
            <p>Thank you for supporting specialty coffee!</p>
            <p>Beans roasted fresh in Melbourne.</p>
            <div class="barcode-sim">||||| ||||||| |||| |||||||| |||</div>
          </div>
        </div>

        <!-- Digital Sharing Form -->
        <div class="receipt-share-section" style="margin-top:14px; padding-top:10px; border-top:1px solid var(--color-border-subtle);">
          <label style="font-size:11px; font-weight:700; color:var(--color-cream-muted); display:block; margin-bottom:6px;">SEND DIGITAL RECEIPT</label>
          <div style="display:flex; gap:6px;">
            <input type="text" id="digital-receipt-target" class="form-input-sm" placeholder="Email or Mobile (04...)" style="flex:1;">
            <button type="button" class="btn btn-secondary btn-sm" id="send-digital-receipt-btn"><i class="ri-send-plane-line"></i> Send</button>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button type="button" class="btn btn-secondary flex-1" id="print-receipt-btn" style="min-width: 130px;"><i class="ri-printer-line"></i> Print Receipt</button>
        <button type="button" class="btn btn-outline flex-1" id="download-pdf-receipt-btn" style="min-width: 140px; background: rgba(217, 107, 67, 0.15); border-color: var(--color-primary); color: #fff;"><i class="ri-file-pdf-line"></i> Download Receipt</button>
        <button type="button" class="btn btn-primary" id="finish-receipt-btn" style="min-width: 80px;">Done</button>
      </div>
    </div>
  </div>

  <!-- Attach Customer Modal -->
  <div class="modal-backdrop hidden" id="customer-modal">
    <div class="modal-card">
      <div class="modal-header">
        <h3>Attach Loyalty Customer</h3>
        <button type="button" class="icon-btn modal-close" id="close-customer-modal-btn" onclick="closeCustomerModal()" aria-label="Close customer modal"><i class="ri-close-line"></i></button>
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
    <div class="role-select-card" style="position:relative;">
      <button type="button" class="icon-btn modal-close" id="close-role-modal-btn" onclick="closeLoginModal()" style="position:absolute; top:14px; right:14px; z-index:10; background:rgba(0,0,0,0.2);" aria-label="Close login dialog"><i class="ri-close-line"></i></button>
      <div class="role-select-brand">
        <div class="role-brand-icon" style="width:68px; height:68px; margin:0 auto 12px; border-radius:50%; overflow:hidden; border:2px solid var(--color-primary-light); box-shadow:0 4px 12px rgba(0,0,0,0.4);">
          <img src="./brand_recources/ravenhill_logo.png" alt="Ravenhill Logo" style="width:100%; height:100%; object-fit:cover;">
        </div>
        <h2>RAVENHILL COFFEE</h2>
      </div>
      <div class="role-select-body">
        <h3>Account Login</h3>
        <p style="font-size:13px; color:var(--color-cream-muted); margin-bottom:16px;">Enter your credentials to access your dashboard.</p>
        
        <div class="form-group" style="margin-bottom:16px;">
          <label style="font-weight:600; margin-bottom:6px; display:block;">Username or Email</label>
          <input type="text" id="role-username-input" class="form-input" placeholder="e.g. admin or john@email.com" onkeydown="if(event.key==='Enter'){ document.getElementById('role-password-input').focus(); }" style="width:100%; padding:12px 14px; font-size:15px; border-radius:10px; background:var(--bg-elevated); border:1px solid var(--color-border); color:var(--color-cream);" />
        </div>

        <div class="form-group" style="margin-bottom:16px;">
          <label style="font-weight:600; margin-bottom:6px; display:block;">Password</label>
          <div style="position:relative; display:flex; align-items:center;">
            <input 
              type="password" 
              id="role-password-input" 
              class="form-input" 
              placeholder="Enter your password"
              onkeydown="if(event.key==='Enter'){ confirmRoleSelection(); }"
              style="padding-right:44px; font-size:16px; font-weight:700; border-radius:10px; background:var(--bg-canvas);"
            />
            <button 
              type="button" 
              onclick="togglePasswordVisibility()"
              style="position:absolute; right:12px; background:none; border:none; color:var(--color-cream-muted); cursor:pointer; font-size:20px; display:flex; align-items:center; justify-content:center;"
              title="Toggle password visibility"
            >
              <i class="ri-eye-off-line" id="toggle-pass-icon"></i>
            </button>
          </div>
          <div class="demo-pass-hint" style="font-size:12px; color:var(--color-accent-gold); margin-top:8px; display:flex; align-items:center; gap:6px; background:rgba(217, 119, 6, 0.12); padding:8px 12px; border-radius:8px; border:1px solid rgba(217, 119, 6, 0.3);">
            <i class="ri-information-fill"></i> System defaults: admin, slin, loconnor, hwright (pass: role+123 e.g. admin123)
          </div>
        </div>

        <div id="role-pass-error" class="error-msg hidden" style="color:var(--color-danger); font-size:13px; margin-bottom:16px; padding:8px; background:rgba(239, 68, 68, 0.1); border-radius:6px; border:1px solid rgba(239, 68, 68, 0.2);">
          Invalid username or password.
        </div>

        <button type="button" class="btn btn-primary" id="confirm-role-login-btn" onclick="confirmRoleSelection()" style="width:100%; padding:14px; font-size:16px; justify-content:center; margin-bottom: 12px;">
          <i class="ri-login-circle-line"></i> Secure Login
        </button>
        <button type="button" class="btn btn-outline" onclick="openRegisterCustomerModal()" style="width:100%; padding:14px; font-size:16px; justify-content:center;">
          <i class="ri-user-add-line"></i> Create Customer Account
        </button>
      </div>
    </div>
  </div>

  <!-- Customer Registration Modal -->
  <div class="modal-backdrop hidden" id="register-customer-modal" style="z-index:9999;">
    <div class="role-select-card" style="position:relative; max-width:450px;">
      <button type="button" class="icon-btn modal-close" onclick="closeRegisterCustomerModal()" style="position:absolute; top:14px; right:14px; z-index:10; background:rgba(0,0,0,0.2);" aria-label="Close dialog"><i class="ri-close-line"></i></button>
      <div class="role-select-brand" style="margin-bottom:20px;">
        <h2>Create Account</h2>
        <p>Join Ravenhill Rewards for exclusive perks</p>
      </div>
      <div class="role-select-body">
        <form id="customer-registration-form" onsubmit="handleCustomerRegistration(event)">
          <div class="form-group" style="margin-bottom:12px;">
            <label style="font-weight:600; margin-bottom:6px; display:block;">Full Name *</label>
            <input type="text" id="reg-name" class="form-input" required style="width:100%; padding:10px 14px; background:var(--bg-elevated); border:1px solid var(--color-border);" />
          </div>
          <div class="form-group" style="margin-bottom:12px;">
            <label style="font-weight:600; margin-bottom:6px; display:block;">Email Address *</label>
            <input type="email" id="reg-email" class="form-input" required style="width:100%; padding:10px 14px; background:var(--bg-elevated); border:1px solid var(--color-border);" />
          </div>
          <div class="form-group" style="margin-bottom:12px;">
            <label style="font-weight:600; margin-bottom:6px; display:block;">Phone Number</label>
            <input type="text" id="reg-phone" class="form-input" style="width:100%; padding:10px 14px; background:var(--bg-elevated); border:1px solid var(--color-border);" />
          </div>
          <div class="form-group" style="margin-bottom:12px;">
            <label style="font-weight:600; margin-bottom:6px; display:block;">Password *</label>
            <input type="password" id="reg-password" class="form-input" required style="width:100%; padding:10px 14px; background:var(--bg-elevated); border:1px solid var(--color-border);" />
          </div>
          <div class="form-group" style="margin-bottom:16px;">
            <label style="font-weight:600; margin-bottom:6px; display:block;">Confirm Password *</label>
            <input type="password" id="reg-confirm-password" class="form-input" required style="width:100%; padding:10px 14px; background:var(--bg-elevated); border:1px solid var(--color-border);" />
          </div>
          <div id="reg-error" class="error-msg hidden" style="color:var(--color-danger); font-size:13px; margin-bottom:16px; padding:8px; background:rgba(239, 68, 68, 0.1); border-radius:6px; border:1px solid rgba(239, 68, 68, 0.2);">
          </div>
          <button type="submit" class="btn btn-primary" id="submit-reg-btn" style="width:100%; padding:14px; font-size:16px; justify-content:center;">
            <i class="ri-user-add-line"></i> Create Account
          </button>
        </form>
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
  <script src="app.js?v=<?php echo filemtime(__DIR__ . '/app.js'); ?>"></script>
</body>
</html>
