<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Configuration;

use Brain\Monkey\Functions;
use Carbon\AbstractTranslator;
use Carbon\Carbon;
use Dbout\WpFoundation\Application;
use Dbout\WpFoundation\Cache\Adapter\TagAwareAdapter;
use Dbout\WpFoundation\Facades\Log;
use Dbout\WpFoundation\Filesystem\Directory;
use Dbout\WpFoundation\Routing\Router;
use Dbout\WpFoundation\Tests\Fixtures\Providers\RecordingProvider;
use Dbout\WpFoundation\Tests\TestCase;
use Dbout\WpFoundation\View\AssetResolver;
use Dbout\WpFoundation\View\ViewRendererInterface;
use Twig\Environment;

final class ApplicationBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Functions\stubs([
            'wp_get_environment_type' => 'production',
            'determine_locale' => 'fr_FR',
            'home_url' => 'https://example.com',
            'get_home_url' => 'https://example.com',
        ]);
        Functions\when('wp_parse_url')->alias('parse_url');
    }

    protected function tearDown(): void
    {
        $translator = Carbon::getTranslator();
        if ($translator instanceof AbstractTranslator) {
            $translator->resetMessages();
        }

        Carbon::setLocale('en');
        parent::tearDown();
    }

    public function testCreatesTheApplicationWithTheDefaultServices(): void
    {
        $basePath = $this->createTemporaryDirectory();
        $app = Application::configure($basePath)->create();

        self::assertTrue($app->isProduction());
        self::assertInstanceOf(TagAwareAdapter::class, $app->getCache());
        self::assertInstanceOf(Router::class, $app->get('router'));
        self::assertInstanceOf(AssetResolver::class, $app->get('asset'));
        self::assertInstanceOf(Environment::class, $app->get('twig'));
        self::assertInstanceOf(ViewRendererInterface::class, $app->get(ViewRendererInterface::class));
        self::assertSame($basePath . '/var/cache/data', $app->getDir()->getPath(Directory::Cache, 'data'));
        self::assertSame('fr_FR', Carbon::getLocale());
        self::assertNotFalse(has_action('init', [$app->get('router'), 'matchCurrentRoute']));
    }

    public function testRegistersTheProjectProvidersAndPaths(): void
    {
        $cachePath = $this->createTemporaryDirectory();
        $app = Application::configure($this->createTemporaryDirectory())
            ->withPaths(config: self::fixturePath('config'), bootstrap: self::fixturePath('bootstrap'))
            ->withDirectory(Directory::Cache, $cachePath)
            ->create();

        $provider = $app->getProvider(RecordingProvider::class);
        self::assertInstanceOf(RecordingProvider::class, $provider);
        self::assertSame(['register', 'boot'], $provider->calls);
        self::assertSame('value', $app->getConfig()->get('custom.key'));
        $router = $app->get('router');
        self::assertInstanceOf(Router::class, $router);
        self::assertNotNull($router->getRouteCollection()->get('blog_show'));
        self::assertSame($cachePath . '/twig', $app->getDir()->getPath(Directory::Cache, 'twig'));
    }

    public function testRegistersTheGivenProviders(): void
    {
        $app = Application::configure($this->createTemporaryDirectory())
            ->withProviders([RecordingProvider::class])
            ->create();

        self::assertInstanceOf(RecordingProvider::class, $app->getProvider(RecordingProvider::class));
    }

    public function testWritesTheLogs(): void
    {
        $logs = $this->createTemporaryDirectory();
        Application::configure($this->createTemporaryDirectory())->withDirectory(Directory::Log, $logs)->create();

        Log::info('Order synchronized.', ['order' => 12]);

        $files = glob($logs . '/app-*.log') ?: [];
        self::assertCount(1, $files);
        self::assertStringContainsString('app.INFO: Order synchronized. {"order":12}', (string) file_get_contents($files[0]));
    }

    public function testTheStoragePathIsTheVarDirectoryWithoutTrailingSlash(): void
    {
        $logs = $this->createTemporaryDirectory();
        $app = Application::configure($this->createTemporaryDirectory())->withDirectory(Directory::Log, $logs)->create();

        self::assertSame($app->getBasePath() . '/var', $app->storagePath());
        self::assertSame($app->getBasePath() . '/var/framework/cache', $app->storagePath('framework/cache'));
        self::assertSame($logs, $app->storagePath('logs'));
        self::assertSame($logs . '/laravel.log', $app->storagePath('logs/laravel.log'));
        self::assertSame($logs . '/laravel.log', $app->storagePath('/logs/laravel.log'));
        self::assertSame($logs . '/laravel.log', $app->getConfig()->get('logging.channels.emergency.path'));
    }

    public function testAppliesTheDateTranslations(): void
    {
        $directory = $this->createTemporaryDirectory();
        file_put_contents($directory . '/app.php', '<?php return ["locale" => "en", "date" => ["translations" => ["weekdays" => ["Sunday", "Moonday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"]]]];');

        Application::configure($this->createTemporaryDirectory())->withPaths(config: $directory)->create();

        self::assertSame('Moonday', Carbon::parse('2024-01-01')->isoFormat('dddd'));
    }
}
