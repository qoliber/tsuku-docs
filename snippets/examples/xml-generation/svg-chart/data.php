<?php

// Templates have no arithmetic, so PHP computes the geometry and the template only places it.
$orders = ['Jun' => 412, 'Jul' => 388, 'Aug' => 451, 'Sep' => 536, 'Oct' => 604];
$width = 360;
$baseline = 150;
$barWidth = 44;
$step = $width / count($orders);
$scale = 120 / max($orders);

$bars = [];
foreach (array_keys($orders) as $i => $month) {
    $height = (int) round($orders[$month] * $scale);
    $bars[] = [
        'label' => $month,
        'value' => $orders[$month],
        'best' => $orders[$month] === max($orders),
        'x' => (int) round($i * $step + ($step - $barWidth) / 2),
        'y' => $baseline - $height,
        'height' => $height,
        'center' => (int) round($i * $step + $step / 2),
        'value_y' => $baseline - $height - 6,
    ];
}

return [
    'chart' => [
        'title' => 'Orders per month',
        'width' => $width,
        'height' => 175,
        'baseline' => $baseline,
        'bar_width' => $barWidth,
        'label_y' => $baseline + 18,
    ],
    'bars' => $bars,
];
