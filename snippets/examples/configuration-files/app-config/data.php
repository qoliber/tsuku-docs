<?php

return [
    'app' => ['name' => 'Kumo Tea: Shop', 'env' => 'production', 'debug' => false, 'url' => 'https://shop.example.com'],
    'db' => ['host' => 'db.internal', 'port' => 3306, 'name' => 'shop', 'user' => 'shop', 'password' => 'p4ss: #1 "x"'],
    'cache' => ['driver' => 'redis', 'host' => 'redis.internal', 'port' => 6379, 'path' => null],
    'queues' => [
        ['name' => 'emails', 'workers' => 2, 'retry' => ['attempts' => 5, 'backoff' => 30]],
        ['name' => 'feeds', 'workers' => 1, 'retry' => null],
    ],
];
