<?php

declare(strict_types=1);

namespace Drupal\Core\Access;

/**
 * Test-only stub of Drupal\Core\Access\AccessResult.
 *
 * Only the members the fixtures reference are declared. The real class lives in
 * Drupal core; here we just need enough for the fixture files to parse.
 */
abstract class AccessResult
{
    /**
     * @param object $account
     * @param string $permission
     */
    public static function allowedIfHasPermission($account, $permission): self
    {
    }
}
