# JSON APIs

API responses, webhooks and structured data. The rules used in every example below:

- Strings go through `@json(value)`. It prints a complete JSON string, quotes included, so it is never wrapped in `"..."` in the template. It escapes `"`, `'`, `&`, `<` and `>` as `"`, `'`, `&`, `<` and `>`, and `/` as `\/` - valid JSON that decodes to the original text and is safe inside HTML.
- Numbers use `@number(value, 2, ".", "")` (the default thousands separator would make `1,249.50`, which is not a number), integers `{value}`.
- Booleans and `null` come from conditions: `@?{product.qty > 0 "true" : "false"}`. Tsuku has no `true`/`false` literals; a bare `true` in a template is a variable name.

Each example has the template, the data, the generated output and the PHP call ([how the examples work](/examples/#how-the-examples-work)).

## Paginated product list

A list endpoint with a `meta` block. `"next"` is a URL on every page but the last, where it is `null`; the space after `"next":` sits inside both branches so the line reads the same either way. The `tags` array is written on one line with the same comma idiom as `data`.

::: code-group
<<< @/snippets/examples/json-apis/product-list/template.json.tsuku{tsuku-json} [Template]
<<< @/snippets/examples/json-apis/product-list/data.php [Data]
<<< @/snippets/examples/json-apis/product-list/output.json [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

### Commas between array items

JSON allows no comma after the last element, and Tsuku has no `loop.last`. Put the separator *in front of* every element except the first instead - the loop index is `0` only for the first one:

```tsuku-json
"data": [@for(products as product, index)@if(index > 0),@end
  {
    ...
  }@end
]
```

Each iteration renders `,` (from the second element on), a line break and the element; the `@end` right after the element's `}` keeps the comma on the same line as the previous `}`. An empty list renders `[` and `]` on two lines, which is still valid JSON. The index is the array key, so pass `array_values($items)` when the keys are not `0, 1, 2, ...`.

`@if(index < length(items) - 1),@end` does not work: conditions have no arithmetic, and since 1.4.0 the `-` is a `ParseException`.

## Order webhook

An `order.created` event with nested objects and an array of items. Timestamps are strings, so `@date("c", ...)` is wrapped in `@json()`. `"is_guest"` uses `not`, and `"shipment"` is an object or `null` depending on the data.

::: code-group
<<< @/snippets/examples/json-apis/order-webhook/template.json.tsuku{tsuku-json} [Template]
<<< @/snippets/examples/json-apis/order-webhook/data.php [Data]
<<< @/snippets/examples/json-apis/order-webhook/output.json [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## JSON-LD product data

Structured data for search engines, embedded in the product page. JSON-LD keys start with `@`, which is written `@@` in a template: `"@@context"` prints `"@context"`. The availability is an inline `@if` / `@elseif` / `@else` block that ends in front of the `,`, so it renders on one line, and `aggregateRating` is only added when the product has reviews. Because `@json()` escapes `<` and `>`, a `</script>` in a product description cannot close the script tag.

::: code-group
<<< @/snippets/examples/json-apis/json-ld-product/template.html.tsuku{tsuku-html} [Template]
<<< @/snippets/examples/json-apis/json-ld-product/data.php [Data]
<<< @/snippets/examples/json-apis/json-ld-product/output.html [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## Validation error

An error response in the Problem Details format ([RFC 9457](https://www.rfc-editor.org/rfc/rfc9457), `application/problem+json`). The apostrophe in the title and the quotes in a reason are escaped by `@json()`.

::: code-group
<<< @/snippets/examples/json-apis/problem-details/template.json.tsuku{tsuku-json} [Template]
<<< @/snippets/examples/json-apis/problem-details/data.php [Data]
<<< @/snippets/examples/json-apis/problem-details/output.json [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## Category tree

Nested arrays, as a navigation menu for a headless storefront. Each loop has its own index variable (`i`, `j`) for its own commas; the children are written one object per line.

::: code-group
<<< @/snippets/examples/json-apis/category-tree/template.json.tsuku{tsuku-json} [Template]
<<< @/snippets/examples/json-apis/category-tree/data.php [Data]
<<< @/snippets/examples/json-apis/category-tree/output.json [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## Checking the result

Decode generated JSON in a test, so a missing `@json()` or comma fails there and not in a client:

```php
$json = $tsuku->process($template, $data);

json_decode($json, flags: JSON_THROW_ON_ERROR);

header('Content-Type: application/json; charset=utf-8');
echo $json;
```

## Next Steps

- [HTML Generation →](/examples/html-generation) - product cards and emails
- [Loops →](/guide/loops) - loop variables and keys
- [Security & Escaping →](/guide/security) - JSON injection
