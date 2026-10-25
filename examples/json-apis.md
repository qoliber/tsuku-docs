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
        "active": @?{user.active "true" : "false"}
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
        "name": "John \u0022The Developer\u0022 Doe",
        "email": "john@example.com",
        "role": "admin",
        "active": true
    }
}
```

`@json()` returns a complete, quoted JSON string, so it is never wrapped in quotes in the template. It escapes `"`, `'`, `&`, `<` and `>` as `\u0022`, `\u0027`, `\u0026`, `\u003C` and `\u003E` (and `/` as `\/`) - valid JSON that any decoder turns back into the original text, and safe to embed in HTML.

Booleans come out of an inline condition: `@?{user.active "true" : "false"}`. There are no `true`/`false` literals in Tsuku - a bare `true` in a template is looked up as a variable - so `@case(true)` or `@if(user.active == true)` do not do what they look like (see [Conditionals](/guide/conditionals)).

## Array Responses

### Product List API

```php
$template = '{
    "success": true,
    "total": @length(products),
    "data": [@for(products as product, index)@if(index > 0),@end
        {
            "id": {product.id},
            "sku": @json(product.sku),
            "name": @json(product.name),
            "price": @number(product.price, 2, ".", ""),
            "stock": {product.stock},
            "available": @?{product.stock > 0 "true" : "false"}
        }@end
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

### Commas Between Array Items

JSON does not allow a comma after the last element, and Tsuku has no `loop.last`. Put the separator *in front of* every element except the first instead - the loop index (the array key) is `0` only for the first one:

```
"data": [@for(products as product, index)@if(index > 0),@end
    {
        ...
    }@end
]
```

Each iteration renders `,` (from the second element on), a line break and the element. Writing `@for` and `@if(index > 0),@end` at the end of the `[` line and `@end` right after the element's closing `}` keeps one element per line with the comma after the previous `}`; an empty list renders `[` and `]` on two lines. Nested arrays use the same idiom with their own index variable (see the GraphQL example below).

The index is the array key, so the first element must have the key `0`. Pass `array_values($items)` when the keys are strings or do not start at `0`.

::: warning Arithmetic is not supported
Earlier versions of this page showed `@if(index < length(items) - 1),@end`. That never emitted a comma, and since 1.4.0 it throws a `ParseException` (`Unexpected '-'`): conditions have no arithmetic. Use the leading separator above.
:::

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
        "items": [@for(order.items as item, index)@if(index > 0),@end
            {
                "id": {item.id},
                "name": @json(item.name),
                "sku": @json(item.sku),
                "quantity": {item.qty},
                "price": @number(item.price, 2, ".", ""),
                "total": @number(item.line_total, 2, ".", "")
            }@end
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
        'created_at' => 1705312800, // 2024-01-15 10:00:00 UTC
        'customer' => [
            'id' => 456,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com'
        ],
        'items' => [
            ['id' => 1, 'name' => 'Widget', 'sku' => 'WID-001', 'qty' => 2, 'price' => 29.99, 'line_total' => 59.98],
            ['id' => 2, 'name' => 'Gadget', 'sku' => 'GAD-002', 'qty' => 1, 'price' => 49.99, 'line_total' => 49.99],
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

**Output:**
```json
{
    "order": {
        "id": "ORD-12345",
        "status": "processing",
        "created_at": "2024-01-15T10:00:00+00:00",
        "customer": {
            "id": 456,
            "name": "Jane Smith",
            "email": "jane@example.com"
        },
        "items": [
            {
                "id": 1,
                "name": "Widget",
                "sku": "WID-001",
                "quantity": 2,
                "price": 29.99,
                "total": 59.98
            },
            {
                "id": 2,
                "name": "Gadget",
                "sku": "GAD-002",
                "quantity": 1,
                "price": 49.99,
                "total": 49.99
            }
        ],
        "totals": {
            "subtotal": 109.97,
            "tax": 10.00,
            "shipping": 5.00,
            "total": 124.97
        }
    }
}
```

`@date()` formats with PHP's default timezone (UTC in this output).

::: tip Compute totals in PHP
Templates have no arithmetic - `@number(item.qty * item.price, 2)` is not a multiplication. Put derived values such as `line_total` into the data before rendering (or register a [custom function](/guide/custom-functions) for it).
:::

## Pagination

### Paginated Response

```php
$template = '{
    "data": [@for(items as item, index)@if(index > 0),@end
        {"id": {item.id}, "name": @json(item.name)}@end
    ],
    "pagination": {
        "current_page": {page.current},
        "per_page": {page.per_page},
        "total": {page.total},
        "total_pages": {page.total_pages},
        "has_next": @?{page.current < page.total_pages "true" : "false"},
        "has_prev": @?{page.current > 1 "true" : "false"}
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

`@?{condition "a" : "b"}` takes a full condition. `@match()` does not: it compares a *value* with its `@case` values, so `@match(page.current < page.total_pages)` looks up a variable literally named `page.current < page.total_pages` (always missing) instead of comparing.

## Error Responses

### Error Handling

```php
$template = '{
    "success": false,
    "error": {
        "code": @json(error.code),
        "message": @json(error.message),
        "details": [@for(error.details as detail, index)@if(index > 0),@end
            {
                "field": @json(detail.field),
                "message": @json(detail.message)
            }@end
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

**Output:**
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Invalid input data",
        "details": [
            {
                "field": "email",
                "message": "Invalid email format"
            },
            {
                "field": "password",
                "message": "Password too short"
            }
        ]
    }
}
```

## GraphQL Response

### GraphQL Query Result

```php
$template = '{
    "data": {
        "user":@if(user) {
            "id": {user.id},
            "name": @json(user.name),
            "email": @json(user.email),
            "posts": [@for(user.posts as post, index)@if(index > 0),@end
                {
                    "id": {post.id},
                    "title": @json(post.title),
                    "excerpt": @json(post.excerpt),
                    "comments": [@for(post.comments as comment, cIndex)@if(cIndex > 0),@end
                        {
                            "id": {comment.id},
                            "author": @json(comment.author),
                            "text": @json(comment.text)
                        }@end
                    ]
                }@end
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
                    ['id' => 2, 'author' => 'Sam', 'text' => 'Thanks for sharing'],
                ]
            ],
            [
                'id' => 2,
                'title' => 'Second Post',
                'excerpt' => 'More thoughts',
                'comments' => []
            ],
        ]
    ]
];

header('Content-Type: application/json');
echo $tsuku->process($template, $data);
```

**Output:**
```json
{
    "data": {
        "user": {
            "id": 123,
            "name": "John Doe",
            "email": "john@example.com",
            "posts": [
                {
                    "id": 1,
                    "title": "First Post",
                    "excerpt": "This is my first post",
                    "comments": [
                        {
                            "id": 1,
                            "author": "Jane",
                            "text": "Great post!"
                        },
                        {
                            "id": 2,
                            "author": "Sam",
                            "text": "Thanks for sharing"
                        }
                    ]
                },
                {
                    "id": 2,
                    "title": "Second Post",
                    "excerpt": "More thoughts",
                    "comments": [
                    ]
                }
            ]
        }
    }
}
```

The space after `"user":` lives inside both branches (`@if(user) {` and `@else null`), so a missing user renders `"user": null`.

## Configuration Export

### Export Settings as JSON

```php
$template = '{
    "app": {
        "name": @json(config.app_name),
        "version": @json(config.version),
        "environment": @json(config.env),
        "debug": @?{config.debug "true" : "false"}
    },
    "database": {
        "host": @json(config.db.host),
        "port": {config.db.port},
        "name": @json(config.db.name),
        "charset": @json(config.db.charset)
    },
    "features": {@for(config.features as feature, index)@if(index > 0),@end
        @json(feature.name): @?{feature.enabled "true" : "false"}@end
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

**Output:**
```json
{
    "app": {
        "name": "My Application",
        "version": "1.0.0",
        "environment": "production",
        "debug": false
    },
    "database": {
        "host": "localhost",
        "port": 3306,
        "name": "myapp",
        "charset": "utf8mb4"
    },
    "features": {
        "cache": true,
        "logging": true,
        "profiling": false
    }
}
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
        "categories": [@for(product.categories as category, index)@if(index > 0),@end
            {
                "id": {category.id},
                "name": @json(category.name)
            }@end
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
$data = ['name' => 'Say "hi" & <b>bye</b>'];

// ✅ Good - Escaped
echo $tsuku->process('{"name": @json(name)}', $data);
// Output: {"name": "Say \u0022hi\u0022 \u0026 \u003Cb\u003Ebye\u003C\/b\u003E"}

// ❌ Bad - Not escaped: the quote ends the JSON string (JSON injection)
echo $tsuku->process('{"name": "{name}"}', $data);
// Output: {"name": "Say "hi" & <b>bye</b>"}
```

### Use Proper Number Formatting

```php
$data = ['price' => 1299.5];

// ✅ Good - No thousands separator
echo $tsuku->process('{"price": @number(price, 2, ".", "")}', $data);
// Output: {"price": 1299.50}

// ❌ Bad - Has comma separator (invalid JSON)
echo $tsuku->process('{"price": @number(price, 2)}', $data);
// Output: {"price": 1,299.50}
```

### Handle Trailing Commas in Arrays

```php
$data = ['items' => [['id' => 1], ['id' => 2], ['id' => 3]]];

// ✅ Good - Separator before every item except the first
echo $tsuku->process('[@for(items as item, index)@if(index > 0),@end
  {"id": {item.id}}@end
]', $data);
// Output:
// [
//   {"id": 1},
//   {"id": 2},
//   {"id": 3}
// ]

// ❌ Bad - Always adds a comma (invalid JSON)
echo $tsuku->process('[@for(items as item){"id": {item.id}},@end]', $data);
// Output: [{"id": 1},{"id": 2},{"id": 3},]
```

See [Commas Between Array Items](#commas-between-array-items) for how the idiom works.

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
