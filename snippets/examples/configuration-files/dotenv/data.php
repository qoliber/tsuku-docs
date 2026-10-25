<?php

// Values from your own deployment config. Quote the ones with spaces; values that can
// contain '"', '$' or line breaks need a function that escapes them for your dotenv parser.
return [
    'generated_at' => strtotime('2026-10-25'),
    'app' => ['env' => 'staging', 'debug' => true, 'url' => 'https://staging.shop.example.com'],
    'db' => ['host' => '10.0.2.15', 'port' => 3306, 'name' => 'shop_staging'],
    'cache' => ['driver' => 'redis', 'host' => '10.0.2.16', 'port' => 6379],
    'mail' => ['address' => 'noreply@shop.example.com', 'name' => 'Kumo Tea (staging)'],
];
