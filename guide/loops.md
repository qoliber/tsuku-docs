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

Loop over arrays of arrays, arrays of objects, or associative arrays:

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

## What Can Be Looped Over

`@for(collection as item)` takes a variable path - `products`, `order.items`, `category.products` (a getter such as `getProducts()` works too). The value must be a **PHP array**:

- a list or an associative array is iterated; its items can be arrays, objects or scalars
- a missing collection, `null`, a string, a generator, an `ArrayIterator` or a collection object renders nothing - convert it first with `iterator_to_array()` or the object's `toArray()`
- a function call is not allowed as the collection (`@for(@split(tags) as tag)` is a `ParseException`) - prepare the array in your data

```php
$template = '[@for(items as item){item}@end]';

echo $tsuku->process($template, ['items' => new ArrayIterator([1, 2])]);
// Output: []

echo $tsuku->process($template, ['items' => iterator_to_array(new ArrayIterator([1, 2]))]);
// Output: [12]
```

For collections too large to hold in memory, render them row by row with [`processToStream()`](/guide/streaming), which accepts any iterable.

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

The second name receives the **array key**, so a list counts from 0.

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

## Whitespace and Indentation

Lines that hold only directives (`@for(...)`, `@if(...)`, `@end`, ...) produce no output - not their indentation, not their line break. Indent them like the markup around them; content lines keep exactly the indentation you give them:

```php
$template = '<ul>
    @for(categories as category)
    <li>{category.name}
        <ul>
            @for(category.products as product)
            <li>{product.name}</li>
            @end
        </ul>
    </li>
    @end
</ul>';

$data = [
    'categories' => [
        ['name' => 'Electronics', 'products' => [['name' => 'Phone'], ['name' => 'Laptop']]],
        ['name' => 'Books', 'products' => [['name' => 'Novel']]],
    ],
];

echo $tsuku->process($template, $data);
```

**Output:**
```html
<ul>
    <li>Electronics
        <ul>
            <li>Phone</li>
            <li>Laptop</li>
        </ul>
    </li>
    <li>Books
        <ul>
            <li>Novel</li>
        </ul>
    </li>
</ul>
```

A loop can also sit inside a line. The body of a `@for` is never trimmed, so it renders exactly as written:

```php
$template = 'Tags: @for(tags as tag)[{tag}]@end
Done';

echo $tsuku->process($template, ['tags' => ['sale', 'new']]);
// Output:
// Tags: [sale][new]
// Done
```

The full rules are in [Whitespace Control](/guide/whitespace-control).

## Variables Inside a Loop

The loop body sees all your data plus the loop variables. A loop variable with the same name as an outer value hides it inside the loop only:

```php
$template = '@for(items as name)
{name} {greeting}
@end
After: {name}';

$data = ['items' => ['Ann', 'Bob'], 'name' => 'outer', 'greeting' => 'says hi'];

echo $tsuku->process($template, $data);
// Output:
// Ann says hi
// Bob says hi
// After: outer
```

## Performance Considerations

1. **Large datasets**: Tsuku processes templates in a single pass; for exports with hundreds of thousands of rows use [`processToStream()`](/guide/streaming) so only one row is in memory at a time
2. **Deep nesting**: Unlimited nesting depth, but keep reasonable for readability
3. **Object creation**: If looping thousands of objects with getters, consider caching

## Common Patterns

Templates have no arithmetic and no `loop.first` / `loop.last` helpers. The patterns below cover the usual needs with the array key, a little preparation in PHP, or a custom function.

### Numbered List

`{index + 1}` is not supported - there is no arithmetic in templates (it is printed as literal text). Re-key the list so it starts at 1:

```php
$template = '@for(items as item, number)
{number}. {item}
@end';

// Keys 1, 2, 3 - from a plain list: array_combine(range(1, count($list)), $list)
$data = ['items' => [1 => 'Apple', 2 => 'Banana', 3 => 'Cherry']];

echo $tsuku->process($template, $data);
// Output:
// 1. Apple
// 2. Banana
// 3. Cherry
```

Or register a small function and keep the list as it is:

```php
$tsuku->registerFunction('position', fn($index) => $index + 1);

$template = '@for(items as item, index)
@position(index). {item}
@end';

echo $tsuku->process($template, ['items' => ['Apple', 'Banana']]);
// Output:
// 1. Apple
// 2. Banana
```

### First and Last Item

The key of a list tells you the first item:

```php
$template = '@for(items as item, index)
@if(index == 0)
* {item} (first)
@else
- {item}
@end
@end';

echo $tsuku->process($template, ['items' => ['Apple', 'Banana', 'Cherry']]);
// Output:
// * Apple (first)
// - Banana
// - Cherry
```

For the last item, number the list from 1 (as above) and compare with `@length()`:

```php
$template = '@for(items as item, number)
{item}@?{number == @length(items) "." : ","}
@end';

$data = ['items' => [1 => 'Apple', 2 => 'Banana', 3 => 'Cherry']];

echo $tsuku->process($template, $data);
// Output:
// Apple,
// Banana,
// Cherry.
```

### Comma-Separated List

```php
$template = '@join(items, ", ")';
// Or manually:
$template = '@for(items as item, index)@if(index > 0), @end{item}@end';
```

The manual version puts the separator **in front of** every item except the first (`index > 0`), so it never needs to know which item is last.

::: warning Lists only
`index > 0` relies on the keys `0, 1, 2, ...`. With string keys it is true for the first item too (`"sku-1" > 0` is true in PHP 8), so pass `array_values($items)` - or use `@join()`.
:::

### Separators Between JSON Objects

The same leading-separator idea builds valid JSON arrays - the comma is printed before every object except the first:

```php
$template = '{
  "items": [@for(items as item, index)@if(index > 0),@end
    {"name": @json(item)}@end
  ]
}';

echo $tsuku->process($template, ['items' => ['Apple', 'Banana', 'Cherry']]);
```

**Output:**
```json
{
  "items": [
    {"name": "Apple"},
    {"name": "Banana"},
    {"name": "Cherry"}
  ]
}
```

`@json()` returns the complete quoted JSON string, so it is not wrapped in quotes. See [JSON APIs](/examples/json-apis) for larger examples.

### Table Rows

```php
$template = '<table>
@for(products as product)
<tr>
  <td>{product.name}</td>
  <td>$@number(product.price, 2)</td>
</tr>
@end
</table>';

$data = [
    'products' => [
        ['name' => 'Widget', 'price' => 29.99],
        ['name' => 'Gadget', 'price' => 1299.5],
    ],
];

echo $tsuku->process($template, $data);
```

**Output:**
```html
<table>
<tr>
  <td>Widget</td>
  <td>$29.99</td>
</tr>
<tr>
  <td>Gadget</td>
  <td>$1,299.50</td>
</tr>
</table>
```

## Next Steps

- [Conditionals →](/guide/conditionals) - Add logic to loops
- [Pattern Matching →](/guide/pattern-matching) - Match values in loops
- [Built-in Functions →](/guide/built-in-functions) - Transform loop data
