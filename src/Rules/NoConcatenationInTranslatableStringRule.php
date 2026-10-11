<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\InterpolatedString;
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
 * method call is a `Concat` expression or an interpolated string
 * (`"Hello $name"`, `"Hello {$name}"`). Both are invisible to the extractor.
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
     * Report when the first argument is not a static source string.
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

        $value = $args[0]->value;
        $isConcat = $value instanceof Concat;
        $isInterpolated = $value instanceof InterpolatedString;
        if (!$isConcat && !$isInterpolated) {
            return [];
        }

        $problem = $isInterpolated ? 'An interpolated string' : 'Concatenation';

        return [
            RuleErrorBuilder::message(sprintf(
                '%s in the first argument of %s breaks translation string extraction. Use a single source string with placeholders (@var, %%var, :var) and pass the values in the second argument.',
                $problem,
                $context,
            ))
                ->identifier('drupalRules.noConcatenationInTranslatableString')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
