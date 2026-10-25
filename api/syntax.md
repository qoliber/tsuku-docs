# Syntax Reference

Complete syntax reference for the Tsuku template language (1.4.0).

## Variables

Access data using curly braces:

```
{variable_name}
{object.property}
{array.key}
{nested.deep.value}
{users.0.name}
```

**Examples:**

```php
$data = [
    'name' => 'John',
    'user' => ['email' => 'john@example.com'],
    'product' => ['price' => 29.99]
];

echo $tsuku->process('{name} | {user.email} | {product.price} | [{missing}]', $data);
// Output: John | john@example.com | 29.99 | []
```

A missing variable renders as an empty string in the default `SILENT` mode (see [Strictness Modes & Errors](/guide/strictness)).

`{...}` is only a variable when it contains a plain path: letters, digits, `_` and `.`, starting with a letter or `_`. Anything else - `{ name }`, `{index + 1}`, `{product.total()}`, JSON or CSS braces - is ordinary text and is printed as written.

**Smart Object Access:**

For every segment of a path Tsuku tries, in this order:

1. Array key: `$user['name']`
2. Getter method: `$user->getName()`
3. Boolean getter: `$user->isName()`
4. Method with the same name: `$user->name()`
5. Public property: `$user->name`

A data key that literally contains dots (`['a.b' => ...]`) wins over the nested lookup of `{a.b}`.

## Loops

### @for Directive

Iterate over arrays:

```
@for(array as item)
    {item}
@end
```

**With Key / Index:**

```
@for(array as item, index)
    {index}. {item}
@end
```

**Note:** Value comes first, then key/index (opposite of PHP). The second variable is the array key: `0, 1, 2, ...` for a list, the string key for an associative array.

**Examples:**

```php
// Simple loop
$template = '@for(products as product)
- {product.name}: ${product.price}
@end';

echo $tsuku->process($template, [
    'products' => [['name' => 'Widget', 'price' => 29.99], ['name' => 'Gadget', 'price' => 49.99]],
]);
// Output:
// - Widget: $29.99
// - Gadget: $49.99

// With index
$template = '@for(items as item, index)
{index}. {item}
@end';

echo $tsuku->process($template, ['items' => ['Apple', 'Banana']]);
// Output:
// 0. Apple
// 1. Banana

// Associative array: the key variable holds the key
$template = '@for(prices as price, currency)
{currency}: {price}
@end';

echo $tsuku->process($template, ['prices' => ['EUR' => '9.99', 'USD' => '10.99']]);
// Output:
// EUR: 9.99
// USD: 10.99

// Nested loops
$template = '@for(categories as category)
Category: {category.name}
@for(category.products as product)
  - {product.name}
@end
@end';

echo $tsuku->process($template, [
    'categories' => [
        ['name' => 'Tools', 'products' => [['name' => 'Hammer'], ['name' => 'Saw']]],
        ['name' => 'Garden', 'products' => [['name' => 'Rake']]],
    ],
]);
// Output:
// Category: Tools
//   - Hammer
//   - Saw
// Category: Garden
//   - Rake
```

**Limits:**
- `@for` iterates arrays. A `Traversable` object or a generator passed to `process()` renders nothing - convert it with `iterator_to_array()` first. (For very large collections use [`processToStream()`](/guide/streaming), which accepts any iterable.)
- There is no arithmetic: `{index + 1}` prints literally. Precompute such values in PHP (e.g. a `position` field) or register a [custom function](/guide/custom-functions).
- There are no `loop.first` / `loop.last` helpers. `@if(index > 0)` detects "not the first item" of a list; anything that needs the last item has to be prepared in the data.

## Conditionals

### @if, @elseif, @else

```
@if(condition)
    Content if true
@end
```

```
@if(condition)
    Content if true
@elseif(other_condition)
    Content if the other condition is true
@else
    Content otherwise
@end
```

An `@if` takes any number of `@elseif` branches, at most one `@else`, and a single `@end`.

### @unless Directive

Inverse of `@if`:

```
@unless(condition)
    Content if false
@end

@unless(condition)
    Content if false
@else
    Content if true
@end
```

### Conditions

Conditions are used by `@if`, `@unless`, `@elseif` and `@?{...}`.

| Operator | Meaning |
|----------|---------|
| `==` `!=` | Equal / not equal (PHP loose comparison: `"10" == 10`) |
| `>` `<` `>=` `<=` | Comparison (numeric strings compare as numbers) |
| `in`, `not in` | List on the right: membership, comparing as strings (`"7231" in ids` matches `7231`). String on the right: case-sensitive substring. Anything else: false |
| `not`, `!` | Negation |
| `and`, `&&` | Both (short-circuit) |
| `or`, `\|\|` | Either (short-circuit) |
| `( ... )` | Grouping |

**Operands:** string literals (`"..."` or `'...'`), numbers (`10`, `-1`, `9.99`), variable paths (`product.price`), function calls (`@length(items)`) and parenthesised expressions. A value used alone is tested for truthiness with PHP `empty()` rules: `0`, `"0"`, `""`, `[]`, `null` and `false` are false. A condition may span several lines.

`and`, `or`, `not` and `in` are keywords only as whole words, so `product.order`, `inventory` or `notes` are ordinary variables.

**Examples:**

```php
$template = '@if(stock > 0)
In Stock
@end
@if(price >= 100)
Premium Product
@else
Standard Product
@end
@if(status == "active" and role != "guest")
Welcome back, member!
@end';

echo $tsuku->process($template, ['stock' => 3, 'price' => 49, 'status' => 'active', 'role' => 'editor']);
// Output:
// In Stock
// Standard Product
// Welcome back, member!
```

More condition shapes:

```
@if(product.qty > 0 and ("sale" in product.tags or product.price < 10))
@if("7231" in @split(product.groups, ","))     exact id membership in a comma-separated string
@if(@length(product.images))                    a function result tested for truthiness
@if(not product.hidden)
```

::: warning true, false and null are not literals
`true`, `false` and `null` are looked up as **variables** (as in every earlier version). A missing variable is `null`, so `flag == true` compares `flag` with `null` - the opposite of what it looks like:

```php
$template = '@if(flag == true)
flag == true
@else
flag != true
@end';

echo $tsuku->process($template, ['flag' => false]);
// Output: flag == true
```

Test the value itself instead: `@if(flag)` / `@if(not flag)`.
:::

Malformed conditions throw a `ParseException` that names the line and column; an unknown function inside a condition throws a `FunctionNotFoundException`. See [Syntax Validation](#syntax-validation) and the [Conditionals guide](/guide/conditionals).

## Inline Conditions

```
@?{condition "value if true" : "value if false"}
```

An optional `?` may separate the condition from the first value. Each value is a string literal, a number, a variable path or a function call:

```php
$template = '{name}: @?{stock > 0 "In Stock" : "Out of Stock"}, @?{price >= 100 ? "Premium" : "Standard"}, @?{user.active "Yes" : "No"}';

echo $tsuku->process($template, ['name' => 'Widget', 'stock' => 0, 'price' => 150, 'user' => ['active' => true]]);
// Output: Widget: Out of Stock, Premium, Yes

echo $tsuku->process('@?{qty > 0 and visible label : @upper(fallback)}', [
    'qty' => 0, 'visible' => true, 'label' => 'x', 'fallback' => 'sold out',
]);
// Output: SOLD OUT
```

There is no `{condition ? a : b}` form - braces only ever hold a variable path.

## Pattern Matching

### @match Directive

Match a value against multiple cases:

```
@match(expression)
@case(value1)
    Content for value1
@case(value2)
    Content for value2
@default
    Content if no match
@end
```

**Multiple Values Per Case:**

```
@match(expression)
@case(value1, value2, value3)
    Content for any of these values
@default
    Default content
@end
```

- The `@match` subject is a variable path or a literal. Function calls and operators are **not** evaluated there - compute the value in PHP, or use `@if` / `@elseif` conditions.
- `@case` values are string literals, numbers or variable paths. They are compared with PHP loose comparison (`==`); the first matching case wins.
- `@default` is optional and must be the last branch.
- Do not match booleans with `@case(1, true)`: `true` is a (missing) variable, i.e. `null`, and `false == null`, so such a case matches `true`, `false` and `0` alike. Use `@if(flag)` or `@?{flag "yes" : "no"}`.

**Examples:**

```php
$template = '@match(status)
@case("active")
✓ Active
@case("pending")
⏳ Pending
@case("suspended")
⚠ Suspended
@default
❌ Unknown
@end';

echo $tsuku->process($template, ['status' => 'pending']);
// Output: ⏳ Pending
```

```php
$template = '@match(user.role)
@case("admin", "moderator")
Full Access
@case("user")
Standard Access
@case("guest")
Limited Access
@default
No Access
@end';

echo $tsuku->process($template, ['user' => ['role' => 'guest']]);
// Output: Limited Access
```

## Functions

Call functions using the `@` prefix. The opening parenthesis must follow the name directly:

```
@function_name(arg1, arg2, ...)
```

Arguments are string literals (`"..."` or `'...'`), numbers, variable paths or nested function calls. Variables keep their type (an array stays an array); a missing variable is passed as `""`; a number literal is passed as a string (`"2"`) unless the PHP parameter is typed. Quotes are respected, so `@replace(name, ")", "")` works.

### Built-in Functions

**String Functions:**

```
@upper(text)              // UPPERCASE (byte-based, like strtoupper)
@lower(text)              // lowercase
@capitalize(text)         // First letter uppercase
@trim(text)               // Remove surrounding whitespace
@substr(text, start, len) // Substring (len optional)
@replace(text, find, rep) // Replace
@concat(str1, str2, ...)  // Concatenate
```

**Number Functions:**

```
@number(value, decimals)                 // Format with thousands separator
@number(value, decimals, decPoint, sep)  // Custom formatting
@number_format(...)                      // Alias of @number
@round(value, precision)                 // Round
@ceil(value)                             // Round up
@floor(value)                            // Round down
@abs(value)                              // Absolute value
```

**Array Functions:**

```
@join(array, separator)  // Join elements (separator defaults to ",")
@length(array)           // Count elements (string length for strings)
@first(array)            // First element
@last(array)             // Last element
@split(string, delim)    // String to list (delimiter defaults to ",")
@contains(haystack, x)   // Same rules as "x in haystack"
```

**Escaping Functions:**

```
@html(text)    // HTML escaping
@xml(text)     // XML escaping
@json(text)    // JSON string, including the surrounding quotes
@url(text)     // URL encoding
@csv(text)     // CSV escaping
@escape(text, type)  // Generic escaping: html (default), xml, json, url, csv
```

**Date Functions:**

```
@date(format)            // Current date
@date(format, timestamp) // Format a Unix timestamp
@strtotime(string)       // String to timestamp
```

**Utility Functions:**

```
@default(value, fallback)  // Fallback for empty values (PHP empty(): "", "0", 0, null, [])
```

See [Built-in Functions](/guide/built-in-functions) for details and examples.

### Function Nesting

Functions can be nested:

```php
echo $tsuku->process('@upper(@trim(text))|@html(@upper(name))|@number(@abs(value), 2)', [
    'text' => ' hi ', 'name' => '<b>', 'value' => -3.5,
]);
// Output: HI|&lt;B&gt;|3.50
```

### Custom Functions

Register with `registerFunction()`:

```php
$tsuku->registerFunction('currency', function(float $amount): string {
    return '$' . number_format($amount, 2);
});

echo $tsuku->process('Price: @currency(product.price)', ['product' => ['price' => 1299.5]]);
// Output: Price: $1,299.50
```

A function name must be an identifier, and names that start with `end` or `else`, or with `default` (other than `default` itself), are read as directives - see [Function Names](/api/tsuku#function-names).

## Custom Directives

Directives registered with `registerDirective()` wrap a block: `@name(arguments) ... @end`, or `@name ... @end` without arguments. The handler receives the rendered body and the resolved arguments:

```php
$tsuku->registerDirective('cdata', fn(string $body): string => '<![CDATA[' . $body . ']]>');

echo $tsuku->process('<title>@cdata{product.name}@end</title>', ['product' => ['name' => 'Fish & Chips']]);
// Output: <title><![CDATA[Fish & Chips]]></title>
```

See [Custom Directives](/guide/custom-directives).

## Comments

Tsuku has no comment syntax. Everything that is not a variable, function or directive is output:

```php
$template = '// This is not a comment in Tsuku
Name: {name}';

echo $tsuku->process($template, ['name' => 'John']);
// Output:
// // This is not a comment in Tsuku
// Name: John
```

**Workaround:** keep comments in PHP, next to the template:

```php
// PHP comment before template
$template = 'Name: {name}';
```

## Whitespace Handling

Text is output as written, with one exception: whitespace that only lays out block directives (`@for`, `@if`, `@unless`, `@elseif`, `@else`, `@match`, `@case`, `@default`, `@end` and custom directives).

- A line that holds only directives renders nothing - neither its indentation nor its line break.
- Indentation in front of `@else`, `@elseif`, `@case`, `@default` and `@end` is dropped.
- Everything else - spaces inside content lines, blank lines, the line break after a content line - is kept.

```php
$template = 'Name:   {name}
@if(email)
Email:  {email}
@end
Done';

echo $tsuku->process($template, ['name' => 'John', 'email' => 'john@example.com']);
// Output:
// Name:   John
// Email:  john@example.com
// Done
```

The five exact rules, inline blocks and CRLF handling are described in [Whitespace Control](/guide/whitespace-control). A `\` at the end of a line joins it with the next line ([Line Continuation](/guide/line-continuation)).

## Special Characters

### Literal @

`@@` prints a literal `@`. A bare `@` that does not start a function, directive or `@?{` is a `ParseException`, so e-mail addresses and CSS at-rules in template text need the escape:

```php
echo $tsuku->process('Email: user@@example.com', []);
// Output: Email: user@example.com

echo $tsuku->process('@@media (max-width: 600px) { .a { color: {color}; } }', ['color' => 'red']);
// Output: @media (max-width: 600px) { .a { color: red; } }
```

Values that come from your data (`{user.email}`) are never parsed, so they need no escaping.

### Braces

Braces need no escaping: only `{plain.path}` is a variable. JSON and CSS braces pass through, and so do Magento directives such as <code v-pre>{{widget ...}}</code>, <code v-pre>{{media url="..."}}</code> and <code v-pre>{{trans "..."}}</code>:

```php
echo $tsuku->process('{{widget type="Magento\Cms\Block\Widget\Block" block_id="footer"}} {{media url="wysiwyg/a.jpg"}}', []);
// Output: {{widget type="Magento\Cms\Block\Widget\Block" block_id="footer"}} {{media url="wysiwyg/a.jpg"}}
```

Double braces are **not** an escape. <code v-pre>{{name}}</code> is a `{`, the variable `{name}` and a `}`:

```php
echo $tsuku->process('{{name}}', ['name' => 'Ann']);
// Output: {Ann}
```

To print a literal `{name}`, build it from parts:

```php
echo $tsuku->process('@concat("{", "name}")', []);
// Output: {name}
```

More in [Escapes & Special Characters](/guide/escaping).

## Operator Precedence

Inside conditions, from lowest to highest:

1. `or`, `||`
2. `and`, `&&`
3. `not`, `!`
4. Comparisons: `==`, `!=`, `>`, `<`, `>=`, `<=`, `in`, `not in`

So `a or b and c` means `a or (b and c)`, and `not a == b` means `not (a == b)`. Use parentheses to group differently. Comparisons cannot be chained (`1 < a < 3` is an error - write `1 < a and a < 3`).

## Grammar Summary

```
template      → node*
node          → text | "@@" | variable | function | inline_if | block
variable      → "{" path "}"
path          → identifier ("." (identifier | digits))*
function      → "@" name "(" [argument ("," argument)*] ")"
argument      → string | number | path | function
inline_if     → "@?{" condition ["?"] value ":" value "}"
value         → string | number | path | function

block         → for | if | unless | match | custom
for           → "@for(" path "as" identifier ["," identifier] ")" template "@end"
if            → "@if(" condition ")" template elseif* ["@else" template] "@end"
unless        → "@unless(" condition ")" template elseif* ["@else" template] "@end"
elseif        → "@elseif(" condition ")" template
match         → "@match(" subject ")" case* ["@default" template] "@end"
subject       → string | number | path
case          → "@case(" subject ("," subject)* ")" template
custom        → "@" registered_name ["(" [argument ("," argument)*] ")"] template "@end"

condition     → or
or            → and (("or" | "||") and)*
and           → not (("and" | "&&") not)*
not           → ("not" | "!") not | comparison
comparison    → operand [("==" | "!=" | ">" | "<" | ">=" | "<=" | "in" | "not" "in") operand]
operand       → string | number | path | function | "(" or ")"
```

Built-in directives accept whitespace before their parenthesis (`@if (x)`); functions and custom directives do not.

## Complete Example

```php
$template = 'Product Catalog
Generated: @date("Y-m-d", generated_at)

@for(products as product)
{product.position}. @upper(product.name)
   SKU: {product.sku}
   Price: $@number(product.price, 2)
   Status: @if(product.stock == 0)
       Out of Stock
   @elseif(product.stock < 10)
       Low Stock ({product.stock})
   @else
       In Stock
   @end
   @if(product.featured)
   ⭐ FEATURED PRODUCT
   @end

@end
Total Products: @length(products)';

$data = [
    'generated_at' => 1705320000, // 2024-01-15 12:00 UTC
    'products' => [
        [
            'position' => 1, // precomputed: templates have no arithmetic
            'name' => 'Widget Pro',
            'sku' => 'WID-001',
            'price' => 29.99,
            'stock' => 150,
            'featured' => true
        ],
        [
            'position' => 2,
            'name' => 'Gadget Max',
            'sku' => 'GAD-002',
            'price' => 49.99,
            'stock' => 5,
            'featured' => false
        ],
        [
            'position' => 3,
            'name' => 'Tool Set',
            'sku' => 'TOO-003',
            'price' => 19.99,
            'stock' => 0,
            'featured' => false
        ]
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**

```
Product Catalog
Generated: 2024-01-15

1. WIDGET PRO
   SKU: WID-001
   Price: $29.99
   Status: In Stock
   ⭐ FEATURED PRODUCT

2. GADGET MAX
   SKU: GAD-002
   Price: $49.99
   Status: Low Stock (5)

3. TOOL SET
   SKU: TOO-003
   Price: $19.99
   Status: Out of Stock

Total Products: 3
```

## Syntax Validation

Invalid syntax throws `Qoliber\Tsuku\Exception\ParseException` (a `TsukuException`). The message names the line and column; `getTemplateLine()` and `getTemplateColumn()` return them as numbers.

```php
// ✅ Valid
echo $tsuku->process('@for(items as item)...@end', ['items' => [1]]);
// Output: ...

// ❌ Missing @end
$tsuku->process('@for(items as item)...', ['items' => [1]]);
// Throws: ParseException: Expected @end but reached end of file at line 1, column 23 (@for opened at line 1, column 1)

// ❌ No parenthesis after @for
$tsuku->process('@for items...@end', []);
// Throws: ParseException: Invalid @ syntax at line 1, column 1 (write @@ for a literal @)

// ❌ Bare @ in text
$tsuku->process('Contact: user@example.com', []);
// Throws: ParseException: Invalid @ syntax at line 1, column 14 (write @@ for a literal @)

// ❌ Malformed condition
$tsuku->process('@if(qty = 1)one@end', []);
// Throws: ParseException: Unexpected '=' (use == to compare) at line 1, column 9 in condition 'qty = 1'

// ❌ Unknown function in a condition
$tsuku->process('@if(@nope(items))x@end', []);
// Throws: FunctionNotFoundException: Unknown function '@nope' at line 1, column 5 in condition '@nope(items)'

// An unclosed brace is not an error: it is plain text
echo $tsuku->process('Hello {name', ['name' => 'John']);
// Output: Hello {name
```

Unknown functions in output (`@nope(x)` outside a condition) and missing variables are not syntax errors: they follow the [strictness mode](/guide/strictness).

## Best Practices

### Use Escaping Functions

```
// ✅ Good - XSS protected
@html(user.comment)

// ❌ Bad - XSS vulnerable
{user.comment}
```

### Consistent Formatting

```
// ✅ Good - readable; the directive-only lines produce no output
@for(items as item)
    {item.name}
@end

// ❌ Bad - hard to read
@for(items as item){item.name}@end
```

The two forms do not render the same: the first prints each name on its own line (with the four spaces of indentation, which belong to the content line), the second prints all names joined.

### Meaningful Variable Names

```
// ✅ Good
@for(products as product)

// ❌ Bad
@for(products as p)
```

## Next Steps

- [Tsuku Class →](/api/tsuku) - API reference
- [Built-in Functions →](/guide/built-in-functions) - Function details
- [Examples →](/examples/) - Real-world examples
