<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Security;

/**
 * Sends the configured HTTP headers on the responses outside the administration (`security.headers`).
 *
 * The headers are sent on `init` (priority 1), before the router (priority 10): the responses of the controllers
 * sent on `init` (`Response`, `JsonResponse`, `ErrorResponse`, ...) stop the script before `send_headers`.
 */
final readonly class SecurityHeaders
{
    private \Closure $sendHeader;

    /**
     * @param array<string, string|null> $headers Header values indexed by name, null to not send a header
     * @param (\Closure(string): void)|null $sendHeader Sends a header line, `header()` by default
     */
    public function __construct(
        private array $headers,
        ?\Closure $sendHeader = null,
    ) {
        $this->sendHeader = $sendHeader ?? static function (string $header): void {
            if (!headers_sent()) {
                header($header);
            }
        };
    }

    public function register(): void
    {
        add_action('init', [$this, 'send'], 1);
    }

    public function send(): void
    {
        if (is_admin()) {
            return;
        }

        foreach ($this->headers as $name => $value) {
            if ($value !== null) {
                ($this->sendHeader)(sprintf('%s: %s', $name, $value));
            }
        }
    }
}
