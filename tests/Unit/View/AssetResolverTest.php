<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\View;

use Brain\Monkey\Functions;
use Dbout\WpFoundation\Facades\Log;
use Dbout\WpFoundation\Tests\TestCase;
use Dbout\WpFoundation\View\AssetResolver;
use Dbout\WpFoundation\View\Resolver\RawResolver;
use Dbout\WpFoundation\View\Resolver\ViteJsResolver;

final class AssetResolverTest extends TestCase
{
    private AssetResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        Functions\when('home_url')->justReturn('https://example.com/');
        $this->resolver = new AssetResolver([
            'theme' => new ViteJsResolver('/static/theme', self::fixturePath('vite')),
            'raw' => new RawResolver('manifest.json', self::fixturePath('raw')),
        ]);
    }

    public function testResolvesTheUrlWithTheResolverOfTheArea(): void
    {
        self::assertSame('https://example.com/static/theme/assets/index-abc123.js', $this->resolver->resolveUrl('theme::main.js'));
        self::assertSame('https://example.com/static/app.123.js', $this->resolver->resolveUrl('raw::app.js'));
        self::assertNull($this->resolver->resolveUrl('raw::unknown.js'));
    }

    public function testReadsTheContentOfTheFile(): void
    {
        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"></svg>', trim((string) $this->resolver->getContent('theme::images/logo.svg')));
        self::assertNull($this->resolver->getContent('theme::images/missing.svg'));
        self::assertNull($this->resolver->getContent('theme::main.js'));
    }

    public function testLogsTheInvalidAssetsOnceWithoutThrowing(): void
    {
        Log::shouldReceive('warning')->once()->with(\Mockery::pattern('/^Unable to resolve the asset unknown::main.js: Invalid area\./'), \Mockery::any());
        Log::shouldReceive('warning')->once()->with(\Mockery::pattern('/^Unable to resolve the asset main.js: Invalid file path/'), \Mockery::any());

        self::assertNull($this->resolver->resolveUrl('unknown::main.js'));
        self::assertNull($this->resolver->resolveUrl('unknown::main.js'));
        self::assertNull($this->resolver->resolvePath('main.js'));
    }

    public function testDoesNotThrowWhenTheLoggerIsNotAvailable(): void
    {
        self::assertNull($this->resolver->resolveUrl('unknown::main.js'));
    }
}
