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
     * @inheritDoc
     */
    public function getFunctions(): array
    {
        $config = ['is_safe' => ['html']];

        return [
            new TwigFunction('theme_url', [Theme::class, 'url'], $config),
            new TwigFunction('admin_url', 'admin_url', $config),
            new TwigFunction('wp_head', 'wp_head', $config),
            new TwigFunction('wp_footer', 'wp_footer', $config),
            new TwigFunction('action', [$this, 'doAction']),
            new TwigFunction('function', [$this, 'callFunction'], $config),
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
     * @inheritDoc
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('wpautop', 'wpautop', ['is_safe' => ['html']]),
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
