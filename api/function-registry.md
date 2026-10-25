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
    public function register(string $name, callable $handler): void;
    public function execute(string $name, array $arguments): mixed;
    public function has(string $name): bool;
}
```

That is the whole public API: there are no methods to fetch, list or remove functions.

A `Tsuku` instance creates its own registry unless you pass one to its constructor, and it does not expose it. To inspect or share a registry, create it yourself:

```php
use Qoliber\Tsuku\Tsuku;
use Qoliber\Tsuku\Function\FunctionRegistry;

$registry = new FunctionRegistry();
$tsuku = new Tsuku(functionRegistry: $registry);   // 4th constructor argument

$tsuku->registerFunction('badge', fn(string $status): string => strtoupper($status));
var_dump($registry->has('badge'));                  // bool(true) - it is the same registry
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

**Built-in Functions Registered (28):**
- String functions: `upper`, `lower`, `capitalize`, `trim`, `substr`, `replace`, `concat`
- Number functions: `number`, `number_format` (alias of `number`), `round`, `ceil`, `floor`, `abs`
- Array functions: `join`, `length`, `first`, `last`, `split`, `contains`
- Escaping functions: `html`, `xml`, `json`, `url`, `csv`, `escape`
- Date functions: `date`, `strtotime`
- Utility functions: `default`

## Methods

### `register()`

Register a function, or replace one that has the same name (built-ins included).

```php
public function register(string $name, callable $handler): void
```

**Parameters:**
- `$name` (string) - Function name, used as `@name(...)` in templates
- `$handler` (callable) - Function implementation

**Returns:**
- void

**Throws:**
- `\InvalidArgumentException` - For a name that a template could never call: names starting with `end`, `else` or `default` (other than `default` itself), the built-in directive names, names with `-` or `.`; see [Function Names](/api/tsuku#function-names). Up to 1.4.0 such names were registered and never called.

**Example:**

```php
$registry->register('currency', function(float $amount, string $code = 'USD'): string {
    return match($code) {
        'USD' => '$' . number_format($amount, 2),
        'EUR' => '€' . number_format($amount, 2),
        'GBP' => '£' . number_format($amount, 2),
        default => $code . ' ' . number_format($amount, 2),
    };
});
```

`Tsuku::registerFunction($name, $handler)` does exactly this on the instance's registry (and returns the `Tsuku` instance).

### `has()`

Check if a function is registered.

```php
public function has(string $name): bool
```

**Parameters:**
- `$name` (string) - Function name (case-sensitive)

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

### `execute()`

Call a registered function with arguments.

```php
public function execute(string $name, array $arguments): mixed
```

**Parameters:**
- `$name` (string) - Function name
- `$arguments` (array) - Function arguments (indexed array), passed to the handler as they are

**Returns:**
- (mixed) - The handler's return value, unchanged (templates cast it to string; `execute()` does not)

**Throws:**
- `Qoliber\Tsuku\Exception\FunctionNotFoundException` - If the function is not registered
- Anything the handler throws (e.g. a `TypeError`)

**Example:**

```php
// Call built-in function
$result = $registry->execute('upper', ['hello']);
// Returns: "HELLO"

// Call with multiple arguments
$result = $registry->execute('substr', ['Hello World', 0, 5]);
// Returns: "Hello"

// Call custom function
$registry->register('add', function(int $a, int $b): int {
    return $a + $b;
});
$result = $registry->execute('add', [5, 3]);
// Returns: 8 (an int)
```

## Complete Example

### Using FunctionRegistry Directly

```php
use Qoliber\Tsuku\Function\FunctionRegistry;

$registry = new FunctionRegistry();

// Register custom functions
$registry->register('currency', function(float $amount, string $code = 'USD'): string {
    return match($code) {
        'USD' => '$' . number_format($amount, 2),
        'EUR' => '€' . number_format($amount, 2),
        default => $code . ' ' . number_format($amount, 2),
    };
});

$registry->register('slug', function(string $text): string {
    return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($text)));
});

// Check if functions exist
if ($registry->has('currency')) {
    $result = $registry->execute('currency', [99.99, 'EUR']);
    echo $result; // €99.99
}
```

### Using with Tsuku

Pass the registry to the `Tsuku` constructor (named argument `functionRegistry`, or the 4th positional argument):

```php
use Qoliber\Tsuku\Tsuku;
use Qoliber\Tsuku\Function\FunctionRegistry;

$registry = new FunctionRegistry();

// Register function
$registry->register('badge', function(string $status): string {
    return match($status) {
        'active' => '✓ Active',
        'pending' => '⏳ Pending',
        'inactive' => '✗ Inactive',
        default => '? Unknown'
    };
});

$tsuku = new Tsuku(functionRegistry: $registry);

// Use in template
$template = 'Status: @badge(user.status)';
$result = $tsuku->process($template, ['user' => ['status' => 'active']]);
// Output: Status: ✓ Active
```

The registry is shared, not copied: functions registered on `$registry` later are available to `$tsuku` (and to every other instance created with the same registry).

### Extending with Multiple Custom Functions

```php
use Qoliber\Tsuku\Tsuku;
use Qoliber\Tsuku\Function\FunctionRegistry;

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
$registry = new FunctionRegistry();
CustomFunctions::register($registry);
$tsuku = new Tsuku(functionRegistry: $registry);

echo $tsuku->process('@phone(p) @format_sku(s) @percent(v, 1) @filesize(b)', [
    'p' => '5551234567', 's' => ' ab-1 ', 'v' => 0.256, 'b' => 1536000,
]);
// Output: (555) 123-4567 AB-1 25.6% 1.46 MB
```

## Function Signatures

### Return Values

A return type is **not** required - any callable works. In a template the return value is cast to string (`null`/`false` print nothing, `true` prints `1`, arrays print `Array` with a PHP warning); `execute()` returns it unchanged.

```php
// ✅ Works
$registry->register('test', function($x) {
    return $x;
});

// ✅ Clearer - with types
$registry->register('test', function(string $x): string {
    return $x;
});
```

### Parameter Type Hints

Recommended but not required. In templates, quoted literals and number literals arrive as strings and variables keep their PHP type; typed parameters are coerced by PHP as usual (`"5"` → `5`):

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
$result = $registry->execute('greet', ['John']);
// Returns: "Hello, John!"

// Call with 2 arguments
$result = $registry->execute('greet', ['John', 'Hi']);
// Returns: "Hi, John!"
```

### Variadic Parameters

Supported:

```php
$registry->register('glue', function(string ...$parts): string {
    return implode('', $parts);
});

$result = $registry->execute('glue', ['Hello', ' ', 'World']);
// Returns: "Hello World"
```

## Built-in Functions List

All built-in functions are registered automatically. Signatures as implemented: handlers typed `mixed` cast their input; `substr`, `replace` and `strtotime` pass it straight to PHP, so an integer from your data (e.g. `@substr(order.id, 0, 3)` with `'id' => 12345`) is a `TypeError` - in a template a function error, i.e. empty output in `SILENT` mode. Cast such values to string in your data.

**String Functions:**
- `upper(mixed $str): string` - `strtoupper()`, byte-based (no multibyte case mapping)
- `lower(mixed $str): string`
- `capitalize(mixed $str): string` - `ucfirst()`
- `trim(mixed $str): string`
- `substr(string $str, int $start, ?int $length = null): string`
- `replace(string|array $str, mixed $search, mixed $replace): string|array` - `str_replace()`
- `concat(mixed ...$parts): string`

**Number Functions:**
- `number(mixed $num, int $decimals = 0, string $decPoint = '.', string $thousandsSep = ','): string`
- `number_format(...)` - same as `number`
- `round(mixed $num, int $precision = 0): float`
- `ceil(mixed $num): float`
- `floor(mixed $num): float`
- `abs(mixed $num): float`

**Array Functions:**
- `join(mixed $array, string $glue = ','): string`
- `length(mixed $value): int` - `count()` for arrays, string length in bytes otherwise
- `first(mixed $array): mixed` - `null` for an empty or non-array value
- `last(mixed $array): mixed`
- `split(mixed $value, string $delimiter = ','): array` - `""`/`null` give `[]`, an array is returned unchanged, an empty delimiter splits into characters
- `contains(mixed $haystack, mixed $needle): bool` - same rules as `needle in haystack` in conditions

**Escaping Functions:**
- `html(mixed $str): string` - `htmlspecialchars(ENT_QUOTES | ENT_HTML5)`
- `xml(mixed $str): string` - `htmlspecialchars(ENT_QUOTES | ENT_XML1)`
- `json(mixed $str): string` - a quoted JSON string; `"`, `'`, `<`, `>`, `&` become `"`, `'`, `<`, `>`, `&`
- `url(mixed $str): string` - `rawurlencode()`
- `csv(mixed $str): string` - quotes the value (doubling `"`) when it contains `,`, `"` or a line break
- `escape(mixed $str, string $type = 'html'): string` - `html`, `xml`, `json`, `url` or `csv`

**Date Functions:**
- `date(string $format, mixed $timestamp = null): string` - the timestamp is cast to `int`; pass a Unix timestamp, not a date string
- `strtotime(string $str): int|false`

**Utility Functions:**
- `default(mixed $value, mixed $default): mixed` - `$default` when `empty($value)` (so also for `"0"` and `0`)

## Error Handling

### Function Not Found

```php
use Qoliber\Tsuku\Exception\FunctionNotFoundException;

try {
    $result = $registry->execute('nonexistent', ['arg']);
} catch (FunctionNotFoundException $e) {
    echo "Error: " . $e->getMessage();
    // Error: Function 'nonexistent' not found
    echo $e->getFunctionName(); // nonexistent
}
```

`FunctionNotFoundException` extends `TsukuException`. In templates, an unknown function in output follows the [strictness mode](/guide/strictness); an unknown function in a condition always throws `FunctionNotFoundException`.

### Invalid Function Name

There is no name check, so this does not throw:

```php
$registry->register('invalid-name', fn() => 'test');
var_dump($registry->has('invalid-name')); // bool(true)
```

Calling it from a template fails, because `@invalid-name(...)` is not a function call: the lexer throws `ParseException` ("Invalid @ syntax ... (write @@ for a literal @)").

### Type Errors

```php
$registry->register('add', function(int $a, int $b): int {
    return $a + $b;
});

try {
    // Passing strings instead of integers
    $result = $registry->execute('add', ['foo', 'bar']);
} catch (\TypeError $e) {
    echo "Type error: " . $e->getMessage();
}
```

In a template the same `TypeError` is a function error: removed in `SILENT`, a warning in `WARNING`, a `TsukuException` in `STRICT`.

## Performance Considerations

### Function Lookup

Function lookup is O(1) using array access:

```php
// Fast - single array lookup
$registry->execute('upper', ['text']);
```

### Avoid Reregistration

Register functions once, reuse many times:

```php
// ✅ Good - register once
$registry->register('format', fn($x) => strtoupper($x));
for ($i = 0; $i < 1000; $i++) {
    $registry->execute('format', ["text{$i}"]);
}

// ❌ Bad - registering in loop
for ($i = 0; $i < 1000; $i++) {
    $registry->register('format', fn($x) => strtoupper($x));
    $registry->execute('format', ["text{$i}"]);
}
```

## Shared State

A FunctionRegistry is an ordinary mutable object:
- ✅ Safe: any number of `execute()` / template calls
- ⚠️ A registry shared between `Tsuku` instances is shared state - a function registered or replaced through one instance changes all of them
- Recommendation: register all functions at initialization

## Memory Usage

A new registry with the 28 built-in closures takes about 12 KB (measured with `memory_get_usage()` on PHP 8.3); each custom function adds the size of its closure.

## Next Steps

- [Tsuku Class →](/api/tsuku) - Main API class
- [Built-in Functions →](/guide/built-in-functions) - Function reference
- [Custom Functions →](/guide/custom-functions) - Creating functions
