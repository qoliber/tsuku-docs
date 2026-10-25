<script setup>
import svgChart from '../snippets/examples/xml-generation/svg-chart/output.svg?raw'
</script>

# XML Generation

Feeds, sitemaps and API requests. In every example, text from the data goes through `@xml(value)`, which escapes `& < > " '` and is therefore safe in element text and in attribute values; prices use `@number(value, 2, ".", "")` so that `1249.50` never becomes `1,249.50`. Directive lines are indented with the elements they control and print nothing.

Each example has the template, the data, the generated output and the PHP call ([how the examples work](/examples/#how-the-examples-work)).

## Google Shopping feed

An RSS 2.0 feed with the `g:` namespace, as Google Merchant Center reads it. Extra images come from a nested loop; `g:sale_price` is only written when it is lower than the regular price (`and`); a product without a GTIN gets a `g:mpn` instead. The availability is an inline `@if` / `@elseif` / `@else` block: content comes before the `@if` and after the `@end`, so the line breaks around each branch are dropped and the value sits between the tags. `@join(product.categories, " > ")` builds the product type, and `@xml()` escapes the `>`.

::: code-group
<<< @/snippets/examples/xml-generation/google-shopping/template.xml.tsuku{tsuku-xml} [Template]
<<< @/snippets/examples/xml-generation/google-shopping/data.php [Data]
<<< @/snippets/examples/xml-generation/google-shopping/output.xml [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## Sitemap with language alternates

`@for(page.alternates as href, language)` loops over an associative array: the second variable is the key, here the language code. A page without translations gets no `xhtml:link` lines. The `&` in the second URL becomes `&amp;`, as the sitemap protocol requires.

::: code-group
<<< @/snippets/examples/xml-generation/sitemap/template.xml.tsuku{tsuku-xml} [Template]
<<< @/snippets/examples/xml-generation/sitemap/data.php [Data]
<<< @/snippets/examples/xml-generation/sitemap/output.xml [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## RSS feed with CDATA

The article body is HTML. Escaped with `@xml()` it would be valid too, but many feed readers expect it in a CDATA section, so the example registers a block directive: `@cdata ... @end` wraps whatever its body renders and splits a `]]>` in the data so the section cannot end early (PHP tab). Text inside CDATA is not escaped. See [Custom Directives](/guide/custom-directives).

::: code-group
<<< @/snippets/examples/xml-generation/rss-feed/template.xml.tsuku{tsuku-xml} [Template]
<<< @/snippets/examples/xml-generation/rss-feed/data.php [Data]
<<< @/snippets/examples/xml-generation/rss-feed/output.xml [Output]
<<< @/snippets/examples/xml-generation/rss-feed/example.php [PHP]
:::

## SOAP request

A sales order sent to an ERP. The line numbers come from the array keys: the items are keyed from `1`, and `@for(order.items as item, line)` hands over the key. `@date("c", ...)` writes an ISO 8601 timestamp, and the `<erp:Remarks>` element is left out when there is no comment.

::: code-group
<<< @/snippets/examples/xml-generation/soap-request/template.xml.tsuku{tsuku-xml} [Template]
<<< @/snippets/examples/xml-generation/soap-request/data.php [Data]
<<< @/snippets/examples/xml-generation/soap-request/output.xml [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## SVG chart

Templates have no arithmetic - `{index * 50}` is printed as text - so PHP computes the bar positions and the template only places them. `@?{bar.best "#3451b2" : "#8ea2e8"}` picks the colour of the best month.

::: code-group
<<< @/snippets/examples/xml-generation/svg-chart/template.svg.tsuku{tsuku-xml} [Template]
<<< @/snippets/examples/xml-generation/svg-chart/data.php [Data]
<<< @/snippets/examples/xml-generation/svg-chart/output.svg{xml} [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

<HtmlPreview :html="svgChart" />

## Streaming a large feed

`process()` builds the whole document in memory. `processToStream()` renders the part before the `@for` once, then the loop body once per row, then the rest, and hands each piece to a callback, so memory use does not grow with the catalog. The rows can come from a generator; the template needs exactly one top-level `@for` over the streamed collection. See [Streaming Large Datasets](/guide/streaming).

::: code-group
<<< @/snippets/examples/xml-generation/streaming-feed/template.xml.tsuku{tsuku-xml} [Template]
<<< @/snippets/examples/xml-generation/streaming-feed/data.php [Data]
<<< @/snippets/examples/xml-generation/streaming-feed/output.xml [Output]
<<< @/snippets/examples/xml-generation/streaming-feed/example.php [PHP]
:::

## Checking the result

A missing `@xml()` or an unbalanced tag only shows up when a consumer rejects the file. Parse generated feeds in a test:

```php
$xml = $tsuku->process($template, $data);

$document = new DOMDocument();
if (!$document->loadXML($xml)) {
    throw new RuntimeException('The feed is not well-formed XML');
}
```

## Next Steps

- [JSON APIs →](/examples/json-apis) - the same data as JSON
- [Streaming Large Datasets →](/guide/streaming) - feeds with hundreds of thousands of rows
- [Custom Directives →](/guide/custom-directives) - block directives such as `@cdata`
