<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Http\Response;

use Dbout\WpFoundation\View\ViewRendererInterface;

/**
 * Renders a template (through the {@see ViewRendererInterface} bound in the container) as the response of a controller.
 *
 * Controllers are executed on `init`, before the WordPress main query. The template is therefore
 * not rendered immediately: it is rendered at the end of `template_redirect`, once the main query
 * is done and all the redirections (canonical, WooCommerce, ...) have been processed.
 *
 * Controller URLs do not match any WordPress content, so WordPress flags the main query as a 404:
 * the flag and the HTTP status are overridden, otherwise `redirect_canonical()` would try to
 * redirect the URL to a "guessed" post and the page would be sent with a 404 status.
 */
class ViewResponse implements ResponseInterface
{
    /**
     * @param string $template  The Twig template (i.e. "features/account/pages/create-account.tw.twig")
     * @param array<string, mixed> $context    Data passed to the template
     * @param int $status       The HTTP status code
     */
    public function __construct(
        protected string $template,
        protected array $context = [],
        protected int $status = 200,
    ) {}

    /**
     * @inheritDoc
     */
    public function send(): mixed
    {
        add_filter('status_header', function (string $header, int $code, string $description, string $protocol): string {
            if ($code !== 404) {
                return $header;
            }

            return sprintf('%s %d %s', $protocol, $this->status, get_status_header_desc($this->status));
        }, 10, 4);

        if ($this->status !== 404) {
            add_action('parse_query', function (\WP_Query $query): void {
                if ($query->is_main_query()) {
                    $query->is_404 = false;
                }
            }, 1);

            add_action('template_redirect', function (): void {
                global $wp_query;
                $wp_query->is_404 = false;
            }, 1);
        }

        add_action('template_redirect', [$this, 'render'], PHP_INT_MAX);

        return null;
    }

    /**
     * Renders the template, once the main query is done (see {@see send()}).
     */
    public function render(): void
    {
        status_header($this->status);
        echo app(ViewRendererInterface::class)->render($this->template, $this->context);
        exit;
    }
}
