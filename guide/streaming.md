# Streaming Large Datasets

`process()` returns the whole output as one string. For a feed with 100,000 products that string, plus the array holding every product, has to fit in memory at once. `processToStream()` (available since 1.3.0) renders the same template piece by piece and hands each piece to a callback as soon as it is ready, so memory use no longer grows with the number of rows.

## Basic Usage

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

$template = <<<'XML'
<?xml version="1.0"?>
<feed currency="{currency}">
@for(products as product)
  <item>
    <sku>{product.sku}</sku>
    <price>{product.price} {currency}</price>
  </item>
@end
</feed>
XML;

$products = [
    ['sku' => 'A-1', 'price' => '9.99'],
    ['sku' => 'B-2', 'price' => '19.50'],
];

$tsuku->processToStream($template, ['currency' => 'EUR'], $products, 'products', function (string $chunk): void {
    echo $chunk;
});
// Output:
// <?xml version="1.0"?>
// <feed currency="EUR">
//   <item>
//     <sku>A-1</sku>
//     <price>9.99 EUR</price>
//   </item>
//   <item>
//     <sku>B-2</sku>
//     <price>19.50 EUR</price>
//   </item>
// </feed>
```

In a real export the rows come from a generator and the writer writes to a file:

```php
$rows = function (PDO $pdo): Generator {
    $statement = $pdo->query('SELECT sku, price FROM products');
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        yield $row;
    }
};

$handle = fopen('feed.xml', 'wb');
$tsuku->processToStream($template, ['currency' => 'EUR'], $rows($pdo), 'products', function (string $chunk) use ($handle): void {
    fwrite($handle, $chunk);
});
fclose($handle);
```

## Signature

```php
public function processToStream(
    string $template,
    array $contextData,
    iterable $rows,
    string $rowsVariable,
    callable $writer,
    ?StrictnessMode $strictnessMode = null
): void
```

| Parameter | Description |
|-----------|-------------|
| `$template` | The template. It must contain exactly one top-level `@for` over `$rowsVariable`. |
| `$contextData` | Data for the whole template: the header, the footer and every row. |
| `$rows` | The rows to stream: an array or any `Traversable` (generator, iterator). Consumed once. |
| `$rowsVariable` | The collection name used in the `@for`, e.g. `'products'` for `@for(products as product)`. |
| `$writer` | `callable(string $chunk): void`, called once per non-empty piece of output. |
| `$strictnessMode` | Optional per-call override of the [strictness mode](/guide/strictness). |

## How the Template Is Split

Tsuku parses the template once and splits it at the streamed `@for`:

| Piece | Rendered with | Written |
|-------|---------------|---------|
| **Header**: everything before the `@for` | `$contextData` | once, first |
| **Row**: the body of the `@for` | `$contextData` + the current row as the loop variable (and the key, if named) | once per row |
| **Footer**: everything after the `@for`'s `@end` | `$contextData` | once, last |

For the example above the writer receives four chunks: the header (`<?xml ...>` and `<feed currency="EUR">` with their line breaks), one `<item>...</item>` block per product, and the footer `</feed>`. A piece that renders to an empty string (a row whose body is an `@if` that is false, an empty footer) is not passed to the writer.

Concatenated, the chunks are exactly what `process()` returns when the rows are part of the data, so you can test a streaming template with `process()` on a small data set:

```php
$streamed = '';
$tsuku->processToStream($template, ['currency' => 'EUR'], $products, 'products', function (string $chunk) use (&$streamed): void {
    $streamed .= $chunk;
});

$streamed === $tsuku->process($template, ['currency' => 'EUR', 'products' => $products]); // true
```

[Whitespace control](/guide/whitespace-control) is applied to the whole template before it is split, so the layout is the same as with `process()`.

## Generators Are Consumed Lazily

Rows are pulled from `$rows` one at a time; each row is rendered and written before the next one is requested:

```php
$log = [];
$rows = (function () use (&$log): Generator {
    foreach ([1, 2] as $id) {
        $log[] = "load $id";
        yield ['id' => $id];
    }
})();

$tsuku->processToStream("H\n@for(rows as row)\n{row.id}\n@end\nF", [], $rows, 'rows', function (string $chunk) use (&$log): void {
    $log[] = 'write ' . json_encode($chunk);
});

// $log: write "H\n", load 1, write "1\n", load 2, write "2\n", write "F"
```

The iterable is traversed once. A generator cannot be rewound: passing the same generator to a second `processToStream()` call throws PHP's `Exception` "Cannot traverse an already closed generator". Create a new generator for every export.

## The Key Variable

`@for(rows as row, key)` works as usual; the key is the key produced by the iterable:

```php
$template = "@for(products as product, sku)\n{sku}: {product.name}\n@end";

$tsuku->processToStream($template, [], ['A-1' => ['name' => 'Widget'], 'B-2' => ['name' => 'Gadget']], 'products', function (string $chunk): void {
    echo $chunk;
});
// Output:
// A-1: Widget
// B-2: Gadget
```

A generator can set keys with `yield $sku => $row;`.

## Header and Footer Data

The header and the footer only see `$contextData`. The streamed rows are not available to them, so anything that depends on all rows - a count, a total, a "last updated" date - has to be known before streaming starts and passed in `$contextData`:

```php
$template = "Products: {count}\n@for(products as product)\n- {product.name}\n@end\nTotal stock: {total_stock}";

$tsuku->processToStream($template, ['count' => 2, 'total_stock' => 15], [
    ['name' => 'Widget', 'stock' => 10],
    ['name' => 'Gadget', 'stock' => 5],
], 'products', function (string $chunk): void {
    echo $chunk;
});
// Output:
// Products: 2
// - Widget
// - Gadget
// Total stock: 15
```

Do not put the rows into `$contextData` as well: the streamed `@for` always iterates `$rows`, but a header such as `@length(products)` would read the array from `$contextData`, and that array would be held in memory for the whole export.

## Requirements and Errors

**Exactly one top-level `@for` over `$rowsVariable`.** "Top-level" means not inside another `@for`, `@if`, `@match` or custom directive. Other top-level loops over other collections are fine; they are part of the header or footer. Otherwise a `\RuntimeException` is thrown before anything is written:

| Template | Message |
|----------|---------|
| no `@for(products ...)`, or only one inside `@if`/`@for` | `Streaming requires a top-level @for over "products" in the template.` |
| two top-level `@for(products ...)` | `Streaming requires exactly one top-level @for over "products"; found 2.` |

```php
$tsuku->processToStream("@if(show)\n@for(products as product){product}@end\n@end", ['show' => true], [1, 2], 'products', function (string $chunk): void {
    echo $chunk;
});
// Throws: RuntimeException: Streaming requires a top-level @for over "products" in the template.
```

`$rowsVariable` is compared with the collection exactly as written in the `@for`, so `@for(feed.items as item)` streams with `'feed.items'`.

**Parse errors** (`ParseException`, `FunctionNotFoundException`) are thrown while the template is parsed, before the writer is called.

**Errors while rendering** - a missing variable in `STRICT` mode, an exception from a function called in a condition, an exception thrown by the writer itself - stop the export where they happen. Chunks written before that point stay written; Tsuku cannot take them back. Write to a temporary file and rename it when `processToStream()` returns:

```php
$tmp = $path . '.tmp';
$handle = fopen($tmp, 'wb');
try {
    $tsuku->processToStream($template, $context, $rows, 'products', function (string $chunk) use ($handle): void {
        fwrite($handle, $chunk);
    }, StrictnessMode::STRICT);
} finally {
    fclose($handle);
}
rename($tmp, $path); // only reached when the export completed
```

**`$rows` must be iterable**: a string or `null` is a `TypeError`.

## Writers

The writer is any callable that accepts a string.

```php
// A plain file
$handle = fopen('feed.csv', 'wb');
$writer = function (string $chunk) use ($handle): void {
    fwrite($handle, $chunk);
};

// A gzip-compressed file
$gz = gzopen('feed.xml.gz', 'wb6');
$writer = function (string $chunk) use ($gz): void {
    gzwrite($gz, $chunk);
};
// ... processToStream(...), then gzclose($gz);

// The HTTP response, sent while it is being generated
header('Content-Type: application/xml; charset=UTF-8');
$writer = function (string $chunk): void {
    echo $chunk;
    flush();
};
```

## Functions, Directives and Strictness

Everything that works with `process()` works inside the header, the row body and the footer: built-in and [custom functions](/guide/custom-functions), [custom directives](/guide/custom-directives), conditions, `@match`, nested `@for` loops over a row's own collections:

```php
$template = "@for(orders as order)\n{order.id}: @join(order.skus, \"|\")@if(order.express) (express)@end\n@end";

$tsuku->processToStream($template, [], [
    ['id' => 1001, 'skus' => ['A-1', 'B-2'], 'express' => true],
    ['id' => 1002, 'skus' => ['C-3'], 'express' => false],
], 'orders', function (string $chunk): void {
    echo $chunk;
});
// Output:
// 1001: A-1|B-2 (express)
// 1002: C-3
```

The strictness mode of the `Tsuku` instance applies, and `getWarnings()` returns the warnings of all pieces after the call. The sixth argument overrides the mode for one call; its warnings are returned by `getWarnings()` too - see [Strictness Modes & Errors](/guide/strictness#per-call-mode-and-warnings).

## Memory

- The template is parsed once per call; each piece is rendered on its own and passed to the writer, never collected.
- Memory for the output is bounded by the largest single piece (the header, the footer or one row), however many rows are streamed.
- Memory for the input is up to you: a generator that fetches rows one by one keeps it small, an array of 100,000 rows is in memory before streaming starts.
- In `WARNING` mode at most 1,000 warnings are kept per call, so a warning on every row does not grow memory with the row count either; `getWarningCount()` counts all of them (see [Warnings](/guide/strictness#warnings)). Up to 1.4.1 every warning was kept: about 10 MB for 100,000 rows.

## Next Steps

- [Strictness Modes & Errors →](/guide/strictness) - Fail fast or collect warnings in long exports
- [Performance & Benchmarks →](/guide/performance) - Speed of large templates
- [XML Generation →](/examples/xml-generation) - Feed templates
