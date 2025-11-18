# Line Continuation

Split long template lines across multiple lines for better readability using backslash `\`.

## Syntax

End any line with a backslash `\` to continue it on the next line. The backslash and newline character will be removed from the output:

```php
$template = 'First line\
Second line\
Third line';

// Output: First lineSecond lineThird line
```

## Use Cases

### CSV Templates

Perfect for CSV files with many columns:

```php
$template = 'Name,SKU,Price,Stock,Category,Status
@for(products as product)
@csv(product.name),\
@csv(product.sku),\
@number(product.price, 2),\
{product.stock},\
@csv(product.category),\
{product.status}
@end';
```

**Benefits:**
- Each field on its own line
- Easy to read and maintain
- No horizontal scrolling

### Long Function Chains

```php
$template = '@upper(\
@trim(\
@default(user.name, "Guest")\
)\
)';

// Output: JOHN (or GUEST if name is empty)
```

### Complex Expressions

```php
$template = 'User: {user.name},\
Email: {user.email},\
Role: {user.role},\
Status: {user.status}';

// Output: User: John,Email: john@example.com,Role: admin,Status: active
```

## How It Works

Line continuation is processed at the lexer level before tokenization:

1. Template is loaded
2. All `\` followed by newline (`\n`, `\r\n`, or `\r`) are removed
3. Template is tokenized and processed normally

This means it works **everywhere** in templates:
- ✅ Inside loops
- ✅ Inside conditionals
- ✅ With functions
- ✅ With variables
- ✅ In plain text

## Escaping Backslashes

Regular backslashes (not followed by newlines) are preserved:

```php
$template = 'Windows path: C:\Program Files\App';
// Output: Windows path: C:\Program Files\App

$template = 'UNC path: \\server\share\file';
// Output: UNC path: \\server\share\file
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
@number(order.price, 2),\
@number(order.total, 2),\
{order.status}
@end';
```

### JSON with Line Breaks

```php
$template = '{
  "name": @json(user.name),\
  "email": @json(user.email),\
  "role": @json(user.role)
}';
```

### Long Conditionals

```php
$template = '@if(\
  stock > 0 && \
  status == "active" && \
  price < 100\
)
  Available
@end';
```

## Best Practices

### ✅ DO: Use for readability

```php
// Good - each field visible
$template = '@csv(name),\
@csv(email),\
@csv(phone)';
```

### ✅ DO: Align for clarity

```php
$template = 'Name:   {user.name},\
Email:  {user.email},\
Phone:  {user.phone}';
```

### ❌ DON'T: Overuse for simple cases

```php
// Bad - unnecessary for short lines
$template = 'Hello,\
World';

// Good
$template = 'Hello, World';
```

### ❌ DON'T: Use inside strings

```php
// Won't work as expected
$template = '@csv("This is \
a string")';  // Backslash removed!

// Use instead
$template = '@csv("This is a string")';
```

## Performance

Line continuation is preprocessed once during lexer initialization. There is **zero runtime overhead** - the processed template is used for all subsequent rendering.

## Related

- [CSV Exports →](/examples/csv-exports) - Practical CSV examples
- [Variables →](/guide/variables) - Variable interpolation
- [Functions →](/guide/built-in-functions) - Built-in functions
