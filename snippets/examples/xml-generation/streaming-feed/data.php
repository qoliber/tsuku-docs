<?php

// A generator: a real one would read the catalog page by page, so only one row is ever in memory.
$products = (static function (): Generator {
    yield ['sku' => 'TEA-KYU-350', 'name' => 'Kyusu Teapot 350 ml', 'price' => 49.00, 'qty' => 12];
    yield ['sku' => 'TEA-SEN-100', 'name' => 'Sencha Asamushi 100 g', 'price' => 14.90, 'qty' => 140];
    yield ['sku' => 'TEA-TET-080', 'name' => 'Cast Iron Kettle "Tetsubin" 0.8 l', 'price' => 89.00, 'qty' => 0];
})();

return [
    'store' => ['code' => 'nl_en', 'currency' => 'EUR'],
    'products' => $products,
];
