# Strictness Modes & Errors

Templates meet imperfect data: a product without a `brand`, a function that was not registered on this server, a currency service that times out. The strictness mode decides whether Tsuku ignores such problems, reports them, or stops. Syntax errors in the template itself are always exceptions.

## The Three Modes

| Mode | Problem in the output | Use it for |
|------|-----------------------|------------|
| `StrictnessMode::SILENT` (default) | prints nothing for that piece and continues | optional fields, legacy data |
| `StrictnessMode::WARNING` | prints nothing, records a warning, continues | production exports you want to monitor |
| `StrictnessMode::STRICT` | throws `TsukuException` | development, tests, exports that must be complete |

The same template and data in each mode:

```php
use Qoliber\Tsuku\Tsuku;
use Qoliber\Tsuku\StrictnessMode;

$tsuku = new Tsuku(StrictnessMode::SILENT);

$template = 'Product: {product.name}, Brand: [{product.brand}]';
$data = ['product' => ['name' => 'Widget']];

echo $tsuku->process($template, $data);
// Output: Product: Widget, Brand: []
```

```php
$tsuku = new Tsuku(StrictnessMode::WARNING);

$template = 'Product: {product.name}, Brand: [{product.brand}]';
$data = ['product' => ['name' => 'Widget']];

echo $tsuku->process($template, $data);
// Output: Product: Widget, Brand: []

print_r($tsuku->getWarnings());
// Array ( [0] => Variable 'product.brand' not found in data )
```

```php
$tsuku = new Tsuku(StrictnessMode::STRICT);

$template = 'Product: {product.name}, Brand: [{product.brand}]';
$data = ['product' => ['name' => 'Widget']];

echo $tsuku->process($template, $data);
// Throws: TsukuException: Variable 'product.brand' not found in data
```

## What the Mode Controls

The mode applies to exactly three situations, all in the template's **output**:

| Situation | Warning / exception message |
|-----------|-----------------------------|
| A `{variable}` that does not exist or is `null` | `Variable 'product.brand' not found in data` |
| An `@function()` that is not registered | `Function 'money' not found` |
| A function or [directive](/guide/custom-directives) handler that throws (including a `TypeError` from its parameter types, or a return value that cannot be converted to a string) | `Function 'stock' error: <message>` / `Directive 'cdata' error: <message>` |

```php
$tsuku = new Tsuku(StrictnessMode::WARNING);
$tsuku->registerFunction('stock', function (string $sku): string {
    throw new \RuntimeException('stock API timeout');
});

$template = 'Price: [@money(price)] Stock: [@stock(sku)] Note: [{note}]';
$data = ['price' => 10, 'sku' => 'A-1', 'note' => null];

echo $tsuku->process($template, $data);
// Output: Price: [] Stock: [] Note: []

print_r($tsuku->getWarnings());
// Array
// (
//     [0] => Function 'money' not found
//     [1] => Function 'stock' error: stock API timeout
//     [2] => Variable 'note' not found in data
// )
```

Empty values are not missing: `''`, `0` and `false` print as an empty string, `0` and an empty string respectively, with no warning in any mode.

### What the mode does not control

- **Missing values everywhere except `{variable}` output.** In conditions (`@if(missing)`), `@for(missing as x)`, `@match(missing)`, `@?{...}` and function arguments a missing variable is simply `null` (or `""` as an argument). That is no warning and no exception, even in `STRICT` mode:

```php
$tsuku = new Tsuku(StrictnessMode::STRICT);

$template = '[@if(product.sale_price)On sale@else Regular price@end] [@upper(product.brand)] [@for(product.tags as tag){tag}@end]';

echo $tsuku->process($template, ['product' => []]);
// Output: [ Regular price] [] []
```

- **Syntax errors** in the template: always a `ParseException`.
- **Unknown functions in conditions**: always a `FunctionNotFoundException`, thrown while the template is parsed.
- **Exceptions thrown by functions called inside conditions**: they reach your code unchanged, in every mode.

## Setting the Mode

Pass the mode to the constructor, or override it for one call with the third argument of `process()` (the sixth of [`processToStream()`](/guide/streaming)):

```php
$tsuku = new Tsuku(); // SILENT

echo $tsuku->process('Hello {name}', [], StrictnessMode::STRICT);
// Throws: TsukuException: Variable 'name' not found in data
```

### Per-call mode and warnings

The per-call override renders with a temporary context, so warnings recorded under it are **not** returned by `getWarnings()`:

```php
$tsuku = new Tsuku(); // SILENT

$tsuku->process('Hello {name}', [], StrictnessMode::WARNING);

$tsuku->getWarnings(); // [] - the warning was recorded in the temporary context
```

This is a limitation of 1.4.0. If you need the warnings, create the instance in `WARNING` mode instead of overriding the mode per call.

## Warnings

`getWarnings()` returns the warnings of the **last** `process()` or `processToStream()` call as a list of strings; `hasWarnings()` tells whether there are any. Every call clears them first, so read them after each call:

```php
$tsuku = new Tsuku(StrictnessMode::WARNING);

foreach ($stores as $store) {
    $feed = $tsuku->process($template, $store->data());

    if ($tsuku->hasWarnings()) {
        foreach ($tsuku->getWarnings() as $warning) {
            $logger->warning($warning, ['store' => $store->code()]);
        }
    }
}
```

Warnings are not de-duplicated: a missing variable inside a `@for` produces one warning per row.

## Exceptions

All exceptions defined by Tsuku extend `Qoliber\Tsuku\Exception\TsukuException`, which extends `\Exception`:

```text
\Exception
└── TsukuException                 STRICT-mode errors (plain TsukuException)
    ├── ParseException             invalid template syntax, malformed conditions
    ├── FunctionNotFoundException  unknown function in a condition, FunctionRegistry::execute()
    ├── DirectiveNotFoundException DirectiveRegistry::get() for an unknown name
    ├── FormatterNotFoundException FormatterRegistry::get() for an unknown name
    └── InvalidTemplateException   defined, not thrown by Tsuku 1.4.0
```

Other exceptions you can meet:

| Exception | When |
|-----------|------|
| `\InvalidArgumentException` | `registerDirective()` with an invalid or built-in name |
| `\RuntimeException` | `processToStream()` when the template has no, or more than one, top-level `@for` over the streamed variable |
| `\TypeError` | wrong PHP argument types, e.g. a string as `$rows` of `processToStream()` |
| anything | thrown by your own functions inside conditions, or by a streaming writer |

### ParseException

Thrown while the template is read, before anything is rendered, in every mode. The message names the line and column; `getTemplateLine()` and `getTemplateColumn()` return them as integers (1-based, counted in the template as you wrote it, including lines joined with `\` and `\r\n` line breaks).

```php
use Qoliber\Tsuku\Exception\ParseException;

$template = "Name: {name}\n@if(status = \"active\")Active@end";

try {
    $tsuku->process($template, ['name' => 'Ann', 'status' => 'active']);
} catch (ParseException $e) {
    echo $e->getMessage();
    // Unexpected '=' (use == to compare) at line 2, column 12 in condition 'status = "active"'
    echo $e->getTemplateLine();   // 2
    echo $e->getTemplateColumn(); // 12
}
```

Common messages:

| Template | Message |
|----------|---------|
| `user@example.com` | `Invalid @ syntax at line 1, column 5 (write @@ for a literal @)` |
| `@upper(name` | `Unclosed parenthesis after @upper at line 1, column 1` |
| `@for(items)` | `Invalid @for syntax at line 1, column 1: 'items'. Expected: 'items as item' or 'items as item, key'` |
| `@if(a)x` | `Expected @else or @end but reached end of file at line 1, column 8 (@if opened at line 1, column 1)` |
| `x@else` | `Unexpected @else at line 1, column 2 - @else must follow @if or @unless` |
| `@if(a = 1)x@end` | `Unexpected '=' (use == to compare) at line 1, column 7 in condition 'a = 1'` |
| `@if(qty - 1 > 0)x@end` | `Unexpected '-' at line 1, column 9 in condition 'qty - 1 > 0'` |
| `@if(1 < qty < 10)x@end` | `Chained comparisons are not supported, combine them with "and" at line 1, column 13 in condition '1 < qty < 10'` |
| `@if(a and)x@end` | `Missing value at line 1, column 10 in condition 'a and'` |

Messages of malformed conditions end with `in condition '...'`. See [Conditionals](/guide/conditionals) for what conditions support.

### FunctionNotFoundException

An unknown function inside a condition is reported while the template is parsed, whatever the mode. `getFunctionName()` returns the name without `@`:

```php
use Qoliber\Tsuku\Exception\FunctionNotFoundException;

try {
    $tsuku->process("Hello\n@if(@is_vip(customer))VIP@end", ['customer' => []]);
} catch (FunctionNotFoundException $e) {
    echo $e->getMessage();      // Unknown function '@is_vip' at line 2, column 5 in condition '@is_vip(customer)'
    echo $e->getFunctionName(); // is_vip
}
```

`FunctionRegistry::execute()` throws it too (`Function 'name' not found`). An unknown function in the output is not an exception unless the mode is `STRICT`, where it is a plain `TsukuException` (`Function 'name' not found`).

### TsukuException in STRICT mode

Missing variables, unknown output functions and handler errors throw `TsukuException` itself, not a subclass. Catch `ParseException` and `FunctionNotFoundException` first if you want to tell template bugs from data problems:

```php
use Qoliber\Tsuku\Exception\FunctionNotFoundException;
use Qoliber\Tsuku\Exception\ParseException;
use Qoliber\Tsuku\Exception\TsukuException;

$tsuku = new Tsuku(StrictnessMode::STRICT);

try {
    $feed = $tsuku->process($template, $data);
} catch (ParseException | FunctionNotFoundException $e) {
    // the template is broken: fix it before the next run
} catch (TsukuException $e) {
    // the data is incomplete: "Variable 'product.brand' not found in data", ...
}
```

## Choosing a Mode

- **Development and tests**: `STRICT`, so a typo such as `{product.nmae}` fails immediately.
- **Scheduled exports**: `WARNING`, log `getWarnings()` after each run, and alert when it is not empty. Or `STRICT` with a temporary file that only replaces the published feed when the export completed (see [Streaming](/guide/streaming#requirements-and-errors)).
- **Optional fields**: `SILENT`, or keep a stricter mode and give the template a fallback with `@default(product.brand, "")` - function arguments are never reported as missing.

## Next Steps

- [Streaming Large Datasets →](/guide/streaming) - Errors in long-running exports
- [Conditionals →](/guide/conditionals) - What a condition may contain
- [Custom Functions →](/guide/custom-functions) - Function errors and argument types
