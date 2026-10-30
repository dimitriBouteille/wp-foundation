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
     * @inheritDoc
     */
    public function send(): never
    {
        wp_safe_redirect($this->url, $this->status, home_url());
        die;
    }
}
