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
use Dbout\WpFoundation\Module\Exception\ModuleException;
use Dbout\WpFoundation\Module\ModuleList;
use Dbout\WpFoundation\Routing\Router;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Blog\BlogModule;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Shop\ShopModule;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Shop\ShopServiceProvider;
use Dbout\WpFoundation\Tests\Fixtures\Modules\UserAccount\UserAccountModule;
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

    public function testEnablesTheModulesOfTheBootstrapFileAndTheGivenOnes(): void
    {
        $app = Application::configure($this->createTemporaryDirectory())
            ->withPaths(bootstrap: self::fixturePath('bootstrap-modules'))
            ->withModules([UserAccountModule::class])
            ->create();

        $modules = $app->getModules();
        self::assertSame([UserAccountModule::class, ShopModule::class, BlogModule::class], array_keys($modules->all()));
        self::assertSame($modules, $app->get('modules'));

        $provider = $app->getProvider(ShopServiceProvider::class);
        self::assertInstanceOf(ShopServiceProvider::class, $provider);
        self::assertSame(['register', 'boot'], $provider->calls);

        self::assertSame(['Dbout\\WpFoundation\\Tests\\Fixtures\\Modules\\Shop\\Console\\'], array_slice((array) $app->getConfig()->get('console.namespaces'), -1));
        $router = $app->get('router');
        self::assertInstanceOf(Router::class, $router);
        self::assertNotNull($router->getRouteCollection()->get('blog_show'));

        $twig = $app->get('twig');
        self::assertInstanceOf(Environment::class, $twig);
        self::assertTrue($twig->getLoader()->exists('@blog/post.twig'));
        self::assertTrue($twig->getLoader()->exists('@userAccount/register.twig'));
    }

    public function testWithoutModulesTheListIsEmpty(): void
    {
        $app = Application::configure($this->createTemporaryDirectory())->create();

        self::assertCount(0, $app->getModules());
        self::assertInstanceOf(ModuleList::class, $app->get('modules'));
    }

    public function testThrowsWhenADependencyOfAModuleIsNotEnabled(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage(UserAccountModule::class);

        Application::configure($this->createTemporaryDirectory())
            ->withPaths(bootstrap: self::fixturePath('bootstrap-modules'))
            ->create();
    }

    public function testThrowsWhenAServiceProviderDoesNotExist(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('The service provider App\\UnknownProvider does not exist.');

        $bootstrap = $this->createTemporaryDirectory();
        file_put_contents($bootstrap . '/providers.php', '<?php return ["App\\\\UnknownProvider"];');

        Application::configure($this->createTemporaryDirectory())->withPaths(bootstrap: $bootstrap)->create();
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
