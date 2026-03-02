# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial PHPStan extension skeleton with composer.json, extension.neon, MIT license and PSR-4 autoload.
- `NoServiceLocatorInDIClassRule` flagging `\Drupal::service()` and friends inside DI-aware classes.
- `HookImplementationSignatureRule` with a curated hook signature map covering form, entity, node access, views, theme and module lifecycle hooks.
- `NoDeprecatedEntityApiRule` flagging `entity_load`, `node_load`, `drupal_render`, `drupal_set_message`, `\Drupal::entityManager()` and friends with concrete replacement suggestions.
- `NoEntityQueryWithoutAccessCheckRule` requiring an explicit `->accessCheck()` call on every `entityQuery()` / `getQuery()` chain (Drupal 10+ requirement).
- Dogfooded PHPStan configuration at level 8 plus GitHub Actions CI matrix (PHP 8.3 / 8.4 × PHPStan ^1.11 / ^2.0).

## [0.1.0] - TBD

- First tagged release once the rule set stabilises.
