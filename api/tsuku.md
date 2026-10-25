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
    public function __construct(
        StrictnessMode $strictnessMode = StrictnessMode::SILENT,
        DirectiveRegistry $directiveRegistry = new DirectiveRegistry(),
        FormatterRegistry $formatterRegistry = new FormatterRegistry(),
        FunctionRegistry $functionRegistry = new FunctionRegistry(),
    );

    public function process(string $template, array $data, ?StrictnessMode $strictnessMode = null): string;

    public function processToStream(
        string $template,
        array $contextData,
        iterable $rows,
        string $rowsVariable,
        callable $writer,
        ?StrictnessMode $strictnessMode = null
    ): void;

    public function getWarnings(): array;
    public function hasWarnings(): bool;

    public function registerFunction(string $name, callable $handler): self;
    public function registerDirective(string $name, callable $handler): self;
    public function registerFormatter(string $name, callable $handler): self;
}
```

`StrictnessMode` and `DirectiveRegistry` / `FormatterRegistry` live in `Qoliber\Tsuku`, `FunctionRegistry` in `Qoliber\Tsuku\Function`. The registries are kept in private properties; there are no getters for them.

## Constructor

### `__construct()`

Creates a new Tsuku instance. Every argument is optional:

| Parameter | Default | Purpose |
|-----------|---------|---------|
| `$strictnessMode` | `StrictnessMode::SILENT` | How missing variables/functions and function errors are handled (`SILENT`, `WARNING`, `STRICT`) |
| `$directiveRegistry` | `new DirectiveRegistry()` | Custom block directives (empty by default) |
| `$formatterRegistry` | `new FormatterRegistry()` | Formatters (see [`registerFormatter()`](#registerformatter)) |
| `$functionRegistry` | `new FunctionRegistry()` | Template functions; a new registry contains the 28 built-ins |

**Example:**

```php
use Qoliber\Tsuku\Tsuku;
use Qoliber\Tsuku\StrictnessMode;
use Qoliber\Tsuku\Function\FunctionRegistry;

$tsuku = new Tsuku();                          // SILENT, built-in functions only
$strict = new Tsuku(StrictnessMode::STRICT);  // throw on missing variables/functions

// Bring your own registry, e.g. to share functions between instances or to call has()
$functions = new FunctionRegistry();
$functions->register('slug', fn(string $text): string => strtolower(preg_replace('/[^a-z0-9]+/i', '-', $text)));
$tsuku = new Tsuku(functionRegistry: $functions);
```

**Notes:**
- A new `FunctionRegistry` registers all built-in functions (`@upper()`, `@html()`, ...).
- Registries are objects: a registry passed in is shared, so functions registered on it later are visible to every `Tsuku` that uses it.
- An instance with the default registries takes about 13 KB (measured with `memory_get_usage()` on PHP 8.3) - cheap, but there is no reason to create one per render.

## Methods

### `process()`

Process a template string with the provided data.

```php
public function process(string $template, array $data, ?StrictnessMode $strictnessMode = null): string
```

**Parameters:**
- `$template` (string) - The template string to process
- `$data` (array) - Associative array of data for the template
- `$strictnessMode` (StrictnessMode|null) - Overrides the instance's mode for this call only

**Returns:**
- (string) - The processed output

**Throws:**
- `Qoliber\Tsuku\Exception\ParseException` - Invalid template syntax or a malformed condition; the message names line and column (`getTemplateLine()`, `getTemplateColumn()`)
- `Qoliber\Tsuku\Exception\FunctionNotFoundException` - An unknown function inside a condition (in every mode)
- `Qoliber\Tsuku\Exception\TsukuException` - In `STRICT` mode: a missing `{variable}`, an unknown function in output, or an exception thrown by a function or directive handler
- Whatever a function called **inside a condition** throws - those exceptions are not wrapped, in any mode

All Tsuku exceptions extend `TsukuException`. Missing values elsewhere - in conditions, `@for` collections, `@match` subjects, function arguments and `@?{...}` values - are never errors: they are `null` / empty in every mode.

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
1. Lexer: removes `\` line continuations, splits the template into tokens and applies [whitespace control](/guide/whitespace-control)
2. Parser: builds the AST; conditions are parsed into expression trees and unknown functions in conditions are rejected
3. Compiler: walks the AST with your data

Each call lexes and parses the template again; nothing is cached between calls.

::: warning Per-call strictness and warnings
When you pass `$strictnessMode`, warnings of that call are recorded in a temporary context and are **not** available through `getWarnings()` afterwards. If you need the warnings, set the mode in the constructor instead.
:::

### `processToStream()`

Render a template incrementally through a writer callback - header once, the body of one top-level `@for` once per row, footer once - so memory stays bounded by the largest single piece instead of the whole output.

```php
public function processToStream(
    string $template,
    array $contextData,
    iterable $rows,
    string $rowsVariable,
    callable $writer,
    ?StrictnessMode $strictnessMode = null
): void
```

**Parameters:**
- `$template` (string) - Must contain exactly one top-level `@for` over `$rowsVariable`
- `$contextData` (array) - Data for the header, the footer and every row
- `$rows` (iterable) - Arrays, iterators or generators; consumed once, lazily
- `$rowsVariable` (string) - The collection name used in the `@for` (e.g. `products` for `@for(products as product)`)
- `$writer` (callable) - Receives each non-empty rendered piece as a string
- `$strictnessMode` (StrictnessMode|null) - Overrides the instance's mode for this call only

**Throws:**
- `\RuntimeException` - When the template has no top-level `@for` over `$rowsVariable`, or more than one
- The same exceptions as `process()`, and anything the writer throws

**Example:**

```php
$template = '<feed currency="{currency}">
@for(products as product)
  <item sku="{product.sku}">{product.price}</item>
@end
</feed>';

$tsuku->processToStream(
    $template,
    ['currency' => 'EUR'],
    [['sku' => 'A-1', 'price' => '9.99'], ['sku' => 'B-2', 'price' => '19.50']],
    'products',
    function (string $chunk) {
        echo $chunk; // or fwrite($handle, $chunk)
    }
);
// Output:
// <feed currency="EUR">
//   <item sku="A-1">9.99</item>
//   <item sku="B-2">19.50</item>
// </feed>
```

See [Streaming Large Datasets](/guide/streaming) for generators, files and the constraints.

### `getWarnings()` / `hasWarnings()`

```php
public function getWarnings(): array   // list of warning messages
public function hasWarnings(): bool
```

In `WARNING` mode, missing variables, unknown functions and function/directive errors are recorded instead of thrown. The list is cleared at the start of every `process()` / `processToStream()` call.

```php
use Qoliber\Tsuku\Tsuku;
use Qoliber\Tsuku\StrictnessMode;

$tsuku = new Tsuku(StrictnessMode::WARNING);

echo $tsuku->process('Hello [{name}] [@missing_fn(x)]', []);
// Output: Hello [] []

if ($tsuku->hasWarnings()) {
    print_r($tsuku->getWarnings());
    // [0 => "Variable 'name' not found in data", 1 => "Function 'missing_fn' not found"]
}
```

See [Strictness Modes & Errors](/guide/strictness).

### `registerFunction()`

Register a custom template function.

```php
public function registerFunction(string $name, callable $handler): self
```

**Parameters:**
- `$name` (string) - Function name, used as `@name(...)` in templates
- `$handler` (callable) - Receives the resolved arguments; its return value is printed

**Returns:**
- `self` - so calls can be chained

**Example:**

```php
$tsuku->registerFunction('currency', function(float $amount, string $code = 'USD'): string {
    return match($code) {
        'USD' => '$' . number_format($amount, 2),
        'EUR' => '€' . number_format($amount, 2),
        'GBP' => '£' . number_format($amount, 2),
        default => $code . ' ' . number_format($amount, 2),
    };
});

$template = 'Total: @currency(price, "EUR")';
$result = $tsuku->process($template, ['price' => 99.99]);
// Output: Total: €99.99
```

**How handlers are called:**
- Arguments: quoted strings as strings, number literals as strings (`"2"`), variables with their PHP type (arrays stay arrays, a missing variable becomes `""`), nested function calls with their result.
- PHP coerces arguments to typed parameters as usual (`"2"` → `int 2`); a `TypeError` is a handler error (see below).
- The return value is cast to string: `null` and `false` print nothing, `true` prints `1`, a `Stringable` object its string, an array prints `Array` (with a PHP warning).
- A return type is not required, and the name is not validated when registering.
- Registering an existing name (also a built-in such as `upper`) replaces it. There is no way to unregister a function.
- An exception thrown by the handler in output follows the strictness mode: removed (`SILENT`), recorded as `Function 'name' error: ...` (`WARNING`), or rethrown as `TsukuException` (`STRICT`). Inside a condition it propagates unchanged.

#### Function Names

`registerFunction()` accepts any string, but the template lexer decides what `@name(` means. A function can only be called when its name

- is an identifier: letters, digits and `_`, not starting with a digit (`format_price`, `customFunc123`);
- does not start with `end` or `else` - those are read as `@end` / `@else` (`@ends_with(...)` is `@end` followed by text);
- does not start with `default`, except `default` itself - `@defaults(...)` is `@default`;
- is not `for`, `if`, `unless`, `match`, `case` or `elseif`;
- is not also registered as a custom directive (a directive wins).

```php
$tsuku->registerFunction('ends_with', fn(string $text, string $suffix): string => str_ends_with($text, $suffix) ? 'yes' : 'no');

$tsuku->process('@ends_with(sku, "-X")', ['sku' => 'A-X']);
// Throws: ParseException: Unexpected @end at line 1, column 1
```

Names are case-sensitive (`@Upper` is not `@upper`). Hyphens or dots (`my-function`, `my.function`) cannot be called either: `@my-function(...)` is a `ParseException` ("Invalid @ syntax").

### `registerDirective()`

Register a custom block directive, used as `@name(arguments) ... @end` or `@name ... @end`.

```php
public function registerDirective(string $name, callable $handler): self
```

**Parameters:**
- `$name` (string) - Directive name without `@`: letters, digits and `_`, not starting with a digit, and not a built-in (`for`, `if`, `unless`, `elseif`, `else`, `match`, `case`, `default`, `end`)
- `$handler` (callable) - Called as `handler(string $body, mixed ...$arguments)`: the rendered body first, then the arguments resolved like function arguments; the return value replaces the whole block

**Returns:**
- `self`

**Throws:**
- `\InvalidArgumentException` - For a name that is not an identifier or that is a built-in directive

**Example:**

```php
$tsuku->registerDirective('wrap', fn(string $body, string $open, string $close): string => $open . $body . $close);

echo $tsuku->process('@wrap("[", "]"){product.sku}@end', ['product' => ['sku' => 'WID-001']]);
// Output: [WID-001]
```

Handler errors follow the strictness mode like function errors (`Directive 'name' error: ...`). Directives also work with `processToStream()`. Details: [Custom Directives](/guide/custom-directives).

### `registerFormatter()`

```php
public function registerFormatter(string $name, callable $handler): self
```

Stores the handler in the instance's `FormatterRegistry`. In 1.4.0 no template syntax uses formatters, so a registered formatter has no effect on the output - `@money(price)` after `registerFormatter('money', ...)` is still an unknown function:

```php
$tsuku->registerFormatter('money', fn($value) => '$' . $value);

echo $tsuku->process('[@money(price)]', ['price' => 5]);
// Output: []
```

Use `registerFunction()` for anything you want to call from a template.

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
        ['name' => 'Smith, Jane', 'email' => 'jane@example.com', 'status' => 'pending'],
    ]
];

$csv = $tsuku->process($template, $data);
file_put_contents('users.csv', $csv);
```

**Output (users.csv):**

```
Name,Email,Status
John Doe,john@example.com,active
"Smith, Jane",jane@example.com,pending
```

### With Custom Functions

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

// Register custom functions (registerFunction() returns $tsuku, so calls chain)
$tsuku
    ->registerFunction('badge', function(string $status): string {
        return match($status) {
            'active' => '✓ Active',
            'pending' => '⏳ Pending',
            'inactive' => '✗ Inactive',
            default => '? Unknown'
        };
    })
    ->registerFunction('format_phone', function(string $phone): string {
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

**Output:**

```
Name: John Doe
Status: ✓ Active
Phone: (555) 123-4567

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
            return '$' . number_format($amount, 2);
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
use Qoliber\Tsuku\Exception\FunctionNotFoundException;
use Qoliber\Tsuku\Exception\ParseException;
use Qoliber\Tsuku\Exception\TsukuException;

$tsuku = new Tsuku();

try {
    $result = $tsuku->process($template, $data);
} catch (ParseException $e) {
    // Syntax error in the template: message names line and column
    error_log(sprintf('Template error at %d:%d: %s', $e->getTemplateLine(), $e->getTemplateColumn(), $e->getMessage()));
    $result = '<!-- Template Error -->';
} catch (FunctionNotFoundException $e) {
    // A condition calls a function that is not registered
    error_log('Unknown function @' . $e->getFunctionName());
    $result = '<!-- Template Error -->';
} catch (TsukuException $e) {
    // Base class: STRICT-mode errors (missing variable, unknown function, handler error)
    error_log('Template processing failed: ' . $e->getMessage());
    $result = '<!-- Template Error -->';
}
```

`process()` does not catch exceptions thrown by functions used inside conditions; catch `\Throwable` as well if your functions can throw. See [Strictness Modes & Errors](/guide/strictness).

## Type Safety

All methods use strict type hints:

```php
// ✅ Correct - string template, array data
$result = $tsuku->process('Hello {name}', ['name' => 'John']);
// Output: Hello John

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

// ❌ Bad - creates new instance (and 28 built-in closures) each time
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
            return '$' . number_format($amount, 2);
        });

        // Other functions...
    }

    public function render(string $template, array $data): string
    {
        return $this->tsuku->process($template, $data);
    }
}
```

### Large Exports

For tens of thousands of rows use [`processToStream()`](#processtostream): the output is written piece by piece instead of being built as one string.

## Instance State

A Tsuku instance holds:

- its strictness mode and registries (functions, directives, formatters), and
- the warnings of the last `process()` / `processToStream()` call.

Templates and data are not kept between calls. Consequences:

- ✅ Safe: many `process()` calls on the same instance
- ✅ Safe: several instances in the same process
- ⚠️ Read `getWarnings()` right after the call it belongs to - the next call clears it
- ⚠️ Functions registered later are visible to every later call (and to every instance that shares the registry)

## Re-registering

A function can be replaced, but not removed:

```php
$tsuku->registerFunction('test', fn() => 'v1');
$tsuku->registerFunction('test', fn() => 'v2'); // Overwrites

echo $tsuku->process('@test()', []);
// Output: v2

// No method to unregister functions
```

## Requirements

- PHP 8.1+
- No extensions required

## Next Steps

- [Syntax Reference →](/api/syntax) - Complete template syntax
- [FunctionRegistry →](/api/function-registry) - Function management
- [Examples →](/examples/) - Real-world usage examples
