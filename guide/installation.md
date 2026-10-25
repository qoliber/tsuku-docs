# Installation

## Requirements

- **PHP**: 8.1 or higher
- **Composer**: Latest version recommended

Tsuku has **zero external dependencies** - it's a standalone library.

## Via Composer (Recommended)

Install Tsuku using Composer:

```bash
composer require qoliber/tsuku
```

This will:
1. Download Tsuku
2. Set up autoloading
3. Make it available in your project

## Manual Installation

If you prefer not to use Composer, you can install manually:

1. Download the latest release from GitHub
2. Extract it into your project
3. Register an autoloader for the `Qoliber\Tsuku\` namespace (a release archive contains `src/`, not a `vendor/` directory):

```php
spl_autoload_register(function (string $class): void {
    $prefix = 'Qoliber\\Tsuku\\';
    if (str_starts_with($class, $prefix)) {
        require '/path/to/tsuku/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});
```

::: warning
Manual installation is not recommended. Composer handles autoloading and updates automatically.
:::

## Verify Installation

Create a test file to verify installation:

```php
<?php

require 'vendor/autoload.php';

use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

$result = $tsuku->process('Hello, {name}!', ['name' => 'World']);

echo $result; // Outputs: Hello, World!
```

If you see "Hello, World!" - you're all set! 🎉

## Version Information

This documentation describes **Tsuku 1.4.1**. Check your installed version:

```bash
composer show qoliber/tsuku
```

To require 1.4 or later explicitly:

```bash
composer require qoliber/tsuku:^1.4
```

## Updating

Update to the latest version:

```bash
composer update qoliber/tsuku
```

::: tip Coming from 1.3?
1.4.0 changes the whitespace around directives and throws on malformed conditions that 1.3.0 evaluated silently. Read [Upgrading from 1.3](/guide/upgrading) before updating, and see the [Changelog](/changelog).
:::

## Development Installation

For contributing or testing:

```bash
# Clone repository
git clone https://github.com/qoliber/tsuku.git
cd tsuku

# Install dev dependencies
composer install

# Run tests
composer test

# Run mutation tests
composer test:mutation

# Run static analysis
composer analyse

# Check code style
composer cs:check

# Run the benchmarks
php benchmarks/run-all.php
```

## PHP Version Compatibility

| Tsuku Version | PHP Version |
|---------------|-------------|
| 1.x | 8.1+ |

Tsuku uses modern PHP features:
- Constructor property promotion
- Match expressions
- Enums
- Strict types

## Troubleshooting

### Composer not found

Install Composer from [getcomposer.org](https://getcomposer.org/)

### PHP version too old

```bash
# Check your PHP version
php -v

# Update PHP if needed (Ubuntu/Debian)
sudo apt update
sudo apt install php8.1
```

### Autoloader not working

Make sure you're requiring the correct autoloader:

```php
require __DIR__ . '/vendor/autoload.php';
```

## Next Steps

Now that Tsuku is installed:

- [Getting Started →](/guide/getting-started) - Create your first template
- [Template Syntax →](/guide/variables) - Learn the syntax
- [Examples →](/examples/) - See real-world use cases
