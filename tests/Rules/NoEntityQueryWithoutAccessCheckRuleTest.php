<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use MykolaPodpriatov\PhpStanDrupalRules\Rules\NoEntityQueryWithoutAccessCheckRule;

/**
 * @extends RuleTestCase<NoEntityQueryWithoutAccessCheckRule>
 */
final class NoEntityQueryWithoutAccessCheckRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new NoEntityQueryWithoutAccessCheckRule(enabled: true);
    }

    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        // Registers ChainParentVisitor so `chainParent` attributes are set
        // during analysis — exactly as the shipped extension.neon does.
        return [__DIR__ . '/../rules.neon'];
    }

    public function testMissingAccessCheckIsFlagged(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoEntityQueryWithoutAccessCheckRule/missing-access-check.php'],
            [
                [
                    'Entity query is missing an explicit ->accessCheck(TRUE|FALSE). Drupal 10+ requires an explicit access-check decision on every entity query.',
                    10,
                ],
                [
                    'Entity query is missing an explicit ->accessCheck(TRUE|FALSE). Drupal 10+ requires an explicit access-check decision on every entity query.',
                    16,
                ],
            ],
        );
    }

    public function testExplicitAccessCheckIsAccepted(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoEntityQueryWithoutAccessCheckRule/with-access-check.php'],
            [],
        );
    }

    public function testMultiStatementMissingAccessCheckIsFlagged(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoEntityQueryWithoutAccessCheckRule/multi-statement-missing-access-check.php'],
            [
                [
                    'Entity query is missing an explicit ->accessCheck(TRUE|FALSE). Drupal 10+ requires an explicit access-check decision on every entity query.',
                    15,
                ],
                [
                    'Entity query is missing an explicit ->accessCheck(TRUE|FALSE). Drupal 10+ requires an explicit access-check decision on every entity query.',
                    21,
                ],
            ],
        );
    }

    public function testMultiStatementExplicitAccessCheckIsAccepted(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoEntityQueryWithoutAccessCheckRule/multi-statement-with-access-check.php'],
            [],
        );
    }

    public function testReassignedQueryVariableIsNotFlagged(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoEntityQueryWithoutAccessCheckRule/query-variable-reassigned.php'],
            [],
        );
    }

}
