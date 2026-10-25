<?php

return [
    'event' => ['id' => 'evt_01JB7X2K9Q', 'created_at' => strtotime('2026-10-21 14:05:11')],
    'order' => [
        'increment_id' => '000002203',
        'status' => 'pending',
        'currency' => 'EUR',
        'grand_total' => 108.70,
        'customer' => ['id' => null, 'email' => 'anna@example.com', 'name' => 'Anna de Vries'],
        'items' => [
            ['sku' => 'TEA-KYU-350', 'qty' => 1, 'row_total' => 49.00],
            ['sku' => 'TEA-SEN-100', 'qty' => 2, 'row_total' => 29.80], // row totals come from PHP
        ],
        'shipment' => null, // not shipped yet
    ],
];
