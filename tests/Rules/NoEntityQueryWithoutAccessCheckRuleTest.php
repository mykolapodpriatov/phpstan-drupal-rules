<?php

declare(strict_types=1);

namespace YourOrg\PhpStanDrupalRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use YourOrg\PhpStanDrupalRules\Rules\NoEntityQueryWithoutAccessCheckRule;

/**
 * @extends RuleTestCase<NoEntityQueryWithoutAccessCheckRule>
 */
final class NoEntityQueryWithoutAccessCheckRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new NoEntityQueryWithoutAccessCheckRule(enabled: true);
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

}
