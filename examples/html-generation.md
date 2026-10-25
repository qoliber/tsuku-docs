# HTML Generation

Generate HTML from complex object structures with proper XSS protection and accessibility.

## Overview

While Tsuku is primarily designed for data transformation (CSV, XML, JSON), it can also generate HTML when needed. The key difference from traditional HTML template engines (Twig, Blade) is that Tsuku focuses on **generating HTML from structured data** rather than full page rendering.

## When to Use Tsuku for HTML

**✅ Good use cases:**
- Product cards from product data
- Email templates (HTML emails)
- Component HTML from API data
- Widget HTML generation
- Partial HTML fragments
- HTML exports from data

**❌ Better alternatives:**
- Full page templates → Use Twig/Blade
- Server-side rendering → Use framework templates
- Complex view logic → Use dedicated view engines

## Basic HTML Generation

### Simple Product Card

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

$template = '<div class="product">
  <h3>@html(product.name)</h3>
  <p class="price">$@number(product.price, 2)</p>
@if(product.stock > 0)
  <span class="in-stock">In Stock</span>
@else
  <span class="out-of-stock">Out of Stock</span>
@end
</div>';

$data = [
    'product' => [
        'name' => 'Premium Widget "Pro"',
        'price' => 99.99,
        'stock' => 5
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```html
<div class="product">
  <h3>Premium Widget &quot;Pro&quot;</h3>
  <p class="price">$99.99</p>
  <span class="in-stock">In Stock</span>
</div>
```

## XSS Protection

**Always escape user input** with `@html()`:

```php
$template = '<div class="comment">
  <div class="author">@html(comment.author)</div>
  <div class="body">@html(comment.body)</div>
</div>';

$data = [
    'comment' => [
        'author' => 'John <Admin>',
        'body' => '<script>alert("XSS")</script>'
    ]
];

echo $tsuku->process($template, $data);
// Output (safe):
// <div class="comment">
//   <div class="author">John &lt;Admin&gt;</div>
//   <div class="body">&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;</div>
// </div>
```

Tsuku never escapes on its own: `{comment.body}` would print the `<script>` tag unchanged. Escaping happens only where you call `@html()`.

## Complex Examples

### Product Grid with Ratings

```php
$template = '<div class="product-grid">
@for(products as product)
  <article class="product-card">
    <img src="@html(product.image)" alt="@html(product.name)">
    <h3>@html(product.name)</h3>
    <div class="rating">
@for(product.rating.stars as star)
      <span class="star @?{star "filled" : "empty"}">★</span>
@end
      <span class="count">({product.rating.count})</span>
    </div>
    <div class="price">
@if(product.sale_price)
      <span class="old">$@number(product.regular_price, 2)</span>
      <span class="sale">$@number(product.sale_price, 2)</span>
@else
      <span>$@number(product.regular_price, 2)</span>
@end
    </div>
@if(product.stock > 0)
    <button class="btn-primary">Add to Cart</button>
@else
    <button class="btn-secondary" disabled>Out of Stock</button>
@end
  </article>
@end
</div>';

$data = [
    'products' => [
        [
            'image' => '/img/widget.jpg',
            'name' => 'Widget "Pro"',
            'rating' => ['stars' => [true, true, true, true, false], 'count' => 128],
            'regular_price' => 129.99,
            'sale_price' => 99.99,
            'stock' => 5,
        ],
        [
            'image' => '/img/gadget.jpg',
            'name' => 'Gadget',
            'rating' => ['stars' => [true, true, true, false, false], 'count' => 12],
            'regular_price' => 49.99,
            'sale_price' => null,
            'stock' => 0,
        ],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```html
<div class="product-grid">
  <article class="product-card">
    <img src="/img/widget.jpg" alt="Widget &quot;Pro&quot;">
    <h3>Widget &quot;Pro&quot;</h3>
    <div class="rating">
      <span class="star filled">★</span>
      <span class="star filled">★</span>
      <span class="star filled">★</span>
      <span class="star filled">★</span>
      <span class="star empty">★</span>
      <span class="count">(128)</span>
    </div>
    <div class="price">
      <span class="old">$129.99</span>
      <span class="sale">$99.99</span>
    </div>
    <button class="btn-primary">Add to Cart</button>
  </article>
  <article class="product-card">
    <img src="/img/gadget.jpg" alt="Gadget">
    <h3>Gadget</h3>
    <div class="rating">
      <span class="star filled">★</span>
      <span class="star filled">★</span>
      <span class="star filled">★</span>
      <span class="star empty">★</span>
      <span class="star empty">★</span>
      <span class="count">(12)</span>
    </div>
    <div class="price">
      <span>$49.99</span>
    </div>
    <button class="btn-secondary" disabled>Out of Stock</button>
  </article>
</div>
```

The stars are a list of booleans prepared in PHP (for example from the average rating): templates have no arithmetic, so a loop like "five stars, filled up to the rating" has to be prepared in the data.

### User Dashboard

```php
$template = '<div class="dashboard">
  <header class="profile">
    <img src="@html(user.avatar)" alt="@html(user.name)">
    <h1>@html(user.name)</h1>
    <p>@html(user.bio)</p>
  </header>

  <section class="orders">
    <h2>Recent Orders</h2>
    <table>
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Date</th>
          <th>Total</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
@for(user.orders as order)
        <tr>
          <td>@html(order.id)</td>
          <td>@date("M d, Y", order.date)</td>
          <td>$@number(order.total, 2)</td>
          <td>
            <span class="status-@html(order.status)">
              @match(order.status)
              @case("completed")
              ✓ Completed
              @case("pending")
              ⏳ Pending
              @default
              Unknown
              @end
            </span>
          </td>
        </tr>
@end
      </tbody>
    </table>
  </section>
</div>';

$data = [
    'user' => [
        'avatar' => '/avatars/jane.png',
        'name' => 'Jane Smith',
        'bio' => 'Gardener & cook',
        'orders' => [
            ['id' => 'ORD-1001', 'date' => strtotime('2024-01-15'), 'total' => 59.98, 'status' => 'completed'],
            ['id' => 'ORD-1002', 'date' => strtotime('2024-02-03'), 'total' => 1250, 'status' => 'pending'],
        ],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```html
<div class="dashboard">
  <header class="profile">
    <img src="/avatars/jane.png" alt="Jane Smith">
    <h1>Jane Smith</h1>
    <p>Gardener &amp; cook</p>
  </header>

  <section class="orders">
    <h2>Recent Orders</h2>
    <table>
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Date</th>
          <th>Total</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>ORD-1001</td>
          <td>Jan 15, 2024</td>
          <td>$59.98</td>
          <td>
            <span class="status-completed">
              ✓ Completed
            </span>
          </td>
        </tr>
        <tr>
          <td>ORD-1002</td>
          <td>Feb 03, 2024</td>
          <td>$1,250.00</td>
          <td>
            <span class="status-pending">
              ⏳ Pending
            </span>
          </td>
        </tr>
      </tbody>
    </table>
  </section>
</div>
```

Lines that hold only a directive (`@match`, `@case`, `@default`, `@end`, ...) print nothing - not even their indentation - so they can be indented like the HTML around them. Each content line keeps its own indentation, which is why the case texts are indented as well. See [Whitespace Control](/guide/whitespace-control).

### Blog Article List

```php
$template = '<div class="blog-posts">
@for(posts as post)
  <article>
    <header>
@if(post.image)
      <img src="@html(post.image)" alt="@html(post.title)">
@end
      <h2><a href="@html(post.url)">@html(post.title)</a></h2>
      <div class="meta">
        <span>By @html(post.author.name)</span>
        <time datetime="@date("Y-m-d", post.published)">
          @date("F j, Y", post.published)
        </time>
        <span>{post.read_time} min read</span>
      </div>
    </header>
    <p>@html(post.excerpt)</p>
@if(post.tags)
    <div class="tags">
@for(post.tags as tag)
      <a href="/tag/@url(tag.slug)">@html(tag.name)</a>
@end
    </div>
@end
    <footer>
      <span>👁 {post.views} views</span>
      <span>💬 {post.comments} comments</span>
    </footer>
  </article>
@end
</div>';

$data = [
    'posts' => [
        [
            'image' => '',
            'url' => '/blog/whitespace',
            'title' => 'Whitespace & Templates',
            'author' => ['name' => 'John Doe'],
            'published' => strtotime('2024-03-05'),
            'read_time' => 4,
            'excerpt' => 'Why <pre> matters',
            'tags' => [['slug' => 'php tips', 'name' => 'PHP Tips']],
            'views' => 1200,
            'comments' => 3,
        ],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```html
<div class="blog-posts">
  <article>
    <header>
      <h2><a href="/blog/whitespace">Whitespace &amp; Templates</a></h2>
      <div class="meta">
        <span>By John Doe</span>
        <time datetime="2024-03-05">
          March 5, 2024
        </time>
        <span>4 min read</span>
      </div>
    </header>
    <p>Why &lt;pre&gt; matters</p>
    <div class="tags">
      <a href="/tag/php%20tips">PHP Tips</a>
    </div>
    <footer>
      <span>👁 1200 views</span>
      <span>💬 3 comments</span>
    </footer>
  </article>
</div>
```

An empty string counts as false, so the post without an image gets no `<img>` tag.

## HTML Email Templates

Tsuku is perfect for generating HTML emails:

```php
$template = '<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>@html(email.subject)</title>
</head>
<body style="font-family: Arial, sans-serif;">
  <div style="max-width: 600px; margin: 0 auto;">
    <h1>@html(email.title)</h1>
    <p>Hi @html(customer.name),</p>
    <p>@html(email.message)</p>

    <table style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr>
          <th style="border: 1px solid #ddd; padding: 8px;">Product</th>
          <th style="border: 1px solid #ddd; padding: 8px;">Qty</th>
          <th style="border: 1px solid #ddd; padding: 8px;">Price</th>
        </tr>
      </thead>
      <tbody>
@for(order.items as item)
        <tr>
          <td style="border: 1px solid #ddd; padding: 8px;">@html(item.name)</td>
          <td style="border: 1px solid #ddd; padding: 8px;">{item.quantity}</td>
          <td style="border: 1px solid #ddd; padding: 8px;">$@number(item.price, 2)</td>
        </tr>
@end
      </tbody>
    </table>

    <p><strong>Total: $@number(order.total, 2)</strong></p>

    <p>Thank you for your order!</p>
  </div>
</body>
</html>';

$data = [
    'email' => [
        'subject' => 'Your order ORD-1001',
        'title' => 'Thanks for your order!',
        'message' => 'We are packing it now & will send it tomorrow.',
    ],
    'customer' => ['name' => 'Jane Smith'],
    'order' => [
        'items' => [
            ['name' => 'Widget', 'quantity' => 2, 'price' => 29.99],
            ['name' => 'Gadget', 'quantity' => 1, 'price' => 49.99],
        ],
        'total' => 109.97, // calculated in PHP: templates have no arithmetic
    ],
];

$html = $tsuku->process($template, $data);
```

## Forms with Dynamic Fields

```php
$template = '<form method="post" action="@html(form.action)">
@for(form.fields as field)
  <div class="form-group">
    <label for="@html(field.name)">
      @html(field.label)
@if(field.required)
      <span class="required">*</span>
@end
    </label>

@match(field.type)
@case("text", "email", "tel")
    <input type="@html(field.type)"
           id="@html(field.name)"
           name="@html(field.name)"
           placeholder="@html(field.placeholder)"
           @?{field.required "required" : ""}>
@case("select")
    <select id="@html(field.name)" name="@html(field.name)">
      <option value="">@html(field.placeholder)</option>
@for(field.options as option)
      <option value="@html(option.value)">@html(option.label)</option>
@end
    </select>
@case("textarea")
    <textarea id="@html(field.name)"
              name="@html(field.name)"
              placeholder="@html(field.placeholder)"></textarea>
@end

@if(field.help)
    <small>@html(field.help)</small>
@end
  </div>
@end
  <button type="submit">Submit</button>
</form>';

$data = [
    'form' => [
        'action' => '/checkout',
        'fields' => [
            [
                'name' => 'email',
                'label' => 'Email',
                'type' => 'email',
                'placeholder' => 'you@example.com',
                'required' => true,
                'help' => 'We send the receipt here',
            ],
            [
                'name' => 'country',
                'label' => 'Country',
                'type' => 'select',
                'placeholder' => 'Choose...',
                'required' => false,
                'options' => [
                    ['value' => 'nl', 'label' => 'Netherlands'],
                    ['value' => 'pl', 'label' => 'Poland'],
                ],
            ],
        ],
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```html
<form method="post" action="/checkout">
  <div class="form-group">
    <label for="email">
      Email
      <span class="required">*</span>
    </label>

    <input type="email"
           id="email"
           name="email"
           placeholder="you@example.com"
           required>

    <small>We send the receipt here</small>
  </div>
  <div class="form-group">
    <label for="country">
      Country
    </label>

    <select id="country" name="country">
      <option value="">Choose...</option>
      <option value="nl">Netherlands</option>
      <option value="pl">Poland</option>
    </select>

  </div>
  <button type="submit">Submit</button>
</form>
```

The `@` in `you@example.com` comes from the data, so it needs no escaping. Typed into the template itself it would have to be written `@@` - see [Escapes & Special Characters](/guide/escaping).

## Accessibility

Always include proper accessibility attributes:

```php
$template = '<article class="product" role="article" aria-labelledby="product-{product.id}">
  <h3 id="product-{product.id}">@html(product.name)</h3>
  <img src="@html(product.image)"
       alt="@html(product.image_alt)"
       role="img">
  <div class="price" aria-label="Price">
    $@number(product.price, 2)
  </div>
@if(product.stock > 0)
  <button aria-label="Add @html(product.name) to cart">
    Add to Cart
  </button>
@else
  <span role="status" aria-live="polite">Out of Stock</span>
@end
</article>';

$data = [
    'product' => [
        'id' => 42,
        'name' => 'Widget',
        'image' => '/img/widget.jpg',
        'image_alt' => 'Blue widget, front view',
        'price' => 19.99,
        'stock' => 3,
    ]
];

echo $tsuku->process($template, $data);
```

**Output:**
```html
<article class="product" role="article" aria-labelledby="product-42">
  <h3 id="product-42">Widget</h3>
  <img src="/img/widget.jpg"
       alt="Blue widget, front view"
       role="img">
  <div class="price" aria-label="Price">
    $19.99
  </div>
  <button aria-label="Add Widget to cart">
    Add to Cart
  </button>
</article>
```

## Security Best Practices

### ✅ DO: Always escape user input

```html
<div class="user-comment">
  <p>@html(comment.body)</p>
  <span>By @html(comment.author)</span>
</div>
```

### ❌ DON'T: Use raw variables

```html
<!-- DANGEROUS - XSS vulnerability -->
<div class="user-comment">
  <p>{comment.body}</p>  <!-- BAD! -->
</div>
```

### ✅ DO: Escape attributes

```html
<img src="@html(product.image)" alt="@html(product.name)">
<a href="@html(product.url)" title="@html(product.title)">Link</a>
```

### ✅ DO: Use URL encoding for URLs

```html
<a href="/search?q=@url(search_term)">Search</a>
<a href="/category/@url(category.slug)">Category</a>
```

## Complete Example

See **[examples/10-html-generation.php](https://github.com/qoliber/tsuku/blob/main/examples/10-html-generation.php)** for complete, runnable examples including:

1. **Product Cards** - Complex product cards with images, ratings, badges
2. **User Dashboard** - Profile header, stats, orders table, addresses
3. **Blog Posts** - Article list with author info, tags, and metadata
4. **Checkout Form** - Dynamic form fields with validation

## When Not to Use Tsuku

For complex web applications with:
- Full page layouts
- Template inheritance
- Includes/partials
- Asset management
- View composers

**Use instead:** Twig, Blade, or your framework's template engine.

## Next Steps

- [Security & Escaping →](/guide/security) - Learn about XSS protection
- [Built-in Functions →](/guide/built-in-functions) - All available functions
- [JSON APIs →](/examples/json-apis) - Alternative data format
- [CSV Exports →](/examples/csv-exports) - Data export format
