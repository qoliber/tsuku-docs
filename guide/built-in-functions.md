# Built-in Functions

Tsuku comes with a comprehensive set of built-in functions for string manipulation, number formatting, array operations, membership tests, escaping, and more.

## How Arguments Work

A function call is `@name(argument, argument, ...)`. Each argument is one of:

- a quoted string: `"hello"` or `'hello'`
- a number: `2`, `1234.5` - passed to the function as written, as a string (`"2"`)
- a variable path: `product.price` - the value from your data, with its type (arrays stay arrays); a missing variable is passed as `""`
- another function call: `@upper(@trim(name))`

Functions that work on text accept numbers and booleans from your data and convert them to strings (`true` is `"1"`, a missing value `""`): `@substr()`, `@replace()`, `@strtotime()`, the format of `@date()`, the separators of `@number()` / `@number_format()` and the glue of `@join()`. So `@substr(sku, 0, 3)` works for an integer SKU. Up to 1.4.0 a number there was a `TypeError`, and the call printed nothing in `SILENT` mode.

Inside a condition (`@if(@length(items) > 2)`) arguments follow the condition rules instead: numbers are passed as numbers and a missing variable as `null`.

There are no array literals (`[1, 2]`) and no arithmetic (`qty * price`) in templates. Pass arrays in your data (or build one with [`@split()`](#split)), calculate values in PHP before rendering, or register a [custom function](/guide/custom-functions).

## String Functions

::: tip ASCII only
`@upper()`, `@lower()` and `@capitalize()` change the letters `a-z` / `A-Z` only - `@upper("éclair")` gives `éCLAIR`. `@substr()` and `@length()` count bytes, not characters. Register a custom function using `mb_strtoupper()` / `mb_substr()` if you need multibyte support.
:::

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

// A number from the data works too
$template = '@substr(sku, 0, 3)';
$data = ['sku' => 123456];
// Output: 123
```

### @replace()

Replace text:

```php
$template = '@replace(text, "foo", "bar")';
$data = ['text' => 'foo and foo'];
// Output: bar and bar

$template = '@replace(code, "0", "-")';
$data = ['code' => 1001];
// Output: 1--1
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
1. `value` - Number to format (a missing value formats as `0`)
2. `decimals` - Decimal places (default: 0)
3. `decPoint` - Decimal separator (default: ".")
4. `thousandsSep` - Thousands separator (default: ",")

`@number_format()` is the same function under PHP's name:

```php
$template = '@number_format(price, 2)';
$data = ['price' => 1234.5];
// Output: 1,234.50
```

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

Count array elements or string length (in bytes):

```php
// Array
$template = '@length(items)';
$data = ['items' => ['a', 'b', 'c']];
// Output: 3

// String
$template = '@length(text)';
$data = ['text' => 'hello'];
// Output: 5

// Bytes, not characters
$template = '@length(text)';
$data = ['text' => 'héllo'];
// Output: 6
```

`@length()` is also handy in conditions: `@if(@length(product.images) > 1)`.

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

`@first()` and `@last()` return values (also for associative arrays) and nothing for an empty array.

## Membership Functions

New in 1.4.0. They follow the same rules as the `in` operator in [conditions](/guide/conditionals).

### @contains()

`@contains(haystack, needle)` is true when:
- the haystack is an array and one of its items equals the needle **as a string** (`7231` matches `"7231"`)
- the haystack is a string that contains the needle (case-sensitive)

Anything else is false. As output, true prints `1` and false prints nothing, so `@contains()` is mostly used in conditions:

```php
$template = '@if(@contains(product.tags, "sale"))On sale!@end';
$data = ['product' => ['tags' => ['new', 'sale']]];
// Output: On sale!
```

::: warning Substring, not word
On a string, `@contains()` (and `in`) is a substring test: `@contains("72310,5", "7231")` is true. To test for an exact id in a comma-separated string, split it first: `@contains(@split(product.groups, ","), "7231")`.
:::

### @split()

`@split(string, delimiter = ",")` turns a string into a list:

```php
$template = '@join(@split(tags, ";"), " | ")';
$data = ['tags' => 'red;green;blue'];
// Output: red | green | blue
```

- the delimiter defaults to `","`
- an empty string (or a missing value) gives an empty list
- an array is returned unchanged
- an empty delimiter `""` splits into characters (UTF-8 aware)

```php
$template = '@?{"7231" in @split(product.groups) "member" : "not a member"}';
$data = ['product' => ['groups' => '72310,5']];
// Output: not a member
```

## Escaping Functions

Escaping is never automatic: `{variable}` prints the raw value. Pick the function for the format you produce.

### @html()

HTML-safe escaping (XSS protection) - `&`, `<`, `>`, `"` and `'` are escaped:

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

Encode a value as a JSON string. The result **includes the surrounding quotes**, and `"`, `'`, `&`, `<` and `>` are written as `\u0022`, `\u0027`, `\u0026`, `\u003C` and `\u003E` - valid JSON that is also safe inside HTML:

```php
$template = '@json(text)';
$data = ['text' => 'Hello "World" & <tag>'];
// Output: "Hello \u0022World\u0022 \u0026 \u003Ctag\u003E"
```

So write `"name": @json(user.name)`, never `"name": "@json(user.name)"`. The value is always encoded as a string - `@json(price)` gives `"29.99"` and a missing value gives `""`. Print numbers and booleans without `@json()`:

```php
$template = '{"name": @json(product.name), "price": {product.price}, "active": @?{product.active "true" : "false"}}';
$data = ['product' => ['name' => 'Widget "Pro"', 'price' => 29.99, 'active' => true]];
// Output: {"name": "Widget \u0022Pro\u0022", "price": 29.99, "active": true}
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

CSV-safe escaping with quotes. A value is quoted (and its `"` doubled) only when it contains a comma, a double quote or a line break:

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

`@csv()` does not look at semicolons or tabs - for `;`- or tab-separated files, make sure the values cannot contain the separator, or register your own escaping function.

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

An unknown type also falls back to HTML escaping.

## Date Functions

### @date()

Format a Unix timestamp with a [PHP date format](https://www.php.net/manual/en/datetime.format.php):

```php
$template = '@date("Y-m-d", order.created)';
$data = ['order' => ['created' => 1705314645]];
// Output: 2024-01-15

$template = '@date("F j, Y", order.created)';
// Output: January 15, 2024

$template = '@date("H:i:s", order.created)';
// Output: 10:30:45

$template = '@date("c", order.created)';
// Output: 2024-01-15T10:30:45+00:00
```

Without a timestamp, `@date()` formats the current time: `@date("Y-m-d")` prints today's date.

The timestamp must be an integer (or a numeric string). A date string such as `"2024-01-15"` is not parsed - it is cast to the number `2024` - so convert it with `@strtotime()` first:

```php
$template = '@date("d.m.Y", @strtotime(order.date))';
$data = ['order' => ['date' => '2024-01-15 10:00']];
// Output: 15.01.2024
```

Dates use PHP's default timezone (`date_default_timezone_set()` / `date.timezone`); the outputs on this page are for UTC.

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

"Empty" means what PHP's `empty()` means: a missing value, `null`, `""`, `0`, `"0"`, `false` and `[]` all get the fallback. Watch out for real zeros:

```php
$template = 'Stock: @default(stock, "N/A")';
$data = ['stock' => 0];
// Output: Stock: N/A
```

Use a condition when `0` is a valid value: `@if(stock == "")N/A@else{stock}@end`.

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
@csv(product.sku),@csv(@upper(product.name)),@csv(@trim(product.description)),@number(product.price, 2, ".", "")
@end';

$data = [
    'products' => [
        ['sku' => 'WID-001', 'name' => 'Widget', 'description' => 'A "premium" widget, new', 'price' => 1299.5],
        ['sku' => 'GAD-002', 'name' => 'Gadget', 'description' => '  Simple gadget  ', 'price' => 29.99],
    ],
];

echo $tsuku->process($template, $data);
// Output:
// SKU,Name,Description,Price
// WID-001,WIDGET,"A ""premium"" widget, new",1299.50
// GAD-002,GADGET,Simple gadget,29.99
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

$data = [
    'products' => [
        ['name' => 'Widget', 'price' => 1299.5, 'description' => 'Now <b>50%</b> "lighter"'],
    ],
];

echo $tsuku->process($template, $data);
```

**Output:**
```html
<div class="product">
  <h3>Widget</h3>
  <p class="price">$1,299.50</p>
  <div class="description">Now &lt;b&gt;50%&lt;/b&gt; &quot;lighter&quot;</div>
</div>
```

### XML Feed with Proper Escaping

```php
$template = '<?xml version="1.0"?>
<products>
@for(products as product)
  <product id="{product.id}">
    <name>@xml(product.name)</name>
    <description>@xml(product.description)</description>
    <price>@number(product.price, 2, ".", "")</price>
    <updated>@date("c", product.updated_at)</updated>
  </product>
@end
</products>';

$data = [
    'products' => [
        ['id' => 1, 'name' => 'Fish & Chips', 'description' => 'Served <hot>', 'price' => 1299.5, 'updated_at' => 1705314645],
    ],
];

echo $tsuku->process($template, $data);
```

**Output:**
```xml
<?xml version="1.0"?>
<products>
  <product id="1">
    <name>Fish &amp; Chips</name>
    <description>Served &lt;hot&gt;</description>
    <price>1299.50</price>
    <updated>2024-01-15T10:30:45+00:00</updated>
  </product>
</products>
```

### URL Generation

```php
$template = '@for(items as item)
https://example.com/search?q=@url(item.query)&cat=@url(item.category)
@end';

$data = ['items' => [['query' => 'red shoes', 'category' => 'Shoes & Boots']]];

echo $tsuku->process($template, $data);
// Output: https://example.com/search?q=red%20shoes&cat=Shoes%20%26%20Boots
```

### Invoice with Formatting

Templates cannot multiply, so the line totals are calculated in PHP and passed in the data:

```php
$template = 'INVOICE #@number(invoice.id, 0, "", "")

Items:
@for(invoice.items as item)
@csv(item.name)  x{item.qty}  @number(item.price, 2)  @number(item.line_total, 2)
@end

Total: $@number(invoice.total, 2)
Date: @date("F j, Y", invoice.date)';

$items = [
    ['name' => 'Widget', 'qty' => 2, 'price' => 10],
    ['name' => 'Cable, 2m', 'qty' => 3, 'price' => 4.5],
];
foreach ($items as &$item) {
    $item['line_total'] = $item['qty'] * $item['price'];
}
unset($item);

$data = [
    'invoice' => [
        'id' => 1001,
        'items' => $items,
        'total' => array_sum(array_column($items, 'line_total')),
        'date' => 1705276800,
    ],
];

echo $tsuku->process($template, $data);
```

**Output:**
```
INVOICE #1001

Items:
Widget  x2  10.00  20.00
"Cable, 2m"  x3  4.50  13.50

Total: $33.50
Date: January 15, 2024
```

::: tip Arithmetic as a function
If you prefer to keep calculations in the template, register a function - it receives the values from your data:

```php
$tsuku->registerFunction('multiply', fn($a, $b) => $a * $b);

$template = '@number(@multiply(item.qty, item.price), 2)';
$data = ['item' => ['qty' => 3, 'price' => 19.99]];
// Output: 59.97
```
:::

## Function Reference Table

| Function | Purpose | Example |
|----------|---------|---------|
| `@upper(str)` | Uppercase | `@upper("hello")` → `HELLO` |
| `@lower(str)` | Lowercase | `@lower("HELLO")` → `hello` |
| `@capitalize(str)` | Capitalize | `@capitalize("hello")` → `Hello` |
| `@trim(str)` | Trim whitespace | `@trim(" hi ")` → `hi` |
| `@substr(str, start, len)` | Substring | `@substr("hello", 0, 3)` → `hel` |
| `@replace(str, find, replace)` | Replace text | `@replace("a-b", "-", "+")` → `a+b` |
| `@concat(a, b, ...)` | Concatenate | `@concat("a", "b", "c")` → `abc` |
| `@number(num, decimals, dec, sep)` | Format number | `@number(1234.56, 2)` → `1,234.56` |
| `@number_format(num, decimals, dec, sep)` | Same as `@number` | `@number_format(1234.5, 2)` → `1,234.50` |
| `@round(num, precision)` | Round | `@round(1.567, 2)` → `1.57` |
| `@ceil(num)` | Round up | `@ceil(1.2)` → `2` |
| `@floor(num)` | Round down | `@floor(1.9)` → `1` |
| `@abs(num)` | Absolute | `@abs(-5)` → `5` |
| `@join(arr, sep)` | Join array | `@join(@split("a;b", ";"), ",")` → `a,b` |
| `@length(arr/str)` | Count/length | `@length(@split("a,b,c"))` → `3` |
| `@first(arr)` | First element | `@first(@split("a,b,c"))` → `a` |
| `@last(arr)` | Last element | `@last(@split("a,b,c"))` → `c` |
| `@split(str, sep)` | String to list | `@join(@split("red,green"), " \| ")` → `red \| green` |
| `@contains(haystack, needle)` | Membership / substring | `@contains("big sale", "sale")` → `1` |
| `@html(str)` | HTML escape | `@html("<tag>")` → `&lt;tag&gt;` |
| `@xml(str)` | XML escape | `@xml("<tag>")` → `&lt;tag&gt;` |
| `@json(str)` | JSON string | `@json('"')` → `"\u0022"` |
| `@url(str)` | URL encode | `@url("a b")` → `a%20b` |
| `@csv(str)` | CSV escape | `@csv("a,b")` → `"a,b"` |
| `@escape(str, type)` | Escape by type | `@escape("<b>", "xml")` → `&lt;b&gt;` |
| `@date(format, ts)` | Format date | `@date("Y-m-d", 1705276800)` → `2024-01-15` |
| `@strtotime(str)` | Parse date (UTC shown) | `@strtotime("2024-01-15")` → `1705276800` |
| `@default(val, fallback)` | Fallback value | `@default("", "N/A")` → `N/A` |

## Next Steps

- [Custom Functions →](/guide/custom-functions) - Create your own functions
- [Security →](/guide/security) - Learn when to use escaping
- [Examples →](/examples/) - See functions in real use cases
