<?php

return [
    'site' => [
        'name' => 'shop-staging',
        'production' => false,
        'domains' => ['staging.shop.example.com', 'staging.shop.example.nl'],
        'root' => '/var/www/shop/current/pub',
        'php' => '8.3',
        'redirects' => [
            ['from' => '/thee', 'to' => '/tea'],
            ['from' => '/sale', 'to' => '/offers'],
        ],
    ],
];
