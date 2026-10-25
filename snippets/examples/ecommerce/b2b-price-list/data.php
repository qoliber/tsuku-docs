<?php

// customer_groups is a multiselect as Magento stores it: ids joined with commas.
return [
    'customer' => ['company' => 'Teehaus Lindner GmbH', 'group_id' => 7231],
    'products' => [
        ['sku' => 'TEA-KYU-350', 'name' => 'Kyusu Teapot 350 ml', 'unit' => 'piece', 'price' => 32.00, 'min_qty' => 6, 'status' => 'active', 'customer_groups' => '2,7231'],
        ['sku' => 'TEA-SEN-1KG', 'name' => 'Sencha Asamushi, bulk', 'unit' => 'kg', 'price' => 71.50, 'min_qty' => 1, 'status' => 'active', 'customer_groups' => '7231'],
        ['sku' => 'TEA-GYO-1KG', 'name' => 'Gyokuro, bulk', 'unit' => 'kg', 'price' => 148.00, 'min_qty' => 1, 'status' => 'active', 'customer_groups' => '17231,9'],
        ['sku' => 'TEA-MAT-SET', 'name' => 'Matcha Set, wholesale (6)', 'unit' => 'pack', 'price' => 129.00, 'min_qty' => 1, 'status' => 'discontinued', 'customer_groups' => '2,7231'],
    ],
];
