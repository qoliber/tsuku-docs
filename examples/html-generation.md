<script setup>
import productGrid from '../snippets/examples/html-generation/product-grid/output.html?raw'
import reviews from '../snippets/examples/html-generation/reviews/output.html?raw'
import orderHistory from '../snippets/examples/html-generation/order-history/output.html?raw'
import orderEmail from '../snippets/examples/html-generation/order-email/output.html?raw'
import checkoutForm from '../snippets/examples/html-generation/checkout-form/output.html?raw'
</script>

# HTML Generation

Tsuku is not a page-layout engine like Twig or Blade: it has no inheritance or includes. It fits HTML that is built from data - product cards, account tables, transactional emails, form fields, widget markup.

Tsuku never escapes on its own. Every string from the data goes through `@html()`, in text and in attribute values; integers such as ids and counts are printed with `{...}`. Each example has the template, the data, the generated output and the PHP call ([how the examples work](/examples/#how-the-examples-work)), followed by a preview of the output styled with a little example CSS.

## Product grid

A category grid: a loop over the products, a badge picked by an `@if` / `@elseif` chain using `in` and `and`, a sale price only when it is lower than the regular price, and a disabled button for a product that is out of stock. The stars are a list prepared in PHP, because templates have no arithmetic. Directives are indented with the markup they control; their lines print nothing.

::: code-group
<<< @/snippets/examples/html-generation/product-grid/template.html.tsuku{tsuku-html} [Template]
<<< @/snippets/examples/html-generation/product-grid/data.php [Data]
<<< @/snippets/examples/html-generation/product-grid/output.html [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

<HtmlPreview :html="productGrid" />

## Customer reviews

Text typed by customers must be escaped. `@html()` turns the `<script>` and `<b>` tags in the reviews into visible text and the quotes in the author's name into `&quot;`. With `{review.body}` instead, the script tag would end up in the page.

::: code-group
<<< @/snippets/examples/html-generation/reviews/template.html.tsuku{tsuku-html} [Template]
<<< @/snippets/examples/html-generation/reviews/data.php [Data]
<<< @/snippets/examples/html-generation/reviews/output.html [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

<HtmlPreview :html="reviews" />

## Order history

The order table of a customer account. `@match` maps the order status to a label, `@case("pending", "processing")` takes several values, and `@default` covers any status the template does not know. `@date()` formats the timestamps (in PHP's default timezone, UTC here).

::: code-group
<<< @/snippets/examples/html-generation/order-history/template.html.tsuku{tsuku-html} [Template]
<<< @/snippets/examples/html-generation/order-history/data.php [Data]
<<< @/snippets/examples/html-generation/order-history/output.html [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

<HtmlPreview :html="orderHistory" />

## Order confirmation email

A complete HTML email with inline styles, as mail clients need them. `@@media` and `support@@shop.example.com` use `@@`, the escape for a literal `@` typed into the template; the braces of the CSS rules need no escaping because only `{plain.path}` is a variable. The total comes from PHP.

::: code-group
<<< @/snippets/examples/html-generation/order-email/template.html.tsuku{tsuku-html} [Template]
<<< @/snippets/examples/html-generation/order-email/data.php [Data]
<<< @/snippets/examples/html-generation/order-email/output.html [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

<HtmlPreview :html="orderEmail" label="Preview (email)" />

## Checkout form

Form fields from configuration. `@match(field.type)` writes an `<input>`, a `<select>` or a `<textarea>`. Inline blocks such as `@if(field.required) required@end` add an attribute inside a tag; the space before `required` is kept because the body is on one line. The customer's earlier input is escaped as well: `Ring twice <3` becomes `Ring twice &lt;3`.

::: code-group
<<< @/snippets/examples/html-generation/checkout-form/template.html.tsuku{tsuku-html} [Template]
<<< @/snippets/examples/html-generation/checkout-form/data.php [Data]
<<< @/snippets/examples/html-generation/checkout-form/output.html [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

<HtmlPreview :html="checkoutForm" />

## Escaping checklist

- Text and attribute values: `@html(value)`. It escapes `& < > " '`, so it is safe in double- and single-quoted attributes.
- A value inside a URL path or query string: `@url(value)`.
- `@html()` does not make a URL safe to link to: a `javascript:` URL is still a link. Check URLs that users entered in PHP.
- JSON inside a `<script>` tag (JSON-LD): `@json()`, which also escapes `<` and `>` - see [JSON-LD product data](/examples/json-apis#json-ld-product-data).
- Never print user input with `{...}` in HTML. More in [Security & Escaping](/guide/security).

## Next Steps

- [E-commerce →](/examples/ecommerce) - invoices, labels and plain-text emails
- [JSON APIs →](/examples/json-apis) - the same kind of data as JSON
- [Built-in Functions →](/guide/built-in-functions) - every escaping and formatting function
