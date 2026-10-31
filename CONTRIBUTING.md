# Contribution guidelines

## How to contribute step-by-step

1. Fork the `dbout/wp-foundation` repository.
2. Create a new branch from `main` in your fork. This makes it easier for you to keep track of your changes.
3. Make the desired changes to the code.
    * If you are adding a new feature or changing a behavior, update the [documentation](docs/README.md).
    * If you are changing the messages of the validation, update the translations (see below).
    * Add or update the tests (see below).
4. Run the checks (see below).
5. Push the changes to your fork.
6. Create a pull request to the `dbout/wp-foundation` repository.
7. In your pull request, please describe in detail:
    * What problem you’re solving
    * Your approach to fixing the problem
    * Any breaking change
8. Check Allow edits from maintainers.
9. Create the pull request.
10. Ensure that all checks have passed.

After you create your pull request, one of the code owners will review your code.

## Development

The package requires PHP 8.3+.

```bash
composer install
```

## Checks

The pull requests must pass the same checks as the CI:

```bash
composer csFixer   # code style (PER-CS 2.0), fix it with: composer csFixer:fix
composer rector    # refactoring, apply it with: composer rector:fix
composer phpstan   # static analysis (level 9)
composer test      # unit tests
```

The dev dependencies are pinned to fixed versions (the `composer.lock` file is not committed): update them in `composer.json` in a dedicated pull request.

## Tests

The unit tests are in `tests/Unit`, with the same structure as `src`. They run without WordPress: the WordPress functions and hooks are mocked with [Brain Monkey](https://github.com/Brain-WP/BrainMonkey), and the WP-CLI calls are recorded by `Tests\Doubles\Cli`.

```bash
composer test
vendor/bin/phpunit --filter RouterTest
```

The classes, configuration files, templates and manifests used by the tests are in `tests/Fixtures`.

## Translations

The messages of the validation constraints are in English. Their translations are in the `languages` directory (`wp-foundation` text domain). After changing a `.po` file, generate the `.mo` file with [WP-CLI](https://developer.wordpress.org/cli/commands/i18n/make-mo/):

```bash
wp i18n make-mo languages
```

## Commit messages

The commit messages are in English and start with a [gitmoji](https://gitmoji.dev/):

```
✨ Add the X feature
🐛 Fix the Y bug
📝 Document the Z option
```
