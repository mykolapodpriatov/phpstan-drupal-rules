<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbid the global t() translation helper inside class methods.
 *
 * Calling the procedural `t()` from within a class is a long-standing Drupal
 * antipattern: it hides a global dependency, makes the class harder to unit
 * test, and sidesteps the DI-friendly translation plumbing. Inside a class the
 * string should be translated through `$this->t()` (provided by
 * `Drupal\Core\StringTranslation\StringTranslationTrait`) or through an injected
 * `TranslationInterface` service.
 *
 * The rule only fires for a global function call (a `FuncCall` named `t`) that
 * happens inside a class scope. A `$this->t()` call is a `MethodCall`, not a
 * `FuncCall`, so the recommended form passes through untouched, as does a bare
 * `t()` in procedural `.module` code where no `$this` is available.
 *
 * @implements Rule<FuncCall>
 */
final class NoGlobalTFunctionInClassRule implements Rule
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
        return FuncCall::class;
    }

    /**
     * @param FuncCall $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->enabled) {
            return [];
        }

        if (!$node->name instanceof Name) {
            return [];
        }

        // Matches both `t(...)` and the fully-qualified `\t(...)`.
        if (ltrim($node->name->toString(), '\\') !== 't') {
            return [];
        }

        // Outside a class body (procedural hooks, .module glue) the global t()
        // is the only option, so we leave it alone.
        if (!$scope->isInClass()) {
            return [];
        }

        // isInClass() guarantees a non-null class reflection here.
        $classReflection = $scope->getClassReflection();

        return [
            RuleErrorBuilder::message(sprintf(
                'Global t() is called inside class %s. Use $this->t() via StringTranslationTrait (or an injected translator) so the string stays translatable and the class stays testable.',
                $classReflection->getName(),
            ))
                ->identifier('drupalRules.noGlobalTFunctionInClass')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
