# FunctionRegistry

Manages all template functions (built-in and custom).

## Namespace

```php
namespace Qoliber\Tsuku\Function;
```

## Class Definition

```php
class FunctionRegistry
{
    public function __construct();
    public function register(string $name, callable $callback): void;
    public function has(string $name): bool;
    public function get(string $name): callable;
    public function call(string $name, array $arguments): mixed;
    public function all(): array;
}
```

## Constructor

### `__construct()`

Creates a new FunctionRegistry with all built-in functions registered.

```php
public function __construct()
```

**Example:**

```php
use Qoliber\Tsuku\Function\FunctionRegistry;

$registry = new FunctionRegistry();
```

**Built-in Functions Registered:**
- String functions: `upper`, `lower`, `capitalize`, `trim`, `substr`, `replace`, `concat`
- Number functions: `number`, `round`, `ceil`, `floor`, `abs`
- Array functions: `join`, `length`, `first`, `last`
- Escaping functions: `html`, `xml`, `json`, `url`, `csv`, `escape`
- Date functions: `date`, `strtotime`
- Utility functions: `default`

## Methods

### `register()`

Register a custom function.

```php
public function register(string $name, callable $callback): void
```

**Parameters:**
- `$name` (string) - Function name (alphanumeric + underscores)
- `$callback` (callable) - Function implementation

**Returns:**
- void

**Throws:**
- `\InvalidArgumentException` - If name is invalid or callback is not callable

**Example:**

```php
$registry->register('currency', function(float $amount, string $code = 'USD'): string {
    return match($code) {
        'USD' => ' . number_format($amount, 2),
        'EUR' => '€' . number_format($amount, 2),
        'GBP' => '£' . number_format($amount, 2),
        default => $code . ' ' . number_format($amount, 2),
    };
});
```

**Function Requirements:**

```php
// ✅ Valid - has return type
$registry->register('test', function(string $x): string {
    return $x;
});

// ❌ Invalid - no return type
$registry->register('test', function($x) {
    return $x;
});
```

**Valid Function Names:**
- ✅ `my_function`
- ✅ `format123`
- ✅ `custom_formatter`
- ❌ `my-function` (hyphen not allowed)
- ❌ `my.function` (dot not allowed)
- ❌ `@myfunction` (@ not allowed)

### `has()`

Check if a function is registered.

```php
public function has(string $name): bool
```

**Parameters:**
- `$name` (string) - Function name

**Returns:**
- (bool) - True if function exists, false otherwise

**Example:**

```php
if ($registry->has('currency')) {
    echo "Currency function is registered";
}

if (!$registry->has('unknown')) {
    echo "Unknown function not found";
}
```

### `get()`

Get a registered function.

```php
public function get(string $name): callable
```

**Parameters:**
- `$name` (string) - Function name

**Returns:**
- (callable) - The function callback

**Throws:**
- `\RuntimeException` - If function not found

**Example:**

```php
$upperFunc = $registry->get('upper');
$result = $upperFunc('hello'); // "HELLO"

try {
    $func = $registry->get('nonexistent');
} catch (\RuntimeException $e) {
    echo "Function not found: " . $e->getMessage();
}
```

### `call()`

Call a registered function with arguments.

```php
public function call(string $name, array $arguments): mixed
```

**Parameters:**
- `$name` (string) - Function name
- `$arguments` (array) - Function arguments (indexed array)

**Returns:**
- (mixed) - Function result

**Throws:**
- `\RuntimeException` - If function not found

**Example:**

```php
// Call built-in function
$result = $registry->call('upper', ['hello']);
// Returns: "HELLO"

// Call with multiple arguments
$result = $registry->call('substr', ['Hello World', 0, 5]);
// Returns: "Hello"

// Call custom function
$registry->register('add', function(int $a, int $b): int {
    return $a + $b;
});
$result = $registry->call('add', [5, 3]);
// Returns: 8
```

### `all()`

Get all registered functions.

```php
public function all(): array
```

**Returns:**
- (array) - Associative array of function name => callable

**Example:**

```php
$functions = $registry->all();

foreach ($functions as $name => $callback) {
    echo "Function: {$name}\n";
}

// Output:
// Function: upper
// Function: lower
// Function: capitalize
// ...
```

## Complete Example

### Using FunctionRegistry Directly

```php
use Qoliber\Tsuku\Function\FunctionRegistry;

$registry = new FunctionRegistry();

// Register custom functions
$registry->register('currency', function(float $amount, string $code = 'USD'): string {
    return match($code) {
        'USD' => ' . number_format($amount, 2),
        'EUR' => '€' . number_format($amount, 2),
        default => $code . ' ' . number_format($amount, 2),
    };
});

$registry->register('slug', function(string $text): string {
    return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($text)));
});

// Check if functions exist
if ($registry->has('currency')) {
    $result = $registry->call('currency', [99.99, 'EUR']);
    echo $result; // €99.99
}

// List all functions
$allFunctions = $registry->all();
echo "Total functions: " . count($allFunctions);
```

### Using with Tsuku

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

// Get registry from Tsuku instance
$registry = $tsuku->getFunctionRegistry();

// Register function
$registry->register('badge', function(string $status): string {
    return match($status) {
        'active' => '✓ Active',
        'pending' => '⏳ Pending',
        'inactive' => '✗ Inactive',
        default => '? Unknown'
    };
});

// Use in template
$template = 'Status: @badge(user.status)';
$result = $tsuku->process($template, ['user' => ['status' => 'active']]);
// Output: Status: ✓ Active
```

### Extending with Multiple Custom Functions

```php
class CustomFunctions
{
    public static function register(FunctionRegistry $registry): void
    {
        // Phone formatting
        $registry->register('phone', function(string $phone): string {
            return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $phone);
        });

        // SKU formatting
        $registry->register('format_sku', function(string $sku): string {
            return strtoupper(trim($sku));
        });

        // Percentage
        $registry->register('percent', function(float $value, int $decimals = 0): string {
            return number_format($value * 100, $decimals) . '%';
        });

        // File size
        $registry->register('filesize', function(int $bytes): string {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            for ($i = 0; $bytes > 1024 && $i < 4; $i++) {
                $bytes /= 1024;
            }
            return round($bytes, 2) . ' ' . $units[$i];
        });
    }
}

// Usage
$tsuku = new Tsuku();
CustomFunctions::register($tsuku->getFunctionRegistry());
```

## Function Signature Requirements

### Return Type Declaration

Functions **must** have a return type:

```php
// ✅ Valid
$registry->register('test', function(string $x): string {
    return $x;
});

// ❌ Invalid - no return type
$registry->register('test', function($x) {
    return $x;
});
```

### Parameter Type Hints

Recommended but not required:

```php
// ✅ Best practice - full type hints
$registry->register('add', function(int $a, int $b): int {
    return $a + $b;
});

// ⚠️ Works but not recommended
$registry->register('add', function($a, $b): int {
    return $a + $b;
});
```

### Default Parameters

Supported:

```php
$registry->register('greet', function(string $name, string $greeting = 'Hello'): string {
    return "{$greeting}, {$name}!";
});

// Call with 1 argument
$result = $registry->call('greet', ['John']);
// Returns: "Hello, John!"

// Call with 2 arguments
$result = $registry->call('greet', ['John', 'Hi']);
// Returns: "Hi, John!"
```

### Variadic Parameters

Supported:

```php
$registry->register('concat', function(string ...$parts): string {
    return implode('', $parts);
});

$result = $registry->call('concat', ['Hello', ' ', 'World']);
// Returns: "Hello World"
```

## Built-in Functions List

All built-in functions are registered automatically:

**String Functions:**
- `upper(string): string`
- `lower(string): string`
- `capitalize(string): string`
- `trim(string): string`
- `substr(string, int, ?int): string`
- `replace(string, string, string): string`
- `concat(...string): string`

**Number Functions:**
- `number(float, int, string, string): string`
- `round(float, int): float`
- `ceil(float): int`
- `floor(float): int`
- `abs(float): float`

**Array Functions:**
- `join(array, string): string`
- `length(array|string): int`
- `first(array): mixed`
- `last(array): mixed`

**Escaping Functions:**
- `html(string): string`
- `xml(string): string`
- `json(string): string`
- `url(string): string`
- `csv(string): string`
- `escape(string, string): string`

**Date Functions:**
- `date(string, ?int): string`
- `strtotime(string): int`

**Utility Functions:**
- `default(mixed, mixed): mixed`

## Error Handling

### Function Not Found

```php
try {
    $result = $registry->call('nonexistent', ['arg']);
} catch (\RuntimeException $e) {
    echo "Error: " . $e->getMessage();
    // Error: Function 'nonexistent' not found
}
```

### Invalid Function Name

```php
try {
    $registry->register('invalid-name', fn() => 'test');
} catch (\InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage();
}
```

### Type Errors

```php
$registry->register('add', function(int $a, int $b): int {
    return $a + $b;
});

try {
    // Passing strings instead of integers
    $result = $registry->call('add', ['foo', 'bar']);
} catch (\TypeError $e) {
    echo "Type error: " . $e->getMessage();
}
```

## Performance Considerations

### Function Lookup

Function lookup is O(1) using array access:

```php
// Fast - single array lookup
$registry->call('upper', ['text']);
```

### Avoid Reregistration

Register functions once, reuse many times:

```php
// ✅ Good - register once
$registry->register('format', fn($x) => strtoupper($x));
for ($i = 0; $i < 1000; $i++) {
    $registry->call('format', ["text{$i}"]);
}

// ❌ Bad - registering in loop
for ($i = 0; $i < 1000; $i++) {
    $registry->register('format', fn($x) => strtoupper($x));
    $registry->call('format', ["text{$i}"]);
}
```

## Thread Safety

FunctionRegistry is **stateful**:
- ✅ Safe: Multiple `call()` on same instance
- ⚠️ Caution: Concurrent `register()` calls may cause race conditions
- Recommendation: Register all functions at initialization

## Memory Usage

- Registry size: ~500 bytes + (number of functions × 200 bytes)
- Built-in functions: ~5 KB
- Custom functions: ~200 bytes per function

## Next Steps

- [Tsuku Class →](/api/tsuku) - Main API class
- [Built-in Functions →](/guide/built-in-functions) - Function reference
- [Custom Functions →](/guide/custom-functions) - Creating functions
