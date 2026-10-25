<?php

$items = [
    ['sku' => 'TEA-KYU-350', 'name' => 'Kyusu Teapot 350 ml', 'qty' => 6, 'price' => 32.00],
    ['sku' => 'TEA-SEN-100', 'name' => 'Sencha Asamushi 100 g', 'qty' => 40, 'price' => 8.90],
    ['sku' => 'TEA-MAT-SET', 'name' => 'Matcha Set, wholesale (6)', 'qty' => 10, 'price' => 21.50],
];

// Templates have no arithmetic: every amount on the invoice is calculated here.
foreach ($items as &$item) {
    $item['row_total'] = $item['qty'] * $item['price'];
}
unset($item);
$subtotal = array_sum(array_column($items, 'row_total'));
$vat = round($subtotal * 0.21, 2);

return [
    'seller' => [
        'name' => 'Kumo Tea B.V.', 'street' => 'Keizersgracht 12', 'postcode' => '1015 CS', 'city' => 'Amsterdam',
        'vat_id' => 'NL123456789B01', 'iban' => 'NL91 ABNA 0417 1643 00',
    ],
    'invoice' => [
        'number' => 'INV-2026-0418',
        'date' => strtotime('2026-10-23'),
        'due_date' => strtotime('2026-11-06'),
        'paid_at' => null,
        'customer' => [
            'company' => 'Teehaus Lindner GmbH', 'street' => 'Bergmannstraße 5', 'postcode' => '10961', 'city' => 'Berlin',
            'vat_id' => 'DE811907980',
        ],
        'items' => $items,
        'subtotal' => $subtotal,
        'vat_rate' => 21,
        'vat' => $vat,
        'total' => $subtotal + $vat,
    ],
];
