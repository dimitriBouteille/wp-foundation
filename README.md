# WordPress Foundation

[![GitHub Release](https://img.shields.io/github/v/release/dimitriBouteille/wp-foundation)](https://github.com/dimitriBouteille/wp-foundation/releases)
[![Compatibility](https://img.shields.io/github/actions/workflow/status/dimitriBouteille/wp-foundation/compatibility.yml?label=compatibility)](https://github.com/dimitriBouteille/wp-foundation/actions/workflows/compatibility.yml)
[![Packagist Downloads](https://img.shields.io/packagist/dt/dbout/wp-foundation?color=yellow)](https://packagist.org/packages/dbout/wp-foundation)

Lightweight application foundation for WordPress: service container, attribute-based routing and WP-CLI commands, Twig views, validation, cache and security hardening, built on Symfony and Illuminate components.

## Philosophy

WordPress is a great CMS, but building a business application on top of it often ends with code scattered in `functions.php`, global functions and hooks everywhere. This package brings the structure of a modern PHP application to WordPress, **without replacing WordPress**:

- **WordPress stays in charge.** The content, the admin, the plugins and the hooks work as usual: the package only adds what WordPress lacks (a container, controllers, a validator, ...). An URL without a route is handled by WordPress.
- **Proven components, no reinvented wheel.** The routing, the validation and the cache come from Symfony, the container and the logger from Illuminate: their documentation and their knowledge apply here.
- **Configuration over code.** The behavior is driven by configuration files merged with sensible defaults, and the classes are discovered from their PSR-4 namespaces: adding a controller or a command is adding a class.
- **Lightweight and explicit.** No magic: a provider registers services and adds hooks, a controller returns a response. The package stays small enough to be read and understood.

### The best of Laravel and Symfony

Rather than choosing a side, the package takes from each framework what it does best, as standalone components:

- **From Laravel**, the developer experience: a container with automatic injection, service providers, facades, configuration files and a logger that are simple to use day to day.
- **From Symfony**, the robust and well-tested building blocks: routing, HTTP foundation, validation and cache, used by thousands of projects (Laravel itself relies on several of them).

Only these components are installed, not the full frameworks: WordPress keeps its own bootstrap, and the package stays light.

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
