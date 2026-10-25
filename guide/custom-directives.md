# Custom Directives

A custom directive wraps a **block** of your template: `@name(arguments) ... @end`. Tsuku renders the block's content and hands it to your PHP handler, and whatever the handler returns replaces the whole block.

Use a [custom function](/guide/custom-functions) when you need a value (`@price(product.price)`); use a directive when you need to transform a piece of rendered template text (wrap it in CDATA, indent it, repeat it, minify it).

::: info New in 1.4.0
`registerDirective()` has been part of the API since 1.0.0, but before 1.4.0 it had no effect: registered directives were never called. See [Upgrading from 1.3](/guide/upgrading).
:::

## Registering a Directive

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

$tsuku->registerDirective('cdata', function (string $body): string {
    return '<![CDATA[' . $body . ']]>';
});

$template = '<title>@cdata{product.name}@end</title>';
$data = ['product' => ['name' => 'Fish & Chips']];

echo $tsuku->process($template, $data);
// Output: <title><![CDATA[Fish & Chips]]></title>
```

`registerDirective()` returns the `Tsuku` instance, so calls can be chained like `registerFunction()`.

## The Handler

The handler is called as `handler(string $body, mixed ...$arguments)`:

- `$body` is the content between the directive and its `@end`, **already rendered** (variables, functions, loops and conditions inside it have been processed).
- The arguments follow, resolved exactly like [function arguments](/guide/custom-functions#how-arguments-are-passed): quoted strings lose their quotes, numbers arrive as strings, variables keep their PHP type, a missing variable is `""`, nested `@function()` calls are evaluated.
- The return value is converted to a string like a function's return value and replaces the block.

```php
$tsuku->registerDirective('wrap', fn(string $body, string $open, string $close): string => $open . $body . $close);

$template = '@wrap("[", "]"){product.sku}@end and @wrap(open, close){product.name}@end';
$data = ['product' => ['sku' => 'WID-001', 'name' => 'Widget'], 'open' => '<b>', 'close' => '</b>'];

echo $tsuku->process($template, $data);
// Output: [WID-001] and <b>Widget</b>
```

Numbers arrive as strings, so cast them in the handler (or use a typed parameter):

```php
$tsuku->registerDirective('repeat', fn(string $body, string $times): string => str_repeat($body, (int) $times));

echo $tsuku->process('@repeat(3)=-@end', []);
// Output: =-=-=-
```

## Block Syntax

A directive can be written with or without arguments:

```text
@name(argument, argument) ... @end
@name ... @end
```

- Without parentheses the body starts right after the name, so in `@cdata {name} @end` the spaces around `{name}` are part of the body.
- The `(` must follow the name directly. `@wrap ("[", "]")` is the directive `@wrap` without arguments whose body starts with ` ("[", "]")`.
- The body can contain anything: text, variables, functions, `@for`, `@if`, `@match` and other directives, including the same directive.

```php
$tsuku->registerDirective('wrap', fn(string $body, string $open, string $close): string => $open . $body . $close);

$template = '@wrap("(", ")")outer @wrap("[", "]")@if(vip)VIP@else regular@end@end@end';

echo $tsuku->process($template, ['vip' => false]);
// Output: (outer [ regular])
```

(The space before `regular` comes from the single-line branch `@else regular`, which keeps its spaces.)

## Whitespace

Directives follow the same [whitespace rules](/guide/whitespace-control) as `@if`.

**Inline block** - content before the directive on its line and after its `@end`: the line breaks and indentation at the start and end of the body are dropped, so the whole block stays inside the line:

```php
$tsuku->registerDirective('cdata', fn(string $body): string => '<![CDATA[' . $body . ']]>');

$template = <<<'XML'
<description>@cdata
    {product.description}
@end</description>
XML;

$data = ['product' => ['description' => '<b>Crispy</b> & hot']];

echo $tsuku->process($template, $data);
// Output: <description><![CDATA[<b>Crispy</b> & hot]]></description>
```

**Block on its own lines** - the directive and `@end` lines are layout and print nothing, but the content lines between them are kept exactly as written, including their indentation and the line break of the last one:

```php
$tsuku->registerDirective('cdata', fn(string $body): string => '<![CDATA[' . $body . ']]>');

$template = <<<'XML'
<items>
@for(items as item)
    @cdata
    {item}
    @end
@end
</items>
XML;

echo $tsuku->process($template, ['items' => ['a', 'b']]);
// Output:
// <items>
// <![CDATA[    a
// ]]><![CDATA[    b
// ]]></items>
```

When the exact body matters (CDATA, attributes), write the block inline or trim the body in the handler.

## Names

Directive names must be identifiers: letters, digits and underscores, not starting with a digit. They are case-sensitive (`@CDATA` is not `@cdata`).

```php
$tsuku->registerDirective('my-block', fn(string $body): string => $body);
// Throws: InvalidArgumentException: Invalid directive name 'my-block'
```

The built-in directive names cannot be registered: `for`, `if`, `unless`, `elseif`, `else`, `match`, `case`, `default`, `end` (the list is `DirectiveRegistry::RESERVED`).

```php
$tsuku->registerDirective('if', fn(string $body): string => $body);
// Throws: InvalidArgumentException: 'if' is a built-in directive and cannot be registered
```

Other names are fine, including names that start like a built-in (`endnote`, `elsewhere`, `defaults`): registered directives are recognised before the built-in keywords. Registering a name again replaces its handler.

### Precedence over functions

A registered directive takes precedence over a function with the same name. Every `@name(...)` in the template then opens a block that needs an `@end`, so do not give a directive the name of a function you still call:

```php
$tsuku->registerDirective('upper', fn(string $body): string => strtoupper($body));

echo $tsuku->process('Hello @upper(name)', ['name' => 'Ann']);
// Throws: ParseException: Expected @end but reached end of file
```

### Register before processing

The lexer needs to know which names are directives, so register them before calling `process()` or `processToStream()`. An unregistered `@cdata ... @end` is not a directive:

```php
$tsuku = new Tsuku();

echo $tsuku->process('<title>@cdata{name}@end</title>', ['name' => 'Ann']);
// Throws: ParseException: Invalid @ syntax at line 1, column 8
```

## Errors

A directive without its `@end` is a parse error, whatever the strictness mode:

```php
$tsuku->registerDirective('cdata', fn(string $body): string => '<![CDATA[' . $body . ']]>');

echo $tsuku->process('<title>@cdata{name}</title>', ['name' => 'Ann']);
// Throws: ParseException: Expected @end but reached end of file at line 1, column 28 (@cdata opened at line 1, column 8)
```

An exception thrown by the handler (including a `TypeError` from its parameter types) is handled like a function error, according to the [strictness mode](/guide/strictness):

| Mode | Result |
|------|--------|
| `SILENT` (default) | the block prints nothing |
| `WARNING` | the block prints nothing, `getWarnings()` contains `Directive 'name' error: <message>` |
| `STRICT` | `TsukuException` with the message `Directive 'name' error: <message>` |

```php
use Qoliber\Tsuku\StrictnessMode;

$tsuku = new Tsuku(StrictnessMode::STRICT);
$tsuku->registerDirective('price_box', function (string $body): string {
    throw new \RuntimeException('currency service unavailable');
});

echo $tsuku->process('@price_box{price}@end', ['price' => 10]);
// Throws: TsukuException: Directive 'price_box' error: currency service unavailable
```

## The Body Is Always Rendered

The body is rendered before the handler is called, even if the handler ignores it. A directive therefore cannot skip, cache or lazily render its content: functions inside the body run every time. To leave something out, use `@if` around it.

```php
$calls = 0;
$tsuku->registerFunction('expensive', function () use (&$calls): string {
    $calls++;
    return 'x';
});
$tsuku->registerDirective('hide', fn(string $body): string => '');

$tsuku->process('@hide@expensive()@end', []);
// $calls is now 1
```

## Streaming

Custom directives work the same way with [`processToStream()`](/guide/streaming), in the header, the footer and the streamed row body:

```php
$tsuku->registerDirective('cdata', fn(string $body): string => '<![CDATA[' . $body . ']]>');

$template = <<<'XML'
<feed>
@for(products as product)
  <title>@cdata{product.name}@end</title>
@end
</feed>
XML;

$tsuku->processToStream($template, [], [['name' => 'Fish & Chips'], ['name' => 'Salt & Pepper']], 'products', function (string $chunk): void {
    echo $chunk;
});
// Output:
// <feed>
//   <title><![CDATA[Fish & Chips]]></title>
//   <title><![CDATA[Salt & Pepper]]></title>
// </feed>
```

## Examples

### Safe CDATA for XML feeds

A CDATA section ends at the first `]]>`, so a robust handler splits that sequence:

```php
$tsuku->registerDirective('cdata', function (string $body): string {
    return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $body) . ']]>';
});

$template = '<g:description>@cdata{product.description}@end</g:description>';
$data = ['product' => ['description' => 'Use a[b[0]]> carefully']];

echo $tsuku->process($template, $data);
// Output: <g:description><![CDATA[Use a[b[0]]]]><![CDATA[> carefully]]></g:description>
```

### Indenting a block

```php
$tsuku->registerDirective('indent', function (string $body, string $spaces): string {
    return preg_replace('/^(?=.)/m', str_repeat(' ', (int) $spaces), $body);
});

$template = <<<'YAML'
service:
@indent(4)
name: {name}
ports:
  - 80
@end
enabled: yes
YAML;

echo $tsuku->process($template, ['name' => 'web']);
// Output:
// service:
//     name: web
//     ports:
//       - 80
// enabled: yes
```

### Minifying HTML

```php
$tsuku->registerDirective('spaceless', fn(string $body): string => trim(preg_replace('/>\s+</', '><', $body)));

$template = <<<'HTML'
@spaceless
<ul>
    @for(items as item)
    <li>{item}</li>
    @end
</ul>
@end
HTML;

echo $tsuku->process($template, ['items' => ['One', 'Two']]);
// Output: <ul><li>One</li><li>Two</li></ul>
```

## Directive Registry

Directives are stored in a `Qoliber\Tsuku\DirectiveRegistry`, the second constructor argument of `Tsuku` (`directiveRegistry`). Pass your own to share directives between instances or to inspect them:

```php
use Qoliber\Tsuku\DirectiveRegistry;
use Qoliber\Tsuku\Tsuku;

$directives = new DirectiveRegistry();
$directives->register('cdata', fn(string $body): string => '<![CDATA[' . $body . ']]>');

$tsuku = new Tsuku(directiveRegistry: $directives);

$directives->has('cdata');  // true
$directives->names();       // ['cdata']
$directives->get('nope');   // throws DirectiveNotFoundException
```

## Next Steps

- [Custom Functions →](/guide/custom-functions) - Values instead of blocks
- [Whitespace Control →](/guide/whitespace-control) - How lines around directives are printed
- [Streaming Large Datasets →](/guide/streaming) - Directives in large feeds
