<?php

return [
    'currency' => 'EUR',
    'products' => [
        ['id' => 101, 'sku' => 'TEA-KYU-350', 'name' => 'Kyusu Teapot 350 ml', 'price' => 49.00, 'qty' => 12, 'tags' => ['teapot', 'clay']],
        ['id' => 103, 'sku' => 'TEA-TET-080', 'name' => 'Cast Iron Kettle "Tetsubin" 0.8 l', 'price' => 89.00, 'qty' => 0, 'tags' => []],
    ],
    'page' => [
        'current' => 1,
        'size' => 2,
        'total' => 5,
        'last' => 3,
        'next_url' => '/api/products?page=2&per_page=2',
    ],
];
