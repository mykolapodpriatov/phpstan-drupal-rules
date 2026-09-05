<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoUnescapedMarkupRule;

final class UnsafeBuilder
{

    public function build(string $bio, string $title): array
    {
        return [
            // ✗ A field value straight into #markup.
            '#markup' => $bio,
        ];
    }

    public function interpolated(string $name): array
    {
        return [
            // ✗ An interpolated string: the literal parts are safe, the
            // variable is not, and that is exactly the bug.
            '#markup' => "Hello {$name}",
        ];
    }

    public function concatenated(string $name): array
    {
        return [
            // ✗ Concatenation of a literal and a variable.
            '#markup' => '<p>' . $name . '</p>',
        ];
    }

    public function prefixAndSuffix(string $open, string $close): array
    {
        return [
            '#type' => 'container',
            // ✗ #prefix and #suffix are filtered the same way.
            '#prefix' => $open,
            '#suffix' => $close,
        ];
    }

    public function nested(string $bio): array
    {
        return [
            '#type' => 'container',
            'child' => [
                // ✗ Nested render arrays are the common shape, not the top level.
                '#markup' => $bio,
            ],
        ];
    }

    public function unknownCall(string $bio): array
    {
        return [
            // ✗ A helper that is not on the trusted list.
            '#markup' => $this->decorate($bio),
        ];
    }

    private function decorate(string $value): string
    {
        return $value;
    }
}
