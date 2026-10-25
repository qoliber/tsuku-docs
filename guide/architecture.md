# Architecture

Tsuku uses a clean three-stage compiler pipeline inspired by traditional programming language design.

## The Three-Stage Pipeline

```
Template String → Lexer (+ whitespace control) → Tokens → Parser → AST → Compiler → Output String
```

Each stage has a single, well-defined responsibility. `Template\TemplateParser` runs the first two stages; `process()` and `processToStream()` share it, so both see exactly the same tree.

## Stage 1: Lexer (Lexical Analysis)

**Location:** `src/Lexer/Lexer.php`

**Purpose:** Break the raw template string into meaningful tokens.

### What the Lexer Does

First the Lexer removes `\` line continuations (a backslash followed by a line break). Then it scans the template and identifies:
- Plain text
- Variables (`{name}`)
- Functions (`@upper()`)
- Inline conditions (`@?{...}`) and the `@@` escape
- Directives (`@if`, `@elseif`, `@else`, `@for`, `@match`, `@case`, `@default`, registered custom directives)
- End markers (`@end`)

Finally `WhitespaceControl` (`src/Lexer/WhitespaceControl.php`) decides, once per template, which whitespace around block directives is layout and drops it from the text tokens - see [Whitespace Control](/guide/whitespace-control).

### Example

**Input:**
```
Hello {name}, @if(admin)welcome@end
```

**Output (Tokens):**
```php
[
    TEXT("Hello "),
    VARIABLE("name"),
    TEXT(", "),
    DIRECTIVE_IF("admin"),
    TEXT("welcome"),
    DIRECTIVE_END,
    EOF
]
```

Every token also carries the line and column where it starts, which is how error messages can say `at line 3, column 7`.

### Why a Lexer?

**Makes parsing easier** - Instead of working with a raw string, the parser works with structured chunks.

**Decides layout once** - Whitespace control works on the token list, before parsing, so rendering never re-scans text.

**Fast** - One pass through the template string, plus one pass over the tokens.

## Stage 2: Parser (Syntax Analysis)

**Location:** `src/Ast/Parser.php`

**Purpose:** Build an Abstract Syntax Tree (AST) from tokens.

### What the Parser Does

The Parser takes the flat list of tokens and builds a **tree structure** that represents the template's logical structure. The conditions of `@if`, `@unless`, `@elseif` and `@?{...}` are parsed here as well, by `ExpressionParser` (`src/Expression/`), into a small expression tree that is stored on the node.

### Example

**Tokens:**
```php
[TEXT("Hello "), VARIABLE("name"), TEXT(", "), DIRECTIVE_IF("admin"), TEXT("welcome"), DIRECTIVE_END, EOF]
```

**Output (AST):**
```
TemplateNode
├── TextNode("Hello ")
├── VariableNode("name")
├── TextNode(", ")
└── IfNode(condition: "admin", expression: PathExpression("admin"))
    └── TextNode("welcome")
```

### Why an AST?

**Handles nesting** - Tree structure naturally represents nested loops and conditionals.

**Validates structure** - Parser catches syntax errors (missing `@end`, a stray `@else`, malformed conditions, unknown functions in conditions) and reports them as a `ParseException` / `FunctionNotFoundException` with line and column.

**Clean compilation** - Compiler just walks the tree; conditions are already parsed.

## Stage 3: Compiler (Code Generation)

**Location:** `src/Compiler/Compiler.php`

**Purpose:** Walk the AST and generate the final output.

### What the Compiler Does

The Compiler uses the **Visitor Pattern** to traverse the AST tree and execute each node:

1. **TemplateNode** - Process all children
2. **TextNode** - Output text as-is
3. **VariableNode** - Look up variable in data, output value
4. **ForNode** - Iterate over array, process children for each item
5. **IfNode** - Evaluate the parsed condition, process appropriate children
6. **TernaryNode** - Evaluate the parsed condition, output one of the two values
7. **MatchNode** - Match value, process matching case
8. **FunctionNode** - Execute function, output result
9. **DirectiveNode** - Render the body, pass it to the registered directive handler

### Example

**AST:**
```
TemplateNode
├── TextNode("Hello ")
├── VariableNode("name")
├── TextNode(", ")
└── IfNode(condition: "admin")
    └── TextNode("welcome")
```

**Compilation (with data: `['name' => 'John', 'admin' => true]`):**

```
TemplateNode.accept(compiler)
  ├── TextNode.accept(compiler) → "Hello "
  ├── VariableNode.accept(compiler) → "John" (lookup in data)
  ├── TextNode.accept(compiler) → ", "
  └── IfNode.accept(compiler)
      └── admin is truthy → TextNode.accept(compiler) → "welcome"

Final Output: "Hello John, welcome"
```

### Why the Visitor Pattern?

**Separation of concerns** - AST structure separate from processing logic.

**Extensible** - Add new node types without changing existing code.

**Type-safe** - Each node knows how to process itself.

## Key Concepts

### Abstract Syntax Tree (AST)

A **tree representation** of your template's structure.

- **Root:** TemplateNode (contains all top-level nodes)
- **Branches:** IfNode, ForNode, MatchNode, DirectiveNode (contain children)
- **Leaves:** TextNode, VariableNode, FunctionNode, TernaryNode (produce output)

### Nodes

Each **element** in the AST tree. Nodes are immutable: their data is in public readonly properties.

**Node Types:**
- `TemplateNode` - Root container
- `TextNode` - Plain text content
- `VariableNode` - Variable reference
- `ForNode` - Loop directive
- `IfNode` - `@if` / `@unless` (an `@elseif` chain is a nested `IfNode` in the else branch)
- `MatchNode` - Pattern matching
- `CaseNode` - Match case
- `FunctionNode` - Function call
- `TernaryNode` - Inline condition `@?{...}`
- `DirectiveNode` - Registered custom block directive

### Tokens

The **smallest meaningful units** from the Lexer.

**Token Types:**
- `TEXT` - Plain text
- `VARIABLE` - `{name}`
- `FUNCTION` - `@upper(...)`
- `TERNARY` - `@?{...}`
- `DIRECTIVE_FOR` - `@for(...)`
- `DIRECTIVE_IF` - `@if(...)`
- `DIRECTIVE_UNLESS` - `@unless(...)`
- `DIRECTIVE_ELSE` - `@else`
- `DIRECTIVE_ELSEIF` - `@elseif(...)`
- `DIRECTIVE_MATCH` - `@match(...)`
- `DIRECTIVE_CASE` - `@case(...)`
- `DIRECTIVE_DEFAULT` - `@default`
- `DIRECTIVE_CUSTOM` - a registered custom directive
- `DIRECTIVE_END` - `@end`
- `EOF` - End of input

### Visitor Pattern

A design pattern where nodes **"accept"** a visitor (the compiler).

**Benefits:**
- Clean separation between tree structure and processing
- Easy to add new operations without changing nodes
- Type-safe method dispatch

**Implementation (simplified from the source):**
```php
interface Node {
    public function accept(NodeVisitor $visitor): mixed;
}

class TextNode implements Node {
    public function __construct(
        public readonly string $content,
    ) {}

    public function accept(NodeVisitor $visitor): mixed {
        return $visitor->visitText($this);
    }
}

class Compiler implements NodeVisitor, DirectiveVisitor {
    public function visitText(TextNode $node): string {
        return $node->content;
    }
}
```

`DirectiveNode` is visited through the separate `DirectiveVisitor` interface, so visitors written against `NodeVisitor` before 1.4.0 still compile.

## Class Structure

### Lexer Classes

- `Lexer` - Main lexer class
- `Token` - Represents one token (type, value, line, column)
- `TokenType` - Enum of all token types
- `WhitespaceControl` - Removes layout whitespace around block directives
- `SourceMap` - Maps offsets back to template lines and columns

### Parser Classes

- `Parser` - Main parser class
- `*Node` - AST node classes (TextNode, IfNode, etc.)
- `NodeVisitor`, `DirectiveVisitor` - Interfaces for visiting nodes

### Expression Classes

- `ExpressionParser` - Parses conditions into expression trees (at parse time)
- `ExpressionEvaluator` - Evaluates parsed conditions against the data
- `Expression` and its nodes (`OrExpression`, `AndExpression`, `NotExpression`, `ComparisonExpression`, `LiteralExpression`, `PathExpression`, `FunctionCallExpression`), `Membership` (rules of `in`)

### Compiler and Registries

- `Compiler` - Main compiler class (implements `NodeVisitor` and `DirectiveVisitor`)
- `FunctionRegistry` - Manages template functions
- `DirectiveRegistry` - Manages custom block directives
- `FormatterRegistry` - Stores formatters (not used by any template syntax in 1.4.0)

## Benefits of This Architecture

### ✅ Predictable Whitespace

Content lines are output exactly as written. Whitespace that only lays out directives (directive-only lines, indentation before `@else` / `@end`, ...) is identified once, in the lexer stage, by five fixed rules. See [Whitespace Control](/guide/whitespace-control).

### ✅ Proper Nesting Validation

The Parser builds a tree or throws a `ParseException` that names the line and column - including where the unclosed directive was opened. Unclosed directives cannot slip through.

### ✅ Clean Separation of Concerns

- Lexer: Tokenization and layout whitespace
- Parser: Structure building and condition parsing
- Compiler: Output generation

Each stage does one thing well.

### ✅ Easy to Extend

Most extensions need no core change at all:
- New template functions: `registerFunction()` ([Custom Functions](/guide/custom-functions))
- New block syntax `@name(...) ... @end`: `registerDirective()` ([Custom Directives](/guide/custom-directives))

New built-in syntax would mean a token type, lexer and parser support, a node class and a visit method in the Compiler.

### ✅ Fast Execution

Single pass through the tree. Conditions are parsed once per template into an expression tree and are not re-parsed for every row, and layout whitespace is decided before rendering. Regular expressions are used while lexing (variables, directive names) and in small, anchored checks when function arguments are resolved; the template text itself is never re-scanned during rendering.

### ✅ Type Safety

Full PHP 8.1+ type hints throughout:
- All nodes are strongly typed
- All methods have return type declarations
- Enums for token types and strictness modes
- Strict types enabled everywhere

## Comparison to Other Approaches

### Tsuku (Lexer → Parser → Compiler)

**Pros:**
- Clean architecture
- Easy to extend
- Type-safe
- Fast
- Proper error handling with line and column

**Cons:**
- More complex to understand initially
- More code (but cleaner)

### Regex Substitution

```php
// Simple regex approach
$output = preg_replace_callback('/{{(.+?)}}/', function($m) use ($data) {
    return $data[$m[1]] ?? '';
}, $template);
```

**Pros:**
- Simple for basic cases
- Less code

**Cons:**
- Doesn't handle nesting well
- Hard to extend
- Error-prone
- Harder to maintain

### Plain PHP Templates (e.g. Magento `.phtml`)

```php
// PHP code in templates
<?php foreach ($items as $item): ?>
    <div><?= $item ?></div>
<?php endforeach; ?>
```

**Pros:**
- Full PHP power

**Cons:**
- Templates can run any code - unsuitable for templates edited by non-developers
- Mixing logic and presentation
- Hard to sandbox
- No validation

## Implementation Example

Here's how a simple template flows through the pipeline:

**Template:**
```
@if(admin){name}@end
```

**Step 1 - Lexer:**
```php
$lexer = new Lexer('@if(admin){name}@end');
$tokens = $lexer->tokenize();
// [DIRECTIVE_IF("admin"), VARIABLE("name"), DIRECTIVE_END, EOF]
```

**Step 2 - Parser:**
```php
$parser = new Parser($tokens);
$ast = $parser->parse();
// TemplateNode([IfNode("admin", [VariableNode("name")])])
```

**Step 3 - Compiler:**
```php
$compiler = new Compiler(['admin' => true, 'name' => 'John']);
$output = $compiler->compile($ast);
// "John"
```

`Tsuku::process()` does the same, but passes its registries along (`new Lexer($template, $directiveNames)`, `new Parser($tokens, $sourceMap, $functionRegistry)`, `new Compiler($data, $context, $functionRegistry, $directiveRegistry)`), so custom functions and directives work. See [API Reference](/api/#advanced-direct-pipeline-access).

## Source Code Organization

```
src/
├── Ast/
│   ├── Parser.php              # AST building
│   ├── Node.php                # Node interface
│   ├── NodeVisitor.php         # Visitor interface
│   ├── DirectiveVisitor.php    # Visitor interface for custom directives
│   ├── TemplateNode.php        # Root node
│   ├── TextNode.php            # Text content
│   ├── VariableNode.php        # Variables
│   ├── ForNode.php             # Loops
│   ├── IfNode.php              # @if / @unless / @elseif
│   ├── MatchNode.php           # Pattern matching
│   ├── CaseNode.php            # Match cases
│   ├── FunctionNode.php        # Functions
│   ├── TernaryNode.php         # Inline conditions @?{...}
│   └── DirectiveNode.php       # Custom block directives
├── Compiler/
│   └── Compiler.php            # Main compiler
├── Exception/
│   ├── TsukuException.php      # Base exception
│   ├── ParseException.php
│   ├── FunctionNotFoundException.php
│   ├── DirectiveNotFoundException.php
│   ├── FormatterNotFoundException.php
│   └── InvalidTemplateException.php
├── Expression/
│   ├── ExpressionParser.php    # Condition parsing
│   ├── ExpressionEvaluator.php # Condition evaluation
│   ├── Expression.php          # Expression node interface
│   ├── AndExpression.php
│   ├── OrExpression.php
│   ├── NotExpression.php
│   ├── ComparisonExpression.php
│   ├── LiteralExpression.php
│   ├── PathExpression.php
│   ├── FunctionCallExpression.php
│   ├── InlineCondition.php     # Parsed @?{...}
│   └── Membership.php          # Rules of "in" / @contains
├── Function/
│   └── FunctionRegistry.php    # Function management
├── Lexer/
│   ├── Lexer.php               # Tokenization
│   ├── Token.php               # Token representation
│   ├── TokenType.php           # Token types enum
│   ├── WhitespaceControl.php   # Layout whitespace rules
│   └── SourceMap.php           # Offsets → line/column
├── Streaming/
│   └── StreamingProcessor.php  # processToStream()
├── Support/
│   ├── DataAccessor.php        # Dot-notation lookup (internal)
│   └── Syntax.php              # Quote-aware scanning (internal)
├── Template/
│   ├── Template.php            # Template class
│   ├── TemplateInterface.php
│   └── TemplateParser.php      # Lexer + parser
├── DirectiveRegistry.php       # Custom directive management
├── FormatterRegistry.php
├── ProcessingContext.php       # Strictness mode + warnings
├── StrictnessMode.php          # SILENT / WARNING / STRICT
└── Tsuku.php                   # Main API
```

## Further Reading

**Compiler Theory:**
- [Compilers: Principles, Techniques, and Tools](https://en.wikipedia.org/wiki/Compilers:_Principles,_Techniques,_and_Tools) (Dragon Book)
- [Abstract Syntax Tree](https://en.wikipedia.org/wiki/Abstract_syntax_tree)

**Design Patterns:**
- [Visitor Pattern](https://refactoring.guru/design-patterns/visitor)
- [Interpreter Pattern](https://refactoring.guru/design-patterns/interpreter)

**Similar Architectures:**
- Twig (PHP) - Lexer → Parser → Compiler
- Babel (JavaScript) - Parse → Transform → Generate
- TypeScript Compiler - Scanner → Parser → Binder → Checker → Emitter

## Next Steps

- [Best Practices →](/guide/best-practices) - Use the architecture effectively
- [API Reference →](/api/) - Dive into the code
- [Examples →](/examples/) - See it in action
