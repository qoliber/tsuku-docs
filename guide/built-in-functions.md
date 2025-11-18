# Built-in Functions

Tsuku comes with a comprehensive set of built-in functions for string manipulation, number formatting, array operations, escaping, and more.

## String Functions

### @upper()

Convert string to uppercase:

```php
$template = '@upper(text)';
$data = ['text' => 'hello world'];
// Output: HELLO WORLD
```

### @lower()

Convert string to lowercase:

```php
$template = '@lower(text)';
$data = ['text' => 'HELLO WORLD'];
// Output: hello world
```

### @capitalize()

Capitalize first letter:

```php
$template = '@capitalize(text)';
$data = ['text' => 'hello world'];
// Output: Hello world
```

### @trim()

Remove whitespace from both ends:

```php
$template = '@trim(text)';
$data = ['text' => '  hello  '];
// Output: hello
```

### @substr()

Extract substring:

```php
$template = '@substr(text, 0, 5)';
$data = ['text' => 'Hello World'];
// Output: Hello

// Without length (from start to end)
$template = '@substr(text, 6)';
// Output: World
```

### @replace()

Replace text:

```php
$template = '@replace(text, "foo", "bar")';
$data = ['text' => 'foo and foo'];
// Output: bar and bar
```

### @concat()

Concatenate strings:

```php
$template = '@concat("Hello", " ", name, "!")';
$data = ['name' => 'World'];
// Output: Hello World!
```

## Number Functions

### @number()

Format numbers with thousands separator and decimals:

```php
$template = '@number(price, 2)';
$data = ['price' => 1234.567];
// Output: 1,234.57

// European format
$template = '@number(price, 2, ",", ".")';
// Output: 1.234,57

// No thousands separator
$template = '@number(price, 2, ".", "")';
// Output: 1234.57
```

**Parameters:**
1. `value` - Number to format
2. `decimals` - Decimal places (default: 0)
3. `decPoint` - Decimal separator (default: ".")
4. `thousandsSep` - Thousands separator (default: ",")

### @round()

Round to specified precision:

```php
$template = '@round(value, 2)';
$data = ['value' => 1.2345];
// Output: 1.23
```

### @ceil()

Round up:

```php
$template = '@ceil(value)';
$data = ['value' => 1.2];
// Output: 2
```

### @floor()

Round down:

```php
$template = '@floor(value)';
$data = ['value' => 1.9];
// Output: 1
```

### @abs()

Absolute value:

```php
$template = '@abs(value)';
$data = ['value' => -42];
// Output: 42
```

## Array Functions

### @join()

Join array elements with separator:

```php
$template = '@join(items, ", ")';
$data = ['items' => ['Apple', 'Banana', 'Cherry']];
// Output: Apple, Banana, Cherry

// Default separator is comma
$template = '@join(items)';
// Output: Apple,Banana,Cherry
```

### @length()

Count array elements or string length:

```php
// Array
$template = '@length(items)';
$data = ['items' => ['a', 'b', 'c']];
// Output: 3

// String
$template = '@length(text)';
$data = ['text' => 'hello'];
// Output: 5
```

### @first()

Get first element:

```php
$template = '@first(items)';
$data = ['items' => ['Apple', 'Banana', 'Cherry']];
// Output: Apple
```

### @last()

Get last element:

```php
$template = '@last(items)';
$data = ['items' => ['Apple', 'Banana', 'Cherry']];
// Output: Cherry
```

## Escaping Functions

### @html()

HTML-safe escaping (XSS protection):

```php
$template = '@html(text)';
$data = ['text' => '<script>alert("XSS")</script>'];
// Output: &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;
```

### @xml()

XML-safe escaping:

```php
$template = '@xml(text)';
$data = ['text' => '<node>Content & More</node>'];
// Output: &lt;node&gt;Content &amp; More&lt;/node&gt;
```

### @json()

JSON-safe escaping:

```php
$template = '@json(text)';
$data = ['text' => 'Hello "World" & <tag>'];
// Output: "Hello \"World\" \u0026 \u003Ctag\u003E"
```

### @url()

URL encoding:

```php
$template = '@url(text)';
$data = ['text' => 'Hello World & More'];
// Output: Hello%20World%20%26%20More

// In URLs
$template = 'https://example.com?q=@url(search)';
$data = ['search' => 'foo bar'];
// Output: https://example.com?q=foo%20bar
```

### @csv()

CSV-safe escaping with quotes:

```php
$template = '@csv(text)';

// With comma
$data = ['text' => 'Hello, World'];
// Output: "Hello, World"

// With quotes
$data = ['text' => 'Say "Hello"'];
// Output: "Say ""Hello"""

// Simple text (no escaping needed)
$data = ['text' => 'SimpleText'];
// Output: SimpleText
```

### @escape()

Generic escape with type parameter:

```php
$template = '@escape(text, "html")';
// Same as @html(text)

$template = '@escape(text, "xml")';
// Same as @xml(text)

$template = '@escape(text, "url")';
// Same as @url(text)

$template = '@escape(text, "csv")';
// Same as @csv(text)

$template = '@escape(text, "json")';
// Same as @json(text)

// Default is HTML
$template = '@escape(text)';
// Same as @html(text)
```

## Date Functions

### @date()

Format current date/time:

```php
$template = '@date("Y-m-d")';
// Output: 2024-01-15

$template = '@date("F j, Y")';
// Output: January 15, 2024

$template = '@date("H:i:s")';
// Output: 14:30:45
```

With timestamp:

```php
$template = '@date("Y-m-d", timestamp)';
$data = ['timestamp' => 1234567890];
// Output: 2009-02-13
```

### @strtotime()

Convert string to timestamp:

```php
$template = '@strtotime("2024-01-15")';
// Output: 1705276800
```

## Utility Functions

### @default()

Provide fallback for empty values:

```php
$template = '@default(value, "N/A")';

$data = ['value' => ''];
// Output: N/A

$data = ['value' => 'Present'];
// Output: Present
```

Works with missing keys:

```php
$template = '@default(missing, "Not provided")';
$data = [];
// Output: Not provided
```

## Nesting Functions

Functions can be nested:

```php
$template = '@upper(@trim(text))';
$data = ['text' => '  hello  '];
// Output: HELLO

$template = '@html(@upper(text))';
$data = ['text' => '<script>'];
// Output: &lt;SCRIPT&gt;
```

## Real-World Examples

### E-commerce Product Export

```php
$template = 'SKU,Name,Description,Price
@for(products as product)
@csv(product.sku),@csv(@upper(product.name)),@csv(@trim(product.description)),$@number(product.price, 2)
@end';
```

### HTML Product List with XSS Protection

```php
$template = '@for(products as product)
<div class="product">
  <h3>@html(product.name)</h3>
  <p class="price">$@number(product.price, 2)</p>
  <div class="description">@html(product.description)</div>
</div>
@end';
```

### XML Feed with Proper Escaping

```php
$template = '<?xml version="1.0"?>
<products>
@for(products as product)
  <product id="{product.id}">
    <name>@xml(product.name)</name>
    <description>@xml(product.description)</description>
    <price>@number(product.price, 2)</price>
    <updated>@date("c")</updated>
  </product>
@end
</products>';
```

### URL Generation

```php
$template = '@for(items as item)
https://example.com/search?q=@url(item.query)&cat=@url(item.category)
@end';
```

### Invoice with Formatting

```php
$template = 'INVOICE #@number(invoice.id, 0, "", "")

Items:
@for(invoice.items as item)
@csv(item.name)  x{item.qty}  @number(item.price, 2)  @number(item.qty * item.price, 2)
@end

Total: $@number(invoice.total, 2)
Date: @date("F j, Y")';
```

## Function Reference Table

| Function | Purpose | Example |
|----------|---------|---------|
| `@upper(str)` | Uppercase | `@upper("hello")` → `HELLO` |
| `@lower(str)` | Lowercase | `@lower("HELLO")` → `hello` |
| `@capitalize(str)` | Capitalize | `@capitalize("hello")` → `Hello` |
| `@trim(str)` | Trim whitespace | `@trim(" hi ")` → `hi` |
| `@substr(str, start, len)` | Substring | `@substr("hello", 0, 3)` → `hel` |
| `@replace(str, find, replace)` | Replace text | `@replace("a", "a", "b")` → `b` |
| `@number(num, decimals, dec, sep)` | Format number | `@number(1234.56, 2)` → `1,234.56` |
| `@round(num, precision)` | Round | `@round(1.567, 2)` → `1.57` |
| `@ceil(num)` | Round up | `@ceil(1.2)` → `2` |
| `@floor(num)` | Round down | `@floor(1.9)` → `1` |
| `@abs(num)` | Absolute | `@abs(-5)` → `5` |
| `@join(arr, sep)` | Join array | `@join([1,2], ",")` → `1,2` |
| `@length(arr/str)` | Count/length | `@length([1,2,3])` → `3` |
| `@first(arr)` | First element | `@first([1,2,3])` → `1` |
| `@last(arr)` | Last element | `@last([1,2,3])` → `3` |
| `@html(str)` | HTML escape | `@html("<tag>")` → `&lt;tag&gt;` |
| `@xml(str)` | XML escape | `@xml("<tag>")` → `&lt;tag&gt;` |
| `@json(str)` | JSON escape | `@json('"')` → `\"` |
| `@url(str)` | URL encode | `@url("a b")` → `a%20b` |
| `@csv(str)` | CSV escape | `@csv("a,b")` → `"a,b"` |
| `@date(format, ts)` | Format date | `@date("Y-m-d")` → `2024-01-15` |
| `@default(val, fallback)` | Fallback value | `@default("", "N/A")` → `N/A` |

## Next Steps

- [Custom Functions →](/guide/custom-functions) - Create your own functions
- [Security →](/guide/security) - Learn when to use escaping
- [Examples →](/examples/) - See functions in real use cases
