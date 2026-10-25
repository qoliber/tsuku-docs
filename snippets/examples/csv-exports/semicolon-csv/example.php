<?php

// @csv() quotes values that contain a comma. A semicolon file needs quotes around ";" instead.
$tsuku->registerFunction('ssv', static function (mixed $value): string {
    $value = (string) $value;

    return preg_match('/[;"\r\n]/', $value) === 1 ? '"' . str_replace('"', '""', $value) . '"' : $value;
});

echo $tsuku->process($template, $data);
