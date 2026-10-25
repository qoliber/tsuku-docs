<?php

$subtotal = 63.90;
$minSubtotal = 75.00;

// Plain-text email (text/plain): nothing to escape. Amounts are calculated in PHP.
return [
    'customer' => ['firstname' => 'Jan'],
    'cart' => [
        'items' => [
            ['qty' => 1, 'name' => 'Kyusu Teapot 350 ml', 'row_total' => 49.00],
            ['qty' => 1, 'name' => 'Sencha Asamushi 100 g', 'row_total' => 14.90],
        ],
        'subtotal' => $subtotal,
        'url' => 'https://shop.example.com/checkout/cart/restore/token/c81e728d',
    ],
    'coupon' => [
        'code' => 'COMEBACK10',
        'percent' => 10,
        'min_subtotal' => $minSubtotal,
        'missing' => max(0, $minSubtotal - $subtotal),
        'expires_at' => strtotime('2026-10-31'),
    ],
];
