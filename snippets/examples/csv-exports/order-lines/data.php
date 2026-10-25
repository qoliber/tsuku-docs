<?php

$orders = [
    ['increment_id' => '000002203', 'created_at' => strtotime('2026-10-21'), 'customer' => 'de Vries, Anna', 'items' => [
        ['sku' => 'TEA-KYU-350', 'qty' => 1, 'price' => 49.00],
        ['sku' => 'TEA-SEN-100', 'qty' => 2, 'price' => 14.90],
    ]],
    ['increment_id' => '000002204', 'created_at' => strtotime('2026-10-22'), 'customer' => 'Jan Kowalski', 'items' => [
        ['sku' => 'TEA-MAT-SET', 'qty' => 1, 'price' => 29.90],
    ]],
];

// Templates have no arithmetic: row totals are calculated here.
foreach ($orders as &$order) {
    foreach ($order['items'] as &$item) {
        $item['row_total'] = $item['qty'] * $item['price'];
    }
}
unset($order, $item);

return ['orders' => $orders];
