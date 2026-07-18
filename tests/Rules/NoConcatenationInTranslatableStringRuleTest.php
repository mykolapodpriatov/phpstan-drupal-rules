<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use MykolaPodpriatov\PhpStanDrupalRules\Rules\NoConcatenationInTranslatableStringRule;

/**
 * @extends RuleTestCase<NoConcatenationInTranslatableStringRule>
 */
final class NoConcatenationInTranslatableStringRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new NoConcatenationInTranslatableStringRule(enabled: true);
    }

    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        // Registers the global t() stub so the analyser recognises the
        // procedural helper the fixtures call.
        return [__DIR__ . '/../stub-functions.neon'];
    }

    public function testConcatenationIsFlagged(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoConcatenationInTranslatableStringRule/concatenated-string.php'],
            [
                [
                    'Concatenation in the first argument of $this->t() breaks translation string extraction. Use a single source string with placeholders (@var, %var, :var) and pass the values in the second argument.',
                    20,
                ],
                [
                    'Concatenation in the first argument of t() breaks translation string extraction. Use a single source string with placeholders (@var, %var, :var) and pass the values in the second argument.',
                    26,
                ],
            ],
        );
    }

    public function testPlaceholderArgumentsAreSilent(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoConcatenationInTranslatableStringRule/placeholder-args.php'],
            [],
        );
    }

}
