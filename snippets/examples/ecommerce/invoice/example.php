<?php

// Fixed-width columns: @pad(value, width) cuts or pads a value to the width, "right" aligns numbers.
$tsuku->registerFunction('pad', static function (mixed $value, int $width, string $align = 'left'): string {
    $value = mb_substr((string) $value, 0, $width);
    $padding = str_repeat(' ', $width - mb_strlen($value));

    return $align === 'right' ? $padding . $value : $value . $padding;
});

echo $tsuku->process($template, $data);
