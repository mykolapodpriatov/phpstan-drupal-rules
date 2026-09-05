<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Rules;

use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Forbid a non-literal value in `#markup`, `#prefix` and `#suffix`.
 *
 * These three render-array keys are filtered with `Xss::filterAdmin()`, not
 * `Xss::filter()`, so the admin tag whitelist applies: `<a href>`, `<img src>`,
 * `<style>`, `<form>` and friends survive. Putting user-editable content there
 * hands anyone who can edit that field the ability to land markup on the page.
 *
 * The safe forms are a deliberate choice the author has to make: `#plain_text`
 * when the value is text, or an explicitly escaped / `Markup::create()`d value
 * when it genuinely is markup. This rule exists to force that choice rather
 * than let it happen by default.
 *
 * A value is treated as safe when it is:
 *  - a string literal, or a concatenation of safe parts;
 *  - a call to one of the configured sanitisers (`safeMarkupCallables`);
 *  - already typed as `Drupal\Component\Render\MarkupInterface`, which covers
 *    `$this->t()` and anything else that returns translated/escaped markup.
 *
 * An interpolated string ("Hello {$name}") is NOT safe: the literal parts are,
 * the interpolated ones are not, and that is the exact shape of the bug.
 *
 * An array carrying `#allowed_tags` alongside the key is left alone. That
 * property only exists because the author thought about the tag whitelist.
 *
 * The rule matches on `Array_` rather than `ArrayItem` so it can see the
 * sibling keys of the array it is looking at; PHPStan 2.x no longer links
 * nodes to their parents.
 *
 * @implements Rule<Array_>
 */
final class NoUnescapedMarkupRule implements Rule
{
    /**
     * Render-array keys whose value is passed through Xss::filterAdmin().
     */
    private const FILTERED_KEYS = ['#markup', '#prefix', '#suffix'];

    /**
     * Presence of this key means the author has considered the tag whitelist.
     */
    private const ESCAPE_HATCH_KEY = '#allowed_tags';

    /**
     * Values of this type are already escaped by the time they get here.
     */
    private const MARKUP_INTERFACE = 'Drupal\Component\Render\MarkupInterface';

    /**
     * @param list<string> $safeMarkupCallables Sanitisers whose return value is
     *   trusted. Three forms: `funcName` for a global function,
     *   `Fully\Qualified\Class::method` for a static call, and `->method` for an
     *   instance method matched by name alone (`$this->t()` and friends, which
     *   cannot be resolved to a class without type information).
     * @param bool $enabled Master switch.
     */
    public function __construct(
        private readonly array $safeMarkupCallables = [],
        private readonly bool $enabled = true,
    ) {
    }

    public function getNodeType(): string
    {
        return Array_::class;
    }

    /**
     * @param Array_ $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->enabled) {
            return [];
        }

        if ($this->hasKey($node, self::ESCAPE_HATCH_KEY)) {
            return [];
        }

        $errors = [];
        foreach ($node->items as $item) {
            if (!$item instanceof ArrayItem || $item->key === null) {
                continue;
            }
            $key = $this->literalKey($item->key);
            if ($key === null || !in_array($key, self::FILTERED_KEYS, true)) {
                continue;
            }
            if ($this->isSafe($item->value, $scope)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Render array key %s is given a value that is not a literal and not explicitly escaped. '
                . 'It is filtered with Xss::filterAdmin(), which permits <a>, <img>, <style> and <form>, so '
                . 'editable content placed here can inject markup. Use #plain_text for text, or escape the '
                . 'value explicitly (Xss::filter(), Html::escape(), Markup::create()) when it really is markup.',
                $key,
            ))
                ->identifier('drupalRules.noUnescapedMarkup')
                ->line($item->value->getStartLine())
                ->build();
        }

        return $errors;
    }

    /**
     * Does the array literally declare $key?
     */
    private function hasKey(Array_ $node, string $key): bool
    {
        foreach ($node->items as $item) {
            if ($item instanceof ArrayItem && $item->key !== null && $this->literalKey($item->key) === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * The literal string value of an array key, or null when it is dynamic.
     */
    private function literalKey(Expr $key): ?string
    {
        return $key instanceof String_ ? $key->value : null;
    }

    /**
     * Is $value safe to hand to a filtered render-array key?
     */
    private function isSafe(Expr $value, Scope $scope): bool
    {
        if ($value instanceof String_) {
            return true;
        }

        if ($value instanceof Concat) {
            return $this->isSafe($value->left, $scope) && $this->isSafe($value->right, $scope);
        }

        if ($this->isSafeCall($value)) {
            return true;
        }

        // Anything the type system already knows is markup has been escaped by
        // whoever produced it: TranslatableMarkup, Markup, FormattableMarkup.
        return (new ObjectType(self::MARKUP_INTERFACE))->isSuperTypeOf($scope->getType($value))->yes();
    }

    /**
     * Is $value a call to one of the configured sanitisers?
     */
    private function isSafeCall(Expr $value): bool
    {
        if ($value instanceof FuncCall && $value->name instanceof Name) {
            return in_array(ltrim($value->name->toString(), '\\'), $this->safeMarkupCallables, true);
        }

        if ($value instanceof StaticCall && $value->class instanceof Name && $value->name instanceof Identifier) {
            $target = ltrim($value->class->toString(), '\\') . '::' . $value->name->toString();

            return in_array($target, $this->safeMarkupCallables, true);
        }

        if ($value instanceof MethodCall && $value->name instanceof Identifier) {
            return in_array('->' . $value->name->toString(), $this->safeMarkupCallables, true);
        }

        return false;
    }
}
