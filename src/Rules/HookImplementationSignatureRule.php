<?php

declare(strict_types=1);

namespace YourOrg\PhpStanDrupalRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Validate the signature of common Drupal hook implementations.
 *
 * Drupal hooks have a documented contract — a fixed number of positional
 * parameters with documented types. When a hook body grows or is copied from
 * a different hook, the signature often drifts and Drupal silently calls the
 * implementation with the wrong arguments. This rule catches that.
 *
 * The implementation is intentionally conservative:
 *  - It only fires on top-level functions whose name matches `*_<hook_suffix>`.
 *  - It checks parameter counts strictly.
 *  - When a hook supports a fixed-arity variant only (e.g. hook_theme), it
 *    flags any deviation.
 *  - When a hook supports a known parameter range (e.g. hook_form_alter takes
 *    3 args), the check is exact too.
 *
 * @implements Rule<Function_>
 */
final class HookImplementationSignatureRule implements Rule
{
    /**
     * Hook suffix => [expectedParameterCount, humanReadableSignature].
     *
     * @var array<string, array{int, string}>
     */
    private const HOOK_SIGNATURES = [
        // Form / entity form lifecycle.
        'form_alter'                    => [3, '(array &$form, FormStateInterface $form_state, string $form_id)'],
        'entity_presave'                => [1, '(EntityInterface $entity)'],
        'entity_insert'                 => [1, '(EntityInterface $entity)'],
        'entity_update'                 => [1, '(EntityInterface $entity)'],
        'entity_delete'                 => [1, '(EntityInterface $entity)'],
        'entity_access'                 => [3, '(EntityInterface $entity, $operation, AccountInterface $account)'],

        // Node access.
        'node_access'                   => [3, '(NodeInterface $node, $op, AccountInterface $account)'],

        // Views.
        'views_pre_render'              => [1, '(ViewExecutable $view)'],
        'views_post_execute'            => [1, '(ViewExecutable $view)'],

        // Theme / render.
        'theme'                         => [4, '(array $existing, $type, $theme, $path)'],
        'preprocess_HOOK'               => [1, '(array &$variables)'],

        // Module lifecycle.
        'install'                       => [1, '(bool $is_syncing)'],
        'uninstall'                     => [1, '(bool $is_syncing)'],
    ];

    public function __construct(
        private readonly bool $enabled = true,
    ) {
    }

    public function getNodeType(): string
    {
        return Function_::class;
    }

    /**
     * @param Function_ $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->enabled) {
            return [];
        }

        $functionName = $node->name->toString();
        $match = $this->matchHook($functionName);
        if ($match === null) {
            return [];
        }

        [$module, $suffix, $expected, $signature] = $match;
        $actual = count($node->getParams());

        if ($actual === $expected) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Hook implementation %s() looks like hook_%s but has %d parameter(s); expected %d. Documented signature: %s.',
                $functionName,
                $suffix,
                $actual,
                $expected,
                $signature,
            ))
                ->identifier('drupalRules.hookImplementationSignature')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    /**
     * Return [module, suffix, expectedParamCount, signature] or null when no hook matches.
     *
     * @return array{string, string, int, string}|null
     */
    private function matchHook(string $functionName): ?array
    {
        if (!str_contains($functionName, '_')) {
            return null;
        }

        // hook_preprocess_HOOK: any function named <module>_preprocess_<anything>.
        if (preg_match('/^([a-z][a-z0-9_]+?)_preprocess_[a-z][a-z0-9_]*$/', $functionName, $m) === 1) {
            [$count, $sig] = self::HOOK_SIGNATURES['preprocess_HOOK'];
            return [$m[1], 'preprocess_HOOK', $count, $sig];
        }

        // Longest suffix wins to avoid matching `_alter` instead of `form_alter`.
        $suffixes = array_keys(self::HOOK_SIGNATURES);
        usort($suffixes, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($suffixes as $suffix) {
            if ($suffix === 'preprocess_HOOK') {
                continue;
            }
            $needle = '_' . $suffix;
            if (!str_ends_with($functionName, $needle)) {
                continue;
            }
            $module = substr($functionName, 0, -strlen($needle));
            if ($module === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $module)) {
                continue;
            }
            [$count, $sig] = self::HOOK_SIGNATURES[$suffix];
            return [$module, $suffix, $count, $sig];
        }

        return null;
    }
}
