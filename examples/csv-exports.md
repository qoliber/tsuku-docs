# CSV Exports

Generate CSV files from your data with proper escaping and formatting.

## Line Continuation for Readability

Use backslash `\` to continue long CSV lines across multiple lines in your template. The backslash and newline will be removed from the output:

```php
$template = 'SKU,Name,Price,Stock,Status
@for(products as product)
@csv(product.sku),\
@csv(product.name),\
@number(product.price, 2, ".", ""),\
{product.stock},\
{product.status}
@end';

$data = [
    'products' => [
        ['sku' => 'WID-001', 'name' => 'Widget, Large', 'price' => 1299.5, 'stock' => 15, 'status' => 'active'],
        ['sku' => 'GAD-002', 'name' => 'Gadget', 'price' => 49.99, 'stock' => 0, 'status' => 'disabled'],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```csv
SKU,Name,Price,Stock,Status
WID-001,"Widget, Large",1299.50,15,active
GAD-002,Gadget,49.99,0,disabled
```

**Benefits:**
- ✅ Readable templates without horizontal scrolling
- ✅ Easy to see each field on its own line
- ✅ Clean CSV output (no extra newlines)

::: tip
Line continuation works everywhere in Tsuku, not just in CSV templates! Lines that hold only directives (such as the `@for` and `@end` lines above) never print anything, so you only need `\` to split a single output row - see [Whitespace Control](/guide/whitespace-control).
:::

## Basic CSV Export

### Product Catalog

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

$template = 'SKU,Name,Description,Price,Stock
@for(products as product)
@csv(product.sku),@csv(product.name),@csv(product.description),@number(product.price, 2, ".", ""),{product.stock}
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

::: warning Numbers in CSV
`@number(value, 2)` uses a comma as thousands separator (`1299.5` becomes `1,299.50`), which splits the value into two CSV columns. In CSV files pass the separators explicitly: `@number(value, 2, ".", "")` gives `1299.50`.
:::

## Handling Special Characters

Tsuku does not escape anything on its own: a `{variable}` is printed exactly as it is. CSV escaping happens only where you call `@csv(value)` (or `@escape(value, "csv")`). It wraps the value in double quotes, doubling any `"` inside, when the value contains a comma, a double quote or a line break (`\n`); other values are printed unchanged.

### Commas in Data

`@csv()` quotes values that contain commas:

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

Templates have no arithmetic (`{item.qty * item.price}` is not a variable and prints as plain text), so compute values such as line totals in PHP before rendering:

```php
$template = 'Order ID,Customer,Item,Quantity,Price,Total
@for(orders as order)
@for(order.items as item, index)
@if(index == 0){order.id},@csv(order.customer),@else,,@end@csv(item.name),{item.qty},@number(item.price, 2, ".", ""),@number(item.total, 2, ".", "")
@end
@end';

$orders = [
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
];

// Line totals are calculated in PHP
foreach ($orders as &$order) {
    foreach ($order['items'] as &$item) {
        $item['total'] = $item['qty'] * $item['price'];
    }
}
unset($order, $item);

echo $tsuku->process($template, ['orders' => $orders]);
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
@csv(item.sku),@csv(item.product),{item.stock},{item.reorder_level},@if(item.stock == 0)
    Out of Stock
@elseif(item.stock <= item.reorder_level)
    Low Stock
@else
    In Stock
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

The `@if` follows content on its line, so it is an *inline* block: the line break and indentation at the start of each branch are dropped and every item stays on one row. Branches written on one line keep their spaces, so `@else In Stock` would print ` In Stock` with a leading space - write multi-line branches as above, or use an inline condition such as `@?{item.stock > 0 "In Stock" : "Out of Stock"}`. See [Whitespace Control](/guide/whitespace-control).

## Financial Reports

### Sales Report with Totals

```php
$template = 'Date,Order Count,Gross Sales,Discounts,Net Sales
@for(reports as report)
@date("Y-m-d", report.date),{report.order_count},@number(report.gross, 2, ".", ""),@number(report.discounts, 2, ".", ""),@number(report.net, 2, ".", "")
@end
Total,{total.orders},@number(total.gross, 2, ".", ""),@number(total.discounts, 2, ".", ""),@number(total.net, 2, ".", "")';

$reports = [
    ['date' => strtotime('2024-01-01'), 'order_count' => 45, 'gross' => 4500.00, 'discounts' => 450.00, 'net' => 4050.00],
    ['date' => strtotime('2024-01-02'), 'order_count' => 52, 'gross' => 5200.00, 'discounts' => 520.00, 'net' => 4680.00],
    ['date' => strtotime('2024-01-03'), 'order_count' => 38, 'gross' => 3800.00, 'discounts' => 380.00, 'net' => 3420.00],
];

// Totals are calculated in PHP: templates have no arithmetic
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
2024-01-01,45,4500.00,450.00,4050.00
2024-01-02,52,5200.00,520.00,4680.00
2024-01-03,38,3800.00,380.00,3420.00
Total,135,13500.00,1350.00,12150.00
```

## Advanced Patterns

### Conditional Columns

```php
$template = 'SKU,Name,Price@if(include_cost),Cost,Margin@end
@for(products as product)
@csv(product.sku),@csv(product.name),@number(product.price, 2, ".", "")@if(include_cost),@number(product.cost, 2, ".", ""),@number(product.margin, 2, ".", "")@end
@end';

// With cost (the margin is calculated in PHP: templates have no arithmetic)
$data = [
    'include_cost' => true,
    'products' => [
        ['sku' => 'A', 'name' => 'Product A', 'price' => 100, 'cost' => 60, 'margin' => 40],
    ]
];

echo $tsuku->process($template, $data);
// Output:
// SKU,Name,Price,Cost,Margin
// A,Product A,100.00,60.00,40.00

// Without cost
$data = [
    'include_cost' => false,
    'products' => [
        ['sku' => 'A', 'name' => 'Product A', 'price' => 100, 'cost' => 60, 'margin' => 40],
    ]
];

echo $tsuku->process($template, $data);
// Output:
// SKU,Name,Price
// A,Product A,100.00
```

An `@end` that follows content on its line keeps the line break after it, so the header and every row end with exactly one line break whether the extra columns are printed or not.

### Custom Delimiters (TSV)

```php
// Tab-separated values
$template = 'Name	Email	Status
@for(users as user)
{user.name}	{user.email}	{user.status}
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
```text
Name	Email	Status
John Doe	john@example.com	active
Jane Smith	jane@example.com	pending
```

`@csv()` does not quote values that contain a tab, so make sure TSV values contain no tabs or line breaks (clean them in PHP, or with `@replace()`).

### European CSV Format

Semicolon-separated files with a comma as decimal point: `@number()` takes the decimal point and the thousands separator as arguments. `@csv()` quotes only values containing a comma, a double quote or a line break - not a `;` - so register a small quoting function for the semicolon format:

```php
$tsuku->registerFunction('ssv', function (mixed $value): string {
    $value = (string) $value;
    return preg_match('/[;"\r\n]/', $value) === 1 ? '"' . str_replace('"', '""', $value) . '"' : $value;
});

$template = 'Product;Price;Stock
@for(products as product)
@ssv(product.name);@number(product.price, 2, ",", "");{product.stock}
@end';

$data = [
    'products' => [
        ['name' => 'Widget; Large', 'price' => 1234.5, 'stock' => 3],
        ['name' => 'Gadget', 'price' => 49.99, 'stock' => 12],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```csv
Product;Price;Stock
"Widget; Large";1234,50;3
Gadget;49,99;12
```

### Spreadsheet Formula Injection

`@csv()` escapes the CSV format only. A value starting with `=`, `+`, `-` or `@` can still be run as a formula when the file is opened in a spreadsheet. If the file goes to Excel or Google Sheets and the data comes from users, register a function that neutralises such values:

```php
$tsuku->registerFunction('csv_safe', function (mixed $value): string {
    $value = (string) $value;
    if ($value !== '' && str_contains('=+-@', $value[0])) {
        $value = "'" . $value; // a leading apostrophe makes spreadsheets show the text as-is
    }
    return str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")
        ? '"' . str_replace('"', '""', $value) . '"'
        : $value;
});

$template = 'Name,Comment
@for(rows as row)
@csv_safe(row.name),@csv_safe(row.comment)
@end';

$data = [
    'rows' => [
        ['name' => 'Ann', 'comment' => '=HYPERLINK("http://evil.example","Click")'],
        ['name' => '@admin', 'comment' => 'Fine, thanks'],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```csv
Name,Comment
Ann,"'=HYPERLINK(""http://evil.example"",""Click"")"
'@admin,"Fine, thanks"
```

### Large Exports

`process()` returns the whole file as one string. For exports with many thousands of rows, `processToStream()` renders the header, each row and the footer separately and hands every piece to a callback (for example `fwrite()`), so memory use stays flat - see [Streaming Large Datasets](/guide/streaming).

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
@csv(product.sku),@csv(product.name),@number(product.price, 2, ".", ""),@number(product.qty, 0, ".", "")
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
$data = ['product' => ['name' => 'Widget, Large', 'description' => 'The "Pro" model']];

// ✅ Good - Escaped
echo $tsuku->process('@csv(product.name),@csv(product.description)', $data);
// Output: "Widget, Large","The ""Pro"" model"

// ❌ Bad - Not escaped: the comma in the name starts a new column
echo $tsuku->process('{product.name},{product.description}', $data);
// Output: Widget, Large,The "Pro" model
```

### Format Numbers Without Thousands Separators

```php
$data = ['product' => ['price' => 1299.5]];

// ✅ Good - two decimals, no thousands separator
echo $tsuku->process('@number(product.price, 2, ".", "")', $data);
// Output: 1299.50

// ❌ Bad - the default thousands separator is a comma, which splits the column
echo $tsuku->process('@number(product.price, 2)', $data);
// Output: 1,299.50

// ⚠️ Raw value - printed as PHP prints the number, without fixed decimals
echo $tsuku->process('{product.price}', $data);
// Output: 1299.5
```

### Include UTF-8 BOM for Excel

```php
// Add BOM for proper Excel Unicode support
$csv = "\xEF\xBB\xBF" . $tsuku->process($template, $data);
```

## Next Steps

- [XML Generation →](/examples/xml-generation) - Learn XML output
- [Security →](/guide/security) - Escaping for every output format
- [Built-in Functions →](/guide/built-in-functions) - All escaping functions
