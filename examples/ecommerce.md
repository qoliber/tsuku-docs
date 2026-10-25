# E-commerce

Documents a shop sends or exchanges every day: invoices, shipping labels, marketplace feeds, emails and price lists. HTML emails and product feeds have their own pages: [order confirmation email](/examples/html-generation#order-confirmation-email), [Google Shopping feed](/examples/xml-generation#google-shopping-feed), [Magento import file](/examples/csv-exports#magento-product-import).

::: tip Prepare amounts in PHP
Templates have no arithmetic: `{item.qty * item.price}` is printed as literal text, and `@number(item.qty * item.price, 2)` does not multiply. Row totals, tax and discounts are calculated where the data is built - see the Data tab of each example.
:::

Each example has the template, the data, the generated output and the PHP call ([how the examples work](/examples/#how-the-examples-work)).

## Invoice

A plain-text invoice with fixed-width columns. The registered `@pad(value, width, "right")` cuts or pads a value to its column, using `mb_` functions so that `ß` and `€` count as one character (PHP tab). The payment line depends on whether the invoice is paid.

::: code-group
<<< @/snippets/examples/ecommerce/invoice/template.txt.tsuku{tsuku} [Template]
<<< @/snippets/examples/ecommerce/invoice/data.php [Data]
<<< @/snippets/examples/ecommerce/invoice/output.txt [Output]
<<< @/snippets/examples/ecommerce/invoice/example.php [PHP]
:::

## Shipping label

`@upper()` for the courier's scanner. The `@if(recipient.company)` line is indented like the address but prints nothing itself, so a private address has no empty line. The `@match` adds a service banner for express and Saturday deliveries only.

::: code-group
<<< @/snippets/examples/ecommerce/shipping-label/template.txt.tsuku{tsuku} [Template]
<<< @/snippets/examples/ecommerce/shipping-label/data.php [Data]
<<< @/snippets/examples/ecommerce/shipping-label/output.txt [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## Marketplace feed

A tab-separated listing file, the format of marketplace "flat file" uploads. A tab or line break inside a value would start a new column or row, and `@csv()` does not handle tabs, so the example registers `@tsv()`, which replaces them with a space (PHP tab). `product.bullets.1` reads a list element by index; a missing element is an empty field.

::: code-group
<<< @/snippets/examples/ecommerce/marketplace-feed/template.tsv.tsuku{tsuku} [Template]
<<< @/snippets/examples/ecommerce/marketplace-feed/data.php [Data]
<<< @/snippets/examples/ecommerce/marketplace-feed/output.tsv{tsv} [Output]
<<< @/snippets/examples/ecommerce/marketplace-feed/example.php [PHP]
:::

## Abandoned cart email

A plain-text email (`text/plain`, nothing to escape). The coupon paragraph depends on two conditions: with a coupon and a large enough basket the code applies, with a coupon and a smaller basket the email says how much is missing (`@elseif`), and without a coupon there is no paragraph. The blank line in front of each paragraph sits inside its branch, so the email never has two blank lines in a row.

::: code-group
<<< @/snippets/examples/ecommerce/abandoned-cart/template.txt.tsuku{tsuku} [Template]
<<< @/snippets/examples/ecommerce/abandoned-cart/data.php [Data]
<<< @/snippets/examples/ecommerce/abandoned-cart/output.txt [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## Price list per customer group

A wholesale price list for one B2B customer. Magento stores a multiselect such as the allowed customer groups as ids joined with commas; `customer.group_id in @split(product.customer_groups)` tests for an exact id, so group `7231` sees the products for `2,7231` and `7231` but not the one for `17231,9` - a substring test such as `@contains(product.customer_groups, "7231")` would match that too. Discontinued products are left out with `and`; a condition may continue on the next line.

::: code-group
<<< @/snippets/examples/ecommerce/b2b-price-list/template.csv.tsuku{tsuku} [Template]
<<< @/snippets/examples/ecommerce/b2b-price-list/data.php [Data]
<<< @/snippets/examples/ecommerce/b2b-price-list/output.csv [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## Escaping by format

| Output | Escape text with | Numbers |
|--------|------------------|---------|
| HTML email, web page | `@html()` | `@number(value, 2)` |
| Plain-text email, label | nothing | `@number(value, 2)` |
| CSV | `@csv()` | `@number(value, 2, ".", "")` |
| TSV, semicolon CSV | a registered function (see above and [CSV Exports](/examples/csv-exports#semicolon-separated-file)) | `@number(value, 2, ".", "")` or `@number(value, 2, ",", ".")` |
| XML feed | `@xml()` | `@number(value, 2, ".", "")` |
| JSON | `@json()` | `@number(value, 2, ".", "")` |

Order numbers such as `000002203` are strings: print them with `{order.increment_id}`, because `@number(order.increment_id)` would print `2,203`.

## Next Steps

- [HTML Generation →](/examples/html-generation) - the HTML order confirmation email
- [Custom Functions →](/guide/custom-functions) - functions such as `@pad` and `@tsv`
- [Security & Escaping →](/guide/security) - escaping for every output format
