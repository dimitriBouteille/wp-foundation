# Getting started

## Installation

```bash
composer require dbout/wp-foundation
```

The package requires PHP 8.3+ and is loaded with the Composer autoloader, so WordPress must load `vendor/autoload.php` (it is the case with [Bedrock](https://roots.io/bedrock/)).

## Create the application

The application is created by a mu-plugin, so that it is available to the plugins and the theme:

```php
<?php
// web/app/mu-plugins/app.php

use Dbout\WpFoundation\Application;

$basePath = dirname(__DIR__, 3);

Application::configure($basePath)
    ->withPaths(
        config: $basePath . '/config',
        bootstrap: $basePath . '/bootstrap',
    )
    ->create();
```

`create()` loads the configuration, registers the service providers then boots them. The application is then available everywhere with the `app()` helper or the facades.

## Lifecycle

```
mu-plugin                       Application::configure()->create()
 ├─ load the configuration       config/*.php merged with the default configuration
 ├─ register the providers       register() of each provider (package, then project)
 └─ boot the providers           boot() of each provider: the WordPress hooks are added

init (priority 0)               the translations of the package are loaded
init (priority 10)              the router matches the URL
 ├─ no route                     WordPress handles the request as usual
 └─ route                        the controller is executed
     ├─ RedirectResponse, JsonResponse, Response, ErrorResponse   sent immediately (the script stops)
     ├─ NotFoundResponse                                           WordPress renders its 404 template
     └─ ViewResponse                                               rendered at the end of template_redirect

cli_init                        the WP-CLI commands are registered (WP-CLI only)
```

The application is created before the plugins are loaded: the services are available in the plugins and the theme, but a service provider must use hooks (i.e. `plugins_loaded`, `init`) to use the functions of the plugins.

The controllers are executed on `init` with the priority 10: the callbacks of `init` with a higher priority are not executed when the response stops the script (redirection, JSON, ...).

## Project structure

```
project/
├── bootstrap/
│   └── providers.php      ← service providers of the project
├── config/                ← configuration of the project
│   ├── router.php
│   └── twig.php
├── app/                   ← code of the project (PSR-4 namespace App\)
│   ├── Controller/
│   ├── Command/
│   └── views/
├── var/
│   ├── cache/
│   └── logs/
└── web/                   ← WordPress
```

Only the paths given to `withPaths()` are used: without a `config` path, the default configuration of the package is used, and without a `bootstrap` path, only the default service providers are registered.

## Configuration

Each file of the `config` directory returns an array, available with the file name as key:

```php
// config/router.php
return [
    'namespaces' => [
        'App\\Controller\\',
    ],
];
```

```php
use Dbout\WpFoundation\Facades\Config;

Config::get('router.namespaces');
```

The files of the project are merged with the default configuration of the package (see the [config](../config) directory): the options of the project replace the default ones, except the following lists, which are added to the default ones:

| File | Merged options |
|---|---|
| `asset.php` | `areas` |
| `console.php` | `namespaces` |
| `logging.php` | `channels` |
| `router.php` | `namespaces`, `customPaths` |
| `security.php` | `headers`, `restApi` |
| `twig.php` | `extensions`, `modules` |

A closure is resolved when the option is read, so that WordPress functions can be used in the configuration:

```php
return [
    'env' => fn () => wp_get_environment_type(),
];
```

The files of a sub-directory are available with the directory name as prefix (`config/services/api.php` → `services.api`).

## Environment

The environment is defined by the `app.env` option, the WordPress environment type (`WP_ENVIRONMENT_TYPE`) by default.

In production, the caches are enabled: the cache pool, the compiled Twig templates and the paths of the Twig modules. In the other environments, they are disabled, so that the changes are visible immediately.

```php
app()->isProduction();
app()->isEnvironment('staging');
```

## Service providers

The service providers of the project are listed in `bootstrap/providers.php`:

```php
// bootstrap/providers.php
return [
    App\AppServiceProvider::class,
];
```

A service provider is a plain class built with the application. All the providers are registered (`register()`), then booted (`boot()`):

```php
namespace App;

use Dbout\WpFoundation\Application;

final class AppServiceProvider
{
    public function __construct(
        private Application $app,
    ) {}

    public function register(): void
    {
        $this->app->singleton(MyService::class);
    }

    public function boot(): void
    {
        add_action('init', fn () => app(MyService::class)->init());
    }
}
```

- `register()` only adds the services to the container, the other providers may not be registered yet.
- `boot()` is called once all the providers are registered, it is the place to add the WordPress hooks.

The providers of the package are registered before the ones of the project: router, WP-CLI commands, Twig, assets, images, security and translations.

Providers can also be added with `withProviders()`:

```php
Application::configure($basePath)
    ->withProviders([App\AppServiceProvider::class])
    ->create();
```

## Directories

The directories used by the package are relative to the base path:

| Directory | Default path |
|---|---|
| `Directory::Root` | the base path |
| `Directory::Var` | `var` |
| `Directory::Cache` | `var/cache` |
| `Directory::Log` | `var/logs` |

A directory can be moved with an absolute path, the other directories keep their default path:

```php
use Dbout\WpFoundation\Filesystem\Directory;

Application::configure($basePath)
    ->withDirectory(Directory::Cache, '/tmp/my-project/cache')
    ->create();
```

```php
use Dbout\WpFoundation\Facades\Dir;

Dir::getPath(Directory::Log, 'app.log');
```

The project can add its own directories with an enum implementing `DirectoryInterface`:

```php
use Dbout\WpFoundation\Filesystem\DirectoryInterface;

enum AppDirectory implements DirectoryInterface
{
    case Uploads;

    public function getPath(): string
    {
        return match ($this) {
            self::Uploads => 'web/app/uploads',
        };
    }
}
```

## Facades

| Facade | Service |
|---|---|
| `App` | The application |
| `Asset` | Asset resolver |
| `Cache` | Cache pool |
| `Config` | Configuration |
| `Dir` | Directories |
| `Log` | Logger |
| `Router` | Router |
| `Twig` | Twig environment |

The facades are in the `Dbout\WpFoundation\Facades` namespace.
