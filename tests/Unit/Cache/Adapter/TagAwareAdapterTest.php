<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Cache\Adapter;

use Dbout\WpFoundation\Cache\Adapter\TagAwareAdapter;
use Dbout\WpFoundation\Tests\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\ItemInterface;

final class TagAwareAdapterTest extends TestCase
{
    public function testTheDisabledCacheCallsTheCallbackEveryTime(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter());
        $calls = 0;
        $callback = function (ItemInterface $item) use (&$calls): int {
            $item->expiresAfter(3600)->tag(['sessions']);

            return ++$calls;
        };

        self::assertSame(1, $cache->get('sessions', $callback));
        self::assertSame(2, $cache->get('sessions', $callback));
        self::assertFalse($cache->hasItem('sessions'));
    }

    public function testTheEnabledCacheSavesTheValue(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter(), enabled: true);
        $calls = new \ArrayObject();
        $callback = function () use ($calls): string {
            $calls->append(true);

            return 'value';
        };

        $cache->get('sessions', $callback);
        $cache->get('sessions', $callback);

        self::assertCount(1, $calls);
    }

    public function testInvalidatesTheTaggedValues(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter(), enabled: true);
        $cache->get('product_1', function (ItemInterface $item): string {
            $item->tag(['product_1']);

            return 'first';
        });

        $cache->invalidateTags(['product_1']);

        self::assertSame('second', $cache->get('product_1', fn (): string => 'second'));
    }
}
