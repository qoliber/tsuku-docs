# E-commerce Examples

Real-world e-commerce scenarios: invoices, order confirmations, product feeds, email templates, and shipping labels.

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
@substr(@concat(item.sku, "            "), 0, 12) @substr(@concat(item.name, "                              "), 0, 30) @substr(@concat(item.qty, "      "), 0, 6) $@substr(@concat(@number(item.price, 2), "          "), 0, 10) $@number(item.qty * item.price, 2)
@end

                                              Subtotal: $@number(invoice.subtotal, 2)
                                                   Tax: $@number(invoice.tax, 2)
                                              Shipping: $@number(invoice.shipping, 2)
                                                 Total: $@number(invoice.total, 2)

Payment Status: @match(invoice.paid)
@case(1, true)PAID
@default UNPAID - Due @date("F j, Y", invoice.due_date)
@end

Thank you for your business!';

$data = [
    'invoice' => [
        'number' => 12345,
        'date' => time(),
        'due_date' => strtotime('+30 days'),
        'customer' => [
            'name' => 'Acme Corporation',
            'address' => '123 Business St',
            'city' => 'New York',
            'state' => 'NY',
            'zip' => '10001',
            'email' => 'billing@acme.com'
        ],
        'items' => [
            ['sku' => 'WID-001', 'name' => 'Premium Widget', 'qty' => 10, 'price' => 29.99],
            ['sku' => 'GAD-002', 'name' => 'Smart Gadget', 'qty' => 5, 'price' => 49.99],
            ['sku' => 'TOO-003', 'name' => 'Professional Tool', 'qty' => 3, 'price' => 19.99],
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
            @case("pending")Processing
            @case("shipped")Shipped
            @case("delivered")Delivered
            @default @html(order.status)
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
                <td>$@number(item.qty * item.price, 2)</td>
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
        'date' => time(),
        'status' => 'shipped',
        'items' => [
            ['name' => 'Wireless Mouse', 'sku' => 'MOU-001', 'qty' => 2, 'price' => 24.99],
            ['name' => 'USB Cable', 'sku' => 'CAB-002', 'qty' => 3, 'price' => 9.99],
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
mail($order['customer_email'], 'Order Confirmation', $email, [
    'Content-Type' => 'text/html; charset=UTF-8'
]);
```

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
@if(to.apartment)@upper(to.apartment)
@end@upper(to.city), @upper(to.state) @upper(to.zip)

TRACKING: @upper(shipment.tracking)
SERVICE: @upper(shipment.service)
WEIGHT: @number(shipment.weight, 2) lbs
PACKAGES: {shipment.package_count}

@match(shipment.service)
@case("overnight")*** OVERNIGHT DELIVERY ***
@case("2day")*** 2-DAY DELIVERY ***
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

## Product Feed for Marketplace

### Amazon Product Feed (Tab-Delimited)

```php
$template = 'item_sku	item_name	brand_name	external_product_id	external_product_id_type	item_type	standard_price	quantity	product_description	bullet_point1	bullet_point2	bullet_point3	main_image_url	category
@for(products as product)
@csv(product.sku)	@csv(product.name)	@csv(product.brand)	{product.upc}	UPC	{product.type}	@number(product.price, 2, ".", "")	{product.qty}	@csv(product.description)	@csv(@first(product.features))	@csv(@default(@first(@substr(product.features, 1)), ""))	@csv(@default(@first(@substr(product.features, 2)), ""))	@csv(product.image_url)	@csv(product.category)
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
                'Easy to use',
                'Lifetime warranty'
            ],
            'image_url' => 'https://example.com/images/wid-001.jpg',
            'category' => 'Tools & Home Improvement'
        ],
    ]
];

$feed = $tsuku->process($template, $data);
file_put_contents('amazon-feed.txt', $feed);
```

## Abandoned Cart Email

### Recovery Email

```php
$template = 'Hi @html(customer.first_name),

We noticed you left some items in your cart. Don\'t worry, we saved them for you!

Items in your cart:
@for(cart.items as item)
- @html(item.name) (@html(item.qty) x $@number(item.price, 2) = $@number(item.qty * item.price, 2))
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
            ['name' => 'Wireless Headphones', 'qty' => 1, 'price' => 79.99],
            ['name' => 'Phone Case', 'qty' => 2, 'price' => 14.99],
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

## Inventory Low Stock Alert

### Stock Alert Email

```php
$template = 'INVENTORY LOW STOCK ALERT
Generated: @date("Y-m-d H:i:s")

The following products are running low on stock:

SKU          Product                         Current    Reorder    Status
@for(products as product)
@substr(@concat(product.sku, "            "), 0, 12) @substr(@concat(product.name, "                               "), 0, 30)  @substr(@concat(product.stock, "         "), 0, 10) @substr(@concat(product.reorder_level, "         "), 0, 10) @match(product.stock)
@case(0)OUT OF STOCK
@default@if(product.stock <= product.reorder_level)REORDER NOW@else OK@end
@end
@end

Total products requiring attention: @length(products)

Please review and place purchase orders as needed.';

$data = [
    'products' => [
        ['sku' => 'WID-001', 'name' => 'Premium Widget', 'stock' => 5, 'reorder_level' => 20],
        ['sku' => 'GAD-002', 'name' => 'Smart Gadget', 'stock' => 0, 'reorder_level' => 15],
        ['sku' => 'TOO-003', 'name' => 'Professional Tool', 'stock' => 8, 'reorder_level' => 10],
    ]
];

$alert = $tsuku->process($template, $data);
mail('inventory@example.com', 'Low Stock Alert', $alert);
```

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
// ✅ Good - Escaped
$template = 'Hi @html(customer.name),';

// ❌ Bad - Not escaped (XSS in email)
$template = 'Hi {customer.name},';
```

### Use Consistent Currency Formatting

```php
// ✅ Good - Consistent 2 decimals
$template = '$@number(price, 2)';

// ❌ Bad - Inconsistent formatting
$template = '${price}';
```

### Include Order References

```php
// Always include order/invoice numbers for tracking
$template = 'Order #@number(order.id, 0, "", "")
Invoice #@number(invoice.id, 0, "", "")';
```

## Next Steps

- [CSV Exports →](/examples/csv-exports) - Learn CSV formatting
- [Security →](/guide/security) - Secure email templates
- [Custom Functions →](/guide/custom-functions) - Create commerce functions
