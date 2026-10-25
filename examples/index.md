# Examples

Practical templates for the formats Tsuku is used for most: CSV and XML feeds, JSON responses, HTML fragments and emails, configuration files and e-commerce documents. Every example on these pages is real: the template, the data and the output are files, and the output is produced by running the template with Tsuku 1.4.

## How the examples work

Each example has four tabs:

| Tab | What it shows |
|-----|---------------|
| **Template** | The template file, `template.<format>.tsuku`, exactly as Tsuku reads it |
| **Data** | `data.php`, which returns the `$data` array |
| **Output** | What the template renders with that data - generated, never edited by hand |
| **PHP** | The call: `echo $tsuku->process($template, $data);`, plus any function the example registers |

Run one yourself with a script like this:

```php
<?php

use Qoliber\Tsuku\Tsuku;

require 'vendor/autoload.php';

$tsuku = new Tsuku();
$template = file_get_contents('template.csv.tsuku'); // the Template tab
$data = require 'data.php';                          // the Data tab

echo $tsuku->process($template, $data);              // prints the Output tab
```

Dates are formatted in UTC. The sources of all examples are in [`snippets/examples`](https://github.com/qoliber/tsuku-docs/tree/main/snippets/examples) of the documentation repository; `scripts/render-examples.php` renders them, and the site is not published when an output no longer matches its template.

## Example pages

### [CSV Exports →](/examples/csv-exports)
A product catalog with escaping and line continuation, a Magento import file, orders with line items, a stock report, optional columns, a semicolon-separated file and an export that is safe to open in Excel.

### [XML Generation →](/examples/xml-generation)
A Google Shopping feed, a sitemap with language alternates, an RSS feed with CDATA content, a SOAP request, an SVG chart and a feed streamed row by row.

### [JSON APIs →](/examples/json-apis)
A paginated product list, an order webhook, JSON-LD product data, a validation error response and a nested category tree.

### [HTML Generation →](/examples/html-generation)
A product grid, customer reviews, an order history table, an order confirmation email and a checkout form - each with a rendered preview.

### [Configuration Files →](/examples/configuration-files)
Application settings in YAML, Docker Compose, a Kubernetes deployment, a `.env` file, an nginx server block and a `Cargo.toml`.

### [E-commerce →](/examples/ecommerce)
A fixed-width invoice, a shipping label, a marketplace feed, an abandoned-cart email and a price list per customer group.

## Quick examples

### CSV export

`@csv()` quotes a value only when it needs it: `Wholesale, EU` contains a comma. `@?{...}` turns a boolean into `yes` / `no`.

::: code-group
<<< @/snippets/examples/index/csv/template.csv.tsuku{tsuku} [Template]
<<< @/snippets/examples/index/csv/data.php [Data]
<<< @/snippets/examples/index/csv/output.csv [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

### XML product feed

`@xml()` escapes the `&` in the product name. The `@match` starts after `<stock>` and its `@end` is followed by `</stock>`, so it is an *inline* block: the line breaks and indentation around each case are dropped and the value lands between the tags.

::: code-group
<<< @/snippets/examples/index/xml/template.xml.tsuku{tsuku-xml} [Template]
<<< @/snippets/examples/index/xml/data.php [Data]
<<< @/snippets/examples/index/xml/output.xml [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

### JSON response

`@json()` prints a complete JSON string, quotes included, so it is not wrapped in `"..."`. The array uses a separator in front of every item except the first (`@if(index > 0), @end`), because JSON allows no trailing comma.

::: code-group
<<< @/snippets/examples/index/json/template.json.tsuku{tsuku-json} [Template]
<<< @/snippets/examples/index/json/data.php [Data]
<<< @/snippets/examples/index/json/output.json [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

### Indented conditions

Lines that hold only directives print nothing - not even their indentation - so directives can be indented like the text they control. Each content line keeps its own indentation. See [Whitespace Control](/guide/whitespace-control).

::: code-group
<<< @/snippets/examples/index/conditions/template.txt.tsuku{tsuku} [Template]
<<< @/snippets/examples/index/conditions/data.php [Data]
<<< @/snippets/examples/index/conditions/output.txt [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

### A registered function

Formatting that the built-in functions do not cover goes into a PHP function registered under a name; the template calls it like any other function. See [Custom Functions](/guide/custom-functions).

::: code-group
<<< @/snippets/examples/index/custom-function/template.txt.tsuku{tsuku} [Template]
<<< @/snippets/examples/index/custom-function/data.php [Data]
<<< @/snippets/examples/index/custom-function/output.txt [Output]
<<< @/snippets/examples/index/custom-function/example.php [PHP]
:::

## Using Tsuku in an application

Keep templates in files next to the code that fills them, and build plain arrays in PHP: the template only places values, it does not query anything.

### Magento 2

```php
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Qoliber\Tsuku\Tsuku;

class ProductCsvExport
{
    public function __construct(
        private readonly Tsuku $tsuku,
        private readonly CollectionFactory $productCollectionFactory,
    ) {
    }

    public function export(): string
    {
        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect(['name', 'description', 'price'])
            ->addAttributeToFilter('status', 1);

        $products = [];
        foreach ($collection as $product) {
            $products[] = [
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => (float) $product->getPrice(),
                'qty' => (int) $product->getExtensionAttributes()->getStockItem()?->getQty(),
            ];
        }

        // the product catalog template from the CSV Exports page
        $template = file_get_contents(__DIR__ . '/templates/products.csv.tsuku');

        return $this->tsuku->process($template, ['products' => $products]);
    }
}
```

The template is the [product catalog](/examples/csv-exports#product-catalog) from the CSV page. For catalogs with many thousands of products, render with [`processToStream()`](/guide/streaming) instead.

### Laravel

```php
use Illuminate\Http\Response;
use Qoliber\Tsuku\Tsuku;

class SitemapController
{
    public function __invoke(Tsuku $tsuku): Response
    {
        $pages = Page::query()->where('published', true)->get()
            ->map(fn (Page $page) => [
                'loc' => url($page->path),
                'updated_at' => $page->updated_at->getTimestamp(),
                'alternates' => $page->translations->pluck('url', 'locale')->all(),
            ])
            ->all();

        $xml = $tsuku->process(file_get_contents(resource_path('feeds/sitemap.xml.tsuku')), ['pages' => $pages]);

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
```

The template is the [sitemap](/examples/xml-generation#sitemap-with-language-alternates) from the XML page.

## Next Steps

- [Getting Started →](/guide/getting-started) - install Tsuku and render a first template
- [Whitespace Control →](/guide/whitespace-control) - why indented templates render clean output
- [Built-in Functions →](/guide/built-in-functions) - escaping, numbers, dates, lists
