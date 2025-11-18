# XML Generation

Generate XML feeds, sitemaps, and documents with proper escaping and formatting.

## Basic XML Document

### Product Feed

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

$template = '<?xml version="1.0" encoding="UTF-8"?>
<products>
@for(products as product)
    <product id="{product.id}">
        <sku>@xml(product.sku)</sku>
        <name>@xml(product.name)</name>
        <description>@xml(product.description)</description>
        <price currency="USD">@number(product.price, 2)</price>
        <stock>@match(product.stock)
            @case(0)out_of_stock
            @default in_stock
        @end</stock>
    </product>
@end
</products>';

$data = [
    'products' => [
        [
            'id' => 1,
            'sku' => 'WID-001',
            'name' => 'Premium Widget',
            'description' => 'High-quality widget with <advanced> features & more',
            'price' => 29.99,
            'stock' => 150
        ],
        [
            'id' => 2,
            'sku' => 'GAD-002',
            'name' => 'Smart Gadget',
            'description' => 'Innovative "gadget" for modern users',
            'price' => 49.99,
            'stock' => 0
        ],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<products>
    <product id="1">
        <sku>WID-001</sku>
        <name>Premium Widget</name>
        <description>High-quality widget with &lt;advanced&gt; features &amp; more</description>
        <price currency="USD">29.99</price>
        <stock>in_stock</stock>
    </product>
    <product id="2">
        <sku>GAD-002</sku>
        <name>Smart Gadget</name>
        <description>Innovative &quot;gadget&quot; for modern users</description>
        <price currency="USD">49.99</price>
        <stock>out_of_stock</stock>
    </product>
</products>
```

## Google Shopping Feed

### Complete Shopping Feed

```php
$template = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
    <channel>
        <title>@xml(store.name)</title>
        <link>@xml(store.url)</link>
        <description>@xml(store.description)</description>
@for(products as product)
        <item>
            <g:id>{product.id}</g:id>
            <g:title>@xml(product.name)</g:title>
            <g:description>@xml(product.description)</g:description>
            <g:link>@xml(product.url)</g:link>
            <g:image_link>@xml(product.image)</g:image_link>
            <g:price>@number(product.price, 2) USD</g:price>
            <g:availability>@match(product.stock)
                @case(0)out of stock
                @default in stock
            @end</g:availability>
            <g:condition>new</g:condition>
            <g:brand>@xml(product.brand)</g:brand>
            <g:gtin>{product.gtin}</g:gtin>
        </item>
@end
    </channel>
</rss>';

$data = [
    'store' => [
        'name' => 'My Store',
        'url' => 'https://example.com',
        'description' => 'Quality products & great service'
    ],
    'products' => [
        [
            'id' => 'WID001',
            'name' => 'Premium Widget',
            'description' => 'High-quality widget',
            'url' => 'https://example.com/widget',
            'image' => 'https://example.com/images/widget.jpg',
            'price' => 29.99,
            'stock' => 100,
            'brand' => 'WidgetCo',
            'gtin' => '0123456789012'
        ],
    ]
];

echo $tsuku->process($template, $data);
```

## Sitemap Generation

### XML Sitemap

```php
$template = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@for(pages as page)
    <url>
        <loc>@xml(page.url)</loc>
        <lastmod>@date("Y-m-d", page.updated_at)</lastmod>
        <changefreq>{page.changefreq}</changefreq>
        <priority>@number(page.priority, 1, ".", "")</priority>
    </url>
@end
</urlset>';

$data = [
    'pages' => [
        [
            'url' => 'https://example.com/',
            'updated_at' => time(),
            'changefreq' => 'daily',
            'priority' => 1.0
        ],
        [
            'url' => 'https://example.com/products',
            'updated_at' => strtotime('-1 day'),
            'changefreq' => 'weekly',
            'priority' => 0.8
        ],
        [
            'url' => 'https://example.com/about',
            'updated_at' => strtotime('-30 days'),
            'changefreq' => 'monthly',
            'priority' => 0.5
        ],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com/</loc>
        <lastmod>2024-01-15</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>https://example.com/products</loc>
        <lastmod>2024-01-14</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>https://example.com/about</loc>
        <lastmod>2023-12-16</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
</urlset>
```

## RSS Feed

### Blog RSS Feed

```php
$template = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>@xml(blog.title)</title>
        <link>@xml(blog.url)</link>
        <description>@xml(blog.description)</description>
        <language>en-us</language>
        <lastBuildDate>@date("r")</lastBuildDate>
@for(posts as post)
        <item>
            <title>@xml(post.title)</title>
            <link>@xml(post.url)</link>
            <description>@xml(post.excerpt)</description>
            <pubDate>@date("r", post.published_at)</pubDate>
            <guid>@xml(post.url)</guid>
            <author>@xml(post.author.email) (@xml(post.author.name))</author>
@for(post.categories as category)
            <category>@xml(category)</category>
@end
        </item>
@end
    </channel>
</rss>';

$data = [
    'blog' => [
        'title' => 'Tech Blog',
        'url' => 'https://blog.example.com',
        'description' => 'Latest tech news & tutorials'
    ],
    'posts' => [
        [
            'title' => 'Getting Started with PHP 8.1',
            'url' => 'https://blog.example.com/php-8-1',
            'excerpt' => 'Learn about new features in PHP 8.1',
            'published_at' => strtotime('2024-01-15'),
            'author' => ['name' => 'John Doe', 'email' => 'john@example.com'],
            'categories' => ['PHP', 'Tutorial']
        ],
    ]
];

echo $tsuku->process($template, $data);
```

## SOAP Response

### SOAP Envelope

```php
$template = '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <OrderResponse xmlns="http://example.com/orders">
            <orderId>{order.id}</orderId>
            <status>@xml(order.status)</status>
            <customer>
                <name>@xml(order.customer.name)</name>
                <email>@xml(order.customer.email)</email>
            </customer>
            <items>
@for(order.items as item)
                <item>
                    <sku>@xml(item.sku)</sku>
                    <quantity>{item.qty}</quantity>
                    <price>@number(item.price, 2)</price>
                </item>
@end
            </items>
            <total>@number(order.total, 2)</total>
        </OrderResponse>
    </soap:Body>
</soap:Envelope>';

$data = [
    'order' => [
        'id' => 12345,
        'status' => 'confirmed',
        'customer' => [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ],
        'items' => [
            ['sku' => 'WID-001', 'qty' => 2, 'price' => 29.99],
            ['sku' => 'GAD-002', 'qty' => 1, 'price' => 49.99],
        ],
        'total' => 109.97
    ]
];

echo $tsuku->process($template, $data);
```

## Configuration Files

### Spring Boot XML Config

```php
$template = '<?xml version="1.0" encoding="UTF-8"?>
<beans xmlns="http://www.springframework.org/schema/beans">
@for(datasources as ds)
    <bean id="{ds.id}" class="org.apache.commons.dbcp.BasicDataSource">
        <property name="driverClassName" value="@xml(ds.driver)" />
        <property name="url" value="@xml(ds.url)" />
        <property name="username" value="@xml(ds.username)" />
        <property name="password" value="@xml(ds.password)" />
        <property name="maxActive" value="{ds.max_connections}" />
    </bean>
@end
</beans>';

$data = [
    'datasources' => [
        [
            'id' => 'mainDataSource',
            'driver' => 'com.mysql.jdbc.Driver',
            'url' => 'jdbc:mysql://localhost:3306/mydb',
            'username' => 'root',
            'password' => 'secret123',
            'max_connections' => 20
        ],
    ]
];

echo $tsuku->process($template, $data);
```

## SVG Generation

### Dynamic SVG Chart

```php
$template = '<svg width="400" height="200" xmlns="http://www.w3.org/2000/svg">
    <rect width="400" height="200" fill="#f0f0f0"/>
@for(bars as bar, index)
    <rect
        x="@number(index * 50 + 10, 0, "", "")"
        y="@number(200 - bar.value * 2, 0, "", "")"
        width="40"
        height="@number(bar.value * 2, 0, "", "")"
        fill="#4CAF50"/>
    <text
        x="@number(index * 50 + 30, 0, "", "")"
        y="@number(200 - bar.value * 2 - 5, 0, "", "")"
        text-anchor="middle"
        font-size="12">@xml(bar.label)</text>
@end
</svg>';

$data = [
    'bars' => [
        ['label' => 'Jan', 'value' => 45],
        ['label' => 'Feb', 'value' => 65],
        ['label' => 'Mar', 'value' => 55],
        ['label' => 'Apr', 'value' => 80],
    ]
];

echo $tsuku->process($template, $data);
```

## Magento 2 Product Feed

### Custom Product Export

```php
use Qoliber\Tsuku\Tsuku;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class ProductXmlFeedGenerator
{
    public function __construct(
        private readonly Tsuku $tsuku,
        private readonly CollectionFactory $productCollectionFactory,
        private readonly \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {}

    public function generate(): string
    {
        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect(['name', 'sku', 'price', 'description', 'image'])
            ->addFieldToFilter('status', 1)
            ->addFieldToFilter('visibility', ['neq' => 1]);

        $products = [];
        $store = $this->storeManager->getStore();

        foreach ($collection as $product) {
            $products[] = [
                'id' => $product->getId(),
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getFinalPrice(),
                'url' => $product->getProductUrl(),
                'image' => $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                         . 'catalog/product' . $product->getImage(),
                'stock' => $product->getExtensionAttributes()->getStockItem()->getQty()
            ];
        }

        $template = '<?xml version="1.0" encoding="UTF-8"?>
<products>
@for(products as product)
    <product>
        <id>{product.id}</id>
        <sku>@xml(product.sku)</sku>
        <name>@xml(product.name)</name>
        <description>@xml(product.description)</description>
        <price>@number(product.price, 2)</price>
        <url>@xml(product.url)</url>
        <image>@xml(product.image)</image>
        <stock>@number(product.stock, 0, "", "")</stock>
    </product>
@end
</products>';

        return $this->tsuku->process($template, ['products' => $products]);
    }
}
```

## Best Practices

### Always Escape Text Content

```php
// ✅ Good - Escaped
$template = '<name>@xml(product.name)</name>';

// ❌ Bad - Not escaped (XSS risk)
$template = '<name>{product.name}</name>';
```

### Use CDATA for Large Text Blocks

```php
$template = '<description><![CDATA[@xml(product.description)]]></description>';
```

### Validate XML Structure

```php
$xml = $tsuku->process($template, $data);

// Validate
$doc = new DOMDocument();
if (!$doc->loadXML($xml)) {
    throw new \RuntimeException('Invalid XML generated');
}
```

### Add Proper Namespaces

```php
$template = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"
     xmlns:g="http://base.google.com/ns/1.0"
     xmlns:c="http://base.google.com/cns/1.0">
    <!-- content -->
</rss>';
```

## Next Steps

- [JSON APIs →](/examples/json-apis) - Learn JSON output
- [Security →](/guide/security) - XML injection prevention
- [Built-in Functions →](/guide/built-in-functions) - XML escaping
