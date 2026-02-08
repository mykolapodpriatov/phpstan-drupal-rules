<?php

declare(strict_types=1);

namespace YourOrg\PhpStanDrupalRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbid \Drupal::service(...) and friends inside classes that are wired with DI.
 *
 * A class is considered "DI-aware" when:
 *  - it declares a non-empty `__construct()` (a constructor with at least one
 *    parameter — a strong proxy for "this class already takes its collaborators
 *    through the container"), OR
 *  - it extends one of the configured Drupal base classes that come with
 *    DI plumbing (ControllerBase, FormBase, ConfigFormBase, etc.).
 *
 * @implements Rule<StaticCall>
 */
final class NoServiceLocatorInDIClassRule implements Rule
{
    /**
     * @param list<string>      $diBaseClasses        Fully qualified class names that imply DI.
     * @param list<string>      $forbiddenLocatorMethods Static methods on \Drupal that count as service locator calls.
     * @param bool              $enabled              Master switch.
     */
    public function __construct(
        private readonly array $diBaseClasses,
        private readonly array $forbiddenLocatorMethods,
        private readonly bool $enabled = true,
    ) {
    }

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @param StaticCall $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->enabled) {
            return [];
        }

        if (!$node->class instanceof Name) {
            return [];
        }

        // We only care about \Drupal::something() calls.
        $calledClass = ltrim($node->class->toString(), '\\');
        if ($calledClass !== 'Drupal') {
            return [];
        }

        if (!$node->name instanceof Identifier) {
            return [];
        }

        $methodName = $node->name->toString();
        if (!in_array($methodName, $this->forbiddenLocatorMethods, true)) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            // Not inside a class body — likely a top-level function or a hook.
            return [];
        }

        if (!$this->classIsDIAware($classReflection)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Use of service locator \Drupal::%s() inside DI-aware class %s. Inject the service via constructor.',
                $methodName,
                $classReflection->getName(),
            ))
                ->identifier('drupalRules.noServiceLocatorInDIClass')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    private function classIsDIAware(ClassReflection $class): bool
    {
        // 1) Has a constructor with at least one parameter — strong DI signal.
        if ($class->hasConstructor()) {
            $constructor = $class->getConstructor();
            $variants = $constructor->getVariants();
            if ($variants !== [] && count($variants[0]->getParameters()) > 0) {
                return true;
            }
        }

        // 2) Extends one of the configured DI-aware base classes.
        foreach ($this->diBaseClasses as $baseClass) {
            if ($class->is($baseClass)) {
                return true;
            }
        }

        return false;
    }
}
