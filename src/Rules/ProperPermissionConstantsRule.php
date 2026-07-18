<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Discourage hard-coded permission strings in access checks.
 *
 * Permission machine names are effectively an API contract between modules,
 * yet they are commonly sprinkled through controllers and access handlers as
 * bare string literals. When a permission is renamed the string copies rot
 * silently. Promoting each permission to a class constant (or a
 * `permissions.yml`-backed registry) makes those references refactor-safe and
 * greppable.
 *
 * The rule flags string literals passed to:
 *  - `->hasPermission('...')` (the first argument), and
 *  - `AccessResult::allowedIfHasPermission($account, '...')` (the second
 *    argument, per the Drupal core signature).
 *
 * Class constants (`MyModule::ADMINISTER`, `self::EDIT`, …) pass through
 * untouched, as does any permission listed in the configured allowlist — a
 * handful of stable core strings such as `access content` are rarely worth
 * promoting.
 *
 * @implements Rule<Node>
 */
final class ProperPermissionConstantsRule implements Rule
{
    /**
     * @param list<string> $allowedPermissions Permission strings allowed to stay as literals.
     * @param bool         $enabled            Master switch.
     */
    public function __construct(
        private readonly array $allowedPermissions = [],
        private readonly bool $enabled = true,
    ) {
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->enabled) {
            return [];
        }

        if ($node instanceof MethodCall) {
            return $this->processMethodCall($node);
        }

        if ($node instanceof StaticCall) {
            return $this->processStaticCall($node);
        }

        return [];
    }

    /**
     * `$account->hasPermission('...')` — the permission is the first argument.
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processMethodCall(MethodCall $node): array
    {
        if (!$node->name instanceof Identifier) {
            return [];
        }
        if ($node->name->toString() !== 'hasPermission') {
            return [];
        }

        return $this->checkPermissionArgument($node, 0, '->hasPermission()');
    }

    /**
     * `AccessResult::allowedIfHasPermission($account, '...')` — the permission
     * is the second argument.
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processStaticCall(StaticCall $node): array
    {
        if (!$node->class instanceof Name) {
            return [];
        }
        if (!$node->name instanceof Identifier) {
            return [];
        }
        if ($node->name->toString() !== 'allowedIfHasPermission') {
            return [];
        }

        $parts = explode('\\', ltrim($node->class->toString(), '\\'));
        if (end($parts) !== 'AccessResult') {
            return [];
        }

        return $this->checkPermissionArgument($node, 1, 'AccessResult::allowedIfHasPermission()');
    }

    /**
     * Report when argument $index of $node is a non-allowlisted string literal.
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function checkPermissionArgument(CallLike $node, int $index, string $context): array
    {
        $args = $node->getArgs();
        if (!isset($args[$index])) {
            return [];
        }

        $value = $args[$index]->value;
        if (!$value instanceof String_) {
            // Class constants, variables, concatenations, etc. pass through.
            return [];
        }

        $permission = $value->value;
        if (in_array($permission, $this->allowedPermissions, true)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Permission \'%s\' is passed to %s as a string literal. Promote it to a class constant (or a permissions.yml-backed registry) so references stay refactor-safe.',
                $permission,
                $context,
            ))
                ->identifier('drupalRules.properPermissionConstants')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
