# Loops

Loops allow you to iterate over arrays and generate repeated content. Tsuku provides the `@for` directive for all looping needs.

## Basic @for Loop

Iterate over an array:

```php
$template = '@for(items as item)
- {item}
@end';

$data = [
    'items' => ['Apple', 'Banana', 'Cherry'],
];

$result = $tsuku->process($template, $data);
// Output:
// - Apple
// - Banana
// - Cherry
```

## Looping Over Objects

Loop over arrays of objects or associative arrays:

```php
$template = '@for(products as product)
Product: {product.name}
Price: ${product.price}
---
@end';

$data = [
    'products' => [
        ['name' => 'Widget', 'price' => 29.99],
        ['name' => 'Gadget', 'price' => 39.99],
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// Product: Widget
// Price: $29.99
// ---
// Product: Gadget
// Price: $39.99
// ---
```

## Loop with Index/Key

Access both the value and the key (value comes first, then key):

```php
$template = '@for(items as item, index)
{index}. {item}
@end';

$data = [
    'items' => ['Apple', 'Banana', 'Cherry'],
];

$result = $tsuku->process($template, $data);
// Output:
// 0. Apple
// 1. Banana
// 2. Cherry
```

### Associative Arrays

With associative arrays, the key is the array key:

```php
$template = '@for(prices as price, sku)
SKU {sku}: ${price}
@end';

$data = [
    'prices' => [
        'WID-001' => 29.99,
        'GAD-002' => 39.99,
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// SKU WID-001: $29.99
// SKU GAD-002: $39.99
```

## Nested Loops

Loop within loops:

```php
$template = '@for(categories as category)
Category: {category.name}
@for(category.products as product)
  - {product.name}: ${product.price}
@end
@end';

$data = [
    'categories' => [
        [
            'name' => 'Electronics',
            'products' => [
                ['name' => 'Phone', 'price' => 699],
                ['name' => 'Laptop', 'price' => 1299],
            ],
        ],
        [
            'name' => 'Books',
            'products' => [
                ['name' => 'Novel', 'price' => 15],
                ['name' => 'Guide', 'price' => 25],
            ],
        ],
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// Category: Electronics
//   - Phone: $699
//   - Laptop: $1299
// Category: Books
//   - Novel: $15
//   - Guide: $25
```

## Loops with Conditionals

Combine loops with conditions:

```php
$template = '@for(products as product)
@if(product.stock > 0)
{product.name}: ✓ In Stock ({product.stock})
@else
{product.name}: ✗ Out of Stock
@end
@end';

$data = [
    'products' => [
        ['name' => 'Widget', 'stock' => 10],
        ['name' => 'Gadget', 'stock' => 0],
        ['name' => 'Tool', 'stock' => 5],
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// Widget: ✓ In Stock (10)
// Gadget: ✗ Out of Stock
// Tool: ✓ In Stock (5)
```

## CSV Generation

Perfect for CSV exports:

```php
$template = 'SKU,Name,Price,Stock
@for(products as product)
@csv(product.sku),@csv(product.name),@number(product.price, 2),{product.stock}
@end';

$data = [
    'products' => [
        ['sku' => 'WID-001', 'name' => 'Widget, Premium', 'price' => 29.99, 'stock' => 100],
        ['sku' => 'GAD-002', 'name' => 'Basic Gadget', 'price' => 19.50, 'stock' => 50],
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// SKU,Name,Price,Stock
// WID-001,"Widget, Premium",29.99,100
// GAD-002,Basic Gadget,19.50,50
```

## XML Generation

Generate XML elements:

```php
$template = '<?xml version="1.0"?>
<products>
@for(products as product)
  <product id="{product.id}">
    <name>@xml(product.name)</name>
    <price>{product.price}</price>
  </product>
@end
</products>';

$data = [
    'products' => [
        ['id' => 1, 'name' => 'Widget & Gadget', 'price' => 29.99],
        ['id' => 2, 'name' => 'Premium Tool', 'price' => 49.99],
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// <?xml version="1.0"?>
// <products>
//   <product id="1">
//     <name>Widget &amp; Gadget</name>
//     <price>29.99</price>
//   </product>
//   <product id="2">
//     <name>Premium Tool</name>
//     <price>49.99</price>
//   </product>
// </products>
```

## Empty Arrays

Empty arrays produce no output:

```php
$template = 'Products:
@for(products as product)
- {product.name}
@end
Done';

$data = ['products' => []];

$result = $tsuku->process($template, $data);
// Output:
// Products:
// Done
```

## Performance Considerations

1. **Large datasets**: Tsuku processes templates in a single pass - very efficient
2. **Deep nesting**: Unlimited nesting depth, but keep reasonable for readability
3. **Object creation**: If looping thousands of objects with getters, consider caching

## Common Patterns

### Numbered List

```php
$template = '@for(items as item, index)
{index + 1}. {item}
@end';
```

### Comma-Separated List

```php
$template = '@join(items, ", ")';
// Or manually:
$template = '@for(items as item, index)@if(index > 0), @end{item}@end';
```

### Table Rows

```php
$template = '<table>
@for(products as product)
<tr>
  <td>{product.name}</td>
  <td>${product.price}</td>
</tr>
@end
</table>';
```

## Next Steps

- [Conditionals →](/guide/conditionals) - Add logic to loops
- [Pattern Matching →](/guide/pattern-matching) - Match values in loops
- [Built-in Functions →](/guide/built-in-functions) - Transform loop data
