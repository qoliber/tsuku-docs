# Conditionals

Conditionals allow you to include or exclude content based on conditions. Tsuku provides `@if`, `@unless`, and `@else` directives.

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

## Real-World Examples

### Stock Status Badge

```php
$template = '@for(products as product)
Product: {product.name}
Status: @if(product.stock > 10)
<span class="badge badge-success">In Stock</span>
@else
@if(product.stock > 0)
<span class="badge badge-warning">Low Stock</span>
@else
<span class="badge badge-danger">Out of Stock</span>
@end
@end
@end';
```

### Conditional CSV Fields

```php
$template = 'Name,Price,Discount,Final
@for(products as product)
{product.name},{product.price},@if(product.discount > 0){product.discount}@else0@end,@if(product.discount > 0){product.price - product.discount}@else{product.price}@end
@end';
```

### Email Template

```php
$template = 'Dear {customer.name},

@if(order.status == "shipped")
Your order #{order.id} has been shipped!
Tracking: {order.tracking}
@else
@if(order.status == "processing")
Your order #{order.id} is being processed.
@else
Order status: {order.status}
@end
@end

Thank you for your purchase!';
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

## Truthy/Falsy Values

Tsuku uses PHP's truthy/falsy evaluation:

**Falsy values:**
- `false`
- `0`
- `"0"`
- `""` (empty string)
- `null`
- `[]` (empty array)

**Truthy values:**
- `true`
- Any non-zero number
- Any non-empty string
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

## Whitespace Preservation

Conditionals preserve exact whitespace:

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

## Common Patterns

### Optional Field

```php
$template = 'Name: {name}
@if(email)
Email: {email}
@end';
```

### Conditional Formatting

```php
$template = '@if(price > 1000)
$@number(price, 2) (Premium)
@else
$@number(price, 2)
@end';
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
```

::: tip
For multiple condition checks with the same variable, use [@match](/guide/pattern-matching) instead of nested @if statements - it's cleaner!
:::

## Next Steps

- [Pattern Matching →](/guide/pattern-matching) - Better than nested if/else
- [Loops →](/guide/loops) - Iterate with conditions
- [Built-in Functions →](/guide/built-in-functions) - Transform values in conditions
