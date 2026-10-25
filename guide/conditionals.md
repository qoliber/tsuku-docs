# Conditionals

Conditionals allow you to include or exclude content based on conditions. Tsuku provides `@if`, `@unless`, `@elseif` and `@else` directives, and inline conditions with `@?{...}`.

Conditions can compare values, combine them with `and` / `or` / `not`, test membership with `in`, and call functions. Since 1.4.0 every condition is parsed once into an expression tree, and a malformed condition throws a `ParseException` instead of silently evaluating part of it.

## @if Directive

Execute content when condition is true:

```php
$template = '@if(stock > 0)
In Stock
@end';

$data = ['stock' => 10];

$result = $tsuku->process($template, $data);
// Output: In Stock
```

## @if with @else

Provide alternative content when condition is false:

```php
$template = '@if(stock > 0)
✓ In Stock
@else
✗ Out of Stock
@end';

$data = ['stock' => 0];

$result = $tsuku->process($template, $data);
// Output: ✗ Out of Stock
```

## @elseif

Chain further conditions with `@elseif`. The first branch whose condition is true is rendered, and the whole chain is closed by a single `@end`:

```php
$template = '@if(qty > 10)
In stock
@elseif(qty > 0)
Low stock
@else
Sold out
@end';

$data = ['qty' => 3];

$result = $tsuku->process($template, $data);
// Output: Low stock
```

You can use as many `@elseif` branches as you need; `@else` is optional and must come last.

## @unless Directive

Execute content when condition is **false** (inverse of @if):

```php
$template = '@unless(sold_out)
Available for purchase
@else
Currently unavailable
@end';

$data = ['sold_out' => false];

$result = $tsuku->process($template, $data);
// Output: Available for purchase
```

`@elseif` works after `@unless` too. Only the `@unless` condition is inverted; the `@elseif` conditions are checked as written:

```php
$template = '@unless(user.active)
Account disabled
@elseif(user.admin)
Administrator
@else
Member
@end';

$data = ['user' => ['active' => true, 'admin' => false]];

$result = $tsuku->process($template, $data);
// Output: Member
```

## Comparison Operators

Tsuku supports standard comparison operators:

### Greater Than (>)

```php
$template = '@if(price > 100)
Premium Product
@end';

$data = ['price' => 150];
// Output: Premium Product
```

### Less Than (<)

```php
$template = '@if(stock < 10)
Low Stock Warning
@end';

$data = ['stock' => 5];
// Output: Low Stock Warning
```

### Greater Than or Equal (>=)

```php
$template = '@if(rating >= 4)
Recommended
@end';

$data = ['rating' => 4];
// Output: Recommended
```

### Less Than or Equal (<=)

```php
$template = '@if(price <= 50)
Budget-Friendly
@end';

$data = ['price' => 50];
// Output: Budget-Friendly
```

### Equal (==)

```php
$template = '@if(status == "active")
Currently Active
@end';

$data = ['status' => 'active'];
// Output: Currently Active
```

### Not Equal (!=)

```php
$template = '@if(status != "cancelled")
Order Pending
@end';

$data = ['status' => 'pending'];
// Output: Order Pending
```

### How Values Are Compared

Comparisons use PHP's **loose** comparison (`==`, `<`, ...), exactly as in earlier versions:

- Numeric strings compare as numbers: `"10" > "9"` is true, and `"10" == "1e1"` is true.
- A number and a non-numeric string compare as strings (PHP 8 rules): `code == 0` is false when `code` is `"abc"`.
- A missing variable is `null`.

```php
$template = '@if(stock > "9")more than nine@else nine or less@end';

$data = ['stock' => '10'];
// Output: more than nine
```

There is no strict comparison: `===` and `!==` are not supported and throw a `ParseException` (see [Errors in Conditions](#errors-in-conditions)).

## Logical Operators

Combine conditions with `and`, `or` and `not`. The symbols `&&`, `||` and `!` mean exactly the same:

| Operator | Alternative | True when |
|----------|-------------|-----------|
| `a and b` | `a && b` | both are true |
| `a or b` | `a \|\| b` | at least one is true |
| `not a` | `!a` | `a` is false |

```php
$template = '@if(product.qty > 0 and product.price < 10)
Bargain!
@end';

$data = ['product' => ['qty' => 4, 'price' => 7.5]];
// Output: Bargain!
```

```php
$template = '@if(status == "new" || status == "sale")
<span class="badge">{status}</span>
@end';

$data = ['status' => 'sale'];
// Output: <span class="badge">sale</span>
```

```php
$template = '@if(!user.verified)
Please verify your email address.
@end';

$data = ['user' => ['verified' => false]];
// Output: Please verify your email address.
```

### Parentheses and Precedence

From lowest to highest precedence: `or` < `and` < `not` < comparisons (`==` `!=` `>` `<` `>=` `<=` `in` `not in`). So `a or b and c` means `a or (b and c)`, and `not status == "active"` means `not (status == "active")`. Use parentheses to group differently:

```php
$template = '@if(featured or on_sale and in_stock)A@else B@end
@if((featured or on_sale) and in_stock)A@else B@end';

$data = ['featured' => true, 'on_sale' => false, 'in_stock' => false];
// Output:
// A
//  B
```

The first condition is true because `featured` alone satisfies `featured or (on_sale and in_stock)`; the second requires `in_stock`. (The space before `B` is part of the single-line `@else B` branch, see [Whitespace Control](/guide/whitespace-control).)

### Short-Circuit Evaluation

`and` stops at the first false operand and `or` stops at the first true one, so the rest is never evaluated. That makes guards safe and skips expensive function calls:

```php
$tsuku->registerFunction('stock_lookup', function (string $sku): int {
    throw new RuntimeException('not called: on_sale already decided the condition');
});

$template = '@if(product.on_sale or @stock_lookup(product.sku) > 0)
Deal!
@end';

$data = ['product' => ['on_sale' => true, 'sku' => 'WID-001']];

echo $tsuku->process($template, $data);
// Output: Deal!
```

```php
$template = '@if(product and product.price > 0)
Price: {product.price}
@else
No price
@end';

$data = ['product' => null];
// Output: No price
```

## Membership: in / not in

`needle in haystack` behaves differently depending on what is on the right:

| Right-hand side | `in` means | Example |
|-----------------|-----------|---------|
| a list (array) | membership; scalars are compared **as strings**, so `"12"` matches `12` | `product.category_id in allowed_ids` |
| a string | **case-sensitive substring** test | `"Pro" in product.name` |
| anything else (number, null, missing variable, object) | always false | |

`not in` is the exact opposite.

```php
$template = '@if(product.category_id in allowed)
Allowed
@else
Blocked
@end';

$data = [
    'product' => ['category_id' => '12'],
    'allowed' => [3, 12, 40],
];
// Output: Allowed
```

```php
$template = '@if(country not in shipping_countries)
We do not ship to {country} yet.
@end';

$data = ['country' => 'US', 'shipping_countries' => ['DE', 'AT', 'PL']];
// Output: We do not ship to US yet.
```

### Substring vs. Exact Id Membership

Because a string on the right means "substring", testing an id against a comma-separated string is **not** an exact match: `"7231"` is found inside `"72310,5"`:

```php
$template = '@if("7231" in product.groups)
member
@else
not a member
@end';

$data = ['product' => ['groups' => '72310,5']];
// Output: member
```

Split the string into a list first with [`@split`](#contains-and-split) to compare whole ids:

```php
$template = '@if("7231" in @split(product.groups, ","))
member
@else
not a member
@end';

$data = ['product' => ['groups' => '72310,5']];
// Output: not a member
```

The substring test is case-sensitive: `"Sale" in title` is false for `"Summer sale"`. Use `@lower()` on both sides if you need a case-insensitive check.

### @contains and @split

The same rules are available as functions:

- `@contains(haystack, needle)` - same as `needle in haystack` (outputs `1` or an empty string when used outside a condition)
- `@split(value, delimiter)` - splits a string into a list; the delimiter defaults to `","`; `""` and `null` give an empty list; a list is returned unchanged

```php
$template = '@if(@contains(tags, "sale"))On sale@end
@join(@split(groups, ","), " | ")';

$data = ['tags' => ['new', 'sale'], 'groups' => '7231,72310,5'];
// Output:
// On sale
// 7231 | 72310 | 5
```

## Functions in Conditions

Call any built-in or [custom function](/guide/custom-functions) with the `@` prefix inside a condition. A function call (or any single value) on its own is tested for truthiness:

```php
$template = '@if(@length(items))
{items.0} and more
@else
Nothing yet
@end';

$data = ['items' => ['Apple', 'Banana']];
// Output: Apple and more
```

```php
$template = '@if(@length(product.images) > 1)
Gallery
@else
Single image
@end';

$data = ['product' => ['images' => ['a.jpg']]];
// Output: Single image
```

The `@` is required: `length(items)` is not a function call (it throws "Unexpected '('"). Calling a function that is not registered throws a `FunctionNotFoundException` while the template is parsed, in every strictness mode (see [Errors in Conditions](#errors-in-conditions)).

## Operands

A condition can use:

- **String literals** in double or single quotes: `"active"`, `'active'`
- **Numbers**: `10`, `-5`, `4.5`
- **Variable paths**: `stock`, `product.price`, `order.customer.name`
- **Function calls**: `@length(items)`, `@lower(name)`
- **Parenthesised expressions**: `(a or b)`

There is no arithmetic: `qty - 1 > 0` or `index < @length(items) - 1` throw a `ParseException`. Compute the value in PHP and pass it in the data, or register a function that does the calculation.

### No true / false / null Literals

`true`, `false` and `null` are **not** literals in Tsuku conditions - they are ordinary variable names, and since your data normally has no key called `true`, they evaluate to `null`. Comparing with them gives surprising results:

```php
$template = '@if(flag == true)
yes
@else
no
@end';

$data = ['flag' => false];
// Output: yes
```

`false == null` is true in PHP, so this prints `yes` for a **false** flag (and `no` for a true one). Test the value directly instead:

```php
$template = '@if(flag)
yes
@else
no
@end
@if(not flag)
flag is off
@end';

$data = ['flag' => false];
// Output:
// no
// flag is off
```

The same applies to `null`: `@if(discount == null)` is also true when `discount` is `0`. Use `@if(not discount)` or check the value in PHP.

### Keywords Are Whole Words

`and`, `or`, `not` and `in` are keywords only as whole words outside of strings. Variables that merely contain them work as usual:

```php
$template = '@if(product.order > 0 and inventory and notes)
ok
@end';

$data = ['product' => ['order' => 2], 'inventory' => 5, 'notes' => 'Fragile'];
// Output: ok
```

## Multi-line Conditions

Long conditions can span several lines; line breaks inside the parentheses are ignored:

```php
$template = '@if(
    product.qty > 0
    and product.visible
    and (product.price < 100 or product.on_sale)
)
Show product
@end';

$data = ['product' => ['qty' => 1, 'visible' => true, 'price' => 200, 'on_sale' => true]];
// Output: Show product
```

## Variable Paths in Conditions

Use dot notation in conditions:

```php
$template = '@if(product.stock > 0)
{product.name} is available
@else
{product.name} is out of stock
@end';

$data = [
    'product' => [
        'name' => 'Widget',
        'stock' => 10,
    ],
];

$result = $tsuku->process($template, $data);
// Output: Widget is available
```

## Conditionals in Loops

Combine with loops for powerful filtering:

```php
$template = '@for(products as product)
@if(product.featured)
⭐ {product.name} - Featured Product
@else
{product.name}
@end
@end';

$data = [
    'products' => [
        ['name' => 'Widget', 'featured' => true],
        ['name' => 'Gadget', 'featured' => false],
        ['name' => 'Tool', 'featured' => true],
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// ⭐ Widget - Featured Product
// Gadget
// ⭐ Tool - Featured Product
```

## Nested Conditionals

Nest conditionals for complex logic:

```php
$template = '@if(user.role == "admin")
Admin Dashboard
@if(user.permissions == "full")
  - Full Access
@else
  - Limited Access
@end
@else
User Dashboard
@end';

$data = [
    'user' => [
        'role' => 'admin',
        'permissions' => 'full',
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// Admin Dashboard
//   - Full Access
```

Directive lines can be indented to show the nesting - lines that hold only directives print nothing, not even their indentation. See [Whitespace Control](/guide/whitespace-control).

## Real-World Examples

### Stock Status Badge

```php
$template = '@for(products as product)
Product: {product.name}
Status: @if(product.stock > 10)
<span class="badge badge-success">In Stock</span>
@elseif(product.stock > 0)
<span class="badge badge-warning">Low Stock</span>
@else
<span class="badge badge-danger">Out of Stock</span>
@end
@end';

$data = [
    'products' => [
        ['name' => 'Widget', 'stock' => 25],
        ['name' => 'Gadget', 'stock' => 3],
        ['name' => 'Tool', 'stock' => 0],
    ],
];
```

**Output:**

```
Product: Widget
Status: <span class="badge badge-success">In Stock</span>
Product: Gadget
Status: <span class="badge badge-warning">Low Stock</span>
Product: Tool
Status: <span class="badge badge-danger">Out of Stock</span>
```

### Conditional CSV Fields

Templates cannot do arithmetic, so compute derived values such as the final price in PHP and pass them in the data. An inline condition keeps each CSV value on its row:

```php
$products = [
    ['name' => 'Widget', 'price' => 100, 'discount' => 10],
    ['name' => 'Gadget', 'price' => 50, 'discount' => 0],
];

foreach ($products as &$product) {
    $product['final_price'] = $product['price'] - $product['discount'];
}
unset($product);

$template = 'Name,Price,Discount,Final
@for(products as product)
{product.name},{product.price},@?{product.discount > 0 product.discount : "0"},{product.final_price}
@end';

$data = ['products' => $products];
```

**Output:**

```
Name,Price,Discount,Final
Widget,100,10,90
Gadget,50,0,50
```

### Email Template

```php
$template = 'Dear {customer.name},

@if(order.status == "shipped")
Your order #{order.id} has been shipped!
Tracking: {order.tracking}
@elseif(order.status == "processing")
Your order #{order.id} is being processed.
@else
Order status: {order.status}
@end

Thank you for your purchase!';

$data = [
    'customer' => ['name' => 'Ann'],
    'order' => ['id' => 1042, 'status' => 'processing'],
];
```

**Output:**

```
Dear Ann,

Your order #1042 is being processed.

Thank you for your purchase!
```

## Boolean Values

Check boolean values directly:

```php
$template = '@if(active)
Service is active
@else
Service is inactive
@end';

$data = ['active' => true];
// Output: Service is active
```

Don't compare with `true` or `false` - see [No true / false / null Literals](#no-true-false-null-literals).

## Truthy/Falsy Values

A value on its own (a variable, a literal or a function result) is tested like PHP's `!empty()`:

**Falsy values:**
- `false`
- `0` and `0.0`
- `"0"`
- `""` (empty string)
- `null` - also every missing variable
- `[]` (empty array)

**Truthy values:**
- `true`
- Any non-zero number
- Any non-empty string other than `"0"` (also `"0.0"` and `" "`)
- Any non-empty array
- Objects

```php
$template = '@if(value)
Value is truthy
@else
Value is falsy
@end';

// Falsy
$tsuku->process($template, ['value' => 0]);     // Output: Value is falsy
$tsuku->process($template, ['value' => '']);    // Output: Value is falsy
$tsuku->process($template, ['value' => null]);  // Output: Value is falsy

// Truthy
$tsuku->process($template, ['value' => 1]);     // Output: Value is truthy
$tsuku->process($template, ['value' => 'text']); // Output: Value is truthy
```

## Comparing Strings

```php
$template = '@if(category == "electronics")
Electronics Category
@else
Other Category
@end';

$data = ['category' => 'electronics'];
// Output: Electronics Category
```

String comparison is case-sensitive. Normalise the case with a function if needed: `@if(@lower(category) == "electronics")`.

## Inline Conditions: @?{...}

For a short value inside a line, use an inline condition: `@?{condition trueValue : falseValue}`. The condition accepts everything an `@if` condition accepts, and a `?` between the condition and the first value is optional:

```php
$template = '@?{qty > 0 "In stock" : "Sold out"}
@?{qty > 0 ? "In stock" : "Sold out"}
@?{qty > 0 and visible "Buy now" : "Unavailable"}';

$data = ['qty' => 2, 'visible' => false];
// Output:
// In stock
// In stock
// Unavailable
```

Each value can be a quoted string, a number, a variable path or a function call:

```php
$template = 'Hello @?{customer.vip @upper(customer.name) : customer.name}!';

$data = ['customer' => ['vip' => true, 'name' => 'Ann']];
// Output: Hello ANN!
```

Inline conditions are the best choice for values inside CSV, JSON or XML lines, because they never add whitespace. There is no `{condition ? a : b}` form - only `@?{...}`.

## Errors in Conditions

A condition that Tsuku cannot parse throws a `ParseException` (namespace `Qoliber\Tsuku\Exception`) before anything is rendered. The message names the template line and column and repeats the condition, and `getTemplateLine()` / `getTemplateColumn()` return the position:

```php
$tsuku->process('@if(qty = 0)Sold out@end', ['qty' => 0]);
// Throws: ParseException: Unexpected '=' (use == to compare) at line 1, column 9 in condition 'qty = 0'

$tsuku->process('@if(status === "active")Active@end', []);
// Throws: ParseException: Unexpected '=' (use == to compare) at line 1, column 14 in condition 'status === "active"'

$tsuku->process('@if(qty - 1 > 0)Several@end', []);
// Throws: ParseException: Unexpected '-' at line 1, column 9 in condition 'qty - 1 > 0'

$tsuku->process('@if(1 < qty < 10)Few@end', []);
// Throws: ParseException: Chained comparisons are not supported, combine them with "and" at line 1, column 13 in condition '1 < qty < 10'

$tsuku->process('@if(qty > 0 and)Yes@end', []);
// Throws: ParseException: Missing value at line 1, column 16 in condition 'qty > 0 and'

$tsuku->process('@if(status == "active)Yes@end', []);
// Throws: ParseException: Unterminated string at line 1, column 15 in condition 'status == "active'

$tsuku->process('@if()Yes@end', []);
// Throws: ParseException: Empty condition at line 1, column 5 in condition ''
```

Positions refer to the template as you wrote it, also on later lines:

```php
$template = 'Line one
  @if(price => 10)Expensive@end';

$tsuku->process($template, []);
// Throws: ParseException: Unexpected '=' (use == to compare) at line 2, column 13 in condition 'price => 10'
```

An unknown function inside a condition throws a `FunctionNotFoundException` (a `TsukuException`; `getFunctionName()` returns the name without `@`). This happens while the template is parsed and in **every** [strictness mode](/guide/strictness) - unlike an unknown function in plain output, which SILENT mode simply removes:

```php
$template = 'Stock:
@if(@in_stock(product))yes@end';

$tsuku->process($template, []);
// Throws: FunctionNotFoundException: Unknown function '@in_stock' at line 2, column 5 in condition '@in_stock(product)'
```

In 1.3.0 these conditions were evaluated silently and wrongly; see [Upgrading from 1.3](/guide/upgrading).

## Whitespace Around Conditionals

Lines that contain only directives produce no output, so the content lines appear exactly as written:

```php
$template = 'Start
@if(show)
  Indented content
@end
End';

$data = ['show' => true];
// Output:
// Start
//   Indented content
// End
```

The rules for directives that share a line with content (for example `Status: @if(...)` or `{sku},@if(...)in@else out@end`) are explained in [Whitespace Control](/guide/whitespace-control).

## Common Patterns

### Optional Field

```php
$template = 'Name: {name}
@if(email)
Email: {email}
@end';

$data = ['name' => 'Ann', 'email' => ''];
// Output: Name: Ann
```

### Conditional Formatting

```php
$template = '@if(price > 1000)
$@number(price, 2) (Premium)
@else
$@number(price, 2)
@end';

$data = ['price' => 1500];
// Output: $1,500.00 (Premium)
```

### Status Messages

```php
$template = '@match(status)
@case("active")
✓ Active
@case("pending")
⏳ Pending
@case("inactive")
✗ Inactive
@default
Unknown
@end';

$data = ['status' => 'inactive'];
// Output: ✗ Inactive
```

::: tip
For multiple condition checks with the same variable, use [@match](/guide/pattern-matching) instead of nested @if statements - it's cleaner!
:::

## Next Steps

- [Pattern Matching →](/guide/pattern-matching) - Better than nested if/else
- [Whitespace Control →](/guide/whitespace-control) - How directive lines affect the output
- [Loops →](/guide/loops) - Iterate with conditions
- [Built-in Functions →](/guide/built-in-functions) - Transform values in conditions
