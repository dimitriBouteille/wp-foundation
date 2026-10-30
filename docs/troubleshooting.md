# Troubleshooting

## The route is not found

- Check that the namespace of the controller is in `router.namespaces` and exists in the Composer autoloader (`composer dump-autoload` after adding a namespace).
- Check that the file follows PSR-4: the class name is derived from the file path, a class named differently from its file is ignored.
- The trailing slash is ignored: `/account/register/` matches the `/account/register` route.
- A route only matches its `methods`: a `POST` on a `GET` route is handled by WordPress (404).
- The routes are matched on `init`: a plugin redirecting earlier (e.g. on `plugins_loaded`) takes precedence.

## "Cannot declare class ..." fatal error

A file of a controller or command namespace declares a class already loaded, because its namespace or name does not match its path. Fix the file so that it follows PSR-4.

## "Variable ... does not exist" in a template

The Twig environment uses `strict_variables`. Check that the variable exists with `is defined` or give it a default value:

```twig
{% if user is defined %}...{% endif %}
{{ title|default('') }}
```

## The controller must return an instance of ResponseInterface

The action returned something other than a [response](routing.md#responses), e.g. it echoed the HTML and returned nothing. Return a `ViewResponse` instead.

## The changes are not visible in production

In production, the cache pool and the compiled Twig templates are cached. Clear them on each deployment:

```bash
wp app:cache:clear
```

## app:cache:clear fails with "Unable to delete ..."

The cache files belong to the user of the web server (e.g. `www-data`) and the command is run with another user. Run it with the user of the web server:

```bash
sudo -u www-data wp app:cache:clear
```

## The command is not listed

- Check that the class has the `#[Command]` attribute and that its namespace is in `console.namespaces`.
- The commands are only registered with WP-CLI: `wp help` lists them.

## The validation messages are in English

The translations are loaded on `init` for the locale of the site. Check the locale (Settings > General > Site Language) and that a translation exists for it (see [Validation](validation.md#translations)).

## The site behaves differently after installing the package

Most of the security protections are enabled by default (no XML-RPC, no oEmbed, no author pages, ...). See [Security](security.md) to disable them.
