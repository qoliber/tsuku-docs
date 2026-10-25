# Widget Support

Tsuku's custom functions and custom block directives can be used to create Magento-style widgets and reusable components.

## Magento Widget Compatibility

::: v-pre
Tsuku does not interpret Magento's `{{widget}}` syntax, and it leaves it alone: `{{widget ...}}`, `{{media ...}}`, `{{trans ...}}` and the other Magento directives pass through **unchanged** (the 1.4.0 changelog confirms this as intended behaviour, not an accident). That gives you two options:
:::

1. **Leave them in the output** and let Magento's template filter expand them later (CMS blocks, CMS pages, e-mails).
2. **Render the widgets with Tsuku** by registering widget functions, as shown on the rest of this page.

### Passing Magento Directives Through

```php
$template = '<div class="footer">
    <p>@html(store.name)</p>
    {{widget type="Magento\Cms\Block\Widget\Block" template="widget/static_block/default.phtml" block_id="footer_links"}}
    <img src="{{media url="wysiwyg/logo.png"}}" alt="">
    <p>{{trans "Thank you, %name!" name=$customer.name}}</p>
</div>';

$data = ['store' => ['name' => 'Fish & Chips Ltd']];
echo $tsuku->process($template, $data);
```

**Output:**
```html
<div class="footer">
    <p>Fish &amp; Chips Ltd</p>
    {{widget type="Magento\Cms\Block\Widget\Block" template="widget/static_block/default.phtml" block_id="footer_links"}}
    <img src="{{media url="wysiwyg/logo.png"}}" alt="">
    <p>{{trans "Thank you, %name!" name=$customer.name}}</p>
</div>
```

::: v-pre
The one exception: `{{name}}` with nothing but a plain variable path inside is `{` + the value of `name` + `}` (`{{customer_name}}` renders `{Ann}`, or `{}` when the variable is missing). Magento directives always have a keyword and attributes (`{{var customer.name}}`, `{{widget type="..."}}`), so they are not affected. See [Escapes & Special Characters](/guide/escaping).
:::

### Magento Syntax vs Tsuku

**Magento:**
```
{{widget type="Magento\Cms\Block\Widget\Block" template="widget/static_block/default.phtml" block_id="footer_links"}}
```

**Tsuku Equivalent:**
```
@cms_block("footer_links", "widget/static_block/default.phtml")
```

## Creating Widget Functions

::: v-pre
A widget is a function that returns HTML. Its return value is printed as it is: Tsuku neither escapes it nor parses it again (`{{...}}` or `@...` inside the returned HTML stays literal text), so escape user data inside the function.
:::

### CMS Block Widget

```php
$tsuku->registerFunction('cms_block', function (string $blockId): string {
    // Stand-in for your CMS storage (database, cache, Magento block repository, ...)
    $blocks = ['footer_links' => '<a href="/about">About</a> | <a href="/contact">Contact</a>'];

    return $blocks[$blockId] ?? "<!-- CMS Block '{$blockId}' not found -->";
});

// Usage
$template = '<footer>
@cms_block("footer_links")
@cms_block("missing_block")
</footer>';

echo $tsuku->process($template, []);
// Output:
// <footer>
// <a href="/about">About</a> | <a href="/contact">Contact</a>
// <!-- CMS Block 'missing_block' not found -->
// </footer>
```

### Product Widget

```php
$tsuku->registerFunction('product_widget', function (string $category, int $limit = 5): string {
    // Stand-in for a product query
    $catalog = [
        'Electronics' => [
            ['name' => 'Headphones', 'price' => 149.99],
            ['name' => 'Smart Watch', 'price' => 399],
            ['name' => 'USB-C Hub', 'price' => 49.5],
        ],
    ];

    $html = '<div class="widget-product-list">';
    $html .= '<h3>Products in ' . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '</h3>';
    foreach (array_slice($catalog[$category] ?? [], 0, $limit) as $product) {
        $html .= sprintf(
            '<div class="product"><h4>%s</h4><p>$%s</p></div>',
            htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'),
            number_format($product['price'], 2)
        );
    }
    $html .= '</div>';

    return $html;
});

// Usage
$template = '@product_widget("Electronics", 2)';
echo $tsuku->process($template, []);
// Output: <div class="widget-product-list"><h3>Products in Electronics</h3><div class="product"><h4>Headphones</h4><p>$149.99</p></div><div class="product"><h4>Smart Watch</h4><p>$399.00</p></div></div>
```

A number written in the template (the `2`) reaches the function as the string `"2"`; the `int` type converts it.

### Newsletter Widget

```php
$tsuku->registerFunction('newsletter_widget', function(string $title = 'Subscribe', string $buttonText = 'Subscribe'): string {
    return <<<HTML
    <div class="widget-newsletter">
        <h4>{$title}</h4>
        <form method="post" action="/newsletter/subscribe">
            <input type="email" name="email" placeholder="Your email" required>
            <button type="submit">{$buttonText}</button>
        </form>
    </div>
    HTML;
});

// Usage
$template = '@newsletter_widget("Stay Updated", "Join Now")';
echo $tsuku->process($template, []);
```

**Output:**
```html
<div class="widget-newsletter">
    <h4>Stay Updated</h4>
    <form method="post" action="/newsletter/subscribe">
        <input type="email" name="email" placeholder="Your email" required>
        <button type="submit">Join Now</button>
    </form>
</div>
```

### Banner Widget

```php
$tsuku->registerFunction('banner_widget', function(string $title, string $image, string $link = '#'): string {
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeImage = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

    return <<<HTML
    <div class="widget-banner">
        <a href="{$safeLink}">
            <img src="{$safeImage}" alt="{$safeTitle}">
        </a>
    </div>
    HTML;
});

// Usage
$template = '@banner_widget("Summer Sale", "/images/summer.jpg", "/sale")';
echo $tsuku->process($template, []);
```

**Output:**
```html
<div class="widget-banner">
    <a href="/sale">
        <img src="/images/summer.jpg" alt="Summer Sale">
    </a>
</div>
```

## Generic Widget Handler

Create a generic widget system similar to Magento, with one function that dispatches on the widget type:

```php
$tsuku->registerFunction('widget', function (string $type, ...$params): string {
    $renderers = [
        'cms_block' => fn(string $id = ''): string => '<div class="cms-block" data-block-id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"></div>',
        'newsletter' => fn(string $title = 'Subscribe'): string => '<div class="widget-newsletter"><h4>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h4></div>',
    ];

    return isset($renderers[$type])
        ? $renderers[$type](...$params)
        : "<!-- Unknown widget: {$type} -->";
});

// Usage
$template = '@widget("cms_block", "footer_links")
@widget("newsletter", "Stay Updated")
@widget("carousel")';

echo $tsuku->process($template, []);
// Output:
// <div class="cms-block" data-block-id="footer_links"></div>
// <div class="widget-newsletter"><h4>Stay Updated</h4></div>
// <!-- Unknown widget: carousel -->
```

## Conditional Widgets

Use widgets with conditionals. The `@if` and `@end` lines produce no output of their own:

```php
// Short versions of the widgets above
$tsuku->registerFunction('newsletter_widget', fn(string $title = 'Subscribe'): string => "<form class=\"newsletter\"><h4>{$title}</h4></form>");
$tsuku->registerFunction('product_widget', fn(string $category, int $limit = 5): string => "<div class=\"products\" data-category=\"{$category}\" data-limit=\"{$limit}\"></div>");

$template = '<div class="sidebar">
@if(show_newsletter)
@newsletter_widget("Subscribe Now")
@end
@if(featured_category)
@product_widget(featured_category, 4)
@end
</div>';

$data = [
    'show_newsletter' => true,
    'featured_category' => 'Electronics',
];
echo $tsuku->process($template, $data);
// Output:
// <div class="sidebar">
// <form class="newsletter"><h4>Subscribe Now</h4></form>
// <div class="products" data-category="Electronics" data-limit="4"></div>
// </div>
```

## Widgets in Loops

Directive-only lines can be indented like the HTML around them; only the content lines (here the widget calls) are printed, with their own indentation:

```php
$tsuku->registerFunction('product_widget', fn(string $category, int $limit = 5): string => "<div class=\"products\" data-category=\"{$category}\" data-limit=\"{$limit}\"></div>");
$tsuku->registerFunction('banner_widget', fn(string $title, string $image, string $link = '#'): string => "<a href=\"{$link}\"><img src=\"{$image}\" alt=\"{$title}\"></a>");
$tsuku->registerFunction('newsletter_widget', fn(string $title = 'Subscribe'): string => "<form class=\"newsletter\"><h4>{$title}</h4></form>");

$template = '@for(sections as section)
<section>
    <h2>@html(section.title)</h2>
    @match(section.type)
    @case("products")
    @product_widget(section.category, section.limit)
    @case("banner")
    @banner_widget(section.title, section.image, section.link)
    @case("newsletter")
    @newsletter_widget(section.title)
    @end
</section>
@end';

$data = ['sections' => [
    ['type' => 'products', 'title' => 'Top Sellers', 'category' => 'Electronics', 'limit' => 4],
    ['type' => 'banner', 'title' => 'Summer Sale', 'image' => '/images/summer.jpg', 'link' => '/sale'],
    ['type' => 'newsletter', 'title' => 'Stay Updated'],
]];
echo $tsuku->process($template, $data);
```

**Output:**
```html
<section>
    <h2>Top Sellers</h2>
    <div class="products" data-category="Electronics" data-limit="4"></div>
</section>
<section>
    <h2>Summer Sale</h2>
    <a href="/sale"><img src="/images/summer.jpg" alt="Summer Sale"></a>
</section>
<section>
    <h2>Stay Updated</h2>
    <form class="newsletter"><h4>Stay Updated</h4></form>
</section>
```

## Widget Containers with Custom Directives

A function widget renders everything itself. When the widget should **wrap template content** - a box with a title around a product list - register a [custom block directive](/guide/custom-directives) (1.4.0). The handler receives the rendered body first, then the arguments:

```php
$tsuku->registerDirective('widget_box', function (string $body, string $title, string $class = 'widget'): string {
    return '<div class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . "\">\n"
        . '<h4>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</h4>\n"
        . $body
        . '</div>';
});

$template = '@widget_box("Top Sellers", "widget widget-products")
@for(products as product)
<p>@html(product.name)</p>
@end
@end';

$data = ['products' => [['name' => 'Headphones'], ['name' => 'Smart Watch']]];
echo $tsuku->process($template, $data);
```

**Output:**
```html
<div class="widget widget-products">
<h4>Top Sellers</h4>
<p>Headphones</p>
<p>Smart Watch</p>
</div>
```

A registered directive name takes precedence over a function with the same name, so do not register `widget_box` as both.

## Magento Integration Example

Complete example integrating with Magento 2. The renderer gets the repositories through constructor injection and registers its widget functions once:

```php
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Qoliber\Tsuku\Tsuku;

class TsukuWidgetRenderer
{
    private Tsuku $tsuku;

    public function __construct(
        private readonly BlockRepositoryInterface $blockRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CollectionFactory $productCollectionFactory,
    ) {
        $this->tsuku = new Tsuku();
        $this->tsuku->registerFunction('cms_block', $this->cmsBlock(...));
        $this->tsuku->registerFunction('product_collection', $this->productCollection(...));
    }

    public function render(string $template, array $data = []): string
    {
        return $this->tsuku->process($template, $data);
    }

    private function cmsBlock(string $identifier): string
    {
        try {
            return (string) $this->blockRepository->getById($identifier)->getContent();
        } catch (NoSuchEntityException) {
            return "<!-- CMS Block not found: {$identifier} -->";
        }
    }

    private function productCollection(int $categoryId, int $limit = 5): string
    {
        $category = $this->categoryRepository->get($categoryId);
        $products = $this->productCollectionFactory->create()
            ->addAttributeToSelect(['name', 'price'])
            ->addCategoryFilter($category)
            ->setPageSize($limit);

        $html = '<div class="product-collection">';
        foreach ($products as $product) {
            $html .= sprintf(
                '<div class="product"><h3>%s</h3><p>$%s</p></div>',
                htmlspecialchars((string) $product->getName(), ENT_QUOTES, 'UTF-8'),
                number_format((float) $product->getPrice(), 2)
            );
        }

        return $html . '</div>';
    }
}

// Use in templates
$html = $renderer->render('<div class="homepage">
@cms_block("header_promo")

<main>
    @product_collection(12, 8)
</main>

<footer>
    @cms_block("footer_info")
</footer>
</div>');
```

::: v-pre
CMS block content often contains `{{widget}}` or `{{media}}` directives itself. Tsuku leaves them in the output, so run the result through Magento's CMS template filter (`Magento\Cms\Model\Template\FilterProvider::getBlockFilter()`) when they should be expanded.
:::

## Best Practices

### 1. Escape Output

Always escape user-provided data:

```php
$tsuku->registerFunction('custom_widget', function(string $userInput): string {
    $safe = htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
    return "<div>{$safe}</div>";
});

$template = '@custom_widget(comment)';
$data = ['comment' => '<script>alert(1)</script>'];
// Output: <div>&lt;script&gt;alert(1)&lt;/script&gt;</div>
```

### 2. Cache Widget Output

For expensive operations, cache results:

```php
$cache = [];

$tsuku->registerFunction('expensive_widget', function(string $id) use (&$cache): string {
    if (isset($cache[$id])) {
        return $cache[$id];
    }

    $result = performExpensiveOperation($id);
    $cache[$id] = $result;

    return $result;
});
```

### 3. Provide Fallbacks

Handle missing data gracefully. A function that throws renders as empty output in the default `SILENT` mode (and throws a `TsukuException` in `STRICT` mode), so return a visible fallback instead of failing silently:

```php
$tsuku->registerFunction('cms_block', function(string $blockId): string {
    $block = getCmsBlock($blockId);

    if (!$block) {
        return '<!-- Block not found -->';
    }

    return $block['content'];
});
```

### 4. Document Widget Parameters

```php
/**
 * Product widget
 *
 * @param string $category Category slug
 * @param int $limit Number of products (default: 5)
 * @param string $template Template to use (default: 'grid')
 * @return string Rendered HTML
 */
$tsuku->registerFunction('product_widget', function(
    string $category,
    int $limit = 5,
    string $template = 'grid'
): string {
    // Implementation
});
```

### 5. Pick Callable Names

Function names are used as `@name(...)`. Names that start with `end`, `else` or `default`, and the directive names `for`, `if`, `unless`, `match`, `case`, `elseif`, can be registered but never called - the template reads them as directives. `@widget(...)`, `@cms_block(...)` and `@product_widget(...)` are fine.

## Converting Magento Templates

::: v-pre
If you want Tsuku to render the widgets itself (instead of passing the `{{widget}}` directives through to Magento), convert them:
:::

**Before (Magento):**
```
<div class="footer">
    {{widget type="Magento\Cms\Block\Widget\Block" block_id="footer_links"}}
    {{widget type="Magento\Newsletter\Block\Subscribe" template="subscribe.phtml"}}
</div>
```

**After (Tsuku):**
```php
$template = '<div class="footer">
@cms_block("footer_links")
@newsletter_widget("Subscribe")
</div>';
```

## Next Steps

- [Custom Functions →](/guide/custom-functions) - Learn function basics
- [Custom Directives →](/guide/custom-directives) - Block directives that wrap content
- [Security →](/guide/security) - Secure your widgets
- [Examples →](/examples/ecommerce) - See widgets in e-commerce
