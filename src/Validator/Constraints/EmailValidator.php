<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\EmailValidator as SymfonyEmailValidator;

final class EmailValidator extends SymfonyEmailValidator
{
    private const int MIN_TLD_LENGTH = 2;

    /**
     * @param Email $constraint
     * @inheritDoc
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        $violations = $this->context->getViolations()->count();
        parent::validate($value, $constraint);

        if ($this->context->getViolations()->count() > $violations) {
            return;
        }

        if (!is_scalar($value) && !$value instanceof \Stringable) {
            return;
        }

        $value = (string) $value;
        if ($value === '') {
            return;
        }

        $host = substr($value, strrpos($value, '@') + 1);
        $tld = substr($host, strrpos($host, '.') + 1);

        if (strlen($tld) < self::MIN_TLD_LENGTH) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
