<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\ProperPermissionConstantsRule;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

final class NodePermissions
{
    public const ADMINISTER = 'administer nodes';
}

function good_permissions(AccountInterface $account): void
{
    // Good: the permission is promoted to a class constant.
    $account->hasPermission(NodePermissions::ADMINISTER);

    // Good: class constant passed to AccessResult::allowedIfHasPermission().
    AccessResult::allowedIfHasPermission($account, NodePermissions::ADMINISTER);

    // Good: 'access content' is on the configured allowlist of stable strings.
    $account->hasPermission('access content');
}
