# PHPCS PHPCompatibility Enforcement

**Date:** 2026-05-29
**Status:** Approved

## Goal

Add PHPCompatibility sniffs to the existing PHPCS setup so CI statically enforces the declared PHP 7.4 minimum and guards against PHP 8.x-incompatible syntax landing in the codebase.

## Approach

Merge PHPCompatibility into the existing `phpcs.xml` alongside `WordPress-Core`. Single PHPCS run, one config file, no new CI steps.

## Changes

### 1. Composer dependency

Add to `require-dev`:

```json
"phpcompatibility/php-compatibility": "^9.3"
```

`dealerdirect/phpcodesniffer-composer-installer` (already present) auto-registers it as a PHPCS standard — no manual path config needed.

### 2. phpcs.xml

Add inside the existing `<ruleset>`:

```xml
<config name="testVersion" value="7.4-8.4"/>
<rule ref="PHPCompatibility"/>
```

- `testVersion` range: 7.4–8.4 (matches declared minimum; upper bound tracks latest stable)
- Placement: after existing `WordPress-Core` rule block
- Scan scope unchanged: `includes/` + main plugin file; templates remain excluded

### 3. CI matrix

Expand from `['8.1']` to `['7.4', '8.1', '8.3']`.

All three jobs run identical steps (PHPCS + PHPStan + PHPUnit). No version-conditional logic.

- **7.4** — validates runtime at declared minimum
- **8.1** — current baseline (existing)
- **8.3** — catches deprecations that become errors in future releases

### 4. Violation fix pass

After config changes, run `composer phpcs` locally and fix any new violations before committing. Expected patterns to fix if found:

| Syntax | Introduced | Fix |
|---|---|---|
| `str_contains()` / `str_starts_with()` | PHP 8.0 | Replace with `strpos()` / `substr()` |
| Nullsafe operator `?->` | PHP 8.0 | Replace with `isset()` guard |
| `match` expression | PHP 8.0 | Replace with `switch` |
| Named arguments | PHP 8.0 | Use positional args |
| `array_is_list()` | PHP 8.1 | Use manual check |
| `readonly` properties | PHP 8.1 | Not expected |
| Enums | PHP 8.1 | Not expected |

If zero violations found, no fix pass needed — commit config changes directly.

## Success Criteria

- `composer phpcs` clean with PHPCompatibility active
- CI matrix runs on PHP 7.4, 8.1, 8.3 and all pass
- Any future 8.0+ syntax triggers PHPCS failure on PR

## Out of Scope

- PHPStan version matrix (stays PHP 8.1 only — PHPStan is version-agnostic for static analysis)
- Adding PHPCompatibility to template files
- Changing the declared PHP minimum
