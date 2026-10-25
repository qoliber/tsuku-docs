<?php

// The header and footer are rendered once, the @for body once per row, and every piece
// goes to the writer as soon as it is ready.
$tsuku->processToStream(
    $template,
    ['store' => $data['store']], // shared by the header, every row and the footer
    $data['products'],           // any iterable; a generator is consumed once
    'products',                  // the @for collection that is streamed
    static function (string $chunk): void {
        echo $chunk;             // in production: fwrite($handle, $chunk)
    },
);
