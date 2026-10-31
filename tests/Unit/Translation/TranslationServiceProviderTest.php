<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Translation;

use Brain\Monkey\Functions;
use Dbout\WpFoundation\Tests\TestCase;
use Dbout\WpFoundation\Translation\TranslationServiceProvider;

final class TranslationServiceProviderTest extends TestCase
{
    public function testLoadsTheTranslationsOnInitAndWhenTheLocaleChanges(): void
    {
        $provider = new TranslationServiceProvider();
        $provider->boot();

        self::assertSame(0, has_action('init', [$provider, 'loadTextDomain']));
        self::assertSame(10, has_action('change_locale', [$provider, 'loadTextDomain']));
    }

    public function testLoadsTheTranslationFileOfTheLocale(): void
    {
        Functions\when('determine_locale')->justReturn('fr_FR');
        Functions\expect('unload_textdomain')->once()->with('wp-foundation');
        Functions\expect('load_textdomain')->once()->with('wp-foundation', \Mockery::on(fn (string $path): bool => realpath($path) === realpath(dirname(__DIR__, 3) . '/languages/wp-foundation-fr_FR.mo')));

        (new TranslationServiceProvider())->loadTextDomain();
    }
}
