<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\BaselineFixture;

/**
 * Smoke-test fixture for baseline.neon.
 *
 * The CI "Validate baseline.neon" step runs PHPStan with only this extension
 * loaded, so every referenced symbol is defined locally to keep the native
 * analysis clean. A green run proves that:
 *   - the two enabled rules (noEntityQueryWithoutAccessCheck, noDeprecatedEntityApi)
 *     load and stay silent on clean code, and
 *   - the disabled hookImplementationSignature rule is genuinely off — the
 *     malformed hook below would trip it if the baseline turned it on.
 */

/**
 * Minimal local stand-in for the Drupal entity query builder so this fixture
 * resolves without a full Drupal install.
 */
final class Drupal
{
    public static function entityQuery(string $entityTypeId): EntityQuery
    {
        return new EntityQuery();
    }
}

final class EntityQuery
{
    public function accessCheck(bool $access): self
    {
        return $this;
    }

    public function condition(string $field, mixed $value): self
    {
        return $this;
    }

    /**
     * @return list<int>
     */
    public function execute(): array
    {
        return [];
    }
}

/**
 * ON in the baseline: noEntityQueryWithoutAccessCheck. The chain states its
 * access-check decision, so the rule stays silent. (noDeprecatedEntityApi is
 * also on and stays silent — no deprecated APIs are used anywhere here.)
 *
 * @return list<int>
 */
function baseline_clean_query(): array
{
    return Drupal::entityQuery('node')
        ->accessCheck(true)
        ->condition('status', 1)
        ->execute();
}

/**
 * OFF in the baseline: hookImplementationSignature. hook_form_alter needs three
 * parameters; this has two, so the rule WOULD flag it — but the baseline keeps
 * that rule off until a project is ready for it, so the check stays green.
 */
function baseline_form_alter(array &$form, object $form_state): void
{
}
