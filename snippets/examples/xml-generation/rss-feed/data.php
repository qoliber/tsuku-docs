<?php

return [
    'blog' => [
        'title' => 'Kumo Tea Journal',
        'url' => 'https://shop.example.com/journal',
        'description' => 'Brewing guides & news from the tea room',
        'updated_at' => strtotime('2026-10-22 09:30'),
    ],
    'posts' => [
        [
            'title' => 'Gyokuro at 50 °C: why cooler water tastes sweeter',
            'url' => 'https://shop.example.com/journal/gyokuro-50-degrees',
            'published_at' => strtotime('2026-10-22 08:00'),
            'categories' => ['Brewing', 'Green tea'],
            'excerpt' => 'Low temperature keeps the bitterness <em>down</em>.',
            'html' => '<p>Gyokuro is shaded for three weeks before harvest &amp; brewed <strong>cool</strong>.</p>',
        ],
    ],
];
