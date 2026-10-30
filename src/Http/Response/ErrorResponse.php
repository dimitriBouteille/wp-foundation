<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Http\Response;

/**
 * Displays the WordPress error page with the given HTTP status (403, 500, ...).
 */
class ErrorResponse implements ResponseInterface
{
    /**
     * @param int $status       The HTTP status code
     * @param string $message   The message displayed, the status description if empty
     */
    public function __construct(
        protected int $status,
        protected string $message = '',
    ) {}

    /**
     * @inheritDoc
     */
    public function send(): never
    {
        $message = $this->message !== '' ? $this->message : get_status_header_desc($this->status);
        wp_die(esc_html($message), '', ['response' => $this->status]);
    }
}
