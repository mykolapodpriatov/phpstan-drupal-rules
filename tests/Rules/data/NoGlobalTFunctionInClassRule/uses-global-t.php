<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoGlobalTFunctionInClassRule;

/**
 * A class that reaches for the procedural t() instead of $this->t().
 */
final class GlobalTController
{

    public function build(): array
    {
        // Bad: global t() called from inside a class.
        $title = t('Welcome');

        return [
            // Bad: same problem in a fully-qualified form.
            '#title' => \t('Dashboard'),
            '#markup' => $title,
        ];
    }

}
