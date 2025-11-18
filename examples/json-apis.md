# JSON APIs

Generate JSON responses for REST APIs, GraphQL, and data serialization with proper escaping.

## Basic JSON Response

### Simple API Response

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

$template = '{
    "success": true,
    "data": {
        "id": {user.id},
        "name": @json(user.name),
        "email": @json(user.email),
        "role": @json(user.role),
        "active": @match(user.active)
            @case(1, true)true
            @default false
        @end
    }
}';

$data = [
    'user' => [
        'id' => 123,
        'name' => 'John "The Developer" Doe',
        'email' => 'john@example.com',
        'role' => 'admin',
        'active' => true
    ]
];

header('Content-Type: application/json');
echo $tsuku->process($template, $data);
```

**Output:**
```json
{
    "success": true,
    "data": {
        "id": 123,
        "name": "John \"The Developer\" Doe",
        "email": "john@example.com",
        "role": "admin",
        "active": true
    }
}
```

## Array Responses

### Product List API

```php
$template = '{
    "success": true,
    "total": @length(products),
    "data": [
@for(products as product, index)
        {
            "id": {product.id},
            "sku": @json(product.sku),
            "name": @json(product.name),
            "price": @number(product.price, 2, ".", ""),
            "stock": {product.stock},
            "available": @match(product.stock)
                @case(0)false
                @default true
            @end
        }@if(index < length(products) - 1),@end
@end
    ]
}';

$data = [
    'products' => [
        ['id' => 1, 'sku' => 'WID-001', 'name' => 'Widget', 'price' => 29.99, 'stock' => 100],
        ['id' => 2, 'sku' => 'GAD-002', 'name' => 'Gadget', 'price' => 49.99, 'stock' => 0],
        ['id' => 3, 'sku' => 'TOO-003', 'name' => 'Tool', 'price' => 19.99, 'stock' => 50],
    ]
];

header('Content-Type: application/json');
echo $tsuku->process($template, $data);
```

**Output:**
```json
{
    "success": true,
    "total": 3,
    "data": [
        {
            "id": 1,
            "sku": "WID-001",
            "name": "Widget",
            "price": 29.99,
            "stock": 100,
            "available": true
        },
        {
            "id": 2,
            "sku": "GAD-002",
            "name": "Gadget",
            "price": 49.99,
            "stock": 0,
            "available": false
        },
        {
            "id": 3,
            "sku": "TOO-003",
            "name": "Tool",
            "price": 19.99,
            "stock": 50,
            "available": true
        }
    ]
}
```

## Nested Objects

### Order Details API

```php
$template = '{
    "order": {
        "id": @json(order.id),
        "status": @json(order.status),
        "created_at": @json(@date("c", order.created_at)),
        "customer": {
            "id": {order.customer.id},
            "name": @json(order.customer.name),
            "email": @json(order.customer.email)
        },
        "items": [
@for(order.items as item, index)
            {
                "id": {item.id},
                "name": @json(item.name),
                "sku": @json(item.sku),
                "quantity": {item.qty},
                "price": @number(item.price, 2, ".", ""),
                "total": @number(item.qty * item.price, 2, ".", "")
            }@if(index < length(order.items) - 1),@end
@end
        ],
        "totals": {
            "subtotal": @number(order.subtotal, 2, ".", ""),
            "tax": @number(order.tax, 2, ".", ""),
            "shipping": @number(order.shipping, 2, ".", ""),
            "total": @number(order.total, 2, ".", "")
        }
    }
}';

$data = [
    'order' => [
        'id' => 'ORD-12345',
        'status' => 'processing',
        'created_at' => time(),
        'customer' => [
            'id' => 456,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com'
        ],
        'items' => [
            ['id' => 1, 'name' => 'Widget', 'sku' => 'WID-001', 'qty' => 2, 'price' => 29.99],
            ['id' => 2, 'name' => 'Gadget', 'sku' => 'GAD-002', 'qty' => 1, 'price' => 49.99],
        ],
        'subtotal' => 109.97,
        'tax' => 10.00,
        'shipping' => 5.00,
        'total' => 124.97
    ]
];

header('Content-Type: application/json');
echo $tsuku->process($template, $data);
```

## Pagination

### Paginated Response

```php
$template = '{
    "data": [
@for(items as item, index)
        {
            "id": {item.id},
            "name": @json(item.name)
        }@if(index < length(items) - 1),@end
@end
    ],
    "pagination": {
        "current_page": {page.current},
        "per_page": {page.per_page},
        "total": {page.total},
        "total_pages": {page.total_pages},
        "has_next": @match(page.current < page.total_pages)
            @case(1, true)true
            @default false
        @end,
        "has_prev": @match(page.current > 1)
            @case(1, true)true
            @default false
        @end
    }
}';

$data = [
    'items' => [
        ['id' => 1, 'name' => 'Item 1'],
        ['id' => 2, 'name' => 'Item 2'],
        ['id' => 3, 'name' => 'Item 3'],
    ],
    'page' => [
        'current' => 2,
        'per_page' => 10,
        'total' => 45,
        'total_pages' => 5
    ]
];

header('Content-Type: application/json');
echo $tsuku->process($template, $data);
```

**Output:**
```json
{
    "data": [
        {"id": 1, "name": "Item 1"},
        {"id": 2, "name": "Item 2"},
        {"id": 3, "name": "Item 3"}
    ],
    "pagination": {
        "current_page": 2,
        "per_page": 10,
        "total": 45,
        "total_pages": 5,
        "has_next": true,
        "has_prev": true
    }
}
```

## Error Responses

### Error Handling

```php
$template = '{
    "success": false,
    "error": {
        "code": @json(error.code),
        "message": @json(error.message),
        "details": [
@for(error.details as detail, index)
            {
                "field": @json(detail.field),
                "message": @json(detail.message)
            }@if(index < length(error.details) - 1),@end
@end
        ]
    }
}';

$data = [
    'error' => [
        'code' => 'VALIDATION_ERROR',
        'message' => 'Invalid input data',
        'details' => [
            ['field' => 'email', 'message' => 'Invalid email format'],
            ['field' => 'password', 'message' => 'Password too short'],
        ]
    ]
];

header('Content-Type: application/json');
http_response_code(400);
echo $tsuku->process($template, $data);
```

## GraphQL Response

### GraphQL Query Result

```php
$template = '{
    "data": {
        "user": @if(user){
            "id": {user.id},
            "name": @json(user.name),
            "email": @json(user.email),
            "posts": [
@for(user.posts as post, index)
                {
                    "id": {post.id},
                    "title": @json(post.title),
                    "excerpt": @json(post.excerpt),
                    "comments": [
@for(post.comments as comment, cIndex)
                        {
                            "id": {comment.id},
                            "author": @json(comment.author),
                            "text": @json(comment.text)
                        }@if(cIndex < length(post.comments) - 1),@end
@end
                    ]
                }@if(index < length(user.posts) - 1),@end
@end
            ]
        }@else null@end
    }
}';

$data = [
    'user' => [
        'id' => 123,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'posts' => [
            [
                'id' => 1,
                'title' => 'First Post',
                'excerpt' => 'This is my first post',
                'comments' => [
                    ['id' => 1, 'author' => 'Jane', 'text' => 'Great post!'],
                ]
            ],
        ]
    ]
];

header('Content-Type: application/json');
echo $tsuku->process($template, $data);
```

## Configuration Export

### Export Settings as JSON

```php
$template = '{
    "app": {
        "name": @json(config.app_name),
        "version": @json(config.version),
        "environment": @json(config.env),
        "debug": @match(config.debug)
            @case(1, true)true
            @default false
        @end
    },
    "database": {
        "host": @json(config.db.host),
        "port": {config.db.port},
        "name": @json(config.db.name),
        "charset": @json(config.db.charset)
    },
    "features": {
@for(config.features as feature, index)
        @json(feature.name): @match(feature.enabled)
            @case(1, true)true
            @default false
        @end@if(index < length(config.features) - 1),@end
@end
    }
}';

$data = [
    'config' => [
        'app_name' => 'My Application',
        'version' => '1.0.0',
        'env' => 'production',
        'debug' => false,
        'db' => [
            'host' => 'localhost',
            'port' => 3306,
            'name' => 'myapp',
            'charset' => 'utf8mb4'
        ],
        'features' => [
            ['name' => 'cache', 'enabled' => true],
            ['name' => 'logging', 'enabled' => true],
            ['name' => 'profiling', 'enabled' => false],
        ]
    ]
];

echo $tsuku->process($template, $data);
```

## Magento 2 REST API

### Product API Endpoint

```php
use Qoliber\Tsuku\Tsuku;
use Magento\Catalog\Api\ProductRepositoryInterface;

class ProductApiController
{
    public function __construct(
        private readonly Tsuku $tsuku,
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    public function get(int $id): string
    {
        try {
            $product = $this->productRepository->getById($id);

            $template = '{
    "success": true,
    "data": {
        "id": {product.id},
        "sku": @json(product.sku),
        "name": @json(product.name),
        "price": @number(product.price, 2, ".", ""),
        "description": @json(product.description),
        "stock": {product.stock},
        "categories": [
@for(product.categories as category, index)
            {
                "id": {category.id},
                "name": @json(category.name)
            }@if(index < length(product.categories) - 1),@end
@end
        ]
    }
}';

            $data = [
                'product' => [
                    'id' => $product->getId(),
                    'sku' => $product->getSku(),
                    'name' => $product->getName(),
                    'price' => $product->getFinalPrice(),
                    'description' => $product->getDescription(),
                    'stock' => $product->getExtensionAttributes()->getStockItem()->getQty(),
                    'categories' => $this->getCategories($product)
                ]
            ];

            return $this->tsuku->process($template, $data);

        } catch (\Exception $e) {
            $errorTemplate = '{
    "success": false,
    "error": {
        "code": "PRODUCT_NOT_FOUND",
        "message": @json(message)
    }
}';
            return $this->tsuku->process($errorTemplate, ['message' => $e->getMessage()]);
        }
    }
}
```

## Webhook Payload

### Order Webhook

```php
$template = '{
    "event": "order.created",
    "timestamp": @json(@date("c")),
    "data": {
        "order_id": @json(order.id),
        "order_number": @json(order.number),
        "status": @json(order.status),
        "customer": {
            "id": {order.customer.id},
            "email": @json(order.customer.email),
            "name": @json(order.customer.name)
        },
        "total": @number(order.total, 2, ".", ""),
        "currency": @json(order.currency),
        "items_count": @length(order.items)
    }
}';

$data = [
    'order' => [
        'id' => 'ORD-12345',
        'number' => '000012345',
        'status' => 'pending',
        'customer' => [
            'id' => 789,
            'email' => 'customer@example.com',
            'name' => 'John Customer'
        ],
        'total' => 124.97,
        'currency' => 'USD',
        'items' => [
            ['id' => 1],
            ['id' => 2]
        ]
    ]
];

// Send webhook
$json = $tsuku->process($template, $data);
$ch = curl_init('https://webhook.example.com/orders');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_exec($ch);
```

## Best Practices

### Always Escape String Values

```php
// ✅ Good - Escaped
$template = '{"name": @json(name)}';

// ❌ Bad - Not escaped (JSON injection)
$template = '{"name": "{name}"}';
```

### Use Proper Number Formatting

```php
// ✅ Good - No thousands separator
$template = '{"price": @number(price, 2, ".", "")}';

// ❌ Bad - Has comma separator (invalid JSON)
$template = '{"price": @number(price, 2)}';
```

### Handle Trailing Commas in Arrays

```php
// ✅ Good - Conditional comma
$template = '@for(items as item, index)
{...}@if(index < length(items) - 1),@end
@end';

// ❌ Bad - Always adds comma (invalid JSON)
$template = '@for(items as item){...},@end';
```

### Set Correct Content-Type Header

```php
header('Content-Type: application/json; charset=utf-8');
echo $tsuku->process($template, $data);
```

## Testing JSON Output

```php
// Validate JSON
$json = $tsuku->process($template, $data);
$decoded = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    throw new \RuntimeException('Invalid JSON: ' . json_last_error_msg());
}
```

## Next Steps

- [Configuration Files →](/examples/configuration-files) - YAML/TOML generation
- [Security →](/guide/security) - JSON injection prevention
- [Built-in Functions →](/guide/built-in-functions) - JSON escaping
