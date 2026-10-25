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
        <price currency="USD">@number(product.price, 2, ".", "")</price>
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

Notes:
- `@xml()` escapes `&`, `<`, `>`, `"` and `'`; a plain `{variable}` is printed unescaped.
- Content comes before `@match` on its line (`<stock>`) and after its `@end` (`</stock>`), so the block is *inline*: the line breaks and indentation around each case body are dropped and the value renders as `<stock>in_stock</stock>`. Lines that hold only directives print nothing. See [Whitespace Control](/guide/whitespace-control).
- `@number(value, 2)` adds a comma as thousands separator (`1,299.50`); feeds usually need `@number(value, 2, ".", "")`.

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
            <g:price>@number(product.price, 2, ".", "") USD</g:price>
            <g:availability>@?{product.stock > 0 "in_stock" : "out_of_stock"}</g:availability>
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

**Output:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
    <channel>
        <title>My Store</title>
        <link>https://example.com</link>
        <description>Quality products &amp; great service</description>
        <item>
            <g:id>WID001</g:id>
            <g:title>Premium Widget</g:title>
            <g:description>High-quality widget</g:description>
            <g:link>https://example.com/widget</g:link>
            <g:image_link>https://example.com/images/widget.jpg</g:image_link>
            <g:price>29.99 USD</g:price>
            <g:availability>in_stock</g:availability>
            <g:condition>new</g:condition>
            <g:brand>WidgetCo</g:brand>
            <g:gtin>0123456789012</g:gtin>
        </item>
    </channel>
</rss>
```

`@?{condition "value if true" : "value if false"}` picks one of two values inline - handy for single values such as the availability.

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
            'updated_at' => strtotime('2024-01-15'),
            'changefreq' => 'daily',
            'priority' => 1.0
        ],
        [
            'url' => 'https://example.com/products',
            'updated_at' => strtotime('2024-01-14'),
            'changefreq' => 'weekly',
            'priority' => 0.8
        ],
        [
            'url' => 'https://example.com/about',
            'updated_at' => strtotime('2023-12-16'),
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
        <lastBuildDate>@date("r", blog.built_at)</lastBuildDate>
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
        'description' => 'Latest tech news & tutorials',
        'built_at' => strtotime('2024-01-16 08:00:00'), // in production: time()
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

**Output (default timezone UTC):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Tech Blog</title>
        <link>https://blog.example.com</link>
        <description>Latest tech news &amp; tutorials</description>
        <language>en-us</language>
        <lastBuildDate>Tue, 16 Jan 2024 08:00:00 +0000</lastBuildDate>
        <item>
            <title>Getting Started with PHP 8.1</title>
            <link>https://blog.example.com/php-8-1</link>
            <description>Learn about new features in PHP 8.1</description>
            <pubDate>Mon, 15 Jan 2024 00:00:00 +0000</pubDate>
            <guid>https://blog.example.com/php-8-1</guid>
            <author>john@example.com (John Doe)</author>
            <category>PHP</category>
            <category>Tutorial</category>
        </item>
    </channel>
</rss>
```

The e-mail address comes from the data, so it needs no escaping in the template. An `@` typed into the template text itself must be written `@@` - see [Escapes & Special Characters](/guide/escaping).

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
                    <price>@number(item.price, 2, ".", "")</price>
                </item>
@end
            </items>
            <total>@number(order.total, 2, ".", "")</total>
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

**Output:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <OrderResponse xmlns="http://example.com/orders">
            <orderId>12345</orderId>
            <status>confirmed</status>
            <customer>
                <name>John Doe</name>
                <email>john@example.com</email>
            </customer>
            <items>
                <item>
                    <sku>WID-001</sku>
                    <quantity>2</quantity>
                    <price>29.99</price>
                </item>
                <item>
                    <sku>GAD-002</sku>
                    <quantity>1</quantity>
                    <price>49.99</price>
                </item>
            </items>
            <total>109.97</total>
        </OrderResponse>
    </soap:Body>
</soap:Envelope>
```

The order total is part of the data: templates have no arithmetic, so totals are calculated in PHP.

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

**Output:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<beans xmlns="http://www.springframework.org/schema/beans">
    <bean id="mainDataSource" class="org.apache.commons.dbcp.BasicDataSource">
        <property name="driverClassName" value="com.mysql.jdbc.Driver" />
        <property name="url" value="jdbc:mysql://localhost:3306/mydb" />
        <property name="username" value="root" />
        <property name="password" value="secret123" />
        <property name="maxActive" value="20" />
    </bean>
</beans>
```

`@xml()` also escapes `"`, so it is safe inside attribute values.

## SVG Generation

### Dynamic SVG Chart

Templates have no arithmetic: `{index * 50}` is printed as plain text, and a function argument such as `index * 50 + 10` is looked up as a variable name, so `@number(index * 50 + 10, 0)` prints `0`. Calculate the chart geometry in PHP instead and let the template place the values:

```php
$template = '<svg width="400" height="200" xmlns="http://www.w3.org/2000/svg">
    <rect width="400" height="200" fill="#f0f0f0"/>
@for(bars as bar)
    <rect x="{bar.x}" y="{bar.y}" width="40" height="{bar.height}" fill="#4CAF50"/>
    <text x="{bar.label_x}" y="{bar.label_y}" text-anchor="middle" font-size="12">@xml(bar.label)</text>
@end
</svg>';

$values = ['Jan' => 45, 'Feb' => 65, 'Mar' => 55, 'Apr' => 80];

$bars = [];
foreach (array_keys($values) as $index => $label) {
    $height = $values[$label] * 2;
    $bars[] = [
        'label' => $label,
        'x' => $index * 50 + 10,
        'y' => 200 - $height,
        'height' => $height,
        'label_x' => $index * 50 + 30,
        'label_y' => 200 - $height - 5,
    ];
}

echo $tsuku->process($template, ['bars' => $bars]);
```

**Output:**
```xml
<svg width="400" height="200" xmlns="http://www.w3.org/2000/svg">
    <rect width="400" height="200" fill="#f0f0f0"/>
    <rect x="10" y="110" width="40" height="90" fill="#4CAF50"/>
    <text x="30" y="105" text-anchor="middle" font-size="12">Jan</text>
    <rect x="60" y="70" width="40" height="130" fill="#4CAF50"/>
    <text x="80" y="65" text-anchor="middle" font-size="12">Feb</text>
    <rect x="110" y="90" width="40" height="110" fill="#4CAF50"/>
    <text x="130" y="85" text-anchor="middle" font-size="12">Mar</text>
    <rect x="160" y="40" width="40" height="160" fill="#4CAF50"/>
    <text x="180" y="35" text-anchor="middle" font-size="12">Apr</text>
</svg>
```

If you prefer to keep such calculations next to the template, register a small function, for example `$tsuku->registerFunction('times', fn($a, $b) => $a * $b);` and write `@times(bar.value, 2)`.

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
        <price>@number(product.price, 2, ".", "")</price>
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
$data = ['product' => ['name' => 'Fish & Chips <Large>']];

// ✅ Good - Escaped
echo $tsuku->process('<name>@xml(product.name)</name>', $data);
// Output: <name>Fish &amp; Chips &lt;Large&gt;</name>

// ❌ Bad - Not escaped: invalid XML, and markup in the data ends up in the document
echo $tsuku->process('<name>{product.name}</name>', $data);
// Output: <name>Fish & Chips <Large></name>
```

### Use CDATA for Large Text Blocks

Text inside a CDATA section is not entity-decoded, so do not run it through `@xml()` - the reader would see `&amp;` literally. Instead, register a block directive that wraps its content in CDATA and splits any `]]>` in the data:

```php
$tsuku->registerDirective('cdata', fn(string $body): string =>
    '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $body) . ']]>');

echo $tsuku->process(
    '<description>@cdata{product.description}@end</description>',
    ['product' => ['description' => 'Fish & Chips <b>fresh</b>']]
);
// Output: <description><![CDATA[Fish & Chips <b>fresh</b>]]></description>
```

See [Custom Directives](/guide/custom-directives) for how block directives work.

### Stream Large Feeds

`process()` builds the whole document in memory. For feeds with tens of thousands of products, `processToStream()` renders the part before the `@for` once, then one row at a time, then the rest, and hands each piece to a callback - so memory use does not grow with the number of products:

```php
$template = '<?xml version="1.0" encoding="UTF-8"?>
<products>
@for(products as product)
    <product id="{product.id}">
        <name>@xml(product.name)</name>
        <price>@number(product.price, 2, ".", "")</price>
    </product>
@end
</products>';

// Any iterable works; a generator can page through the database one row at a time
$rows = (function (): \Generator {
    yield ['id' => 1, 'name' => 'Fish & Chips', 'price' => 8.5];
    yield ['id' => 2, 'name' => 'Widget', 'price' => 1299.5];
})();

$handle = fopen('feed.xml', 'wb');
$tsuku->processToStream(
    $template,
    [],          // data shared by the header, every row and the footer
    $rows,       // the rows of the streamed @for, consumed once
    'products',  // the name of the @for collection that is streamed
    fn(string $chunk) => fwrite($handle, $chunk)
);
fclose($handle);
```

**Output (feed.xml):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<products>
    <product id="1">
        <name>Fish &amp; Chips</name>
        <price>8.50</price>
    </product>
    <product id="2">
        <name>Widget</name>
        <price>1299.50</price>
    </product>
</products>
```

The template must contain exactly one top-level `@for` over the streamed collection. See [Streaming Large Datasets](/guide/streaming).

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

Declare every namespace prefix you use (`g:`, `c:`, ...) on the root element of the template:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"
     xmlns:g="http://base.google.com/ns/1.0"
     xmlns:c="http://base.google.com/cns/1.0">
    <!-- content -->
</rss>
```

## Next Steps

- [JSON APIs →](/examples/json-apis) - Learn JSON output
- [Security →](/guide/security) - Escaping for every output format
- [Built-in Functions →](/guide/built-in-functions) - XML escaping
