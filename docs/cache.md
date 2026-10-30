# Cache

The cache pool is a [Symfony Cache](https://symfony.com/doc/current/components/cache.html) pool with tags, stored in `var/cache/data`.

> [!NOTE]
> The cache is only enabled in production (see [Environment](getting-started.md#environment)): in the other environments, the callback is executed on every call, so that the changes are visible immediately.

## Cache a value

```php
use Dbout\WpFoundation\Facades\Cache;
use Symfony\Contracts\Cache\ItemInterface;

$sessions = Cache::get('next_sessions_' . $productId, function (ItemInterface $item) use ($productId): array {
    $item->expiresAfter(3600);

    return $this->sessions->findNext($productId);
});
```

The callback is called when the value is not in the cache, its result is saved then returned. The key must not contain the characters `{}()/\@:`.

```php
Cache::delete('next_sessions_' . $productId);
```

## Tags

A value can be tagged, to invalidate all the values of a tag at once:

```php
$sessions = Cache::get('next_sessions_' . $productId, function (ItemInterface $item) use ($productId): array {
    $item->tag(['sessions', 'product_' . $productId]);

    return $this->sessions->findNext($productId);
});

// e.g. when a session is saved
Cache::invalidateTags(['product_' . $productId]);
```

## PSR-6

The pool implements the [PSR-6](https://www.php-fig.org/psr/psr-6/) interfaces, to give it to the libraries expecting a `CacheItemPoolInterface`:

```php
$pool = app()->getCache();

$item = $pool->getItem('my_key');
if (!$item->isHit()) {
    $item->set($value);
    $pool->save($item);
}
```

> [!WARNING]
> Only `get()` is disabled outside production: the PSR-6 methods always read and write the cache.

## Clear the cache

```bash
wp app:cache:clear
```

Run the command on each deployment. See [Console](console.md#clear-the-cache) to clear your own caches with the command.
