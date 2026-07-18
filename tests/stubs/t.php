<?php

declare(strict_types=1);

/**
 * Test-only stub of Drupal's global t() translation helper.
 *
 * Registered as a PHPStan stubFile (see tests/stub-functions.neon) so the
 * analyser recognises the global t() the NoGlobalTFunctionInClassRule and
 * NoConcatenationInTranslatableStringRule fixtures call. The body is never
 * executed — PHPStan uses the signature for reflection only.
 */
function t(string $string, array $args = [], array $options = []): string
{
    return $string;
}
