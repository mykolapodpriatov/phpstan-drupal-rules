<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoDeprecatedEntityApiRule;

function modern_examples(int $nid, int $uid): void
{
    // Good: entity_type.manager via the service container.
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

    // Good: messenger service.
    \Drupal::messenger()->addStatus('Saved.');

    // Good: renderer service.
    $renderer = \Drupal::service('renderer');
    $html = $renderer->render([]);
}
