<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Http\Response;

class Response implements ResponseInterface
{
    public function __construct(
        protected string $content,
        protected int $status = 200,
    ) {}

    /**
     * @inheritDoc
     */
    public function send(): never
    {
        status_header($this->status);
        echo $this->content;
        die;
    }
}
