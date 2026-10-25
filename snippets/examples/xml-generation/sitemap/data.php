<?php

return [
    'pages' => [
        [
            'loc' => 'https://shop.example.com/en/teapots',
            'updated_at' => strtotime('2026-10-20'),
            'alternates' => [
                'en' => 'https://shop.example.com/en/teapots',
                'de' => 'https://shop.example.com/de/teekannen',
                'nl' => 'https://shop.example.com/nl/theepotten',
            ],
        ],
        [
            'loc' => 'https://shop.example.com/en/teapots?page=2&limit=24',
            'updated_at' => strtotime('2026-10-18'),
            'alternates' => [],
        ],
    ],
];
