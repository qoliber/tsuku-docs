# Escapes & Special Characters

Tsuku has two special characters in template text: `@` starts a directive, function or inline condition, and `{` starts a variable when it is followed by a plain variable path and `}`. Apart from a `\` at the very end of a line (a [line continuation](/guide/line-continuation)), everything else - quotes, backslashes, other braces - is ordinary text.

This page is about writing these characters **literally**. To make *data* safe for HTML, XML, JSON, CSV or URLs, use the escaping functions described in [Security & Escaping](/guide/security).

## A Literal @: Write @@

`@@` prints a single `@` (since 1.4.0):

```php
$template = 'Contact: support@@example.com
Follow @@qoliber';

echo $tsuku->process($template, []);
// Output:
// Contact: support@example.com
// Follow @qoliber
```

It works anywhere in text, so CSS at-rules and examples of Tsuku syntax are easy to write:

```php
$template = '@@media (max-width: 600px) {
  .grid { display: block; }
}';

echo $tsuku->process($template, []);
// Output:
// @media (max-width: 600px) {
//   .grid { display: block; }
// }
```

```php
$template = 'Write @@if(...) to start a condition, @@@@ for a literal @@.';

echo $tsuku->process($template, []);
// Output: Write @if(...) to start a condition, @@ for a literal @.
```

### A Bare @ Is an Error

An `@` that does not start a directive, a function call `@name(...)` or an inline condition `@?{...}` throws a `ParseException`, and the message tells you what to do:

```php
$tsuku->process('Contact: support@example.com', []);
// Throws: ParseException: Invalid @ syntax at line 1, column 17 (write @@ for a literal @)

$tsuku->process('Price @ 9.99', []);
// Throws: ParseException: Invalid @ syntax at line 1, column 7 (write @@ for a literal @)
```

Words that begin with `end`, `else` or `default` are read as those directives: `@endpoint` is `@end` followed by `point`, and `@elsewhere` is `@else` followed by `where`:

```php
$tsuku->process('Call the @endpoint now', []);
// Throws: ParseException: Unexpected @end at line 1, column 10
```

::: warning @word( is a function call
An `@` followed by a name and an opening parenthesis is a **function call**, even in CSS: `@media(max-width: 600px)` calls a function named `media`. Unknown functions are removed silently in the default SILENT [strictness mode](/guide/strictness), so the at-rule simply disappears from the output. Always write `@@media`.
:::

```php
$template = '@media(max-width: 600px) { }';

echo $tsuku->process($template, []);
// Output:  { }
```

### @ Inside Strings

Inside a quoted function argument or a quoted value in a condition, `@` is just a character - no escaping needed:

```php
$template = '@upper("support@example.com")
@if(email == "support@example.com")match@end';

$data = ['email' => 'support@example.com'];
// Output:
// SUPPORT@EXAMPLE.COM
// match
```

Values that come from your data are never parsed either, so `{user.email}` prints an address with `@` as it is.

## Braces

A `{` starts a variable only when it is directly followed by a variable path (letters, digits, `_` and dots, not starting with a digit) and a closing `}`. Anything else is plain text, so JSON, CSS and most other brace syntax need no escaping:

```php
$template = '.badge { color: red; } { name } {0} {name|upper} {product.price}';

$data = ['name' => 'Ann', 'product' => ['price' => 9]];
// Output: .badge { color: red; } { name } {0} {name|upper} 9
```

```php
$template = '{"name": @json(name), "tags": []}';

$data = ['name' => 'Ann'];
// Output: {"name": "Ann", "tags": []}
```

### Double Braces: Magento Directives Pass Through

::: v-pre
`{{` is **not** an escape. Magento CMS and email directives such as `{{widget ...}}`, `{{media ...}}`, `{{trans ...}}`, `{{var ...}}` or `{{store ...}}` contain spaces after the name, so they are not variables and pass through unchanged - you can render Magento content with Tsuku without touching them:
:::

```php
$template = '{{widget type="Magento\Cms\Block\Widget\Block" block_id="footer_links"}}
<img src="{{media url="wysiwyg/logo.png"}}" alt="{store_name}">
{{trans "Thank you for your order"}} {{var order.increment_id}}';

$data = ['store_name' => 'Qoliber Shop'];
// Output:
// {{widget type="Magento\Cms\Block\Widget\Block" block_id="footer_links"}}
// <img src="{{media url="wysiwyg/logo.png"}}" alt="Qoliber Shop">
// {{trans "Thank you for your order"}} {{var order.increment_id}}
```

::: v-pre
Only a **plain variable path** inside double braces is replaced: `{{name}}` is the text `{`, the variable `{name}` and the text `}`:
:::

```php
$template = '{{name}} / {{product.price}}';

$data = ['name' => 'Ann', 'product' => ['price' => 9]];
// Output: {Ann} / {9}
```

A missing variable renders as `{}` (in SILENT mode). See [Widget Support](/guide/widget-support) for building your own widget functions.

### A Literal {name}

To print a brace pair around a variable name literally, build it with `@concat` (or pass the text in your data):

```php
$template = '@concat("{", "name}") = {name}';

$data = ['name' => 'Ann'];
// Output: {name} = Ann
```

Inline conditions use the same idea: a `}` inside a quoted value does not end the `@?{...}`:

```php
$template = '@?{open "}" : "{"}';

$data = ['open' => true];
// Output: }
```

## Quotes

### Quotes in Function Arguments

Commas inside a quoted function argument never split it, and since 1.4.0 a parenthesis inside a quoted argument no longer ends the call (1.3.0 cut `@replace(name, ")", "")` off at the first `)`):

```php
$template = '@replace(name, ")", "")';

$data = ['name' => 'Widget (Pro))'];
// Output: Widget (Pro
```

A string can use double or single quotes. To search for a double quote, wrap it in single quotes - a backslash does **not** escape a quote inside an argument, it stays part of the value:

```php
$template = '@replace(text, \'"\', "")';

$data = ['text' => 'Say "hi"'];
// Output: Say hi
```

```php
$template = '@replace(text, "\"", "")';

$data = ['text' => 'Say "hi"'];
// Output: Say "hi"
```

The second template searches for the two characters `\"`, which do not occur in the text.

### Apostrophes in Text

A quote directly after a letter, digit or underscore is an apostrophe, not the start of a string, so contractions and possessives work inside and outside of directives:

```php
$template = '@if(owner)Bob\'s garage@end
@upper("Bob\'s garage")';

$data = ['owner' => true];
// Output:
// Bob's garage
// BOB'S GARAGE
```

## Backslashes

A backslash has exactly one meaning in a Tsuku template: **`\` directly before a line break is a [line continuation](/guide/line-continuation)** - both characters are removed. Everywhere else a backslash is printed as it is; it does not escape `@`, `{`, quotes or anything else:

```php
$template = <<<'TPL'
C:\Program Files\App \{name} \n \@@ \\server
TPL;

$data = ['name' => 'Ann'];

echo $tsuku->process($template, $data);
// Output: C:\Program Files\App \Ann \n \@ \\server
```

Mind the PHP layer: in a single-quoted PHP string `'\\'` is **one** backslash, so `'\\\\server'` is needed for `\\server`. A nowdoc (`<<<'TPL'`) passes backslashes through unchanged.

## Summary

::: v-pre
| You want | Write |
|----------|-------|
| `@` | `@@` |
| `@@` | `@@@@` |
| `@media`, `@import`, `@font-face` in CSS | `@@media`, `@@import`, `@@font-face` |
| `{` / `}` / `{ name }` / JSON / CSS braces | as is |
| Magento `{{widget ...}}`, `{{media ...}}`, `{{trans ...}}` | as is |
| `{name}` literally | `@concat("{", "name}")` |
| `"` inside a function argument | `'"'` |
| `,` `(` `)` inside a function argument | quote the argument: `")"` |
| a backslash | `\` (not before a line break) |
| HTML/XML/JSON/CSV/URL-safe data | `@html()`, `@xml()`, `@json()`, `@csv()`, `@url()` - see [Security & Escaping](/guide/security) |
:::

## Next Steps

- [Security & Escaping →](/guide/security) - Escape data for each output format
- [Line Continuation →](/guide/line-continuation) - The one thing a backslash does
- [Widget Support →](/guide/widget-support) - Custom widget functions
- [Variables →](/guide/variables) - Variable syntax and object access
