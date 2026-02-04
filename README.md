# phpstan-drupal-rules

Opinionated PHPStan rules for Drupal 10.3+/11 codebases. Layers on top of
[`mglaman/phpstan-drupal`](https://github.com/mglaman/phpstan-drupal) and
catches a set of issues that the upstream extension intentionally does not
flag — service locator abuse, hook signature drift, deprecated entity APIs
and unsafe `entityQuery()` calls.

## Status

Work in progress — initial skeleton, rule implementations land in follow-up commits.

## Requirements

| Package | Version |
| --- | --- |
| PHP | `^8.3` |
| `phpstan/phpstan` | `^1.11` or `^2.0` |
| `mglaman/phpstan-drupal` | `^1.3` or `^2.0` |
| Drupal core (target codebase) | `10.3+` or `11.x` |

## Installation

```bash
composer require --dev your-org/phpstan-drupal-rules
```

If you do not use `phpstan/extension-installer`, include the extension in
your project's `phpstan.neon`:

```neon
includes:
    - vendor/mglaman/phpstan-drupal/extension.neon
    - vendor/mglaman/phpstan-drupal/rules.neon
    - vendor/your-org/phpstan-drupal-rules/extension.neon
```

## Configuration

Every rule can be turned off individually via the `parameters.drupalRules`
block. Defaults are all `true`.

```neon
parameters:
    drupalRules:
        noServiceLocatorInDIClass: true
        hookImplementationSignature: true
        noDeprecatedEntityApi: true
        noEntityQueryWithoutAccessCheck: true
```

See `extension.neon` in this package for the full list of tunables.

## License

[MIT](LICENSE)
