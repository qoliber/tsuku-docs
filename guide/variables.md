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

Tsuku automatically detects the best way to access your data. It tries **5 strategies** in order:

### Strategy 1: Array Access

```php
$data = [
    'product' => ['name' => 'Widget'],
];

$template = '{product.name}';
// Accesses: $product['name']
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

$template = '{product.name}';
// Calls: $product->getName()
```

### Strategy 3: Boolean Getters

```php
class Product {
    public function isAvailable() {
        return true;
    }
}

$data = ['product' => new Product()];

$template = '{product.available}';
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

$template = '{product.total}';
// Calls: $product->total()
```

### Strategy 5: Public Properties

```php
class Product {
    public $name = 'Widget';
}

$data = ['product' => new Product()];

$template = '{product.name}';
// Accesses: $product->name
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

By default, missing variables output as empty strings:

```php
$template = 'Name: {name}
Missing: {missing}
More text';

$data = ['name' => 'John'];

$result = $tsuku->process($template, $data);
// Output:
// Name: John
// Missing:
// More text
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
For HTML/XML output, always use escaping functions to prevent XSS:
```php
$template = 'Description: @html(product.description)';
// Output: Description: A &quot;premium&quot; product &amp; more
```
:::

## Variable Naming Rules

Variable names must:
- Start with a letter or underscore
- Contain only letters, numbers, and underscores
- Use camelCase or snake_case

```php
// Valid
{name}
{product_name}
{productName}
{_private}
{item123}

// Invalid
{123item}     // Can't start with number
{product-name} // No hyphens allowed
{product.name!} // No special chars
```

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
