<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Guards against wiring drift between the rule classes and their registration.
 *
 * Every `src/Rules/*Rule.php` class must be:
 *  - registered as a PHPStan rule service in `extension.neon`,
 *  - given a boolean `parameters.drupalRules.<toggle>` default,
 *  - declared in the `parametersSchema.drupalRules` structure, and
 *  - documented under a numbered `### N.` heading in `README.md`.
 *
 * The `<toggle>` is derived from the class name by dropping the trailing `Rule`
 * suffix and lower-casing the first letter, matching the existing convention
 * (`NoServiceLocatorInDIClassRule` -> `noServiceLocatorInDIClass`). This runs
 * under the existing `composer test` (PHPUnit) with no new dependencies.
 */
final class ExtensionConsistencyTest extends TestCase
{

    private const RULES_NAMESPACE = 'MykolaPodpriatov\\PhpStanDrupalRules\\Rules\\';

    private static function rootDir(): string
    {
        return dirname(__DIR__);
    }

    private static function extensionNeon(): string
    {
        return (string) file_get_contents(self::rootDir() . '/extension.neon');
    }

    private static function readme(): string
    {
        return (string) file_get_contents(self::rootDir() . '/README.md');
    }

    /**
     * The toggle key for a rule class name (drop `Rule`, lower-case first char).
     */
    private static function toggleFor(string $className): string
    {
        return lcfirst(substr($className, 0, -strlen('Rule')));
    }

    /**
     * @return list<array{string}> Each rule's short class name.
     */
    public static function ruleClassProvider(): array
    {
        $files = glob(self::rootDir() . '/src/Rules/*Rule.php');
        if ($files === false) {
            return [];
        }

        $cases = [];
        foreach ($files as $file) {
            $cases[] = [basename($file, '.php')];
        }

        return $cases;
    }

    public function testAtLeastOneRuleIsDiscovered(): void
    {
        self::assertNotEmpty(
            self::ruleClassProvider(),
            'Expected to find at least one src/Rules/*Rule.php class.',
        );
    }

    #[DataProvider('ruleClassProvider')]
    public function testRuleIsRegisteredAsService(string $className): void
    {
        $needle = 'class: ' . self::RULES_NAMESPACE . $className;

        self::assertStringContainsString(
            $needle,
            self::extensionNeon(),
            sprintf('%s is not registered as a service in extension.neon (expected a "%s" line).', $className, $needle),
        );
    }

    #[DataProvider('ruleClassProvider')]
    public function testRuleHasBooleanToggleDefault(string $className): void
    {
        $toggle = self::toggleFor($className);

        self::assertMatchesRegularExpression(
            '/^\s+' . preg_quote($toggle, '/') . ':\s*(?:true|false)\s*$/m',
            self::extensionNeon(),
            sprintf('%s has no boolean "drupalRules.%s" default in extension.neon.', $className, $toggle),
        );
    }

    #[DataProvider('ruleClassProvider')]
    public function testRuleHasParametersSchemaEntry(string $className): void
    {
        $toggle = self::toggleFor($className);

        self::assertMatchesRegularExpression(
            '/^\s+' . preg_quote($toggle, '/') . ':\s*bool\(\)\s*$/m',
            self::extensionNeon(),
            sprintf('%s has no "%s: bool()" entry in the parametersSchema of extension.neon.', $className, $toggle),
        );
    }

    #[DataProvider('ruleClassProvider')]
    public function testRuleHasNumberedReadmeHeading(string $className): void
    {
        self::assertMatchesRegularExpression(
            '/^###\s+\d+\.\s+.*' . preg_quote($className, '/') . '/m',
            self::readme(),
            sprintf('%s is not documented under a numbered "### N." heading in README.md.', $className),
        );
    }

}
