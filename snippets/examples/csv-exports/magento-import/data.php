<?php

// Magento's import format: several category paths in one column, "code=value" pairs in additional_attributes.
return [
    'products' => [
        [
            'sku' => 'TEA-KYU-350',
            'categories' => ['Default Category/Teaware/Teapots', 'Default Category/New'],
            'name' => 'Kyusu Teapot 350 ml',
            'price' => 49.00,
            'special_price' => null,
            'qty' => 12,
            'backorders' => false,
            'url_key' => 'kyusu-teapot-350-ml',
            'attributes' => ['color=Red-brown', 'material=Tokoname clay'],
        ],
        [
            'sku' => 'TEA-MAT-SET',
            'categories' => ['Default Category/Teaware/Matcha'],
            'name' => 'Matcha Whisk & Bowl Set',
            'price' => 39.90,
            'special_price' => 29.90,
            'qty' => 0,
            'backorders' => true,
            'url_key' => 'matcha-whisk-bowl-set',
            'attributes' => ['material=Bamboo'],
        ],
    ],
];
