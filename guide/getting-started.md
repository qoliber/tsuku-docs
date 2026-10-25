# Getting Started

This guide will walk you through installing Tsuku and creating your first template.

## Prerequisites

- PHP 8.1 or higher
- Composer

## Installation

Install Tsuku via Composer:

```bash
composer require qoliber/tsuku
```

That's it! Tsuku has zero dependencies, so you're ready to go.

## Your First Template

Let's create a simple CSV export:

```php
<?php

require 'vendor/autoload.php';

use Qoliber\Tsuku\Tsuku;

// Create Tsuku instance
$tsuku = new Tsuku();

// Your data
$data = [
    'products' => [
        ['name' => 'Widget', 'price' => 29.99],
        ['name' => 'Gadget', 'price' => 39.99],
    ],
];

// Your template
$template = 'Name,Price
@for(products as product)
{product.name},${product.price}
@end';

// Process
$csv = $tsuku->process($template, $data);

echo $csv;
```

**Output:**
```csv
Name,Price
Widget,$29.99
Gadget,$39.99
```

## How It Works

### 1. Create a Tsuku Instance

```php
$tsuku = new Tsuku();
```

This creates a new template processor with default settings.

### 2. Prepare Your Data

```php
$data = [
    'products' => [
        ['name' => 'Widget', 'price' => 29.99],
        ['name' => 'Gadget', 'price' => 39.99],
    ],
];
```

Values can be arrays, objects, or a mix - `{product.name}` reads `$product['name']` from an array or calls `$product->getName()` on an object. Collections you loop over with `@for` must be PHP arrays: convert iterators and collection objects with `iterator_to_array()` or their `toArray()` method first (a generator or `ArrayIterator` renders nothing). For very large collections, see [Streaming Large Datasets](/guide/streaming).

### 3. Write Your Template

```php
$template = 'Name,Price
@for(products as product)
{product.name},${product.price}
@end';
```

Templates use:
- `{variable}` for simple output
- `@for` for loops
- `@if` / `@elseif` / `@else` for conditionals
- `@match` for pattern matching
- `@function()` for built-in and custom functions
- `@@` for a literal `@` (a bare `@` in template text, as in an e-mail address, is a syntax error)

Lines that hold only directives - like `@for(...)` and `@end` above - produce no output at all, not even an empty line, so you can indent them freely. See [Whitespace Control](/guide/whitespace-control).

### 4. Process the Template

```php
$csv = $tsuku->process($template, $data);
```

The `process()` method takes your template and data, returns the final output.

## Common Patterns

### Variables

Access data using dot notation:

```php
$template = 'Product: {product.name}
Price: ${product.price}
Category: {product.category.name}';

$data = ['product' => ['name' => 'Widget', 'price' => 29.99, 'category' => ['name' => 'Electronics']]];

echo $tsuku->process($template, $data);
// Output:
// Product: Widget
// Price: $29.99
// Category: Electronics
```

### Loops

Iterate over arrays:

```php
$template = '@for(items as item)
- {item.name}
@end';

$data = ['items' => [['name' => 'Apple'], ['name' => 'Banana']]];

echo $tsuku->process($template, $data);
// Output:
// - Apple
// - Banana
```

With keys (the value comes first, then the key):

```php
$template = '@for(items as item, index)
{index}. {item.name}
@end';

$data = ['items' => [['name' => 'Apple'], ['name' => 'Banana']]];

echo $tsuku->process($template, $data);
// Output:
// 0. Apple
// 1. Banana
```

### Conditionals

```php
$template = '@if(product.stock > 10)
In Stock
@elseif(product.stock > 0 and product.backorder)
Only {product.stock} left - more on the way
@elseif(product.stock > 0)
Only {product.stock} left
@else
Out of Stock
@end';

$data = ['product' => ['stock' => 3, 'backorder' => false]];

echo $tsuku->process($template, $data);
// Output: Only 3 left
```

Conditions support `==`, `!=`, `>`, `<`, `>=`, `<=`, `and`, `or`, `not`, parentheses and `in` - see [Conditionals](/guide/conditionals).

### Pattern Matching

```php
$template = '@match(status)
@case("active")
✓ Active
@case("pending")
⏳ Pending
@default
❌ Unknown
@end';

echo $tsuku->process($template, ['status' => 'pending']);
// Output: ⏳ Pending
```

### Functions

```php
$template = '@upper(product.name)
@number(product.price, 2)
@html(product.description)';

$data = ['product' => ['name' => 'widget', 'price' => 1234.5, 'description' => '<b>New!</b>']];

echo $tsuku->process($template, $data);
// Output:
// WIDGET
// 1,234.50
// &lt;b&gt;New!&lt;/b&gt;
```

## A More Complete Example

Let's build a product export with proper escaping and formatting:

```php
<?php

require 'vendor/autoload.php';

use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

$template = 'SKU,Name,Description,Price,Stock,Status
@for(products as product)
@csv(product.sku),@csv(product.name),@csv(product.description),$@number(product.price, 2),{product.stock},@match(product.status)
@case("active", "in_stock")
Available
@case("discontinued")
Discontinued
@default
Unknown
@end
@end';

$data = [
    'products' => [
        [
            'sku' => 'WID-001',
            'name' => 'Premium Widget',
            'description' => 'A "premium" widget with features',
            'price' => 129.99,
            'stock' => 50,
            'status' => 'active',
        ],
        [
            'sku' => 'GAD-002',
            'name' => 'Basic Gadget',
            'description' => 'Simple gadget',
            'price' => 29.50,
            'stock' => 0,
            'status' => 'discontinued',
        ],
    ],
];

$csv = $tsuku->process($template, $data);

// Save to file
file_put_contents('products.csv', $csv);

echo $csv;
```

**Output:**
```csv
SKU,Name,Description,Price,Stock,Status
WID-001,Premium Widget,"A ""premium"" widget with features",$129.99,50,Available
GAD-002,Basic Gadget,Simple gadget,$29.50,0,Discontinued
```

Notice how:
- `@csv()` properly escapes commas and quotes
- `@number()` formats prices
- `@match` handles status values - it starts on the CSV row, so its result stays on that row
- Everything works together seamlessly

## Next Steps

Now that you've created your first template, explore:

- [Template Syntax →](/guide/variables) - Learn all syntax features
- [Built-in Functions →](/guide/built-in-functions) - Discover available functions
- [Examples →](/examples/) - See real-world use cases
- [Security →](/guide/security) - Learn about escaping and safety

## Quick Reference

| Syntax | Purpose | Example |
|--------|---------|---------|
| `{var}` | Output variable | `{product.name}` |
| `@for` | Loop | `@for(items as item)` |
| `@if/@elseif/@else` | Conditional | `@if(stock > 0 and visible)` |
| `@unless` | Inverse conditional | `@unless(sold_out)` |
| `@?{...}` | Inline condition | `@?{stock > 0 "In stock" : "Sold out"}` |
| `@match/@case` | Pattern matching | `@match(status)` |
| `@function()` | Call function | `@upper(text)` |
| `@html()` | HTML escape | `@html(user_input)` |
| `@csv()` | CSV escape | `@csv(product.name)` |
| `@number()` | Format number | `@number(price, 2)` |
| `@@` | Literal `@` | `sales@@example.com` |

Happy templating! 🎉
