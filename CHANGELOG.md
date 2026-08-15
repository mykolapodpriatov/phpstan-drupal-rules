# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed
- `NoEntityQueryWithoutAccessCheckRule` now flags `->execute()` when a query assigned from `entityQuery()` / `getQuery()` is built across multiple statements and never calls `accessCheck()`.

## [0.1.0] - 2026-06-22

### Added
- Initial PHPStan extension skeleton with `composer.json`, `extension.neon`, MIT license and PSR-4 autoload.
- `NoServiceLocatorInDIClassRule` flagging `\Drupal::service()` and friends inside DI-aware classes.
- `HookImplementationSignatureRule` with a curated hook signature map covering form, entity, node access, views, theme and module lifecycle hooks.
- `NoDeprecatedEntityApiRule` flagging `entity_load`, `node_load`, `drupal_render`, `drupal_set_message`, `\Drupal::entityManager()` and friends with concrete replacement suggestions.
- `NoEntityQueryWithoutAccessCheckRule` requiring an explicit `->accessCheck()` call on every `entityQuery()` / `getQuery()` chain (Drupal 10+ requirement).
- `ChainParentVisitor`, registered via `phpstan.parser.richParserNodeVisitor`, supplying the parent links the entity-query rule needs on PHPStan 2.x (which no longer connects nodes by default).
- Dogfooded PHPStan configuration at level 8 plus a GitHub Actions CI matrix (PHP 8.3 / 8.4).
- README with bad/good code samples for every rule, configuration reference and a roadmap section.

### Notes
- Targets PHPStan `^2.0` and `mglaman/phpstan-drupal` `^2.0`. The full test suite and the level-8 self-analysis are green on this baseline.

[Unreleased]: https://github.com/mykolapodpriatov/phpstan-drupal-rules/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/mykolapodpriatov/phpstan-drupal-rules/releases/tag/v0.1.0
