# 🧱 WordPress Foundation

[![GitHub Release](https://img.shields.io/github/v/release/dimitriBouteille/wp-foundation)](https://github.com/dimitriBouteille/wp-foundation/releases)
[![Compatibility](https://img.shields.io/github/actions/workflow/status/dimitriBouteille/wp-foundation/compatibility.yml?label=compatibility)](https://github.com/dimitriBouteille/wp-foundation/actions/workflows/compatibility.yml)
[![Packagist Downloads](https://img.shields.io/packagist/dt/dbout/wp-foundation?color=yellow)](https://packagist.org/packages/dbout/wp-foundation)

Lightweight application foundation for WordPress: service container, attribute-based routing and WP-CLI commands, Twig views, validation, cache and security hardening, built on Symfony and Illuminate components.

## Installation

```bash
composer require dbout/wp-foundation
```

Requires PHP 8.3+.

## Usage

Create the application in a mu-plugin:

```php
use Dbout\WpFoundation\Application;

Application::configure($basePath)
    ->withPaths(config: $basePath . '/config', bootstrap: $basePath . '/bootstrap')
    ->create();
```

- `config/*.php`: configuration merged with the default one of the package (see the `config` directory).
- `bootstrap/providers.php`: list of the service providers of the project.

## License

MIT
