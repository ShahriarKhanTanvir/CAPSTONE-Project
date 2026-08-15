<?php
/**
 * feedback.php
 * 16. Customer Feedback Management
 * FR61: Customer feedback submission
 * FR62: Order-based feedback linking
 * FR63: Feedback response management
 * FR64: Customer rating calculation
 *
 * Routes:
 *   POST /api/feedback/feedback.php                  — FR61 & FR62: Submit feedback & link to order/customer
 *   GET  /api/feedback/feedback.php?action=ratings   — FR64: Calculate aggregate rating metrics & breakdown
 *   POST /api/feedback/feedback.php?id=123&action=respond — FR63: Respond to customer review (Admin/Manager)
 *   GET  /api/feedback/feedback.php                  — List all feedback (supports ?rating=, ?category=, ?order_id=)
 *   GET  /api/feedback/feedback.php?id=123           — Get single feedback with order items
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;

// Gracefully ensure extra columns exist in Feedback
try { $db->exec("ALTER TABLE Feedback ADD COLUMN guest_name VARCHAR(255) NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Feedback ADD COLUMN category VARCHAR(100) DEFAULT 'Overall Experience'"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Feedback ADD COLUMN response_text TEXT NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Feedback ADD COLUMN responded_by VARCHAR(100) NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Feedback ADD COLUMN responded_at DATETIME NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Feedback ADD COLUMN status VARCHAR(50) DEFAULT 'new'"); } catch (Exception $e) {}

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── GET ?action=ratings: FR64 — Customer Rating Calculation ────────────────
if ($method === 'GET' && $action === 'ratings') {
    // Total count & average
    $agg = $db->query("
        SELECT COUNT(*) AS total_reviews,
               ROUND(AVG(rating), 2) AS average_rating,
               SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) AS five_star,
               SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) AS four_star,
               SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) AS three_star,
               SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) AS two_star,
               SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) AS one_star
        FROM Feedback
    ")->fetch();

    $total = (int)$agg['total_reviews'];
    $avg   = (float)$agg['average_rating'];

    // Star distributions
    $distribution = [
        '5_star' => ['count' => (int)$agg['five_star'],  'percent' => $total > 0 ? round(((int)$agg['five_star'] / $total) * 100, 1) : 0],
        '4_star' => ['count' => (int)$agg['four_star'],  'percent' => $total > 0 ? round(((int)$agg['four_star'] / $total) * 100, 1) : 0],
        '3_star' => ['count' => (int)$agg['three_star'], 'percent' => $total > 0 ? round(((int)$agg['three_star'] / $total) * 100, 1) : 0],
        '2_star' => ['count' => (int)$agg['two_star'],   'percent' => $total > 0 ? round(((int)$agg['two_star'] / $total) * 100, 1) : 0],
        '1_star' => ['count' => (int)$agg['one_star'],   'percent' => $total > 0 ? round(((int)$agg['one_star'] / $total) * 100, 1) : 0]
    ];

    // Category ratings
    $catQuery = $db->query("
        SELECT category, COUNT(*) AS count, ROUND(AVG(rating), 2) AS category_avg
        FROM Feedback
        GROUP BY category
        ORDER BY count DESC
    ")->fetchAll();

    sendResponse(true, 'Customer ratings calculated.', [
        'total_reviews'       => $total,
        'overall_rating'      => $avg,
        'satisfaction_score'  => $total > 0 ? round((((int)$agg['five_star'] + (int)$agg['four_star']) / $total) * 100, 1) . '%' : '0%',
        'rating_distribution' => $distribution,
        'category_averages'   => $catQuery
    ]);
}

// ── GET: List or Retrieve Feedback ─────────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("
            SELECT f.*, 
                   CONCAT(c.first_name, ' ', COALESCE(c.last_name, '')) AS customer_name,
                   c.email AS customer_email,
                   o.total_amount, o.order_type, o.created_at AS order_created_at
            FROM Feedback f
            LEFT JOIN Customers c ON f.customer_id = c.customer_id
            LEFT JOIN Orders o ON f.order_id = o.order_id
            WHERE f.feedback_id = ?
        ");
        $stmt->execute([$id]);
        $fb = $stmt->fetch();

        if (!$fb) {
            sendResponse(false, 'Feedback review not found.', null, 404);
        }

        // Fetch order items if linked
        if (!empty($fb['order_id'])) {
            $iStmt = $db->prepare("
                SELECT oi.quantity, oi.unit_price, p.product_name
                FROM OrderItems oi
                LEFT JOIN Products p ON oi.product_id = p.product_id
                WHERE oi.order_id = ?
            ");
            $iStmt->execute([$fb['order_id']]);
            $fb['order_items'] = $iStmt->fetchAll();
        }

        sendResponse(true, 'Feedback details retrieved.', $fb);
    }

    $ratingFilter = isset($_GET['rating']) ? (int)$_GET['rating'] : null;
    $orderFilter  = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
    $catFilter    = $_GET['category'] ?? null;

    $sql = "
        SELECT f.*, 
               COALESCE(CONCAT(c.first_name, ' ', COALESCE(c.last_name, '')), f.guest_name, 'Guest') AS author_name,
               o.total_amount AS order_total, o.order_type
        FROM Feedback f
        LEFT JOIN Customers c ON f.customer_id = c.customer_id
        LEFT JOIN Orders o ON f.order_id = o.order_id
        WHERE 1=1
    ";
    $params = [];

    if ($ratingFilter) {
        $sql .= " AND f.rating = ?";
        $params[] = $ratingFilter;
    }

    if ($orderFilter) {
        $sql .= " AND f.order_id = ?";
        $params[] = $orderFilter;
    }

    if ($catFilter) {
        $sql .= " AND LOWER(f.category) = LOWER(?)";
        $params[] = $catFilter;
    }

    $sql .= " ORDER BY f.submitted_at DESC LIMIT 50";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $feedbacks = $stmt->fetchAll();

    foreach ($feedbacks as &$f) {
        $f['rating'] = (int)$f['rating'];
    }

    sendResponse(true, 'Customer feedback reviews retrieved.', [
        'count'    => count($feedbacks),
        'feedback' => $feedbacks
    ]);
}

// ── POST: FR61 & FR62 — Feedback Submission & Order Linking ────────────────
if ($method === 'POST' && !$action) {
    $body = getRequestBody();

    $rating = isset($body['rating']) ? (int)$body['rating'] : 0;
    if ($rating < 1 || $rating > 5) {
        sendResponse(false, 'Rating must be an integer between 1 and 5 stars.', null, 422);
    }

    $comments   = $body['comments'] ?? '';
    $orderId    = !empty($body['order_id']) ? (int)$body['order_id'] : null;
    $customerId = !empty($body['customer_id']) ? (int)$body['customer_id'] : null;
    $guestName  = !empty($body['guest_name']) ? trim($body['guest_name']) : null;
    $category   = !empty($body['category']) ? trim($body['category']) : 'Overall Experience';

    // FR62: If order_id provided, attempt auto-resolution of customer_id
    if ($orderId && !$customerId) {
        $oStmt = $db->prepare("SELECT customer_id FROM Orders WHERE order_id = ?");
        $oStmt->execute([$orderId]);
        $oRow = $oStmt->fetch();
        if ($oRow && !empty($oRow['customer_id'])) {
            $customerId = (int)$oRow['customer_id'];
        }
    }

    $stmt = $db->prepare("
        INSERT INTO Feedback (customer_id, order_id, rating, comments, category, guest_name, submitted_at, status)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), 'new')
    ");
    $stmt->execute([$customerId, $orderId, $rating, $comments, $category, $guestName]);
    $newId = (int)$db->lastInsertId();

    sendResponse(true, 'Thank you for your feedback! Your review has been recorded.', [
        'feedback_id'  => $newId,
        'rating'       => $rating,
        'category'     => $category,
        'order_id'     => $orderId,
        'customer_id'  => $customerId,
        'submitted_at' => date('Y-m-d H:i:s')
    ], 201);
}

// ── POST ?action=respond: FR63 — Feedback Response Management ──────────────
if ($method === 'POST' && $action === 'respond') {
    requireAuth(['admin', 'manager']);

    if (!$id) {
        sendResponse(false, 'Feedback ID is required to submit a response.', null, 422);
    }

    $body = getRequestBody();
    if (empty($body['response_text'])) {
        sendResponse(false, 'response_text is required.', null, 422);
    }

    $responseText = trim($body['response_text']);
    $respondedBy  = $_SESSION['username'] ?? 'Store Manager';

    $stmt = $db->prepare("SELECT * FROM Feedback WHERE feedback_id = ?");
    $stmt->execute([$id]);
    $fb = $stmt->fetch();

    if (!$fb) {
        sendResponse(false, 'Feedback record not found.', null, 404);
    }

    $db->prepare("
        UPDATE Feedback 
        SET response_text = ?, responded_by = ?, responded_at = NOW(), status = 'responded' 
        WHERE feedback_id = ?
    ")->execute([$responseText, $respondedBy, $id]);

    sendResponse(true, 'Official management response recorded.', [
        'feedback_id'   => $id,
        'response_text' => $responseText,
        'responded_by'  => $respondedBy,
        'responded_at'  => date('Y-m-d H:i:s'),
        'status'        => 'responded'
    ]);
}

sendResponse(false, 'Method not allowed.', null, 405);
