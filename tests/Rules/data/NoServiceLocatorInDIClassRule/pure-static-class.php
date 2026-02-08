<?php

declare(strict_types=1);

namespace YourOrg\PhpStanDrupalRules\Tests\Fixtures\NoServiceLocatorInDIClassRule;

/**
 * A small utility class with no DI plumbing — locator calls are tolerated here.
 *
 * The rule only fires inside classes that already use DI. This class has
 * neither a constructor nor a DI-aware base, so `\Drupal::service()` is
 * the only sane way for it to reach a service.
 */
final class PureStaticHelper
{

    public static function siteName(): string
    {
        return (string) \Drupal::config('system.site')->get('name');
    }

}
