<?php

return [
    'services' => [
        [
            'name' => 'web',
            'build' => '.',
            'ports' => ['8080:80'],
            'environment' => ['APP_ENV' => 'dev', 'DB_HOST' => 'db'],
            'volumes' => ['./src:/var/www/html'],
            'depends_on' => ['db', 'redis'],
        ],
        [
            'name' => 'db',
            'image' => 'mysql:8.4',
            'environment' => ['MYSQL_DATABASE' => 'shop', 'MYSQL_ROOT_PASSWORD' => 'change-me'],
            'volumes' => ['db-data:/var/lib/mysql'],
        ],
        ['name' => 'redis', 'image' => 'redis:7-alpine'],
    ],
    'volumes' => ['db-data'],
];
