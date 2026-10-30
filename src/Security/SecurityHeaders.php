<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Security;

/**
 * Sends the configured HTTP headers on the front-end responses (`security.headers`).
 */
final readonly class SecurityHeaders
{
    /**
     * @param array<string, string|null> $headers Header values indexed by name, null to not send a header
     */
    public function __construct(
        private array $headers,
    ) {}

    public function register(): void
    {
        add_action('send_headers', [$this, 'send']);
    }

    public function send(): void
    {
        if (is_admin()) {
            return;
        }

        foreach ($this->headers as $name => $value) {
            if ($value !== null) {
                header(sprintf('%s: %s', $name, $value));
            }
        }
    }
}
