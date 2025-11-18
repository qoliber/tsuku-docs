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
    details: Loops, conditionals, pattern matching - everything you need for complex data transformations

  - icon: 🎨
    title: Smart Object Access
    details: Automatic getter detection, method calls, and property access for both arrays and objects

  - icon: 🔧
    title: Custom Functions
    details: Register your own functions to extend template capabilities with domain-specific logic

  - icon: 🛡️
    title: Security Built-in
    details: HTML, XML, CSV, URL, and JSON escaping functions for safe output generation

  - icon: ⚡
    title: Production-Ready
    details: 196 tests, 423 assertions, 88% mutation score - battle-tested and reliable

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
@csv(product.sku),@csv(product.name),$@number(product.price, 2),{product.stock}
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
WID-001,Widget,$29.99,100
GAD-002,"Gadget, Premium",$1,299.50,50
```

## Why Tsuku?

### Perfect for E-commerce & Data Exports

Tsuku excels at transforming structured data into various text formats - CSV exports, XML feeds, JSON APIs, configuration files, and more. Unlike HTML-focused template engines (Twig, Blade), Tsuku is designed specifically for **data transformation**.

### Modern PHP Architecture

Built with a clean three-stage compiler pipeline:
- **Lexer**: Breaks templates into tokens
- **Parser**: Builds an Abstract Syntax Tree (AST)
- **Compiler**: Generates optimized output

The same architecture used by programming languages like PHP itself, JavaScript, and Python.

### Battle-Tested Quality

With 88% mutation score, Tsuku is more thoroughly tested than most production code. Every feature is validated with comprehensive tests to ensure reliability.

## Installation

```bash
composer require qoliber/tsuku
```

[Get Started →](/guide/getting-started)
