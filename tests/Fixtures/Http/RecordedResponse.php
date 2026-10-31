<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Fixtures\Http;

use Dbout\WpFoundation\Http\Response\ResponseInterface;

final class RecordedResponse implements ResponseInterface
{
    /**
     * @var RecordedResponse[]
     */
    public static array $sent = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly string $action,
        public readonly array $data = [],
    ) {}

    public function send(): mixed
    {
        self::$sent[] = $this;

        return null;
    }
}
