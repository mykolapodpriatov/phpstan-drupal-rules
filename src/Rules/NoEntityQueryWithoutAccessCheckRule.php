<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Rules;

use MykolaPodpriatov\PhpStanDrupalRules\NodeVisitor\ChainParentVisitor;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
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
 *  parent is not another method call on them). The parent link comes from our
 *  own {@see ChainParentVisitor}, registered via the rich-parser visitor tag,
 *  because PHPStan 2.x no longer connects nodes by default. From the tail we
 *  walk down the receivers and collect method names. If the deepest receiver is
 *  `entityQuery()` (a static call on \Drupal) or `getQuery()` (called on an
 *  entity storage), and the chain does not contain `accessCheck`, we report.
 *
 *  Queries are also often assigned to a variable and extended across later
 *  statements. For a tail `->execute()` whose receiver is such a variable, we
 *  walk the enclosing function/method and track the last assignment from
 *  `entityQuery()` / `getQuery()`. If no `accessCheck()` was seen on that
 *  variable after that assignment, we report on the `->execute()` call.
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
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->enabled) {
            return [];
        }

        // Only consider the chain tail — when this node is itself the receiver
        // of an outer call we let that outer call do the reporting instead.
        $parent = $node->getAttribute(ChainParentVisitor::ATTRIBUTE);
        if ($parent instanceof MethodCall && $parent->var === $node) {
            return [];
        }

        $methods = $this->collectChainMethodNames($node);
        $root = $this->chainRoot($node);

        if ($this->isEntityQueryRoot($root, $methods)) {
            if (in_array('accessCheck', $methods, true)) {
                return [];
            }

            // Bare getQuery() / an assigned fluent prefix without execute() is
            // the start of a multi-statement query. Report on ->execute() later
            // instead of treating the constructor call as a finished chain.
            if (!$this->shouldDeferToVariableTracking($node, $methods)) {
                return [$this->missingAccessCheckError($node)];
            }
        }

        if (
            $root instanceof Variable
            && is_string($root->name)
            && in_array('execute', $methods, true)
            && !in_array('accessCheck', $methods, true)
            && $this->assignedQueryMissingAccessCheck($node, $root->name)
        ) {
            return [$this->missingAccessCheckError($node)];
        }

        return [];
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
        while (true) {
            if ($current->name instanceof Identifier) {
                $names[] = $current->name->toString();
            }
            $receiver = $current->var;
            if (!$receiver instanceof MethodCall) {
                break;
            }
            $current = $receiver;
        }
        return $names;
    }

    /**
     * Return the deepest receiver of the method chain (the actual chain root).
     */
    private function chainRoot(MethodCall $node): Node
    {
        $current = $node;
        while ($current->var instanceof MethodCall) {
            $current = $current->var;
        }
        // $current is a MethodCall whose ->var is *not* another MethodCall.
        return $current->var;
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

    /**
     * True when this tail is query construction that may still get accessCheck()
     * on a later statement against the assigned variable.
     *
     * @param list<string> $methods
     */
    private function shouldDeferToVariableTracking(MethodCall $node, array $methods): bool
    {
        if ($methods === ['getQuery']) {
            return true;
        }
        if (in_array('execute', $methods, true)) {
            return false;
        }
        $parent = $node->getAttribute(ChainParentVisitor::ATTRIBUTE);

        return $parent instanceof Assign
            && $parent->expr === $node
            && $parent->var instanceof Variable;
    }

    /**
     * Whether $varName was assigned from entityQuery()/getQuery() in this
     * function and never received accessCheck() before $execute.
     */
    private function assignedQueryMissingAccessCheck(MethodCall $execute, string $varName): bool
    {
        $function = $this->enclosingFunction($execute);
        if ($function === null) {
            return false;
        }

        $isQuery = false;
        $hasAccessCheck = false;
        $shouldReport = false;

        $this->walkChildNodes(
            $function,
            function (Node $node) use ($execute, $varName, &$isQuery, &$hasAccessCheck, &$shouldReport): bool {
                if ($node instanceof Assign) {
                    $this->applyAssignment($node, $varName, $isQuery, $hasAccessCheck);
                }

                if (!$node instanceof MethodCall) {
                    return false;
                }

                if ($this->isAccessCheckOnVariable($node, $varName)) {
                    $hasAccessCheck = true;
                }

                if ($node !== $execute) {
                    return false;
                }

                $shouldReport = $isQuery && !$hasAccessCheck;

                return true;
            },
        );

        return $shouldReport;
    }

    /**
     * Update query-tracking state for an assignment to $varName.
     */
    private function applyAssignment(
        Assign $assign,
        string $varName,
        bool &$isQuery,
        bool &$hasAccessCheck,
    ): void {
        if (!$assign->var instanceof Variable || !is_string($assign->var->name) || $assign->var->name !== $varName) {
            return;
        }

        if ($this->exprIsEntityQuery($assign->expr)) {
            $isQuery = true;
            $hasAccessCheck = $this->exprChainHasAccessCheck($assign->expr);
            return;
        }

        if ($assign->expr instanceof MethodCall && $this->chainRootVariableName($assign->expr) === $varName) {
            if ($this->exprChainHasAccessCheck($assign->expr)) {
                $hasAccessCheck = true;
            }
            return;
        }

        $isQuery = false;
        $hasAccessCheck = false;
    }

    private function exprIsEntityQuery(Node $expr): bool
    {
        if ($expr instanceof StaticCall
            && $expr->name instanceof Identifier
            && $expr->name->toString() === 'entityQuery'
        ) {
            return true;
        }

        if (!$expr instanceof MethodCall) {
            return false;
        }

        return $this->isEntityQueryRoot($this->chainRoot($expr), $this->collectChainMethodNames($expr));
    }

    private function exprChainHasAccessCheck(Node $expr): bool
    {
        return $expr instanceof MethodCall
            && in_array('accessCheck', $this->collectChainMethodNames($expr), true);
    }

    private function isAccessCheckOnVariable(MethodCall $node, string $varName): bool
    {
        return $node->name instanceof Identifier
            && $node->name->toString() === 'accessCheck'
            && $this->chainRootVariableName($node) === $varName;
    }

    private function chainRootVariableName(MethodCall $node): ?string
    {
        $root = $this->chainRoot($node);
        if ($root instanceof Variable && is_string($root->name)) {
            return $root->name;
        }

        return null;
    }

    private function enclosingFunction(Node $node): ?FunctionLike
    {
        $current = $node;
        while (true) {
            $parent = $current->getAttribute(ChainParentVisitor::ATTRIBUTE);
            if (!$parent instanceof Node) {
                return null;
            }
            if ($parent instanceof FunctionLike) {
                return $parent;
            }
            $current = $parent;
        }
    }

    /**
     * Pre-order walk of $node's descendants. Nested functions are skipped.
     *
     * @param callable(Node): bool $enter Return true to stop walking.
     */
    private function walkChildNodes(Node $node, callable $enter): bool
    {
        foreach ($node->getSubNodeNames() as $name) {
            /** @var mixed $sub */
            $sub = $node->{$name};
            if ($sub instanceof Node) {
                if ($this->visit($sub, $enter)) {
                    return true;
                }
                continue;
            }
            if (!is_array($sub)) {
                continue;
            }
            foreach ($sub as $item) {
                if ($item instanceof Node && $this->visit($item, $enter)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param callable(Node): bool $enter
     */
    private function visit(Node $node, callable $enter): bool
    {
        if ($node instanceof FunctionLike) {
            return false;
        }
        if ($enter($node)) {
            return true;
        }

        return $this->walkChildNodes($node, $enter);
    }

    private function missingAccessCheckError(MethodCall $node): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            'Entity query is missing an explicit ->accessCheck(TRUE|FALSE). Drupal 10+ requires an explicit access-check decision on every entity query.',
        )
            ->identifier('drupalRules.noEntityQueryWithoutAccessCheck')
            ->line($node->getStartLine())
            ->build();
    }
}
