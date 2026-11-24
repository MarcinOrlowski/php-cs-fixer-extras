# php-cs-fixer-extras

Extra fixers for [PHP CS Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer),
for rules it does not ship.

## Install

```bash
composer require --dev marcin-orlowski/php-cs-fixer-extras
```

Requires PHP 8.3+ and PHP CS Fixer 3.95+.

## Use

Register the fixers, then turn on the ones you want by name:

```php
<?php

// .php-cs-fixer.dist.php

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

A custom fixer must be **registered and enabled**. Registering it alone does nothing.

## Fixers

| Rule | What it does |
| ---- | ------------ |
| [`PCFE/attribute_arguments_per_line`](docs/attribute-arguments-per-line.md) | An attribute with two or more arguments gets one argument per line and a trailing comma. |

## Develop

```bash
composer install
composer test        # PHPUnit
composer cs-check    # this package's own style, dry run
composer cs-fix
```

## License

* Written and copyrighted &copy;2026 by Marcin Orlowski <mail (#) marcinorlowski (.) com>
* This is open-source software licensed under the [MIT license](http://opensource.org/licenses/MIT)
