# Configuration Files

YAML, `.env`, nginx and TOML files from deployment data. Most of these formats depend on indentation, and Tsuku's [whitespace control](/guide/whitespace-control) is built for that: a line that holds only directives (`@if(...)`, `@for(...)`, `@end`) prints nothing at all, so directives can be indented like the configuration around them, while content lines keep their own indentation.

Each example has the template, the data, the generated output and the PHP call ([how the examples work](/examples/#how-the-examples-work)).

## Application settings (YAML)

Booleans come from `@?{app.debug "true" : "false"}`: Tsuku has no boolean literals, and `{app.debug}` would print `1` or nothing. Strings that can contain YAML syntax - `: `, ` #`, quotes - go through `@json()`; YAML 1.2 reads JSON strings, so `"Kumo Tea: Shop"` and the password stay intact. The `cache` section is one of two blocks, and the `retry` block is left out for a queue without retries.

::: code-group
<<< @/snippets/examples/configuration-files/app-config/template.yaml.tsuku{tsuku-yaml} [Template]
<<< @/snippets/examples/configuration-files/app-config/data.php [Data]
<<< @/snippets/examples/configuration-files/app-config/output.yaml [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## Docker Compose

Every optional section of a service - `build` or `image`, `ports`, `environment`, `volumes`, `depends_on` - is an `@if` around a nested `@for`, indented exactly like the YAML it produces. A missing key and an empty list both count as false, so `redis` gets only its image. `@for(service.environment as value, key)` writes a map from an associative array.

::: code-group
<<< @/snippets/examples/configuration-files/docker-compose/template.yaml.tsuku{tsuku-yaml} [Template]
<<< @/snippets/examples/configuration-files/docker-compose/data.php [Data]
<<< @/snippets/examples/configuration-files/docker-compose/output.yaml [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## Kubernetes deployment

Deeply nested YAML with a list of containers. The second container has no port and no resource limits, and those sections disappear without leaving blank lines or stray indentation behind.

::: code-group
<<< @/snippets/examples/configuration-files/kubernetes/template.yaml.tsuku{tsuku-yaml} [Template]
<<< @/snippets/examples/configuration-files/kubernetes/data.php [Data]
<<< @/snippets/examples/configuration-files/kubernetes/output.yaml [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## Environment file

A `.env` file per environment. `@match(cache.driver)` writes the settings of the configured cache only; the `#` line is plain text to Tsuku and is copied into the file. Values with spaces are quoted in the template. Values that can contain `"`, `$` or line breaks need a [registered function](/guide/custom-functions) that escapes them the way your dotenv parser expects.

::: code-group
<<< @/snippets/examples/configuration-files/dotenv/template.env.tsuku{tsuku} [Template]
<<< @/snippets/examples/configuration-files/dotenv/data.php [Data]
<<< @/snippets/examples/configuration-files/dotenv/output.env{dotenv} [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## nginx server block

A staging host: `@unless(site.production)` adds password protection, a loop writes one `location` per redirect, and `@first(site.domains)` names the certificate directory. nginx's own `$uri` and `$args` and the braces of its blocks are plain text to Tsuku. The blank lines between blocks live inside the `@unless` and the `@for`, so a production host without redirects gets no double blank lines.

::: code-group
<<< @/snippets/examples/configuration-files/nginx/template.conf.tsuku{tsuku} [Template]
<<< @/snippets/examples/configuration-files/nginx/data.php [Data]
<<< @/snippets/examples/configuration-files/nginx/output.conf{nginx} [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## Cargo.toml

TOML arrays and inline tables written on one line: `[@for(...)@if(index > 0), @end"{feature}"@end]` puts a separator in front of every element but the first. The `{ version = ... }` braces of the inline table are text, because only `{plain.path}` is a variable. The values here are package names and versions; free text in TOML strings would need a function that escapes `"` and `\`.

::: code-group
<<< @/snippets/examples/configuration-files/cargo-toml/template.toml.tsuku{tsuku} [Template]
<<< @/snippets/examples/configuration-files/cargo-toml/data.php [Data]
<<< @/snippets/examples/configuration-files/cargo-toml/output.toml [Output]
```php [PHP]
echo $tsuku->process($template, $data);
```
:::

## Checking the result

Parse a generated file before it is deployed, for example with `symfony/yaml`:

```php
use Symfony\Component\Yaml\Yaml;

$yaml = $tsuku->process($template, $data);
Yaml::parse($yaml); // throws a ParseException for invalid YAML

file_put_contents('docker-compose.yml', $yaml);
```

## Next Steps

- [Whitespace Control →](/guide/whitespace-control) - the five rules behind indented templates
- [E-commerce →](/examples/ecommerce) - invoices, labels and feeds
- [Conditionals →](/guide/conditionals) - `@if`, `@unless`, `@match` and conditions
