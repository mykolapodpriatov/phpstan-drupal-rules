<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoServiceLocatorInDIClassRule;

use Drupal\Core\Controller\ControllerBase;

/**
 * A controller extending ControllerBase that mixes DI with the static container.
 */
final class BadController extends ControllerBase
{

    public function build(): array
    {
        // Bad: extends ControllerBase but still resolves services via locator.
        $config = \Drupal::config('system.site');

        // Bad: same here.
        $messenger = \Drupal::service('messenger');

        return [
            '#markup' => (string) $config->get('name'),
        ];
    }

}
