# Pattern Matching

The `@match` directive provides clean pattern matching for multiple condition checks - similar to PHP 8's match expression. It's cleaner and more readable than nested `@if` statements.

## Basic @match

Match a value against multiple cases:

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

$data = ['status' => 'pending'];

$result = $tsuku->process($template, $data);
// Output: ⏳ Pending
```

## Syntax Flexibility

Tsuku's `@match` syntax is **flexible** - you can write it multiline for clarity or inline for compactness:

**Multiline (more readable for complex content):**
```php
$template = '@match(status)
@case("active") ✓ Active
@case("pending") ⏳ Pending
@case("suspended") ⚠ Suspended
@default ❌ Unknown
@end';
```

**Inline (compact for simple values):**
```php
$template = '@match(status) @case("active") ✓ Active @case("pending") ⏳ Pending @default ❌ Unknown @end';
```

**Inline in loops or other contexts:**
```php
$template = '@for(users as user)
{user.name}: @match(user.status) @case("active") ✓ @case("inactive") ✗ @end
@end';
```

Both styles work identically - choose based on what's more readable for your use case! The same flexibility applies to `@if`, `@for`, and all other directives.

## Why @match Over @if?

**Without @match (verbose):**
```php
$template = '@if(status == "active")
✓ Active
@else
@if(status == "pending")
⏳ Pending
@else
@if(status == "suspended")
⚠ Suspended
@else
❌ Unknown
@end
@end
@end';
```

**With @match (clean):**
```php
$template = '@match(status)
@case("active") ✓ Active
@case("pending") ⏳ Pending
@case("suspended") ⚠ Suspended
@default ❌ Unknown
@end';
```

Much cleaner! 🎯

## Multiple Values Per Case

Match multiple values in a single case:

```php
$template = '@match(user.role)
@case("admin", "moderator")
Full Access
@case("user", "guest")
Limited Access
@default
No Access
@end';

$data = ['user' => ['role' => 'moderator']];

$result = $tsuku->process($template, $data);
// Output: Full Access
```

## Number Matching

Match numbers:

```php
$template = '@match(code)
@case(200) OK
@case(404) Not Found
@case(500) Server Error
@default Unknown Status
@end';

$data = ['code' => 404];

$result = $tsuku->process($template, $data);
// Output: Not Found
```

## @default Fallback

The `@default` case matches when no other case matches:

```php
$template = '@match(status)
@case("active")
Active
@case("inactive")
Inactive
@default
Unknown status: {status}
@end';

$data = ['status' => 'cancelled'];

$result = $tsuku->process($template, $data);
// Output: Unknown status: cancelled
```

Optional - you can omit @default if you don't need fallback:

```php
$template = '@match(status)
@case("active")
Active
@end';

$data = ['status' => 'other'];

$result = $tsuku->process($template, $data);
// Output: (empty)
```

## Variable Paths

Use dot notation in match expressions:

```php
$template = '@match(order.status)
@case("shipped")
Order #{order.id} has shipped
@case("delivered")
Order #{order.id} delivered
@default
Processing order #{order.id}
@end';

$data = [
    'order' => ['id' => 12345, 'status' => 'shipped'],
];

$result = $tsuku->process($template, $data);
// Output: Order #12345 has shipped
```

## Match in Loops

Perfect for status badges in loops:

```php
$template = '@for(orders as order)
Order #{order.id}: @match(order.status)
@case("pending", "processing")
<span class="badge badge-warning">{order.status}</span>
@case("shipped", "delivered")
<span class="badge badge-success">{order.status}</span>
@case("cancelled", "refunded")
<span class="badge badge-danger">{order.status}</span>
@default
<span class="badge badge-secondary">{order.status}</span>
@end
@end';

$data = [
    'orders' => [
        ['id' => 1, 'status' => 'shipped'],
        ['id' => 2, 'status' => 'pending'],
        ['id' => 3, 'status' => 'cancelled'],
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// Order #1: <span class="badge badge-success">shipped</span>
// Order #2: <span class="badge badge-warning">pending</span>
// Order #3: <span class="badge badge-danger">cancelled</span>
```

## Nested Match

Match within match for complex logic:

```php
$template = '@match(user.type)
@case("customer")
Customer Portal
@match(user.tier)
@case("premium")
  - VIP Support
@case("standard")
  - Standard Support
@end
@case("admin")
Admin Dashboard
@end';
```

## Match with Functions

Use functions in case content:

```php
$template = '@match(format)
@case("uppercase")
@upper(text)
@case("lowercase")
@lower(text)
@case("capitalize")
@capitalize(text)
@default
{text}
@end';

$data = [
    'format' => 'uppercase',
    'text' => 'hello world',
];

$result = $tsuku->process($template, $data);
// Output: HELLO WORLD
```

## Real-World Examples

### Order Status Email

```php
$template = 'Dear {customer.name},

@match(order.status)
@case("confirmed")
Your order #{order.id} has been confirmed.
Expected delivery: {order.delivery_date}
@case("shipped")
Your order #{order.id} is on its way!
Tracking: {order.tracking}
@case("delivered")
Your order #{order.id} has been delivered.
Thank you for your purchase!
@case("cancelled")
Your order #{order.id} has been cancelled.
Refund will be processed within 3-5 days.
@default
Order #{order.id} status: {order.status}
@end

Best regards,
Your Shop';
```

### Product Category Icons

```php
$template = '@for(products as product)
@match(product.category)
@case("electronics")
📱 {product.name}
@case("books")
📚 {product.name}
@case("clothing")
👔 {product.name}
@case("food")
🍕 {product.name}
@default
📦 {product.name}
@end
@end';
```

### Priority Level Badge

```php
$template = '@match(ticket.priority)
@case(1, 2)
<span class="priority-critical">URGENT</span>
@case(3, 4)
<span class="priority-high">HIGH</span>
@case(5, 6)
<span class="priority-medium">MEDIUM</span>
@default
<span class="priority-low">LOW</span>
@end';
```

### Content Type Rendering

```php
$template = '@for(items as item)
@match(item.type)
@case("image")
<img src="{item.url}" alt="{item.title}">
@case("video")
<video src="{item.url}" controls></video>
@case("link")
<a href="{item.url}">{item.title}</a>
@default
<p>{item.title}</p>
@end
@end';
```

## Matching Behavior

### Loose Comparison

Tsuku uses PHP's loose comparison (`==`), so:

```php
$template = '@match(value)
@case(1)
Matched number 1
@case("1")
Matched string "1"
@end';

$data = ['value' => 1];
// Output: Matched number 1
// (Stops at first match, even though "1" == 1 in PHP)
```

### First Match Wins

Matching stops at the first successful case:

```php
$template = '@match(value)
@case(1)
First
@case(1)
Second (never reached)
@end';

$data = ['value' => 1];
// Output: First
```

## When to Use @match

**✅ Use @match when:**
- Checking the same variable against multiple values
- Status/type/category switching
- Multiple distinct outcomes for different values
- You want clean, readable code

**❌ Use @if when:**
- Checking different variables
- Complex boolean conditions
- Range checks combined with other logic

## Performance

@match is as fast as @if/@else - both compile to the same efficient AST-based code. Choose based on readability, not performance.

## Next Steps

- [Built-in Functions →](/guide/built-in-functions) - Use functions in match cases
- [Loops →](/guide/loops) - Combine match with loops
- [Examples →](/examples/ecommerce) - See match in action
