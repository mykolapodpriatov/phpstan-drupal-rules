<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoEntityQueryWithoutAccessCheckRule;

function query_variable_reassigned(): void
{
    // Reused for an unrelated purpose — must not false-positive.
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query = new \ArrayIterator([1, 2]);
    $count = $query->count();

    // Reassigned, then execute() on the unrelated object — still not a query.
    $q2 = \Drupal::entityQuery('user');
    $q2 = new class {
        /**
         * @return list<int>
         */
        public function execute(): array
        {
            return [];
        }
    };
    $ids = $q2->execute();
}
