<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\ProperPermissionConstantsRule;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

function bad_permissions(AccountInterface $account): void
{
    // Bad: literal permission string on ->hasPermission().
    $account->hasPermission('administer nodes');

    // Bad: literal permission string on AccessResult::allowedIfHasPermission().
    AccessResult::allowedIfHasPermission($account, 'administer users');
}
