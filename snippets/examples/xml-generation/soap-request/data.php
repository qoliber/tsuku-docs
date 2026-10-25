<?php

return [
    'order' => [
        'increment_id' => '000002203',
        'created_at' => strtotime('2026-10-21 14:05:09'),
        'customer' => ['number' => 'C-10442', 'name' => 'Anna de Vries', 'email' => 'anna@example.com'],
        // keyed from 1: the loop's second variable is the array key, used as the line number
        'items' => [
            1 => ['sku' => 'TEA-KYU-350', 'qty' => 1, 'price' => 49.00],
            2 => ['sku' => 'TEA-SEN-100', 'qty' => 2, 'price' => 14.90],
        ],
        'comment' => 'Gift wrap, no invoice in the parcel',
    ],
];
