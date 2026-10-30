# Console

The [WP-CLI](https://wp-cli.org/) commands are classes with the `#[Command]` attribute.

## Create a command

```php
namespace App\Command;

use Dbout\WpFoundation\Console\Attribute\Command;
use Dbout\WpFoundation\Console\Concerns\InteractsWithOutput;

#[Command(name: 'app:orders:cancel', description: 'Cancel the pending orders.')]
final class CancelOrders
{
    use InteractsWithOutput;

    public function __construct(
        private OrderRepository $orders,
    ) {}

    /**
     * @param string[] $args
     * @param array<string, string> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $count = $this->orders->cancelPending();

        $this->successMessage(sprintf('%d orders cancelled.', $count));
    }
}
```

```bash
wp app:orders:cancel
```

| Argument | Description |
|---|---|
| `name` | Name of the command |
| `description` | Short description displayed by `wp help` |

The command is built by the container, so the dependencies of its constructor are injected. Like any WP-CLI command, a class without `__invoke()` defines a sub-command per public method (`wp app:orders cancel`), and the docblock of the methods defines their arguments (see the [WP-CLI documentation](https://make.wordpress.org/cli/handbook/guides/commands-cookbook/)).

### Output

The `InteractsWithOutput` trait adds:

| Method | Description |
|---|---|
| `successMessage($message)` | Message prefixed with "Success:" |
| `warningMessage($message)` | Message prefixed with "Warning:" |
| `errorMessage($message, $exit = true)` | Message prefixed with "Error:", then stops the command with the exit code 1 |

## Discovery

The commands are found from the PSR-4 namespaces of `config/console.php`, like the controllers (`*` wildcards supported):

```php
// config/console.php
return [
    'namespaces' => [
        'App\\Command\\',
    ],
];
```

The classes without the `#[Command]` attribute are ignored. The commands are registered on `cli_init`, so only when WP-CLI runs.

## Clear the cache

```bash
wp app:cache:clear
```

The command runs the cache clearers, then the `app_cache_cleared` action. The package clears:

- the cache pool (`var/cache/data`),
- the compiled Twig templates (`var/cache/twig`).

The command fails (exit code 1) when a cache cannot be cleared, e.g. when the files belong to another user: run it with the user of the web server on deployment.

### Add a cache

A cache clearer implements `CacheClearerInterface` and is tagged in the container:

```php
use Dbout\WpFoundation\Cache\Clearer\CacheClearerInterface;
use Dbout\WpFoundation\Cache\Clearer\DirectoryCacheClearer;
use Dbout\WpFoundation\Facades\Dir;
use Dbout\WpFoundation\Filesystem\Directory;

// In the register() method of a service provider
$this->app->bind('app.pdf_cache_clearer', fn () => new DirectoryCacheClearer(
    name: 'Generated PDF',
    directory: Dir::getPath(Directory::Cache, 'pdf'),
));
$this->app->tag('app.pdf_cache_clearer', CacheClearerInterface::TAG);
```

| Clearer | Description |
|---|---|
| `PoolCacheClearer($pool, $name = 'Cache pool')` | Clears a PSR-6 cache pool |
| `DirectoryCacheClearer($name, $directory)` | Deletes a directory |

Other caches can also be cleared with the action:

```php
add_action('app_cache_cleared', fn () => delete_transient('my_transient'));
```
