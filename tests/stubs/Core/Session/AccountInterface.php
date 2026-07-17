<?php

declare(strict_types=1);

namespace Drupal\Core\Session;

/**
 * Test-only stub of Drupal\Core\Session\AccountInterface.
 *
 * Only the `hasPermission()` member the fixtures reference is declared.
 */
interface AccountInterface
{
    public function hasPermission(string $permission): bool;
}
