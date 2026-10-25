<?php

return [
    'problem' => [
        'title' => "Your request parameters didn't validate.",
        'status' => 422,
        'detail' => 'Two fields of the shipping address are invalid.',
        'instance' => '/api/carts/8f3a/shipping-address',
        'invalid_params' => [
            ['name' => 'postcode', 'reason' => 'must match "1234 AB" for the Netherlands'],
            ['name' => 'telephone', 'reason' => 'must not be empty'],
        ],
    ],
];
