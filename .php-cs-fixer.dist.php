<?php

declare(strict_types=1);

/**
 * Style of this package. The rule set mirrors the projects that consume the package, so code moved
 * between them does not change shape. The package also uses its own fixer, which is the simplest
 * way to keep it honest.
 */
$finder = new PhpCsFixer\Finder()
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    // Skip files git ignores.
    ->ignoreVCSIgnored(true)
;

return new PhpCsFixer\Config()
    ->setRiskyAllowed(true)
    ->registerCustomFixers([
        new PCFE\AttributeArgumentsPerLineFixer(),
    ])
    // Docs on available rules: https://github.com/PHP-CS-Fixer/PHP-CS-Fixer
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => true,
        'static_lambda' => false,
        'yoda_style' => false,
        // @Symfony aligns PHPDoc tags vertically, padding `@param` with spaces so the types and
        // variable names line up. We want no padding. `left` is what removes it; turning the rule
        // off would only stop new padding and leave what is already there.
        'phpdoc_align' => ['align' => 'left'],
        // Part of @Symfony:risky. It prefixes the compiler-optimized natives, sprintf(), count(),
        // in_array(), array_key_exists(), is_string() and friends, with a backslash. We do not want
        // that: `include: []` marks no function as one that needs the backslash, and `strict: true`
        // removes it from every native function call that has it.
        'native_function_invocation' => ['include' => [], 'strict' => true],
        // Global classes and constants are written as `\SplFileInfo`, `\T_ATTRIBUTE`, as @Symfony
        // does. Global functions are left as they are, so `strspn()` stays as it is.
        'global_namespace_import' => [
            'import_classes' => false,
            'import_constants' => false,
            'import_functions' => null,
        ],
        // Part of @Symfony, which only shortens an FQCN that already has a `use`. `import_symbols`
        // adds the missing import too, so a namespaced class is never written inline. Single-segment
        // global classes stay inline, which is what `global_namespace_import` above asks for.
        'fully_qualified_strict_types' => ['import_symbols' => true],
        // Part of @Symfony. It joins a multi-line `throw` into one line, however long it gets.
        'single_line_throw' => false,
        // @Symfony joins a multi-line `implements`/`extends` list into one line, however long it
        // gets.
        'class_definition' => ['single_line' => false],
        // @Symfony writes `'a'.$b`. We keep one space around the concatenation operator: `'a' . $b`.
        'concat_space' => ['spacing' => 'one'],
        // Static analysers read `@var` and `(@)phpstan-ignore-next-line` only from `/** */`
        // docblocks, so they must not become plain `/* */` comments.
        'phpdoc_to_comment' => [
            'ignored_tags' => ['var', 'phpstan-ignore-next-line'],
        ],
        // The pre-commit `trailing-whitespace` hook strips trailing spaces inside multi-line strings
        // too. Doing it here keeps the fixer output and the committed code the same.
        'no_trailing_whitespace_in_string' => true,
        // Part of @Symfony. It takes a `/* ****** */` separator line for an empty comment and
        // removes it. We use these separators, so it stays off.
        'no_empty_comment' => false,
        // An empty body stays as `) {` and `}` on two lines instead of `) {}`.
        'single_line_empty_body' => false,
        // @Symfony writes a space after a cast: `(int) $value`. We write it without: `(int)$value`.
        'cast_spaces' => ['space' => 'none'],
        // An attribute without arguments has no empty parentheses: `#[Override]`, not
        // `#[Override()]`.
        'attribute_empty_parentheses' => ['use_parentheses' => false],
        // Part of @Symfony:risky, which strips the `b` flag from fopen() modes. Binary streams are
        // meant to say so, and the flag is a no-op on Linux but not on every platform.
        'fopen_flags' => ['b_mode' => true],
        // This package's own rule: an attribute with 2 or more arguments gets one argument per line.
        PCFE\AttributeArgumentsPerLineFixer::NAME => true,
    ])
    ->setFinder($finder);
