<?php

declare(strict_types=1);

namespace Drupal\Component\Render;

/**
 * Test-only stub of Drupal\Component\Render\MarkupInterface.
 *
 * NoUnescapedMarkupRule treats any value of this type as already escaped, so
 * the interface has to exist for that branch to be reachable in a fixture.
 */
interface MarkupInterface extends \Stringable
{
}
