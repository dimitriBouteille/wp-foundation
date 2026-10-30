<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Validator;

use Symfony\Component\Validator\ConstraintViolationInterface;

class ConstraintViolationList extends \Symfony\Component\Validator\ConstraintViolationList
{
    public function getFirstError(): ?ConstraintViolationInterface
    {
        return $this->has(0) ? $this->get(0) : null;
    }

    public function hasErrors(): bool
    {
        return $this->count() > 0;
    }
}
