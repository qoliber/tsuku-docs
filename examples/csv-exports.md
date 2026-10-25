# CSV Exports

CSV looks simple until a value contains a comma, a quote or a line break, or a price is printed as `1,249.50`. The rules used in every example below:

- Text goes through `@csv(value)`. It wraps the value in double quotes, doubling any `"` inside, when the value contains a comma, a double quote or a line break; other values are printed unchanged.
- Numbers go through `@number(value, 2, ".", "")`. The default `@number(value, 2)` adds a comma as thousands separator, which splits the value into two columns.
- Rows start at the beginning of the line, so directive lines (`@for`, `@if`, `@end`) are not indented here; they print nothing. A `\` at the end of a line joins it with the next one, so a long row can be written over several template lines.

Each example has the template, the data, the generated output and the PHP call ([how the examples work](/examples/#how-the-examples-work)).

## Product catalog

The products contain commas, double quotes and a line break; `@csv()` quotes exactly those fields, and the multi-line description stays one CSV field. The price `1249.50` keeps its full value because the thousands separator is switched off. The row is split over two template lines with `\`.

::: code-group
<<< @/snippets/examples/csv-exports/product-catalog/template.csv.tsuku{tsuku} [Template]
<<< @/snippets/examples/csv-exports/product-catalog/data.php [Data]
<<< @/snippets/examples/csv-exports/product-catalog/output.csv [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## Magento product import

A file for Magento's *System > Data Transfer > Import*. Several category paths go into one column with `@csv(@join(product.categories, ","))`, the `code=value` pairs of `additional_attributes` likewise. `special_price` stays empty when there is none, and `is_in_stock` uses `or`: a product with backorders counts as in stock.

::: code-group
<<< @/snippets/examples/csv-exports/magento-import/template.csv.tsuku{tsuku} [Template]
<<< @/snippets/examples/csv-exports/magento-import/data.php [Data]
<<< @/snippets/examples/csv-exports/magento-import/output.csv [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## Orders with line items

One row per order line, with the order columns filled only on the first line of each order: the inner loop's index is `0` for the first item. The row totals come from PHP, because templates have no arithmetic. `@else,,@end` writes the two empty columns; the body starts right after `@else`, so no space is printed.

::: code-group
<<< @/snippets/examples/csv-exports/order-lines/template.csv.tsuku{tsuku} [Template]
<<< @/snippets/examples/csv-exports/order-lines/data.php [Data]
<<< @/snippets/examples/csv-exports/order-lines/output.csv [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## Stock report

The status column is an `@if` / `@elseif` / `@else` chain that starts at the end of the row. Because content precedes the `@if` on its line, the block is *inline*: the line break and indentation at the start of each branch are dropped, so a branch continues the row, and the line break at its end finishes it. The first branch combines two conditions with `and not`. See [Whitespace Control](/guide/whitespace-control).

::: code-group
<<< @/snippets/examples/csv-exports/stock-report/template.csv.tsuku{tsuku} [Template]
<<< @/snippets/examples/csv-exports/stock-report/data.php [Data]
<<< @/snippets/examples/csv-exports/stock-report/output.csv [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## Optional columns

The same `@if(include_cost)` adds the cost columns to the header and to every row. An `@end` that follows content keeps the line break after it, so every line ends with exactly one line break whether the columns are printed or not; with `include_cost` set to `false` the file has three columns. The margin is calculated in PHP.

::: code-group
<<< @/snippets/examples/csv-exports/conditional-columns/template.csv.tsuku{tsuku} [Template]
<<< @/snippets/examples/csv-exports/conditional-columns/data.php [Data]
<<< @/snippets/examples/csv-exports/conditional-columns/output.csv [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## Semicolon-separated file

German and Dutch spreadsheet settings expect `;` between fields and a decimal comma. `@number(value, 2, ",", ".")` writes `1.249,50`. `@csv()` quotes values with a comma, not with a semicolon, so the example registers `@ssv()`, which quotes for this format (PHP tab). A tab-separated file needs the same kind of function: see the [marketplace feed](/examples/ecommerce#marketplace-feed).

::: code-group
<<< @/snippets/examples/csv-exports/semicolon-csv/template.csv.tsuku{tsuku} [Template]
<<< @/snippets/examples/csv-exports/semicolon-csv/data.php [Data]
<<< @/snippets/examples/csv-exports/semicolon-csv/output.csv [Output]
<<< @/snippets/examples/csv-exports/semicolon-csv/example.php [PHP]
:::

## Safe to open in a spreadsheet

`@csv()` escapes the CSV format only. A value that starts with `=`, `+`, `-` or `@` runs as a formula when the file is opened in Excel or Google Sheets, so an export of anything visitors typed needs more: the registered `@csv_safe()` puts an apostrophe in front of such values and then quotes like `@csv()`.

::: code-group
<<< @/snippets/examples/csv-exports/formula-injection/template.csv.tsuku{tsuku} [Template]
<<< @/snippets/examples/csv-exports/formula-injection/data.php [Data]
<<< @/snippets/examples/csv-exports/formula-injection/output.csv [Output]
<<< @/snippets/examples/csv-exports/formula-injection/example.php [PHP]
:::

## Serving the file

```php
$csv = $tsuku->process($template, $data);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="products-' . date('Y-m-d') . '.csv"');
echo "\xEF\xBB\xBF"; // UTF-8 byte order mark, so Excel reads umlauts and € correctly
echo $csv;
```

`process()` returns the whole file as one string. For exports with many thousands of rows, `processToStream()` renders the header, each row and the footer separately and hands every piece to a callback such as `fwrite()`, so memory use stays flat - see [Streaming Large Datasets](/guide/streaming).

## Next Steps

- [XML Generation →](/examples/xml-generation) - product feeds and sitemaps
- [Line Continuation →](/guide/line-continuation) - long rows over several template lines
- [Security & Escaping →](/guide/security) - escaping for every output format
