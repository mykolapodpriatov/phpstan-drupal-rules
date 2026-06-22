<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\NodeVisitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Records each node's parent under the `chainParent` attribute.
 *
 * PHPStan 2.x no longer connects nodes by default, and reading the built-in
 * `parent`/`next`/`previous` attributes is reported as a mistake. Rules that
 * must reason about a node's position in a method chain (e.g. "only act on the
 * tail of a fluent call") therefore need to supply their own parent links.
 *
 * The visitor is registered through the `phpstan.parser.richParserNodeVisitor`
 * tag so the attribute is set during parsing and survives into rule analysis.
 * A custom attribute name is used on purpose: it keeps the dependency explicit
 * and avoids the discouraged built-in `parent` attribute entirely.
 *
 * @see https://phpstan.org/blog/preprocessing-ast-for-custom-rules
 */
final class ChainParentVisitor extends NodeVisitorAbstract
{
    public const ATTRIBUTE = 'chainParent';

    /**
     * @var list<Node>
     */
    private array $stack = [];

    /**
     * @param array<Node> $nodes
     */
    public function beforeTraverse(array $nodes): ?array
    {
        $this->stack = [];

        return null;
    }

    public function enterNode(Node $node): ?Node
    {
        if ($this->stack !== []) {
            $node->setAttribute(self::ATTRIBUTE, $this->stack[count($this->stack) - 1]);
        }
        $this->stack[] = $node;

        return null;
    }

    public function leaveNode(Node $node): ?Node
    {
        array_pop($this->stack);

        return null;
    }
}
