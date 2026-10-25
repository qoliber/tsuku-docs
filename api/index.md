# API Reference

Complete API documentation for Tsuku classes and methods (1.4.0).

## Overview

Tsuku provides a simple, clean API for template processing with full PHP 8.1+ type safety. Almost everything goes through one class, `Qoliber\Tsuku\Tsuku`; the lower-level classes below are public too, for tooling and for advanced use such as reusing a parsed template.

## Main Classes

### [Tsuku →](/api/tsuku)

The main entry point for template processing.

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();
$result = $tsuku->process($template, $data);
```

**Key Methods:**
- `__construct(StrictnessMode $strictnessMode = StrictnessMode::SILENT, DirectiveRegistry $directiveRegistry = new DirectiveRegistry(), FormatterRegistry $formatterRegistry = new FormatterRegistry(), FunctionRegistry $functionRegistry = new FunctionRegistry())`
- `process(string $template, array $data, ?StrictnessMode $strictnessMode = null): string` - Process a template with data
- `processToStream(string $template, array $contextData, iterable $rows, string $rowsVariable, callable $writer, ?StrictnessMode $strictnessMode = null): void` - Render row by row through a writer ([Streaming](/guide/streaming))
- `getWarnings(): array`, `hasWarnings(): bool` - Warnings of the last call in `WARNING` mode
- `registerFunction(string $name, callable $handler): self` - Register a custom function
- `registerDirective(string $name, callable $handler): self` - Register a custom block directive ([Custom Directives](/guide/custom-directives))
- `registerFormatter(string $name, callable $handler): self` - Stores a formatter; no template syntax uses formatters in 1.4.0

### [FunctionRegistry →](/api/function-registry)

Manages all template functions (built-in and custom): `register()`, `execute()`, `has()`.

```php
use Qoliber\Tsuku\Tsuku;
use Qoliber\Tsuku\Function\FunctionRegistry;

$registry = new FunctionRegistry();
$registry->register('double', fn($x) => $x * 2);

$tsuku = new Tsuku(functionRegistry: $registry);
echo $tsuku->process('@double(qty)', ['qty' => 21]);
// Output: 42
```

`Tsuku` has no getter for its registry - create the registry yourself and pass it in when you need direct access.

### DirectiveRegistry

`Qoliber\Tsuku\DirectiveRegistry` holds the handlers of custom block directives.

```php
class DirectiveRegistry
{
    public const RESERVED = ['for', 'if', 'unless', 'elseif', 'else', 'match', 'case', 'default', 'end'];

    public function register(string $name, callable $handler): void; // \InvalidArgumentException for invalid or reserved names
    public function get(string $name): callable;                      // DirectiveNotFoundException
    public function has(string $name): bool;
    public function names(): array;                                   // registered names
}
```

### StrictnessMode

```php
namespace Qoliber\Tsuku;

enum StrictnessMode: int
{
    case SILENT = 0;   // missing variables/functions render as "", no warnings (default)
    case WARNING = 1;  // same output, plus a warning per problem (getWarnings())
    case STRICT = 2;   // throw TsukuException
}
```

See [Strictness Modes & Errors](/guide/strictness).

## Core Components

The pipeline behind `process()`: Lexer → Parser → Compiler. `Template\TemplateParser` runs the first two steps; `process()` and `processToStream()` both use it.

### Lexer

**Location:** `src/Lexer/Lexer.php`

Tokenizes template strings into meaningful tokens.

```php
use Qoliber\Tsuku\Lexer\Lexer;

$lexer = new Lexer($template);                      // or new Lexer($template, $directiveRegistry->names())
$tokens = $lexer->tokenize();
```

**Methods:**
- `__construct(string $input, array $blockDirectives = [])` - removes `\` line continuations; `$blockDirectives` are the names of registered custom directives (without them, `@name` of a custom directive is not recognised)
- `tokenize(): array<Token>` - Tokens with [whitespace control](/guide/whitespace-control) applied, ending with an `EOF` token
- `tokenizeRaw(): array<Token>` - Tokens exactly as written: no whitespace control, no `EOF`
- `getSourceMap(): SourceMap` - Maps offsets back to template line/column (used for error positions)

### Parser

**Location:** `src/Ast/Parser.php`

Builds the Abstract Syntax Tree (AST) from tokens.

```php
use Qoliber\Tsuku\Ast\Parser;

$parser = new Parser($tokens, $lexer->getSourceMap(), $functionRegistry);
$ast = $parser->parse();
```

**Methods:**
- `__construct(array $tokens, ?SourceMap $sourceMap = null, ?FunctionRegistry $functionRegistry = null)` - `$tokens` must end with `EOF` (as returned by `tokenize()`). With a function registry, unknown functions in conditions are rejected while parsing (otherwise when the condition is evaluated); with the source map, error positions inside conditions are exact.
- `parse(): TemplateNode` - Build the AST; throws `ParseException`

Conditions of `@if`, `@unless`, `@elseif` and `@?{...}` are parsed here, once, into an `Expression` tree (`IfNode::$expression`, `TernaryNode::$expression`).

### TemplateParser

**Location:** `src/Template/TemplateParser.php`

Lexer and parser in one call, wired the way `process()` does it:

```php
use Qoliber\Tsuku\Template\TemplateParser;

$ast = (new TemplateParser($functionRegistry, $directiveRegistry))->parse($template); // TemplateNode
```

Both constructor arguments are optional (`?FunctionRegistry`, `?DirectiveRegistry`).

### Compiler

**Location:** `src/Compiler/Compiler.php`

Compiles the AST to the final output string. Implements `NodeVisitor` and `DirectiveVisitor`.

```php
use Qoliber\Tsuku\Compiler\Compiler;

$compiler = new Compiler($data, $context, $functionRegistry, $directiveRegistry);
$output = $compiler->compile($ast);
```

**Methods:**
- `__construct(array $data, ?ProcessingContext $context = null, ?FunctionRegistry $functionRegistry = null, ?DirectiveRegistry $directiveRegistry = null)` - without a context the mode is `SILENT`; without a function registry a new one (built-ins only) is used; without a directive registry a custom directive node throws `DirectiveNotFoundException`
- `compile(TemplateNode $ast): string` - Compile AST to output

### Expressions

**Location:** `src/Expression/`

- `ExpressionParser(?FunctionRegistry)` - `parseCondition(string $source, ?callable $position = null): Expression`, `parseInlineCondition(string $source, ?callable $position = null): InlineCondition`; throws `ParseException` / `FunctionNotFoundException` (`$position` maps an offset to `[line, column]` for error messages)
- `ExpressionEvaluator(?FunctionRegistry)` - `evaluate(string $condition, array $data): bool` and `parse(string $condition): Expression` (both cache the parsed condition), `evaluateExpression(Expression $expression, array $data): bool`, `callFunction()`, static `isTruthy()` and `compare()`
- `Expression` (interface, `evaluate(ExpressionEvaluator $evaluator, array $data): mixed`) with `OrExpression`, `AndExpression`, `NotExpression`, `ComparisonExpression`, `LiteralExpression`, `PathExpression`, `FunctionCallExpression`
- `Membership::contains($haystack, $needle): bool` - the rules of `in` and `@contains()`

```php
use Qoliber\Tsuku\Expression\ExpressionEvaluator;
use Qoliber\Tsuku\Function\FunctionRegistry;

$evaluator = new ExpressionEvaluator(new FunctionRegistry());
var_dump($evaluator->evaluate('qty > 0 and "sale" in tags', ['qty' => 1, 'tags' => ['sale']]));
// bool(true)
```

### Other classes

- `ProcessingContext(StrictnessMode $strictnessMode = StrictnessMode::SILENT)` - holds the mode and collects warnings: `getStrictnessMode()`, `addWarning()`, `getWarnings()`, `hasWarnings()`, `clearWarnings()`
- `Template\Template(string $template)` - `render(array $data, ProcessingContext $context, DirectiveRegistry $directives, FormatterRegistry $formatters, ?FunctionRegistry $functionRegistry = null): string` (implements `Template\TemplateInterface`)
- `Streaming\StreamingProcessor` - the implementation of `processToStream()`
- `Support\DataAccessor`, `Support\Syntax` - internal helpers (`@internal`), not part of the public API

## Node Types

All nodes implement the `Node` interface:

```php
namespace Qoliber\Tsuku\Ast;

interface Node
{
    public function accept(NodeVisitor $visitor): mixed;
}
```

Nodes are immutable value objects: their data is in **public readonly properties** (there are no getters).

```php
class TemplateNode implements Node
{
    public readonly array $children;          // Node[]
}

class TextNode implements Node
{
    public readonly string $content;
}

class VariableNode implements Node             // {name}
{
    public readonly string $name;              // the path, e.g. "product.price"
}

class ForNode implements Node                  // @for(collection as itemVar, keyVar)
{
    public readonly string $collection;
    public readonly string $itemVar;
    public readonly ?string $keyVar;
    public readonly array $children;
}

class IfNode implements Node                   // @if / @unless / @elseif
{
    public readonly string $condition;         // condition source text
    public readonly bool $negate;              // true for @unless
    public readonly array $children;
    public readonly array $elseChildren;       // an @elseif chain is one nested IfNode here
    public readonly ?Expression $expression;   // parsed condition (null = parsed when rendered)
}

class MatchNode implements Node                // @match(expression)
{
    public readonly string $expression;
    public readonly array $cases;              // CaseNode[]
    public readonly array $defaultChildren;
}

class CaseNode implements Node                 // @case(values...)
{
    public readonly array $values;             // argument source texts
    public readonly array $children;
}

class FunctionNode implements Node             // @name(arguments)
{
    public readonly string $name;
    public readonly array $arguments;          // argument source texts
}

class TernaryNode implements Node              // @?{condition "a" : "b"}
{
    public readonly string $condition;
    public readonly string $trueValue;         // value source texts
    public readonly string $falseValue;
    public readonly ?Expression $expression;
}

class DirectiveNode implements Node            // registered @name(arguments) ... @end
{
    public readonly string $name;
    public readonly array $arguments;
    public readonly array $children;
}
```

(The properties are promoted constructor parameters; `IfNode::$elseChildren`, `IfNode::$expression`, `TernaryNode::$expression` and the `MatchNode`/`CaseNode` lists are optional constructor arguments.)

### Visitors

`NodeVisitor` declares `visitTemplate`, `visitText`, `visitVariable`, `visitFor`, `visitIf`, `visitTernary`, `visitFunction`, `visitMatch` and `visitCase`, each returning `mixed`. Custom directives are visited through the separate `DirectiveVisitor` interface (`visitDirective(DirectiveNode $node): mixed`), so `NodeVisitor` implementations written for 1.3 stay valid; a `DirectiveNode` throws a `LogicException` when it is visited by a visitor that does not implement `DirectiveVisitor`.

## Tokens

### Token

```php
namespace Qoliber\Tsuku\Lexer;

class Token
{
    public readonly TokenType $type;
    public readonly string $value;      // text, variable path, directive arguments, "name|args" for functions
    public readonly int $line;          // 1-based, in the template as written
    public readonly int $column;        // 1-based
    public readonly int $valueOffset;   // offset of $value in the preprocessed template, -1 when unknown
}
```

### Token Types

Defined in `src/Lexer/TokenType.php`:

```php
enum TokenType: string
{
    case TEXT = 'TEXT';                         // Plain text (also the "@" of an "@@" escape)
    case VARIABLE = 'VARIABLE';                 // {variable}
    case FUNCTION = 'FUNCTION';                 // @functionName(args), value "name|args"
    case TERNARY = 'TERNARY';                   // @?{condition "true" : "false"}
    case DIRECTIVE_FOR = 'DIRECTIVE_FOR';       // @for(...)
    case DIRECTIVE_IF = 'DIRECTIVE_IF';         // @if(...)
    case DIRECTIVE_UNLESS = 'DIRECTIVE_UNLESS'; // @unless(...)
    case DIRECTIVE_ELSE = 'DIRECTIVE_ELSE';     // @else
    case DIRECTIVE_ELSEIF = 'DIRECTIVE_ELSEIF'; // @elseif(...)
    case DIRECTIVE_MATCH = 'DIRECTIVE_MATCH';   // @match(...)
    case DIRECTIVE_CASE = 'DIRECTIVE_CASE';     // @case(...)
    case DIRECTIVE_DEFAULT = 'DIRECTIVE_DEFAULT'; // @default
    case DIRECTIVE_CUSTOM = 'DIRECTIVE_CUSTOM'; // registered block directive, value "name|args"
    case DIRECTIVE_END = 'DIRECTIVE_END';       // @end
    case EOF = 'EOF';                           // End of input
}
```

Helper methods: `isBlockDirective()` (every `DIRECTIVE_*` case - these take part in whitespace control), `opensBlock()` (`for`, `if`, `unless`, `match`, custom) and `isBranch()` (`else`, `elseif`, `case`, `default`).

## Exception Handling

All Tsuku exceptions extend `TsukuException`, which extends PHP's `\Exception`:

```
Qoliber\Tsuku\Exception\TsukuException
├── ParseException              invalid syntax or malformed condition; getTemplateLine(), getTemplateColumn()
├── FunctionNotFoundException   unknown function in a condition or in FunctionRegistry::execute(); getFunctionName()
├── DirectiveNotFoundException  DirectiveRegistry::get() / a Compiler without the directive's handler
├── FormatterNotFoundException  FormatterRegistry::get()
└── InvalidTemplateException    defined, not thrown by Tsuku 1.4.0
```

`TsukuException` itself is thrown in `STRICT` mode (a missing `{variable}`, an unknown function in output, a function or directive handler error). Outside this hierarchy: `registerDirective()` throws `\InvalidArgumentException` for invalid or reserved names, `processToStream()` throws `\RuntimeException` when the streamed `@for` is missing or ambiguous, and an exception thrown by a function **inside a condition** reaches you unchanged.

**Example:**

```php
use Qoliber\Tsuku\Exception\ParseException;
use Qoliber\Tsuku\Exception\TsukuException;

try {
    $result = $tsuku->process($template, $data);
} catch (ParseException $e) {
    error_log("Template error at line {$e->getTemplateLine()}, column {$e->getTemplateColumn()}: {$e->getMessage()}");
} catch (TsukuException $e) {
    error_log('Template error: ' . $e->getMessage());
    // Handle error...
}
```

## Type Safety

All classes use strict types and full type hints:

```php
declare(strict_types=1);

namespace Qoliber\Tsuku;

class Tsuku
{
    public function process(string $template, array $data, ?StrictnessMode $strictnessMode = null): string
    {
        // Implementation...
    }

    public function registerFunction(string $name, callable $handler): self
    {
        // Implementation...
    }
}
```

## Usage Patterns

### Basic Usage

```php
$tsuku = new Tsuku();
$output = $tsuku->process($template, $data);
```

### With Custom Functions

```php
$tsuku = new Tsuku();

$tsuku->registerFunction('currency', function(float $amount): string {
    return '$' . number_format($amount, 2);
});

$output = $tsuku->process('Total: @currency(total)', ['total' => 1234.5]);
// Output: Total: $1,234.50
```

### Advanced: Direct Pipeline Access

```php
use Qoliber\Tsuku\Lexer\Lexer;
use Qoliber\Tsuku\Ast\Parser;
use Qoliber\Tsuku\Compiler\Compiler;
use Qoliber\Tsuku\DirectiveRegistry;
use Qoliber\Tsuku\Function\FunctionRegistry;

$functions = new FunctionRegistry();
$directives = new DirectiveRegistry();

// Stage 1: Tokenize (the lexer needs the custom directive names)
$lexer = new Lexer($template, $directives->names());
$tokens = $lexer->tokenize();

// Stage 2: Parse
$parser = new Parser($tokens, $lexer->getSourceMap(), $functions);
$ast = $parser->parse();

// Stage 3: Compile (reusable for different data)
$output1 = (new Compiler($data1, null, $functions, $directives))->compile($ast);
$output2 = (new Compiler($data2, null, $functions, $directives))->compile($ast);
```

Pass the **same** registries to every stage. A `Compiler` created with only `$data` knows the built-in functions but none of your custom functions (they render as missing) and no custom directives.

## Performance Considerations

### Reuse Tsuku Instance

```php
// Good - reuse instance
$tsuku = new Tsuku();
foreach ($batches as $batch) {
    $output = $tsuku->process($template, $batch);
}

// Bad - create new instance each time
foreach ($batches as $batch) {
    $tsuku = new Tsuku();
    $output = $tsuku->process($template, $batch);
}
```

### Cache Parsed AST

`process()` lexes and parses the template on every call. To render one template with many data sets, parse it once:

```php
use Qoliber\Tsuku\Compiler\Compiler;
use Qoliber\Tsuku\DirectiveRegistry;
use Qoliber\Tsuku\Function\FunctionRegistry;
use Qoliber\Tsuku\ProcessingContext;
use Qoliber\Tsuku\StrictnessMode;
use Qoliber\Tsuku\Template\TemplateParser;

$functions = new FunctionRegistry();
$functions->register('shout', fn(string $s): string => strtoupper($s) . '!');
$directives = new DirectiveRegistry();

// Parse once
$ast = (new TemplateParser($functions, $directives))->parse("@for(items as item)\n- @shout(item)\n@end");

// Compile multiple times with different data
$context = new ProcessingContext(StrictnessMode::SILENT);
foreach ([['items' => ['a', 'b']], ['items' => ['c']]] as $data) {
    echo (new Compiler($data, $context, $functions, $directives))->compile($ast);
}
// - A!
// - B!
// - C!
```

A `ProcessingContext` used this way collects warnings across all compilations (call `clearWarnings()` yourself). For very large outputs, [`processToStream()`](/guide/streaming) is usually the better tool.

## Requirements

- **PHP:** 8.1 or higher
- **Extensions:** None (pure PHP)

## Installation

```bash
composer require qoliber/tsuku
```

## Next Steps

- [Tsuku Class →](/api/tsuku) - Main API class
- [Syntax Reference →](/api/syntax) - Complete syntax guide
- [FunctionRegistry →](/api/function-registry) - Function management
