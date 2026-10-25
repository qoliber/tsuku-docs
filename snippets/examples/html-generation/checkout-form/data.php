<?php

// The field list comes from configuration; 'value' is what the customer entered before.
return [
    'form' => [
        'action' => '/checkout/shipping/save',
        'key' => 'f7Kq2Lw9',
        'fields' => [
            ['name' => 'firstname', 'label' => 'First name', 'type' => 'text', 'value' => 'Anna', 'autocomplete' => 'given-name', 'required' => true, 'hint' => ''],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => 'anna@example.com', 'autocomplete' => 'email', 'required' => true, 'hint' => 'We send the order confirmation here.'],
            ['name' => 'telephone', 'label' => 'Phone', 'type' => 'tel', 'value' => '', 'autocomplete' => 'tel', 'required' => false, 'hint' => 'Only used by the courier.'],
            [
                'name' => 'country_id',
                'label' => 'Country',
                'type' => 'select',
                'value' => 'NL',
                'required' => true,
                'hint' => '',
                'options' => [
                    ['value' => 'BE', 'label' => 'Belgium'],
                    ['value' => 'DE', 'label' => 'Germany'],
                    ['value' => 'NL', 'label' => 'Netherlands'],
                ],
            ],
            ['name' => 'comment', 'label' => 'Delivery note', 'type' => 'textarea', 'value' => 'Ring twice <3', 'required' => false, 'hint' => ''],
        ],
    ],
];
