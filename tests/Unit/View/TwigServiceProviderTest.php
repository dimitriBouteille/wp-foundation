<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\View;

use Dbout\WpFoundation\Cache\Adapter\TagAwareAdapter;
use Dbout\WpFoundation\Cache\Clearer\CacheClearerInterface;
use Dbout\WpFoundation\Cache\Clearer\DirectoryCacheClearer;
use Dbout\WpFoundation\Tests\TestCase;
use Dbout\WpFoundation\View\TwigServiceProvider;
use Dbout\WpFoundation\View\ViewRendererInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\DebugExtension;

final class TwigServiceProviderTest extends TestCase
{
    public function testRendersTheTemplatesOfTheModules(): void
    {
        $app = $this->createApplication([
            'app' => ['env' => 'development'],
            'twig' => ['modules' => [self::fixturePath('Modules') . '/*'], 'extensions' => [DebugExtension::class]],
        ]);
        $app->register(TwigServiceProvider::class);

        $twig = $app->get('twig');
        self::assertInstanceOf(Environment::class, $twig);
        self::assertTrue($twig->isDebug());
        self::assertTrue($twig->isStrictVariables());
        self::assertFalse($twig->getCache());
        self::assertTrue($twig->hasExtension(DebugExtension::class));
        self::assertSame('Post Hello', trim($app->get(ViewRendererInterface::class)->render('@blog/post.twig', ['title' => 'Hello'])));

        $this->expectException(RuntimeError::class);
        $twig->render('@blog/post.twig');
    }

    public function testCachesTheTemplatesInProduction(): void
    {
        $app = $this->createApplication(['app' => ['env' => 'production']]);
        $app->instance('cache', new TagAwareAdapter(new ArrayAdapter(), enabled: true));
        $app->register(TwigServiceProvider::class);

        $twig = $app->get('twig');
        self::assertInstanceOf(Environment::class, $twig);
        self::assertFalse($twig->isDebug());
        self::assertSame($app->getBasePath() . '/var/cache/twig', $twig->getCache());
    }

    public function testRegistersTheCacheClearer(): void
    {
        $app = $this->createApplication();
        $app->register(TwigServiceProvider::class);

        $clearers = iterator_to_array($app->tagged(CacheClearerInterface::TAG));

        self::assertCount(1, $clearers);
        self::assertInstanceOf(DirectoryCacheClearer::class, $clearers[0]);
        self::assertSame('Twig templates', $clearers[0]->getName());
    }

    public function testTheExtensionsMustImplementTheTwigInterface(): void
    {
        $app = $this->createApplication(['twig' => ['extensions' => [\stdClass::class]]]);
        $app->register(TwigServiceProvider::class);

        $this->expectExceptionMessage('Twig extension "stdClass" must implement');

        $app->get('twig');
    }
}
