# Pattern Matching

The `@match` directive provides clean pattern matching for multiple condition checks - similar to PHP 8's match expression. It's cleaner and more readable than long `@if` / `@elseif` chains.

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

Lines that hold only `@match`, `@case`, `@default` or `@end` print nothing, so the output contains just the content line of the matching case.

## Syntax Flexibility

Tsuku's `@match` syntax is **flexible** - you can write it multiline for clarity or compact for short values. The one thing to keep in mind: content written on the same line as a `@case(...)` or `@default` starts **directly after it**, and spaces you put there are part of the output.

**Multiline (indent freely):**
```php
$template = '@match(status)
    @case("active")
        ✓ Active
    @case("pending")
        ⏳ Pending
    @default
        ❌ Unknown
@end';

$data = ['status' => 'pending'];
// Output:         ⏳ Pending
```

In a block that stands on its own lines, the content lines are printed exactly as written, including their indentation (here eight spaces). Indent the directive lines, not the content, if the output must start at column one - or use the compact form:

**Compact (one line per case):**
```php
$template = '@match(status)
    @case("active")✓ Active
    @case("pending")⏳ Pending
    @case("suspended")⚠ Suspended
    @default❌ Unknown
@end';

$data = ['status' => 'pending'];
// Output: ⏳ Pending
```

The indentation in front of `@case` and `@default` is dropped. Writing `@case("pending") ⏳ Pending` with a space instead prints ` ⏳ Pending` with a leading space.

**Inline (inside a line of text):**
```php
$template = '@for(users as user)
{user.name}: @match(user.status)@case("active")✓@case("inactive")✗@end
@end';

$data = [
    'users' => [
        ['name' => 'Alice', 'status' => 'active'],
        ['name' => 'Bob', 'status' => 'inactive'],
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// Alice: ✓
// Bob: ✗
```

An inline `@match` can also be spread over several lines - when it starts after content, each case body continues that line:

```php
$template = '@for(users as user)
{user.name}: @match(user.status)
    @case("active")
        ✓
    @case("inactive")
        ✗
@end
@end';

$data = [
    'users' => [
        ['name' => 'Alice', 'status' => 'active'],
        ['name' => 'Bob', 'status' => 'inactive'],
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// Alice: ✓
// Bob: ✗
```

Each row stays on its own line (in 1.3.0 the rows of such templates were merged or picked up the indentation of the case lines). The full rules are in [Whitespace Control](/guide/whitespace-control); they apply to `@if`, `@for` and all other directives as well.

## Why @match Over @if?

**Without @match (verbose):**
```php
$template = '@if(status == "active")
✓ Active
@elseif(status == "pending")
⏳ Pending
@elseif(status == "suspended")
⚠ Suspended
@else
❌ Unknown
@end';

$data = ['status' => 'pending'];
// Output: ⏳ Pending
```

**With @match (clean):**
```php
$template = '@match(status)
@case("active")✓ Active
@case("pending")⏳ Pending
@case("suspended")⚠ Suspended
@default❌ Unknown
@end';

$data = ['status' => 'pending'];
// Output: ⏳ Pending
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
@case(200)OK
@case(404)Not Found
@case(500)Server Error
@default
Unknown Status
@end';

$data = ['code' => 404];

$result = $tsuku->process($template, $data);
// Output: Not Found
```

Because the comparison is loose, a numeric string such as `'404'` from a database row matches `@case(404)` too.

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

`@default` must be the last branch. Only whitespace may appear between `@match(...)` and the first `@case` - any other text there throws a `ParseException` ("Expected @case or @default inside @match").

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

Case values can be variable paths too - the case matches when both values are equal:

```php
$template = '@match(order.status)
@case(config.final_status)
Done
@default
Open
@end';

$data = [
    'order' => ['status' => 'closed'],
    'config' => ['final_status' => 'closed'],
];
// Output: Done
```

## @match Takes a Value, Not an Expression

`@match(...)` and `@case(...)` accept a quoted string, a number or a variable path - nothing else. Comparisons, `and`/`or` and function calls are **not** evaluated there: `@match(qty > 0)` looks up a variable literally named `qty > 0`, which does not exist, so the match value is `null`:

```php
$template = '@match(qty > 0)
@case(1)
In stock
@default
Out of stock
@end';

$data = ['qty' => 5];
// Output: Out of stock
```

The same happens with `@match(@lower(status))`. For conditions use [`@if` / `@elseif`](/guide/conditionals), or compute the value in PHP and match on that:

```php
$template = '@if(qty > 10)
In stock
@elseif(qty > 0)
Low stock
@else
Out of stock
@end';

$data = ['qty' => 5];
// Output: Low stock
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

$data = ['user' => ['type' => 'customer', 'tier' => 'premium']];

$result = $tsuku->process($template, $data);
// Output:
// Customer Portal
//   - VIP Support
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

$data = [
    'customer' => ['name' => 'Ann'],
    'order' => ['id' => 1042, 'status' => 'shipped', 'tracking' => '1Z999AA10123456784'],
];
```

**Output:**

```
Dear Ann,

Your order #1042 is on its way!
Tracking: 1Z999AA10123456784

Best regards,
Your Shop
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

$data = [
    'products' => [
        ['name' => 'Phone', 'category' => 'electronics'],
        ['name' => 'Novel', 'category' => 'books'],
        ['name' => 'Desk', 'category' => 'furniture'],
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// 📱 Phone
// 📚 Novel
// 📦 Desk
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

$data = ['ticket' => ['priority' => 4]];
// Output: <span class="priority-high">HIGH</span>
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

$data = [
    'items' => [
        ['type' => 'image', 'url' => '/img/logo.png', 'title' => 'Logo'],
        ['type' => 'link', 'url' => '/about', 'title' => 'About us'],
        ['type' => 'quote', 'title' => 'Stay curious'],
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// <img src="/img/logo.png" alt="Logo">
// <a href="/about">About us</a>
// <p>Stay curious</p>
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

Loose comparison also means that a **missing** variable (`null`) matches `@case(0)` and `@case("")`, because `null == 0` and `null == ""` are true in PHP.

### Don't Match Booleans

There are no `true` / `false` literals - inside `@case()` the word `true` is a variable path, normally missing and therefore `null`. Combined with loose comparison, a case such as `@case(1, true)` matches `true`, `false` **and** `0` alike:

```php
$template = '@match(flag)
@case(1, true)
yes
@default
no
@end';

$data = ['flag' => false];
// Output: yes
```

For booleans use a condition instead - `@if(flag)...@else...@end` or the inline form `@?{flag "yes" : "no"}` (see [Conditionals](/guide/conditionals#inline-conditions)):

```php
$template = '"debug": @?{flag "true" : "false"}';

$data = ['flag' => false];
// Output: "debug": false
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
- Complex boolean conditions (`and`, `or`, `in`)
- Range checks (`qty > 10`) combined with other logic
- Boolean flags

## Performance

`@match` and `@if` are both evaluated directly from the parsed template tree; choose based on readability, not performance.

## Next Steps

- [Conditionals →](/guide/conditionals) - `@if`, `@elseif`, `and` / `or` / `in`
- [Whitespace Control →](/guide/whitespace-control) - How `@match` blocks fit into lines and rows
- [Built-in Functions →](/guide/built-in-functions) - Use functions in match cases
- [Loops →](/guide/loops) - Combine match with loops
- [Examples →](/examples/ecommerce) - See match in action
