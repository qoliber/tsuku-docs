<?php

return [
    'order' => [
        'increment_id' => '000002203',
        'created_at' => strtotime('2026-10-21'),
        'customer_firstname' => 'Anna',
        'items' => [
            ['name' => 'Kyusu Teapot 350 ml', 'sku' => 'TEA-KYU-350', 'qty' => 1, 'price' => 49.00],
            ['name' => 'Sencha Asamushi 100 g', 'sku' => 'TEA-SEN-100', 'qty' => 2, 'price' => 14.90],
            ['name' => 'Matcha Whisk & Bowl Set', 'sku' => 'TEA-MAT-SET', 'qty' => 1, 'price' => 29.90],
        ],
        'shipping_method' => 'DHL Parcel',
        'shipping_amount' => 0,
        'grand_total' => 108.70, // calculated in PHP: templates have no arithmetic
        'tracking_url' => 'https://track.example.com/?code=JVGL0612345678&lang=en',
        'shipping_address' => ['name' => 'Anna de Vries', 'street' => 'Keizersgracht 12', 'city' => 'Amsterdam'],
    ],
];
