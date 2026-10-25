<?php

// Values typed by visitors can start with = + - @ and run as a formula in Excel or Google Sheets.
// A leading apostrophe makes the spreadsheet show them as text; then the value is quoted like @csv().
$tsuku->registerFunction('csv_safe', static function (mixed $value): string {
    $value = (string) $value;
    if ($value !== '' && str_contains("=+-@\t\r", $value[0])) {
        $value = "'" . $value;
    }

    return preg_match('/[,"\r\n]/', $value) === 1 ? '"' . str_replace('"', '""', $value) . '"' : $value;
});

echo $tsuku->process($template, $data);
