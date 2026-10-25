# Examples

Real-world examples showing how to use Tsuku for common data transformation tasks.

## Overview

Tsuku excels at transforming structured data (arrays, objects) into any text-based format. These examples demonstrate practical use cases you'll encounter in production applications.

## Available Examples

### [CSV Exports →](/examples/csv-exports)

Learn how to generate CSV files from your data:
- Product catalogs with proper escaping
- Order exports with line totals
- Customer lists
- Inventory reports with conditionals
- Sales reports with totals
- **[See complete examples in examples/07-csv-export.php](https://github.com/qoliber/tsuku/blob/main/examples/07-csv-export.php)**

### [XML Generation →](/examples/xml-generation)

Create XML feeds and documents:
- Product feeds (Google Shopping, Facebook)
- RSS/Atom feeds
- XML Sitemaps
- SOAP web services
- API responses
- **[See complete examples in examples/08-xml-export.php](https://github.com/qoliber/tsuku/blob/main/examples/08-xml-export.php)**

### [YAML Configuration →](/examples/configuration-files)

Generate YAML configuration files:
- Application configuration
- Docker Compose files
- Kubernetes deployments
- GitHub Actions workflows
- OpenAPI specifications
- **[See complete examples in examples/09-yaml-export.php](https://github.com/qoliber/tsuku/blob/main/examples/09-yaml-export.php)**

### [HTML Generation →](/examples/html-generation)

Build HTML from complex objects:
- Product cards with ratings
- User dashboards
- Blog article lists
- Checkout forms
- Complex nested components
- **[See complete examples in examples/10-html-generation.php](https://github.com/qoliber/tsuku/blob/main/examples/10-html-generation.php)**

### [JSON APIs →](/examples/json-apis)

Build JSON responses for APIs:
- REST API responses
- GraphQL-like structures
- JSON-LD structured data
- Webhook payloads
- Error responses

### [E-commerce →](/examples/ecommerce)

E-commerce specific scenarios:
- Invoice generation
- Order confirmations
- Product feeds
- Email templates
- Shipping labels

## Quick Examples

### Simple CSV Export

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

$template = 'Name,Email,Status
@for(users as user)
@csv(user.name),@csv(user.email),{user.status}
@end';

$data = [
    'users' => [
        ['name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active'],
        ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'status' => 'pending'],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```csv
Name,Email,Status
John Doe,john@example.com,active
Jane Smith,jane@example.com,pending
```

### XML Product Feed

```php
$template = '<?xml version="1.0" encoding="UTF-8"?>
<products>
@for(products as product)
    <product id="{product.id}">
        <name>@xml(product.name)</name>
        <price>@number(product.price, 2)</price>
        <stock>@match(product.stock)
            @case(0)
                out_of_stock
            @default
                in_stock
        @end</stock>
    </product>
@end
</products>';

$data = [
    'products' => [
        ['id' => 1, 'name' => 'Widget', 'price' => 29.99, 'stock' => 10],
        ['id' => 2, 'name' => 'Gadget', 'price' => 49.99, 'stock' => 0],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<products>
    <product id="1">
        <name>Widget</name>
        <price>29.99</price>
        <stock>in_stock</stock>
    </product>
    <product id="2">
        <name>Gadget</name>
        <price>49.99</price>
        <stock>out_of_stock</stock>
    </product>
</products>
```

Content comes before `@match` on its line (`<stock>`) and after its `@end` (`</stock>`), so the block is *inline*: the line breaks and indentation around each case body are dropped and the value renders as `<stock>in_stock</stock>`. Lines holding only directives (`@for`, `@case`, `@end`, ...) print nothing. See [Whitespace Control](/guide/whitespace-control).

### JSON API Response

```php
$template = '{
    "success": true,
    "data": {
        "id": {user.id},
        "name": @json(user.name),
        "email": @json(user.email),
        "role": @json(user.role)
    }
}';

$data = [
    'user' => [
        'id' => 123,
        'name' => 'John "The Developer" Doe',
        'email' => 'john@example.com',
        'role' => 'admin'
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```json
{
    "success": true,
    "data": {
        "id": 123,
        "name": "John \u0022The Developer\u0022 Doe",
        "email": "john@example.com",
        "role": "admin"
    }
}
```

`@json()` prints a complete JSON string, quotes included, so it is not wrapped in `"..."` in the template. Quotes, `<`, `>`, `&` and `'` inside the value are written as `\u0022`, `\u003C`, `\u003E`, `\u0026` and `\u0027` - valid JSON that decodes to the original text and is safe to embed in HTML.

## Common Patterns

### Conditional Output

Directive lines can be indented like the content around them; they print nothing, not even their indentation:

```php
$template = '@for(orders as order)
Order #{order.id}
    @if(order.shipped)
    Tracking: {order.tracking_number}
    @else
    Status: Processing
    @end
@end';

$data = [
    'orders' => [
        ['id' => 1001, 'shipped' => true, 'tracking_number' => '1Z999AA10123456784'],
        ['id' => 1002, 'shipped' => false],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```text
Order #1001
    Tracking: 1Z999AA10123456784
Order #1002
    Status: Processing
```

### Nested Data

```php
$template = '@for(categories as category)
Category: {category.name}
@for(category.products as product)
  - {product.name}: ${product.price}
@end

@end';

$data = [
    'categories' => [
        ['name' => 'Books', 'products' => [
            ['name' => 'PHP Basics', 'price' => '29.99'],
            ['name' => 'Clean Code', 'price' => '39.99'],
        ]],
        ['name' => 'Music', 'products' => [
            ['name' => 'Jazz Classics', 'price' => '14.99'],
        ]],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```text
Category: Books
  - PHP Basics: $29.99
  - Clean Code: $39.99

Category: Music
  - Jazz Classics: $14.99

```

### Custom Formatting

```php
$tsuku->registerFunction('currency', function (float $amount, string $code = 'USD'): string {
    return match ($code) {
        'USD' => '$' . number_format($amount, 2),
        'EUR' => '€' . number_format($amount, 2),
        'GBP' => '£' . number_format($amount, 2),
        default => $code . ' ' . number_format($amount, 2),
    };
});

echo $tsuku->process('Total: @currency(total, "EUR")', ['total' => 1234.5]);
// Output: Total: €1,234.50
```

## Integration Examples

### Magento 2 Integration

```php
use Qoliber\Tsuku\Tsuku;

class ProductExportController
{
    public function __construct(
        private readonly Tsuku $tsuku,
        private readonly \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
    ) {}

    public function execute(): void
    {
        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect(['name', 'sku', 'price']);

        $products = [];
        foreach ($collection as $product) {
            $products[] = [
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
            ];
        }

        $template = 'SKU,Name,Price
@for(products as product)
@csv(product.sku),@csv(product.name),@number(product.price, 2, ".", "")
@end';

        $csv = $this->tsuku->process($template, ['products' => $products]);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="products.csv"');
        echo $csv;
    }
}
```

### Laravel Integration

```php
use Qoliber\Tsuku\Tsuku;

class OrderExportService
{
    public function __construct(private readonly Tsuku $tsuku) {}

    public function exportToXml(Collection $orders): string
    {
        $template = '<?xml version="1.0"?>
<orders>
@for(orders as order)
    <order id="{order.id}">
        <customer>@xml(order.customer_name)</customer>
        <total>@number(order.total, 2, ".", "")</total>
        <status>{order.status}</status>
    </order>
@end
</orders>';

        return $this->tsuku->process($template, [
            'orders' => $orders->toArray()
        ]);
    }
}
```

## Next Steps

Explore detailed examples for specific use cases:
- [CSV Exports →](/examples/csv-exports)
- [XML Generation →](/examples/xml-generation)
- [JSON APIs →](/examples/json-apis)
- [Configuration Files →](/examples/configuration-files)
- [E-commerce →](/examples/ecommerce)
