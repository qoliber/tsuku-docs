# Architecture

Tsuku uses a clean three-stage compiler pipeline inspired by traditional programming language design.

## The Three-Stage Pipeline

```
Template String → Lexer → Tokens → Parser → AST → Compiler → Output String
```

Each stage has a single, well-defined responsibility.

## Stage 1: Lexer (Lexical Analysis)

**Location:** `src/Lexer/Lexer.php`

**Purpose:** Break the raw template string into meaningful tokens.

### What the Lexer Does

The Lexer reads your template character by character and identifies:
- Plain text
- Variables (`{name}`)
- Directives (`@if`, `@for`, `@match`)
- Functions (`@upper()`)
- End markers (`@end`)

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
    DIRECTIVE_END
]
```

### Why a Lexer?

**Makes parsing easier** - Instead of working with a raw string, the parser works with structured chunks.

**Preserves whitespace** - Every character is captured exactly, including spaces and newlines.

**Fast** - Single pass through the template string.

## Stage 2: Parser (Syntax Analysis)

**Location:** `src/Ast/Parser.php`

**Purpose:** Build an Abstract Syntax Tree (AST) from tokens.

### What the Parser Does

The Parser takes the flat list of tokens and builds a **tree structure** that represents the template's logical structure.

### Example

**Tokens:**
```php
[TEXT("Hello "), VARIABLE("name"), DIRECTIVE_IF("admin"), TEXT("welcome"), DIRECTIVE_END]
```

**Output (AST):**
```
TemplateNode
├── TextNode("Hello ")
├── VariableNode("name")
└── IfNode(condition: "admin")
    └── TextNode("welcome")
```

### Why an AST?

**Handles nesting** - Tree structure naturally represents nested loops and conditionals.

**Validates structure** - Parser catches syntax errors (missing `@end`, etc.).

**Clean compilation** - Compiler just walks the tree, no parsing logic needed.

## Stage 3: Compiler (Code Generation)

**Location:** `src/Compiler/Compiler.php`

**Purpose:** Walk the AST and generate the final output.

### What the Compiler Does

The Compiler uses the **Visitor Pattern** to traverse the AST tree and execute each node:

1. **TemplateNode** - Process all children
2. **TextNode** - Output text as-is
3. **VariableNode** - Look up variable in data, output value
4. **ForNode** - Iterate over array, process children for each item
5. **IfNode** - Evaluate condition, process appropriate children
6. **MatchNode** - Match value, process matching case
7. **FunctionNode** - Execute function, output result

### Example

**AST:**
```
TemplateNode
├── TextNode("Hello ")
├── VariableNode("name")
└── IfNode(condition: "admin")
    └── TextNode("welcome")
```

**Compilation (with data: `['name' => 'John', 'admin' => true]`):**

```
TemplateNode.accept(compiler)
  ├── TextNode.accept(compiler) → "Hello "
  ├── VariableNode.accept(compiler) → "John" (lookup in data)
  └── IfNode.accept(compiler)
      └── if (admin == true) TextNode.accept(compiler) → "welcome"

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
- **Branches:** IfNode, ForNode, MatchNode (contain children)
- **Leaves:** TextNode, VariableNode, FunctionNode (produce output)

### Nodes

Each **element** in the AST tree.

**Node Types:**
- `TemplateNode` - Root container
- `TextNode` - Plain text content
- `VariableNode` - Variable reference
- `ForNode` - Loop directive
- `IfNode` - Conditional directive
- `MatchNode` - Pattern matching
- `CaseNode` - Match case
- `FunctionNode` - Function call
- `TernaryNode` - Ternary operator

### Tokens

The **smallest meaningful units** from the Lexer.

**Token Types:**
- `TEXT` - Plain text
- `VARIABLE` - `{name}`
- `DIRECTIVE_FOR` - `@for(...)`
- `DIRECTIVE_IF` - `@if(...)`
- `DIRECTIVE_MATCH` - `@match(...)`
- `DIRECTIVE_CASE` - `@case(...)`
- `DIRECTIVE_DEFAULT` - `@default`
- `DIRECTIVE_ELSE` - `@else`
- `DIRECTIVE_END` - `@end`
- `FUNCTION` - `@upper(...)`
- `EOF` - End of file

### Visitor Pattern

A design pattern where nodes **"accept"** a visitor (the compiler).

**Benefits:**
- Clean separation between tree structure and processing
- Easy to add new operations without changing nodes
- Type-safe method dispatch

**Implementation:**
```php
interface Node {
    public function accept(NodeVisitor $visitor): string;
}

class TextNode implements Node {
    public function accept(NodeVisitor $visitor): string {
        return $visitor->visitText($this);
    }
}

class Compiler implements NodeVisitor {
    public function visitText(TextNode $node): string {
        return $node->text;
    }
}
```

## Class Structure

### Lexer Classes

- `Lexer` - Main lexer class
- `Token` - Represents one token
- `TokenType` - Enum of all token types

### Parser Classes

- `Parser` - Main parser class
- `*Node` - AST node classes (TextNode, IfNode, etc.)
- `NodeVisitor` - Interface for visiting nodes

### Compiler Classes

- `Compiler` - Main compiler class (implements NodeVisitor)
- `ExpressionEvaluator` - Evaluates conditions
- `FunctionRegistry` - Manages template functions

## Benefits of This Architecture

### ✅ Exact Whitespace Preservation

The Lexer captures every character, including spaces and newlines. Your output matches your template exactly.

### ✅ Proper Nesting Validation

The Parser builds a tree or throws an error. Impossible to have unclosed directives.

### ✅ Clean Separation of Concerns

- Lexer: Tokenization
- Parser: Structure building
- Compiler: Output generation

Each stage does one thing well.

### ✅ Easy to Extend

Want to add a new directive?
1. Add token type to Lexer
2. Create Node class
3. Add visit method to Compiler
4. Done!

No need to rewrite existing code.

### ✅ Fast Execution

Single pass through the tree. No repeated parsing or regex matching during compilation.

### ✅ Type Safety

Full PHP 8.1+ type hints throughout:
- All nodes are strongly typed
- All methods have return type declarations
- Enums for token types
- Strict types enabled everywhere

## Comparison to Other Approaches

### Tsuku (Lexer → Parser → Compiler)

**Pros:**
- Clean architecture
- Easy to extend
- Type-safe
- Fast
- Proper error handling

**Cons:**
- More complex to understand initially
- More code (but cleaner)

### Regex-Based (Blade, Mustache)

```php
// Simple regex approach
$output = preg_replace_callback('/{{(.+?)}}/', function($m) {
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

### PHP Eval (Magento)

```php
// PHP code in templates (risky!)
<?php foreach ($items as $item): ?>
    <div><?= $item ?></div>
<?php endforeach; ?>
```

**Pros:**
- Full PHP power

**Cons:**
- Security risks (code injection)
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

## Source Code Organization

```
src/
├── Lexer/
│   ├── Lexer.php          # Tokenization
│   ├── Token.php          # Token representation
│   └── TokenType.php      # Token types enum
├── Ast/
│   ├── Parser.php         # AST building
│   ├── Node.php           # Base node interface
│   ├── NodeVisitor.php    # Visitor interface
│   ├── TemplateNode.php   # Root node
│   ├── TextNode.php       # Text content
│   ├── VariableNode.php   # Variables
│   ├── ForNode.php        # Loops
│   ├── IfNode.php         # Conditionals
│   ├── MatchNode.php      # Pattern matching
│   ├── CaseNode.php       # Match cases
│   ├── FunctionNode.php   # Functions
│   └── TernaryNode.php    # Ternary operator
├── Compiler/
│   ├── Compiler.php       # Main compiler
│   └── ExpressionEvaluator.php # Condition evaluation
├── Function/
│   └── FunctionRegistry.php # Function management
├── Template/
│   ├── Template.php       # Template class
│   └── TemplateInterface.php
└── Tsuku.php             # Main API
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
- TypeScript Compiler - Scanner → Parser → Checker → Emitter

## Next Steps

- [Best Practices →](/guide/best-practices) - Use the architecture effectively
- [API Reference →](/api/) - Dive into the code
- [Examples →](/examples/) - See it in action
