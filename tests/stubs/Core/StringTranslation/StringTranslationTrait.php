<?php

declare(strict_types=1);

namespace Drupal\Core\StringTranslation;

/**
 * Test-only stub of Drupal\Core\StringTranslation\StringTranslationTrait.
 *
 * Only the `t()` member the fixtures reference is declared, so a class can
 * translate strings through `$this->t()` without a real Drupal install.
 */
trait StringTranslationTrait
{
    /**
     * @param array<string, mixed> $args
     * @param array<string, mixed> $options
     */
    protected function t(string $string, array $args = [], array $options = []): string
    {
        return $string;
    }
}
