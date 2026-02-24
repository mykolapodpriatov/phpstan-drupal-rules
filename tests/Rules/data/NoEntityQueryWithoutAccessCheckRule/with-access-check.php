<?php

declare(strict_types=1);

namespace YourOrg\PhpStanDrupalRules\Tests\Fixtures\NoEntityQueryWithoutAccessCheckRule;

function good_queries(): void
{
    // Good: accessCheck(TRUE) appears in the chain.
    $ids = \Drupal::entityQuery('node')
        ->accessCheck(TRUE)
        ->condition('status', 1)
        ->execute();

    // Good: explicit accessCheck(FALSE) — opt-out, but explicit.
    $storage = \Drupal::entityTypeManager()->getStorage('user');
    $uids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->execute();

    // Good: unrelated chain, must not be flagged.
    $sum = (new \ArrayIterator([1, 2]))->count();
}
