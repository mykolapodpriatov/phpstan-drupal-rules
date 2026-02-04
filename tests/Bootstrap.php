<?php

declare(strict_types=1);

/**
 * PHPUnit + PHPStan RuleTestCase bootstrap.
 *
 * Loads the Composer autoloader and registers a small set of Drupal class
 * stubs so the rules can be unit-tested without booting a full Drupal site.
 * The real `mglaman/phpstan-drupal` package brings its own stubs for the
 * static analyser; here we only need enough symbols for fixture files to
 * parse against PHPStan's analyser, which scans the stubs directory.
 */

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
}

// Make the local Drupal stubs autoloadable for the test process. The files
// under tests/stubs/ declare empty classes in the Drupal namespace so that
// fixture files can reference ControllerBase, FormBase, etc. without a real
// Drupal install on the classpath.
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'Drupal\\')) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen('Drupal\\')));
    $file = __DIR__ . '/stubs/' . $relative . '.php';

    if (is_file($file)) {
        require $file;
    }
});
