<?php

declare(strict_types=1);

namespace Drupal\Component\Utility;

/**
 * Test-only stub of Drupal\Component\Utility\Xss.
 *
 * @param list<string> $allowed_tags
 */
final class Xss
{
    /**
     * @param list<string>|null $allowed_tags
     */
    public static function filter(string $string, ?array $allowed_tags = null): string
    {
        return $string;
    }

    public static function filterAdmin(string $string): string
    {
        return $string;
    }
}
