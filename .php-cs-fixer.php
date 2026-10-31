<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);
$finder = PhpCsFixer\Finder::create()
    ->name('*.php')
    ->in([
        'src',
        'config',
        'tests',
    ])
;

$header = <<<EOF
    Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
    See LICENSE.txt for license details.
    EOF;

$config = new PhpCsFixer\Config();

return $config
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        '@PHP83Migration' => true,

        // PER-CS overrides
        'blank_line_after_opening_tag' => false,
        'function_declaration' => ['closure_fn_spacing' => 'one'],
        'trailing_comma_in_multiline' => [
            'after_heredoc' => true,
            'elements' => ['arguments', 'array_destructuring', 'arrays', 'match', 'parameters'],
        ],

        // Whitespace & blank lines
        'array_indentation' => true,
        'binary_operator_spaces' => ['default' => 'single_space'],
        'blank_line_before_statement' => ['statements' => ['return', 'throw', 'try']],
        'blank_lines_before_namespace' => ['min_line_breaks' => 2],
        'cast_spaces' => true,
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'method' => 'one',
                'property' => 'one',
                'trait_import' => 'none',
                'case' => 'none',
            ],
        ],
        'concat_space' => ['spacing' => 'one'],
        'method_chaining_indentation' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'attribute',
                'break',
                'case',
                'continue',
                'curly_brace_block',
                'default',
                'extra',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'switch',
                'throw',
                'use',
            ],
        ],
        'no_singleline_whitespace_before_semicolons' => true,
        'no_spaces_around_offset' => true,
        'single_line_empty_body' => true,
        'trim_array_spaces' => true,
        'type_declaration_spaces' => true,
        'unary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => ['ensure_single_space' => true],

        // Syntax
        'array_syntax' => ['syntax' => 'short'],
        'combine_consecutive_unsets' => true,
        'declare_strict_types' => true,
        'fully_qualified_strict_types' => true,
        'get_class_to_class_keyword' => true,
        'modernize_strpos' => true,
        'new_with_parentheses' => true,
        'no_empty_comment' => true,
        'no_empty_statement' => true,
        'no_superfluous_elseif' => true,
        'nullable_type_declaration' => ['syntax' => 'question_mark'],
        'nullable_type_declaration_for_default_null_value' => true,
        'octal_notation' => false,
        'ordered_types' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        'self_accessor' => true,
        'simplified_if_return' => true,
        'single_quote' => true,
        'strict_param' => true,

        // Imports & class structure
        'no_unused_imports' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],

        // PHPDoc
        'align_multiline_comment' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_phpdoc' => true,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true, 'remove_inheritdoc' => false],
        'phpdoc_indent' => true,
        'phpdoc_no_package' => true,
        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,

        'header_comment' => [
            'header' => $header,
            'comment_type' => 'PHPDoc',
            'location' => 'after_open',
            'separate' => 'none',
        ],
    ]);
