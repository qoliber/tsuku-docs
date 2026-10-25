<?php

return [
    'store' => [
        'name' => 'Kumo Tea',
        'url' => 'https://shop.example.com',
        'description' => 'Japanese tea & teaware',
        'currency' => 'EUR',
    ],
    'products' => [
        [
            'sku' => 'TEA-KYU-350',
            'name' => 'Kyusu Teapot 350 ml',
            'description' => 'Tokoname clay teapot with a fine <mesh> filter.',
            'url' => 'https://shop.example.com/kyusu-teapot-350-ml',
            'image' => 'https://shop.example.com/media/catalog/product/k/y/kyusu-350.jpg',
            'gallery' => [
                'https://shop.example.com/media/catalog/product/k/y/kyusu-350-side.jpg',
                'https://shop.example.com/media/catalog/product/k/y/kyusu-350-lid.jpg',
            ],
            'qty' => 12,
            'backorders' => false,
            'price' => 49.00,
            'special_price' => null,
            'brand' => 'Gyokko',
            'gtin' => '4901234567894',
            'categories' => ['Teaware', 'Teapots'],
            'weight' => 0.42,
        ],
        [
            'sku' => 'TEA-MAT-SET',
            'name' => 'Matcha Whisk & Bowl Set',
            'description' => 'Bamboo chasen (100 tines), chawan and scoop.',
            'url' => 'https://shop.example.com/matcha-whisk-bowl-set',
            'image' => 'https://shop.example.com/media/catalog/product/m/a/matcha-set.jpg',
            'gallery' => [],
            'qty' => 0,
            'backorders' => true,
            'price' => 39.90,
            'special_price' => 29.90,
            'brand' => 'Kumo Tea',
            'gtin' => '',
            'categories' => ['Teaware', 'Matcha'],
            'weight' => 0.65,
        ],
    ],
];
