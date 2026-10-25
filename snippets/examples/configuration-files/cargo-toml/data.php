<?php

return [
    'package' => [
        'name' => 'feed-worker',
        'version' => '0.4.2',
        'authors' => ['Anna de Vries <anna@example.com>', 'Jan Kowalski <jan@example.com>'],
    ],
    'dependencies' => [
        ['name' => 'serde', 'version' => '1.0', 'features' => ['derive']],
        ['name' => 'tokio', 'version' => '1.40', 'features' => ['rt-multi-thread', 'macros']],
        ['name' => 'quick-xml', 'version' => '0.36', 'features' => []],
    ],
];
