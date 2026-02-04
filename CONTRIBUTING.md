# Contributing

Thanks for considering a contribution. This package adds a small, opinionated
set of PHPStan rules on top of `mglaman/phpstan-drupal`. The goal is to keep
each rule narrow, well-tested, and useful for real Drupal 10.3+/11 teams.

## Development setup

```bash
composer install
composer test
composer analyze
```

## Adding a new rule

1. Add the rule class under `src/Rules/` implementing `PHPStan\Rules\Rule`.
2. Wire the service into `extension.neon` with the `phpstan.rules.rule` tag.
3. Add a `parameters.drupalRules.<ruleName>` toggle so users can disable it.
4. Add a `tests/Rules/<RuleName>Test.php` extending `RuleTestCase` plus
   realistic fixtures under `tests/Rules/data/<RuleName>/`.
5. Document the rule in `README.md` with a bad/good example.
6. Update `CHANGELOG.md` under the `Unreleased` section.

## Rule design guidelines

- Prefer one node type per rule. If you need more than one, consider splitting.
- Keep error messages actionable — name the offending API and the replacement.
- Make the rule configurable when in doubt, default to the team-friendly choice.
- Every rule should be exercised by at least one passing and one failing fixture.

## Code style

- `declare(strict_types=1);` in every file.
- PSR-12 formatting, four-space indent, trailing newline.
- No suppression comments unless justified inline.

## Reporting issues

Please include the smallest possible reproducer (a fixture file and the
PHPStan output) and the PHPStan / Drupal versions you are running.
