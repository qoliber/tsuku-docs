---
layout: home

hero:
  name: "Tsuku"
  text: "Transform Data Into Any Format"
  tagline: A lightweight PHP templating library built with clean Lexer → Parser → Compiler architecture
  image:
    src: /logo.svg
    alt: Tsuku Logo
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started
    - theme: alt
      text: View on GitHub
      link: https://github.com/qoliber/tsuku

features:
  - icon: 🎯
    title: Any Text Format
    details: CSV, XML, JSON, YAML, TOML, HTML, Markdown, INI, XSD, or custom formats - Tsuku handles them all

  - icon: 🔄
    title: Powerful Control Flow
    details: Loops, @if / @elseif / @else, pattern matching and real conditions with and, or, not and in

  - icon: 🎨
    title: Smart Object Access
    details: Automatic getter detection, method calls, and property access for both arrays and objects

  - icon: 🔧
    title: Custom Functions & Directives
    details: Register your own @functions() and @block() ... @end directives for domain-specific logic

  - icon: 🛡️
    title: Security Built-in
    details: HTML, XML, CSV, URL, and JSON escaping functions for safe output generation

  - icon: ⚡
    title: Production-Ready
    details: 848 tests, 99.4% line coverage, 98.7% mutation score - battle-tested and reliable

  - icon: 🏗️
    title: Clean Architecture
    details: Lexer → Parser → Compiler pipeline with AST-based compilation

  - icon: 📦
    title: Zero Dependencies
    details: Modern PHP 8.1+ with no external dependencies - lightweight and fast
---

## Quick Example

Transform your data into any format with simple, intuitive templates:

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

$template = 'SKU,Name,Price,Stock
@for(products as product)
@csv(product.sku),@csv(product.name),@number(product.price, 2, ".", ""),{product.stock}
@end';

$data = [
    'products' => [
        ['sku' => 'WID-001', 'name' => 'Widget', 'price' => 29.99, 'stock' => '100'],
        ['sku' => 'GAD-002', 'name' => 'Gadget, Premium', 'price' => 1299.50, 'stock' => '50'],
    ],
];

echo $tsuku->process($template, $data);
```

**Output:**
```csv
SKU,Name,Price,Stock
WID-001,Widget,29.99,100
GAD-002,"Gadget, Premium",1299.50,50
```

`@csv()` quotes the name that contains a comma, and `@number(price, 2, ".", "")` keeps the thousands separator out of the price column.

## What's New in Tsuku 1.4.0

- **Real conditions** - `and` / `&&`, `or` / `||`, `not` / `!`, parentheses and `in` / `not in` in `@if`, `@unless`, `@elseif` and `@?{...}` ([Conditionals](/guide/conditionals))
- **`@elseif`** branches with a single `@end`
- **Whitespace control** - lines that hold only directives vanish, so templates can be indented freely ([Whitespace Control](/guide/whitespace-control))
- **Custom block directives** - `registerDirective()` now works: `@cdata ... @end`, `@wrap("[", "]") ... @end` ([Custom Directives](/guide/custom-directives))
- **`@@`** prints a literal `@` - `user@@example.com`, `@@media (...)` ([Escapes & Special Characters](/guide/escaping))
- **Clear errors** - malformed conditions throw a `ParseException` with line and column instead of rendering something wrong
- Since 1.3.0: **streaming** with `processToStream()` for feeds with hundreds of thousands of rows ([Streaming](/guide/streaming))

1.4.0 changes some output compared to 1.3.0 (mostly whitespace around directives) - read [Upgrading from 1.3](/guide/upgrading) before you update, and the [Changelog](/changelog) for everything else.

## Why Tsuku?

### Perfect for E-commerce & Data Exports

Tsuku excels at transforming structured data into various text formats - CSV exports, XML feeds, JSON APIs, configuration files, and more. Unlike HTML-focused template engines (Twig, Blade), Tsuku is designed specifically for **data transformation**.

### Modern PHP Architecture

Built with a clean three-stage compiler pipeline:
- **Lexer**: Breaks templates into tokens (and decides which whitespace around directives is layout)
- **Parser**: Builds an Abstract Syntax Tree (AST), conditions included
- **Compiler**: Walks the tree and generates the output

The same architecture used by programming languages like PHP itself, JavaScript, and Python.

### Battle-Tested Quality

Tsuku 1.4.0 ships with 848 tests, 99.4% line coverage and a 98.7% mutation score. Characterization tests pin the 1.3.0 behaviour that 1.4.0 keeps.

## Installation

```bash
composer require qoliber/tsuku
```

[Get Started →](/guide/getting-started)
