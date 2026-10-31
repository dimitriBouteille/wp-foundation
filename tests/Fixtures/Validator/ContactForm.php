<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Fixtures\Validator;

use Dbout\WpFoundation\Validator\Constraints\Date;
use Dbout\WpFoundation\Validator\Constraints\Email;
use Dbout\WpFoundation\Validator\Constraints\NotHtml;
use Dbout\WpFoundation\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\IsTrue;

final class ContactForm
{
    #[Required(label: 'Name')]
    public ?string $name = null;

    #[Email]
    public ?string $email = null;

    #[NotHtml]
    public ?string $message = null;

    #[Date]
    public ?string $birthday = null;

    public bool $accepted = true;

    #[IsTrue(message: 'The terms must be accepted.')]
    public function isAccepted(): bool
    {
        return $this->accepted;
    }
}
