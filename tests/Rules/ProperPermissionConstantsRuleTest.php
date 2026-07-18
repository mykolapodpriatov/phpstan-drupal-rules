<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use MykolaPodpriatov\PhpStanDrupalRules\Rules\ProperPermissionConstantsRule;

/**
 * @extends RuleTestCase<ProperPermissionConstantsRule>
 */
final class ProperPermissionConstantsRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ProperPermissionConstantsRule(
            allowedPermissions: [
                'access content',
            ],
            enabled: true,
        );
    }

    public function testLiteralPermissionsAreFlagged(): void
    {
        $this->analyse(
            [__DIR__ . '/data/ProperPermissionConstantsRule/literal-permissions.php'],
            [
                [
                    "Permission 'administer nodes' is passed to ->hasPermission() as a string literal. Promote it to a class constant (or a permissions.yml-backed registry) so references stay refactor-safe.",
                    13,
                ],
                [
                    "Permission 'administer users' is passed to AccessResult::allowedIfHasPermission() as a string literal. Promote it to a class constant (or a permissions.yml-backed registry) so references stay refactor-safe.",
                    16,
                ],
            ],
        );
    }

    public function testConstantAndAllowlistedPermissionsAreSilent(): void
    {
        $this->analyse(
            [__DIR__ . '/data/ProperPermissionConstantsRule/constant-permissions.php'],
            [],
        );
    }

}
