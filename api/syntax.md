# Syntax Reference

Complete syntax reference for Tsuku template language.

## Variables

Access data using curly braces:

```
{variable_name}
{object.property}
{array.key}
{nested.deep.value}
```

**Examples:**

```php
$data = [
    'name' => 'John',
    'user' => ['email' => 'john@example.com'],
    'product' => ['price' => 29.99]
];

{name}            // John
{user.email}      // john@example.com
{product.price}   // 29.99
```

**Smart Object Access:**

Tsuku automatically tries multiple access strategies:

1. Array access: `$user['name']`
2. Getter methods: `$user->getName()`
3. Boolean getters: `$user->isActive()`
4. Direct method calls: `$user->total()`
5. Public properties: `$user->name`

## Loops

### @for Directive

Iterate over arrays:

```
@for(array as item)
    {item}
@end
```

**With Index:**

```
@for(array as item, index)
    {index}. {item}
@end
```

**Note:** Value comes first, then key/index (opposite of PHP).

**Examples:**

```php
// Simple loop
@for(products as product)
- {product.name}: ${product.price}
@end

// With index
@for(items as item, index)
{index}. {item}
@end

// Nested loops
@for(categories as category)
Category: {category.name}
@for(category.products as product)
  - {product.name}
@end
@end
```

## Conditionals

### @if Directive

```
@if(condition)
    Content if true
@end
```

**With @else:**

```
@if(condition)
    Content if true
@else
    Content if false
@end
```

**Supported Operators:**

- `==` - Equal
- `!=` - Not equal
- `>` - Greater than
- `<` - Less than
- `>=` - Greater than or equal
- `<=` - Less than or equal

**Examples:**

```php
@if(stock > 0)
In Stock
@end

@if(price >= 100)
Premium Product
@else
Standard Product
@end

@if(status == "active")
✓ Active
@end

@if(role != "guest")
Welcome, member!
@end
```

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

**Examples:**

```php
@unless(sold_out)
Available for purchase
@end

@unless(admin)
Limited access
@else
Full access
@end
```

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

**Examples:**

```php
@match(status)
@case("active")
✓ Active
@case("pending")
⏳ Pending
@case("suspended")
⚠ Suspended
@default
❌ Unknown
@end

@match(user.role)
@case("admin", "moderator")
Full Access
@case("user")
Standard Access
@case("guest")
Limited Access
@default
No Access
@end
```

## Functions

Call functions using `@` prefix:

```
@function_name(arg1, arg2, ...)
```

### Built-in Functions

**String Functions:**

```
@upper(text)              // UPPERCASE
@lower(text)              // lowercase
@capitalize(text)         // Capitalize
@trim(text)               // Remove whitespace
@substr(text, start, len) // Substring
@replace(text, find, rep) // Replace
@concat(str1, str2, ...)  // Concatenate
```

**Number Functions:**

```
@number(value, decimals)                 // Format with thousands
@number(value, decimals, decPoint, sep)  // Custom formatting
@round(value, precision)                 // Round
@ceil(value)                             // Round up
@floor(value)                            // Round down
@abs(value)                              // Absolute value
```

**Array Functions:**

```
@join(array, separator)  // Join elements
@length(array)           // Count elements
@first(array)            // First element
@last(array)             // Last element
```

**Escaping Functions:**

```
@html(text)    // HTML escaping
@xml(text)     // XML escaping
@json(text)    // JSON escaping
@url(text)     // URL encoding
@csv(text)     // CSV escaping
@escape(text, type)  // Generic escaping
```

**Date Functions:**

```
@date(format)           // Current date
@date(format, timestamp) // Format timestamp
@strtotime(string)      // String to timestamp
```

**Utility Functions:**

```
@default(value, fallback)  // Fallback for empty values
```

### Function Nesting

Functions can be nested:

```
@upper(@trim(text))
@html(@upper(name))
@number(@abs(value), 2)
```

### Custom Functions

Register with `registerFunction()`:

```php
$tsuku->registerFunction('currency', function(float $amount): string {
    return ' . number_format($amount, 2);
});
```

**Usage:**

```
@currency(product.price)
```

## Ternary Operator

**Syntax:**

```
{condition ? true_value : false_value}
```

**Examples:**

```php
{stock > 0 ? "In Stock" : "Out of Stock"}
{price >= 100 ? "Premium" : "Standard"}
{user.active ? "Yes" : "No"}
```

## Comments

Tsuku does not have comment syntax. Use your programming language's comments:

```php
$template = '
// This is not a comment in Tsuku
Name: {name}
';
```

**Workaround:**

```php
// PHP comment before template
$template = 'Name: {name}';
```

## Whitespace Handling

Tsuku preserves **all** whitespace exactly:

```
Hello {name}     // No space before/after
Hello {name} !   // Spaces preserved
Hello
{name}           // Newline preserved
```

**Example:**

```php
$template = 'Name:   {name}
Email:  {email}';

// Output preserves spacing:
// Name:   John
// Email:  john@example.com
```

## Special Characters

### Escaping Braces

Use double braces to output literal braces:

```
{{not_a_variable}}  // Output: {not_a_variable}
```

### Escaping @ Symbol

To output literal `@`, use `@@`:

```
Email: @@example.com  // Output: Email: @example.com
```

## Operator Precedence

1. Function calls: `@function()`
2. Variable access: `{var}`
3. Ternary: `condition ? a : b`
4. Comparison: `==`, `!=`, `>`, `<`, `>=`, `<=`

## Grammar Summary

```
template     → (text | variable | directive | function)*
variable     → "{" identifier ("." identifier)* "}"
directive    → for_directive | if_directive | unless_directive | match_directive
for_directive → "@for(" identifier "as" identifier ["," identifier] ")" template "@end"
if_directive → "@if(" expression ")" template ["@else" template] "@end"
unless_directive → "@unless(" expression ")" template ["@else" template] "@end"
match_directive → "@match(" expression ")" case* ["@default" template] "@end"
case          → "@case(" value ("," value)* ")" template
function      → "@" identifier "(" [expression ("," expression)*] ")"
expression    → variable | string | number | function | ternary
ternary       → expression "?" expression ":" expression
```

## Complete Example

```php
$template = 'Product Catalog
Generated: @date("Y-m-d H:i:s")

@for(products as product, index)
{index + 1}. @upper(product.name)
   SKU: {product.sku}
   Price: $@number(product.price, 2)
   Status: @match(product.stock)
       @case(0)Out of Stock
       @default@if(product.stock < 10)Low Stock ({product.stock})@else In Stock@end
   @end
   @if(product.featured)
   ⭐ FEATURED PRODUCT
   @end

@end

Total Products: @length(products)';

$data = [
    'products' => [
        [
            'name' => 'Widget Pro',
            'sku' => 'WID-001',
            'price' => 29.99,
            'stock' => 150,
            'featured' => true
        ],
        [
            'name' => 'Gadget Max',
            'sku' => 'GAD-002',
            'price' => 49.99,
            'stock' => 5,
            'featured' => false
        ],
        [
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
Generated: 2024-01-15 14:30:45

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

Invalid syntax will throw `TemplateException`:

```php
// ✅ Valid
@for(items as item)...@end

// ❌ Invalid - missing @end
@for(items as item)...

// ❌ Invalid - unclosed brace
{name

// ❌ Invalid - invalid syntax
@for items...@end
```

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
// ✅ Good - readable
@for(items as item)
    {item.name}
@end

// ❌ Bad - hard to read
@for(items as item){item.name}@end
```

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
