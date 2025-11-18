# CSV Exports

Generate CSV files from your data with proper escaping and formatting.

## Line Continuation for Readability

Use backslash `\` to continue long CSV lines across multiple lines in your template. The backslash and newline will be removed from the output:

```php
$template = 'SKU,Name,Price,Stock,Status
@for(products as product)
@csv(product.sku),\
@csv(product.name),\
@number(product.price, 2),\
{product.stock},\
{product.status}
@end';
```

**Benefits:**
- ✅ Readable templates without horizontal scrolling
- ✅ Easy to see each field on its own line
- ✅ Clean CSV output (no extra newlines)

::: tip
Line continuation works everywhere in Tsuku, not just in CSV templates!
:::

## Basic CSV Export

### Product Catalog

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

$template = 'SKU,Name,Description,Price,Stock
@for(products as product)
@csv(product.sku),@csv(product.name),@csv(product.description),@number(product.price, 2),{product.stock}
@end';

$data = [
    'products' => [
        [
            'sku' => 'WID-001',
            'name' => 'Premium Widget',
            'description' => 'High-quality widget with advanced features',
            'price' => 29.99,
            'stock' => 150
        ],
        [
            'sku' => 'GAD-002',
            'name' => 'Smart Gadget',
            'description' => 'Innovative gadget for modern users',
            'price' => 49.99,
            'stock' => 75
        ],
    ]
];

$csv = $tsuku->process($template, $data);
file_put_contents('products.csv', $csv);
```

**Output (products.csv):**
```csv
SKU,Name,Description,Price,Stock
WID-001,Premium Widget,High-quality widget with advanced features,29.99,150
GAD-002,Smart Gadget,Innovative gadget for modern users,49.99,75
```

## Handling Special Characters

### Commas in Data

CSV escaping automatically handles commas:

```php
$template = 'Name,Address,City
@for(customers as customer)
@csv(customer.name),@csv(customer.address),@csv(customer.city)
@end';

$data = [
    'customers' => [
        ['name' => 'John Doe', 'address' => '123 Main St, Apt 4B', 'city' => 'New York'],
        ['name' => 'Jane Smith', 'address' => '456 Oak Ave', 'city' => 'Los Angeles'],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```csv
Name,Address,City
John Doe,"123 Main St, Apt 4B",New York
Jane Smith,456 Oak Ave,Los Angeles
```

### Quotes in Data

```php
$template = 'Product,Tagline
@for(products as product)
@csv(product.name),@csv(product.tagline)
@end';

$data = [
    'products' => [
        ['name' => 'Widget Pro', 'tagline' => 'The "Best" Widget Ever'],
        ['name' => 'Gadget Max', 'tagline' => 'Innovation at its finest'],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```csv
Product,Tagline
Widget Pro,"The ""Best"" Widget Ever"
Gadget Max,Innovation at its finest
```

### Newlines in Data

```php
$template = 'Title,Description
@for(items as item)
@csv(item.title),@csv(item.description)
@end';

$data = [
    'items' => [
        [
            'title' => 'Product A',
            'description' => "Line 1\nLine 2\nLine 3"
        ],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```csv
Title,Description
Product A,"Line 1
Line 2
Line 3"
```

## Customer Export

### Complete Customer List

```php
$template = 'ID,Name,Email,Phone,Status,Created
@for(customers as customer)
{customer.id},@csv(customer.name),@csv(customer.email),@csv(customer.phone),{customer.status},@date("Y-m-d", customer.created_at)
@end';

$data = [
    'customers' => [
        [
            'id' => 1001,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '(555) 123-4567',
            'status' => 'active',
            'created_at' => strtotime('2024-01-15')
        ],
        [
            'id' => 1002,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '(555) 987-6543',
            'status' => 'pending',
            'created_at' => strtotime('2024-02-20')
        ],
    ]
];

$csv = $tsuku->process($template, $data);
file_put_contents('customers.csv', $csv);
```

**Output:**
```csv
ID,Name,Email,Phone,Status,Created
1001,John Doe,john@example.com,(555) 123-4567,active,2024-01-15
1002,Jane Smith,jane@example.com,(555) 987-6543,pending,2024-02-20
```

## Order Export

### Order Details with Line Items

```php
$template = 'Order ID,Customer,Item,Quantity,Price,Total
@for(orders as order)
@for(order.items as item, index)
@if(index == 0){order.id},@csv(order.customer),@else,,@end@csv(item.name),{item.qty},@number(item.price, 2),@number(item.qty * item.price, 2)
@end
@end';

$data = [
    'orders' => [
        [
            'id' => 'ORD-001',
            'customer' => 'John Doe',
            'items' => [
                ['name' => 'Widget', 'qty' => 2, 'price' => 29.99],
                ['name' => 'Gadget', 'qty' => 1, 'price' => 49.99],
            ]
        ],
        [
            'id' => 'ORD-002',
            'customer' => 'Jane Smith',
            'items' => [
                ['name' => 'Tool', 'qty' => 3, 'price' => 19.99],
            ]
        ],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```csv
Order ID,Customer,Item,Quantity,Price,Total
ORD-001,John Doe,Widget,2,29.99,59.98
,,Gadget,1,49.99,49.99
ORD-002,Jane Smith,Tool,3,19.99,59.97
```

## Inventory Report

### Stock Levels with Status

```php
$template = 'SKU,Product,Current Stock,Reorder Level,Status
@for(inventory as item)
@csv(item.sku),@csv(item.product),{item.stock},{item.reorder_level},@match(item.stock)
@case(0)Out of Stock
@default@if(item.stock <= item.reorder_level)Low Stock@else In Stock@end
@end
@end';

$data = [
    'inventory' => [
        ['sku' => 'WID-001', 'product' => 'Widget', 'stock' => 150, 'reorder_level' => 50],
        ['sku' => 'GAD-002', 'product' => 'Gadget', 'stock' => 25, 'reorder_level' => 30],
        ['sku' => 'TOO-003', 'product' => 'Tool', 'stock' => 0, 'reorder_level' => 20],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```csv
SKU,Product,Current Stock,Reorder Level,Status
WID-001,Widget,150,50,In Stock
GAD-002,Gadget,25,30,Low Stock
TOO-003,Tool,0,20,Out of Stock
```

## Financial Reports

### Sales Report with Totals

```php
$template = 'Date,Order Count,Gross Sales,Discounts,Net Sales
@for(reports as report)
@date("Y-m-d", report.date),{report.order_count},@number(report.gross, 2),@number(report.discounts, 2),@number(report.net, 2)
@end
Total,{total.orders},@number(total.gross, 2),@number(total.discounts, 2),@number(total.net, 2)';

$reports = [
    ['date' => strtotime('2024-01-01'), 'order_count' => 45, 'gross' => 4500.00, 'discounts' => 450.00, 'net' => 4050.00],
    ['date' => strtotime('2024-01-02'), 'order_count' => 52, 'gross' => 5200.00, 'discounts' => 520.00, 'net' => 4680.00],
    ['date' => strtotime('2024-01-03'), 'order_count' => 38, 'gross' => 3800.00, 'discounts' => 380.00, 'net' => 3420.00],
];

$data = [
    'reports' => $reports,
    'total' => [
        'orders' => array_sum(array_column($reports, 'order_count')),
        'gross' => array_sum(array_column($reports, 'gross')),
        'discounts' => array_sum(array_column($reports, 'discounts')),
        'net' => array_sum(array_column($reports, 'net')),
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```csv
Date,Order Count,Gross Sales,Discounts,Net Sales
2024-01-01,45,4,500.00,450.00,4,050.00
2024-01-02,52,5,200.00,520.00,4,680.00
2024-01-03,38,3,800.00,380.00,3,420.00
Total,135,13,500.00,1,370.00,12,150.00
```

## Advanced Patterns

### Conditional Columns

```php
$template = 'SKU,Name,Price@if(include_cost),Cost,Margin@end
@for(products as product)
@csv(product.sku),@csv(product.name),@number(product.price, 2)@if(include_cost),@number(product.cost, 2),@number(product.price - product.cost, 2)@end
@end';

// With cost
$data = [
    'include_cost' => true,
    'products' => [
        ['sku' => 'A', 'name' => 'Product A', 'price' => 100, 'cost' => 60],
    ]
];

// Without cost
$data = [
    'include_cost' => false,
    'products' => [
        ['sku' => 'A', 'name' => 'Product A', 'price' => 100, 'cost' => 60],
    ]
];
```

### Custom Delimiters (TSV)

```php
// Tab-separated values
$template = 'Name	Email	Status
@for(users as user)
{user.name}	{user.email}	{user.status}
@end';
```

### European CSV Format

```php
// Semicolon separator with comma as decimal point
$tsuku->registerFunction('eu_number', function(float $number): string {
    return str_replace('.', ',', number_format($number, 2, '.', ''));
});

$template = 'Product;Price;Stock
@for(products as product)
@csv(product.name);@eu_number(product.price);{product.stock}
@end';
```

## Magento 2 Integration

### Product Export from Magento

```php
use Qoliber\Tsuku\Tsuku;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class ProductCsvExporter
{
    public function __construct(
        private readonly Tsuku $tsuku,
        private readonly CollectionFactory $productCollectionFactory
    ) {}

    public function export(): string
    {
        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect(['sku', 'name', 'price', 'qty'])
            ->addFieldToFilter('status', 1);

        $products = [];
        foreach ($collection as $product) {
            $products[] = [
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'qty' => $product->getExtensionAttributes()->getStockItem()->getQty(),
            ];
        }

        $template = 'SKU,Name,Price,Quantity
@for(products as product)
@csv(product.sku),@csv(product.name),@number(product.price, 2),@number(product.qty, 0, "", "")
@end';

        return $this->tsuku->process($template, ['products' => $products]);
    }
}
```

## Download CSV in Controller

### HTTP Response

```php
public function downloadAction(): void
{
    $csv = $this->tsuku->process($template, $data);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="export-' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
    echo $csv;
    exit;
}
```

## Best Practices

### Always Use @csv() for Text Fields

```php
// ✅ Good - Escaped
$template = '@csv(product.name),@csv(product.description)';

// ❌ Bad - Not escaped
$template = '{product.name},{product.description}';
```

### Use @number() for Numeric Fields

```php
// ✅ Good - Formatted
$template = '@number(product.price, 2)';

// ❌ Bad - Raw number
$template = '{product.price}';
```

### Include UTF-8 BOM for Excel

```php
// Add BOM for proper Excel Unicode support
$csv = "\xEF\xBB\xBF" . $tsuku->process($template, $data);
```

## Next Steps

- [XML Generation →](/examples/xml-generation) - Learn XML output
- [Security →](/guide/security) - CSV injection prevention
- [Built-in Functions →](/guide/built-in-functions) - All escaping functions
