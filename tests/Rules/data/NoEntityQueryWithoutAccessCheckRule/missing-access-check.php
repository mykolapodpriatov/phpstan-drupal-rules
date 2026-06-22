<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoEntityQueryWithoutAccessCheckRule;

function bad_queries(): void
{
    // Bad: \Drupal::entityQuery() chain without accessCheck.
    $ids = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->execute();

    // Bad: $storage->getQuery() chain without accessCheck.
    $storage = \Drupal::entityTypeManager()->getStorage('user');
    $uids = $storage->getQuery()
        ->condition('status', 1)
        ->execute();
}
