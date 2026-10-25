<?php

// Cost columns only for the purchasing team; the margin is calculated here, not in the template.
$products = [
    ['sku' => 'TEA-KYU-350', 'name' => 'Kyusu Teapot 350 ml', 'price' => 49.00, 'cost' => 21.50],
    ['sku' => 'TEA-SEN-100', 'name' => 'Sencha Asamushi 100 g', 'price' => 14.90, 'cost' => 6.20],
];
foreach ($products as &$product) {
    $product['margin'] = ($product['price'] - $product['cost']) / $product['price'] * 100;
}
unset($product);

return ['include_cost' => true, 'products' => $products];
