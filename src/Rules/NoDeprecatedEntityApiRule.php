<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Catch usage of removed or deprecated Drupal entity / utility APIs.
 *
 * mglaman/phpstan-drupal flags deprecation via PhpDoc tags on the real Drupal
 * codebase, but in practice many shops vendor older glue code or copy-paste
 * snippets from D7 era. This rule maintains a hand-curated table of the
 * worst offenders and points to the modern replacement.
 *
 * The rule visits two node types:
 *  - global function calls (FuncCall): `entity_load()`, `node_load()`, ...
 *  - static calls (StaticCall): `\Drupal::entityManager()`.
 *
 * @implements Rule<Node>
 */
final class NoDeprecatedEntityApiRule implements Rule
{
    /**
     * Map of deprecated global functions => replacement guidance.
     *
     * @var array<string, string>
     */
    private const DEPRECATED_FUNCTIONS = [
        'entity_load'                => 'Use the entity_type.manager service: \Drupal::entityTypeManager()->getStorage($entity_type)->load($id).',
        'entity_load_multiple'       => 'Use the entity_type.manager service: \Drupal::entityTypeManager()->getStorage($entity_type)->loadMultiple($ids).',
        'entity_get_form_display'    => 'Use \Drupal::service(\'entity_display.repository\')->getFormDisplay() instead.',
        'entity_get_display'         => 'Use \Drupal::service(\'entity_display.repository\')->getViewDisplay() instead.',
        'node_load'                  => 'Use \Drupal::entityTypeManager()->getStorage(\'node\')->load($nid).',
        'node_load_multiple'         => 'Use \Drupal::entityTypeManager()->getStorage(\'node\')->loadMultiple($nids).',
        'user_load'                  => 'Use \Drupal::entityTypeManager()->getStorage(\'user\')->load($uid).',
        'user_load_multiple'         => 'Use \Drupal::entityTypeManager()->getStorage(\'user\')->loadMultiple($uids).',
        'taxonomy_term_load'         => 'Use \Drupal::entityTypeManager()->getStorage(\'taxonomy_term\')->load($tid).',
        'file_load'                  => 'Use \Drupal::entityTypeManager()->getStorage(\'file\')->load($fid).',
        'drupal_render'              => 'Use the renderer service: \Drupal::service(\'renderer\')->render($build) — or better, return the render array.',
        'drupal_set_message'         => 'Use the messenger service: \Drupal::messenger()->addStatus() / addWarning() / addError().',
        'format_string'              => 'Use \Drupal\\Component\\Render\\FormattableMarkup or t() for translatable strings.',
    ];

    /**
     * Map of deprecated \Drupal::method() calls => replacement.
     *
     * @var array<string, string>
     */
    private const DEPRECATED_DRUPAL_METHODS = [
        'entityManager' => '\Drupal::entityManager() is removed. Use \Drupal::entityTypeManager() or inject entity_type.manager.',
    ];

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
            return $this->processFunctionCall($node);
        }

        if ($node instanceof StaticCall) {
            return $this->processStaticCall($node);
        }

        return [];
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processFunctionCall(FuncCall $node): array
    {
        if (!$node->name instanceof Name) {
            return [];
        }

        $name = ltrim($node->name->toString(), '\\');
        if (!isset(self::DEPRECATED_FUNCTIONS[$name])) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Function %s() is deprecated/removed in Drupal 10.3+. %s',
                $name,
                self::DEPRECATED_FUNCTIONS[$name],
            ))
                ->identifier('drupalRules.noDeprecatedEntityApi')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processStaticCall(StaticCall $node): array
    {
        if (!$node->class instanceof Name) {
            return [];
        }
        $class = ltrim($node->class->toString(), '\\');
        if ($class !== 'Drupal') {
            return [];
        }
        if (!$node->name instanceof Identifier) {
            return [];
        }
        $method = $node->name->toString();
        if (!isset(self::DEPRECATED_DRUPAL_METHODS[$method])) {
            return [];
        }

        return [
            RuleErrorBuilder::message(self::DEPRECATED_DRUPAL_METHODS[$method])
                ->identifier('drupalRules.noDeprecatedEntityApi')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
