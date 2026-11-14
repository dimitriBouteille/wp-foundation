# Modules

A module groups the code of a feature (controllers, commands, views, service providers, ...). Each site enables the modules it needs: the same modules can be shared by several sites, for example from a Composer package.

## Declare a module

A module is a class next to the code of the feature, usually extending `AbstractModule`:

```
app/Modules/Blog/
├── BlogModule.php
├── BlogServiceProvider.php
├── Console/                ← WP-CLI commands
├── Controller/             ← controllers
└── views/                  ← Twig templates, namespace @blog
```

```php
namespace App\Modules\Blog;

use App\Modules\Newsletter\NewsletterModule;
use Dbout\WpFoundation\Module\AbstractModule;

final class BlogModule extends AbstractModule
{
    public function getDependencies(): array
    {
        return [NewsletterModule::class];
    }

    public function getProviders(): array
    {
        return [BlogServiceProvider::class];
    }
}
```

`AbstractModule` finds the configuration from the directories next to the class, when they exist:

| Directory | Configuration |
|---|---|
| `Controller/` | the namespace is added to `router.namespaces` |
| `Console/` | the namespace is added to `console.namespaces` |
| `views/` | the module directory is added to `twig.modules`, the namespace of the templates is the camelCase name of the directory (`@blog`, `@userAccount`) |

A module can implement `ModuleInterface` instead, to declare its configuration without convention.

## Enable the modules

The modules of the site are listed in `bootstrap/modules.php`, next to `providers.php`:

```php
// bootstrap/modules.php
return [
    App\Modules\Newsletter\NewsletterModule::class,
    App\Modules\Blog\BlogModule::class,
];
```

Modules can also be added with `withModules()`:

```php
Application::configure($basePath)
    ->withPaths(config: $basePath . '/config', bootstrap: $basePath . '/bootstrap')
    ->withModules([App\Modules\Blog\BlogModule::class])
    ->create();
```

When the application is created, once the configuration is loaded:

1. the modules are built by the container, a module listed twice is kept once;
2. the dependencies are checked: an exception names the modules to enable when a dependency is missing;
3. the configuration of the modules is added to the configuration lists, in the order of the modules;
4. the service providers of the modules are registered after the ones of the package and of the project.

The dependencies are checked, not sorted: the order of the modules is the order of the list, a module can be declared before its dependencies and two modules can depend on each other.

Since the configuration of the modules is added before any service provider is registered, a provider can read it in its `register()` method.

## Other options

Override `getConfiguration()` to add other options, for example the directories read by a package. The values are added to the lists of the configuration:

```php
final class BlogModule extends AbstractModule
{
    public function getConfiguration(): array
    {
        return array_merge_recursive(parent::getConfiguration(), [
            'hook.paths' => $this->pathIfExists('Hooks'),
            'api.namespaces' => $this->namespaceIfExists('Api/Routes'),
        ]);
    }
}
```

To share these conventions, create an abstract module in the project and extend it in each module.

- `pathIfExists('Hooks')` returns the absolute path of the directory, or an empty list when it does not exist;
- `namespaceIfExists('Api/Routes')` returns its namespace with a trailing backslash (`App\Modules\Blog\Api\Routes\`);
- `getPath()` and `getNamespace()` return the directory and the namespace of the module class.

## Read the enabled modules

```php
use App\Modules\Blog\BlogModule;

if (app()->getModules()->has(BlogModule::class)) {
    // ...
}
```

`getModules()` returns the `ModuleList` (also bound as `modules`):

- `has()`, `get()` and `all()` return the enabled modules;
- `getHash()` returns a hash of the enabled modules, to add to the key of a cache that depends on them.
