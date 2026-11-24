<?php

declare(strict_types=1);

namespace PCFE\Tests;

use PCFE\AttributeArgumentsPerLineFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AttributeArgumentsPerLineFixerTest extends TestCase
{
    private AttributeArgumentsPerLineFixer $fixer;

    #[\Override]
    protected function setUp(): void
    {
        $this->fixer = new AttributeArgumentsPerLineFixer();
    }

    public function testItReportsItsName(): void
    {
        self::assertSame('PCFE/attribute_arguments_per_line', $this->fixer->getName());
        self::assertSame(AttributeArgumentsPerLineFixer::NAME, $this->fixer->getName());
    }

    public function testItIsNotRisky(): void
    {
        self::assertFalse($this->fixer->isRisky());
    }

    public function testItRunsBeforeMethodArgumentSpaceAndArrayIndentation(): void
    {
        // method_argument_space is 30 and array_indentation is 29, so both must see
        // the already split lines.
        self::assertGreaterThan(30, $this->fixer->getPriority());
    }

    public function testItIsACandidateOnlyWhenAnAttributeIsPresent(): void
    {
        self::assertTrue($this->fixer->isCandidate($this->tokens("<?php\n#[Foo(a: 1, b: 2)]\nclass C {}\n")));
        self::assertFalse($this->fixer->isCandidate($this->tokens("<?php\nclass C {}\n")));
    }

    #[DataProvider('provideFixCases')]
    public function testItFixes(string $expected, string $input): void
    {
        self::assertSame($expected, $this->fix($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideFixCases(): iterable
    {
        yield 'two arguments are split and get a trailing comma' => [
            "<?php\n#[Foo(\n    a: 1,\n    b: 2,\n)]\nclass C {}\n",
            "<?php\n#[Foo(a: 1, b: 2)]\nclass C {}\n",
        ];

        yield 'an existing trailing comma is not doubled' => [
            "<?php\n#[Foo(\n    a: 1,\n    b: 2,\n)]\nclass C {}\n",
            "<?php\n#[Foo(a: 1, b: 2,)]\nclass C {}\n",
        ];

        yield 'one argument is left alone' => [
            "<?php\n#[Foo(a: 1)]\nclass C {}\n",
            "<?php\n#[Foo(a: 1)]\nclass C {}\n",
        ];

        yield 'an attribute without arguments is left alone' => [
            "<?php\n#[Foo]\nclass C {}\n",
            "<?php\n#[Foo]\nclass C {}\n",
        ];

        yield 'an empty argument list is left alone' => [
            "<?php\n#[Foo()]\nclass C {}\n",
            "<?php\n#[Foo()]\nclass C {}\n",
        ];

        yield 'a comma inside an array argument does not split' => [
            "<?php\n#[Foo(a: [1, 2])]\nclass C {}\n",
            "<?php\n#[Foo(a: [1, 2])]\nclass C {}\n",
        ];

        yield 'a comma inside a nested call does not count as a separator' => [
            "<?php\n#[Foo(\n    a: bar(1, 2),\n    b: 3,\n)]\nclass C {}\n",
            "<?php\n#[Foo(a: bar(1, 2), b: 3)]\nclass C {}\n",
        ];

        yield 'the indent of the attribute line is kept' => [
            "<?php\nclass C\n{\n    #[Foo(\n        a: 1,\n        b: 2,\n    )]\n    public int \$x = 0;\n}\n",
            "<?php\nclass C\n{\n    #[Foo(a: 1, b: 2)]\n    public int \$x = 0;\n}\n",
        ];

        yield 'two attributes in one block are both split' => [
            "<?php\n#[Foo(\n    a: 1,\n    b: 2,\n), Bar(\n    c: 3,\n    d: 4,\n)]\nclass C {}\n",
            "<?php\n#[Foo(a: 1, b: 2), Bar(c: 3, d: 4)]\nclass C {}\n",
        ];

        yield 'an already split list is left as it is' => [
            "<?php\n#[Foo(\n    a: 1,\n    b: 2,\n)]\nclass C {}\n",
            "<?php\n#[Foo(\n    a: 1,\n    b: 2,\n)]\nclass C {}\n",
        ];
    }

    public function testItKeepsACommentInTheArgumentListOnItsLine(): void
    {
        $input = "<?php\n#[Foo(a: 1, /* why */ b: 2)]\nclass C {}\n";
        $output = $this->fix($input);

        self::assertStringContainsString('/* why */', $output);
        // The comment keeps the token right in front of it, so no line break is
        // inserted there.
        self::assertStringContainsString('/* why */ b: 2', $output);
    }

    private function fix(string $code): string
    {
        $tokens = $this->tokens($code);
        $this->fixer->fix(new \SplFileInfo(__FILE__), $tokens);

        return $tokens->generateCode();
    }

    private function tokens(string $code): Tokens
    {
        return Tokens::fromCode($code);
    }
}
