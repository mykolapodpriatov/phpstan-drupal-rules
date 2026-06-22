<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use MykolaPodpriatov\PhpStanDrupalRules\Rules\HookImplementationSignatureRule;

/**
 * @extends RuleTestCase<HookImplementationSignatureRule>
 */
final class HookImplementationSignatureRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new HookImplementationSignatureRule(enabled: true);
    }

    public function testWrongSignaturesAreFlagged(): void
    {
        $this->analyse(
            [__DIR__ . '/data/HookImplementationSignatureRule/wrong-signature.php'],
            [
                [
                    'Hook implementation my_module_form_alter() looks like hook_form_alter but has 2 parameter(s); expected 3. Documented signature: (array &$form, FormStateInterface $form_state, string $form_id).',
                    6,
                ],
                [
                    'Hook implementation my_module_entity_presave() looks like hook_entity_presave but has 2 parameter(s); expected 1. Documented signature: (EntityInterface $entity).',
                    12,
                ],
                [
                    'Hook implementation my_module_theme() looks like hook_theme but has 0 parameter(s); expected 4. Documented signature: (array $existing, $type, $theme, $path).',
                    18,
                ],
            ],
        );
    }

    public function testCorrectSignaturesPass(): void
    {
        $this->analyse(
            [__DIR__ . '/data/HookImplementationSignatureRule/correct-signature.php'],
            [],
        );
    }

}
