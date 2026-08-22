<?php
/**
 * paypal.php - PayPal Sandbox REST API v2 Configuration & Helper Engine
 * Ravenhill Coffee POS System
 */

define('PAYPAL_CLIENT_ID', 'AYJK7O3QBO-dlC1YlzWd8eujwC_mGTQjgwG2V6UiGgzIh3gdfFa1nviCwQ02LU7q6ZuwIGet0HjVelto');
define('PAYPAL_SECRET', 'EFaItmT1F8NMhEO_xEz7gpVWFTKxqq2j6YiBYwfexmaeVO5h24yjyuBuqngGYeYVzuooLdgkZcAei2Ov');
define('PAYPAL_BASE_URL', 'https://api-m.sandbox.paypal.com');
define('PAYPAL_CURRENCY', 'AUD');

/**
 * Get PayPal OAuth2 Bearer Access Token
 */
function getPayPalAccessToken() {
    static $cachedToken = null;
    static $tokenExpiry = 0;

    if ($cachedToken !== null && time() < $tokenExpiry) {
        return $cachedToken;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => PAYPAL_BASE_URL . '/v1/oauth2/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
        CURLOPT_USERPWD        => PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Accept-Language: en_US',
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode !== 200) {
        error_log("[PayPal Auth Error] HTTP $httpCode: $response | Curl Error: $error");
        return null;
    }

    $data = json_decode($response, true);
    if (!empty($data['access_token'])) {
        $cachedToken = $data['access_token'];
        $tokenExpiry = time() + ($data['expires_in'] ?? 3600) - 60; // 1 min buffer
        return $cachedToken;
    }

    return null;
}

/**
 * Create a PayPal Order (Intent: CAPTURE)
 */
function createPayPalOrder($orderId, $amount, $description = 'Ravenhill Coffee Order') {
    $token = getPayPalAccessToken();
    if (!$token) {
        return ['success' => false, 'message' => 'Unable to authenticate with PayPal Sandbox.'];
    }

    $formattedAmount = number_format((float)$amount, 2, '.', '');

    $payload = [
        'intent' => 'CAPTURE',
        'purchase_units' => [
            [
                'reference_id' => 'ORDER_' . $orderId,
                'description'  => $description . ' #' . $orderId,
                'amount'       => [
                    'currency_code' => PAYPAL_CURRENCY,
                    'value'         => $formattedAmount
                ]
            ]
        ],
        'application_context' => [
            'brand_name'          => 'Ravenhill Coffee Roasters',
            'locale'              => 'en-AU',
            'landing_page'        => 'NO_PREFERENCE',
            'user_action'         => 'PAY_NOW',
            'return_url'          => 'https://mehedihasan.au/kent/cpro306/g1/',
            'cancel_url'          => 'https://mehedihasan.au/kent/cpro306/g1/'
        ]
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => PAYPAL_BASE_URL . '/v2/checkout/orders',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            'PayPal-Request-Id: ' . uniqid('order_', true)
        ],
        CURLOPT_TIMEOUT        => 15
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error || ($httpCode !== 200 && $httpCode !== 201)) {
        return ['success' => false, 'message' => "PayPal API Error ($httpCode): " . ($error ?: $response)];
    }

    $data = json_decode($response, true);
    return [
        'success'         => true,
        'paypal_order_id' => $data['id'],
        'status'          => $data['status'],
        'links'           => $data['links'] ?? []
    ];
}

/**
 * Capture an Approved PayPal Order
 */
function capturePayPalOrder($paypalOrderId) {
    $token = getPayPalAccessToken();
    if (!$token) {
        return ['success' => false, 'message' => 'Unable to authenticate with PayPal Sandbox.'];
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => PAYPAL_BASE_URL . '/v2/checkout/orders/' . urlencode($paypalOrderId) . '/capture',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => '{}',
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            'PayPal-Request-Id: ' . uniqid('capture_', true)
        ],
        CURLOPT_TIMEOUT        => 15
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error || ($httpCode !== 200 && $httpCode !== 201)) {
        return ['success' => false, 'message' => "PayPal Capture Error ($httpCode): " . ($error ?: $response)];
    }

    $data = json_decode($response, true);
    $captureId = null;
    $amount    = null;
    $status    = $data['status'] ?? 'COMPLETED';

    if (!empty($data['purchase_units'][0]['payments']['captures'][0])) {
        $capture   = $data['purchase_units'][0]['payments']['captures'][0];
        $captureId = $capture['id'];
        $amount    = $capture['amount']['value'] ?? null;
        $status    = $capture['status'] ?? $status;
    }

    return [
        'success'         => true,
        'paypal_order_id' => $paypalOrderId,
        'capture_id'      => $captureId ?: ('PAYPAL-' . $paypalOrderId),
        'status'          => $status,
        'amount'          => $amount,
        'raw'             => $data
    ];
}
