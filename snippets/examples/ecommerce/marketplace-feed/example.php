<?php

// A tab or line break inside a value would start a new column or row: replace them with a space.
$tsuku->registerFunction('tsv', static fn (mixed $value): string => preg_replace('/[\t\r\n]+/', ' ', (string) $value));

echo $tsuku->process($template, $data);
