# Performance & Benchmarks

## ⚠️ SPOILER ALERT: IT'S NOT A WEB-PAGE ENGINE ⚠️

::: danger HONEST PERFORMANCE DISCLOSURE
**Tsuku does not compile templates to PHP and keeps no template cache.** Every `process()` call tokenizes, parses and renders the template again.

Engines that compile templates to cached PHP code (Twig, Mustache, Blade) are considerably faster per render, especially for small templates rendered thousands of times per second.

This is a deliberate trade-off for:
- **Zero dependencies** (no external packages)
- **Zero setup** (no cache directories, no configuration)
- **Extreme simplicity** (install and use immediately)

Tsuku is built for exports, feeds and documents, where one render produces a large output.
:::

## Benchmark Results (1.4.0)

The library repository ships four benchmarks in `benchmarks/`. These are the numbers we measured for 1.4.0:

**Environment:**
- Tsuku 1.4.0, PHP 8.3.36, run as `/usr/bin/php8.3 -n benchmarks/run-all.php` (no OPcache, no Xdebug)
- Intel Core Ultra 7 265K, Linux
- Median of 9 runs, spread about ±5%; PHP 8.1 and 8.5 were within 10%. Your hardware will differ

| Benchmark | What it renders | Per render | Throughput |
|-----------|-----------------|------------|------------|
| **Simple** (`simple.php`) | CSV with 100 products (variables, `@for`, `@number`) | 0.16 ms | ~6,400 renders/sec |
| **Complex** (`complex.php`) | 10 categories × 20 products: nested loops, conditions, `@match`, functions | 0.56 ms | ~1,800 renders/sec |
| **Variables** (`variables.php`) | 1,000 simple `{variables}` | 1.3 ms | ~760 renders/sec |
| **CSV Export** (`csv-export.php`) | 1,000 products with `@csv`, `@number`, `@match` | 2.9 ms | ~350 exports/sec (**~350,000 products/sec**) |

::: tip What changed in 1.4.0
Conditions are now parsed once per template into an expression tree instead of being matched with regular expressions every time they are evaluated. According to the [1.4.0 changelog](/changelog), 100k-row feeds render 6–14% faster than with 1.3.0.
:::

## Running the Benchmarks Yourself

The benchmarks live in the library repository (they are not installed by `composer require`):

```bash
git clone https://github.com/qoliber/tsuku.git
cd tsuku
composer install

# All four benchmarks
php benchmarks/run-all.php
```

Or one at a time:

```bash
php benchmarks/simple.php      # CSV with 100 products
php benchmarks/complex.php     # Nested loops + conditions + match + functions
php benchmarks/variables.php   # 1,000 variables
php benchmarks/csv-export.php  # 1,000-product CSV export
```

Run them with `php -n` (or at least with Xdebug switched off): Xdebug in develop mode makes every benchmark about 5x slower. `run-all.php` starts each benchmark with the same PHP binary and options it was started with, and its header shows the PHP version, CPU and whether Xdebug or OPcache is active.

## What Was Tested?

Between them, the four benchmarks cover the features used in real exports:

### Directives
- ✓ `@for` loops, including nested loops (categories → products)
- ✓ `@if` / `@else` conditionals
- ✓ `@match` / `@case` / `@default` (pattern matching, including `@case` with several values)

### Functions
- ✓ **Escaping:** `@csv`
- ✓ **Number:** `@number` with decimals
- ✓ **Many variables:** 1,000 `{variable}` lookups in one template

### Template Complexity
- 100 to 1,000 rows per render
- Nested data access (`category.products`, `product.price`)
- Pattern matching on a status field per row

## Understanding the Results

### Why is Tsuku slower than compiled engines?

1. **No Caching**: Tsuku re-parses templates on every render for simplicity
   - No cache directory setup
   - No file permission issues
   - No cache invalidation logic

2. **Zero Dependencies**: Tsuku has NO external dependencies
   - Twig requires multiple packages
   - Easier to install and maintain

3. **Full Lexical Analysis**: Tsuku does complete tokenization, whitespace control and parsing on every call
   - Flexible syntax
   - Error messages with line and column
   - Extensible architecture (custom functions and directives)

The parsing cost is paid once per `process()` call, not once per row: a 1,000-row export parses its template once and then walks the tree 1,000 times. That is why Tsuku does well on large exports and worse on tiny templates rendered in a tight loop.

### When Tsuku is the Right Choice

✅ **Simplicity matters** - Zero setup, zero configuration

✅ **Dependencies are a concern** - Absolutely zero dependencies

✅ **Data transformation** - CSV, XML, JSON, YAML generation

✅ **One render, big output** - Exports, reports, data feeds

✅ **Huge datasets** - [`processToStream()`](/guide/streaming) keeps memory bounded by one row

### When to Use Alternatives

Use Twig or Mustache when:

- Rendering thousands of pages per second (web apps)
- Template compilation/caching is acceptable
- Per-render latency is the absolute priority
- You're building a high-traffic website

## Performance Perspective

**With the numbers measured above:**
- A 1,000-product CSV export (escaping, number formatting, `@match`) takes about 3 ms
- That is roughly 350,000 product rows per second
- A 100-row CSV takes under 1 ms
- A template with 1,000 variables takes about 1.3 ms

For data transformation use cases (Tsuku's primary goal), this is **more than fast enough**: the database queries that produce the data are usually far slower than rendering it. Measure your own templates with your own data before drawing conclusions - the cost depends mostly on the number of rows and on the functions you call per row.

## Optimization Tips

### 1. Reuse Tsuku Instance

Create one instance, use it many times:

```php
// ✓ GOOD - Create once
$tsuku = new Tsuku();
foreach ($batches as $batch) {
    $result = $tsuku->process($template, $batch);
}

// ✗ BAD - Creates new instance each time
foreach ($batches as $batch) {
    $tsuku = new Tsuku();  // Unnecessary overhead
    $result = $tsuku->process($template, $batch);
}
```

**Why?** The function registry with its 28 built-in functions is built in the constructor, and your `registerFunction()` / `registerDirective()` calls only have to happen once.

### 2. Render Many Rows in One Call

Every `process()` call parses its template again. Rendering 10,000 rows with one call parses once; calling `process()` 10,000 times parses 10,000 times:

```php
// ✗ SLOW - the row template is parsed again for every product
$output = '';
foreach ($products as $product) {
    $output .= $tsuku->process($rowTemplate, ['product' => $product]);
}

// ✓ FAST - parsed once, the loop runs inside Tsuku
$output = $tsuku->process($template, ['products' => $products]);
```

### 3. Stream Large Exports

For tens of thousands of rows, hold neither all rows nor the whole output in memory. [`processToStream()`](/guide/streaming) renders the header once, each row as it arrives and the footer once, and hands every piece to a callback:

```php
$template = 'sku,name
@for(products as product)
@csv(product.sku),@csv(product.name)
@end';

$rows = [
    ['sku' => 'WID-001', 'name' => 'Widget'],
    ['sku' => 'GAD-002', 'name' => 'Gadget, Deluxe'],
];

$tsuku->processToStream($template, [], $rows, 'products', function (string $chunk): void {
    echo $chunk; // or fwrite($handle, $chunk)
});
// Output:
// sku,name
// WID-001,Widget
// GAD-002,"Gadget, Deluxe"
```

In a real export, `$rows` is a generator that reads from the database. It is consumed lazily, so memory stays bounded by the largest single piece (header, footer or one row).

### 4. Parse Once, Render Many Times

If you really have to render one template many separate times (one e-mail body per customer, say), parse it once with `TemplateParser` and render the tree with a `Compiler` per data set. That is what `process()` does internally, minus the repeated parsing:

```php
use Qoliber\Tsuku\Compiler\Compiler;
use Qoliber\Tsuku\DirectiveRegistry;
use Qoliber\Tsuku\Function\FunctionRegistry;
use Qoliber\Tsuku\ProcessingContext;
use Qoliber\Tsuku\StrictnessMode;
use Qoliber\Tsuku\Template\TemplateParser;

$functions = new FunctionRegistry();   // comes with the built-in functions
$functions->register('shout', fn(string $text): string => strtoupper($text) . '!');
$directives = new DirectiveRegistry();
$context = new ProcessingContext(StrictnessMode::SILENT);

// Parse once (lexer + whitespace control + parser)
$ast = (new TemplateParser($functions, $directives))->parse('{sku},@shout(name)');

// Render many times
foreach ([['sku' => 'A-1', 'name' => 'widget'], ['sku' => 'B-2', 'name' => 'gadget']] as $row) {
    echo (new Compiler($row, $context, $functions, $directives))->compile($ast), "\n";
}
// A-1,WIDGET!
// B-2,GADGET!
```

Pass the same registries to `TemplateParser` and `Compiler`: the parser needs them to recognise custom directives and to reject unknown functions in conditions, the compiler to call them. Warnings (in `WARNING` mode) are collected in the `ProcessingContext` you pass. To keep using a `Tsuku` instance with the same functions, inject the registries: `new Tsuku(StrictnessMode::SILENT, $directives, new FormatterRegistry(), $functions)`.

For a small template rendered 20,000 times this was about 8x faster than calling `process()` each time on our machine; for one big export it makes no difference.

### 5. Strictness Modes Cost Nothing Until Something Is Missing

The strictness mode is only consulted when a variable or function is missing or a function throws:

```php
use Qoliber\Tsuku\StrictnessMode;

// Missing data renders as empty output
$tsuku = new Tsuku(StrictnessMode::SILENT);

// Missing data renders as empty output and is recorded in getWarnings()
$tsuku = new Tsuku(StrictnessMode::WARNING);

// Missing data throws a TsukuException
$tsuku = new Tsuku(StrictnessMode::STRICT);
```

For templates that find all their data, the three modes render at the same speed, so choose the mode for correctness, not speed: `STRICT` in tests and CI, `WARNING` while developing (log `getWarnings()`), `SILENT` or `WARNING` in production. Set the mode in the constructor if you need `getWarnings()` - a mode passed as the third argument of `process()` does not record warnings on the instance. See [Strictness Modes & Errors](/guide/strictness).

### 6. Profile Before Optimizing

**Tsuku is rarely the bottleneck.**

Typical bottlenecks in data export workflows:

1. **Database queries** (usually much slower than rendering)
2. **File I/O** (disk writes)
3. **Network requests** (API calls)
4. **Data preparation** (before templating)

**Profile your application:**
```php
$start = microtime(true);

// Your workflow
$data = fetchFromDatabase();     // Usually slowest
$result = $tsuku->process($template, $data);  // Fast enough
file_put_contents('export.csv', $result);     // Often slow

$end = microtime(true);
echo "Total: " . (($end - $start) * 1000) . "ms\n";
```

## Conclusion

**Tsuku prioritizes:**
1. **Zero dependencies** - Absolutely NO external packages required
2. **Simplicity** - No cache setup, no configuration, works immediately
3. **Data transformation** - Purpose-built for CSV, XML, JSON, YAML generation
4. **Ease of use** - Install and start using in seconds

**Trade-off:** no compiled-template cache, so it is slower per render than compiled, cached engines

### Is This Acceptable?

**YES!** At roughly **350,000 CSV rows per second** on our machine, Tsuku is **perfectly suited for:**
- ✅ Batch data exports (CSV, XML, JSON)
- ✅ Product feeds with tens of thousands of rows ([streaming](/guide/streaming))
- ✅ Report generation pipelines
- ✅ Data transformation & ETL
- ✅ API response formatting
- ✅ One-time document generation

### When is it NOT suitable?

- ❌ High-traffic web applications rendering thousands of pages/second
- ❌ Real-time page generation under heavy load
- ❌ Sub-millisecond latency requirements for small templates

**For Tsuku's intended use case (data transformation), this trade-off is absolutely worth it.**

## Next Steps

- [Streaming Large Datasets →](/guide/streaming) - Memory-bounded exports
- [Architecture →](/guide/architecture) - Understanding the compiler pipeline
- [Best Practices →](/guide/best-practices) - Writing efficient templates
- [Security →](/guide/security) - Performance vs. security trade-offs
