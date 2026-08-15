<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoEntityQueryWithoutAccessCheckRule;

function multi_statement_missing_access_check(?string $type): void
{
    // Bad: query assigned then extended across statements, never accessCheck.
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    if ($type) {
        $query->condition('type', $type);
    }
    $ids = $query->execute();

    // Bad: getQuery() assigned, then executed without accessCheck.
    $storage = \Drupal::entityTypeManager()->getStorage('user');
    $userQuery = $storage->getQuery();
    $userQuery->condition('status', 1);
    $uids = $userQuery->execute();
}
