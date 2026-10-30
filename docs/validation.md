# Validation

The validation uses [Symfony Validator](https://symfony.com/doc/current/validation.html) with the constraints declared as PHP attributes.

## Validate a request

Declare the fields and their constraints in a class:

```php
namespace App\Input;

use Dbout\WpFoundation\Validator\Constraints\Email;
use Dbout\WpFoundation\Validator\Constraints\NotHtml;
use Dbout\WpFoundation\Validator\Constraints\Required;

final class ContactInput
{
    #[Required(label: 'email')]
    #[Email]
    public ?string $email = null;

    #[Required(label: 'message')]
    #[NotHtml]
    public ?string $message = null;
}
```

Then hydrate and validate it:

```php
use Dbout\WpFoundation\Validator\Validator;

$input = new ContactInput();
$errors = (new Validator())->validate($input, $request);

if ($errors->hasErrors()) {
    $error = $errors->getFirstError();
    // $error->getPropertyPath(), $error->getMessage()
}
```

The second argument is the source of the values:

| Source | Values |
|---|---|
| `WP_REST_Request` | All the parameters (`get_params()`) |
| `Symfony\Component\HttpFoundation\Request` | The query string |
| Array or iterable | Its values |
| `null` | No hydration, the value is only validated |

The values are set with the [PropertyAccess component](https://symfony.com/doc/current/components/property_access.html) (public properties or setters), the unknown fields are ignored.

### Validate a value

```php
$errors = (new Validator())->validate($email, constraints: [new Required(), new Email()]);
```

## Constraints

The constraints of the package are in the `Dbout\WpFoundation\Validator\Constraints` namespace:

| Constraint | Description |
|---|---|
| `Required(label: 'email')` | Not blank, the label is added to the message ("The field email is required.") |
| `Email` | Valid email address (Symfony `Email` constraint), with a top-level domain of 2 characters or more |
| `NotHtml` | No HTML tag |
| `Date` | Date with the `d-m-Y` format |

All the [Symfony constraints](https://symfony.com/doc/current/reference/constraints.html) can also be used. Every constraint accepts a `message` option to replace its message.

## Translations

The messages are translated with the WordPress translations, in the language of the site (`determine_locale()`). The package provides the translations of its constraints in the `wp-foundation` text domain:

| Language | File |
|---|---|
| French | `languages/wp-foundation-fr_FR.mo` |

The messages given with the `message` option are translated too: a message without translation is displayed as is.

To replace the translations of the package (or add a language), load your own `.mo` file in the `wp-foundation` domain:

```php
add_filter('load_textdomain_mofile', function (string $mofile, string $domain): string {
    if ($domain !== 'wp-foundation') {
        return $mofile;
    }

    return dirname(__DIR__) . '/languages/wp-foundation-' . determine_locale() . '.mo';
}, 10, 2);
```

The messages of the Symfony constraints are not translated, use the `message` option to translate them.

## Spam protection

`SpamProtection` detects the bots on a form without external service, with two hidden fields:

- `website_url`: honeypot, must stay empty,
- `_ts`: Unix timestamp of the form loading, the form must be submitted between 3 seconds and 24 hours later.

```twig
<input type="text" name="website_url" value="" tabindex="-1" autocomplete="off" hidden>
<input type="hidden" name="_ts" value="{{ 'now'|date('U') }}">
```

```php
use Dbout\WpFoundation\Validator\SpamProtection;

if (SpamProtection::isSpam($request->get_params())) {
    // Return a fake success, so that the bot is not alerted.
}
```
