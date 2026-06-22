<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use MykolaPodpriatov\PhpStanDrupalRules\Rules\NoServiceLocatorInDIClassRule;

/**
 * @extends RuleTestCase<NoServiceLocatorInDIClassRule>
 */
final class NoServiceLocatorInDIClassRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new NoServiceLocatorInDIClassRule(
            diBaseClasses: [
                'Drupal\\Core\\Controller\\ControllerBase',
                'Drupal\\Core\\Form\\FormBase',
                'Drupal\\Core\\Form\\ConfigFormBase',
                'Drupal\\Core\\Block\\BlockBase',
                'Drupal\\Core\\Condition\\ConditionPluginBase',
                'Drupal\\Core\\Field\\FormatterBase',
                'Drupal\\Core\\Field\\WidgetBase',
                'Drupal\\Core\\Plugin\\PluginBase',
            ],
            forbiddenLocatorMethods: [
                'service',
                'config',
                'entityTypeManager',
                'entityQuery',
                'currentUser',
                'logger',
                'state',
                'cache',
                'database',
                'moduleHandler',
                'messenger',
            ],
            enabled: true,
        );
    }

    public function testControllerWithDIUsingLocatorIsFlagged(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoServiceLocatorInDIClassRule/controller-with-di-uses-locator.php'],
            [
                [
                    'Use of service locator \Drupal::config() inside DI-aware class MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoServiceLocatorInDIClassRule\BadController. Inject the service via constructor.',
                    18,
                ],
                [
                    'Use of service locator \Drupal::service() inside DI-aware class MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoServiceLocatorInDIClassRule\BadController. Inject the service via constructor.',
                    21,
                ],
            ],
        );
    }

    public function testControllerWithCleanDIDoesNotTrigger(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoServiceLocatorInDIClassRule/controller-with-di-clean.php'],
            [],
        );
    }

    public function testPureStaticHelperIsAllowedToUseLocator(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoServiceLocatorInDIClassRule/pure-static-class.php'],
            [],
        );
    }

}
