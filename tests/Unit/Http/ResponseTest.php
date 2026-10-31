<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Http;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Dbout\WpFoundation\Http\Exception\AccessDeniedHttpException;
use Dbout\WpFoundation\Http\Exception\HttpException;
use Dbout\WpFoundation\Http\Exception\NotFoundHttpException;
use Dbout\WpFoundation\Http\Response\ErrorResponse;
use Dbout\WpFoundation\Http\Response\NotFoundResponse;
use Dbout\WpFoundation\Http\Response\RedirectResponse;
use Dbout\WpFoundation\Http\Response\ViewResponse;
use Dbout\WpFoundation\Tests\TestCase;

final class ResponseTest extends TestCase
{
    public function testTheHttpExceptionsHaveTheirStatusCode(): void
    {
        $previous = new \RuntimeException();

        self::assertSame(404, (new NotFoundHttpException())->getStatusCode());
        self::assertSame(403, (new AccessDeniedHttpException('Denied', $previous))->getStatusCode());
        self::assertSame($previous, (new AccessDeniedHttpException('Denied', $previous))->getPrevious());
        self::assertSame(429, (new HttpException(429, 'Too many requests'))->getStatusCode());
    }

    public function testTheNotFoundResponseFlagsTheMainQueryAsA404(): void
    {
        $onWp = null;
        Actions\expectAdded('wp')->once()->whenHappen(function (callable $callback) use (&$onWp): void {
            $onWp = $callback;
        });

        (new NotFoundResponse())->send();

        self::assertSame(10, has_filter('do_redirect_guess_404_permalink', '__return_false'));
        self::assertIsCallable($onWp);

        $query = new class () {
            public bool $is404 = false;

            public function set_404(): void
            {
                $this->is404 = true;
            }
        };
        $GLOBALS['wp_query'] = $query;
        Functions\expect('status_header')->once()->with(404);
        Functions\expect('nocache_headers')->once();

        $onWp();

        self::assertTrue($query->is404);
        unset($GLOBALS['wp_query']);
    }

    public function testTheErrorResponseDisplaysTheStatusDescriptionByDefault(): void
    {
        Functions\when('esc_html')->returnArg();
        Functions\when('get_status_header_desc')->justReturn('Forbidden');
        Functions\expect('wp_die')
            ->once()
            ->with('Forbidden', '', ['response' => 403])
            ->andThrow(new \RuntimeException('wp_die'));

        $this->expectExceptionMessage('wp_die');

        (new ErrorResponse(403))->send();
    }

    public function testTheRedirectResponseRequiresAnUrl(): void
    {
        self::assertSame('/account', (new RedirectResponse('/account'))->getUrl());

        $this->expectException(\InvalidArgumentException::class);

        new RedirectResponse('');
    }

    public function testTheRedirectResponseRedirectsAnUnsafeUrlToTheHomeUrl(): void
    {
        Functions\when('home_url')->justReturn('https://example.com');
        Functions\when('wp_sanitize_redirect')->returnArg();
        Functions\expect('wp_validate_redirect')
            ->once()
            ->with('https://evil.example.org', 'https://example.com')
            ->andReturn('https://example.com');
        Functions\expect('wp_redirect')
            ->once()
            ->with('https://example.com', 301)
            ->andThrow(new \RuntimeException('wp_redirect'));

        $this->expectExceptionMessage('wp_redirect');

        (new RedirectResponse('https://evil.example.org', 301))->send();
    }

    public function testTheViewResponseReplacesThe404StatusOfWordPress(): void
    {
        Functions\when('get_status_header_desc')->justReturn('Created');

        $statusHeader = null;
        Filters\expectAdded('status_header')->once()->whenHappen(function (callable $callback) use (&$statusHeader): void {
            $statusHeader = $callback;
        });

        $response = new ViewResponse('@account/register.twig', status: 201);
        $response->send();

        self::assertIsCallable($statusHeader);
        self::assertSame('HTTP/1.1 201 Created', $statusHeader('HTTP/1.1 404 Not Found', 404, 'Not Found', 'HTTP/1.1'));
        self::assertSame('HTTP/1.1 302 Found', $statusHeader('HTTP/1.1 302 Found', 302, 'Found', 'HTTP/1.1'));
        self::assertSame(PHP_INT_MAX, has_action('template_redirect', [$response, 'render']));
        self::assertSame(1, has_action('parse_query', 'function (\WP_Query $query)'));
    }

    public function testTheViewResponseKeepsThe404Flag(): void
    {
        Functions\when('get_status_header_desc')->justReturn('Not Found');

        (new ViewResponse('404.twig', status: 404))->send();

        self::assertFalse(has_action('parse_query'));
        self::assertSame(10, has_filter('do_redirect_guess_404_permalink', '__return_false'));
    }
}
