<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Same as the {@see \Symfony\Component\Validator\Constraints\NotBlankValidator}, with the `{{ label }}` parameter.
 */
final class RequiredValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Required) {
            throw new UnexpectedTypeException($constraint, Required::class);
        }

        if ($constraint->allowNull && $value === null) {
            return;
        }

        if (is_string($value) && $constraint->normalizer !== null) {
            $value = ($constraint->normalizer)($value);
        }

        if ($value === false || (empty($value) && $value != '0')) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setParameter('{{ label }}', (string) $constraint->label)
                ->setCode(NotBlank::IS_BLANK_ERROR)
                ->addViolation();
        }
    }
}
