<?php

return [
    'categories' => [
        [
            'id' => 3,
            'name' => 'Tea',
            'url' => '/tea',
            'children' => [
                ['id' => 11, 'name' => 'Green tea', 'product_count' => 24],
                ['id' => 12, 'name' => 'Matcha', 'product_count' => 9],
            ],
        ],
        [
            'id' => 4,
            'name' => 'Teaware',
            'url' => '/teaware',
            'children' => [
                ['id' => 21, 'name' => 'Teapots & Kettles', 'product_count' => 17],
            ],
        ],
    ],
];
