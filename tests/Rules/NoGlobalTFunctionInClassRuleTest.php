<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use MykolaPodpriatov\PhpStanDrupalRules\Rules\NoGlobalTFunctionInClassRule;

/**
 * @extends RuleTestCase<NoGlobalTFunctionInClassRule>
 */
final class NoGlobalTFunctionInClassRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new NoGlobalTFunctionInClassRule(enabled: true);
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

    public function testGlobalTInsideClassIsFlagged(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoGlobalTFunctionInClassRule/uses-global-t.php'],
            [
                [
                    'Global t() is called inside class MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoGlobalTFunctionInClassRule\GlobalTController. Use $this->t() via StringTranslationTrait (or an injected translator) so the string stays translatable and the class stays testable.',
                    16,
                ],
                [
                    'Global t() is called inside class MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoGlobalTFunctionInClassRule\GlobalTController. Use $this->t() via StringTranslationTrait (or an injected translator) so the string stays translatable and the class stays testable.',
                    20,
                ],
            ],
        );
    }

    public function testInjectedTAndProceduralTAreSilent(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoGlobalTFunctionInClassRule/uses-injected-t.php'],
            [],
        );
    }

}
