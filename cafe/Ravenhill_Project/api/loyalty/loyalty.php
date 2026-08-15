<?php
/**
 * loyalty.php
 * 11. Loyalty Program Management
 * FR41: Loyalty account registration
 * FR42: Loyalty point allocation
 * FR43: Loyalty point redemption
 * FR44: Loyalty transaction history
 *
 * Routes:
 *   POST /api/loyalty/loyalty.php?action=register — FR41: Enroll customer & award welcome bonus points
 *   POST /api/loyalty/loyalty.php?action=allocate — FR42: Allocate points for purchase (10 pts per $1 spent)
 *   POST /api/loyalty/loyalty.php?action=redeem   — FR43: Redeem points for order discount ($1 per 100 pts)
 *   GET  /api/loyalty/loyalty.php?customer_id=123 — FR44: View customer loyalty balance & transaction history
 *   GET  /api/loyalty/loyalty.php                 — View loyalty members directory / leaderboard
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$custId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;

// Gracefully ensure extra columns exist in Customers & LoyaltyTransactions
try { $db->exec("ALTER TABLE Customers ADD COLUMN loyalty_tier VARCHAR(50) DEFAULT 'Bronze'"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Customers ADD COLUMN joined_loyalty_at DATETIME DEFAULT CURRENT_TIMESTAMP"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE LoyaltyTransactions ADD COLUMN description VARCHAR(255) NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE LoyaltyTransactions ADD COLUMN balance_after INT DEFAULT 0"); } catch (Exception $e) {}

// Tier calculation helper
function calculateTier($totalPoints) {
    if ($totalPoints >= 2000) return 'Platinum';
    if ($totalPoints >= 1000) return 'Gold';
    if ($totalPoints >= 500)  return 'Silver';
    return 'Bronze';
}

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── POST ?action=register: FR41 — Loyalty Account Registration ─────────────
if ($method === 'POST' && $action === 'register') {
    $body = getRequestBody();

    $customerId = !empty($body['customer_id']) ? (int)$body['customer_id'] : null;
    $firstName  = $body['first_name'] ?? null;
    $lastName   = $body['last_name'] ?? null;
    $email      = $body['email'] ?? null;
    $phone      = $body['phone'] ?? null;
    $welcomeBonus = isset($body['welcome_bonus']) ? (int)$body['welcome_bonus'] : 50; // 50 free welcome points

    $db->beginTransaction();

    try {
        if ($customerId) {
            // Upgrade existing customer
            $stmt = $db->prepare("SELECT * FROM Customers WHERE customer_id = ?");
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch();

            if (!$customer) {
                throw new Exception('Customer not found.');
            }

            $newBalance = (int)$customer['loyalty_points'] + $welcomeBonus;
            $tier = calculateTier($newBalance);

            $db->prepare("
                UPDATE Customers 
                SET loyalty_points = ?, loyalty_tier = ?, joined_loyalty_at = NOW() 
                WHERE customer_id = ?
            ")->execute([$newBalance, $tier, $customerId]);

        } else {
            // Register new customer with loyalty
            if (empty($firstName) || empty($phone)) {
                throw new Exception('first_name and phone are required for new registration.');
            }

            if (!empty($email)) {
                $check = $db->prepare("SELECT customer_id FROM Customers WHERE email = ?");
                $check->execute([$email]);
                if ($check->fetch()) {
                    throw new Exception('A customer with this email already exists.');
                }
            }

            $tier = calculateTier($welcomeBonus);
            $stmt = $db->prepare("
                INSERT INTO Customers (first_name, last_name, phone, email, loyalty_points, loyalty_tier, joined_loyalty_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$firstName, $lastName, $phone, $email, $welcomeBonus, $tier]);
            $customerId = (int)$db->lastInsertId();
            $newBalance = $welcomeBonus;
        }

        // Record Welcome Bonus Transaction
        if ($welcomeBonus > 0) {
            $db->prepare("
                INSERT INTO LoyaltyTransactions (customer_id, order_id, points_earned, points_redeemed, description, balance_after, transaction_date)
                VALUES (?, NULL, ?, 0, 'Welcome to Ravenhill Rewards Bonus', ?, NOW())
            ")->execute([$customerId, $welcomeBonus, $newBalance]);
        }

        $db->commit();

        sendResponse(true, 'Enrolled in Ravenhill Loyalty Program successfully!', [
            'customer_id'    => $customerId,
            'loyalty_points' => $newBalance,
            'loyalty_tier'   => $tier,
            'welcome_bonus'  => $welcomeBonus
        ], 201);

    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(false, 'Loyalty registration failed: ' . $e->getMessage(), null, 400);
    }
}

// ── POST ?action=allocate: FR42 — Loyalty Point Allocation ─────────────────
if ($method === 'POST' && $action === 'allocate') {
    $body = getRequestBody();

    if (empty($body['customer_id'])) {
        sendResponse(false, 'customer_id is required for point allocation.', null, 422);
    }

    $customerId  = (int)$body['customer_id'];
    $spendAmount = isset($body['spend_amount']) ? (float)$body['spend_amount'] : 0.0;
    $orderId     = !empty($body['order_id']) ? (int)$body['order_id'] : null;
    $rate        = isset($body['rate']) ? (int)$body['rate'] : 10; // Default 10 points per $1 AUD

    // Explicit points or calculated from spend
    $pointsToEarn = isset($body['points']) ? (int)$body['points'] : (int)floor($spendAmount * $rate);

    if ($pointsToEarn <= 0) {
        sendResponse(false, 'Points to allocate must be greater than 0.', null, 422);
    }

    $stmt = $db->prepare("SELECT loyalty_points, first_name FROM Customers WHERE customer_id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();

    if (!$customer) {
        sendResponse(false, 'Customer not found.', null, 404);
    }

    $db->beginTransaction();

    try {
        $newBalance = (int)$customer['loyalty_points'] + $pointsToEarn;
        $tier = calculateTier($newBalance);

        $db->prepare("UPDATE Customers SET loyalty_points = ?, loyalty_tier = ? WHERE customer_id = ?")
           ->execute([$newBalance, $tier, $customerId]);

        $desc = $orderId ? "Earned from Order #$orderId ($$spendAmount)" : "Loyalty point adjustment";
        $db->prepare("
            INSERT INTO LoyaltyTransactions (customer_id, order_id, points_earned, points_redeemed, description, balance_after, transaction_date)
            VALUES (?, ?, ?, 0, ?, ?, NOW())
        ")->execute([$customerId, $orderId, $pointsToEarn, $desc, $newBalance]);

        $db->commit();

        sendResponse(true, "Allocated $pointsToEarn points to {$customer['first_name']}.", [
            'customer_id'   => $customerId,
            'points_earned' => $pointsToEarn,
            'new_balance'   => $newBalance,
            'tier'          => $tier
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(false, 'Failed to allocate points: ' . $e->getMessage(), null, 400);
    }
}

// ── POST ?action=redeem: FR43 — Loyalty Point Redemption ───────────────────
if ($method === 'POST' && $action === 'redeem') {
    $body = getRequestBody();

    if (empty($body['customer_id'])) {
        sendResponse(false, 'customer_id is required for point redemption.', null, 422);
    }

    $customerId     = (int)$body['customer_id'];
    $pointsToRedeem = isset($body['points']) ? (int)$body['points'] : 0;
    $orderId        = !empty($body['order_id']) ? (int)$body['order_id'] : null;

    if ($pointsToRedeem < 100) {
        sendResponse(false, 'Minimum redemption is 100 points ($1.00 discount).', null, 422);
    }

    $stmt = $db->prepare("SELECT loyalty_points, first_name FROM Customers WHERE customer_id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();

    if (!$customer) {
        sendResponse(false, 'Customer not found.', null, 404);
    }

    $currentBalance = (int)$customer['loyalty_points'];
    if ($currentBalance < $pointsToRedeem) {
        sendResponse(false, "Insufficient balance. Available: $currentBalance points, Requested: $pointsToRedeem points.", [
            'available_points' => $currentBalance,
            'requested_points' => $pointsToRedeem
        ], 422);
    }

    // Conversion rate: 100 points = $1.00 AUD discount
    $dollarDiscount = round($pointsToRedeem / 100.0, 2);

    $db->beginTransaction();

    try {
        $newBalance = $currentBalance - $pointsToRedeem;
        $tier = calculateTier($newBalance);

        $db->prepare("UPDATE Customers SET loyalty_points = ?, loyalty_tier = ? WHERE customer_id = ?")
           ->execute([$newBalance, $tier, $customerId]);

        $desc = $orderId ? "Redeemed for $$dollarDiscount discount on Order #$orderId" : "Redeemed for $$dollarDiscount reward";
        $db->prepare("
            INSERT INTO LoyaltyTransactions (customer_id, order_id, points_earned, points_redeemed, description, balance_after, transaction_date)
            VALUES (?, ?, 0, ?, ?, ?, NOW())
        ")->execute([$customerId, $orderId, $pointsToRedeem, $desc, $newBalance]);

        $db->commit();

        sendResponse(true, "Redeemed $pointsToRedeem points for $$dollarDiscount off.", [
            'customer_id'     => $customerId,
            'points_redeemed' => $pointsToRedeem,
            'discount_value'  => $dollarDiscount,
            'remaining_points'=> $newBalance,
            'tier'            => $tier
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(false, 'Failed to redeem points: ' . $e->getMessage(), null, 400);
    }
}

// ── GET: FR44 — Customer Loyalty History & Balance ─────────────────────────
if ($method === 'GET' && $custId) {
    $cStmt = $db->prepare("SELECT * FROM Customers WHERE customer_id = ?");
    $cStmt->execute([$custId]);
    $customer = $cStmt->fetch();

    if (!$customer) {
        sendResponse(false, 'Customer not found.', null, 404);
    }

    // Fetch history
    $tStmt = $db->prepare("
        SELECT * FROM LoyaltyTransactions
        WHERE customer_id = ?
        ORDER BY transaction_date DESC
    ");
    $tStmt->execute([$custId]);
    $history = $tStmt->fetchAll();

    $points = (int)$customer['loyalty_points'];
    $tier   = $customer['loyalty_tier'] ?? calculateTier($points);

    // Next tier calculation
    $tierThresholds = ['Bronze' => 500, 'Silver' => 1000, 'Gold' => 2000, 'Platinum' => 5000];
    $nextTierTarget = $tierThresholds[$tier] ?? 5000;
    $pointsToNextTier = max(0, $nextTierTarget - $points);

    sendResponse(true, 'Loyalty history retrieved.', [
        'customer' => [
            'customer_id'       => (int)$customer['customer_id'],
            'name'              => $customer['first_name'] . ' ' . ($customer['last_name'] ?? ''),
            'email'             => $customer['email'],
            'phone'             => $customer['phone'],
            'loyalty_points'    => $points,
            'redeemable_value'  => '$' . number_format($points / 100.0, 2),
            'loyalty_tier'      => $tier,
            'points_to_next'    => $pointsToNextTier,
            'joined_loyalty_at' => $customer['joined_loyalty_at']
        ],
        'total_transactions' => count($history),
        'transactions'       => $history
    ]);
}

// ── GET: Loyalty Members Directory / Leaderboard ───────────────────────────
if ($method === 'GET' && !$custId) {
    $search = $_GET['search'] ?? '';
    $sql = "
        SELECT customer_id, first_name, last_name, email, phone, loyalty_points, loyalty_tier, joined_loyalty_at
        FROM Customers
        WHERE 1=1
    ";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $like = '%' . $search . '%';
        $params = [$like, $like, $like, $like];
    }

    $sql .= " ORDER BY loyalty_points DESC LIMIT 50";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $members = $stmt->fetchAll();

    foreach ($members as &$m) {
        $m['loyalty_points']   = (int)$m['loyalty_points'];
        $m['redeemable_value'] = '$' . number_format($m['loyalty_points'] / 100.0, 2);
    }

    sendResponse(true, 'Loyalty members leaderboard retrieved.', [
        'count'   => count($members),
        'members' => $members
    ]);
}

sendResponse(false, 'Method not allowed.', null, 405);
