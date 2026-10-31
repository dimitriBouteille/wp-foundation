<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\View\Twig\Extensions;

use Dbout\WpFoundation\Support\Site;
use Dbout\WpFoundation\Support\Theme;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig extension that registers WordPress-related functions for use in templates.
 */
final class WordPressExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * The values returned by the functions are escaped like any value: `function()` can return a value of the visitor
     * (`get_search_query`, a post meta, ...). Add `|raw` to print the HTML of a trusted function
     * (`{{ function('get_the_post_thumbnail')|raw }}`).
     *
     * @inheritDoc
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('theme_url', [Theme::class, 'url']),
            new TwigFunction('admin_url', 'admin_url'),
            new TwigFunction('wp_head', 'wp_head', ['is_safe' => ['html']]),
            new TwigFunction('wp_footer', 'wp_footer', ['is_safe' => ['html']]),
            new TwigFunction('action', [$this, 'doAction']),
            new TwigFunction('function', [$this, 'callFunction']),
            new TwigFunction('page_title', [$this, 'setPageTitle']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getGlobals(): array
    {
        return [
            'site' => new Site(),
        ];
    }

    /**
     * `wpautop` escapes its input (like the `nl2br` filter of Twig), then adds the paragraphs: `{{ text|wpautop }}`
     * is safe with a value of the visitor. Add `|raw` before it for a trusted HTML content (`{{ post.content|raw|wpautop }}`).
     *
     * @inheritDoc
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('wpautop', 'wpautop', ['pre_escape' => 'html', 'is_safe' => ['html']]),
        ];
    }

    public function doAction(string $hookName, mixed ...$args): void
    {
        if ($hookName === '') {
            throw new \InvalidArgumentException('The action name cannot be empty.');
        }

        do_action($hookName, ...$args);
    }

    /**
     * Replaces the title of the document, must be called outside the blocks of the page template, so before `wp_head()`.
     */
    public function setPageTitle(string $title): void
    {
        add_filter('document_title_parts', function (array $parts) use ($title): array {
            $parts['title'] = $title;

            return $parts;
        });
    }

    /**
     * @param string|array{0: class-string|object, 1: string} $callable
     */
    public function callFunction(string|array $callable, mixed ...$args): mixed
    {
        if (is_string($callable)) {
            $callable = trim($callable);
        }

        if (!is_callable($callable, false, $name)) {
            throw new \InvalidArgumentException(sprintf('The function "%s" is not callable.', $name));
        }

        return $callable(...$args);
    }
}
