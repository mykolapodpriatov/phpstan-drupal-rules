<?php

declare(strict_types=1);

namespace YourOrg\PhpStanDrupalRules\Tests\Fixtures\NoServiceLocatorInDIClassRule;

use Drupal\Core\Controller\ControllerBase;

/**
 * A controller that exclusively uses constructor injection — no locator calls.
 */
final class CleanController extends ControllerBase
{

    /**
     * @param object $messenger Injected messenger (stub).
     * @param object $configFactory Injected config factory (stub).
     */
    public function __construct(
        private readonly object $messenger,
        private readonly object $configFactory,
    ) {
    }

    public function build(): array
    {
        return [
            '#markup' => 'Hello world',
        ];
    }

}
