<?php
/**
 * privacy.php
 * 3. Privacy Requirements
 * NFR19: Customer data confidentiality
 * NFR20: Employee data confidentiality
 * NFR21: Customer consent management
 * NFR22: Promotional communication opt-out
 * NFR23: Minimum personal data collection
 * NFR24: Authorised personal data modification & erasure
 *
 * Routes:
 *   POST /api/privacy/privacy.php?action=consent      — NFR21: Record/update customer consent preferences
 *   POST /api/privacy/privacy.php?action=opt_out      — NFR22: One-click promotional opt-out / unsubscribe
 *   POST /api/privacy/privacy.php?action=anonymize    — NFR24: Data erasure / anonymization request (GDPR/Australian Privacy Principles)
 *   GET  /api/privacy/privacy.php?customer_id=123     — NFR19 & NFR21: View customer privacy and consent status (Staff only)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';
require_once __DIR__ . '/../reports/audit.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$custId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;

// Gracefully ensure privacy and consent columns exist in Customers
try { $db->exec("ALTER TABLE Customers ADD COLUMN consent_marketing BOOLEAN DEFAULT FALSE"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Customers ADD COLUMN consent_terms BOOLEAN DEFAULT TRUE"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Customers ADD COLUMN consent_date DATETIME NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE Customers ADD COLUMN is_anonymized BOOLEAN DEFAULT FALSE"); } catch (Exception $e) {}

// ── OPTIONS preflight ──────────────────────────────────────────────────────
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── POST ?action=consent: NFR21 — Customer Consent Management ──────────────
if ($method === 'POST' && $action === 'consent') {
    $body = getRequestBody();

    if (empty($body['customer_id'])) {
        sendResponse(false, 'customer_id is required.', null, 422);
    }

    $customerId   = (int)$body['customer_id'];
    $consentMktg  = isset($body['consent_marketing']) ? ($body['consent_marketing'] ? 1 : 0) : 0;
    $consentTerms = isset($body['consent_terms']) ? ($body['consent_terms'] ? 1 : 0) : 1;

    $stmt = $db->prepare("SELECT customer_id, first_name FROM Customers WHERE customer_id = ?");
    $stmt->execute([$customerId]);
    $cust = $stmt->fetch();

    if (!$cust) {
        sendResponse(false, 'Customer not found.', null, 404);
    }

    $db->prepare("
        UPDATE Customers 
        SET consent_marketing = ?, consent_terms = ?, consent_date = NOW() 
        WHERE customer_id = ?
    ")->execute([$consentMktg, $consentTerms, $customerId]);

    logAudit('CONSENT_UPDATE', 'Customers', "Updated privacy consent for Customer #$customerId (Marketing: $consentMktg)");

    sendResponse(true, 'Privacy consent preferences updated successfully.', [
        'customer_id'       => $customerId,
        'consent_marketing' => (bool)$consentMktg,
        'consent_terms'     => (bool)$consentTerms,
        'consent_date'      => date('Y-m-d H:i:s')
    ]);
}

// ── POST ?action=opt_out: NFR22 — Promotional Communication Opt-out ────────
if ($method === 'POST' && $action === 'opt_out') {
    $body = getRequestBody();

    $email = $body['email'] ?? null;
    $phone = $body['phone'] ?? null;
    $customerId = !empty($body['customer_id']) ? (int)$body['customer_id'] : null;

    if (!$customerId && !$email && !$phone) {
        sendResponse(false, 'Please provide customer_id, email, or phone number to opt out.', null, 422);
    }

    $sql = "UPDATE Customers SET consent_marketing = 0 WHERE 1=0";
    $params = [];

    if ($customerId) {
        $sql .= " OR customer_id = ?";
        $params[] = $customerId;
    }
    if ($email) {
        $sql .= " OR email = ?";
        $params[] = $email;
    }
    if ($phone) {
        $sql .= " OR phone = ?";
        $params[] = $phone;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $affected = $stmt->rowCount();

    if ($affected === 0) {
        sendResponse(false, 'No matching customer record found to unsubscribe.', null, 404);
    }

    logAudit('PROMO_OPT_OUT', 'Customers', "Opted out from marketing communications for: " . ($email ?? $phone ?? "#$customerId"));

    sendResponse(true, 'You have been successfully opted out from all marketing and promotional communications.', [
        'opted_out'        => true,
        'records_affected' => $affected
    ]);
}

// ── POST ?action=anonymize: NFR24 — Authorised Personal Data Erasure ────────
if ($method === 'POST' && $action === 'anonymize') {
    requireAuth(['admin', 'manager']);
    $body = getRequestBody();

    if (empty($body['customer_id'])) {
        sendResponse(false, 'customer_id is required for data erasure.', null, 422);
    }

    $customerId = (int)$body['customer_id'];
    $stmt = $db->prepare("SELECT customer_id FROM Customers WHERE customer_id = ?");
    $stmt->execute([$customerId]);
    if (!$stmt->fetch()) {
        sendResponse(false, 'Customer not found.', null, 404);
    }

    // Anonymize personal info while preserving numerical totals for accounting
    $anonName  = 'Anonymized Guest #' . $customerId;
    $anonEmail = 'anonymized_' . $customerId . '@ravenhill.private';
    $anonPhone = '0000000000';

    $db->prepare("
        UPDATE Customers 
        SET first_name = ?, last_name = '', email = ?, phone = ?, 
            consent_marketing = 0, is_anonymized = 1 
        WHERE customer_id = ?
    ")->execute([$anonName, $anonEmail, $anonPhone, $customerId]);

    logAudit('DATA_ANONYMIZATION', 'Customers', "Personal data for Customer #$customerId anonymized per privacy request");

    sendResponse(true, "Customer #$customerId personal identifying information has been securely anonymized.", [
        'customer_id'   => $customerId,
        'is_anonymized' => true
    ]);
}

// ── GET: View Privacy & Consent Status (Staff / Manager) ───────────────────
if ($method === 'GET' && $custId) {
    requireAuth(['admin', 'manager', 'cashier']);

    $stmt = $db->prepare("
        SELECT customer_id, first_name, last_name, email, phone, 
               consent_marketing, consent_terms, consent_date, is_anonymized, joined_loyalty_at
        FROM Customers 
        WHERE customer_id = ?
    ");
    $stmt->execute([$custId]);
    $cust = $stmt->fetch();

    if (!$cust) {
        sendResponse(false, 'Customer not found.', null, 404);
    }

    $cust['consent_marketing'] = (bool)$cust['consent_marketing'];
    $cust['consent_terms']     = (bool)$cust['consent_terms'];
    $cust['is_anonymized']     = (bool)$cust['is_anonymized'];

    sendResponse(true, 'Privacy status retrieved.', $cust);
}

sendResponse(false, 'Method not allowed.', null, 405);
