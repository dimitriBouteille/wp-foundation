<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Cache\Adapter;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\CacheItem;

/**
 * Tag-aware cache adapter with a toggle to disable caching entirely.
 *
 * Extends Symfony's {@see \Symfony\Component\Cache\Adapter\TagAwareAdapter} to add an `$enabled` flag.
 * When enabled, behaves exactly like the parent: items are cached, TTLs are respected,
 * and tag-based invalidation works normally.
 *
 * When disabled, the callback is still executed with a valid {@see CacheItem} (so callers
 * can safely call `$item->expiresAfter()` or `$item->tag()` without errors), but the
 * result is never persisted — effectively making every call a cache miss.
 *
 * This is useful in development or testing environments where caching should be
 * transparently bypassed without changing consumer code.
 */
class TagAwareAdapter extends \Symfony\Component\Cache\Adapter\TagAwareAdapter
{
    public function __construct(
        AdapterInterface $itemsPool,
        ?AdapterInterface $tagsPool = null,
        float $knownTagVersionsTtl = 0.15,
        private readonly bool $enabled = false,
    ) {
        parent::__construct($itemsPool, $tagsPool, $knownTagVersionsTtl);
    }

    /**
     * @inheritDoc
     * @param array<mixed>|null $metadata
     */
    public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed
    {
        if ($this->enabled) {
            return parent::get($key, $callback, $beta, $metadata);
        }

        $save = true;

        return $callback($this->createNoOpItem($key), $save);
    }

    /**
     * Creates a no-op CacheItem used when the cache is disabled,
     * so the callback receives a valid ItemInterface instance.
     */
    private function createNoOpItem(string $key): CacheItem
    {
        return $this->getItem($key);
    }
}
