# What is Tsuku?

**Tsuku** (つくる - *tsukuru* - "to create" in Japanese) is a lightweight PHP templating library designed for transforming structured data into any text format you need.

:::tip Born from Frustration
Tsuku was born out of pure hatred for XSLT. After years of wrestling with verbose, cryptic XML transformations in e-commerce projects, there had to be a better way. Tsuku is that better way - simple, readable, and purpose-built for real-world data transformation needs.
:::

## The Problem It Solves

Have you ever needed to:
- Export product data to CSV with proper escaping?
- Generate XML feeds for Google Shopping or marketplaces?
- Create JSON API responses from database objects?
- Build YAML/TOML configuration files from data?
- Transform data for reports, emails, or integrations?

Most PHP template engines (Twig, Blade, Smarty) are designed for **HTML rendering**. They're great for web pages but overkill - or poorly suited - for data transformation tasks.

## The Tsuku Approach

Tsuku is purpose-built for **data transformation**:

```php
// Your data
$data = [
    'products' => [
        ['sku' => 'WID-001', 'name' => 'Widget', 'price' => 29.99],
        ['sku' => 'GAD-002', 'name' => 'Gadget, Premium', 'price' => 39.99],
    ],
];

// Your template (CSV, XML, JSON, YAML - whatever you need)
$template = 'SKU,Name,Price
@for(products as product)
{product.sku},@csv(product.name),{product.price}
@end';

// Transform
$csv = $tsuku->process($template, $data);
// Output:
// SKU,Name,Price
// WID-001,Widget,29.99
// GAD-002,"Gadget, Premium",39.99
```

### Key Features

**🎯 Multi-Format Output**
- CSV with proper escaping through `@csv()` (nothing is escaped unless you ask for it)
- XML/JSON for APIs and feeds
- YAML/TOML for configuration
- HTML/Markdown for documentation
- Any custom text format

**🔄 Powerful Control Flow**
- `@for` loops with key/value support
- `@if` / `@unless` / `@elseif` / `@else` conditionals
- Real conditions: `and`, `or`, `not`, parentheses, `in` / `not in`
- `@match` pattern matching
- Indent templates freely: lines that hold only directives produce no output

**🎨 Smart Data Access**
- Automatic getter detection: `product.price` → `$product->getPrice()`
- Boolean getters: `user.active` → `$user->isActive()`
- Method calls: `product.total` → `$product->total()` (written without parentheses)
- Works with both arrays AND objects

**🔧 Extensible**
- Register custom functions: `@currency(price, "EUR")`
- Register custom block directives: `@cdata ... @end`
- Build Magento-style widgets
- Domain-specific template logic

**🛡️ Security Built-in**
- `@html()` - XSS protection
- `@xml()` - XML-safe output
- `@csv()` - Proper CSV escaping
- `@url()` - URL encoding
- `@json()` - JSON-safe escaping

## Current Version: 1.4.1

Tsuku 1.4.1 fixes six bugs of 1.4.0 (see the [Changelog](/changelog)). Tsuku 1.4.0 added real boolean conditions (`and`/`&&`, `or`/`||`, `not`/`!`, parentheses, `in`/`not in`), `@elseif`, predictable [whitespace control](/guide/whitespace-control), working [custom block directives](/guide/custom-directives), `@@` for a literal `@`, and `ParseException`s with line and column for malformed conditions. [Streaming](/guide/streaming) with `processToStream()` arrived in 1.3.0.

Coming from 1.3? Some output changes - see [Upgrading from 1.3](/guide/upgrading) and the [Changelog](/changelog).

## Architecture

Tsuku uses a clean three-stage compiler pipeline:

```
Template String → Lexer → Tokens → Parser → AST → Compiler → Output String
```

This is the same architecture used by:
- Programming languages (PHP, JavaScript, Python)
- Professional template engines (Twig)
- Code processors (Babel, TypeScript)

### Why This Matters

**Type Safety**: Full PHP 8.1+ type hints throughout

**Performance**: Single-pass compilation with efficient AST walking

**Reliability**: 944 tests, 99.4% line coverage, 98.8% mutation score (1.4.1)

**Maintainability**: Clean separation of concerns

## When to Use Tsuku

### ✅ Perfect For:
- E-commerce product exports (CSV, XML, JSON)
- API response formatting
- Data feed generation
- Configuration file creation
- Email templates
- Reports and documents
- Data transformation pipelines

### ⚠️ Not Ideal For:
- Complex HTML layouts with inheritance
- Full CMS theming (use Twig/Blade)
- Admin interfaces with components

## vs The Competition

| Feature | Tsuku | Twig | Blade | Magento |
|---------|-------|------|-------|---------|
| Multi-format (CSV/XML/JSON) | ✅ Best | ❌ HTML-focused | ❌ HTML-focused | ⚠️ PHTML |
| Pattern matching | ✅ `@match` | ❌ | ❌ | ❌ |
| E-commerce exports | ✅ Best | ⚠️ OK | ⚠️ OK | ⚠️ Complex |
| Learning curve | ✅ 30 mins | ⚠️ 2-3 hours | ✅ 1 hour | ❌ Weeks |
| Zero dependencies | ✅ | ❌ | ❌ (Laravel) | ❌ (Magento) |
| Template inheritance | ❌ | ✅ | ✅ | ✅ |

## Philosophy

Tsuku follows the **Unix philosophy**: Do one thing and do it well.

**One Thing**: Transform data into text

**Do It Well**:
- Clean, intuitive syntax
- Robust architecture
- Comprehensive testing
- Zero dependencies
- Excellent performance

## Next Steps

Ready to start? [Get Started →](/guide/getting-started)

Want to see examples? [View Examples →](/examples/)

Curious about the internals? [Architecture →](/guide/architecture)
