<?php
/**
 * discounts.php
 * 10. Discount and Promotion Management System
 * Comprehensive Admin Promotion Engine & Validation
 *
 * Routes:
 *   GET    /api/discounts/discounts.php                    — List all promotions (supports ?status=, ?type=, ?search=)
 *   GET    /api/discounts/discounts.php?id=123             — Get single promotion
 *   GET    /api/discounts/discounts.php?action=analytics   — Promotion KPI summary
 *   GET    /api/discounts/discounts.php?action=banner      — Active homepage banner promotion
 *   POST   /api/discounts/discounts.php                    — Create promotion (Admin/Manager)
 *   PUT    /api/discounts/discounts.php?id=123             — Update promotion (Admin/Manager)
 *   DELETE /api/discounts/discounts.php?id=123             — Delete promotion (Admin/Manager)
 *   POST   /api/discounts/discounts.php?action=validate    — Validate promo code or automatic discount cart payload
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;

// Ensure extended columns exist
try { $db->exec("ALTER TABLE Discounts ADD COLUMN name VARCHAR(150) NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN description VARCHAR(255) NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN type VARCHAR(50) DEFAULT 'percentage'"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN fixed_amount FLOAT DEFAULT 0.0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN min_spend FLOAT DEFAULT 0.0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN applicable_category_id INT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN usage_limit INT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN usage_count INT DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN is_active BOOLEAN DEFAULT TRUE"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN start_time VARCHAR(10) DEFAULT '00:00:00'"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN end_time VARCHAR(10) DEFAULT '23:59:59'"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN applicable_scope VARCHAR(50) DEFAULT 'all'"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN applicable_product_ids TEXT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN applicable_category_ids TEXT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN is_automatic TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN show_on_banner TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN once_per_customer TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN bogo_buy_qty INT DEFAULT 1"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN bogo_get_qty INT DEFAULT 1"); } catch (Exception $e) {}

/**
 * Compute real-time status of promotion: active, scheduled, expired, disabled
 */
function computeDiscountStatus($d) {
    $now = date('Y-m-d H:i:s');
    $isActive = (bool)($d['is_active'] ?? true);
    if (!$isActive) {
        return 'disabled';
    }
    
    $startDate = !empty($d['start_date']) ? $d['start_date'] : '2000-01-01';
    $startTime = !empty($d['start_time']) ? $d['start_time'] : '00:00:00';
    $startDateTime = trim("$startDate $startTime");

    $endDate = !empty($d['end_date']) ? $d['end_date'] : '2099-12-31';
    $endTime = !empty($d['end_time']) ? $d['end_time'] : '23:59:59';
    $endDateTime = trim("$endDate $endTime");

    if ($now < $startDateTime) {
        return 'scheduled';
    }
    if ($now > $endDateTime) {
        return 'expired';
    }
    if ($d['usage_limit'] !== null && (int)$d['usage_limit'] > 0 && (int)$d['usage_count'] >= (int)$d['usage_limit']) {
        return 'expired';
    }

    return 'active';
}

/**
 * Normalize and format discount object
 */
function formatDiscountRecord(&$d) {
    $d['discount_id']         = (int)$d['discount_id'];
    $d['name']                = !empty($d['name']) ? $d['name'] : (!empty($d['description']) ? $d['description'] : $d['code']);
    $d['description']         = $d['description'] ?? '';
    $d['discount_percentage'] = (float)($d['discount_percentage'] ?? 0);
    $d['fixed_amount']        = (float)($d['fixed_amount'] ?? 0);
    $d['min_spend']           = (float)($d['min_spend'] ?? 0);
    $d['is_active']           = (bool)($d['is_active'] ?? true);
    $d['is_automatic']        = (bool)($d['is_automatic'] ?? false);
    $d['show_on_banner']      = (bool)($d['show_on_banner'] ?? false);
    $d['once_per_customer']   = (bool)($d['once_per_customer'] ?? false);
    $d['applicable_scope']    = !empty($d['applicable_scope']) ? $d['applicable_scope'] : 'all';
    $d['usage_limit']         = $d['usage_limit'] !== null ? (int)$d['usage_limit'] : null;
    $d['usage_count']         = (int)($d['usage_count'] ?? 0);
    $d['start_time']          = !empty($d['start_time']) ? $d['start_time'] : '00:00:00';
    $d['end_time']            = !empty($d['end_time']) ? $d['end_time'] : '23:59:59';
    $d['status']              = computeDiscountStatus($d);

    if (!empty($d['applicable_product_ids']) && is_string($d['applicable_product_ids'])) {
        $d['applicable_product_ids'] = json_decode($d['applicable_product_ids'], true) ?: [];
    } else {
        $d['applicable_product_ids'] = is_array($d['applicable_product_ids'] ?? null) ? $d['applicable_product_ids'] : [];
    }

    if (!empty($d['applicable_category_ids']) && is_string($d['applicable_category_ids'])) {
        $d['applicable_category_ids'] = json_decode($d['applicable_category_ids'], true) ?: [];
    } else {
        $d['applicable_category_ids'] = is_array($d['applicable_category_ids'] ?? null) ? $d['applicable_category_ids'] : [];
    }
}

// ── GET: Promotion Analytics (FR68 / Analytics) ────────────────────────────
if ($method === 'GET' && $action === 'analytics') {
    $allDiscountsStmt = $db->query("SELECT * FROM Discounts");
    $allDiscounts = $allDiscountsStmt->fetchAll(PDO::FETCH_ASSOC);

    $totalCount = count($allDiscounts);
    $activeCount = 0;
    $scheduledCount = 0;
    $expiredCount = 0;
    $disabledCount = 0;

    foreach ($allDiscounts as $d) {
        $st = computeDiscountStatus($d);
        if ($st === 'active') $activeCount++;
        elseif ($st === 'scheduled') $scheduledCount++;
        elseif ($st === 'expired') $expiredCount++;
        elseif ($st === 'disabled') $disabledCount++;
    }

    // Order metrics for promotions
    $orderStats = $db->query("
        SELECT 
            COUNT(CASE WHEN discount_amount > 0 OR discount_code IS NOT NULL THEN 1 END) AS orders_using_promotions,
            COALESCE(SUM(discount_amount), 0) AS total_discount_given,
            COALESCE(SUM(CASE WHEN discount_amount > 0 THEN total_amount ELSE 0 END), 0) AS total_sales_with_promotions
        FROM Orders
    ")->fetch(PDO::FETCH_ASSOC);

    // Top promo
    $topPromoStmt = $db->query("
        SELECT discount_code, COUNT(*) as usage_count, SUM(discount_amount) as total_saved
        FROM Orders
        WHERE discount_code IS NOT NULL AND discount_code != ''
        GROUP BY discount_code
        ORDER BY usage_count DESC
        LIMIT 1
    ");
    $topPromo = $topPromoStmt->fetch(PDO::FETCH_ASSOC);

    $bestPromoName = 'None yet';
    if ($topPromo) {
        $bestPromoName = $topPromo['discount_code'] . " (" . $topPromo['usage_count'] . " orders)";
    } else {
        // Fallback to highest usage in Discounts table
        usort($allDiscounts, function($a, $b) {
            return (int)($b['usage_count'] ?? 0) - (int)($a['usage_count'] ?? 0);
        });
        if (!empty($allDiscounts) && (int)$allDiscounts[0]['usage_count'] > 0) {
            $bestPromoName = ($allDiscounts[0]['name'] ?: $allDiscounts[0]['code']) . " (" . $allDiscounts[0]['usage_count'] . " redemptions)";
        }
    }

    sendResponse(true, 'Promotion analytics retrieved.', [
        'total_promotions'            => $totalCount,
        'active_promotions'           => $activeCount,
        'scheduled_promotions'        => $scheduledCount,
        'expired_promotions'          => $expiredCount,
        'disabled_promotions'         => $disabledCount,
        'total_discount_given'        => (float)($orderStats['total_discount_given'] ?? 0),
        'orders_using_promotions'     => (int)($orderStats['orders_using_promotions'] ?? 0),
        'total_sales_with_promotions' => (float)($orderStats['total_sales_with_promotions'] ?? 0),
        'best_promotion'              => $bestPromoName
    ]);
}

// ── GET: Active Banner Promotion (FR39 / Homepage Integration) ─────────────
if ($method === 'GET' && $action === 'banner') {
    $stmt = $db->query("
        SELECT * FROM Discounts 
        WHERE is_active = 1
        ORDER BY show_on_banner DESC, is_automatic DESC, discount_id DESC
    ");
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $bannerPromo = null;

    foreach ($all as $d) {
        if (computeDiscountStatus($d) === 'active') {
            formatDiscountRecord($d);
            $bannerPromo = $d;
            break;
        }
    }

    sendResponse(true, 'Banner promotion retrieved.', $bannerPromo);
}

// ── GET: List or Retrieve Discounts ────────────────────────────────────────
if ($method === 'GET' && !$action) {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM Discounts WHERE discount_id = ?");
        $stmt->execute([$id]);
        $discount = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$discount) {
            sendResponse(false, 'Discount not found.', null, 404);
        }

        formatDiscountRecord($discount);
        sendResponse(true, 'Discount retrieved.', $discount);
    }

    $statusFilter = $_GET['status'] ?? null;
    $typeFilter   = $_GET['type'] ?? null;
    $searchQuery  = $_GET['search'] ?? null;
    $automatic    = isset($_GET['automatic']) ? (int)$_GET['automatic'] : null;

    $sql = "SELECT * FROM Discounts WHERE 1=1";
    $params = [];

    if ($typeFilter) {
        $sql .= " AND LOWER(type) = LOWER(?)";
        $params[] = $typeFilter;
    }

    if ($automatic !== null) {
        $sql .= " AND is_automatic = ?";
        $params[] = $automatic ? 1 : 0;
    }

    if ($searchQuery) {
        $sql .= " AND (code LIKE ? OR name LIKE ? OR description LIKE ?)";
        $wildcard = "%$searchQuery%";
        $params[] = $wildcard;
        $params[] = $wildcard;
        $params[] = $wildcard;
    }

    $sql .= " ORDER BY is_active DESC, discount_id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $discounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];
    foreach ($discounts as $d) {
        formatDiscountRecord($d);
        if ($statusFilter && strtolower($d['status']) !== strtolower($statusFilter)) {
            continue;
        }
        $formatted[] = $d;
    }

    sendResponse(true, 'Discounts retrieved.', $formatted);
}

// ── POST: Create Promotion ─────────────────────────────────────────────────
if ($method === 'POST' && !$action) {
    requireAuth(['admin', 'manager']);
    $body = getRequestBody();

    $name        = trim($body['name'] ?? '');
    $isAutomatic = !empty($body['is_automatic']) ? 1 : 0;
    $code        = !empty($body['code']) ? strtoupper(trim($body['code'])) : '';

    if (empty($code)) {
        if ($isAutomatic) {
            // Generate system code for automatic promo
            $code = 'AUTO_' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name ?: 'PROMO'), 0, 10)) . '_' . rand(100, 999);
        } else {
            sendResponse(false, 'Promotional code is required for non-automatic promotions.', null, 422);
        }
    }

    if (empty($name)) {
        $name = $code;
    }

    $description   = $body['description'] ?? '';
    $type          = !empty($body['type']) ? strtolower(trim($body['type'])) : 'percentage';
    $percent       = isset($body['discount_percentage']) ? (float)$body['discount_percentage'] : 0.0;
    $fixedAmt      = isset($body['fixed_amount']) ? (float)$body['fixed_amount'] : 0.0;
    $minSpend      = isset($body['min_spend']) ? (float)$body['min_spend'] : 0.0;
    $startDate     = !empty($body['start_date']) ? $body['start_date'] : date('Y-m-d');
    $startTime     = !empty($body['start_time']) ? $body['start_time'] : '00:00:00';
    $endDate       = !empty($body['end_date']) ? $body['end_date'] : date('Y-m-d', strtotime('+1 year'));
    $endTime       = !empty($body['end_time']) ? $body['end_time'] : '23:59:59';
    $catId         = !empty($body['applicable_category_id']) ? (int)$body['applicable_category_id'] : null;
    $scope         = !empty($body['applicable_scope']) ? $body['applicable_scope'] : 'all';
    $productIds    = !empty($body['applicable_product_ids']) ? (is_array($body['applicable_product_ids']) ? json_encode($body['applicable_product_ids']) : $body['applicable_product_ids']) : null;
    $categoryIds   = !empty($body['applicable_category_ids']) ? (is_array($body['applicable_category_ids']) ? json_encode($body['applicable_category_ids']) : $body['applicable_category_ids']) : null;
    $usageLimit    = isset($body['usage_limit']) && $body['usage_limit'] !== '' && $body['usage_limit'] !== null ? (int)$body['usage_limit'] : null;
    $isActive      = isset($body['is_active']) ? ($body['is_active'] ? 1 : 0) : 1;
    $showOnBanner  = !empty($body['show_on_banner']) ? 1 : 0;
    $onceCustomer  = !empty($body['once_per_customer']) ? 1 : 0;
    $bogoBuy       = isset($body['bogo_buy_qty']) ? (int)$body['bogo_buy_qty'] : 1;
    $bogoGet       = isset($body['bogo_get_qty']) ? (int)$body['bogo_get_qty'] : 1;

    // Check unique code
    $check = $db->prepare("SELECT discount_id FROM Discounts WHERE code = ?");
    $check->execute([$code]);
    if ($check->fetch()) {
        sendResponse(false, "Promotion code '$code' already exists.", null, 409);
    }

    $stmt = $db->prepare("
        INSERT INTO Discounts (
            code, name, description, type, discount_percentage, fixed_amount, min_spend,
            start_date, start_time, end_date, end_time, applicable_scope, applicable_product_ids,
            applicable_category_ids, applicable_category_id, usage_limit, usage_count, is_active,
            is_automatic, show_on_banner, once_per_customer, bogo_buy_qty, bogo_get_qty
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $code, $name, $description, $type, $percent, $fixedAmt, $minSpend,
        $startDate, $startTime, $endDate, $endTime, $scope, $productIds,
        $categoryIds, $catId, $usageLimit, $isActive,
        $isAutomatic, $showOnBanner, $onceCustomer, $bogoBuy, $bogoGet
    ]);

    $newId = (int)$db->lastInsertId();

    $stmt = $db->prepare("SELECT * FROM Discounts WHERE discount_id = ?");
    $stmt->execute([$newId]);
    $created = $stmt->fetch(PDO::FETCH_ASSOC);
    formatDiscountRecord($created);

    sendResponse(true, 'Promotion created successfully.', $created, 201);
}

// ── PUT: Update Promotion ──────────────────────────────────────────────────
if ($method === 'PUT') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Discount ID is required for update.', null, 422);
    }

    $body = getRequestBody();
    $allowed = [
        'code', 'name', 'description', 'type', 'discount_percentage', 'fixed_amount',
        'min_spend', 'start_date', 'start_time', 'end_date', 'end_time',
        'applicable_scope', 'applicable_product_ids', 'applicable_category_ids',
        'applicable_category_id', 'usage_limit', 'is_active', 'is_automatic',
        'show_on_banner', 'once_per_customer', 'bogo_buy_qty', 'bogo_get_qty'
    ];

    $fields = [];
    $values = [];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $fields[] = "$field = ?";
            if (in_array($field, ['discount_percentage', 'fixed_amount', 'min_spend'])) {
                $values[] = (float)$body[$field];
            } elseif (in_array($field, ['is_active', 'is_automatic', 'show_on_banner', 'once_per_customer'])) {
                $values[] = $body[$field] ? 1 : 0;
            } elseif (in_array($field, ['usage_limit', 'applicable_category_id', 'bogo_buy_qty', 'bogo_get_qty'])) {
                $values[] = ($body[$field] !== null && $body[$field] !== '') ? (int)$body[$field] : null;
            } elseif (in_array($field, ['applicable_product_ids', 'applicable_category_ids'])) {
                $values[] = is_array($body[$field]) ? json_encode($body[$field]) : $body[$field];
            } else {
                $values[] = $body[$field];
            }
        }
    }

    if (empty($fields)) {
        sendResponse(false, 'No valid fields provided for update.', null, 422);
    }

    $check = $db->prepare("SELECT discount_id FROM Discounts WHERE discount_id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(false, 'Discount not found.', null, 404);
    }

    $values[] = $id;
    $db->prepare("UPDATE Discounts SET " . implode(', ', $fields) . " WHERE discount_id = ?")
       ->execute($values);

    $stmt = $db->prepare("SELECT * FROM Discounts WHERE discount_id = ?");
    $stmt->execute([$id]);
    $updated = $stmt->fetch(PDO::FETCH_ASSOC);
    formatDiscountRecord($updated);

    sendResponse(true, 'Promotion updated successfully.', $updated);
}

// ── DELETE: Delete Promotion ───────────────────────────────────────────────
if ($method === 'DELETE') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Discount ID is required for deletion.', null, 422);
    }

    $check = $db->prepare("SELECT discount_id, code FROM Discounts WHERE discount_id = ?");
    $check->execute([$id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        sendResponse(false, 'Discount not found.', null, 404);
    }

    $stmt = $db->prepare("DELETE FROM Discounts WHERE discount_id = ?");
    $stmt->execute([$id]);

    sendResponse(true, "Promotion '{$existing['code']}' deleted successfully.");
}

// ── POST ?action=validate: Promo Code / Cart Promotion Validation ──────────
if ($method === 'POST' && $action === 'validate') {
    $body = getRequestBody();

    $code     = !empty($body['code']) ? strtoupper(trim($body['code'])) : null;
    $items    = is_array($body['items'] ?? null) ? $body['items'] : [];
    $subtotal = isset($body['subtotal']) ? (float)$body['subtotal'] : 0.0;
    $now      = date('Y-m-d H:i:s');

    // Calculate subtotal from items if not provided or to verify
    if (empty($subtotal) && !empty($items)) {
        foreach ($items as $it) {
            $price = (float)($it['price'] ?? $it['unit_price'] ?? 0);
            $qty   = max(1, (int)($it['qty'] ?? $it['quantity'] ?? 1));
            $subtotal += ($price * $qty);
        }
    }

    $promo = null;

    if ($code) {
        $stmt = $db->prepare("SELECT * FROM Discounts WHERE UPPER(code) = ?");
        $stmt->execute([$code]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$promo) {
            sendResponse(false, "Promotional code '$code' is not valid.", [
                'is_valid' => false,
                'reason'   => 'Code not recognized.'
            ], 404);
        }
    } else {
        // Automatic promotion evaluation for the cart
        $stmt = $db->query("
            SELECT * FROM Discounts 
            WHERE is_active = 1 AND is_automatic = 1
            ORDER BY discount_percentage DESC, fixed_amount DESC, discount_id DESC
        ");
        $autoPromos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($autoPromos as $candidate) {
            $status = computeDiscountStatus($candidate);
            if ($status !== 'active') continue;

            $minSpend = (float)$candidate['min_spend'];
            if ($subtotal < $minSpend) continue;

            // Check eligible items
            $scope = $candidate['applicable_scope'] ?? 'all';
            if ($scope === 'all') {
                $promo = $candidate;
                break;
            } elseif ($scope === 'categories' || !empty($candidate['applicable_category_id'])) {
                $catIds = !empty($candidate['applicable_category_ids']) ? (is_array($candidate['applicable_category_ids']) ? $candidate['applicable_category_ids'] : json_decode($candidate['applicable_category_ids'], true)) : [];
                if ($candidate['applicable_category_id']) $catIds[] = (int)$candidate['applicable_category_id'];
                
                $hasEligible = false;
                foreach ($items as $it) {
                    $itemCat = (int)($it['category_id'] ?? $it['catId'] ?? 0);
                    if (in_array($itemCat, $catIds)) {
                        $hasEligible = true;
                        break;
                    }
                }
                if ($hasEligible) {
                    $promo = $candidate;
                    break;
                }
            } elseif ($scope === 'products') {
                $prodIds = !empty($candidate['applicable_product_ids']) ? (is_array($candidate['applicable_product_ids']) ? $candidate['applicable_product_ids'] : json_decode($candidate['applicable_product_ids'], true)) : [];
                $hasEligible = false;
                foreach ($items as $it) {
                    $itId = (int)($it['product_id'] ?? $it['id'] ?? 0);
                    if (in_array($itId, $prodIds)) {
                        $hasEligible = true;
                        break;
                    }
                }
                if ($hasEligible) {
                    $promo = $candidate;
                    break;
                }
            }
        }

        if (!$promo) {
            sendResponse(true, 'No automatic promotion applicable to cart.', [
                'is_valid'         => false,
                'deduction_amount' => 0.0,
                'subtotal'         => $subtotal,
                'net_total'        => $subtotal
            ]);
        }
    }

    formatDiscountRecord($promo);

    // Validate Status
    if ($promo['status'] === 'disabled') {
        sendResponse(false, "Promotional offer '{$promo['code']}' is currently disabled.", [
            'is_valid' => false,
            'reason'   => 'Promotion disabled by management.'
        ], 400);
    }
    if ($promo['status'] === 'scheduled') {
        sendResponse(false, "Promotion starts on {$promo['start_date']} at {$promo['start_time']}.", [
            'is_valid' => false,
            'reason'   => 'Promotion has not started yet.'
        ], 400);
    }
    if ($promo['status'] === 'expired') {
        sendResponse(false, "Promotion has expired or reached maximum redemption limit.", [
            'is_valid' => false,
            'reason'   => 'Promotion expired.'
        ], 400);
    }

    // Minimum spend check
    $minSpend = (float)$promo['min_spend'];
    if ($subtotal < $minSpend) {
        $short = number_format($minSpend - $subtotal, 2);
        sendResponse(false, "Minimum spend of $" . number_format($minSpend, 2) . " required. Add $$short more to qualify.", [
            'is_valid'  => false,
            'min_spend' => $minSpend,
            'subtotal'  => $subtotal,
            'reason'    => 'Minimum spend threshold not met.'
        ], 422);
    }

    // Scope Item Matching & Deduction Calculation
    $deduction = 0.0;
    $type      = $promo['type'] ?? 'percentage';
    $percent   = (float)$promo['discount_percentage'];
    $fixedAmt  = (float)$promo['fixed_amount'];
    $scope     = $promo['applicable_scope'] ?? 'all';

    // Calculate eligible subtotal
    $eligibleSubtotal = 0.0;
    $prodIds = !empty($promo['applicable_product_ids']) ? (array)$promo['applicable_product_ids'] : [];
    $catIds  = !empty($promo['applicable_category_ids']) ? (array)$promo['applicable_category_ids'] : [];
    if (!empty($promo['applicable_category_id'])) $catIds[] = (int)$promo['applicable_category_id'];

    if ($scope === 'all' || empty($items)) {
        $eligibleSubtotal = $subtotal;
    } else {
        foreach ($items as $it) {
            $itId    = (int)($it['product_id'] ?? $it['id'] ?? 0);
            $itemCat = (int)($it['category_id'] ?? $it['catId'] ?? 0);
            $pPrice  = (float)($it['price'] ?? $it['unit_price'] ?? 0);
            $pQty    = max(1, (int)($it['qty'] ?? $it['quantity'] ?? 1));
            $lineTot = $pPrice * $pQty;

            if ($scope === 'products' && in_array($itId, $prodIds)) {
                $eligibleSubtotal += $lineTot;
            } elseif ($scope === 'categories' && in_array($itemCat, $catIds)) {
                $eligibleSubtotal += $lineTot;
            }
        }

        if ($eligibleSubtotal <= 0) {
            sendResponse(false, "Promotion is only valid on selected products or categories.", [
                'is_valid' => false,
                'reason'   => 'No eligible items in cart.'
            ], 422);
        }
    }

    // Compute Deduction
    if ($type === 'percentage' || $percent > 0) {
        $deduction = ($eligibleSubtotal * $percent) / 100.0;
    } elseif ($type === 'fixed_amount' || $fixedAmt > 0) {
        $deduction = min($eligibleSubtotal, $fixedAmt);
    } elseif ($type === 'bogo' || $type === 'buy_x_get_y') {
        // Flat 50% off second item or 25% on eligible items
        $deduction = $eligibleSubtotal * 0.25;
    } elseif ($type === 'free_item') {
        // Deduct cheapest eligible item
        $minItemPrice = 9999.0;
        foreach ($items as $it) {
            $pPrice = (float)($it['price'] ?? $it['unit_price'] ?? 0);
            if ($pPrice > 0 && $pPrice < $minItemPrice) {
                $minItemPrice = $pPrice;
            }
        }
        $deduction = ($minItemPrice < 9999.0) ? $minItemPrice : 5.0;
    } elseif ($type === 'min_order') {
        $deduction = ($percent > 0) ? (($subtotal * $percent) / 100.0) : min($subtotal, $fixedAmt);
    }

    $deduction = round(min($subtotal, max(0.0, $deduction)), 2);
    $netTotal  = max(0.0, round($subtotal - $deduction, 2));

    sendResponse(true, "Promotion applied successfully!", [
        'is_valid'         => true,
        'discount_id'      => (int)$promo['discount_id'],
        'code'             => $promo['code'],
        'name'             => $promo['name'],
        'description'      => $promo['description'],
        'promo_type'       => $type,
        'is_automatic'     => (bool)$promo['is_automatic'],
        'subtotal'         => $subtotal,
        'deduction_amount' => $deduction,
        'net_total'        => $netTotal,
        'formatted_saving' => "-$" . number_format($deduction, 2)
    ]);
}

sendResponse(false, 'Method not allowed.', null, 405);
