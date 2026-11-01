# Security

The package hardens WordPress with the options of `config/security.php`.

Only the protections without visible effect are enabled by default (HTTP headers, XML-RPC, REST API discovery). The others change the behavior of the site: enable the ones you need in `config/security.php`.

## Default configuration

```php
// config/security.php of the package
return [
    'headers' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ],
    'disableXmlRpc' => true,
    'hideVersion' => false,
    'preventUserEnumeration' => false,
    'disableOEmbed' => false,
    'hideRestApiDiscovery' => true,
    'disableAdminRedirects' => false,
    'restApi' => [
        'restricted' => false,
        'publicNamespaces' => [],
        'capability' => 'manage_options',
    ],
];
```

The options of the project replace the default ones, except `headers` and `restApi`, which are merged with them:

```php
// config/security.php of the project
return [
    'disableOEmbed' => true,
    'headers' => [
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
    ],
];
```

## HTTP headers

`headers` are sent on all the responses outside the administration, on `init` (before the router): the responses of the controllers (`Response`, `JsonResponse`, ...) and the REST API responses get them too. Set a header to `null` to not send it:

```php
'headers' => [
    'X-Frame-Options' => null,
],
```

`Content-Security-Policy` is not sent by default: it depends on the scripts of each site (analytics, maps, payment, ...).

## Options

| Option | Default | Effect |
|---|---|---|
| `disableXmlRpc` | `true` | Disables the XML-RPC methods requiring authentication, removes the `X-Pingback` header and the RSD / WLW links |
| `hideVersion` | `false` | Removes the generator tag and the `ver` query argument of the front-end scripts and styles |
| `preventUserEnumeration` | `false` | Returns a 404 on the author pages and the `?author=` requests, the author links point to the home page, the users routes of the REST API (`/wp/v2/users`) are hidden to the visitors who are not logged in, and the author of the oEmbed data is replaced by the site |
| `disableOEmbed` | `false` | Disables the oEmbed routes, discovery links and rewrite rules |
| `hideRestApiDiscovery` | `true` | Removes the links advertising the REST API (head, HTTP header, RSD) |
| `disableAdminRedirects` | `false` | Disables the redirections of `/admin`, `/login`, `/dashboard`, `/wp-admin` and `/wp-login.php`, so that a custom admin location is not revealed |
| `restApi.restricted` | `false` | Hides the REST routes to the users without the `restApi.capability`, except the `restApi.publicNamespaces` |

### Points of attention

- `disableXmlRpc` does not block `xmlrpc.php`: the public methods (e.g. `system.listMethods`) still answer. Block the file in the web server configuration to disable XML-RPC completely.
- `hideVersion` also removes the cache busting of the WordPress and plugins assets: after an update, the browsers can use the old files until their cache expires.
- `disableOEmbed` removes the rewrite rules on the next flush (Settings > Permalinks > Save).
- `preventUserEnumeration` disables the author archives: do not enable it if the theme displays them.

## REST API

With `restApi.restricted`, the visitors only see the routes of the public namespaces:

```php
'restApi' => [
    'restricted' => true,
    'publicNamespaces' => [
        'app/v1',
        'wc/store',
    ],
],
```

The users with the capability (`manage_options` by default) see all the routes.

> [!CAUTION]
> The block editor uses the `wp/v2` routes: the users without the capability (e.g. the editors) can no longer use it. Use a capability they have, e.g. `'capability' => 'edit_posts'`, or add `wp/v2` to the public namespaces.
