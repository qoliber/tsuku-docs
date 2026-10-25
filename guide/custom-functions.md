# Custom Functions

Extend Tsuku with your own custom functions to add domain-specific logic to your templates.

A function is called as `@name(arguments)` and its return value is printed in place of the call. To wrap a whole *block* of template text instead (`@name(...) ... @end`), register a [custom directive](/guide/custom-directives).

## Registering a Function

Use `registerFunction()` to add custom functions. It returns the `Tsuku` instance, so registrations can be chained:

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

$tsuku->registerFunction('greet', function(string $name): string {
    return "Hello, {$name}!";
});

$template = '@greet("World")';
$result = $tsuku->process($template, []);
// Output: Hello, World!
```

```php
$tsuku
    ->registerFunction('first_word', fn(string $text): string => strtok($text, ' '))
    ->registerFunction('initial', fn(string $text): string => substr($text, 0, 1) . '.');

$result = $tsuku->process('@first_word(name) @initial(surname)', ['name' => 'Mary Ann', 'surname' => 'Smith']);
// Output: Mary S.
```

## Function with Multiple Parameters

```php
$tsuku->registerFunction('currency', function(float $amount, string $code = 'USD'): string {
    return match($code) {
        'USD' => '$' . number_format($amount, 2),
        'EUR' => '€' . number_format($amount, 2),
        'GBP' => '£' . number_format($amount, 2),
        default => $code . ' ' . number_format($amount, 2),
    };
});

$template = 'Total: @currency(product.price, "EUR")';
$data = ['product' => ['price' => 99.99]];
$result = $tsuku->process($template, $data);
// Output: Total: €99.99
```

## HTML-Generating Functions

Create functions that return HTML:

```php
$tsuku->registerFunction('badge', function(string $text, string $color = 'blue'): string {
    return "<span class=\"badge badge-{$color}\">{$text}</span>";
});

$template = 'Status: @badge(status, "green")';
$data = ['status' => 'Active'];
$result = $tsuku->process($template, $data);
// Output: Status: <span class="badge badge-green">Active</span>
```

## Array-Processing Functions

Work with array data:

```php
$tsuku->registerFunction('pluck', function(array $items, string $key): array {
    return array_column($items, $key);
});

$template = 'Names: @join(@pluck(users, "name"), ", ")';
$data = [
    'users' => [
        ['name' => 'Alice', 'age' => 30],
        ['name' => 'Bob', 'age' => 25],
        ['name' => 'Charlie', 'age' => 35],
    ],
];
$result = $tsuku->process($template, $data);
// Output: Names: Alice, Bob, Charlie
```

## Variadic Functions

Accept variable number of arguments:

```php
$tsuku->registerFunction('format_list', function(...$items): string {
    if (count($items) === 0) {
        return '';
    }
    if (count($items) === 1) {
        return $items[0];
    }
    $last = array_pop($items);
    return implode(', ', $items) . ' and ' . $last;
});

$template = 'Ingredients: @format_list("flour", "sugar", "eggs", "milk")';
$result = $tsuku->process($template, []);
// Output: Ingredients: flour, sugar, eggs and milk
```

## Closures with External Data

Use closures to access configuration:

```php
$config = [
    'site_name' => 'My Website',
    'domain' => 'example.com',
];

$tsuku->registerFunction('site_url', function(string $path = '') use ($config): string {
    return 'https://' . $config['domain'] . '/' . ltrim($path, '/');
});

$template = 'Visit: @site_url("products")';
$result = $tsuku->process($template, []);
// Output: Visit: https://example.com/products
```

## Date Formatting Functions

Custom date formatting:

```php
$tsuku->registerFunction('format_date', function(string|int $date, string $format = 'Y-m-d'): string {
    $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
    return date($format, $timestamp);
});

$template = 'Published: @format_date(article.published_at, "F j, Y")';
$data = ['article' => ['published_at' => '2024-01-15']];
$result = $tsuku->process($template, $data);
// Output: Published: January 15, 2024
```

## Slug Generation

```php
$tsuku->registerFunction('slug', function(string $text): string {
    return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $text), '-'));
});

$template = 'URL: /blog/@slug(article.title)';
$data = ['article' => ['title' => 'Hello World!']];
$result = $tsuku->process($template, $data);
// Output: URL: /blog/hello-world
```

## Markdown to HTML

```php
$tsuku->registerFunction('markdown', function(string $text): string {
    // Simple markdown (use real library in production)
    $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
    return $html;
});

$template = '@markdown(content)';
$data = ['content' => 'This is **bold** and *italic* text'];
$result = $tsuku->process($template, $data);
// Output: This is <strong>bold</strong> and <em>italic</em> text
```

## Image Thumbnail Function

```php
$tsuku->registerFunction('thumbnail', function(string $image, int $width, int $height): string {
    return "/thumbnails/{$width}x{$height}/{$image}";
});

$template = '<img src="@thumbnail(product.image, 300, 300)" alt="{product.name}">';
$data = ['product' => ['image' => 'widget.jpg', 'name' => 'Widget']];
$result = $tsuku->process($template, $data);
// Output: <img src="/thumbnails/300x300/widget.jpg" alt="Widget">
```

The numbers `300` reach the function as the strings `"300"`; the `int` parameter types convert them (see [How Arguments Are Passed](#how-arguments-are-passed)).

## Nesting Custom Functions

Custom functions work with built-in functions:

```php
$tsuku->registerFunction('full_name', function(string $first, string $last): string {
    return "{$first} {$last}";
});

$template = '@upper(@full_name(user.first_name, user.last_name))';
$data = ['user' => ['first_name' => 'john', 'last_name' => 'doe']];
$result = $tsuku->process($template, $data);
// Output: JOHN DOE
```

## Real-World Example: Price Formatting

```php
$tsuku->registerFunction('price', function(float $amount, string $currency = 'USD'): string {
    $symbol = match($currency) {
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        default => $currency . ' ',
    };

    return $symbol . number_format($amount, 2);
});

$template = '@for(products as product)
{product.name}: @price(product.price, "EUR")
@end';

$data = [
    'products' => [
        ['name' => 'Widget', 'price' => 29.99],
        ['name' => 'Gadget', 'price' => 199.50],
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// Widget: €29.99
// Gadget: €199.50
```

## Example: Status Badge Component

```php
$tsuku->registerFunction('status_badge', function(string $status): string {
    $config = [
        'active' => ['color' => 'success', 'icon' => '✓', 'text' => 'Active'],
        'pending' => ['color' => 'warning', 'icon' => '⏳', 'text' => 'Pending'],
        'inactive' => ['color' => 'danger', 'icon' => '✗', 'text' => 'Inactive'],
    ];

    $badge = $config[$status] ?? ['color' => 'secondary', 'icon' => '?', 'text' => 'Unknown'];

    return sprintf(
        '<span class="badge badge-%s">%s %s</span>',
        $badge['color'],
        $badge['icon'],
        $badge['text']
    );
});

$template = '@for(users as user)
{user.name}: @status_badge(user.status)
@end';

$data = [
    'users' => [
        ['name' => 'Alice', 'status' => 'active'],
        ['name' => 'Bob', 'status' => 'banned'],
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// Alice: <span class="badge badge-success">✓ Active</span>
// Bob: <span class="badge badge-secondary">? Unknown</span>
```

## Example: Conditional Discount

```php
$tsuku->registerFunction('apply_discount', function(float $price, float $discount = 0): float {
    if ($discount > 0) {
        return $price * (1 - $discount / 100);
    }
    return $price;
});

$template = '@for(products as product)
{product.name}: $@number(@apply_discount(product.price, product.discount), 2)
@end';

$data = [
    'products' => [
        ['name' => 'Widget', 'price' => 100, 'discount' => 10],
        ['name' => 'Gadget', 'price' => 50, 'discount' => 0],
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// Widget: $90.00
// Gadget: $50.00
```

::: warning Missing values and typed parameters
A variable that does not exist is passed as an empty string `""`. Had the Gadget row no `discount` key at all, `float $discount` would reject `""` with a `TypeError`; Tsuku treats that as a function error, so in the default SILENT mode the whole `@number(...)` prints nothing (`Gadget: $`). Give such rows a value in the data, or accept `mixed` and cast inside the function. See [Strictness Modes & Errors](/guide/strictness).
:::

## Arithmetic

Templates have no arithmetic operators: `{price * qty}` is printed as literal text, and `@if(qty - 1 > 0)` throws a `ParseException`. Compute values in PHP before rendering (usually the simplest option) or register small functions:

```php
$tsuku->registerFunction('multiply', fn($a, $b) => $a * $b);
$tsuku->registerFunction('sum', fn(array $rows, string $key) => array_sum(array_column($rows, $key)));

$template = '@for(order.items as item)
{item.name}: {item.qty} x @number(item.price, 2) = @number(@multiply(item.qty, item.price), 2)
@end
Total: @number(@sum(order.items, "line_total"), 2)';

$data = [
    'order' => [
        'items' => [
            ['name' => 'Widget', 'qty' => 3, 'price' => 9.99, 'line_total' => 29.97],
            ['name' => 'Gadget', 'qty' => 1, 'price' => 24.50, 'line_total' => 24.50],
        ],
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// Widget: 3 x 9.99 = 29.97
// Gadget: 1 x 24.50 = 24.50
// Total: 54.47
```

PHP converts the numeric strings `"3"` and `"9.99"` to numbers for `*`; untyped parameters are fine here.

## How Arguments Are Passed

Each argument is resolved before your function is called:

| Argument in the template | Your function receives |
|--------------------------|------------------------|
| `"text"` or `'text'` | the string, quotes removed; `\"` inside `"..."` and `\'` inside `'...'` are quotes, every other backslash is kept as written |
| `42`, `4.5` | the **string** `"42"`, `"4.5"` |
| `product.qty`, `items` | the value with its PHP type (int, array, object, bool, ...) |
| a variable that does not exist | `""` (empty string) |
| `@upper(name)` | the result of the nested call |
| `@unknown(x)` | `""` (an unregistered nested function is treated like a missing variable) |

```php
$tsuku->registerFunction('type', fn($value) => get_debug_type($value));

$template = '@type(42) @type("42") @type(qty) @type(items) @type(missing) @type(flag)';
$data = ['qty' => 42, 'items' => [1, 2], 'flag' => true];
$result = $tsuku->process($template, $data);
// Output: string string int array string bool
```

Typed parameters convert the strings for you: `int $width` receives `300` from `@thumbnail(img, 300, 300)`. Arguments are separated by commas outside quotes and parentheses, so `@concat("a, b", x)` passes two arguments.

## Return Values

A return type is not required. The value is converted to a string:

| Returned | Printed |
|----------|---------|
| string | as is |
| int / float | `42`, `0.3` |
| `true` / `false` | `1` / nothing |
| `null` | nothing |
| object with `__toString()` | its string |
| array | `Array` (plus a PHP "Array to string conversion" warning) - return a string instead |
| other objects | nothing; the conversion error is handled like any function error |

Return arrays only from functions that feed other functions, such as `@join(@pluck(users, "name"), ", ")` above.

## Function Names

Names are case-sensitive and must look like identifiers (`[a-zA-Z_][a-zA-Z0-9_]*`); `@` must be followed directly by the name and the name directly by `(`. `registerFunction()` throws an `InvalidArgumentException` for a name that the template could never call:

| Name | Why it cannot be called |
|------|-------------------------|
| `my-fn`, `my.fn`, `2col` | not an identifier |
| `if`, `for`, `unless`, `match`, `case`, `elseif`, `else`, `end` | built-in directives |
| anything starting with `end` (`endpoint`) | `@endpoint(...)` is read as `@end` followed by the text `point(...)` |
| anything starting with `else` (`else_label`) | read as `@else` |
| anything starting with `default` except `default` itself (`default_price`) | read as `@default` |

```php
$tsuku->registerFunction('endpoint', fn($path) => '/api/' . $path);
// Throws: InvalidArgumentException: Function name 'endpoint' cannot be called: @endpoint(...) is read as the @end directive followed by text. Choose a name that does not start with 'end'
```

Names such as `iffy`, `forward`, `cases` or `format123` are fine. Up to 1.4.0 these names were accepted and the function never ran.

You can replace a built-in function by registering the same name:

```php
$tsuku->registerFunction('upper', fn($text) => mb_strtoupper((string) $text));

$result = $tsuku->process('@upper(city)', ['city' => 'Kraków']);
// Output: KRAKÓW
```

A [custom directive](/guide/custom-directives) with the same name as a function takes precedence over it.

## Functions in Conditions

Registered functions can be called inside `@if`, `@unless`, `@elseif` and `@?{...}` conditions. The result is compared or tested for truthiness:

```php
$tsuku->registerFunction('is_vip', fn(array $customer): bool => ($customer['orders'] ?? 0) >= 10);
$tsuku->registerFunction('margin', fn($price, $cost) => ($price - $cost) / $price * 100);

$template = '@if(@is_vip(customer))VIP @end@if(@margin(price, cost) > 30)high margin@end';
$data = ['customer' => ['orders' => 12], 'price' => 100, 'cost' => 50];
$result = $tsuku->process($template, $data);
// Output: VIP high margin
```

Two rules differ from functions in output:

- An unknown function in a condition throws `FunctionNotFoundException` when the template is parsed, in every strictness mode (in output, an unknown function prints nothing in SILENT mode).
- An exception thrown by your function inside a condition follows the strictness mode, as in the output, and the condition sees `null` as the result in `SILENT` and `WARNING` mode (see [Functions that throw inside conditions](/guide/strictness#functions-that-throw-inside-conditions)).

See [Conditionals](/guide/conditionals) and [Strictness Modes & Errors](/guide/strictness).

## Best Practices

### 1. Type Hints

Type hints document what a function expects, and `int` / `float` parameters convert the numeric strings that template numbers arrive as:

```php
$tsuku->registerFunction('add', function(int $a, int $b): int {
    return $a + $b;
});
```

Remember that a missing variable arrives as `""`, which `int` and `float` parameters reject (see the warning in [Conditional Discount](#example-conditional-discount)). Use untyped or `mixed` parameters where a value may be missing.

### 2. Return Strings

Return strings or scalars; Tsuku converts scalars automatically:

```php
// Good
$tsuku->registerFunction('count', function(array $items): string {
    return (string) count($items);
});

// Also OK (auto-converted)
$tsuku->registerFunction('count', function(array $items): int {
    return count($items);
});
```

### 3. Escape HTML

If generating HTML, consider escaping user input:

```php
$tsuku->registerFunction('alert', function(string $message): string {
    $safe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    return "<div class=\"alert\">{$safe}</div>";
});
```

### 4. Document Your Functions

```php
/**
 * Format a phone number
 *
 * @param string $phone Raw phone number
 * @param string $format Output format (us, uk, intl)
 * @return string Formatted phone number
 */
$tsuku->registerFunction('phone', function(string $phone, string $format = 'us'): string {
    // Implementation...
    return $phone;
});
```

### 5. Keep Functions Pure

Avoid side effects (database queries, file writes):

```php
// Good - pure function
$tsuku->registerFunction('format_sku', function(string $sku): string {
    return strtoupper($sku);
});

// Bad - side effect
$tsuku->registerFunction('log_view', function(string $product_id): string {
    Database::log('product_view', $product_id); // DON'T DO THIS
    return $product_id;
});
```

## Function Registry Reference

Functions live in a `Qoliber\Tsuku\Function\FunctionRegistry`. `Tsuku` creates its own registry and does not expose it. To share one set of functions between several `Tsuku` instances, or to check what is registered, create the registry yourself and pass it as the fourth constructor argument (`functionRegistry`):

```php
use Qoliber\Tsuku\Function\FunctionRegistry;
use Qoliber\Tsuku\Tsuku;

$registry = new FunctionRegistry(); // already contains the built-in functions
$registry->register('slug', fn(string $text): string => strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $text), '-')));

$csv = new Tsuku(functionRegistry: $registry);
$xml = new Tsuku(functionRegistry: $registry);

$registry->has('slug');              // true
$registry->has('upper');             // true (built-in)
$registry->execute('upper', ['hi']); // "HI"
$registry->execute('nope', []);      // throws FunctionNotFoundException
```

`registerFunction()` on either instance registers into the shared registry. The registry has `register()`, `has()` and `execute()`; there is no method to list or remove functions.

## Next Steps

- [Custom Directives →](/guide/custom-directives) - Wrap blocks of a template with your own `@name ... @end`
- [Widget Support →](/guide/widget-support) - Magento-style widgets
- [Built-in Functions →](/guide/built-in-functions) - See all built-in functions
- [Examples →](/examples/) - See custom functions in action
