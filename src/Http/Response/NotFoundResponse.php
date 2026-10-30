<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Http\Response;

/**
 * Flags the main query as a 404, WordPress then renders the 404 template like for any unknown URL.
 *
 * Controllers are executed on `init`, before the WordPress main query, so the flag is set on `wp`.
 * The guess redirection of `redirect_canonical()` is disabled, otherwise the URL could be redirected to a "guessed" post.
 */
class NotFoundResponse implements ResponseInterface
{
    /**
     * @inheritDoc
     */
    public function send(): mixed
    {
        add_filter('do_redirect_guess_404_permalink', '__return_false');
        add_action('wp', function (): void {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
        });

        return null;
    }
}
