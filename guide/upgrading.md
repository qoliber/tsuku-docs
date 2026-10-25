# Upgrading from 1.3 to 1.4

Tsuku 1.4.0 is a drop-in upgrade for PHP code: no public method was removed or renamed, and `process()` / `processToStream()` take the same arguments. What changes is the **output of some templates**: whitespace around directives is now controlled by fixed rules, and conditions are parsed properly instead of being matched with regular expressions. Most templates print the same or better output; this page lists every difference so you can check yours.

```bash
composer require qoliber/tsuku:^1.4
```

## Checklist

1. Before upgrading, render every template you use with representative data and save the output.
2. Upgrade, render the same templates with the same data, and diff the results.
3. Every difference should be one of the changes below. Templates that now throw a `ParseException` or `FunctionNotFoundException` contained conditions that 1.3.0 evaluated wrongly; the message names the line and column.
4. Remove workarounds you added for 1.3.0 behaviour (extra blank lines, `\` continuations only used to stop rows from merging, avoided `and`/`or`).

## What Is New

- Conditions with `and` / `&&`, `or` / `||`, `not` / `!`, parentheses, `in` / `not in`, and `@elseif` - see [Conditionals](/guide/conditionals).
- `@contains(haystack, needle)` and `@split(string, delimiter)`.
- `@@` prints a literal `@` - see [Escapes & Special Characters](/guide/escaping).
- [Custom directives](/guide/custom-directives) (`registerDirective()` now works).
- [Whitespace control](/guide/whitespace-control): directive-only lines print nothing.
- `ParseException::getTemplateLine()` / `getTemplateColumn()` and `FunctionNotFoundException` - see [Strictness Modes & Errors](/guide/strictness).

The full list is in the [changelog](/changelog).

## Output Changes

The examples below show the 1.4.0 output in the code and what 1.3.0 printed underneath.

### Indented directive lines no longer print their indentation

A line that holds only directives prints nothing in 1.4.0. In 1.3.0 its indentation was printed in front of the next content line, which broke YAML and other indentation-sensitive formats:

```php
$template = <<<'YAML'
services:
@for(services as service)
  {service.name}:
    image: {service.image}
    @if(service.ports)
    ports:
      @for(service.ports as port)
      - "{port}"
      @end
    @end
@end
YAML;

$data = ['services' => [
    ['name' => 'web', 'image' => 'nginx', 'ports' => [80, 443]],
    ['name' => 'db', 'image' => 'postgres', 'ports' => []],
]];

echo $tsuku->process($template, $data);
// Output (1.4.0):
// services:
//   web:
//     image: nginx
//     ports:
//       - "80"
//       - "443"
//   db:
//     image: postgres
```

1.3.0 printed (the last line holds four spaces):

```text
services:
  web:
    image: nginx
        ports:
            - "80"
            - "443"
            db:
    image: postgres
    
```

**To keep the old output:** content lines now get exactly their own indentation. If a template relied on the indentation of a directive line appearing in front of the next content line - for example an indented `@match` above unindented `@case` lines - indent the content line itself:

```php
$template = <<<'TPL'
@for(products as product)
  @match(product.status)
@case("active")
    {product.sku}: on sale
@default
    {product.sku}: hidden
@end
@end
TPL;

$data = ['products' => [['sku' => 'A-1', 'status' => 'active'], ['sku' => 'B-2', 'status' => 'retired']]];

echo $tsuku->process($template, $data);
// Output (1.4.0):
//     A-1: on sale
//     B-2: hidden
```

With the content lines indented by two spaces, as they were written for 1.3.0, 1.4.0 prints `  A-1: on sale` where 1.3.0 printed `    A-1: on sale` (the two spaces of the `@match` line plus the two of the content line).

### An `@end` after content keeps its line break

In 1.3.0 the line break after an `@end` that followed content on its line was swallowed, so CSV rows with an inline condition merged into one line:

```php
$template = <<<'CSV'
sku,stock
@for(products as product)
{product.sku},@if(product.qty > 0)in@else out@end
@end
CSV;

$data = ['products' => [['sku' => 'A-1', 'qty' => 5], ['sku' => 'B-2', 'qty' => 0]]];

echo $tsuku->process($template, $data);
// Output (1.4.0):
// sku,stock
// A-1,in
// B-2, out
```

1.3.0 printed:

```text
sku,stock
A-1,inB-2, out
```

The space in ` out` is the single-line `@else` branch, kept as written in both versions. `{product.sku},@?{product.qty > 0 "in" : "out"}` prints `A-1,in` / `B-2,out` in both 1.3.0 and 1.4.0.

**Workarounds now print what they say.** A template that added an empty line inside the loop to get one row per line now prints that empty line (`A-1,in`, empty line, `B-2, out`, empty line). Remove the extra line.

**Templates that used the swallowing to join lines** now print the line breaks:

```php
$template = <<<'XML'
<product@if(product.featured)
  featured="1"@end
>
XML;

echo $tsuku->process($template, ['product' => ['featured' => true]]);
// Output (1.4.0):
// <productfeatured="1"
// >
```

1.3.0 printed `<product  featured="1">`. Write such markup on one line, or join the lines with [`\` continuations](/guide/line-continuation):

```php
$template = '<product@if(product.featured) featured="1"@end>';

echo $tsuku->process($template, ['product' => ['featured' => true]]);
// Output: <product featured="1">
```

### Inline blocks drop the indentation of their branch bodies

When content precedes `@if`, `@unless`, `@match` or a custom directive on its line (an *inline* block), the line break and indentation at the start of each branch are dropped, so the branch continues the line:

```php
$template = "Status: @if(active)\n   Active\n@end";

echo $tsuku->process($template, ['active' => true]);
// Output (1.4.0): Status: Active
```

1.3.0 printed `Status:    Active` (the space after the colon plus the three spaces of the body). When content also follows the block's `@end` on that line, the line break before `@end` is dropped too:

```php
$template = "<stock>@if(in_stock)\n  yes\n@end</stock>";

echo $tsuku->process($template, ['in_stock' => true]);
// Output (1.4.0): <stock>yes</stock>
```

1.3.0 printed `<stock>  yes`, a line break and `</stock>`.

**To keep the old output:** whitespace without a line break is kept, so write the spaces on the directive's line: `Status: @if(active)   Active@end` prints `Status:    Active`.

### CRLF templates follow the same rules as LF templates

1.3.0 only recognised `\n`, so in a template with Windows line breaks (`\r\n`) every line break after a directive was kept, doubling the empty lines. 1.4.0 treats `\r\n` like `\n`:

```php
$template = "sku\r\n@for(products as product)\r\n{product.sku}\r\n@end\r\ntotal\r\n";

echo json_encode($tsuku->process($template, ['products' => [['sku' => 'A'], ['sku' => 'B']]]));
// Output (1.4.0): "sku\r\nA\r\nB\r\ntotal\r\n"
```

1.3.0 printed `"sku\r\n\r\nA\r\n\r\nB\r\n\r\ntotal\r\n"`. The line breaks of content lines are kept as written (`\r\n` stays `\r\n`).

### Conditions that were evaluated wrongly now throw

1.3.0 matched conditions with regular expressions and silently evaluated whatever part it recognised. 1.4.0 parses the whole condition and throws a `ParseException` (or `FunctionNotFoundException`) that names the line and column. With `qty = 5`, `status = "active"` and `customer = ['orders' => 12]`:

| Condition | 1.3.0 | 1.4.0 | Write instead |
|-----------|-------|-------|---------------|
| `@if(status = "active")` | false | `ParseException`: Unexpected '=' (use == to compare) | `status == "active"` |
| `@if(qty - 1 > 0)` | false | `ParseException`: Unexpected '-' | compute the value in PHP, see [Custom Functions](/guide/custom-functions#arithmetic) |
| `@if(1 < qty < 10)` | false | `ParseException`: Chained comparisons are not supported | `qty > 1 and qty < 10` |
| `@if()` | false | `ParseException`: Empty condition | - |
| `@if(@is_vip(customer))` without registering `is_vip` | false | `FunctionNotFoundException`: Unknown function '@is_vip' | register the function |

Dangling operators (`a and`) and unterminated strings (`a == "x`) throw as well. An unknown function in a condition is reported in every [strictness mode](/guide/strictness) and also inside `@?{...}`; unknown functions in the output behave as before (nothing in SILENT, a warning in WARNING, an exception in STRICT).

#### The JSON comma idiom

The best-known case is the trailing-comma idiom for JSON arrays. It never printed a comma in 1.3.0 (the condition was always false, so the output was invalid JSON) and is a `ParseException` in 1.4.0:

```php
$template = "[\n@for(items as item, index)\n  \"{item}\"@if(index < length(items) - 1),@end\n@end\n]";

echo $tsuku->process($template, ['items' => ['a', 'b', 'c']]);
// Throws: ParseException: Unexpected '-' at line 3, column 37 in condition 'index < length(items) - 1'
```

Templates have no arithmetic and no "last item" variable. Put the separator **before** every item except the first instead; `index` is the array key, so the items must be a list (`array_values()`):

```php
$template = "[@for(items as item, index)@if(index > 0),@end\n  \"{item}\"@end\n]";

echo $tsuku->process($template, ['items' => ['a', 'b', 'c']]);
// Output:
// [
//   "a",
//   "b",
//   "c"
// ]
```

More examples: [JSON APIs](/examples/json-apis).

### Conditions with `and`, `or`, `in`, `!` or a lone function now evaluate as written

These conditions did not throw in 1.3.0, but their results were wrong. They are correct in 1.4.0, so check templates whose output depended on the old results:

| Condition | Data | 1.3.0 | 1.4.0 |
|-----------|------|-------|-------|
| `qty > 0 and price < 10` (also `&&`) | qty 5, price 50 | true (only the first comparison was evaluated) | false |
| `qty > 10 or featured` (also `\|\|`) | qty 0, featured 1 | false | true |
| `"sale" in tags` | tags `['new', 'sale']` | false | true |
| `!discontinued` | discontinued `false` | false | true |
| `@length(images)` | images `['a.jpg']` | false (a function or literal alone was always false) | true |

`@unless(...)` and `@?{...}` use the same conditions and changed in the same way.

### Smaller fixes that change output

| Template | 1.3.0 | 1.4.0 |
|----------|-------|-------|
| `@if (x)yes@end` (space before the parenthesis) | `)yes` | `yes` |
| `@for (items as i){i}@end` with items `[1, 2]` | `)1)2` | `12` |
| `@unless (x)no@end` with `x` false | `)no` | `no` |
| `@match(s)@case ("on")ON@end` with `s` = `on` | `)ON` | `ON` |
| `@match (s)@case("a")A@end` | `ParseException` (Expected @case or @default) | `A` |
| `@if("a>b" == label)match@end` with label `a>b` | nothing (the condition was split at `>`) | `match` |
| `@replace(name, ")", "")` with name `Widget (XL)` | `", "")` | `Widget (XL` |
| `@?{a "}" : "b"}` | `ParseException` | `}` |

Quotes are now respected when Tsuku looks for the closing parenthesis or brace. A quote right after a letter or digit (`Bob's`) is an apostrophe, not the start of a string.

## Templates That Failed in 1.3.0

These did not work at all before, so no existing output changes:

- `@@` was a `ParseException`; it now prints `@`. A bare `@` that starts nothing is still a `ParseException`, whose message now suggests `@@`.
- `@elseif(...)` was a `ParseException` ("Unexpected @else").
- `@contains()` and `@split()` were unknown functions (they printed nothing in SILENT mode).
- Custom directives: `registerDirective()` was accepted but never used, so `@name ... @end` was a `ParseException`. Registered directives now work. In exchange, `registerDirective()` throws `\InvalidArgumentException` for names that could never match (not an identifier, such as `my-block`) and for the built-in directive names (`if`, `for`, `end`, ...).

## What Did Not Change

- `true`, `false` and `null` are still **not** literals in conditions; they are looked up as variables (normally missing, so `null`). `@if(flag == true)` is true when `flag` is `false`. Write `@if(flag)` and `@if(not flag)`.
- Comparisons are PHP loose comparisons as before (numeric strings compare as numbers); a missing variable is `null`.
- <code v-pre>{{</code> is not an escape: Magento <code v-pre>{{widget ...}}</code>, <code v-pre>{{media ...}}</code> and <code v-pre>{{trans ...}}</code> pass through unchanged, and <code v-pre>{{name}}</code> prints `{` + the value + `}`.
- Unknown functions in the output, missing variables and function errors follow the strictness mode exactly as in 1.3.0.
- `\` line continuations are removed first, exactly as in 1.2 and 1.3.

## If You Use the Internal Classes

Code that only calls `Tsuku` needs no change. If you build on the lexer, parser or compiler directly:

- `Compiler`, `Template::render()` and `StreamingProcessor::process()` accept a `DirectiveRegistry` as a new optional argument; existing calls keep working.
- `IfNode` and `TernaryNode` take the parsed condition as a new optional constructor argument.
- Custom directives are `DirectiveNode`s, visited through the separate `DirectiveVisitor` interface, so existing `NodeVisitor` implementations remain valid (they only fail when a template actually contains a custom directive).
- New: `Lexer::tokenizeRaw()` (tokens before whitespace control), `DirectiveRegistry::names()`, `Template\TemplateParser` (lexer + parser, shared by `process()` and `processToStream()`).

## The Library's Own Examples

The scripts in the library's `examples/` directory show these changes too (the 1.3.0 output is captured in `tests/Fixtures/v1.3.0/examples`, the 1.4.0 differences in `tests/Fixtures/v1.4.0/examples`):

- `02`: one user per line instead of one merged line.
- `04`, `08`, `09`: leaked indentation removed.
- `07`: product rows no longer merged, leaked indentation removed.
- `10`: the case text no longer inherits the indentation of the `@match` line.
- `11`: now stops at its first example with a `ParseException` for `i < length(product.images) - 1` (1.3.0 printed invalid JSON and stopped at the fifth).

## Next Steps

- [Whitespace Control →](/guide/whitespace-control) - The five rules in detail
- [Conditionals →](/guide/conditionals) - Everything a condition can contain
- [Changelog →](/changelog) - Full list of changes
