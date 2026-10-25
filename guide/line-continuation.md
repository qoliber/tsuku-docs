# Line Continuation

Split long template lines across multiple lines for better readability using backslash `\`.

::: tip Since 1.4.0 you need it less often
Indenting directives no longer leaks whitespace into the output: lines that hold only directives print nothing, and directives inside a content line keep that line together (see [Whitespace Control](/guide/whitespace-control)). Use `\` when you want one **output** line from several **template** lines - many CSV fields, attributes of one XML tag, a compact JSON object.
:::

## Syntax

End any line with a backslash `\` to continue it on the next line. The backslash and newline character will be removed from the output:

```php
$template = 'First line\
Second line\
Third line';

// Output: First lineSecond lineThird line
```

Only the backslash and the line break are removed. Spaces at the end of the first line or at the start of the next one are kept.

## Use Cases

### CSV Templates

Perfect for CSV files with many columns:

```php
$template = 'Name,SKU,Price,Stock,Category,Status
@for(products as product)
@csv(product.name),\
@csv(product.sku),\
@number(product.price, 2, ".", ""),\
{product.stock},\
@csv(product.category),\
{product.status}
@end';

$data = [
    'products' => [
        ['name' => 'Widget', 'sku' => 'WID-001', 'price' => 29.99, 'stock' => 100, 'category' => 'Tools, Hardware', 'status' => 'active'],
        ['name' => 'Gadget "Pro"', 'sku' => 'GAD-002', 'price' => 1299.5, 'stock' => 0, 'category' => 'Electronics', 'status' => 'inactive'],
    ],
];

echo $tsuku->process($template, $data);
// Output:
// Name,SKU,Price,Stock,Category,Status
// Widget,WID-001,29.99,100,"Tools, Hardware",active
// "Gadget ""Pro""",GAD-002,1299.50,0,Electronics,inactive
```

**Benefits:**
- Each field on its own line
- Easy to read and maintain
- No horizontal scrolling

`@number(product.price, 2, ".", "")` formats without a thousands separator - the default `@number(price, 2)` would print `1,299.50`, which splits the CSV field.

### Long Function Chains

```php
$template = '@upper(\
@trim(\
@default(user.name, "Guest")\
)\
)';

$data = ['user' => ['name' => '  john ']];

echo $tsuku->process($template, $data);
// Output: JOHN
```

With an empty name the same template prints `GUEST`.

### Complex Expressions

```php
$template = 'User: {user.name},\
Email: {user.email},\
Role: {user.role},\
Status: {user.status}';

$data = ['user' => ['name' => 'John', 'email' => 'john@example.com', 'role' => 'admin', 'status' => 'active']];

// Output: User: John,Email: john@example.com,Role: admin,Status: active
```

## How It Works

Line continuation is processed at the lexer level before tokenization:

1. Template is loaded
2. All `\` followed by newline (`\n`, `\r\n`, or `\r`) are removed
3. Template is tokenized, and [whitespace control](/guide/whitespace-control) is applied to the joined lines
4. Template is parsed and processed normally

This means it works **everywhere** in templates:
- ✅ Inside loops
- ✅ Inside conditionals
- ✅ With functions
- ✅ With variables
- ✅ In plain text
- ✅ Inside quoted function arguments (see the last best practice below)

Line and column numbers in error messages still refer to the template **as you wrote it**, with its original lines:

```php
$template = 'Name,\
SKU
@if(x = 1)y@end';

$tsuku->process($template, []);
// Throws: ParseException: Unexpected '=' (use == to compare) at line 3, column 7
```

## Backslashes Elsewhere

A backslash that is **not** directly followed by a line break is ordinary text and is printed as it is - it is not an escape character for `@` or `{`. The only other exception is a quote inside a quoted string: `\"` in `"..."` and `\'` in `'...'` (see [Escapes & Special Characters](/guide/escaping)):

```php
$template = 'Windows path: C:\Program Files\App';
// Output: Windows path: C:\Program Files\App
```

Tsuku prints every backslash it receives, but mind the PHP string you write the template in: in a single-quoted PHP string `\\` is **one** backslash. `'UNC path: \\server\share\file'` reaches Tsuku as `UNC path: \server\share\file`. Write four backslashes, or use a nowdoc, which keeps backslashes unchanged:

```php
$template = 'UNC path: \\\\server\share\file';
// Output: UNC path: \\server\share\file

$template = <<<'TPL'
UNC path: \\server\share\file
TPL;
// Output: UNC path: \\server\share\file
```

A backslash at the very end of a line is always a continuation. To print one there, put it in your data:

```php
$template = 'path: C:\Temp{backslash}
next';

$data = ['backslash' => '\\'];

echo $tsuku->process($template, $data);
// Output:
// path: C:\Temp\
// next
```

## Cross-Platform Support

Line continuation works with all newline formats:
- Unix/Linux: `\n`
- Windows: `\r\n`
- Classic Mac: `\r`

## Examples

### Multi-Column CSV

```php
$template = 'Order ID,Date,Customer,Email,Product,Qty,Price,Total,Status
@for(orders as order)
{order.id},\
@date("Y-m-d", order.date),\
@csv(order.customer.name),\
@csv(order.customer.email),\
@csv(order.product),\
{order.quantity},\
@number(order.price, 2, ".", ""),\
@number(order.total, 2, ".", ""),\
{order.status}
@end';

$data = [
    'orders' => [
        [
            'id' => 1001,
            'date' => 1705276800,
            'customer' => ['name' => 'Doe, John', 'email' => 'john@example.com'],
            'product' => 'Widget',
            'quantity' => 2,
            'price' => 29.99,
            'total' => 59.98,
            'status' => 'paid',
        ],
    ],
];

echo $tsuku->process($template, $data);
// Output:
// Order ID,Date,Customer,Email,Product,Qty,Price,Total,Status
// 1001,2024-01-15,"Doe, John",john@example.com,Widget,2,29.99,59.98,paid
```

(`@date()` uses PHP's default timezone; the output above is for UTC.)

### Compact JSON

Write a JSON object over several lines but output it on one:

```php
$template = '{\
"name": @json(user.name), \
"email": @json(user.email), \
"role": @json(user.role)\
}';

$data = ['user' => ['name' => 'Ann', 'email' => 'ann@example.com', 'role' => 'admin']];

echo $tsuku->process($template, $data);
// Output: {"name": "Ann", "email": "ann@example.com", "role": "admin"}
```

The space before each `\` is kept; the line breaks are not.

### Joining Attributes

An `@end` that follows content keeps its line break (see [Whitespace Control](/guide/whitespace-control#joining-lines-with)). To spread one XML tag over several lines, join them with `\`:

```php
$template = '<product@if(p.featured)\
 featured="1"@end\
/>';

$data = ['p' => ['featured' => true]];

echo $tsuku->process($template, $data);
// Output: <product featured="1"/>
```

### Long Conditionals

Conditions may span several lines, with or without continuations. `&&` and `and` are equivalent (see [Conditionals](/guide/conditionals#logical-operators)):

```php
$template = '@if(\
  stock > 0 && \
  status == "active" && \
  price < 100\
)
  Available
@end';

$data = ['stock' => 5, 'status' => 'active', 'price' => 50];

echo $tsuku->process($template, $data);
// Output:   Available
```

Since 1.4.0 the backslashes are optional here - line breaks inside the parentheses of a condition are simply ignored:

```php
$template = '@if(
  stock > 0
  and status == "active"
  and price < 100
)
  Available
@end';

$data = ['stock' => 5, 'status' => 'active', 'price' => 50];

echo $tsuku->process($template, $data);
// Output:   Available
```

`  Available` keeps its own two spaces of indentation - it is a content line.

## Best Practices

### ✅ DO: Use for readability

```php
// Good - each field visible
$template = '@csv(name),\
@csv(email),\
@csv(phone)';

$data = ['name' => 'Ann', 'email' => 'ann@example.com', 'phone' => '+48 123, ext. 4'];
// Output: Ann,ann@example.com,"+48 123, ext. 4"
```

### ✅ DO: Align for clarity

```php
$template = 'Name:   {user.name},\
Email:  {user.email},\
Phone:  {user.phone}';

$data = ['user' => ['name' => 'Ann', 'email' => 'ann@example.com', 'phone' => '555-0100']];
// Output: Name:   Ann,Email:  ann@example.com,Phone:  555-0100
```

### ❌ DON'T: Overuse for simple cases

```php
// Bad - unnecessary for short lines (and easy to lose a space)
$template = 'Hello,\
World';
// Output: Hello,World

// Good
$template = 'Hello, World';
// Output: Hello, World
```

### ❌ DON'T: Use inside strings

Continuations are removed everywhere, also inside quoted function arguments. A string written over two lines with `\` becomes one line - you cannot use it to put a line break into a value:

```php
// The value is "This is a string" - no line break
$template = '@csv("This is \
a string")';
// Output: This is a string

// Clearer: write it on one line
$template = '@csv("This is a string")';
// Output: This is a string
```

## Performance

Line continuations are removed in a single pass when the template is tokenized, before whitespace control and parsing. Rendering is not affected. (Tsuku does not cache templates: every `process()` call tokenizes the template again, so if you render the same template very often, keep it short and simple or measure - see [Performance](/guide/performance).)

## Related

- [Whitespace Control →](/guide/whitespace-control) - What happens to indentation and line breaks around directives
- [CSV Exports →](/examples/csv-exports) - Practical CSV examples
- [Variables →](/guide/variables) - Variable interpolation
- [Functions →](/guide/built-in-functions) - Built-in functions
