# Custom Functions

Extend Tsuku with your own custom functions to add domain-specific logic to your templates.

## Registering a Function

Use `registerFunction()` to add custom functions:

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
    return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($text)));
});

$template = 'URL: /blog/@slug(article.title)';
$data = ['article' => ['title' => 'Hello World!']];
$result = $tsuku->process($template, $data);
// Output: URL: /blog/hello-world-
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
```

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
```

## Best Practices

### 1. Type Hints

Always use PHP type hints:

```php
// Good
$tsuku->registerFunction('add', function(int $a, int $b): int {
    return $a + $b;
});

// Bad
$tsuku->registerFunction('add', function($a, $b) {
    return $a + $b;
});
```

### 2. Return Strings

Functions should return strings (Tsuku converts them automatically):

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

The `FunctionRegistry` class manages all functions. You can also check if a function exists:

```php
if ($tsuku->getFunctionRegistry()->has('my_function')) {
    // Function exists
}
```

## Next Steps

- [Widget Support →](/guide/widget-support) - Magento-style widgets
- [Built-in Functions →](/guide/built-in-functions) - See all built-in functions
- [Examples →](/examples/) - See custom functions in action
