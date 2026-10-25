<?php

$tsuku->registerFunction('money', static fn (mixed $amount, string $currency = 'EUR'): string => match ($currency) {
    'EUR' => number_format((float) $amount, 2, ',', '.') . ' €',
    'GBP' => '£' . number_format((float) $amount, 2),
    default => $currency . ' ' . number_format((float) $amount, 2),
});

echo $tsuku->process($template, $data);
