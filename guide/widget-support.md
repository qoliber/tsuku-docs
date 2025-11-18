# Widget Support

Tsuku's custom functions can be used to create Magento-style widgets and reusable components.

## Magento Widget Compatibility

While Tsuku doesn't support Magento's `{{widget}}` syntax natively, you can achieve the same functionality with custom functions.

### Magento Syntax vs Tsuku

**Magento:**
```
{{widget class="Magento\Cms\Block\Widget\Block" template="widget/static_block/default.phtml" block_id="footer_links"}}
```

**Tsuku Equivalent:**
```php
@cms_block("footer_links", "widget/static_block/default.phtml")
```

## Creating Widget Functions

### CMS Block Widget

```php
$tsuku->registerFunction('cms_block', function(string $blockId, ?string $template = null): string {
    // Load CMS block from database/cache
    $block = getCmsBlockById($blockId);

    if (!$block) {
        return "<!-- CMS Block '{$blockId}' not found -->";
    }

    return $block['content'];
});

// Usage
$template = '<footer>
@cms_block("footer_links")
</footer>';
```

### Product Widget

```php
$tsuku->registerFunction('product_widget', function(string $category, int $limit = 5): string {
    $products = getProductsByCategory($category, $limit);

    $html = '<div class="widget-product-list">';
    $html .= "<h3>Products in {$category}</h3>";
    foreach ($products as $product) {
        $html .= sprintf(
            '<div class="product"><h4>%s</h4><p>$%s</p></div>',
            htmlspecialchars($product['name']),
            number_format($product['price'], 2)
        );
    }
    $html .= '</div>';

    return $html;
});

// Usage
$template = '@product_widget("Electronics", 10)';
```

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
```

## Generic Widget Handler

Create a generic widget system similar to Magento:

```php
$tsuku->registerFunction('widget', function(string $type, ...$params): string {
    return match($type) {
        'cms_block' => renderCmsBlock($params[0] ?? ''),
        'product_list' => renderProductList($params[0] ?? '', $params[1] ?? 5),
        'newsletter' => renderNewsletter($params[0] ?? 'Subscribe'),
        'banner' => renderBanner($params[0] ?? '', $params[1] ?? '', $params[2] ?? '#'),
        default => "<!-- Unknown widget: {$type} -->",
    };
});

// Usage
$template = '@widget("cms_block", "footer_links")
@widget("product_list", "Electronics", 10)
@widget("newsletter", "Stay Updated")';
```

## Conditional Widgets

Use widgets with conditionals:

```php
$template = '<div class="sidebar">
@if(show_newsletter)
@newsletter_widget("Subscribe Now")
@end

@if(featured_category)
@product_widget(featured_category, 5)
@end
</div>';

$data = [
    'show_newsletter' => true,
    'featured_category' => 'Electronics',
];
```

## Widgets in Loops

```php
$template = '@for(sections as section)
<section>
    <h2>{section.title}</h2>
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
```

## Magento Integration Example

Complete example integrating with Magento 2:

```php
use Qoliber\Tsuku\Tsuku;
use Magento\Framework\App\ObjectManager;

$tsuku = new Tsuku();

// Register Magento widget functions
$tsuku->registerFunction('cms_block', function(string $blockId) {
    $objectManager = ObjectManager::getInstance();
    $blockRepository = $objectManager->get(\Magento\Cms\Api\BlockRepositoryInterface::class);

    try {
        $block = $blockRepository->getById($blockId);
        return $block->getContent();
    } catch (\Exception $e) {
        return "<!-- CMS Block not found: {$blockId} -->";
    }
});

$tsuku->registerFunction('product_collection', function(string $category, int $limit = 5) {
    $objectManager = ObjectManager::getInstance();
    $categoryRepository = $objectManager->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);
    $productCollection = $objectManager->get(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);

    // Load products by category
    $category = $categoryRepository->get($category);
    $products = $productCollection
        ->addCategoryFilter($category)
        ->setPageSize($limit)
        ->load();

    $html = '<div class="product-collection">';
    foreach ($products as $product) {
        $html .= sprintf(
            '<div class="product"><h3>%s</h3><p>$%s</p></div>',
            $product->getName(),
            number_format($product->getFinalPrice(), 2)
        );
    }
    $html .= '</div>';

    return $html;
});

// Use in templates
$template = '<div class="homepage">
@cms_block("header_promo")

<main>
    @product_collection("electronics", 8)
</main>

<footer>
    @cms_block("footer_info")
</footer>
</div>';

$result = $tsuku->process($template, []);
```

## Best Practices

### 1. Escape Output

Always escape user-provided data:

```php
$tsuku->registerFunction('custom_widget', function(string $userInput): string {
    $safe = htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
    return "<div>{$safe}</div>";
});
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

Handle missing data gracefully:

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

## Converting Magento Templates

If migrating from Magento, convert widgets:

**Before (Magento):**
```
<div class="footer">
    {{widget class="Magento\Cms\Block\Widget\Block" block_id="footer_links"}}
    {{widget class="Magento\Newsletter\Block\Subscribe" template="subscribe.phtml"}}
</div>
```

**After (Tsuku):**
```php
$template = '<div class="footer">
@cms_block("footer_links")
@newsletter_widget("subscribe.phtml")
</div>';
```

## Next Steps

- [Custom Functions →](/guide/custom-functions) - Learn function basics
- [Security →](/guide/security) - Secure your widgets
- [Examples →](/examples/ecommerce) - See widgets in e-commerce
