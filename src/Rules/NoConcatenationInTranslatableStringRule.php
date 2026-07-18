<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbid string concatenation in the first argument of t() / $this->t().
 *
 * The translation extractor treats the literal first argument of `t()` as the
 * source string. Concatenating (`'Hello ' . $name`) produces a run-time string
 * the extractor can never see, so the message silently drops out of every
 * `.po` file and can never be translated. The Drupal way is to keep a single
 * static source string with placeholders (`@name`, `%name`, `:url`) and pass
 * the dynamic values through the second argument.
 *
 * Mirrors the Drupal Coder sniff `Drupal.Semantics.FunctionT.Concat`. The rule
 * fires when the first argument of a global `t(...)` call or a `$this->t(...)`
 * method call is a `Concat` expression.
 *
 * @implements Rule<Node>
 */
final class NoConcatenationInTranslatableStringRule implements Rule
{
    /**
     * @param bool $enabled Master switch.
     */
    public function __construct(
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

        if ($node instanceof FuncCall) {
            if (!$node->name instanceof Name) {
                return [];
            }
            if (ltrim($node->name->toString(), '\\') !== 't') {
                return [];
            }

            return $this->checkFirstArgument($node, 't()');
        }

        if ($node instanceof MethodCall) {
            if (!$node->name instanceof Identifier) {
                return [];
            }
            if ($node->name->toString() !== 't') {
                return [];
            }

            return $this->checkFirstArgument($node, '$this->t()');
        }

        return [];
    }

    /**
     * Report when the first argument of the call is a concatenation.
     *
     * @param FuncCall|MethodCall $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function checkFirstArgument(Node $node, string $context): array
    {
        $args = $node->getArgs();
        if (!isset($args[0])) {
            return [];
        }

        if (!$args[0]->value instanceof Concat) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Concatenation in the first argument of %s breaks translation string extraction. Use a single source string with placeholders (@var, %%var, :var) and pass the values in the second argument.',
                $context,
            ))
                ->identifier('drupalRules.noConcatenationInTranslatableString')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
