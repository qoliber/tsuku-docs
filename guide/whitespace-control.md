# Whitespace Control

Templates for CSV, YAML, JSON or XML are only correct when every line break and every space ends up in the right place. Since 1.4.0, Tsuku decides which whitespace around directives is **layout** (dropped) and which is **content** (kept), so you can indent templates freely without `\` line continuations.

The decision is made while the template is tokenized (once per `process()` call, before any data is looked at), not while rows are rendered, so it does not slow down large loops.

## The Short Version

- A line that holds only directives produces **no output** - not its indentation, not its line break.
- Content lines are printed **exactly as written**, including their own indentation.
- A directive at the end of a content line takes that line's line break with it - **except `@end`**, which keeps it.
- When a block starts after content on the same line (`{sku},@if(...)`), its branches continue that line.

## Terms

| Term | Meaning |
|------|---------|
| **Line break** | `\n` or `\r\n` |
| **Block directives** | `@for` `@if` `@unless` `@elseif` `@else` `@match` `@case` `@default` `@end` and registered [custom directives](/guide/custom-directives) |
| **Content** | everything else: text, `{variables}`, `@functions()`, `@?{...}` inline conditions, `@@` |

[Line continuations](/guide/line-continuation) (`\` at the end of a line) are removed **first**, exactly as in 1.2 and 1.3, and the rules below are applied to the joined lines.

## Rule 1: Directive-Only Lines Are Layout

A line holding only block directives and whitespace renders nothing - neither its indentation nor its line break. Indent directives to match the structure of the output:

```php
$template = 'services:
@for(services as service)
  {service.name}:
    image: {service.image}
    @if(service.ports)
    ports:
      @for(service.ports as port)
      - "{port}"
      @end
    @end
@end';

$data = [
    'services' => [
        ['name' => 'web', 'image' => 'nginx:1.27', 'ports' => [80, 443]],
        ['name' => 'worker', 'image' => 'php:8.3-cli', 'ports' => []],
    ],
];

echo $tsuku->process($template, $data);
// Output:
// services:
//   web:
//     image: nginx:1.27
//     ports:
//       - "80"
//       - "443"
//   worker:
//     image: php:8.3-cli
```

In 1.3.0 the indentation of the `@if`, `@for` and `@end` lines leaked into the next content line and produced invalid YAML:

```
services:
  web:
    image: nginx:1.27
        ports:
            - "80"
            - "443"
            worker:
    image: php:8.3-cli
```

Several directives on one layout line are fine too (`@end @end`); the spaces between them are layout as well.

## Rule 2: Indentation Before Closing and Branch Directives Is Dropped

Indentation in front of `@else`, `@elseif`, `@case`, `@default` or `@end` at the start of a line is dropped - also when content follows the directive on that line:

```php
$template = '@for(products as p)
@match(p.status)
    @case("active")Active
    @default Unavailable
@end
@end';

$data = [
    'products' => [
        ['status' => 'active'],
        ['status' => 'retired'],
    ],
];

echo $tsuku->process($template, $data);
// Output:
// Active
//  Unavailable
```

The space in ` Unavailable` is not indentation - it is the space after `@default` on the same line, which is content (see [single-line branches](#single-line-branches-keep-their-spaces)). In 1.3.0 the four spaces of indentation were printed in front of it.

## Rule 3: A Directive at the End of a Content Line Takes Its Line Break

When a directive other than `@end` ends a line that has content before it, the trailing whitespace and the line break after the directive are removed - the next line continues the current one. This is how a value can be chosen by a multi-line, indented `@match` and still land on its row:

```php
$template = '@for(products as p)
{p.sku},@match(p.status)
    @case("active")
        Available
    @default
        Unavailable
@end
@end';

$data = [
    'products' => [
        ['sku' => 'WID-001', 'status' => 'active'],
        ['sku' => 'GAD-002', 'status' => 'retired'],
    ],
];

echo $tsuku->process($template, $data);
// Output:
// WID-001,Available
// GAD-002,Unavailable
```

1.3.0 printed the indentation of the case lines and of the next row:

```
WID-001,        Available
    GAD-002,        Unavailable
```

(The indentation of `Available` is removed by [rule 5](#rule-5-inline-blocks-continue-their-line), because the `@match` starts after content.)

## Rule 4: An @end After Content Keeps Its Line Break

An `@end` that follows content on its line keeps the line break after it. This is what keeps CSV rows apart when a row ends with an inline condition:

```php
$template = 'sku,stock
@for(products as p)
{p.sku},@if(p.qty > 0)in@else out@end
@end';

$data = [
    'products' => [
        ['sku' => 'WID-001', 'qty' => 5],
        ['sku' => 'GAD-002', 'qty' => 0],
    ],
];

echo $tsuku->process($template, $data);
// Output:
// sku,stock
// WID-001,in
// GAD-002, out
```

In 1.3.0 the line break after `@end` was swallowed and the rows were merged:

```
sku,stock
WID-001,inGAD-002, out
```

### Single-Line Branches Keep Their Spaces

Note the space in `GAD-002, out`: whitespace that does not contain a line break is content, so `@else out` prints ` out`. For values inside CSV, JSON or XML lines, an [inline condition](/guide/conditionals#inline-conditions) is usually the better choice - it never adds whitespace:

```php
$template = 'sku,stock
@for(products as p)
{p.sku},@?{p.qty > 0 "in" : "out"}
@end';

$data = [
    'products' => [
        ['sku' => 'WID-001', 'qty' => 5],
        ['sku' => 'GAD-002', 'qty' => 0],
    ],
];

echo $tsuku->process($template, $data);
// Output:
// sku,stock
// WID-001,in
// GAD-002,out
```

The same applies on any single line: `Stock: @if(qty > 0) {qty} left @else none @end.` prints `Stock:  none .` for `qty = 0` - both spaces around `none` and the one before `@else` belong to the branches.

## Rule 5: Inline Blocks Continue Their Line

When content precedes `@if`, `@unless`, `@match` or a custom directive on its line, the block is **inline**: whitespace that contains a line break at the **start** of each branch (or case) body is dropped, so the body continues the opener's line.

```php
$template = 'Status: @if(active)
   Active
@else
   Inactive
@end';

$data = ['active' => true];

echo $tsuku->process($template, $data);
// Output: Status: Active
```

1.3.0 printed `Status:    Active`.

When content also **follows** the block's `@end` on that line, the same is done at the **end** of each body, so the whole block sits inside the line:

```php
$template = '@for(products as p)
<stock>@match(p.status)
    @case("active")
        in stock
    @default
        out of stock
@end</stock>
@end';

$data = [
    'products' => [
        ['status' => 'active'],
        ['status' => 'retired'],
    ],
];

echo $tsuku->process($template, $data);
// Output:
// <stock>in stock</stock>
// <stock>out of stock</stock>
```

In 1.3.0 this produced:

```
<stock>        in stock
    </stock>
<stock>        out of stock
</stock>
```

Whitespace **without** a line break is never trimmed, which is why single-line bodies such as `@else out` keep their spaces.

### @for Bodies Are Never Trimmed

Rule 5 does not apply to `@for`: a loop body is repeated as written. Only rule 3 applies to an opening `@for` at the end of a content line - its line break is removed - while the indentation and the line break before `@end` stay in every iteration:

```php
$template = '<tags>@for(tags as tag)
  <tag>{tag}</tag>
@end</tags>';

$data = ['tags' => ['new', 'sale']];

echo $tsuku->process($template, $data);
// Output:
// <tags>  <tag>new</tag>
//   <tag>sale</tag>
// </tags>
```

The same layout with `@if` would sit inside the line (`<tags><tag>new</tag></tags>`). To put one entry per line, start the loop on a line of its own:

```php
$template = '<tags>
@for(tags as tag)
  <tag>{tag}</tag>
@end
</tags>';

$data = ['tags' => ['new', 'sale']];

echo $tsuku->process($template, $data);
// Output:
// <tags>
//   <tag>new</tag>
//   <tag>sale</tag>
// </tags>
```

## Indented HTML

The rules work together so that nested, indented directives leave clean markup:

```php
$template = '<ul>
    @for(items as item)
        @if(item.visible)
    <li>{item.name}</li>
        @end
    @end
</ul>';

$data = [
    'items' => [
        ['name' => 'Home', 'visible' => true],
        ['name' => 'Drafts', 'visible' => false],
        ['name' => 'Contact', 'visible' => true],
    ],
];

echo $tsuku->process($template, $data);
// Output:
// <ul>
//     <li>Home</li>
//     <li>Contact</li>
// </ul>
```

The content line `    <li>{item.name}</li>` keeps its own four spaces; the deeper indentation of the directive lines disappears with them.

## Joining Lines with `\`

Sometimes the line breaks you need for readability must not appear in the output at all - for example attributes inside one XML tag. Rule 4 keeps the line break after an `@end` that follows content, and rule 5 drops the line break (and the indentation) at the start of an inline body:

```php
$template = '<product@if(p.featured)
 featured="1"@end
/>';

$data = ['p' => ['featured' => true]];

echo $tsuku->process($template, $data);
// Output:
// <productfeatured="1"
// />
```

Join such lines with `\` [line continuations](/guide/line-continuation). They are removed before whitespace control runs, so the space before `featured` stays:

```php
$template = '<product@if(p.featured)\
 featured="1"@end\
/>';

$data = ['p' => ['featured' => true]];

echo $tsuku->process($template, $data);
// Output: <product featured="1"/>
```

(1.3.0 printed `<product featured="1"/>` for the first template too, because it swallowed those line breaks; see [Upgrading from 1.3](/guide/upgrading).)

## Windows Line Endings (CRLF)

`\r\n` is a line break like `\n`, so templates saved with Windows line endings follow exactly the same rules. Content lines keep their `\r\n`:

```php
$template = "sku,stock\r\n@for(products as p)\r\n{p.sku},@?{p.qty > 0 \"in\" : \"out\"}\r\n@end\r\nend";

$data = [
    'products' => [
        ['sku' => 'WID-001', 'qty' => 5],
        ['sku' => 'GAD-002', 'qty' => 0],
    ],
];

var_dump($tsuku->process($template, $data) === "sku,stock\r\nWID-001,in\r\nGAD-002,out\r\nend");
// bool(true)
```

1.3.0 only recognised `\n` after directives, so the `\r` of the directive lines stayed in the output and the same template rendered with extra empty lines: `"sku,stock\r\n\r\nWID-001,in\r\n\r\nGAD-002,out\r\n\r\nend"`.

## Quick Reference

| You write | You get |
|-----------|---------|
| `@if(x)` / `@end` alone on a line (indented or not) | nothing - no indentation, no line break |
| `    @else` / `    @case(...)Text` / `    @end` | the indentation is dropped |
| `Total: @if(x)` at the end of a line | the line break after `@if(x)` is dropped |
| `{sku},@if(x)in@end` at the end of a row | the line break after `@end` is kept |
| `Status: @if(x)` + body on the next lines | the body continues the `Status: ` line |
| `<a>@if(x)` ... `@end</a>` | the whole block sits inside `<a>...</a>` |
| `@else out` | ` out` - spaces without a line break are content |
| `@for(...)` body | repeated exactly as written |
| `\` + line break | removed before any of the rules apply |

## Next Steps

- [Upgrading from 1.3 →](/guide/upgrading) - Every output change of 1.4.0 and how to adapt templates
- [Line Continuation →](/guide/line-continuation) - Join lines with `\`
- [Conditionals →](/guide/conditionals) - `@if`, `@elseif` and inline conditions
- [Pattern Matching →](/guide/pattern-matching) - `@match` in rows and lines
