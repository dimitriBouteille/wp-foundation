<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Http\Response;

class RedirectResponse implements ResponseInterface
{
    protected string $url;

    /**
     * @param string $url   The URL to redirect to, an external URL is replaced by the home URL (see the `allowed_redirect_hosts` filter)
     * @param int $status   The HTTP status code (302 "Found" by default)
     */
    public function __construct(
        string $url,
        protected int $status = 302,
    ) {
        $this->setUrl($url);
    }

    /**
     * @return $this
     */
    public function setUrl(string $url): self
    {
        if ('' === $url) {
            throw new \InvalidArgumentException('Cannot redirect to an empty URL.');
        }

        $this->url = $url;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Same as `wp_safe_redirect()`, but with the home URL as fallback instead of the admin URL,
     * so that an unsafe URL does not reveal the admin location.
     *
     * @inheritDoc
     */
    public function send(): never
    {
        wp_redirect(wp_validate_redirect(wp_sanitize_redirect($this->url), home_url()), $this->status);
        die;
    }
}
