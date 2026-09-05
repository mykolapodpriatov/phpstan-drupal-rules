<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Rules;

use MykolaPodpriatov\PhpStanDrupalRules\Rules\NoUnescapedMarkupRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoUnescapedMarkupRule>
 */
final class NoUnescapedMarkupRuleTest extends RuleTestCase
{

    /**
     * The defaults shipped in extension.neon.
     *
     * @var list<string>
     */
    private const DEFAULT_SAFE_CALLABLES = [
        't',
        'check_markup',
        '->t',
        '->formatPlural',
        'Drupal\Component\Utility\Xss::filter',
        'Drupal\Component\Utility\Xss::filterAdmin',
        'Drupal\Component\Utility\Html::escape',
        'Drupal\Core\Render\Markup::create',
        'Drupal\Core\Render\Markup::fromObject',
    ];

    /**
     * @var list<string>
     */
    private array $safeCallables = self::DEFAULT_SAFE_CALLABLES;

    protected function getRule(): Rule
    {
        return new NoUnescapedMarkupRule(
            safeMarkupCallables: $this->safeCallables,
            enabled: true,
        );
    }

    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        // Registers the global t() / check_markup() stubs the fixtures call.
        return [__DIR__ . '/../stub-functions.neon'];
    }

    /**
     * The message is long and identical on every hit; building it here keeps the
     * expectations readable and stops a wording change from being edited in
     * seven places.
     */
    private static function message(string $key): string
    {
        return sprintf(
            'Render array key %s is given a value that is not a literal and not explicitly escaped. '
            . 'It is filtered with Xss::filterAdmin(), which permits <a>, <img>, <style> and <form>, so '
            . 'editable content placed here can inject markup. Use #plain_text for text, or escape the '
            . 'value explicitly (Xss::filter(), Html::escape(), Markup::create()) when it really is markup.',
            $key,
        );
    }

    public function testUnescapedValuesAreFlagged(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoUnescapedMarkupRule/unescaped-markup.php'],
            [
                [self::message('#markup'), 14],
                [self::message('#markup'), 23],
                [self::message('#markup'), 31],
                [self::message('#prefix'), 40],
                [self::message('#suffix'), 41],
                [self::message('#markup'), 51],
                [self::message('#markup'), 60],
            ],
        );
    }

    public function testLiteralsEscapesAndTranslationsAreSilent(): void
    {
        $this->analyse([__DIR__ . '/data/NoUnescapedMarkupRule/escaped-markup.php'], []);
    }

    public function testAProjectSanitiserCanBeTrusted(): void
    {
        // The same fixture, with the project's own wrapper added to the list:
        // the previously-flagged `$this->decorate($bio)` call goes quiet and
        // nothing else changes.
        $this->safeCallables = [...self::DEFAULT_SAFE_CALLABLES, '->decorate'];

        $this->analyse(
            [__DIR__ . '/data/NoUnescapedMarkupRule/unescaped-markup.php'],
            [
                [self::message('#markup'), 14],
                [self::message('#markup'), 23],
                [self::message('#markup'), 31],
                [self::message('#prefix'), 40],
                [self::message('#suffix'), 41],
                [self::message('#markup'), 51],
            ],
        );
    }

    public function testDisabledRuleReportsNothing(): void
    {
        $rule = new NoUnescapedMarkupRule(
            safeMarkupCallables: self::DEFAULT_SAFE_CALLABLES,
            enabled: false,
        );

        self::assertSame([], $rule->processNode(
            new \PhpParser\Node\Expr\Array_([
                new \PhpParser\Node\ArrayItem(
                    new \PhpParser\Node\Expr\Variable('bio'),
                    new \PhpParser\Node\Scalar\String_('#markup'),
                ),
            ]),
            $this->createMock(\PHPStan\Analyser\Scope::class),
        ));
    }
}
