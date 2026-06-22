<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use MykolaPodpriatov\PhpStanDrupalRules\Rules\NoDeprecatedEntityApiRule;

/**
 * @extends RuleTestCase<NoDeprecatedEntityApiRule>
 */
final class NoDeprecatedEntityApiRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new NoDeprecatedEntityApiRule(enabled: true);
    }

    public function testDeprecatedFunctionsAreFlagged(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoDeprecatedEntityApiRule/deprecated-functions.php'],
            [
                [
                    'Function node_load() is deprecated/removed in Drupal 10.3+. Use \Drupal::entityTypeManager()->getStorage(\'node\')->load($nid).',
                    10,
                ],
                [
                    'Function user_load() is deprecated/removed in Drupal 10.3+. Use \Drupal::entityTypeManager()->getStorage(\'user\')->load($uid).',
                    13,
                ],
                [
                    'Function drupal_render() is deprecated/removed in Drupal 10.3+. Use the renderer service: \Drupal::service(\'renderer\')->render($build) — or better, return the render array.',
                    16,
                ],
                [
                    'Function drupal_set_message() is deprecated/removed in Drupal 10.3+. Use the messenger service: \Drupal::messenger()->addStatus() / addWarning() / addError().',
                    19,
                ],
                [
                    '\Drupal::entityManager() is removed. Use \Drupal::entityTypeManager() or inject entity_type.manager.',
                    22,
                ],
            ],
        );
    }

    public function testModernReplacementsAreSilent(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoDeprecatedEntityApiRule/modern-replacements.php'],
            [],
        );
    }

}
