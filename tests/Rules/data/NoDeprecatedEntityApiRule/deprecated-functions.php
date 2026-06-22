<?php

declare(strict_types=1);

namespace MykolaPodpriatov\PhpStanDrupalRules\Tests\Fixtures\NoDeprecatedEntityApiRule;

function deprecated_examples(int $nid, int $uid, array $build): void
{
    // Bad: node_load is removed.
    $node = node_load($nid);

    // Bad: user_load is removed.
    $user = user_load($uid);

    // Bad: drupal_render replaced by the renderer service.
    $html = drupal_render($build);

    // Bad: drupal_set_message replaced by the messenger service.
    drupal_set_message('Saved.');

    // Bad: entityManager removed.
    $em = \Drupal::entityManager();
}
