# phpstan-drupal-rules

[![CI](https://github.com/mykolapodpriatov/phpstan-drupal-rules/actions/workflows/ci.yml/badge.svg)](https://github.com/mykolapodpriatov/phpstan-drupal-rules/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Opinionated PHPStan rules for Drupal 10.3+/11 codebases. Layers on top of
[`mglaman/phpstan-drupal`](https://github.com/mglaman/phpstan-drupal) and
catches a set of issues the upstream extension intentionally leaves alone —
service locator abuse inside DI-aware classes, hook signature drift,
deprecated entity APIs, and unsafe `entityQuery()` calls.

## Requirements

| Package | Version |
| --- | --- |
| PHP | `^8.3` |
| `phpstan/phpstan` | `^2.0` |
| `mglaman/phpstan-drupal` | `^2.0` |
| Drupal core (target codebase) | `10.3+` or `11.x` |

## Installation

```bash
composer require --dev mykolapodpriatov/phpstan-drupal-rules
```

If you use [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer)
the extension is wired up automatically. Otherwise include it manually in your
project's `phpstan.neon`:

```neon
includes:
    - vendor/mglaman/phpstan-drupal/extension.neon
    - vendor/mglaman/phpstan-drupal/rules.neon
    - vendor/mykolapodpriatov/phpstan-drupal-rules/extension.neon
```

## Configuration

Every rule has an individual on/off toggle under `parameters.drupalRules`.
Defaults are all `true`.

```neon
parameters:
    drupalRules:
        noServiceLocatorInDIClass: true
        hookImplementationSignature: true
        noDeprecatedEntityApi: true
        noEntityQueryWithoutAccessCheck: true
```

You can also tune the DI-aware base class list and the set of forbidden
`\Drupal::*` static methods — see `extension.neon` for the full set of knobs.

## Rules

### 1. `NoServiceLocatorInDIClassRule`

Forbids `\Drupal::service('...')`, `\Drupal::config('...')`, and the rest of
the locator family inside classes that already use Dependency Injection.

A class is considered DI-aware when it declares a non-empty constructor **or**
extends one of the configured Drupal base classes (`ControllerBase`,
`FormBase`, `ConfigFormBase`, `BlockBase`, etc).

Bad:

```php
final class ArticleController extends ControllerBase {
    public function build(): array {
        // ✗ Rule fires: ControllerBase already gives you DI.
        $config = \Drupal::config('system.site');
        return ['#markup' => (string) $config->get('name')];
    }
}
```

Good:

```php
final class ArticleController extends ControllerBase {
    public function __construct(
        private readonly ConfigFactoryInterface $configFactory,
    ) {}

    public function build(): array {
        $config = $this->configFactory->get('system.site');
        return ['#markup' => (string) $config->get('name')];
    }
}
```

### 2. `HookImplementationSignatureRule`

Validates the signature of common Drupal hooks. The rule looks at top-level
functions whose name matches `<module>_<hook_suffix>` and checks the parameter
count against a curated table.

Supported hooks include `hook_form_alter`, `hook_entity_presave`,
`hook_entity_insert/update/delete`, `hook_entity_access`, `hook_node_access`,
`hook_views_pre_render`, `hook_views_post_execute`, `hook_theme`,
`hook_preprocess_HOOK`, `hook_install`, `hook_uninstall`.

Bad:

```php
// ✗ hook_form_alter needs (array &$form, FormStateInterface $form_state, string $form_id)
function my_module_form_alter(array &$form, $form_state) {
    // ...
}
```

Good:

```php
function my_module_form_alter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // ...
}
```

### 3. `NoDeprecatedEntityApiRule`

Catches a hand-curated list of removed or deprecated Drupal APIs and points
to the modern replacement.

| Deprecated | Replacement |
| --- | --- |
| `entity_load`, `entity_load_multiple` | `\Drupal::entityTypeManager()->getStorage(...)->load[Multiple]()` |
| `node_load`, `user_load`, `taxonomy_term_load`, `file_load` | entity_type.manager storage handlers |
| `entity_get_form_display`, `entity_get_display` | `entity_display.repository` service |
| `drupal_render` | `renderer` service |
| `drupal_set_message` | `messenger` service |
| `format_string` | `FormattableMarkup` / `t()` |
| `\Drupal::entityManager()` | `\Drupal::entityTypeManager()` |

Bad:

```php
// ✗ entityManager() was removed.
$em = \Drupal::entityManager();
$node = node_load(42);
drupal_set_message('Saved.');
```

Good:

```php
$node = \Drupal::entityTypeManager()->getStorage('node')->load(42);
\Drupal::messenger()->addStatus('Saved.');
```

### 4. `NoEntityQueryWithoutAccessCheckRule`

Since Drupal 10 the entity query API throws if you do not state your access
check decision explicitly. The rule walks the method chain originating at
`\Drupal::entityQuery(...)` or `$storage->getQuery()` and reports if no
`accessCheck()` call appears anywhere in the chain.

Bad:

```php
// ✗ No accessCheck — throws at runtime.
$ids = \Drupal::entityQuery('node')
    ->condition('status', 1)
    ->execute();
```

Good:

```php
$ids = \Drupal::entityQuery('node')
    ->accessCheck(TRUE)
    ->condition('status', 1)
    ->execute();
```

## Roadmap

- `ProperPermissionConstantsRule` — flag literal permission strings passed to
  `->hasPermission()` and `AccessResult::allowedIfHasPermission()`, encourage
  promoting them to class constants or a `permissions.yml` registry.
- A `baseline` neon shipping reasonable defaults for migrating projects.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

[MIT](LICENSE) © 2026 Mykola Podpriatov
