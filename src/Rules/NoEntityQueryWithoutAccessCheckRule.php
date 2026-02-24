<?php

declare(strict_types=1);

namespace YourOrg\PhpStanDrupalRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Require an explicit ->accessCheck() on every entityQuery chain.
 *
 * Since Drupal 10 the entity query API throws if an access check decision is
 * not stated explicitly. Catching the missing call statically is much cheaper
 * than waiting for the request that finally exercises the rare code path.
 *
 * Strategy:
 *  We only look at MethodCall nodes that are the *tail* of a chain (i.e. their
 *  parent is not another method call on them). From the tail we walk down the
 *  receivers and collect method names. If the deepest receiver is
 *  `entityQuery()` (a static call on \Drupal) or `getQuery()` (called on an
 *  entity storage), and the chain does not contain `accessCheck`, we report.
 *
 * @implements Rule<MethodCall>
 */
final class NoEntityQueryWithoutAccessCheckRule implements Rule
{
    public function __construct(
        private readonly bool $enabled = true,
    ) {
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->enabled) {
            return [];
        }

        // Only consider the chain tail — when this node is itself the receiver
        // of an outer call we let that outer call do the reporting instead.
        $parent = $node->getAttribute('parent');
        if ($parent instanceof MethodCall && $parent->var === $node) {
            return [];
        }

        $methods = $this->collectChainMethodNames($node);
        $root = $this->chainRoot($node);

        if (!$this->isEntityQueryRoot($root, $methods)) {
            return [];
        }

        if (in_array('accessCheck', $methods, true)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Entity query is missing an explicit ->accessCheck(TRUE|FALSE). Drupal 10+ requires an explicit access-check decision on every entity query.',
            )
                ->identifier('drupalRules.noEntityQueryWithoutAccessCheck')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    /**
     * Collect method names on the chain, from tail to root.
     *
     * @return list<string>
     */
    private function collectChainMethodNames(MethodCall $node): array
    {
        $names = [];
        $current = $node;
        while ($current instanceof MethodCall) {
            if ($current->name instanceof Identifier) {
                $names[] = $current->name->toString();
            }
            $current = $current->var instanceof MethodCall ? $current->var : null;
            if ($current === null) {
                break;
            }
        }
        return $names;
    }

    /**
     * Return the deepest receiver of the method chain (the actual chain root).
     */
    private function chainRoot(MethodCall $node): Node
    {
        $current = $node;
        while ($current instanceof MethodCall && $current->var instanceof MethodCall) {
            $current = $current->var;
        }
        // $current is a MethodCall whose ->var is *not* another MethodCall.
        return $current instanceof MethodCall ? $current->var : $current;
    }

    /**
     * Heuristic: does this chain start at an entity query?
     *
     * Two acceptable shapes:
     *   \Drupal::entityQuery('node')->...
     *   $storage->getQuery()->...
     *
     * @param list<string> $methods chain method names, tail-to-root.
     */
    private function isEntityQueryRoot(Node $root, array $methods): bool
    {
        if ($root instanceof StaticCall
            && $root->name instanceof Identifier
            && $root->name->toString() === 'entityQuery'
        ) {
            return true;
        }

        if ($methods !== [] && end($methods) === 'getQuery') {
            return true;
        }

        return false;
    }
}
