<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Validator\Constraints;

use Symfony\Component\Validator\{Constraint, ConstraintValidator};

class NotHtmlValidator extends ConstraintValidator
{
    /**
     * @param NotHtml $constraint
     * @inheritDoc
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!is_string($value) && !$value instanceof \Stringable) {
            return;
        }

        $value = (string) $value;
        if ($value !== strip_tags($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
