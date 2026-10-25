# Variables

Variables are the foundation of Tsuku templates. They allow you to output data from your data array into the template.

## Basic Syntax

Use curly braces `{}` to output variables:

```php
$template = 'Hello, {name}!';
$data = ['name' => 'World'];

$result = $tsuku->process($template, $data);
// Output: Hello, World!
```

## Dot Notation

Access nested data using dot notation:

```php
$template = 'Product: {product.name}
Price: ${product.price}
Category: {product.category.name}';

$data = [
    'product' => [
        'name' => 'Widget',
        'price' => 29.99,
        'category' => [
            'name' => 'Electronics',
        ],
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// Product: Widget
// Price: $29.99
// Category: Electronics
```

## Array Index Access

Access array elements by index:

```php
$template = 'First: {items.0}
Second: {items.1}
Third: {items.2}';

$data = [
    'items' => ['Apple', 'Banana', 'Cherry'],
];

$result = $tsuku->process($template, $data);
// Output:
// First: Apple
// Second: Banana
// Third: Cherry
```

## Smart Object/Array Access

Tsuku automatically detects the best way to access your data. For every segment of a path such as `product.name` it tries these **5 strategies** in order and uses the first one that exists:

1. Array key: `$product['name']`
2. Getter method: `$product->getName()`
3. Boolean getter: `$product->isName()`
4. Method with the same name: `$product->name()`
5. Public property: `$product->name`

### Strategy 1: Array Access

```php
$data = [
    'product' => ['name' => 'Widget'],
];

echo $tsuku->process('{product.name}', $data);
// Accesses: $product['name']
// Output: Widget
```

### Strategy 2: Getter Methods

```php
class Product {
    private $name = 'Widget';

    public function getName() {
        return $this->name;
    }
}

$data = ['product' => new Product()];

echo $tsuku->process('{product.name}', $data);
// Calls: $product->getName()
// Output: Widget
```

### Strategy 3: Boolean Getters

```php
class Product {
    public function isAvailable() {
        return true;
    }
}

$data = ['product' => new Product()];

echo $tsuku->process('{product.available}', $data);
// Calls: $product->isAvailable()
// Output: 1
```

### Strategy 4: Direct Method Calls

```php
class Product {
    public function total() {
        return 99.99;
    }
}

$data = ['product' => new Product()];

echo $tsuku->process('{product.total}', $data);
// Calls: $product->total() - write the path without parentheses
// Output: 99.99
```

A path never contains parentheses: `{product.total()}` is not a variable and is printed as literal text.

### Strategy 5: Public Properties

```php
class Product {
    public $name = 'Widget';
}

$data = ['product' => new Product()];

echo $tsuku->process('{product.name}', $data);
// Accesses: $product->name
// Output: Widget
```

Private and protected properties are never read directly - expose them through a getter.

### Keys That Contain Dots

If the data has a key that literally contains the dots, that key wins over the nested lookup:

```php
$data = [
    'a.b' => 'flat key',
    'a' => ['b' => 'nested'],
];

echo $tsuku->process('{a.b}', $data);
// Output: flat key
```

## Deep Nesting

Access deeply nested data:

```php
$template = '{order.customer.address.city}';

$data = [
    'order' => [
        'customer' => [
            'address' => [
                'city' => 'New York',
            ],
        ],
    ],
];

$result = $tsuku->process($template, $data);
// Output: New York
```

## Missing Variables

By default, missing variables output as empty strings. A value of `null` counts as missing:

```php
$template = 'Name: {name}
Missing: [{missing}]
Phone: [{phone}]';

$data = ['name' => 'John', 'phone' => null];

$result = $tsuku->process($template, $data);
// Output:
// Name: John
// Missing: []
// Phone: []
```

That is the default `SILENT` [strictness mode](/guide/strictness). In `WARNING` mode the missing variable still renders empty but is reported by `getWarnings()`; in `STRICT` mode it throws:

```php
use Qoliber\Tsuku\Tsuku;
use Qoliber\Tsuku\StrictnessMode;

$tsuku = new Tsuku(StrictnessMode::STRICT);

$tsuku->process('Hello {missing}', []);
// Throws: TsukuException: Variable 'missing' not found in data
```

### Using @default() Function

Provide fallback values for missing data:

```php
$template = 'Name: @default(name, "Guest")
Stock: @default(stock, "0")';

$data = [];

$result = $tsuku->process($template, $data);
// Output:
// Name: Guest
// Stock: 0
```

## Working with Objects

Tsuku handles objects intelligently:

```php
class Product {
    private $name = 'Widget';
    private $price = 99.99;
    private $inStock = true;

    public function getName() { return $this->name; }
    public function getPrice() { return $this->price; }
    public function isInStock() { return $this->inStock; }
    public function getFormattedPrice() {
        return '$' . number_format($this->price, 2);
    }
}

$template = 'Product: {product.name}
Price: {product.formattedPrice}
In Stock: {product.inStock}';

$data = ['product' => new Product()];

$result = $tsuku->process($template, $data);
// Output:
// Product: Widget
// Price: $99.99
// In Stock: 1
```

## Arrays, Booleans and Numbers

A variable prints its value as a string, the way PHP casts it:

- numbers print as PHP prints them: `29.90` becomes `29.9` - use [`@number(price, 2)`](/guide/built-in-functions#number) for fixed decimals
- `true` prints `1`, `false` prints nothing
- an array prints `Array` (and PHP raises an "Array to string conversion" warning) - use `@join()` or a loop

```php
$template = 'Tags: @join(tags, ", ")
Active: @?{active "yes" : "no"}
Deleted: @?{deleted "yes" : "no"}';

$data = ['tags' => ['sale', 'new'], 'active' => true, 'deleted' => false];

echo $tsuku->process($template, $data);
// Output:
// Tags: sale, new
// Active: yes
// Deleted: no
```

## Special Characters

Variables can contain data with special characters:

```php
$template = 'Description: {product.description}';

$data = [
    'product' => [
        'description' => 'A "premium" product & more',
    ],
];

$result = $tsuku->process($template, $data);
// Output: Description: A "premium" product & more
```

::: warning
Variables are printed exactly as they are - nothing is escaped automatically. For HTML/XML output, always use escaping functions to prevent XSS:
```php
$template = 'Description: @html(product.description)';
$data = ['product' => ['description' => 'A "premium" product & more']];
// Output: Description: A &quot;premium&quot; product &amp; more
```
:::

## Variable Naming Rules

A variable is a path in curly braces:
- Each name starts with a letter or underscore
- It contains only letters, numbers, and underscores
- Path segments are separated by dots; a segment after a dot may also be a number (`{items.0}`)
- No spaces inside the braces

camelCase and snake_case both work.

```
// Valid
{name}
{product_name}
{productName}
{_private}
{item123}
{items.0.name}

// Not a variable - printed literally, no error
{123item}       // Can't start with number
{product-name}  // No hyphens allowed
{product.name!} // No special chars
{ name }        // No spaces
```

Because anything that is not a valid path stays literal text, JSON, CSS and Magento-style <code v-pre>{{widget ...}}</code> directives need no escaping - see [Escapes & Special Characters](/guide/escaping).

## Combining with Literals

Mix variables with literal text:

```php
$template = 'Price: ${product.price} USD
SKU: PROD-{product.id}
URL: https://example.com/products/{product.slug}';

$data = [
    'product' => [
        'price' => 29.99,
        'id' => 12345,
        'slug' => 'premium-widget',
    ],
];

$result = $tsuku->process($template, $data);
// Output:
// Price: $29.99 USD
// SKU: PROD-12345
// URL: https://example.com/products/premium-widget
```

## Performance Tips

1. **Use direct array access when possible** - It's fastest
2. **Avoid deep nesting** - Keep data structures flat where practical
3. **Cache object results** - If calling expensive getter methods repeatedly, consider caching

## Next Steps

- [Loops →](/guide/loops) - Iterate over arrays
- [Conditionals →](/guide/conditionals) - Add logic to templates
- [Built-in Functions →](/guide/built-in-functions) - Transform and format data
