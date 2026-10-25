# E-commerce Examples

Real-world e-commerce scenarios: invoices, order confirmations, product feeds, email templates, and shipping labels.

::: tip Prepare totals in PHP
Templates have no arithmetic: `{item.qty * item.price}` is printed as literal text and `@number(item.qty * item.price, 2)` does not multiply. The examples below therefore pass derived values such as `line_total` in the data. Compute them where you build the data, or - if you really need it in the template - register a [custom function](/guide/custom-functions): `$tsuku->registerFunction('multiply', fn($a, $b) => $a * $b)` makes `@number(@multiply(item.qty, item.price), 2)` work.
:::

## Invoice Generation

### Customer Invoice

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

$template = 'INVOICE #@number(invoice.number, 0, "", "")

Bill To:
@html(invoice.customer.name)
@html(invoice.customer.address)
@html(invoice.customer.city), @html(invoice.customer.state) @html(invoice.customer.zip)
Email: @html(invoice.customer.email)

Invoice Date: @date("F j, Y", invoice.date)
Due Date: @date("F j, Y", invoice.due_date)

Items:
SKU          Description                    Qty    Price      Total
@for(invoice.items as item)
@substr(@concat(item.sku, "            "), 0, 12) @substr(@concat(item.name, "                              "), 0, 30) @substr(@concat(item.qty, "      "), 0, 6) $@substr(@concat(@number(item.price, 2), "          "), 0, 10) $@number(item.line_total, 2)
@end

                                              Subtotal: $@number(invoice.subtotal, 2)
                                                   Tax: $@number(invoice.tax, 2)
                                              Shipping: $@number(invoice.shipping, 2)
                                                 Total: $@number(invoice.total, 2)

Payment Status: @if(invoice.paid)
PAID
@else
UNPAID - Due @date("F j, Y", invoice.due_date)
@end

Thank you for your business!';

$data = [
    'invoice' => [
        'number' => 12345,
        'date' => 1705276800,     // 2024-01-15
        'due_date' => 1707868800, // 2024-02-14
        'customer' => [
            'name' => 'Acme Corporation',
            'address' => '123 Business St',
            'city' => 'New York',
            'state' => 'NY',
            'zip' => '10001',
            'email' => 'billing@acme.com'
        ],
        'items' => [
            ['sku' => 'WID-001', 'name' => 'Premium Widget', 'qty' => 10, 'price' => 29.99, 'line_total' => 299.90],
            ['sku' => 'GAD-002', 'name' => 'Smart Gadget', 'qty' => 5, 'price' => 49.99, 'line_total' => 249.95],
            ['sku' => 'TOO-003', 'name' => 'Professional Tool', 'qty' => 3, 'price' => 19.99, 'line_total' => 59.97],
        ],
        'subtotal' => 609.82,
        'tax' => 54.88,
        'shipping' => 15.00,
        'total' => 679.70,
        'paid' => false
    ]
];

$invoice = $tsuku->process($template, $data);
file_put_contents('invoice-12345.txt', $invoice);
```

**Output (invoice-12345.txt):**
```
INVOICE #12345

Bill To:
Acme Corporation
123 Business St
New York, NY 10001
Email: billing@acme.com

Invoice Date: January 15, 2024
Due Date: February 14, 2024

Items:
SKU          Description                    Qty    Price      Total
WID-001      Premium Widget                 10     $29.99      $299.90
GAD-002      Smart Gadget                   5      $49.99      $249.95
TOO-003      Professional Tool              3      $19.99      $59.97

                                              Subtotal: $609.82
                                                   Tax: $54.88
                                              Shipping: $15.00
                                                 Total: $679.70

Payment Status: UNPAID - Due February 14, 2024

Thank you for your business!
```

Columns are padded by appending spaces with `@concat()` and cutting to a fixed width with `@substr()`. `Payment Status: @if(...)` is an inline block: the line break at the start of each branch is dropped, so the status continues the `Payment Status:` line.

## Order Confirmation Email

### HTML Email Template

```php
$template = '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .header { background: #4CAF50; color: white; padding: 20px; }
        .order-details { margin: 20px 0; }
        .items { border-collapse: collapse; width: 100%; }
        .items th, .items td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .items th { background: #f2f2f2; }
        .totals { text-align: right; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order Confirmation</h1>
        <p>Thank you for your order!</p>
    </div>

    <div class="order-details">
        <p><strong>Order Number:</strong> @html(order.number)</p>
        <p><strong>Order Date:</strong> @date("F j, Y", order.date)</p>
        <p><strong>Status:</strong> @match(order.status)
            @case("pending")
                Processing
            @case("shipped")
                Shipped
            @case("delivered")
                Delivered
            @default
                @html(order.status)
        @end</p>
    </div>

    <h2>Order Items</h2>
    <table class="items">
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
        @for(order.items as item)
            <tr>
                <td>@html(item.name)</td>
                <td>@html(item.sku)</td>
                <td>{item.qty}</td>
                <td>$@number(item.price, 2)</td>
                <td>$@number(item.line_total, 2)</td>
            </tr>
        @end
        </tbody>
    </table>

    <div class="totals">
        <p><strong>Subtotal:</strong> $@number(order.subtotal, 2)</p>
        <p><strong>Shipping:</strong> $@number(order.shipping, 2)</p>
        <p><strong>Tax:</strong> $@number(order.tax, 2)</p>
        <p style="font-size: 1.2em;"><strong>Total:</strong> $@number(order.total, 2)</p>
    </div>

    <h2>Shipping Address</h2>
    <p>
        @html(order.shipping_address.name)<br>
        @html(order.shipping_address.street)<br>
        @html(order.shipping_address.city), @html(order.shipping_address.state) @html(order.shipping_address.zip)<br>
        @html(order.shipping_address.country)
    </p>

    @if(order.tracking_number)
    <h2>Tracking Information</h2>
    <p>Your tracking number: <strong>@html(order.tracking_number)</strong></p>
    <p><a href="@html(order.tracking_url)">Track your shipment</a></p>

    @end
    <p>If you have any questions, please contact us at @html(support.email) or call @html(support.phone).</p>

    <p>Thank you for shopping with us!</p>
</body>
</html>';

$data = [
    'order' => [
        'number' => 'ORD-2024-001',
        'date' => 1705276800, // 2024-01-15
        'status' => 'shipped',
        'customer_email' => 'john@example.com',
        'items' => [
            ['name' => 'Wireless Mouse', 'sku' => 'MOU-001', 'qty' => 2, 'price' => 24.99, 'line_total' => 49.98],
            ['name' => 'USB Cable', 'sku' => 'CAB-002', 'qty' => 3, 'price' => 9.99, 'line_total' => 29.97],
        ],
        'subtotal' => 79.95,
        'shipping' => 5.99,
        'tax' => 7.19,
        'total' => 93.13,
        'shipping_address' => [
            'name' => 'John Doe',
            'street' => '456 Oak Avenue',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'zip' => '90001',
            'country' => 'USA'
        ],
        'tracking_number' => '1Z999AA10123456784',
        'tracking_url' => 'https://track.example.com/1Z999AA10123456784'
    ],
    'support' => [
        'email' => 'support@example.com',
        'phone' => '1-800-EXAMPLE'
    ]
];

$email = $tsuku->process($template, $data);
mail($data['order']['customer_email'], 'Order Confirmation', $email, [
    'Content-Type' => 'text/html; charset=UTF-8'
]);
```

**Output (excerpt):**
```html
    <div class="order-details">
        <p><strong>Order Number:</strong> ORD-2024-001</p>
        <p><strong>Order Date:</strong> January 15, 2024</p>
        <p><strong>Status:</strong> Shipped</p>
    </div>

        <tbody>
            <tr>
                <td>Wireless Mouse</td>
                <td>MOU-001</td>
                <td>2</td>
                <td>$24.99</td>
                <td>$49.98</td>
            </tr>
            <tr>
                <td>USB Cable</td>
                <td>CAB-002</td>
                <td>3</td>
                <td>$9.99</td>
                <td>$29.97</td>
            </tr>
        </tbody>
```

The `@match` sits inside the `<p>` line and its `@end` is followed by `</p>`, so each case body is trimmed of the line breaks and indentation around it and the status lands on one line. The indented `@for`, `@if` and `@end` lines produce no output at all.

## Shipping Label

### FedEx/UPS Style Label

```php
$template = 'FROM:
@upper(from.company)
@upper(from.street)
@upper(from.city), @upper(from.state) @upper(from.zip)

TO:
@upper(to.name)
@upper(to.street)
@if(to.apartment)
@upper(to.apartment)
@end
@upper(to.city), @upper(to.state) @upper(to.zip)

TRACKING: @upper(shipment.tracking)
SERVICE: @upper(shipment.service)
WEIGHT: @number(shipment.weight, 2) lbs
PACKAGES: {shipment.package_count}

@match(shipment.service)
@case("overnight")
*** OVERNIGHT DELIVERY ***
@case("2day")
*** 2-DAY DELIVERY ***
@end

Order: @upper(order.number)
Reference: @upper(order.reference)';

$data = [
    'from' => [
        'company' => 'My Store',
        'street' => '123 Warehouse Blvd',
        'city' => 'Dallas',
        'state' => 'TX',
        'zip' => '75201'
    ],
    'to' => [
        'name' => 'John Doe',
        'street' => '456 Oak Ave',
        'apartment' => 'Apt 4B',
        'city' => 'New York',
        'state' => 'NY',
        'zip' => '10001'
    ],
    'shipment' => [
        'tracking' => '1Z999AA10123456784',
        'service' => 'overnight',
        'weight' => 5.5,
        'package_count' => 1
    ],
    'order' => [
        'number' => 'ORD-2024-001',
        'reference' => 'CUST-12345'
    ]
];

$label = $tsuku->process($template, $data);
file_put_contents('shipping-label.txt', $label);
```

**Output (shipping-label.txt):**
```
FROM:
MY STORE
123 WAREHOUSE BLVD
DALLAS, TX 75201

TO:
JOHN DOE
456 OAK AVE
APT 4B
NEW YORK, NY 10001

TRACKING: 1Z999AA10123456784
SERVICE: OVERNIGHT
WEIGHT: 5.50 lbs
PACKAGES: 1

*** OVERNIGHT DELIVERY ***

Order: ORD-2024-001
Reference: CUST-12345
```

Without an `apartment` the whole `APT` line disappears: the `@if` and `@end` lines are directive-only and produce nothing themselves.

## Product Feed for Marketplace

### Amazon Product Feed (Tab-Delimited)

```php
$template = 'item_sku	item_name	brand_name	external_product_id	external_product_id_type	item_type	standard_price	quantity	product_description	bullet_point1	bullet_point2	bullet_point3	main_image_url	category
@for(products as product)
@csv(product.sku)	@csv(product.name)	@csv(product.brand)	{product.upc}	UPC	{product.type}	@number(product.price, 2, ".", "")	{product.qty}	@csv(product.description)	@csv(product.features.0)	@csv(product.features.1)	@csv(product.features.2)	@csv(product.image_url)	@csv(product.category)
@end';

$data = [
    'products' => [
        [
            'sku' => 'WID-001',
            'name' => 'Premium Widget Pro',
            'brand' => 'WidgetCo',
            'upc' => '012345678901',
            'type' => 'widget',
            'price' => 29.99,
            'qty' => 150,
            'description' => 'High-quality widget with advanced features',
            'features' => [
                'Durable construction',
                'Easy to use'
            ],
            'image_url' => 'https://example.com/images/wid-001.jpg',
            'category' => 'Tools & Home Improvement'
        ],
    ]
];

$feed = $tsuku->process($template, $data);
file_put_contents('amazon-feed.txt', $feed);
```

**Output (amazon-feed.txt, tab-separated):**
```
item_sku	item_name	brand_name	external_product_id	external_product_id_type	item_type	standard_price	quantity	product_description	bullet_point1	bullet_point2	bullet_point3	main_image_url	category
WID-001	Premium Widget Pro	WidgetCo	012345678901	UPC	widget	29.99	150	High-quality widget with advanced features	Durable construction	Easy to use		https://example.com/images/wid-001.jpg	Tools & Home Improvement
```

List elements are addressed by index with dot notation (`product.features.0`); a missing element (`product.features.2` here) renders as an empty field.

## Abandoned Cart Email

### Recovery Email

```php
$template = 'Hi @html(customer.first_name),

We noticed you left some items in your cart. Don\'t worry, we saved them for you!

Items in your cart:
@for(cart.items as item)
- @html(item.name) (@html(item.qty) x $@number(item.price, 2) = $@number(item.line_total, 2))
@end

Cart Total: $@number(cart.total, 2)

@if(discount.available)
Special Offer! Use code @upper(discount.code) for @number(discount.percentage, 0, "", "")% off your order!

@end
Complete your purchase: @html(cart.url)

Need help? Contact us at @html(support.email)

Best regards,
The @html(store.name) Team';

$data = [
    'customer' => [
        'first_name' => 'Jane',
        'email' => 'jane@example.com'
    ],
    'cart' => [
        'items' => [
            ['name' => 'Wireless Headphones', 'qty' => 1, 'price' => 79.99, 'line_total' => 79.99],
            ['name' => 'Phone Case', 'qty' => 2, 'price' => 14.99, 'line_total' => 29.98],
        ],
        'total' => 109.97,
        'url' => 'https://example.com/cart/recover/abc123'
    ],
    'discount' => [
        'available' => true,
        'code' => 'COMEBACK10',
        'percentage' => 10
    ],
    'support' => [
        'email' => 'support@example.com'
    ],
    'store' => [
        'name' => 'My Store'
    ]
];

$email = $tsuku->process($template, $data);
```

**Output:**
```
Hi Jane,

We noticed you left some items in your cart. Don't worry, we saved them for you!

Items in your cart:
- Wireless Headphones (1 x $79.99 = $79.99)
- Phone Case (2 x $14.99 = $29.98)

Cart Total: $109.97

Special Offer! Use code COMEBACK10 for 10% off your order!

Complete your purchase: https://example.com/cart/recover/abc123

Need help? Contact us at support@example.com

Best regards,
The My Store Team
```

The blank line after the offer is inside the `@if`, so the email has no double blank line when there is no discount.

## Product Price List

### Wholesale Price List (CSV)

```php
$template = 'Category,SKU,Product Name,Retail Price,Wholesale Price,Qty Available,Minimum Order
@for(products as product)
@csv(product.category),@csv(product.sku),@csv(product.name),@number(product.retail_price, 2),@number(product.wholesale_price, 2),{product.qty},@default(product.min_order, "1")
@end';

$data = [
    'products' => [
        [
            'category' => 'Electronics',
            'sku' => 'ELEC-001',
            'name' => 'Wireless Mouse',
            'retail_price' => 29.99,
            'wholesale_price' => 19.99,
            'qty' => 500,
            'min_order' => 10
        ],
        [
            'category' => 'Electronics',
            'sku' => 'ELEC-002',
            'name' => 'USB Cable (6ft)',
            'retail_price' => 12.99,
            'wholesale_price' => 7.99,
            'qty' => 1000,
            'min_order' => 50
        ],
    ]
];

$priceList = $tsuku->process($template, $data);
file_put_contents('wholesale-prices.csv', $priceList);
```

**Output (wholesale-prices.csv):**
```csv
Category,SKU,Product Name,Retail Price,Wholesale Price,Qty Available,Minimum Order
Electronics,ELEC-001,Wireless Mouse,29.99,19.99,500,10
Electronics,ELEC-002,USB Cable (6ft),12.99,7.99,1000,50
```

`@number(x, 2)` adds a thousands separator from 1,000 on (`1,299.00`), which would split the CSV field. For prices that can reach 1,000, use `@number(x, 2, ".", "")`.

## Inventory Low Stock Alert

### Stock Alert Email

```php
$template = 'INVENTORY LOW STOCK ALERT
Generated: @date("Y-m-d H:i", generated_at)

The following products are running low on stock:

SKU          Product                         Current    Reorder    Status
@for(products as product)
@substr(@concat(product.sku, "            "), 0, 12) @substr(@concat(product.name, "                               "), 0, 30)  @substr(@concat(product.stock, "         "), 0, 10) @substr(@concat(product.reorder_level, "         "), 0, 10) @if(product.stock == 0)
OUT OF STOCK
@elseif(product.stock <= product.reorder_level)
REORDER NOW
@else
OK
@end
@end

Total products requiring attention: @length(products)

Please review and place purchase orders as needed.';

$data = [
    'generated_at' => 1705312800, // 2024-01-15 10:00 UTC
    'products' => [
        ['sku' => 'WID-001', 'name' => 'Premium Widget', 'stock' => 5, 'reorder_level' => 20],
        ['sku' => 'GAD-002', 'name' => 'Smart Gadget', 'stock' => 0, 'reorder_level' => 15],
        ['sku' => 'TOO-003', 'name' => 'Professional Tool', 'stock' => 12, 'reorder_level' => 10],
    ]
];

$alert = $tsuku->process($template, $data);
mail('inventory@example.com', 'Low Stock Alert', $alert);
```

**Output:**
```
INVENTORY LOW STOCK ALERT
Generated: 2024-01-15 10:00

The following products are running low on stock:

SKU          Product                         Current    Reorder    Status
WID-001      Premium Widget                  5          20         REORDER NOW
GAD-002      Smart Gadget                    0          15         OUT OF STOCK
TOO-003      Professional Tool               12         10         OK

Total products requiring attention: 3

Please review and place purchase orders as needed.
```

The status is an `@if` / `@elseif` / `@else` chain that starts at the end of the row, so each branch continues that row; the line break at the end of the chosen branch ends it.

## Magento 2 Order Export

### Export Orders to CSV

```php
use Qoliber\Tsuku\Tsuku;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class OrderExporter
{
    public function __construct(
        private readonly Tsuku $tsuku,
        private readonly CollectionFactory $orderCollectionFactory
    ) {}

    public function export(\DateTime $from, \DateTime $to): string
    {
        $collection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('created_at', [
                'from' => $from->format('Y-m-d H:i:s'),
                'to' => $to->format('Y-m-d H:i:s')
            ]);

        $orders = [];
        foreach ($collection as $order) {
            $orders[] = [
                'increment_id' => $order->getIncrementId(),
                'status' => $order->getStatus(),
                'customer_name' => $order->getCustomerName(),
                'customer_email' => $order->getCustomerEmail(),
                'subtotal' => $order->getSubtotal(),
                'tax' => $order->getTaxAmount(),
                'shipping' => $order->getShippingAmount(),
                'grand_total' => $order->getGrandTotal(),
                'created_at' => $order->getCreatedAt(),
                'items_count' => $order->getTotalItemCount()
            ];
        }

        $template = 'Order Number,Status,Customer Name,Customer Email,Subtotal,Tax,Shipping,Total,Items,Date
@for(orders as order)
@csv(order.increment_id),@csv(order.status),@csv(order.customer_name),@csv(order.customer_email),@number(order.subtotal, 2),@number(order.tax, 2),@number(order.shipping, 2),@number(order.grand_total, 2),{order.items_count},@csv(order.created_at)
@end';

        return $this->tsuku->process($template, ['orders' => $orders]);
    }
}
```

## Best Practices

### Always Escape User Data in Emails

```php
$data = ['customer' => ['name' => 'Ann <b onmouseover="alert(1)">']];

// ✅ Good - Escaped
echo $tsuku->process('Hi @html(customer.name),', $data);
// Output: Hi Ann &lt;b onmouseover=&quot;alert(1)&quot;&gt;,

// ❌ Bad - Not escaped (XSS in email)
echo $tsuku->process('Hi {customer.name},', $data);
// Output: Hi Ann <b onmouseover="alert(1)">,
```

### Use Consistent Currency Formatting

```php
$data = ['price' => 1299.5];

// ✅ Good - Consistent 2 decimals
echo $tsuku->process('$@number(price, 2)', $data);
// Output: $1,299.50

// ❌ Bad - Inconsistent formatting (PHP's float-to-string conversion)
echo $tsuku->process('${price}', $data);
// Output: $1299.5
```

### Include Order References

```php
// Always include order/invoice numbers for tracking
$template = 'Order #{order.id}
Invoice #@number(invoice.id, 0, "", "")';

$data = ['order' => ['id' => '000123'], 'invoice' => ['id' => 4567]];

echo $tsuku->process($template, $data);
// Output:
// Order #000123
// Invoice #4567
```

Print ids that are strings (with leading zeros) as plain variables; `@number()` would turn `000123` into `123`.

## Next Steps

- [CSV Exports →](/examples/csv-exports) - Learn CSV formatting
- [Security →](/guide/security) - Secure email templates
- [Custom Functions →](/guide/custom-functions) - Create commerce functions
