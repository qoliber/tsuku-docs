# Best Practices

Effective patterns and recommendations for using Tsuku in production.

## Template Organization

### Keep Templates Simple

**Good - Simple and readable:**
```php
$template = 'Name,Email,Status
@for(users as user)
@csv(user.name),@csv(user.email),{user.status}
@end';

$data = ['users' => [
    ['name' => 'Ann', 'email' => 'ann@example.com', 'status' => 'active'],
    ['name' => 'Bob, Jr.', 'email' => 'bob@example.com', 'status' => 'pending'],
]];
echo $tsuku->process($template, $data);
// Output:
// Name,Email,Status
// Ann,ann@example.com,active
// "Bob, Jr.",bob@example.com,pending
```

**Bad - Too complex:**
```php
$template = '@for(users as user)@if(user.active)@if(user.email)@if(user.verified){user.name}@end@end@end@end';
```

**Better - one condition, one line per row:**
```php
$template = '@for(users as user)
@if(user.active and user.email and user.verified)
{user.name}
@end
@end';

$data = ['users' => [
    ['name' => 'Ann', 'active' => true, 'email' => 'ann@example.com', 'verified' => true],
    ['name' => 'Bob', 'active' => true, 'email' => '', 'verified' => true],
]];
echo $tsuku->process($template, $data);
// Output: Ann
```

Since 1.4.0 conditions understand `and`/`or`/`not` (and `&&`/`||`/`!`), parentheses and `in` - see [Conditionals](/guide/conditionals). Lines that hold only directives produce no output, so the directives can sit on their own lines.

### Break Down Complex Templates

For complex output, write the parts as separate strings and **combine them into one template** before processing. One `process()` call parses the template once and renders every row:

```php
// Header part
$header = 'Product Report - @date("F j, Y", generated_at)

SKU,Name,Price,Stock';

// Row part
$row = '@csv(product.sku),@csv(product.name),@number(product.price, 2, ".", ""),{product.stock}';

// One template, one call
$template = $header . "\n@for(products as product)\n" . $row . "\n@end";

$output = $tsuku->process($template, [
    'generated_at' => 1761350400,
    'products' => $products,
]);
```

With `$products = [['sku' => 'WID-001', 'name' => 'Widget', 'price' => 1299.5, 'stock' => 5], ['sku' => 'GAD-002', 'name' => 'Gadget, Deluxe', 'price' => 29.99, 'stock' => 0]]` the output is:

```
Product Report - October 25, 2025

SKU,Name,Price,Stock
WID-001,Widget,1299.50,5
GAD-002,"Gadget, Deluxe",29.99,0
```

Calling `process()` once per row would parse the row template again for every product (see [Performance](/guide/performance)). Note `@number(price, 2, ".", "")`: the default thousands separator (`1,299.50`) would split a CSV column in two.

### Use Consistent Formatting

Indent templates the way you indent code. Whitespace control (1.4.0) drops the indentation of directive-only lines and of `@case`/`@default`/`@else`/`@end`, so the output stays clean:

**Readable formatting:**
```php
$template = '@for(orders as order)
Order #{order.id}
Customer: {order.customer.name}
Total: $@number(order.total, 2)
Status: @match(order.status)
    @case("pending")
        Pending
    @case("shipped")
        Shipped
    @case("delivered")
        Delivered
    @default
        Unknown
@end

@end';

$data = ['orders' => [
    ['id' => 1001, 'customer' => ['name' => 'Ann'], 'total' => 59.9, 'status' => 'shipped'],
    ['id' => 1002, 'customer' => ['name' => 'Bob'], 'total' => 5, 'status' => 'lost'],
]];
echo $tsuku->process($template, $data);
```

**Output:**
```
Order #1001
Customer: Ann
Total: $59.90
Status: Shipped

Order #1002
Customer: Bob
Total: $5.00
Status: Unknown

```

Put each case body on its own line, as above. A body written on the directive's line keeps its spaces: `@default Unknown` would print `Status:  Unknown` (two spaces). The rules are on the [Whitespace Control](/guide/whitespace-control) page; you no longer need `\` line continuations just to keep indented directives out of the output.

## Security Best Practices

### Always Escape User Input

**Critical Rule:** Escape **ALL** user-provided data.

```php
$data = ['user' => ['comment' => '<script>alert(1)</script>']];

// ❌ WRONG - XSS vulnerability
$template = '<div class="comment">{user.comment}</div>';
// Output: <div class="comment"><script>alert(1)</script></div>

// ✅ CORRECT - XSS protected
$template = '<div class="comment">@html(user.comment)</div>';
// Output: <div class="comment">&lt;script&gt;alert(1)&lt;/script&gt;</div>
```

### Choose the Right Escaper

| Output Format | Use |
|---------------|-----|
| HTML content | `@html()` |
| HTML attributes | `@html()` |
| XML | `@xml()` |
| CSV | `@csv()` |
| JSON | `@json()` (it adds the quotes itself) |
| URLs | `@url()` |

### Validate Before Templating

```php
// Validate data before processing
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new InvalidArgumentException('Invalid email');
}

// Then use in template (still escape!)
$template = 'Email: @html(email)';
$data = ['email' => 'ann@example.com'];
// Output: Email: ann@example.com
```

### Never Build SQL with Templates

```php
// ❌ NEVER DO THIS - SQL injection risk
$template = 'SELECT * FROM users WHERE name = "{name}"';

// ✅ Use prepared statements instead
$stmt = $pdo->prepare('SELECT * FROM users WHERE name = ?');
$stmt->execute([$name]);
```

### Write `@@` for a Literal `@`

Every `@` in template text starts a directive or function, so an e-mail address or a CSS `@media` rule in a template throws a `ParseException`. Double it:

```php
$template = 'Questions? Write to support@@example.com';
// Output: Questions? Write to support@example.com

$template = 'Questions? Write to support@example.com';
// Throws: ParseException: Invalid @ syntax at line 1, column 28 (write @@ for a literal @)
```

Values from your data are never parsed, so an `@` in `{user.email}` needs no escaping. See [Escapes & Special Characters](/guide/escaping).

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

`process()` parses the template on every call; there is no built-in template cache. For a template that you render many separate times, parse it once and reuse the tree:

```php
use Qoliber\Tsuku\Compiler\Compiler;
use Qoliber\Tsuku\DirectiveRegistry;
use Qoliber\Tsuku\Function\FunctionRegistry;
use Qoliber\Tsuku\ProcessingContext;
use Qoliber\Tsuku\StrictnessMode;
use Qoliber\Tsuku\Template\TemplateParser;

$functions = new FunctionRegistry();          // built-in functions; register() your own here
$directives = new DirectiveRegistry();        // register() custom directives here
$context = new ProcessingContext(StrictnessMode::SILENT);

// Parse template once
$ast = (new TemplateParser($functions, $directives))->parse($template);

// Reuse AST for multiple data sets
foreach ($dataSets as $data) {
    $compiler = new Compiler($data, $context, $functions, $directives);
    $output = $compiler->compile($ast);
    // Process output...
}
```

Functions and directives registered on a `Tsuku` instance are not visible here - register them on the registries you pass in. For one big export (one `process()` call with all rows) this makes no difference; see [Performance](/guide/performance#_4-parse-once-render-many-times).

### Stream Large Exports

For tens of thousands of rows use [`processToStream()`](/guide/streaming): it renders the template row by row from any iterable (for example a generator reading from the database) and hands each piece to a callback, so neither the rows nor the output have to fit in memory.

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
            return '$' . number_format($amount, 2);
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

### Compute Values in PHP

Templates have **no arithmetic**: `{item.qty * item.price}` is printed as literal text, and `x - 1` inside a condition throws a `ParseException`. Compute totals, margins and counters in PHP and pass them in:

```php
$template = '@for(items as item)
{item.name}: {item.qty} x $@number(item.price, 2) = $@number(item.line_total, 2)
@end';

$data = ['items' => array_map(
    fn(array $item): array => $item + ['line_total' => $item['qty'] * $item['price']],
    [['name' => 'Widget', 'qty' => 2, 'price' => 9.5], ['name' => 'Gadget', 'qty' => 1, 'price' => 20]]
)];
echo $tsuku->process($template, $data);
// Output:
// Widget: 2 x $9.50 = $19.00
// Gadget: 1 x $20.00 = $20.00
```

The same goes for "is this the last item?" - there is no `loop.last`. Use the index for the first item (`@if(index > 0)`), or precompute a flag in PHP.

### Use Smart Object Access

Tsuku automatically calls getters:

```php
class Product
{
    public function __construct(
        private string $name,
        private float $price,
        private bool $available,
    ) {
    }

    public function getName(): string { return $this->name; }
    public function getPrice(): float { return $this->price; }
    public function isAvailable(): bool { return $this->available; }
}

// Template automatically uses getName(), getPrice(), isAvailable()
$template = '@if(product.available)
{product.name}: $@number(product.price, 2)
@end';

$data = ['product' => new Product('Widget', 9.5, true)];
echo $tsuku->process($template, $data);
// Output: Widget: $9.50
```

`@for` only loops over arrays: convert collections and iterators with `iterator_to_array()` (or stream them with [`processToStream()`](/guide/streaming), which accepts any iterable).

### Check Group/Category Ids with `@split`

`in` with a string on the right is a **substring** test, so `"7231" in "72310,5"` is true. For comma-separated id lists, split them first - `in` with a list compares whole values:

```php
$template = '@if("7231" in product.group_ids)substring match!@end';
$data = ['product' => ['group_ids' => '72310,5']];
// Output: substring match!

$template = '@if("7231" in @split(product.group_ids))B2B price@else Retail price@end';
$data = ['product' => ['group_ids' => '72310,7231,5']];
// Output: B2B price
```

## Error Handling

### Provide Fallback Values

Use `@default()` for missing data:

```php
$template = 'Name: @default(user.name, "Unknown")
Email: @default(user.email, "N/A")';

$data = ['user' => ['name' => 'Ann', 'email' => '']];
// Output:
// Name: Ann
// Email: N/A
```

`@default()` uses PHP's `empty()`, so `0` and `"0"` count as empty too: `@default(stock, "N/A")` prints `N/A` for a stock of `0`. Use `@if` for numbers.

### Validate Template Syntax

Catch syntax errors early. All Tsuku exceptions extend `Qoliber\Tsuku\Exception\TsukuException`:

```php
use Qoliber\Tsuku\Exception\FunctionNotFoundException;
use Qoliber\Tsuku\Exception\ParseException;
use Qoliber\Tsuku\Exception\TsukuException;

try {
    $result = $tsuku->process($template, $data);
} catch (ParseException $e) {
    // Broken template: unclosed @for, bare @, "=" instead of "==", arithmetic in a condition, ...
    error_log(sprintf(
        'Template error at line %d, column %d: %s',
        $e->getTemplateLine(),
        $e->getTemplateColumn(),
        $e->getMessage()
    ));
    $result = "Error processing template";
} catch (FunctionNotFoundException $e) {
    // A condition calls a function that is not registered
    error_log('Unknown function @' . $e->getFunctionName());
    $result = "Error processing template";
} catch (TsukuException $e) {
    // STRICT mode: missing variable or function, or a function threw
    error_log('Rendering failed: ' . $e->getMessage());
    $result = "Error processing template";
}
```

What each one looks like:

```php
$template = 'SKU,Name
@for(products as product)
{product.sku},{product.name}
';
// Throws: ParseException: Expected @end but reached end of file at line 4, column 1 (@for opened at line 2, column 1)

$template = '@if(@is_sale(product))Sale@end';
// Throws: FunctionNotFoundException: Unknown function '@is_sale' at line 1, column 5
```

Parse errors are thrown in every strictness mode. Missing variables and functions are only errors in `STRICT` mode - see [Strictness Modes & Errors](/guide/strictness).

### Run Tests and CI in STRICT Mode

A typo in a variable name renders as empty output in the default `SILENT` mode. Render your templates in `STRICT` mode in tests so the typo fails the build:

```php
use Qoliber\Tsuku\StrictnessMode;

$tsuku = new Tsuku(StrictnessMode::STRICT);
$tsuku->process('{product.nmae}', ['product' => ['name' => 'Widget']]);
// Throws: TsukuException: Variable 'product.nmae' not found in data
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
@csv(product.sku),@csv(product.name),@csv(product.description),@number(product.price, 2, ".", ""),{product.stock}
@end';

$data = ['products' => [
    ['sku' => 'WID-001', 'name' => 'Widget', 'description' => 'A "premium" widget, new', 'price' => 1299.5, 'stock' => 5],
    ['sku' => 'GAD-002', 'name' => 'Gadget', 'description' => 'Simple gadget', 'price' => 29.99, 'stock' => 0],
]];
$result = $tsuku->process($template, $data);
file_put_contents('export.csv', $result);
```

**Result:**
```
SKU,Name,Description,Price,Stock
WID-001,Widget,"A ""premium"" widget, new",1299.50,5
GAD-002,Gadget,Simple gadget,29.99,0
```

### XML Feed Pattern

```php
$template = '<?xml version="1.0" encoding="UTF-8"?>
<products>
@for(products as product)
    <product id="{product.id}">
        <name>@xml(product.name)</name>
        <description>@xml(product.description)</description>
        <price>@number(product.price, 2, ".", "")</price>
        <availability>@match(product.stock)
            @case(0)
                out_of_stock
            @default
                in_stock
        @end</availability>
    </product>
@end
</products>';

$data = ['products' => [
    ['id' => 1, 'name' => 'Fish & Chips', 'description' => '<b>Crispy</b>', 'price' => 1299.5, 'stock' => 5],
    ['id' => 2, 'name' => 'Gadget', 'description' => 'Simple', 'price' => 29.99, 'stock' => 0],
]];
echo $tsuku->process($template, $data);
```

**Output:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<products>
    <product id="1">
        <name>Fish &amp; Chips</name>
        <description>&lt;b&gt;Crispy&lt;/b&gt;</description>
        <price>1299.50</price>
        <availability>in_stock</availability>
    </product>
    <product id="2">
        <name>Gadget</name>
        <description>Simple</description>
        <price>29.99</price>
        <availability>out_of_stock</availability>
    </product>
</products>
```

The `@match` opens after `<availability>` and its `@end` is followed by `</availability>`, so the whole block renders inside that one line.

### JSON API Response Pattern

There is no `loop.last`, so put the comma **before** every element except the first. Start the loop at the end of the `[` line and end each element with `}@end`:

```php
$template = '{
    "success": true,
    "data": [@for(items as item, index)@if(index > 0),@end
        {
            "id": {item.id},
            "name": @json(item.name),
            "description": @json(item.description)
        }@end
    ]
}';

$data = ['items' => [
    ['id' => 1, 'name' => 'Widget', 'description' => 'Small'],
    ['id' => 2, 'name' => 'Gadget', 'description' => 'Large'],
]];
echo $tsuku->process($template, $data);
```

**Output:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Widget",
            "description": "Small"
        },
        {
            "id": 2,
            "name": "Gadget",
            "description": "Large"
        }
    ]
}
```

`index` is the array key, so pass a list (`array_values()`). The 1.3 idiom `@if(index < length(items) - 1),@end` never printed a comma and throws a `ParseException` in 1.4.0 (no arithmetic in conditions).

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

$data = [
    'customer' => ['name' => 'Ann'],
    'order' => [
        'id' => 10042,
        'items' => [['name' => 'Widget', 'qty' => 2, 'price' => 9.5], ['name' => 'Gadget', 'qty' => 1, 'price' => 1200]],
        'total' => 1219,
    ],
];
echo $tsuku->process($template, $data);
```

**Output:**
```
Dear Ann,

Your order #10042 has been confirmed.

Order Details:
- Widget x2 - $9.50
- Gadget x1 - $1,200.00

Total: $1,219.00

Thank you for your purchase!
```

### Wrapper Pattern: Custom Directives

When the same wrapper appears around many values, register a [custom directive](/guide/custom-directives) instead of repeating it. The handler receives the rendered body:

```php
$tsuku->registerDirective('cdata', fn(string $body): string =>
    '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $body) . ']]>'
);

$template = '<name>@cdata{product.name}@end</name>';
$data = ['product' => ['name' => 'Fish & Chips']];
// Output: <name><![CDATA[Fish & Chips]]></name>
```

## Custom Functions Best Practices

### Type Hints and Return Types

Types are optional - Tsuku calls any callable and casts its return value to a string - but they document the function and catch bad data:

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

Arguments arrive as they are in your data (a number written in the template, like the `2` in `@substr(name, 0, 2)`, arrives as the string `"2"`); PHP converts scalars to the declared type. When the conversion fails (an array passed to a `string` parameter), the call throws a `TypeError`, which Tsuku handles like any error in a function: empty output in `SILENT` mode, a warning in `WARNING` mode, a `TsukuException` ("Function 'format_phone' error: ...") in `STRICT` mode.

Do not start function names with `end`, `else` or `default`, and do not use `for`, `if`, `unless`, `match`, `case` or `elseif`: the template would read `@endpoint(...)` as `@end` and `@if(...)` as the directive. Registration does not check names - see [Custom Functions](/guide/custom-functions).

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

The return value of a function is printed as it is - nothing escapes it for you:

```php
$tsuku->registerFunction('user_badge', function(string $name, string $role): string {
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeRole = htmlspecialchars($role, ENT_QUOTES, 'UTF-8');
    return "<span class=\"badge\">{$safeName} - {$safeRole}</span>";
});

$template = '@user_badge(user.name, user.role)';
$data = ['user' => ['name' => 'Ann <3', 'role' => 'admin']];
// Output: <span class="badge">Ann &lt;3 - admin</span>
```

## Testing Templates

### Unit Test Your Templates

```php
use PHPUnit\Framework\TestCase;
use Qoliber\Tsuku\StrictnessMode;
use Qoliber\Tsuku\Tsuku;

class ProductExportTest extends TestCase
{
    private Tsuku $tsuku;

    protected function setUp(): void
    {
        // STRICT: a missing variable fails the test instead of rendering empty
        $this->tsuku = new Tsuku(StrictnessMode::STRICT);
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

        $this->assertSame("SKU,Name\nABC-123,Widget\nXYZ-789,Gadget\n", $result);
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
    $this->assertSame("A\nB\nC\n", $result);
}
```

## Anti-Patterns to Avoid

### ❌ Don't Put Logic in Templates

Conditions can combine comparisons with `and`/`or` since 1.4.0, but business rules still belong in PHP, where they can be tested and reused:

```php
// Bad - Business rules in the template
$template = '@if(price > 100 and discount > 0 and category == "electronics")Discounted Price@end';

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

### ❌ Don't Compare with `true`, `false` or `null`

There are no boolean or null literals: `true` in a condition is a variable named `true`, which is missing and therefore `null`. `@if(flag == true)` is **true when `flag` is false**:

```php
$template = '@if(flag == true)on@else off@end';
$data = ['flag' => false];
// Output: on
```

Test the value itself: `@if(flag)`, `@if(not flag)`. The same trap applies to `@case(true)` in `@match`. See [Conditionals](/guide/conditionals).

## Deployment Checklist

Before deploying templates to production:

- [ ] All user input is escaped with appropriate function
- [ ] No SQL queries built with templates
- [ ] Templates are tested with edge cases, in `STRICT` mode
- [ ] Custom functions are pure (no side effects)
- [ ] Error handling is in place (`ParseException`, `TsukuException`)
- [ ] Large exports use `processToStream()`
- [ ] Performance has been considered
- [ ] Templates are documented
- [ ] Code has been reviewed

## Next Steps

- [Examples →](/examples/) - See best practices in action
- [Security →](/guide/security) - Deep dive into security
- [Upgrading from 1.3 →](/guide/upgrading) - What changed in 1.4.0
- [API Reference →](/api/) - Complete API documentation
