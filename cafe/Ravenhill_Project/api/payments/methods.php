<?php
/**
 * methods.php
 * FR34: Payment method selection
 *
 * Routes:
 *   GET /api/payments/methods.php — List all available payment methods and their configs (surcharges, icons, minimums)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';

$methods = [
    [
        'id'            => 'cash',
        'name'          => 'Cash',
        'icon'          => 'ri-money-dollar-circle-line',
        'requires_ref'  => false,
        'allows_change' => true,
        'surcharge_rate'=> 0.0,
        'description'   => 'Physical AUD currency with automatic change calculation'
    ],
    [
        'id'            => 'card',
        'name'          => 'Credit / Debit Card (EFTPOS)',
        'icon'          => 'ri-bank-card-line',
        'requires_ref'  => true,
        'allows_change' => false,
        'surcharge_rate'=> 0.015, // 1.5% merchant surcharge
        'description'   => 'Visa, Mastercard, AMEX contactless & chip terminal'
    ],
    [
        'id'            => 'apple_pay',
        'name'          => 'Apple Pay / Google Pay',
        'icon'          => 'ri-smartphone-line',
        'requires_ref'  => true,
        'allows_change' => false,
        'surcharge_rate'=> 0.015,
        'description'   => 'NFC Digital Mobile Wallet'
    ],
    [
        'id'            => 'loyalty_points',
        'name'          => 'Loyalty Points Redemption',
        'icon'          => 'ri-medal-line',
        'requires_ref'  => false,
        'allows_change' => false,
        'surcharge_rate'=> 0.0,
        'description'   => 'Redeem 100 points per $1 discount'
    ],
    [
        'id'            => 'paypal',
        'name'          => 'PayPal Sandbox Checkout',
        'icon'          => 'ri-paypal-line',
        'requires_ref'  => true,
        'allows_change' => false,
        'surcharge_rate'=> 0.0,
        'description'   => 'Instant PayPal Sandbox digital wallet and card checkout'
    ],
    [
        'id'            => 'gift_card',
        'name'          => 'Ravenhill Gift Card / Voucher',
        'icon'          => 'ri-gift-line',
        'requires_ref'  => true,
        'allows_change' => false,
        'surcharge_rate'=> 0.0,
        'description'   => 'Prepaid digital or plastic gift voucher'
    ]
];

sendResponse(true, 'Payment methods retrieved.', [
    'available_methods_count' => count($methods),
    'methods'                 => $methods
]);
