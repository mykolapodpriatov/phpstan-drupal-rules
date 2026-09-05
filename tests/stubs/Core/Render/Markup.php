<?php

declare(strict_types=1);

namespace Drupal\Core\Render;

use Drupal\Component\Render\MarkupInterface;

/**
 * Test-only stub of Drupal\Core\Render\Markup.
 *
 * Only the factory methods the fixtures call are declared.
 */
final class Markup implements MarkupInterface
{
    public static function create(string $string): self
    {
        return new self();
    }

    public static function fromObject(object $object): self
    {
        return new self();
    }

    public function __toString(): string
    {
        return '';
    }
}
