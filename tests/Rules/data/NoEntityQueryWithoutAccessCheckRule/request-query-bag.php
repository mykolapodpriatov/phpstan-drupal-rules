<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoEntityQueryWithoutAccessCheckRule;

use Symfony\Component\HttpFoundation\Request;

function read_page(Request $request): mixed
{
    // The query bag, not an entity query. get() is not a query-builder method.
    return $request->getQuery()->get('page');
}
