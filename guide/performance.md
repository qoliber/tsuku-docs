# Performance & Benchmarks

## ⚠️ SPOILER ALERT: IT'S SLOW - BUT NOT FOR LONG! ⚠️

::: danger HONEST PERFORMANCE DISCLOSURE
**Tsuku is currently ~37x slower than compiled/cached templating engines like Twig and Mustache.**

This is a deliberate trade-off for:
- **Zero dependencies** (no external packages)
- **Zero setup** (no cache directories, no configuration)
- **Extreme simplicity** (install and use immediately)

**Performance improvements are actively planned for future releases.**
:::

## Comprehensive Benchmark Results

**Environment:**
- PHP 8.4.14
- 10,000 iterations (after 100 warmup)
- Real-world e-commerce order template

### Performance Comparison

| Rank | Engine | Avg Time (ms) | Total (ms) | Renders/Sec | vs Fastest |
|------|--------|---------------|------------|-------------|------------|
| 🥇 | **Mustache** | 0.0106 | 106.15 | ~94,000/sec | ✓ FASTEST |
| 🥈 | **Twig (cached)** | 0.0143 | 143.07 | ~69,000/sec | 1.35x slower |
| 🥉 | **Twig (no cache)** | 0.0146 | 145.91 | ~68,000/sec | 1.37x slower |
| 4️⃣ | **Tsuku** | 0.3955 | 3,955.29 | **~2,500/sec** | **37x slower** |

## Running the Benchmarks Yourself

```bash
# Install benchmark dependencies
composer install

# Run the comprehensive benchmark
composer benchmark
```

Or directly:

```bash
php benchmark/run.php
```

## What Was Tested?

The benchmark tests **ALL** of Tsuku's major features with a comprehensive real-world template:

### Directives
- ✓ `@for` loops with index tracking
- ✓ `@if` / `@else` conditionals
- ✓ `@unless` (inverse conditionals)
- ✓ `@match` / `@case` / `@default` (pattern matching)
- ✓ Nested loops (items → features, items → tags)

### Functions
- ✓ **String:** `@upper`, `@lower`, `@capitalize`, `@join`
- ✓ **Number:** `@number` with different precisions
- ✓ **Array:** `@length`, `@join`, `@default`
- ✓ **Date:** `@date` formatting

### Advanced Features
- ✓ **Ternary expressions:** `@?{condition "true" : "false"}`
- ✓ **Mathematical expressions:** `price * quantity * (1 - discount / 100)`
- ✓ **Null handling:** `@default(value, "fallback")`
- ✓ **Deep nested data access:** `store.order.items.features` (4 levels)

### Template Complexity
The benchmark template includes:
- 2 order items with features arrays
- Multiple nested loops
- Pattern matching for order status & stock status
- Complex calculations with discounts
- Conditional feature/tag display
- 4-level nested data structures

## Understanding the Results

### Why is Tsuku 37x slower?

With this **comprehensive real-world template** testing ALL features, Tsuku is 37x slower than Mustache because it makes different trade-offs:

1. **No Caching Required**: Tsuku re-parses templates on every render for simplicity
   - No cache directory setup
   - No file permissions issues
   - No cache invalidation logic

2. **Zero Dependencies**: Tsuku has NO external dependencies
   - Twig requires multiple packages
   - Easier to install and maintain

3. **Full Lexical Analysis**: Tsuku does complete tokenization and parsing
   - More flexible syntax
   - Better error messages
   - Extensible architecture

### When Tsuku is the Right Choice

Despite being slower in micro-benchmarks, Tsuku excels when:

✅ **Simplicity matters** - Zero setup, zero configuration

✅ **Dependencies are a concern** - Absolutely zero dependencies

✅ **Data transformation** - CSV, XML, JSON, YAML generation

✅ **Template is rendered once** - Exports, reports, data feeds

✅ **Memory constraints** - No cache files to maintain

### When to Use Alternatives

Use Twig or Mustache when:

- Rendering thousands of pages per second (web apps)
- Template compilation/caching is acceptable
- Performance is the absolute priority
- You're building a high-traffic website

## Performance Perspective

**0.40 ms per comprehensive template means:**
- **~2,500 renders per second**
- Rendering 1,000 complex order summaries = 400ms (~0.4 seconds)
- Processing one complex CSV export = 0.40ms
- Batch processing 10,000 records = ~4 seconds
- Generating 100 reports = ~40ms

For data transformation use cases (Tsuku's primary goal), this performance is **excellent**.

### Real-World Performance Examples

| Task | Records | Time | Acceptable? |
|------|---------|------|-------------|
| **CSV Product Export** | 5,000 products | ~2 seconds | ✅ Excellent |
| **XML Order Feeds** | 1,000 orders | ~0.4 seconds | ✅ Excellent |
| **JSON API Responses** | 100 responses | ~40ms | ✅ Fast |
| **PDF Invoice Generation** | 500 invoices | ~1 second | ✅ Great |
| **Email Templates** | 10,000 emails | ~4 seconds | ✅ Acceptable |
| **Report Generation** | 50 reports | ~20ms | ✅ Very Fast |

**Bottom Line:** At 2,500 renders/sec, Tsuku is **more than fast enough** for batch processing and data transformation tasks.

## Benchmark Template Details

The benchmark uses a **comprehensive e-commerce order** template featuring:

**Data Structure (4 levels deep):**
```
store
  └─ order
      ├─ customer (tier, vip status, verification)
      ├─ items[] (2 products)
      │   ├─ features[] (3-4 features each)
      │   └─ tags[] (2 tags each)
      ├─ shipping (method, cost, delivery time)
      ├─ payment (method, installments)
      └─ notes[] (2 special notes)
```

**Feature Coverage:**
- **6 directive types**: @for, @if, @unless, @match/@case/@default, @end
- **10+ functions**: @upper, @lower, @capitalize, @join, @number, @date, @length, @default
- **Nested loops**: Items → Features (2 levels), Items → Tags (2 levels)
- **Pattern matching**: Order status (4 cases), Stock status (3 cases)
- **Ternary operations**: Priority level, rating display
- **Math expressions**: Discount calculations, price totals
- **Null handling**: Default values for missing data

This represents a **real-world comprehensive template** with all of Tsuku's capabilities.

## Optimization Tips

Despite Tsuku's current performance limitations, you can still optimize your usage:

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

**Why?** Function/directive registries are initialized once per instance.

### 2. Use SILENT Mode for Performance

Strictness modes have different overhead:

```php
use Qoliber\Tsuku\StrictnessMode;

// Fastest - no warning collection
$tsuku = new Tsuku(StrictnessMode::SILENT);

// Slower - collects warnings
$tsuku = new Tsuku(StrictnessMode::WARNING);

// Slower - throws exceptions
$tsuku = new Tsuku(StrictnessMode::STRICT);
```

**Recommendation:** Use `SILENT` in production, `WARNING` in development, `STRICT` for debugging.

### 3. Batch Processing

Process data in batches for better overall workflow performance:

```php
// Process 1000 products at a time
$batches = array_chunk($allProducts, 1000);

foreach ($batches as $i => $batch) {
    $csv = $tsuku->process($template, ['products' => $batch]);
    file_put_contents("export-part-{$i}.csv", $csv);
}
```

### 4. Profile Before Optimizing

**Tsuku is rarely the bottleneck.**

Typical bottlenecks in data export workflows:

1. **Database queries** (often 100-1000x slower than Tsuku)
2. **File I/O** (disk writes)
3. **Network requests** (API calls)
4. **Data transformation** (before templating)

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

**Trade-off:** ~37x slower than optimized, cached engines on comprehensive templates

### Is This Acceptable?

**YES!** At **0.40ms per render** (~2,500/sec), Tsuku is **perfectly suited for:**
- ✅ Batch data exports (CSV, XML, JSON)
- ✅ Report generation pipelines
- ✅ Email template rendering
- ✅ Data transformation & ETL
- ✅ API response formatting
- ✅ One-time document generation

### When is it NOT suitable?

- ❌ High-traffic web applications rendering thousands of pages/second
- ❌ Real-time page generation under heavy load
- ❌ Sub-millisecond latency requirements

**For Tsuku's intended use case (data transformation), this trade-off is absolutely worth it.**

::: tip PERFORMANCE ROADMAP
Performance optimizations are planned for future releases, including:
- Compiled template caching (optional)
- Optimized parser with lookahead
- Expression compilation
- Template precompilation support

**Stay tuned for updates!**
:::

## Next Steps

- [Architecture →](/guide/architecture) - Understanding the compiler pipeline
- [Best Practices →](/guide/best-practices) - Writing efficient templates
- [Security →](/guide/security) - Performance vs. security trade-offs
