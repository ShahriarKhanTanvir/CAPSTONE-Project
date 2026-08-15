<?php
/**
 * discounts.php
 * 10. Discount and Promotion Management
 * FR37: Discount creation
 * FR38: Promotion type management
 * FR39: Promotion period management
 * FR40: Promotional code validation
 *
 * Routes:
 *   GET    /api/discounts/discounts.php                    — List all discounts (supports ?active=1, ?type=)
 *   GET    /api/discounts/discounts.php?id=123             — Get single discount details
 *   POST   /api/discounts/discounts.php                    — FR37, FR38, FR39: Create discount with promo type and date period (Admin/Manager)
 *   PUT    /api/discounts/discounts.php?id=123             — Update discount & schedule (Admin/Manager)
 *   DELETE /api/discounts/discounts.php?id=123             — Delete discount (Admin/Manager)
 *   POST   /api/discounts/discounts.php?action=validate    — FR40: Validate promo code against date window, min spend, & calc deduction
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;

// Gracefully ensure extra columns exist in Discounts
try { $db->exec("ALTER TABLE Discounts ADD COLUMN description VARCHAR(255) NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN type VARCHAR(50) DEFAULT 'percentage'"); } catch (Exception $e) {} // 'percentage', 'fixed_amount', 'bogo', 'category_discount'
try { $db->exec("ALTER TABLE Discounts ADD COLUMN fixed_amount FLOAT DEFAULT 0.0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN min_spend FLOAT DEFAULT 0.0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN applicable_category_id INT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN usage_limit INT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN usage_count INT DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Discounts ADD COLUMN is_active BOOLEAN DEFAULT TRUE"); } catch (Exception $e) {}

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET: List or Retrieve Discounts ────────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM Discounts WHERE discount_id = ?");
        $stmt->execute([$id]);
        $discount = $stmt->fetch();

        if (!$discount) {
            sendResponse(false, 'Discount not found.', null, 404);
        }

        $discount['discount_percentage'] = (float)$discount['discount_percentage'];
        $discount['fixed_amount']        = (float)$discount['fixed_amount'];
        $discount['min_spend']           = (float)$discount['min_spend'];
        $discount['is_active']           = (bool)$discount['is_active'];

        sendResponse(true, 'Discount retrieved.', $discount);
    }

    $activeOnly = isset($_GET['active']) ? (int)$_GET['active'] : null;
    $typeFilter = $_GET['type'] ?? null;

    $sql = "SELECT * FROM Discounts WHERE 1=1";
    $params = [];

    if ($activeOnly !== null) {
        $sql .= " AND is_active = ?";
        $params[] = $activeOnly ? 1 : 0;
    }

    if ($typeFilter) {
        $sql .= " AND LOWER(type) = LOWER(?)";
        $params[] = $typeFilter;
    }

    $sql .= " ORDER BY is_active DESC, discount_id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $discounts = $stmt->fetchAll();

    foreach ($discounts as &$d) {
        $d['discount_percentage'] = (float)$d['discount_percentage'];
        $d['fixed_amount']        = (float)$d['fixed_amount'];
        $d['min_spend']           = (float)$d['min_spend'];
        $d['is_active']           = (bool)$d['is_active'];
    }

    sendResponse(true, 'Discounts retrieved.', $discounts);
}

// ── POST: FR37, FR38, FR39 — Discount Creation ─────────────────────────────
if ($method === 'POST' && !$action) {
    requireAuth(['admin', 'manager']);
    $body = getRequestBody();

    if (empty($body['code'])) {
        sendResponse(false, 'Promotion code is required.', null, 422);
    }

    $code        = strtoupper(trim($body['code']));
    $description = $body['description'] ?? '';
    $type        = !empty($body['type']) ? strtolower(trim($body['type'])) : 'percentage';
    $percent     = isset($body['discount_percentage']) ? (float)$body['discount_percentage'] : 0.0;
    $fixedAmt    = isset($body['fixed_amount']) ? (float)$body['fixed_amount'] : 0.0;
    $minSpend    = isset($body['min_spend']) ? (float)$body['min_spend'] : 0.0;
    $startDate   = !empty($body['start_date']) ? $body['start_date'] : date('Y-m-d');
    $endDate     = !empty($body['end_date']) ? $body['end_date'] : date('Y-m-d', strtotime('+1 year'));
    $catId       = !empty($body['applicable_category_id']) ? (int)$body['applicable_category_id'] : null;
    $usageLimit  = !empty($body['usage_limit']) ? (int)$body['usage_limit'] : null;
    $isActive    = isset($body['is_active']) ? ($body['is_active'] ? 1 : 0) : 1;

    // Verify unique code
    $check = $db->prepare("SELECT discount_id FROM Discounts WHERE code = ?");
    $check->execute([$code]);
    if ($check->fetch()) {
        sendResponse(false, "Discount code '$code' already exists.", null, 409);
    }

    $stmt = $db->prepare("
        INSERT INTO Discounts (code, description, type, discount_percentage, fixed_amount, min_spend, start_date, end_date, applicable_category_id, usage_limit, usage_count, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)
    ");
    $stmt->execute([
        $code,
        $description,
        $type,
        $percent,
        $fixedAmt,
        $minSpend,
        $startDate,
        $endDate,
        $catId,
        $usageLimit,
        $isActive
    ]);

    $newId = (int)$db->lastInsertId();

    sendResponse(true, 'Promotion / discount created successfully.', [
        'discount_id'         => $newId,
        'code'                => $code,
        'type'                => $type,
        'discount_percentage' => $percent,
        'fixed_amount'        => $fixedAmt,
        'min_spend'           => $minSpend,
        'start_date'          => $startDate,
        'end_date'            => $endDate,
        'is_active'           => (bool)$isActive
    ], 201);
}

// ── POST ?action=validate: FR40 — Promotional Code Validation ──────────────
if ($method === 'POST' && $action === 'validate') {
    $body = getRequestBody();

    if (empty($body['code'])) {
        sendResponse(false, 'Promotional code is required for validation.', null, 422);
    }

    $code     = strtoupper(trim($body['code']));
    $subtotal = isset($body['subtotal']) ? (float)$body['subtotal'] : 0.0;
    $today    = date('Y-m-d');

    $stmt = $db->prepare("SELECT * FROM Discounts WHERE code = ?");
    $stmt->execute([$code]);
    $promo = $stmt->fetch();

    if (!$promo) {
        sendResponse(false, "Invalid promotional code '$code'.", [
            'is_valid' => false,
            'reason'   => 'Code not recognized.'
        ], 404);
    }

    if (!$promo['is_active']) {
        sendResponse(false, "Promotional code '$code' is currently inactive.", [
            'is_valid' => false,
            'reason'   => 'Promotion disabled by management.'
        ], 400);
    }

    // FR39: Check Promotion Period Window
    if (!empty($promo['start_date']) && $today < $promo['start_date']) {
        sendResponse(false, "Promotion starts on {$promo['start_date']}.", [
            'is_valid' => false,
            'reason'   => 'Promotion has not started yet.'
        ], 400);
    }

    if (!empty($promo['end_date']) && $today > $promo['end_date']) {
        sendResponse(false, "Promotion expired on {$promo['end_date']}.", [
            'is_valid' => false,
            'reason'   => 'Promotion has expired.'
        ], 400);
    }

    // Check Usage Limit
    if ($promo['usage_limit'] !== null && (int)$promo['usage_count'] >= (int)$promo['usage_limit']) {
        sendResponse(false, "Promotional code '$code' has reached its maximum redemption limit.", [
            'is_valid' => false,
            'reason'   => 'Maximum usage limit reached.'
        ], 400);
    }

    // Check Minimum Spend
    $minSpend = (float)$promo['min_spend'];
    if ($subtotal < $minSpend) {
        $short = number_format($minSpend - $subtotal, 2);
        sendResponse(false, "Minimum spend of $" . number_format($minSpend, 2) . " required for this code. Add $$short more to qualify.", [
            'is_valid'  => false,
            'min_spend' => $minSpend,
            'subtotal'  => $subtotal,
            'reason'    => 'Minimum spend threshold not met.'
        ], 422);
    }

    // Calculate Deduction based on Promotion Type (FR38)
    $type = $promo['type'] ?? 'percentage';
    $deduction = 0.0;
    $percent   = (float)$promo['discount_percentage'];
    $fixedAmt  = (float)$promo['fixed_amount'];

    if ($type === 'percentage' || $percent > 0) {
        $deduction = ($subtotal * $percent) / 100.0;
    } elseif ($type === 'fixed_amount' || $fixedAmt > 0) {
        $deduction = min($subtotal, $fixedAmt);
    } elseif ($type === 'bogo') {
        // Flat 50% discount on cart or second item equivalent
        $deduction = $subtotal * 0.25; 
    }

    $deduction = round($deduction, 2);
    $netTotal  = max(0.0, round($subtotal - $deduction, 2));

    sendResponse(true, "Promotion code '$code' applied successfully!", [
        'is_valid'         => true,
        'discount_id'      => (int)$promo['discount_id'],
        'code'             => $promo['code'],
        'description'      => $promo['description'],
        'promo_type'       => $type,
        'subtotal'         => $subtotal,
        'deduction_amount' => $deduction,
        'net_total'        => $netTotal,
        'formatted_saving' => "-$" . number_format($deduction, 2)
    ]);
}

// ── PUT: Update Discount (Admin/Manager) ───────────────────────────────────
if ($method === 'PUT') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Discount ID is required for update.', null, 422);
    }

    $body = getRequestBody();
    $allowed = ['code', 'description', 'type', 'discount_percentage', 'fixed_amount', 'min_spend', 'start_date', 'end_date', 'applicable_category_id', 'usage_limit', 'is_active'];
    $fields = [];
    $values = [];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $fields[] = "$field = ?";
            if ($field === 'discount_percentage' || $field === 'fixed_amount' || $field === 'min_spend') {
                $values[] = (float)$body[$field];
            } elseif ($field === 'is_active') {
                $values[] = $body[$field] ? 1 : 0;
            } elseif ($field === 'usage_limit' || $field === 'applicable_category_id') {
                $values[] = !empty($body[$field]) ? (int)$body[$field] : null;
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

    sendResponse(true, 'Discount updated successfully.');
}

// ── DELETE: Delete Discount (Admin/Manager) ────────────────────────────────
if ($method === 'DELETE') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Discount ID is required for deletion.', null, 422);
    }

    $check = $db->prepare("SELECT discount_id FROM Discounts WHERE discount_id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(false, 'Discount not found.', null, 404);
    }

    $stmt = $db->prepare("DELETE FROM Discounts WHERE discount_id = ?");
    $stmt->execute([$id]);

    sendResponse(true, 'Discount deleted successfully.');
}

sendResponse(false, 'Method not allowed.', null, 405);
