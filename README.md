# 🧱 WordPress Foundation

[![GitHub Release](https://img.shields.io/github/v/release/dimitriBouteille/wp-foundation)](https://github.com/dimitriBouteille/wp-foundation/releases)
[![Compatibility](https://img.shields.io/github/actions/workflow/status/dimitriBouteille/wp-foundation/compatibility.yml?label=compatibility)](https://github.com/dimitriBouteille/wp-foundation/actions/workflows/compatibility.yml)
[![Packagist Downloads](https://img.shields.io/packagist/dt/dbout/wp-foundation?color=yellow)](https://packagist.org/packages/dbout/wp-foundation)

Lightweight application foundation for WordPress: service container, attribute-based routing and WP-CLI commands, Twig views, validation, cache and security hardening, built on Symfony and Illuminate components.

> [!TIP]
> To simplify the integration of this library, we recommend using WordPress with [Bedrock](https://roots.io/bedrock/): the application lives next to WordPress and is installed with Composer.

## Features

- ✅ Service container ([Illuminate](https://laravel.com/docs/container)) with service providers and facades
- ✅ Configuration files merged with the default configuration of the package
- ✅ Routing with PHP attributes on top of [Symfony Routing](https://symfony.com/doc/current/routing.html), controllers discovered from PSR-4 namespaces (wildcards supported)
- ✅ HTTP responses (view, redirect, JSON, errors) and HTTP exceptions
- ✅ WP-CLI commands declared with a `#[Command]` attribute
- ✅ Twig views with WordPress functions, assets (Vite manifest) and image resizing (WebP)
- ✅ Validation with [Symfony Validator](https://symfony.com/doc/current/validation.html), messages translated with the WordPress translations
- ✅ Cache ([Symfony Cache](https://symfony.com/doc/current/components/cache.html)) disabled outside production, cleared with `wp app:cache:clear`
- ✅ Security hardening (HTTP headers, XML-RPC, REST API, user enumeration, ...)

> [!WARNING]
> Most of the [security protections](docs/security.md) are enabled by default: installing the package disables XML-RPC, oEmbed, the author pages and the `/admin` redirection. Review them before installing the package on an existing site.

## Installation

Requires PHP 8.3+.

```bash
composer require dbout/wp-foundation
```

## Quick start

Create the application in a mu-plugin:

```php
use Dbout\WpFoundation\Application;

$basePath = dirname(__DIR__, 3);

Application::configure($basePath)
    ->withPaths(config: $basePath . '/config', bootstrap: $basePath . '/bootstrap')
    ->create();
```

Declare where the controllers and the templates are:

```php
// config/router.php
return [
    'namespaces' => [
        'App\\Controller\\',
    ],
];

// config/twig.php: the `views` directory of `app` is the `@app` Twig namespace
return [
    'modules' => [
        dirname(__DIR__) . '/app',
    ],
];
```

And add a controller:

```php
namespace App\Controller;

use Dbout\WpFoundation\Http\Response\ResponseInterface;
use Dbout\WpFoundation\Http\Response\ViewResponse;
use Dbout\WpFoundation\Routing\Attribute\Route;

#[Route(path: '/hello/{name}', name: 'hello', methods: ['GET'])]
final class Hello
{
    public function __invoke(string $name): ResponseInterface
    {
        return new ViewResponse('@app/hello.twig', ['name' => $name]);
    }
}
```

See [Getting started](docs/getting-started.md) for the details.

## Documentation

The documentation is in the [docs](docs/README.md) directory:

- [Getting started](docs/getting-started.md)
- [Configuration reference](docs/configuration.md)
- [Routing](docs/routing.md)
- [Views](docs/views.md)
- [Validation](docs/validation.md)
- [Console](docs/console.md)
- [Cache](docs/cache.md)
- [Images](docs/images.md)
- [Logging](docs/logging.md)
- [Security](docs/security.md)
- [Troubleshooting](docs/troubleshooting.md)

## Contributing

💕 🦄 We encourage you to contribute to this repository, so everyone can benefit from new features, bug fixes, and any other improvements. Have a look at our [contributing guidelines](CONTRIBUTING.md) to find out how to raise a pull request.
