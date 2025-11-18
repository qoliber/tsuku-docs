# Tsuku Class

The main entry point for template processing.

## Namespace

```php
namespace Qoliber\Tsuku;
```

## Class Definition

```php
class Tsuku
{
    public function __construct();
    public function process(string $template, array $data): string;
    public function registerFunction(string $name, callable $callback): void;
    public function getFunctionRegistry(): \Qoliber\Tsuku\Function\FunctionRegistry;
}
```

## Constructor

### `__construct()`

Creates a new Tsuku instance with all built-in functions registered.

```php
public function __construct()
```

**Example:**

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();
```

**Notes:**
- Automatically registers all built-in functions (`@upper()`, `@html()`, etc.)
- Lightweight - safe to create multiple instances
- No configuration needed

## Methods

### `process()`

Process a template string with the provided data.

```php
public function process(string $template, array $data): string
```

**Parameters:**
- `$template` (string) - The template string to process
- `$data` (array) - Associative array of data for the template

**Returns:**
- (string) - The processed output

**Throws:**
- `\Qoliber\Tsuku\Exception\TemplateException` - On template syntax errors

**Example:**

```php
$template = 'Hello {name}, you have @length(messages) messages.';
$data = [
    'name' => 'John',
    'messages' => ['msg1', 'msg2', 'msg3']
];

$result = $tsuku->process($template, $data);
// Output: Hello John, you have 3 messages.
```

**Processing Steps:**
1. Tokenize template (Lexer)
2. Parse tokens to AST (Parser)
3. Compile AST with data (Compiler)

### `registerFunction()`

Register a custom template function.

```php
public function registerFunction(string $name, callable $callback): void
```

**Parameters:**
- `$name` (string) - Function name (used as `@name()` in templates)
- `$callback` (callable) - Function implementation

**Returns:**
- void

**Throws:**
- `\InvalidArgumentException` - If function name is invalid

**Example:**

```php
$tsuku->registerFunction('currency', function(float $amount, string $code = 'USD'): string {
    return match($code) {
        'USD' => ' . number_format($amount, 2),
        'EUR' => '€' . number_format($amount, 2),
        'GBP' => '£' . number_format($amount, 2),
        default => $code . ' ' . number_format($amount, 2),
    };
});

$template = 'Total: @currency(price, "EUR")';
$result = $tsuku->process($template, ['price' => 99.99]);
// Output: Total: €99.99
```

**Function Requirements:**
- Must have a return type declaration
- Should return string or stringable type
- Parameters should have type hints
- Function name must be alphanumeric (underscores allowed)

**Valid Function Names:**
- ✅ `my_function`
- ✅ `customFunc123`
- ✅ `format_price`
- ❌ `my-function` (hyphen not allowed)
- ❌ `@myfunction` (@ not allowed)

### `getFunctionRegistry()`

Get the function registry instance.

```php
public function getFunctionRegistry(): \Qoliber\Tsuku\Function\FunctionRegistry
```

**Returns:**
- `FunctionRegistry` - The function registry

**Example:**

```php
$registry = $tsuku->getFunctionRegistry();

// Check if function exists
if ($registry->has('my_function')) {
    echo "Function exists!";
}

// Get all registered functions
$functions = $registry->all();
foreach ($functions as $name => $callback) {
    echo "Function: {$name}\n";
}
```

## Complete Example

### Basic Usage

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

$template = 'Name,Email,Status
@for(users as user)
@csv(user.name),@csv(user.email),{user.status}
@end';

$data = [
    'users' => [
        ['name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active'],
        ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'status' => 'pending'],
    ]
];

$csv = $tsuku->process($template, $data);
file_put_contents('users.csv', $csv);
```

### With Custom Functions

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

// Register custom functions
$tsuku->registerFunction('badge', function(string $status): string {
    return match($status) {
        'active' => '✓ Active',
        'pending' => '⏳ Pending',
        'inactive' => '✗ Inactive',
        default => '? Unknown'
    };
});

$tsuku->registerFunction('format_phone', function(string $phone): string {
    return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $phone);
});

$template = '@for(users as user)
Name: @html(user.name)
Status: @badge(user.status)
Phone: @format_phone(user.phone)

@end';

$data = [
    'users' => [
        ['name' => 'John Doe', 'status' => 'active', 'phone' => '5551234567'],
    ]
];

echo $tsuku->process($template, $data);
```

### Dependency Injection (Magento 2, Laravel, Symfony)

```php
use Qoliber\Tsuku\Tsuku;

class ProductExportService
{
    public function __construct(
        private readonly Tsuku $tsuku
    ) {}

    public function exportToCsv(array $products): string
    {
        $template = 'SKU,Name,Price
@for(products as product)
@csv(product.sku),@csv(product.name),@number(product.price, 2)
@end';

        return $this->tsuku->process($template, ['products' => $products]);
    }
}

// Magento 2 di.xml
// <type name="ProductExportService">
//     <arguments>
//         <argument name="tsuku" xsi:type="object">Qoliber\Tsuku\Tsuku</argument>
//     </arguments>
// </type>
```

### Service Container Registration

**Laravel:**

```php
// app/Providers/AppServiceProvider.php
use Qoliber\Tsuku\Tsuku;

public function register()
{
    $this->app->singleton(Tsuku::class, function ($app) {
        $tsuku = new Tsuku();

        // Register custom functions
        $tsuku->registerFunction('currency', function(float $amount): string {
            return ' . number_format($amount, 2);
        });

        return $tsuku;
    });
}
```

**Symfony:**

```yaml
# config/services.yaml
services:
    Qoliber\Tsuku\Tsuku:
        class: Qoliber\Tsuku\Tsuku
        public: true
```

## Error Handling

```php
use Qoliber\Tsuku\Tsuku;
use Qoliber\Tsuku\Exception\TemplateException;

$tsuku = new Tsuku();

try {
    $result = $tsuku->process($template, $data);
} catch (TemplateException $e) {
    // Log error
    error_log('Template processing failed: ' . $e->getMessage());

    // Return fallback
    $result = '<!-- Template Error -->';
}
```

## Type Safety

All methods use strict type hints:

```php
// ✅ Correct - string template, array data
$result = $tsuku->process('Hello {name}', ['name' => 'John']);

// ❌ TypeError - template must be string
$result = $tsuku->process(['template'], $data);

// ❌ TypeError - data must be array
$result = $tsuku->process($template, 'invalid');
```

## Performance Tips

### Reuse Instance

```php
// ✅ Good - reuse instance
$tsuku = new Tsuku();
foreach ($batches as $batch) {
    $output = $tsuku->process($template, $batch);
}

// ❌ Bad - creates new instance each time
foreach ($batches as $batch) {
    $tsuku = new Tsuku();
    $output = $tsuku->process($template, $batch);
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
        $this->registerCustomFunctions();
    }

    private function registerCustomFunctions(): void
    {
        $this->tsuku->registerFunction('currency', function(float $amount): string {
            return ' . number_format($amount, 2);
        });

        // Other functions...
    }

    public function render(string $template, array $data): string
    {
        return $this->tsuku->process($template, $data);
    }
}
```

## Thread Safety

The Tsuku class is **stateless** except for registered functions:

- ✅ Safe: Multiple `process()` calls on same instance
- ✅ Safe: Multiple instances in same process
- ⚠️ Caution: Registering functions after use may affect concurrent requests

## Immutability

Once a function is registered, it cannot be unregistered or modified:

```php
$tsuku->registerFunction('test', fn() => 'v1');
$tsuku->registerFunction('test', fn() => 'v2'); // Overwrites

// No method to unregister functions
```

## Memory Usage

- Instance size: ~1-2 KB (function registry)
- Per-process overhead: Minimal
- Template compilation: Temporary (garbage collected)

## Requirements

- PHP 8.1+
- No extensions required

## Next Steps

- [Syntax Reference →](/api/syntax) - Complete template syntax
- [FunctionRegistry →](/api/function-registry) - Function management
- [Examples →](/examples/) - Real-world usage examples
