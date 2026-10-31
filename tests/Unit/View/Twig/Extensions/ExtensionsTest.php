<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\View\Twig\Extensions;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Dbout\WpFoundation\Config\View\Twig\Extensions\ConfigExtension;
use Dbout\WpFoundation\Routing\View\Twig\Extensions\RouterExtension;
use Dbout\WpFoundation\Tests\TestCase;
use Dbout\WpFoundation\View\AssetResolver;
use Dbout\WpFoundation\View\Resolver\ViteJsResolver;
use Dbout\WpFoundation\View\Twig\Extensions\AssetExtension;
use Dbout\WpFoundation\View\Twig\Extensions\WordPressExtension;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\ArrayLoader;

final class ExtensionsTest extends TestCase
{
    public function testTheAssetFunctions(): void
    {
        Functions\when('home_url')->justReturn('https://example.com');
        $app = $this->createApplication();
        $app->instance('asset', new AssetResolver(['theme' => new ViteJsResolver('/static', self::fixturePath('vite'))]));

        self::assertSame(
            'https://example.com/static/assets/index-abc123.js|<svg xmlns="http://www.w3.org/2000/svg"></svg>',
            trim($this->render(new AssetExtension(), '{{ asset_url("theme::main.js") }}|{{ asset_content("theme::images/logo.svg")|raw }}')),
        );
    }

    public function testTheConfigFunctions(): void
    {
        $this->createApplication(['app' => ['env' => fn (): string => 'staging']]);

        self::assertSame('staging|yes|no', $this->render(new ConfigExtension(), '{{ config("app.env") }}|{{ config_exist("app.env") ? "yes" : "no" }}|{{ config_exist("app.missing") ? "yes" : "no" }}'));
    }

    public function testThePageUrlFunction(): void
    {
        $app = $this->createApplication();
        $router = \Mockery::mock();
        $router->shouldReceive('generate')->once()->with('blog_show', ['id' => 42])->andReturn('/blog/42');
        $app->instance('router', $router);

        self::assertSame('/blog/42', $this->render(new RouterExtension(), '{{ page_url("blog_show", {id: 42}) }}'));
    }

    public function testTheActionFunctionRunsTheAction(): void
    {
        Actions\expectDone('my_theme_header')->once()->with('home', 2);

        $this->render(new WordPressExtension(), '{{ action("my_theme_header", "home", 2) }}');
    }

    public function testTheActionNameCannotBeEmpty(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('The action name cannot be empty.');

        $this->render(new WordPressExtension(), '{{ action("") }}');
    }

    public function testTheFunctionFunctionCallsTheCallable(): void
    {
        self::assertSame('ABC|abab', $this->render(new WordPressExtension(), '{{ function(" strtoupper ", "abc") }}|{{ function("str_repeat", "ab", 2) }}'));
    }

    public function testTheFunctionFunctionRequiresACallable(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('The function "unknown_function" is not callable.');

        $this->render(new WordPressExtension(), '{{ function("unknown_function") }}');
    }

    public function testThePageTitleFunctionReplacesTheDocumentTitle(): void
    {
        $filter = null;
        Filters\expectAdded('document_title_parts')->once()->whenHappen(function (callable $callback) use (&$filter): void {
            $filter = $callback;
        });

        $this->render(new WordPressExtension(), '{{ page_title("My account") }}');

        self::assertIsCallable($filter);
        self::assertSame(['title' => 'My account', 'site' => 'Example'], $filter(['title' => 'Home', 'site' => 'Example']));
    }

    public function testTheWordPressFunctionsAndGlobals(): void
    {
        Functions\when('get_template_directory_uri')->justReturn('https://example.com/app/themes/theme/');
        Functions\when('admin_url')->alias(fn (string $path = ''): string => 'https://example.com/wp-admin/' . $path);
        Functions\when('wpautop')->alias(fn (string $text): string => '<p>' . $text . '</p>');
        Functions\when('home_url')->justReturn('https://example.com');
        Functions\when('get_bloginfo')->alias(fn (string $show): string => ['name' => 'Example &amp; Co', 'description' => 'Tagline', 'language' => 'fr-FR', 'charset' => 'UTF-8'][$show]);
        Functions\when('wp_specialchars_decode')->alias(fn (string $text): string => html_entity_decode($text, ENT_QUOTES));

        self::assertSame(
            'https://example.com/app/themes/theme/images/logo.svg|https://example.com/wp-admin/edit.php|<p>Text</p>|Example & Co|https://example.com|Tagline|fr-FR|UTF-8',
            $this->render(new WordPressExtension(), '{{ theme_url("/images/logo.svg") }}|{{ admin_url("edit.php") }}|{{ "Text"|wpautop }}|{{ site.name|raw }}|{{ site.url }}|{{ site.description }}|{{ site.language }}|{{ site.charset }}'),
        );
    }

    private function render(ExtensionInterface $extension, string $template): string
    {
        $twig = new Environment(new ArrayLoader(['template' => $template]), ['strict_variables' => true]);
        $twig->addExtension($extension);

        return $twig->render('template');
    }
}
