# `PCFE/attribute_arguments_per_line`

An attribute with **two or more** arguments gets one argument per line, a trailing comma after the last one, and `)]` on its own line. An attribute with one argument, or none, is left as it is.

PHP CS Fixer has no built-in equivalent: `method_argument_space` with `ensure_fully_multiline` only finishes a split that was already started, so an attribute written on one line stays on one line.

- Class: `PCFE\AttributeArgumentsPerLineFixer`
- Risky: no, it only moves whitespace and adds a trailing comma
- Priority: 31, so it runs before `method_argument_space` (30) and `array_indentation` (29) and they see the split lines

## Enable it

```php
<?php

// .php-cs-fixer.dist.php

$finder = new PhpCsFixer\Finder()
    ->in(__DIR__ . '/src')
    ->ignoreVCSIgnored(true)
;

return new PhpCsFixer\Config()
    ->registerCustomFixers([
        new PCFE\AttributeArgumentsPerLineFixer(),
    ])
    ->setRules([
        '@Symfony' => true,
        PCFE\AttributeArgumentsPerLineFixer::NAME => true,
    ])
    ->setFinder($finder);
```

Then:

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff    # see what would change
vendor/bin/php-cs-fixer fix                     # apply
```

## What it changes

Two or more arguments are split:

```php
// before
#[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
private ?\DateTimeImmutable $dueDate = null;

// after
#[ORM\Column(
    type: Types::DATE_IMMUTABLE,
    nullable: true,
)]
private ?\DateTimeImmutable $dueDate = null;
```

One argument is left alone:

```php
#[Assert\Length(max: 255)]
private string $name = '';
```

The indent of the attribute's own line is kept:

```php
class Board
{
    #[ORM\Column(
        type: Types::STRING,
        length: 255,
    )]
    private string $name = '';
}
```

Several attributes in one block are each handled:

```php
// before
#[Foo(a: 1, b: 2), Bar(c: 3, d: 4)]

// after
#[Foo(
    a: 1,
    b: 2,
), Bar(
    c: 3,
    d: 4,
)]
```

## What it leaves alone

A comma **inside** an argument is not a separator, so a single argument holding an array or a call stays on one line:

```php
#[Foo(a: [1, 2])]
#[Foo(a: bar(1, 2))]
```

An attribute with no arguments, or an empty argument list:

```php
#[Foo]
#[Foo()]
```

A comment in the argument list keeps the token in front of it, so it stays on its line rather than being pushed down:

```php
#[Foo(a: 1, /* why */ b: 2)]
```

An already split list is not touched again, so the fixer is idempotent.
