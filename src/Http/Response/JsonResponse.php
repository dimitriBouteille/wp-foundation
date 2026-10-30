<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Http\Response;

class JsonResponse implements ResponseInterface
{
    // Encode <, >, ', &, and " characters in the JSON, making it also safe to be embedded into HTML.
    // 15 === JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    final public const int DEFAULT_ENCODING_OPTIONS = 15;

    /**
     * @param array<mixed> $data
     */
    public function __construct(
        protected array $data,
        protected int $status = 200,
        protected int $encodingOptions = self::DEFAULT_ENCODING_OPTIONS,
    ) {}

    /**
     * @inheritDoc
     */
    public function send(): never
    {
        wp_send_json($this->data, $this->status, $this->encodingOptions);
        die;
    }
}
