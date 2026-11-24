<?php

/**
 * Attributes with multiple arguments are split into one argument per line.
 *
 * @author Marcin Orlowski <marcin.orlowski@expert4you.pl>
 */

declare(strict_types=1);

namespace PCFE;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;

/**
 * Puts each argument of an attribute with 2 or more arguments on its own line, adds
 * a trailing comma, and puts the closing parenthesis on its own line:
 *
 *     #[ORM\Column(
 *         type: Types::DATE_IMMUTABLE,
 *         nullable: true,
 *     )]
 *
 * An attribute with 1 argument (or none) is not changed. A comment in the argument
 * list stays where it is: there is no line break in front of it.
 *
 * PHP-CS-Fixer has no built-in rule for this: `method_argument_space` with
 * `ensure_fully_multiline` only completes a split that was already started.
 */
final class AttributeArgumentsPerLineFixer implements FixerInterface, WhitespacesAwareFixerInterface
{
    public const string NAME = 'PCFE/attribute_arguments_per_line';

    private WhitespacesFixerConfig $whitespaces;

    public function __construct()
    {
        $this->whitespaces = new WhitespacesFixerConfig();
    }

    #[\Override]
    public function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Each argument of an attribute with 2 or more arguments is on its own line, with a trailing comma.',
            [new CodeSample("<?php\n#[ORM\\Column(type: Types::TEXT, nullable: true)]\nprivate ?string \$text;\n")],
        );
    }

    /**
     * Before `method_argument_space` (30) and `array_indentation` (29),
     * so that they work on the split lines.
     */
    #[\Override]
    public function getPriority(): int
    {
        return 31;
    }

    #[\Override]
    public function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public function supports(\SplFileInfo $file): bool
    {
        return true;
    }

    #[\Override]
    public function setWhitespacesConfig(WhitespacesFixerConfig $config): void
    {
        $this->whitespaces = $config;
    }

    #[\Override]
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_ATTRIBUTE);
    }

    #[\Override]
    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        // From the end of the file: a token inserted into a list does
        // not move the lists before it.
        foreach (array_reverse($this->argumentLists($tokens)) as $open) {
            $this->splitArguments($tokens, $open);
        }
    }

    /**
     * The index of the "(" of each attribute argument list, in file order.
     *
     * @return list<int>
     */
    private function argumentLists(Tokens $tokens): array
    {
        $lists = [];
        for ($index = 0, $count = $tokens->count(); $index < $count; ++$index) {
            if (!$tokens[$index]->isGivenKind(\T_ATTRIBUTE)) {
                continue;
            }
            // One "#[...]" block can have more than one attribute: "#[A(x: 1), B(y: 2)]".
            $end = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ATTRIBUTE, $index);
            for ($inner = $index + 1; $inner < $end; ++$inner) {
                if ($tokens[$inner]->equals('(')) {
                    $lists[] = $inner;
                    $inner = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS,
                        $inner);
                }
            }
            $index = $end;
        }

        return $lists;
    }

    private function splitArguments(Tokens $tokens, int $open): void
    {
        $close = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS, $open);
        $last = $tokens->getPrevMeaningfulToken($close) ?? $open;
        $separators = $this->separators($tokens, $open, $close);
        $hasTrailingComma = $tokens[$last]->equals(',');
        if ($hasTrailingComma) {
            // The trailing comma separates no arguments.
            array_pop($separators);
        }
        if ($separators === []) {
            return;
        }

        $lineEnding = $this->whitespaces->getLineEnding();
        $indent = $this->lineIndent($tokens, $open);
        $argumentStart = $lineEnding . $indent . $this->whitespaces->getIndent();

        // From the right to the left: a token inserted here does not move the
        // indexes still to do.
        if (!$hasTrailingComma) {
            $tokens->insertAt($last + 1, new Token(','));
            ++$last;
        }
        $this->breakLineAfter($tokens, $last, $lineEnding . $indent);
        foreach (array_reverse($separators) as $comma) {
            $this->breakLineAfter($tokens, $comma, $argumentStart);
        }
        $this->breakLineAfter($tokens, $open, $argumentStart);
    }

    /**
     * The commas between the arguments of the list. Commas inside an argument
     * (in an array, a call ...) do not count.
     *
     * @return list<int>
     */
    private function separators(Tokens $tokens, int $open, int $close): array
    {
        $commas = [];
        for ($index = $open + 1; $index < $close; ++$index) {
            $block = Tokens::detectBlockType($tokens[$index]);
            if ($block !== null && $block['isStart']) {
                $index = $tokens->findBlockEnd($block['type'], $index);
            } elseif ($tokens[$index]->equals(',')) {
                $commas[] = $index;
            }
        }

        return $commas;
    }

    /**
     * Sets the whitespace after the token at $index to $whitespace, unless a
     * comment follows the token: then nothing changes, so that the comment stays
     * on its line.
     */
    private function breakLineAfter(Tokens $tokens, int $index,
        string $whitespace): void
    {
        $next = $tokens->getNextNonWhitespace($index);
        if ($next !== null && $tokens[$next]->isComment()) {
            return;
        }
        $tokens->ensureWhitespaceAtIndex($index + 1, 0, $whitespace);
    }

    /**
     * The indent (spaces and tabs) at the start of the line that has the
     * token at $index.
     */
    private function lineIndent(Tokens $tokens, int $index): string
    {
        for ($previous = $index - 1; $previous >= 0; --$previous) {
            $content = $tokens[$previous]->getContent();
            $lineBreak = strrpos($content, "\n");
            if ($lineBreak !== false) {
                $lineStart = substr($content, $lineBreak + 1);

                return substr($lineStart, 0, strspn($lineStart, " \t"));
            }
        }

        return '';
    }
}
