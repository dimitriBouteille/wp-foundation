<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Translation;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Carbon\AbstractTranslator;
use Carbon\Carbon;
use Dbout\WpFoundation\Tests\TestCase;
use Dbout\WpFoundation\Translation\DateServiceProvider;

final class DateServiceProviderTest extends TestCase
{
    private const array TRANSLATIONS = ['weekdays' => ['Dimanche', 'Lunedi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']];

    protected function tearDown(): void
    {
        $translator = Carbon::getTranslator();
        if ($translator instanceof AbstractTranslator) {
            $translator->resetMessages();
        }

        Carbon::setLocale('en');
        parent::tearDown();
    }

    public function testSetsTheLocaleWhenItIsRegistered(): void
    {
        $this->createProvider(fn (): string => 'fr_FR')->register();

        self::assertSame('fr_FR', Carbon::getLocale());
    }

    public function testSetsTheLocaleAgainOnInit(): void
    {
        // The language of the request is only known once the plugins (WPML, Polylang) and the user are loaded
        $locale = 'fr_FR';
        $provider = $this->createProvider(function () use (&$locale): string {
            return $locale;
        });
        $provider->register();
        $callbacks = $this->captureHooks($provider);

        $locale = 'de_DE';
        $callbacks['init']();

        self::assertSame('de_DE', Carbon::getLocale());
    }

    public function testFollowsTheSwitchedLocale(): void
    {
        $provider = $this->createProvider('fr_FR');
        $provider->register();
        $callbacks = $this->captureHooks($provider);

        Functions\when('is_locale_switched')->justReturn(true);
        $callbacks['change_locale']('de_DE');
        self::assertSame('de_DE', Carbon::getLocale());

        // restore_previous_locale() gives the locale of WordPress, the config is set again
        Functions\when('is_locale_switched')->justReturn(false);
        $callbacks['change_locale']('en_US');
        self::assertSame('fr_FR', Carbon::getLocale());
    }

    public function testTheTranslationsOfTheConfigOnlyReplaceTheOnesOfTheConfiguredLocale(): void
    {
        $provider = $this->createProvider('fr', self::TRANSLATIONS);
        $provider->register();
        self::assertSame('Lunedi', Carbon::parse('2024-01-01')->isoFormat('dddd'));

        $provider->applyLocale('de');
        self::assertSame('Montag', Carbon::parse('2024-01-01')->isoFormat('dddd'));
    }

    public function testKeepsTheLocaleWithoutConfig(): void
    {
        $this->createProvider(null)->register();

        self::assertSame('en', Carbon::getLocale());
    }

    /**
     * @param string|\Closure(): string|null $locale
     * @param array<string, mixed> $translations
     */
    private function createProvider(string|\Closure|null $locale, array $translations = []): DateServiceProvider
    {
        return new DateServiceProvider($this->createApplication(['app' => ['locale' => $locale, 'date' => ['translations' => $translations]]]));
    }

    /**
     * @return array{init: \Closure(): void, change_locale: \Closure(string): void}
     */
    private function captureHooks(DateServiceProvider $provider): array
    {
        $callbacks = [];
        Actions\expectAdded('init')->once()->with(\Mockery::type(\Closure::class), 0)->whenHappen(function (\Closure $callback) use (&$callbacks): void {
            $callbacks['init'] = $callback;
        });
        Actions\expectAdded('change_locale')->once()->whenHappen(function (\Closure $callback) use (&$callbacks): void {
            $callbacks['change_locale'] = $callback;
        });

        $provider->boot();

        /** @var array{init: \Closure(): void, change_locale: \Closure(string): void} $callbacks */
        return $callbacks;
    }
}
