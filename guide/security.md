# Security & Escaping

Security is critical when generating output from user data. Tsuku provides escaping functions for HTML, XML, CSV, URLs and JSON to prevent XSS, injection attacks and data corruption. Escaping is never automatic: `{variable}` prints the value exactly as it is, so choosing the right escaper is your job.

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

Use for **all** user-provided content in HTML, in element content and in attribute values:

```php
$template = '<div class="comment">
    <h4>@html(user.name)</h4>
    <p>@html(user.comment)</p>
</div>';

$data = ['user' => ['name' => 'Bob <b>', 'comment' => 'It\'s <script>x</script>']];
echo $tsuku->process($template, $data);
```

**Output:**
```html
<div class="comment">
    <h4>Bob &lt;b&gt;</h4>
    <p>It&apos;s &lt;script&gt;x&lt;/script&gt;</p>
</div>
```

`@html()` is PHP's `htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')`. **Escapes:**
- `<` → `&lt;`
- `>` → `&gt;`
- `"` → `&quot;`
- `'` → `&apos;`
- `&` → `&amp;`

Two things follow from that:

- Text that is already escaped is escaped again: `Fish &amp; Chips` becomes `Fish &amp;amp; Chips`. Escape raw values, not pre-escaped HTML.
- A value that is not valid UTF-8 produces **empty output** (that is how `htmlspecialchars()` reports invalid input). Convert legacy encodings to UTF-8 before rendering.

```php
$template = '<p>@html(text)</p>';
$data = ['text' => 'Fish &amp; Chips'];
// Output: <p>Fish &amp;amp; Chips</p>
```

### @xml() - XML Escaping

Use for XML element content and attribute values:

```php
$template = '<?xml version="1.0"?>
<product id="@xml(product.id)">
    <name>@xml(product.name)</name>
    <description>@xml(product.description)</description>
</product>';

$data = ['product' => ['id' => 'A-1', 'name' => 'Fish & Chips', 'description' => '<p>"Crispy"</p>']];
echo $tsuku->process($template, $data);
```

**Output:**
```xml
<?xml version="1.0"?>
<product id="A-1">
    <name>Fish &amp; Chips</name>
    <description>&lt;p&gt;&quot;Crispy&quot;&lt;/p&gt;</description>
</product>
```

`@xml()` is `htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8')`: the same five characters as `@html()`, with XML entity rules.

### @csv() - CSV Escaping

Use for CSV exports:

```php
$template = 'Name,Description
@for(products as product)
@csv(product.name),@csv(product.description)
@end';

$data = ['products' => [
    ['name' => 'Widget, Pro', 'description' => "Line 1\nLine 2"],
    ['name' => 'Gadget', 'description' => 'A "great" gadget'],
]];
echo $tsuku->process($template, $data);
```

**Output:**
```
Name,Description
"Widget, Pro","Line 1
Line 2"
Gadget,"A ""great"" gadget"
```

**Handles:**
- Commas → Wraps in quotes
- Quotes → Wraps in quotes and doubles them (`"` → `""`)
- Newlines (`\n`) → Wraps in quotes, keeps the line break inside the field

A value without any of these three characters is printed unchanged. `@csv()` is written for **comma-separated** files: it does not quote values that contain `;`, tabs or a lone `\r`, it does not trim spaces, and it does **not** protect against spreadsheet formula injection (see [below](#csv-formula-injection)).

### @url() - URL Encoding

Use for one query parameter value or one path segment:

```php
$template = 'https://example.com/search?q=@url(search_term)&category=@url(category)';

$data = [
    'search_term' => 'foo bar & baz',
    'category' => 'Electronics/Gadgets',
];
// Output: https://example.com/search?q=foo%20bar%20%26%20baz&category=Electronics%2FGadgets
```

`@url()` is PHP's `rawurlencode()`: everything except letters, digits and `-_.~` is percent-encoded, spaces become `%20` and `/` becomes `%2F`. Do not run a complete URL through it.

### @json() - JSON Escaping

Use for JSON string values. `@json()` returns a **complete JSON string including its double quotes**, so never put quotes around it:

```php
$template = '{
    "name": @json(product.name),
    "description": @json(product.description)
}';

$data = ['product' => ['name' => 'Widget "Pro"', 'description' => '<b>New</b> & it\'s here']];
echo $tsuku->process($template, $data);
```

**Output:**
```json
{
    "name": "Widget \u0022Pro\u0022",
    "description": "\u003Cb\u003ENew\u003C\/b\u003E \u0026 it\u0027s here"
}
```

`@json()` is `json_encode((string) $value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)`: `<`, `>`, `'`, `"` and `&` become `\u003C`, `\u003E`, `\u0027`, `\u0022` and `\u0026`, non-ASCII characters become `\u....` escapes and `/` becomes `\/`. All of it is valid JSON and decodes back to the original text, and the result is safe even inside an HTML `<script>` block.

Because the value is cast to a string first, `@json()` always produces a JSON **string**: `42` becomes `"42"`, `null` becomes `""` and `true` becomes `"1"`. Print numbers directly (`{product.id}`, or `@number(product.price, 2, ".", "")`) and booleans with `@?{flag "true" : "false"}`:

```php
$template = '{"id": {product.id}, "price": @number(product.price, 2, ".", ""), "name": @json(product.name)}';
$data = ['product' => ['id' => 7, 'price' => 1299.5, 'name' => 'Widget']];
// Output: {"id": 7, "price": 1299.50, "name": "Widget"}
```

## When to Use Each Escaper

| Context | Escaper | Example |
|---------|---------|---------|
| HTML content | `@html()` | `<p>@html(text)</p>` |
| HTML attributes | `@html()` | `<img alt="@html(text)">` |
| XML content and attributes | `@xml()` | `<name>@xml(text)</name>` |
| CSV fields | `@csv()` | `@csv(field)` |
| URL parameters | `@url()` | `?q=@url(query)` |
| JSON string values | `@json()` | `"key": @json(value)` (no quotes around it) |

`@escape(value, "html")` does the same as the dedicated functions (`"html"`, `"xml"`, `"json"`, `"url"`, `"csv"`; any other type, or none, means HTML).

## Common Vulnerabilities

### ❌ Unescaped HTML

```php
$data = ['product' => ['name' => 'Widget <script>steal()</script>', 'description' => '<img src=x onerror=alert(1)>']];

// WRONG
$template = '<div class="product">
    <h3>{product.name}</h3>
    <p>{product.description}</p>
</div>';
// Output:
// <div class="product">
//     <h3>Widget <script>steal()</script></h3>
//     <p><img src=x onerror=alert(1)></p>
// </div>

// CORRECT
$template = '<div class="product">
    <h3>@html(product.name)</h3>
    <p>@html(product.description)</p>
</div>';
// Output:
// <div class="product">
//     <h3>Widget &lt;script&gt;steal()&lt;/script&gt;</h3>
//     <p>&lt;img src=x onerror=alert(1)&gt;</p>
// </div>
```

### ❌ Unescaped Attributes

```php
$data = ['image' => ['url' => '/img/1.jpg', 'title' => '" onerror="alert(1)']];

// WRONG - the title closes the attribute and adds an event handler
$template = '<img src="{image.url}" alt="{image.title}">';
// Output: <img src="/img/1.jpg" alt="" onerror="alert(1)">

// CORRECT
$template = '<img src="@html(image.url)" alt="@html(image.title)">';
// Output: <img src="/img/1.jpg" alt="&quot; onerror=&quot;alert(1)">
```

::: warning Escaping is not URL validation
`@html()` keeps a value inside its attribute, but it does not make a URL safe: `href="@html(link)"` with `javascript:alert(1)` is still a script link. Check the scheme (`https:`, `mailto:`, ...) of user-supplied URLs in PHP before rendering them.
:::

### ❌ Unescaped CSV

```php
$data = ['products' => [['name' => 'Widget, Pro', 'description' => 'A "great" widget']]];

// WRONG - Breaks with commas/quotes
$template = '@for(products as product)
{product.name},{product.description}
@end';
// Output: Widget, Pro,A "great" widget

// CORRECT
$template = '@for(products as product)
@csv(product.name),@csv(product.description)
@end';
// Output: "Widget, Pro","A ""great"" widget"
```

### ❌ Unescaped URLs

```php
$data = ['search' => 'red & blue shoes'];

// WRONG - Breaks with spaces/special chars
$template = 'https://example.com?q={search}';
// Output: https://example.com?q=red & blue shoes

// CORRECT
$template = 'https://example.com?q=@url(search)';
// Output: https://example.com?q=red%20%26%20blue%20shoes
```

### CSV Formula Injection

Spreadsheet programs treat a cell that starts with `=`, `+`, `-` or `@` as a formula. `@csv()` only takes care of the CSV structure, so `=HYPERLINK(...)` from a product name reaches Excel as a working formula. If your CSV files are opened in spreadsheets, register a function that neutralises such values and use it instead of `@csv()`:

```php
$tsuku->registerFunction('csv_safe', function ($value): string {
    $value = (string) $value;
    // A leading apostrophe makes spreadsheets show the value as text
    if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        $value = "'" . $value;
    }
    return str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")
        ? '"' . str_replace('"', '""', $value) . '"'
        : $value;
});

$template = '@for(rows as row)
@csv_safe(row.name),@csv_safe(row.note)
@end';

$data = ['rows' => [
    ['name' => 'Widget', 'note' => '=HYPERLINK("http://evil.example","Click")'],
    ['name' => '+1 Gadget', 'note' => '-5% off, today'],
]];
echo $tsuku->process($template, $data);
// Output:
// Widget,"'=HYPERLINK(""http://evil.example"",""Click"")"
// '+1 Gadget,"'-5% off, today"
```

The prefix changes the data (`-5%` becomes `'-5%`), so only use it for files meant for spreadsheets, not for machine-to-machine imports.

## Data Is Never Parsed as a Template

Only the template text is parsed. Values from `$data` are printed literally, even when they contain Tsuku syntax, so user input cannot call functions or read other variables:

```php
$template = '<p>{comment}</p>';
$data = ['comment' => '@upper(secret) {secret} {{widget type="x"}}'];
// Output: <p>@upper(secret) {secret} {{widget type="x"}}</p>
```

The reverse is not true: **never build a template from user input**. A template can call every registered function and print every value you pass in, so a user-supplied template is code. Keep templates in your code base or in an admin-only store.

::: v-pre
A related trap in your own templates: every `@` in template text starts a directive or function, so an e-mail address written into a template throws a `ParseException`. Write `@@` for a literal `@` (`support@@example.com`); `{{...}}` (Magento `{{widget}}`, `{{media}}`, `{{trans}}`) passes through unchanged. See [Escapes & Special Characters](/guide/escaping).
:::

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
$data = ['product' => ['status_code' => 3]];
// Output:
// <div class="status-3">
//     Status: Active
// </div>
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
$data = ['user' => ['comment' => 'Great <b>product</b>!']];
// Output:
// <div class="comment">
//     Great &lt;b&gt;product&lt;/b&gt;!
// </div>
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
$data = ['user' => ['email' => 'ann@example.com']];
// Output: <p>Email: ann@example.com</p>
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

$data = ['user' => [
    'name' => 'Ann "The Dev" O\'Neil',
    'bio' => 'Loves <code>',
    'email' => 'ann@example.com',
    'avatar' => '/avatars/ann.png',
]];
echo $tsuku->process($template, $data);
```

**Output:**
```html
<div class="profile">
    <h2>Ann &quot;The Dev&quot; O&apos;Neil</h2>
    <p>Loves &lt;code&gt;</p>
    <a href="mailto:ann@example.com">Contact</a>
    <img src="/avatars/ann.png" alt="Ann &quot;The Dev&quot; O&apos;Neil">
</div>
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

$data = ['products' => [
    ['name' => 'Widget', 'price' => 1299.5, 'description' => 'A "premium" widget, <b>new</b>', 'slug' => 'premium widget'],
]];
echo $tsuku->process($template, $data);
```

**Output:**
```html
<div class="product">
    <h3>Widget</h3>
    <p class="price">$1,299.50</p>
    <div class="description">A &quot;premium&quot; widget, &lt;b&gt;new&lt;/b&gt;</div>
    <a href="/product/premium%20widget">View Details</a>
</div>
```

### Safe CSV Export

```php
$template = 'SKU,Name,Description,Price
@for(products as product)
@csv(product.sku),@csv(product.name),@csv(product.description),@number(product.price, 2, ".", "")
@end';

$data = ['products' => [
    ['sku' => 'WID-001', 'name' => 'Widget', 'description' => 'A "premium" widget, new', 'price' => 1299.5],
    ['sku' => 'GAD-002', 'name' => 'Gadget', 'description' => 'Simple gadget', 'price' => 29.99],
]];
echo $tsuku->process($template, $data);
```

**Output:**
```
SKU,Name,Description,Price
WID-001,Widget,"A ""premium"" widget, new",1299.50
GAD-002,Gadget,Simple gadget,29.99
```

Note the `@number(product.price, 2, ".", "")`: the default `@number(price, 2)` adds a thousands separator (`1,299.50`), which would split the price into two CSV columns.

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

$data = ['products' => [
    ['id' => 1, 'name' => 'Fish & Chips', 'description' => '<b>Crispy</b>', 'url' => 'https://example.com/p?id=1&ref=feed'],
]];
echo $tsuku->process($template, $data);
```

**Output:**
```xml
<?xml version="1.0"?>
<products>
    <product id="1">
        <name>Fish &amp; Chips</name>
        <description>&lt;b&gt;Crispy&lt;/b&gt;</description>
        <link>https://example.com/p?id=1&amp;ref=feed</link>
    </product>
</products>
```

### Safe API Response

Tsuku has no `loop.last`, so JSON arrays put the comma **before** every element except the first (`@if(index > 0),@end`). The loop starts at the end of the `[` line and every element ends with `}@end`, so the output is pretty-printed:

```php
$template = '{
    "products": [@for(products as product, index)@if(index > 0),@end
        {
            "id": {product.id},
            "name": @json(product.name),
            "description": @json(product.description)
        }@end
    ]
}';

$data = ['products' => [
    ['id' => 1, 'name' => 'Widget "Pro"', 'description' => '<b>New</b>'],
    ['id' => 2, 'name' => 'Gadget', 'description' => 'It\'s here'],
]];
echo $tsuku->process($template, $data);
```

**Output:**
```json
{
    "products": [
        {
            "id": 1,
            "name": "Widget \u0022Pro\u0022",
            "description": "\u003Cb\u003ENew\u003C\/b\u003E"
        },
        {
            "id": 2,
            "name": "Gadget",
            "description": "It\u0027s here"
        }
    ]
}
```

`index` is the array key, so this needs a list (keys `0, 1, 2, ...`); pass `array_values($products)` if your array has other keys. Arithmetic such as `@if(index < length(products) - 1)` is not supported and throws a `ParseException`.

## SQL Injection Prevention

::: warning
Tsuku is for **output generation**, not database queries. Never use Tsuku to build SQL queries!
:::

```php
// ❌ NEVER DO THIS
$template = 'SELECT * FROM users WHERE name = "{user_input}"';
$data = ['user_input' => 'x" OR "1"="1'];
// Output: SELECT * FROM users WHERE name = "x" OR "1"="1"

// ✅ Use prepared statements instead
$stmt = $pdo->prepare('SELECT * FROM users WHERE name = ?');
$stmt->execute([$user_input]);
```

## Security Checklist

Before deploying templates:

- [ ] All user input is escaped with appropriate function
- [ ] HTML content uses `@html()`
- [ ] CSV exports use `@csv()` (or a formula-safe variant for spreadsheet users)
- [ ] URLs use `@url()`, and user-supplied link targets are validated
- [ ] XML uses `@xml()`
- [ ] JSON uses `@json()` without surrounding quotes
- [ ] No raw variables in sensitive contexts
- [ ] Templates never come from user input
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
// Output: <div>&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;</div>

// Verify output is escaped
assert(strpos($result, '<script>') === false);
```

## Performance Impact

Escaping functions are **fast** - don't skip them for performance! In our measurement (PHP 8.3), rendering 1,000 values through `@html()` took about 0.3 ms longer than printing the same 1,000 values raw - roughly a third of a microsecond per value.

## Next Steps

- [Best Practices →](/guide/best-practices) - Additional security tips
- [Built-in Functions →](/guide/built-in-functions) - All escaping functions
- [Escapes & Special Characters →](/guide/escaping) - `@@` and Magento directives
- [Examples →](/examples/) - See security in action
