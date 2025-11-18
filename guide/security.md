# Security & Escaping

Security is critical when generating output from user data. Tsuku provides comprehensive escaping functions to prevent XSS, injection attacks, and data corruption.

## XSS Protection

Cross-Site Scripting (XSS) occurs when user input is output without escaping.

### The Problem

```php
// DANGEROUS - Never do this!
$template = '<div>{user_comment}</div>';
$data = ['user_comment' => '<script>alert("XSS")</script>'];
$result = $tsuku->process($template, $data);
// Output: <div><script>alert("XSS")</script></div>
// ☠️ Script executes in browser!
```

### The Solution: @html()

```php
// SAFE - Always escape user input for HTML
$template = '<div>@html(user_comment)</div>';
$data = ['user_comment' => '<script>alert("XSS")</script>'];
$result = $tsuku->process($template, $data);
// Output: <div>&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;</div>
// ✅ Script is harmless text
```

## Escaping Functions

### @html() - HTML Escaping

Use for **all** user-provided content in HTML:

```php
$template = '<div class="comment">
    <h4>@html(user.name)</h4>
    <p>@html(user.comment)</p>
</div>';
```

**Escapes:**
- `<` → `&lt;`
- `>` → `&gt;`
- `"` → `&quot;`
- `'` → `&apos;`
- `&` → `&amp;`

### @xml() - XML Escaping

Use for XML output:

```php
$template = '<?xml version="1.0"?>
<product>
    <name>@xml(product.name)</name>
    <description>@xml(product.description)</description>
</product>';
```

### @csv() - CSV Escaping

Use for CSV exports:

```php
$template = 'Name,Description
@for(products as product)
@csv(product.name),@csv(product.description)
@end';
```

**Handles:**
- Commas → Wraps in quotes
- Quotes → Doubles them (`"` → `""`)
- Newlines → Preserves in quoted strings

### @url() - URL Encoding

Use for URLs and query parameters:

```php
$template = 'https://example.com/search?q=@url(search_term)&category=@url(category)';

$data = [
    'search_term' => 'foo bar & baz',
    'category' => 'Electronics/Gadgets',
];
// Output: https://example.com/search?q=foo%20bar%20%26%20baz&category=Electronics%2FGadgets
```

### @json() - JSON Escaping

Use for JSON output:

```php
$template = '{
    "name": @json(product.name),
    "description": @json(product.description)
}';
```

## When to Use Each Escaper

| Context | Escaper | Example |
|---------|---------|---------|
| HTML content | `@html()` | `<p>@html(text)</p>` |
| HTML attributes | `@html()` | `<img alt="@html(text)">` |
| XML content | `@xml()` | `<name>@xml(text)</name>` |
| CSV fields | `@csv()` | `@csv(field)` |
| URL parameters | `@url()` | `?q=@url(query)` |
| JSON values | `@json()` | `"key": @json(value)` |

## Common Vulnerabilities

### ❌ Unescaped HTML

```php
// WRONG
$template = '<div class="product">
    <h3>{product.name}</h3>
    <p>{product.description}</p>
</div>';
```

```php
// CORRECT
$template = '<div class="product">
    <h3>@html(product.name)</h3>
    <p>@html(product.description)</p>
</div>';
```

### ❌ Unescaped Attributes

```php
// WRONG
$template = '<img src="{image.url}" alt="{image.title}">';
```

```php
// CORRECT
$template = '<img src="@html(image.url)" alt="@html(image.title)">';
```

### ❌ Unescaped CSV

```php
// WRONG - Breaks with commas/quotes
$template = '@for(products as product)
{product.name},{product.description}
@end';
```

```php
// CORRECT
$template = '@for(products as product)
@csv(product.name),@csv(product.description)
@end';
```

### ❌ Unescaped URLs

```php
// WRONG - Breaks with spaces/special chars
$template = 'https://example.com?q={search}';
```

```php
// CORRECT
$template = 'https://example.com?q=@url(search)';
```

## Trusted vs Untrusted Data

### Trusted Data (No Escaping Needed)

Data you control:
- Static text you write
- Configuration values
- Database IDs (numeric)
- Internal status codes

```php
// OK - You control these values
$template = '<div class="status-{product.status_code}">
    Status: Active
</div>';
```

### Untrusted Data (Always Escape)

Data from users or external sources:
- User input (forms, comments, names)
- Product descriptions
- Email addresses
- File names
- Any external API data

```php
// MUST escape
$template = '<div class="comment">
    @html(user.comment)
</div>';
```

## Defense in Depth

### Layer 1: Input Validation

Validate before storing:

```php
// Validate before saving to database
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new \InvalidArgumentException('Invalid email');
}
```

### Layer 2: Output Escaping

Always escape when outputting:

```php
// Even if validated, still escape on output
$template = '<p>Email: @html(user.email)</p>';
```

### Layer 3: Content Security Policy

Add CSP headers to your application:

```php
header("Content-Security-Policy: default-src 'self'; script-src 'self'");
```

## Real-World Examples

### Safe User Profile

```php
$template = '<div class="profile">
    <h2>@html(user.name)</h2>
    <p>@html(user.bio)</p>
    <a href="mailto:@html(user.email)">Contact</a>
    <img src="@html(user.avatar)" alt="@html(user.name)">
</div>';
```

### Safe Product Catalog

```php
$template = '@for(products as product)
<div class="product">
    <h3>@html(product.name)</h3>
    <p class="price">$@number(product.price, 2)</p>
    <div class="description">@html(product.description)</div>
    <a href="/product/@url(product.slug)">View Details</a>
</div>
@end';
```

### Safe CSV Export

```php
$template = 'SKU,Name,Description,Price
@for(products as product)
@csv(product.sku),@csv(product.name),@csv(product.description),@number(product.price, 2)
@end';
```

### Safe XML Feed

```php
$template = '<?xml version="1.0"?>
<products>
@for(products as product)
    <product id="@xml(product.id)">
        <name>@xml(product.name)</name>
        <description>@xml(product.description)</description>
        <link>@xml(product.url)</link>
    </product>
@end
</products>';
```

### Safe API Response

```php
$template = '{
    "products": [
@for(products as product, index)
        {
            "id": {product.id},
            "name": @json(product.name),
            "description": @json(product.description)
        }@if(index < length(products) - 1),@end
@end
    ]
}';
```

## SQL Injection Prevention

::: warning
Tsuku is for **output generation**, not database queries. Never use Tsuku to build SQL queries!
:::

```php
// ❌ NEVER DO THIS
$template = 'SELECT * FROM users WHERE name = "{user_input}"';

// ✅ Use prepared statements instead
$stmt = $pdo->prepare('SELECT * FROM users WHERE name = ?');
$stmt->execute([$user_input]);
```

## Security Checklist

Before deploying templates:

- [ ] All user input is escaped with appropriate function
- [ ] HTML content uses `@html()`
- [ ] CSV exports use `@csv()`
- [ ] URLs use `@url()`
- [ ] XML uses `@xml()`
- [ ] JSON uses `@json()`
- [ ] No raw variables in sensitive contexts
- [ ] Input validation is in place
- [ ] CSP headers are configured
- [ ] Templates are code-reviewed

## Testing Security

Test with malicious input:

```php
$malicious_data = [
    'name' => '<script>alert("XSS")</script>',
    'description' => '"; DROP TABLE products; --',
    'comment' => '<img src=x onerror=alert(1)>',
];

$template = '<div>@html(name)</div>';
$result = $tsuku->process($template, $malicious_data);

// Verify output is escaped
assert(strpos($result, '<script>') === false);
```

## Performance Impact

Escaping functions are **very fast** - don't skip them for performance!

```php
// Negligible performance difference
@html(text)  // ~0.001ms
{text}       // ~0.0005ms

// Worth the security!
```

## Next Steps

- [Best Practices →](/guide/best-practices) - Additional security tips
- [Built-in Functions →](/guide/built-in-functions) - All escaping functions
- [Examples →](/examples/) - See security in action
