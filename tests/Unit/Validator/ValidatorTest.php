<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Validator;

use Brain\Monkey\Functions;
use Dbout\WpFoundation\Tests\Fixtures\Validator\ContactForm;
use Dbout\WpFoundation\Tests\Fixtures\Validator\InvalidGetterForm;
use Dbout\WpFoundation\Tests\TestCase;
use Dbout\WpFoundation\Validator\Constraints\Date;
use Dbout\WpFoundation\Validator\Constraints\Email;
use Dbout\WpFoundation\Validator\Constraints\NotHtml;
use Dbout\WpFoundation\Validator\Constraints\Required;
use Dbout\WpFoundation\Validator\ConstraintViolationList;
use Dbout\WpFoundation\Validator\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MappingException;

final class ValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('translate')->returnArg();
    }

    /**
     * @return iterable<string, array{mixed, Constraint, string|null}>
     */
    public static function constraintProvider(): iterable
    {
        yield 'required: empty string' => ['', new Required(), 'The field is required.'];
        yield 'required: null' => [null, new Required(), 'The field is required.'];
        yield 'required: empty array' => [[], new Required(), 'The field is required.'];
        yield 'required: false' => [false, new Required(), 'The field is required.'];
        yield 'required: zero string' => ['0', new Required(), null];
        yield 'required: with label' => ['', new Required('Email'), 'The field Email is required.'];
        yield 'required: custom message' => ['', new Required(message: 'Custom.'), 'Custom.'];
        yield 'required: null allowed' => [null, new Required(allowNull: true), null];
        yield 'required: normalized' => ['  ', new Required(normalizer: 'trim'), 'The field is required.'];
        yield 'email: valid' => ['john@example.com', new Email(), null];
        yield 'email: empty' => ['', new Email(), null];
        yield 'email: invalid' => ['john.example.com', new Email(), 'The email address is not valid.'];
        yield 'email: one letter tld' => ['john@example.c', new Email(), 'The email address is not valid.'];
        yield 'email: custom message' => ['john', new Email(['message' => 'Invalid.']), 'Invalid.'];
        yield 'not html: text' => ['1 < 2 and 3 > 2', new NotHtml(), null];
        yield 'not html: html' => ['<a href="https://example.com">link</a>', new NotHtml(), 'HTML code is not allowed.'];
        yield 'not html: not a string' => [12, new NotHtml(), null];
        yield 'date: valid' => ['31-12-2024', new Date(), null];
        yield 'date: other format' => ['2024-12-31', new Date(), 'The date does not have the right format.'];
        yield 'date: invalid' => ['31-02-2024', new Date(), 'The date does not have the right format.'];
    }

    #[DataProvider('constraintProvider')]
    public function testTheConstraints(mixed $value, Constraint $constraint, ?string $message): void
    {
        $violations = (new Validator())->validate($value, constraints: $constraint);

        self::assertSame($message, $violations->getFirstError()?->getMessage());
    }

    public function testValidatesTheAttributesOfTheObject(): void
    {
        $form = new ContactForm();
        $form->email = 'john';
        $form->message = '<b>Hello</b>';
        $form->birthday = '1990-01-01';
        $form->accepted = false;

        $violations = (new Validator())->validate($form);

        self::assertSame([
            'name' => 'The field Name is required.',
            'email' => 'The email address is not valid.',
            'message' => 'HTML code is not allowed.',
            'birthday' => 'The date does not have the right format.',
            'accepted' => 'The terms must be accepted.',
        ], $this->messages($violations));
    }

    public function testHydratesTheObjectFromAnArray(): void
    {
        $form = new ContactForm();

        $violations = (new Validator())->validate($form, [
            'name' => 'John',
            'email' => 'john@example.com',
            'unknown' => 'ignored',
        ]);

        self::assertFalse($violations->hasErrors());
        self::assertSame('John', $form->name);
        self::assertSame('john@example.com', $form->email);
    }

    public function testHydratesTheObjectFromTheQueryOfTheRequest(): void
    {
        $form = new ContactForm();

        (new Validator())->validate($form, Request::create('/contact?name=John'));

        self::assertSame('John', $form->name);
    }

    public function testIgnoresTheFieldsThatCannotBeHydrated(): void
    {
        $form = new ContactForm();

        (new Validator())->validate($form, Request::create('/contact?0=x&name[]=x&email=john@example.com'));
        (new Validator())->validate($form, ['John', 'Doe']);

        self::assertSame('john@example.com', $form->email);
    }

    public function testTheSourceMustBeIterable(): void
    {
        $this->expectExceptionMessage('The source parameter must be iterable');

        (new Validator())->validate(new ContactForm(), 'name=John');
    }

    public function testTheHydratedValueMustBeAnObjectOrAnArray(): void
    {
        $this->expectExceptionMessage('The value must be an object or an array to be hydrated');

        (new Validator())->validate('John', ['name' => 'John']);
    }

    public function testTheConstraintsOfTheMethodsRequireAGetter(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Constraints can only be added on methods beginning with "get", "is" or "has".');

        (new Validator())->validate(new InvalidGetterForm());
    }

    public function testTheViolationListHelpers(): void
    {
        $empty = (new Validator())->validate('John', constraints: new Required());

        self::assertInstanceOf(ConstraintViolationList::class, $empty);
        self::assertFalse($empty->hasErrors());
        self::assertNull($empty->getFirstError());
    }

    /**
     * @return array<string, string>
     */
    private function messages(ConstraintViolationList $violations): array
    {
        $messages = [];
        foreach ($violations as $violation) {
            $messages[$violation->getPropertyPath()] = (string) $violation->getMessage();
        }

        return $messages;
    }
}
