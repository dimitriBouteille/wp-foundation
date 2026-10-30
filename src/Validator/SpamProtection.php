<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Validator;

/**
 * Lightweight server-side spam detection for form submissions.
 *
 * Combines two techniques to filter out bots without external dependencies:
 *  1. **Honeypot** — a hidden `website_url` field that humans never fill but bots do.
 *  2. **Timestamp** — rejects submissions faster than {@see MIN_SUBMIT_DELAY} seconds
 *     or older than {@see MAX_SUBMIT_DELAY} seconds after form load.
 *
 * Usage:
 *   if (SpamProtection::isSpam($request->all())) {
 *       // Return a fake success response to avoid alerting the bot.
 *   }
 *
 * Required form fields:
 *  - `website_url` — hidden honeypot input (must remain empty).
 *  - `_ts`         — hidden input containing the form load timestamp (Unix).
 */
final class SpamProtection
{
    /**
     * Minimum time in seconds between form load and submission.
     */
    private const int MIN_SUBMIT_DELAY = 3;

    /**
     * Maximum time in seconds between form load and submission (24h).
     */
    private const int MAX_SUBMIT_DELAY = 86400;

    /**
     * @param array<string, mixed> $data
     */
    public static function isSpam(array $data): bool
    {
        // 1. Honeypot — must be empty
        if (!empty($data['website_url'] ?? null)) {
            return true;
        }

        // 2. Timestamp — too fast = bot
        $timestamp = $data['_ts'] ?? 0;
        $timestamp = is_numeric($timestamp) ? (int) $timestamp : 0;
        $elapsed = time() - $timestamp;

        return (bool) ($elapsed < self::MIN_SUBMIT_DELAY || $elapsed > self::MAX_SUBMIT_DELAY)
        ;
    }
}
