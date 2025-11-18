# What is Tsuku?

**Tsuku** (つく - "to create" in Japanese) is a lightweight PHP templating library designed for transforming structured data into any text format you need.

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
$data = ['products' => [...]];

// Your template (CSV, XML, JSON, YAML - whatever you need)
$template = 'SKU,Name,Price
@for(products as product)
{product.sku},{product.name},{product.price}
@end';

// Transform
$csv = $tsuku->process($template, $data);
```

### Key Features

**🎯 Multi-Format Output**
- CSV with automatic escaping
- XML/JSON for APIs and feeds
- YAML/TOML for configuration
- HTML/Markdown for documentation
- Any custom text format

**🔄 Powerful Control Flow**
- `@for` loops with key/value support
- `@if/@unless/@else` conditionals
- `@match` pattern matching (unique to Tsuku!)

**🎨 Smart Data Access**
- Automatic getter detection: `product.price` → `$product->getPrice()`
- Method calls: `product.total()` → `$product->total()`
- Boolean getters: `user.active` → `$user->isActive()`
- Works with both arrays AND objects

**🔧 Extensible**
- Register custom functions
- Build Magento-style widgets
- Domain-specific template logic

**🛡️ Security Built-in**
- `@html()` - XSS protection
- `@xml()` - XML-safe output
- `@csv()` - Proper CSV escaping
- `@url()` - URL encoding
- `@json()` - JSON-safe escaping

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

**Reliability**: 196 tests, 423 assertions, 88% mutation score

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
