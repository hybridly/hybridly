<?php

$finder = Symfony\Component\Finder\Finder::create()
    ->in([
        __DIR__ . '/build',
        __DIR__ . '/tests',
        __DIR__ . '/packages/laravel/src',
        __DIR__ . '/packages/laravel/config',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PHP82Migration' => true,
        '@PhpCsFixer:risky' => true,
        'single_space_around_construct' => true,
        'heredoc_indentation' => ['indentation' => 'same_as_start'],
        'declare_strict_types' => false,
        'single_quote' => true,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'comment_to_phpdoc' => false,
        'is_null' => false,
        'types_spaces' => true,
        'ternary_operator_spaces' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => [
                'class',
                'function',
                'const',
            ],
        ],
        'ordered_class_elements' => true,
        'no_trailing_whitespace_in_string' => false,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'concat_space' => ['spacing' => 'one'],
        'single_trait_insert_per_statement' => true,
        'explicit_string_variable' => true,
        'single_line_throw' => false,
        'not_operator_with_successor_space' => false,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
        'phpdoc_scalar' => true,
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => true,
        'logical_operators' => true,
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
        'method_chaining_indentation' => true,
        'array_indentation' => true,
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'trait_import' => 'none',
            ],
        ],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            // 'keep_multiple_spaces_after_comma' => true,
        ],
        'no_leading_import_slash' => true,
        'no_alternative_syntax' => true,
        'native_function_casing' => true,
        'native_function_type_declaration_casing' => true,
        'single_line_after_imports' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => true,
        'blank_lines_before_namespace' => true,
        'curly_braces_position' => [
            'anonymous_classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
        ],
        'no_extra_blank_lines' => [
            'tokens' => [
                'case',
                'continue',
                'curly_brace_block',
                'default',
                'extra',
                'parenthesis_brace_block',
                'square_brace_block',
                'switch',
                'throw',
                'use',
            ],
        ],
        'native_constant_invocation' => [
            'include' => ['@compiler_optimized'],
            'strict' => true,
        ],
        'native_function_invocation' => [
            'scope' => 'namespaced',
            'strict' => true,
        ],
        'static_lambda' => false,
    ])
    ->setFinder($finder);
