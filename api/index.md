# API Reference

Complete API documentation for Tsuku classes and methods.

## Overview

Tsuku provides a simple, clean API for template processing with full PHP 8.1+ type safety.

## Main Classes

### [Tsuku →](/api/tsuku)

The main entry point for template processing.

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();
$result = $tsuku->process($template, $data);
```

**Key Methods:**
- `process(string $template, array $data): string` - Process a template with data
- `registerFunction(string $name, callable $callback): void` - Register custom function
- `getFunctionRegistry(): FunctionRegistry` - Get function registry

### [FunctionRegistry →](/api/function-registry)

Manages all template functions (built-in and custom).

```php
$registry = $tsuku->getFunctionRegistry();
$registry->register('myFunc', fn($x) => $x * 2);
```

## Core Components

### Lexer

**Location:** `src/Lexer/Lexer.php`

Tokenizes template strings into meaningful tokens.

```php
use Qoliber\Tsuku\Lexer\Lexer;

$lexer = new Lexer($template);
$tokens = $lexer->tokenize();
```

**Methods:**
- `tokenize(): array<Token>` - Convert template to tokens

### Parser

**Location:** `src/Ast/Parser.php`

Builds Abstract Syntax Tree (AST) from tokens.

```php
use Qoliber\Tsuku\Ast\Parser;

$parser = new Parser($tokens);
$ast = $parser->parse();
```

**Methods:**
- `parse(): TemplateNode` - Build AST from tokens

### Compiler

**Location:** `src/Compiler/Compiler.php`

Compiles AST to final output string.

```php
use Qoliber\Tsuku\Compiler\Compiler;

$compiler = new Compiler($data);
$output = $compiler->compile($ast);
```

**Methods:**
- `compile(Node $node): string` - Compile AST to output

## Node Types

All nodes implement the `Node` interface:

```php
namespace Qoliber\Tsuku\Ast;

interface Node
{
    public function accept(NodeVisitor $visitor): string;
}
```

### TemplateNode

Root node containing all top-level nodes.

```php
class TemplateNode implements Node
{
    public function __construct(
        private readonly array $children
    ) {}

    public function getChildren(): array;
}
```

### TextNode

Plain text content.

```php
class TextNode implements Node
{
    public function __construct(
        private readonly string $text
    ) {}

    public function getText(): string;
}
```

### VariableNode

Variable reference (`{name}`).

```php
class VariableNode implements Node
{
    public function __construct(
        private readonly string $path
    ) {}

    public function getPath(): string;
}
```

### ForNode

Loop directive (`@for(...)`).

```php
class ForNode implements Node
{
    public function __construct(
        private readonly string $arrayPath,
        private readonly string $itemVar,
        private readonly ?string $keyVar,
        private readonly array $children
    ) {}

    public function getArrayPath(): string;
    public function getItemVar(): string;
    public function getKeyVar(): ?string;
    public function getChildren(): array;
}
```

### IfNode

Conditional directive (`@if(...)`).

```php
class IfNode implements Node
{
    public function __construct(
        private readonly string $condition,
        private readonly array $thenChildren,
        private readonly array $elseChildren
    ) {}

    public function getCondition(): string;
    public function getThenChildren(): array;
    public function getElseChildren(): array;
}
```

### MatchNode

Pattern matching directive (`@match(...)`).

```php
class MatchNode implements Node
{
    public function __construct(
        private readonly string $expression,
        private readonly array $cases,
        private readonly array $defaultChildren
    ) {}

    public function getExpression(): string;
    public function getCases(): array;
    public function getDefaultChildren(): array;
}
```

### CaseNode

Match case (`@case(...)`).

```php
class CaseNode implements Node
{
    public function __construct(
        private readonly array $values,
        private readonly array $children
    ) {}

    public function getValues(): array;
    public function getChildren(): array;
}
```

### FunctionNode

Function call (`@upper(...)`).

```php
class FunctionNode implements Node
{
    public function __construct(
        private readonly string $name,
        private readonly array $arguments
    ) {}

    public function getName(): string;
    public function getArguments(): array;
}
```

### TernaryNode

Ternary operator (`condition ? true : false`).

```php
class TernaryNode implements Node
{
    public function __construct(
        private readonly string $condition,
        private readonly string $trueValue,
        private readonly string $falseValue
    ) {}

    public function getCondition(): string;
    public function getTrueValue(): string;
    public function getFalseValue(): string;
}
```

## Token Types

Defined in `src/Lexer/TokenType.php`:

```php
enum TokenType: string
{
    case TEXT = 'TEXT';
    case VARIABLE = 'VARIABLE';
    case DIRECTIVE_FOR = 'DIRECTIVE_FOR';
    case DIRECTIVE_IF = 'DIRECTIVE_IF';
    case DIRECTIVE_UNLESS = 'DIRECTIVE_UNLESS';
    case DIRECTIVE_MATCH = 'DIRECTIVE_MATCH';
    case DIRECTIVE_CASE = 'DIRECTIVE_CASE';
    case DIRECTIVE_DEFAULT = 'DIRECTIVE_DEFAULT';
    case DIRECTIVE_ELSE = 'DIRECTIVE_ELSE';
    case DIRECTIVE_END = 'DIRECTIVE_END';
    case FUNCTION = 'FUNCTION';
    case EOF = 'EOF';
}
```

## Exception Handling

All Tsuku exceptions extend `TemplateException`:

```php
namespace Qoliber\Tsuku\Exception;

class TemplateException extends \RuntimeException {}
```

**Common Exceptions:**
- `TemplateException` - Base exception
- `LexerException` - Tokenization errors
- `ParserException` - Parsing errors
- `CompilerException` - Compilation errors

**Example:**

```php
use Qoliber\Tsuku\Exception\TemplateException;

try {
    $result = $tsuku->process($template, $data);
} catch (TemplateException $e) {
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
    public function process(string $template, array $data): string
    {
        // Implementation...
    }

    public function registerFunction(string $name, callable $callback): void
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
    return ' . number_format($amount, 2);
});

$output = $tsuku->process($template, $data);
```

### Advanced: Direct Pipeline Access

```php
use Qoliber\Tsuku\Lexer\Lexer;
use Qoliber\Tsuku\Ast\Parser;
use Qoliber\Tsuku\Compiler\Compiler;

// Stage 1: Tokenize
$lexer = new Lexer($template);
$tokens = $lexer->tokenize();

// Stage 2: Parse
$parser = new Parser($tokens);
$ast = $parser->parse();

// Stage 3: Compile (reusable for different data)
$compiler1 = new Compiler($data1);
$output1 = $compiler1->compile($ast);

$compiler2 = new Compiler($data2);
$output2 = $compiler2->compile($ast);
```

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

```php
// Parse once
$lexer = new Lexer($template);
$tokens = $lexer->tokenize();
$parser = new Parser($tokens);
$ast = $parser->parse();

// Compile multiple times with different data
foreach ($dataSets as $data) {
    $compiler = new Compiler($data);
    $output = $compiler->compile($ast);
}
```

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
