# Best Practices

Effective patterns and recommendations for using Tsuku in production.

## Template Organization

### Keep Templates Simple

**Good - Simple and readable:**
```php
$template = 'Name,Email,Status
@for(users as user)
{user.name},{user.email},{user.status}
@end';
```

**Bad - Too complex:**
```php
$template = '@for(users as user)@if(user.active)@if(user.email)@if(user.verified){user.name}@end@end@end@end';
```

### Break Down Complex Templates

For complex output, use multiple templates:

```php
// Header template
$header = 'Product Report - @date("F j, Y")

SKU,Name,Price,Stock';

// Row template
$row = '@csv(product.sku),@csv(product.name),@number(product.price, 2),{product.stock}';

// Build complete output
$output = $tsuku->process($header, []);
foreach ($products as $product) {
    $output .= "\n" . $tsuku->process($row, ['product' => $product]);
}
```

### Use Consistent Formatting

**Readable formatting:**
```php
$template = '@for(orders as order)
Order #{order.id}
Customer: {order.customer.name}
Total: $@number(order.total, 2)
Status: @match(order.status)
    @case("pending")Pending
    @case("shipped")Shipped
    @case("delivered")Delivered
    @default Unknown
@end

@end';
```

## Security Best Practices

### Always Escape User Input

**Critical Rule:** Escape **ALL** user-provided data.

```php
// ❌ WRONG - XSS vulnerability
$template = '<div class="comment">{user.comment}</div>';

// ✅ CORRECT - XSS protected
$template = '<div class="comment">@html(user.comment)</div>';
```

### Choose the Right Escaper

| Output Format | Use |
|---------------|-----|
| HTML content | `@html()` |
| HTML attributes | `@html()` |
| XML | `@xml()` |
| CSV | `@csv()` |
| JSON | `@json()` |
| URLs | `@url()` |

### Validate Before Templating

```php
// Validate data before processing
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new InvalidArgumentException('Invalid email');
}

// Then use in template (still escape!)
$template = 'Email: @html(email)';
```

### Never Build SQL with Templates

```php
// ❌ NEVER DO THIS - SQL injection risk
$template = 'SELECT * FROM users WHERE name = "{name}"';

// ✅ Use prepared statements instead
$stmt = $pdo->prepare('SELECT * FROM users WHERE name = ?');
$stmt->execute([$name]);
```

## Performance Optimization

### Reuse Tsuku Instance

**Good - Reuse instance:**
```php
$tsuku = new Tsuku();

foreach ($batches as $batch) {
    $output = $tsuku->process($template, $batch);
    // Process output...
}
```

**Bad - Creating new instance every time:**
```php
foreach ($batches as $batch) {
    $tsuku = new Tsuku(); // Wasteful!
    $output = $tsuku->process($template, $batch);
}
```

### Cache Parsed Templates

For frequently used templates, parse once and compile multiple times:

```php
$tsuku = new Tsuku();

// Parse template once
$lexer = new \Qoliber\Tsuku\Lexer\Lexer($template);
$tokens = $lexer->tokenize();
$parser = new \Qoliber\Tsuku\Ast\Parser($tokens);
$ast = $parser->parse();

// Reuse AST for multiple data sets
foreach ($dataSets as $data) {
    $compiler = new \Qoliber\Tsuku\Compiler\Compiler($data);
    $output = $compiler->compile($ast);
    // Process output...
}
```

### Register Functions Once

```php
class TemplateService
{
    private Tsuku $tsuku;

    public function __construct()
    {
        $this->tsuku = new Tsuku();

        // Register all functions once
        $this->registerFunctions();
    }

    private function registerFunctions(): void
    {
        $this->tsuku->registerFunction('currency', function(float $amount): string {
            return ' . number_format($amount, 2);
        });

        // Register other functions...
    }

    public function process(string $template, array $data): string
    {
        return $this->tsuku->process($template, $data);
    }
}
```

## Data Preparation

### Prepare Data Before Templating

Transform data into template-friendly structure:

```php
// Transform complex objects into simple arrays
$data = [
    'products' => array_map(function($product) {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getFinalPrice(),
            'stock' => $product->getStockQty(),
        ];
    }, $productCollection)
];

$template = '@for(products as product)
{product.id},{product.name},{product.price}
@end';
```

### Use Smart Object Access

Tsuku automatically calls getters:

```php
class Product
{
    public function getName(): string { return $this->name; }
    public function getPrice(): float { return $this->price; }
    public function isAvailable(): bool { return $this->available; }
}

// Template automatically uses getName(), getPrice(), isAvailable()
$template = '@if(product.available)
{product.name}: $@number(product.price, 2)
@end';
```

## Error Handling

### Provide Fallback Values

Use `@default()` for missing data:

```php
$template = 'Name: @default(user.name, "Unknown")
Email: @default(user.email, "N/A")';
```

### Validate Template Syntax

Catch syntax errors early:

```php
try {
    $result = $tsuku->process($template, $data);
} catch (\Qoliber\Tsuku\Exception\TemplateException $e) {
    // Log error and use fallback
    error_log("Template error: " . $e->getMessage());
    $result = "Error processing template";
}
```

### Test with Edge Cases

```php
$edgeCases = [
    'empty_array' => [],
    'null_values' => ['name' => null],
    'special_chars' => ['text' => '<script>alert("XSS")</script>'],
    'unicode' => ['text' => '你好世界 🌍'],
];

foreach ($edgeCases as $name => $data) {
    $result = $tsuku->process($template, $data);
    // Verify output...
}
```

## Common Patterns

### CSV Export Pattern

```php
$template = 'SKU,Name,Description,Price,Stock
@for(products as product)
@csv(product.sku),@csv(product.name),@csv(product.description),@number(product.price, 2),{product.stock}
@end';

$result = $tsuku->process($template, ['products' => $products]);
file_put_contents('export.csv', $result);
```

### XML Feed Pattern

```php
$template = '<?xml version="1.0" encoding="UTF-8"?>
<products>
@for(products as product)
    <product id="{product.id}">
        <name>@xml(product.name)</name>
        <description>@xml(product.description)</description>
        <price>@number(product.price, 2)</price>
        <availability>@match(product.stock)
            @case(0)out_of_stock
            @default in_stock
        @end</availability>
    </product>
@end
</products>';
```

### JSON API Response Pattern

```php
$template = '{
    "success": true,
    "data": [
@for(items as item, index)
        {
            "id": {item.id},
            "name": @json(item.name),
            "description": @json(item.description)
        }@if(index < length(items) - 1),@end
@end
    ]
}';
```

### Email Template Pattern

```php
$template = 'Dear @html(customer.name),

Your order #@number(order.id, 0, "", "") has been confirmed.

Order Details:
@for(order.items as item)
- @html(item.name) x{item.qty} - $@number(item.price, 2)
@end

Total: $@number(order.total, 2)

Thank you for your purchase!';
```

## Custom Functions Best Practices

### Type Hints and Return Types

```php
// ✅ Good - Clear types
$tsuku->registerFunction('format_phone', function(string $phone): string {
    return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $phone);
});

// ❌ Bad - No types
$tsuku->registerFunction('format_phone', function($phone) {
    return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $phone);
});
```

### Pure Functions

Keep functions side-effect free:

```php
// ✅ Good - Pure function
$tsuku->registerFunction('format_sku', function(string $sku): string {
    return strtoupper(trim($sku));
});

// ❌ Bad - Side effects
$tsuku->registerFunction('log_product', function(string $id): string {
    Database::insert('logs', ['product_id' => $id]); // Don't do this!
    return $id;
});
```

### Escape Output in Functions

```php
$tsuku->registerFunction('user_badge', function(string $name, string $role): string {
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeRole = htmlspecialchars($role, ENT_QUOTES, 'UTF-8');
    return "<span class=\"badge\">{$safeName} - {$safeRole}</span>";
});
```

## Testing Templates

### Unit Test Your Templates

```php
use PHPUnit\Framework\TestCase;

class ProductExportTest extends TestCase
{
    private Tsuku $tsuku;

    protected function setUp(): void
    {
        $this->tsuku = new Tsuku();
    }

    public function testCsvExport(): void
    {
        $template = 'SKU,Name
@for(products as product)
@csv(product.sku),@csv(product.name)
@end';

        $data = [
            'products' => [
                ['sku' => 'ABC-123', 'name' => 'Widget'],
                ['sku' => 'XYZ-789', 'name' => 'Gadget'],
            ]
        ];

        $result = $this->tsuku->process($template, $data);

        $this->assertStringContainsString('ABC-123,Widget', $result);
        $this->assertStringContainsString('XYZ-789,Gadget', $result);
    }

    public function testXssProtection(): void
    {
        $template = '@html(text)';
        $data = ['text' => '<script>alert("XSS")</script>'];

        $result = $this->tsuku->process($template, $data);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }
}
```

### Test Edge Cases

```php
public function testEmptyArray(): void
{
    $template = '@for(items as item){item}@end';
    $result = $this->tsuku->process($template, ['items' => []]);
    $this->assertEmpty($result);
}

public function testNestedLoops(): void
{
    $template = '@for(categories as category)
@for(category.products as product)
{product.name}
@end
@end';

    $data = [
        'categories' => [
            ['products' => [['name' => 'A'], ['name' => 'B']]],
            ['products' => [['name' => 'C']]],
        ]
    ];

    $result = $this->tsuku->process($template, $data);
    $this->assertStringContainsString('A', $result);
    $this->assertStringContainsString('B', $result);
    $this->assertStringContainsString('C', $result);
}
```

## Anti-Patterns to Avoid

### ❌ Don't Put Logic in Templates

```php
// Bad - Complex logic in template
$template = '@if(price > 100)@if(discount > 0)@if(category == "electronics")
Discounted Price
@end@end@end';

// Good - Pre-compute in PHP
$data = [
    'show_discount' => $price > 100 && $discount > 0 && $category === 'electronics'
];
$template = '@if(show_discount)Discounted Price@end';
```

### ❌ Don't Reparse Same Template

```php
// Bad - Parsing in loop
foreach ($items as $item) {
    $tsuku->process($template, ['item' => $item]); // Reparses every time!
}

// Good - Process all at once
$tsuku->process($template, ['items' => $items]);
```

### ❌ Don't Mix Output Formats

```php
// Bad - Mixing HTML and CSV
$template = '<div>@csv(text)</div>'; // Wrong escaping!

// Good - One format per template
$htmlTemplate = '<div>@html(text)</div>';
$csvTemplate = '@csv(text)';
```

## Deployment Checklist

Before deploying templates to production:

- [ ] All user input is escaped with appropriate function
- [ ] No SQL queries built with templates
- [ ] Templates are tested with edge cases
- [ ] Custom functions are pure (no side effects)
- [ ] Error handling is in place
- [ ] Performance has been considered
- [ ] Templates are documented
- [ ] Code has been reviewed

## Next Steps

- [Examples →](/examples/) - See best practices in action
- [Security →](/guide/security) - Deep dive into security
- [API Reference →](/api/) - Complete API documentation
