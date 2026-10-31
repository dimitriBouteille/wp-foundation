# Views

The views are rendered with [Twig](https://twig.symfony.com/doc/3.x/).

## Templates

The templates are in the `views` directory of the modules listed in `config/twig.php`. Each module is a Twig namespace, named after its directory in camelCase:

```php
// config/twig.php
return [
    'modules' => [
        dirname(__DIR__) . '/app/Modules/*',
    ],
];
```

```
app/Modules/
├── Account/views/register.twig         → @account/register.twig
└── TrainingQuote/views/form.twig       → @trainingQuote/form.twig
```

The `modules` are glob patterns. When two modules have the same name, the templates of the last one override the ones of the first one.

### Templates without namespace

The directories of `paths` contain templates rendered without namespace, e.g. the templates of the theme:

```php
// config/twig.php
return [
    'paths' => [
        get_template_directory() . '/templates',
    ],
];
```

```php
echo Twig::render('pages/home.twig');
```

When a template exists in several directories, the first directory wins.

## Render a template

From a controller, return a `ViewResponse` (see [Routing](routing.md)):

```php
return new ViewResponse('@account/register.twig', ['user' => $user]);
```

Anywhere else, use the `Twig` facade:

```php
use Dbout\WpFoundation\Facades\Twig;

echo Twig::render('@account/register.twig', ['user' => $user]);
```

The Twig environment uses `strict_variables`: an undefined variable throws an error. Outside production, the `debug` mode is enabled and the templates are not cached. In production, they are compiled in `var/cache/twig`.

### Environment options

The [options of the environment](https://twig.symfony.com/doc/3.x/api.html#environment-options) are changed with `options`, e.g. to allow the undefined variables or to escape only some templates:

```php
// config/twig.php
return [
    'options' => [
        'strict_variables' => false,
        'autoescape' => [App\Twig\EscapingStrategy::class, 'guess'],
    ],
];
```

```php
namespace App\Twig;

final class EscapingStrategy
{
    public static function guess(string $name): string|false
    {
        return str_starts_with($name, '@') ? 'html' : false;
    }
}
```

> [!WARNING]
> Disabling the autoescape exposes the site to XSS: the variables must then be escaped in the templates (`{{ value|e }}`).

### Replace the renderer

The `ViewResponse` renders the templates with the `ViewRendererInterface` service. Bind your own implementation in a service provider to use another engine:

```php
use Dbout\WpFoundation\View\ViewRendererInterface;

$this->app->singleton(ViewRendererInterface::class, MyRenderer::class);
```

## Extensions

The extensions are listed in `config/twig.php`, as class names (built by the container) or instances:

```php
return [
    'extensions' => [
        App\Twig\MyExtension::class,
    ],
];
```

They are added to the default extensions of the package.

### WordPress

| Function / filter | Description |
|---|---|
| `wp_head()`, `wp_footer()` | The WordPress functions |
| `action('hook_name', ...args)` | Runs `do_action()` |
| `function('function_name', ...args)` | Calls a PHP function or a static method (`function(['MyClass', 'method'])`) |
| `page_title('Title')` | Replaces the title of the document, must be called before `wp_head()` |
| `theme_url('images/logo.svg')` | URL of a file of the theme |
| `admin_url('admin.php')` | The WordPress function |
| `text\|wpautop` | The WordPress function, the text is escaped first: `content\|raw\|wpautop` for a trusted HTML content |

The values returned by `function()`, `theme_url()` and `admin_url()` are escaped like any value, `function()` can return a value of the visitor (e.g. `get_search_query`). Add `|raw` to print the HTML returned by a trusted function:

```twig
{{ function('get_the_post_thumbnail', post.id, 'large')|raw }}
```

The `site` global contains the information of the site:

```twig
<html lang="{{ site.language }}">
<meta charset="{{ site.charset }}">
<a href="{{ site.url }}">{{ site.name }}</a> - {{ site.description }}
```

### Asset functions

| Function | Description |
|---|---|
| `asset_url('area::file')` | URL of an asset |
| `asset_content('area::file')` | Content of an asset, e.g. to inline an SVG |

See [Assets](#assets).

### Images

| Filter | Description |
|---|---|
| `src\|resize(width, height = 0, crop = 'default', force = false)` | Resized copy of an image of the media library, a size of 0 is computed from the ratio |
| `src\|towebp(quality = 80, force = false)` | WebP copy of an image |

```twig
<img src="{{ image.src|resize(800, 600)|towebp }}" alt="{{ image.alt }}">
```

The generated files are deleted with the attachment.

### Optional extensions

These extensions are not enabled by default:

| Extension | Functions |
|---|---|
| `Dbout\WpFoundation\Routing\View\Twig\Extensions\RouterExtension` | `page_url(name, parameters = {})`, see [Routing](routing.md#generate-urls) |
| `Dbout\WpFoundation\Config\View\Twig\Extensions\ConfigExtension` | `config('key', default)`, `config_exist('key')` |

## Assets

The assets are grouped in areas, declared in `config/asset.php` with a resolver:

```php
use Dbout\WpFoundation\View\Resolver\RawResolver;
use Dbout\WpFoundation\View\Resolver\ViteJsResolver;

return [
    'areas' => [
        'theme' => new ViteJsResolver(
            publicPath: 'static/theme',
            root: dirname(__DIR__) . '/web/static/theme',
        ),
        'legacy' => new RawResolver(
            manifest: 'manifest.json',
            root: dirname(__DIR__) . '/web/static/legacy',
        ),
    ],
];
```

An asset is identified by `area::file`:

```php
use Dbout\WpFoundation\Facades\Asset;

Asset::resolveUrl('theme::main.js');      // https://example.com/static/theme/assets/main-4f2a1c.js
Asset::getContent('theme::icons/arrow.svg');
```

| Resolver | Description |
|---|---|
| `ViteJsResolver($publicPath, $root, $manifest = 'manifest.json', $entry = 'index.html')` | Reads the [Vite manifest](https://vite.dev/guide/backend-integration.html): `main.js` and `main.css` are the files of the entry, the images are returned in the public path. `asset_content()` supports the SVG files. |
| `RawResolver($manifest, $root)` | Returns the value of the file in a JSON manifest (`{"app.js": "app.4f2a1c.js"}`) |

A missing asset or manifest returns `null` and logs a warning, so that it does not break the page. A custom resolver implements `ResolverInterface`.
