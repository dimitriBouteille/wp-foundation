<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\View\Resolver;

use Dbout\WpFoundation\Tests\TestCase;
use Dbout\WpFoundation\View\Resolver\RawResolver;
use Dbout\WpFoundation\View\Resolver\ViteJsResolver;

final class ResolverTest extends TestCase
{
    public function testTheViteResolverResolvesTheEntryFiles(): void
    {
        $resolver = new ViteJsResolver('/static/v2/theme/', self::fixturePath('vite'));

        self::assertSame('/static/v2/theme/assets/index-abc123.js', $resolver->resolve('main.js'));
        self::assertSame('/static/v2/theme/assets/index-def456.css', $resolver->resolve('main.css'));
        self::assertSame('/static/v2/theme/images/logo.svg', $resolver->resolve('/images/logo.svg'));
        self::assertSame('/static/v2/theme/images/photo.WEBP', $resolver->resolve('images/photo.WEBP'));
        self::assertNull($resolver->resolve('app.js'));
        self::assertSame(self::fixturePath('vite'), $resolver->getRootPath());
    }

    public function testTheViteResolverReturnsThePathOfTheSvg(): void
    {
        $resolver = new ViteJsResolver('/static', self::fixturePath('vite'));

        self::assertSame(self::fixturePath('vite/images/logo.svg'), $resolver->getPath('/images/logo.svg'));
        self::assertNull($resolver->getPath('images/photo.png'));
    }

    public function testTheViteResolverUsesTheGivenEntry(): void
    {
        $resolver = new ViteJsResolver('/static', self::fixturePath('vite'), entry: 'admin.html');

        self::assertNull($resolver->resolve('main.js'));
    }

    public function testTheRawResolverReturnsTheValueOfTheManifest(): void
    {
        $resolver = new RawResolver('manifest.json', self::fixturePath('raw'));

        self::assertSame('/static/app.123.js', $resolver->resolve('app.js'));
        self::assertNull($resolver->resolve('nested'));
        self::assertNull($resolver->resolve('unknown.js'));
        self::assertNull($resolver->getPath('app.js'));
    }

    public function testThrowsWhenTheManifestDoesNotExist(): void
    {
        $this->expectExceptionMessage('file not found');

        (new RawResolver('missing.json', self::fixturePath('raw')))->resolve('app.js');
    }

    public function testThrowsWhenTheManifestIsInvalid(): void
    {
        $this->expectExceptionMessage('is invalid');

        (new RawResolver('manifest.json', self::fixturePath('invalid')))->resolve('app.js');
    }
}
