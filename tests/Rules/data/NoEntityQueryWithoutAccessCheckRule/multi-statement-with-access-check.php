<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoEntityQueryWithoutAccessCheckRule;

function multi_statement_with_access_check(?string $type): void
{
    // Good: accessCheck on a later statement, before execute.
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    if ($type) {
        $query->condition('type', $type);
    }
    $query->accessCheck(TRUE);
    $ids = $query->execute();

    // Good: accessCheck immediately after getQuery(), then more statements.
    $storage = \Drupal::entityTypeManager()->getStorage('user');
    $userQuery = $storage->getQuery();
    $userQuery->accessCheck(FALSE);
    $userQuery->condition('status', 1);
    $uids = $userQuery->execute();

    // Good: accessCheck lives on the execute statement itself.
    $q2 = \Drupal::entityQuery('node');
    $q2->condition('status', 1);
    $ids2 = $q2->accessCheck(TRUE)->execute();

    // Good: accessCheck already present on the assignment chain.
    $q3 = \Drupal::entityQuery('node')->accessCheck(TRUE);
    $q3->condition('status', 1);
    $ids3 = $q3->execute();
}
