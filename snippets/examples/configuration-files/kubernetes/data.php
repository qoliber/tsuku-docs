<?php

return [
    'app' => [
        'name' => 'shop-web',
        'labels' => ['app' => 'shop-web', 'team' => 'commerce'],
        'replicas' => 3,
        'containers' => [
            [
                'name' => 'php-fpm',
                'image' => 'registry.example.com/shop/php-fpm',
                'tag' => '2026.10.3',
                'port' => 9000,
                'env' => ['APP_ENV' => 'production', 'REDIS_HOST' => 'redis.internal'],
                'resources' => ['cpu' => '500m', 'memory' => '768Mi'],
            ],
            [
                'name' => 'log-shipper',
                'image' => 'fluent/fluent-bit',
                'tag' => '3.1',
                'port' => null,
                'env' => ['FLB_LOG_LEVEL' => 'warn'],
                'resources' => null,
            ],
        ],
    ],
];
