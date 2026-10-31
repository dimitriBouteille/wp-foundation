# Configuration reference

The default configuration of the package is in the [config](../config) directory. The files of the project replace these options, except the lists marked as **merged**, which are added to the default ones (see [Configuration](getting-started.md#configuration)).

## app.php

| Option | Default | Description |
|---|---|---|
| `env` | `wp_get_environment_type()` | Environment of the application, the caches are enabled in `production` |
| `locale` | `determine_locale()` | Locale of the dates (Carbon) |
| `date.translations` | `[]` | [Carbon translations](https://carbon.nesbot.com/docs/#api-localization) replacing the default ones of the locale |

## asset.php

| Option | Default | Description |
|---|---|---|
| `areas` (merged) | `[]` | Resolvers of the assets, by area name, see [Views](views.md#assets) |

## console.php

| Option | Default | Description |
|---|---|---|
| `namespaces` (merged) | `['Dbout\WpFoundation\*\Console\']` | PSR-4 namespaces of the commands, see [Console](console.md#discovery) |

## logging.php

| Option | Default | Description |
|---|---|---|
| `default` | `'app'` | Default channel |
| `channels` (merged) | `app`: daily file `var/logs/app.log` kept 25 days, `emergency`: file `var/logs/laravel.log` of the emergency logger (used when a channel fails) | Channels, see [Logging](logging.md#channels) |

## router.php

| Option | Default | Description |
|---|---|---|
| `namespaces` (merged) | `[]` | PSR-4 namespaces of the controllers, see [Routing](routing.md#discovery) |
| `customPaths` (merged) | `['home' => home URL]` | URLs generated with `Router::generate()` that are not routes, see [Routing](routing.md#custom-paths) |

## security.php

| Option | Default | Description |
|---|---|---|
| `headers` (merged) | `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` | HTTP headers of the front-end responses |
| `disableXmlRpc` | `true` | |
| `hideVersion` | `false` | |
| `preventUserEnumeration` | `false` | |
| `disableOEmbed` | `false` | |
| `hideRestApiDiscovery` | `true` | |
| `disableAdminRedirects` | `false` | |
| `restApi` (merged) | `restricted: false`, `publicNamespaces: []`, `capability: 'manage_options'` | |

See [Security](security.md) for the effect of each option.

## twig.php

| Option | Default | Description |
|---|---|---|
| `extensions` (merged) | `AssetExtension`, `ImageExtension`, `WordPressExtension` | Twig extensions, see [Views](views.md#extensions) |
| `modules` (merged) | `[]` | Glob patterns of the modules whose `views` directory is a Twig namespace, see [Views](views.md#templates) |
| `paths` (merged) | `[]` | Directories of the templates without namespace, see [Views](views.md#templates-without-namespace) |
| `options` (merged) | `[]` | [Options of the Twig environment](https://twig.symfony.com/doc/3.x/api.html#environment-options), replacing the default ones (`strict_variables`, `debug`, `cache`), see [Views](views.md#environment-options) |
