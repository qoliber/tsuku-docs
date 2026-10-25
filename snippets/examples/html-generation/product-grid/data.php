<?php

// Five stars, filled up to the rounded rating: templates have no arithmetic, so PHP prepares the list.
$stars = static fn (float $rating): array => array_map(
    static fn (int $star): bool => $star <= round($rating),
    range(1, 5),
);

return [
    'category' => ['name' => 'Teapots & Kettles'],
    'products' => [
        [
            'id' => 101,
            'name' => 'Kyusu Teapot 350 ml',
            'url' => '/kyusu-teapot-350-ml',
            'image' => '/examples/media/teapot.svg',
            'labels' => ['new'],
            'price' => 49.00,
            'special_price' => null,
            'rating' => 4.4,
            'stars' => $stars(4.4),
            'review_count' => 38,
            'qty' => 12,
        ],
        [
            'id' => 102,
            'name' => 'Matcha Whisk & Bowl Set',
            'url' => '/matcha-whisk-bowl-set',
            'image' => '/examples/media/whisk.svg',
            'labels' => [],
            'price' => 39.90,
            'special_price' => 29.90,
            'rating' => 4.2,
            'stars' => $stars(4.2),
            'review_count' => 12,
            'qty' => 5,
        ],
        [
            'id' => 103,
            'name' => 'Cast Iron Kettle "Tetsubin" 0.8 l',
            'url' => '/cast-iron-kettle-tetsubin',
            'image' => '/examples/media/kettle.svg',
            'labels' => ['bestseller'],
            'price' => 89.00,
            'special_price' => null,
            'rating' => 4.8,
            'stars' => $stars(4.8),
            'review_count' => 51,
            'qty' => 0,
        ],
    ],
];
