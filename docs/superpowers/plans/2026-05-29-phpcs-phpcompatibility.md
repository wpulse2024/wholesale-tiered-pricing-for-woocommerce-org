# PHPCompatibility Enforcement Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add PHPCompatibility sniffs to PHPCS and expand CI to PHP 7.4/8.1/8.3 so the declared PHP 7.4 minimum is statically enforced on every PR.

**Architecture:** Add `phpcompatibility/php-compatibility` as a dev dependency (auto-registered by the existing `dealerdirect` installer), merge the ruleset into `phpcs.xml` alongside `WordPress-Core`, expand the CI matrix to three PHP versions, and keep PHPStan pinned to PHP 8.1 only (it may not run on 7.4).

**Tech Stack:** PHP, PHPCS 3.x, PHPCompatibility 9.x, GitHub Actions

---

## File Map

| Action | File |
|---|---|
| Modify | `composer.json` |
| Modify | `.phpcs.xml` |
| Modify | `.github/workflows/ci.yml` |

---

### Task 1: Add PHPCompatibility composer dependency

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Edit `composer.json` — add the dependency**

  In `require-dev`, add one line after `"dealerdirect/phpcodesniffer-composer-installer": "^1.0"`:

  ```json
  "require-dev": {
      "phpunit/phpunit": "^9.6",
      "brain/monkey": "^2.6",
      "phpstan/phpstan": "^1.12",
      "szepeviktor/phpstan-wordpress": "^1.3",
      "squizlabs/php_codesniffer": "^3.10",
      "wp-coding-standards/wpcs": "^3.1",
      "dealerdirect/phpcodesniffer-composer-installer": "^1.0",
      "phpcompatibility/php-compatibility": "^9.3"
  },
  ```

- [ ] **Step 2: Install the new dependency**

  ```bash
  composer update phpcompatibility/php-compatibility
  ```

  Expected output includes a line like:
  ```
  Installing phpcompatibility/php-compatibility (9.3.5)
  ```
  The `dealerdirect` installer will also print:
  ```
  PHPCompatibility registered as a coding standard
  ```

- [ ] **Step 3: Verify PHPCS sees the new standard**

  ```bash
  ./vendor/bin/phpcs -i
  ```

  Expected output includes `PHPCompatibility` in the list of installed standards.

---

### Task 2: Update phpcs.xml to enable PHPCompatibility

**Files:**
- Modify: `.phpcs.xml`

- [ ] **Step 1: Add `testVersion` config and rule to `.phpcs.xml`**

  Replace the entire contents of `.phpcs.xml` with:

  ```xml
  <?xml version="1.0"?>
  <ruleset name="wholesale-tiered-pricing">
      <description>WordPress-Core coding standard with project-specific exclusions.</description>

      <!-- Paths to scan -->
      <file>includes</file>
      <file>wholesale-tiered-pricing-for-woocommerce.php</file>

      <!-- Paths to exclude -->
      <exclude-pattern>vendor/*</exclude-pattern>
      <exclude-pattern>node_modules/*</exclude-pattern>
      <exclude-pattern>plugin-assets/*</exclude-pattern>
      <exclude-pattern>templates/*</exclude-pattern>

      <rule ref="WordPress-Core">
          <!-- Existing filenames use class- prefix; sniff fights WP plugin conventions -->
          <exclude name="WordPress.Files.FileName"/>
          <!-- Team preference; low bug-prevention value -->
          <exclude name="WordPress.PHP.YodaConditions"/>
          <!-- Hook names use whtprole_ vendor prefix; correct as-is -->
          <exclude name="WordPress.NamingConventions.ValidHookName"/>
      </rule>

      <config name="minimum_supported_wp_version" value="6.0"/>

      <!-- PHPCompatibility: enforce PHP 7.4 minimum, guard against 8.x removals -->
      <config name="testVersion" value="7.4-8.4"/>
      <rule ref="PHPCompatibility"/>
  </ruleset>
  ```

- [ ] **Step 2: Run PHPCS to see current violation state**

  ```bash
  composer phpcs
  ```

  Two possible outcomes:
  - **No output / exit 0** — no violations. Proceed to Task 4 (skip Task 3).
  - **Violations listed** — proceed to Task 3 to fix them before committing.

---

### Task 3: Fix PHPCompatibility violations (run only if Task 2 Step 2 found violations)

**Files:**
- Modify: whichever `includes/*.php` files are flagged

  This task lists the fix pattern for each violation type PHPCompatibility may report. Apply only the fixes matching violations actually found.

- [ ] **Fix pattern: `str_contains()` (PHP 8.0+)**

  Replace:
  ```php
  str_contains( $haystack, $needle )
  ```
  With:
  ```php
  false !== strpos( $haystack, $needle )
  ```

- [ ] **Fix pattern: `str_starts_with()` (PHP 8.0+)**

  Replace:
  ```php
  str_starts_with( $string, $prefix )
  ```
  With:
  ```php
  0 === strpos( $string, $prefix )
  ```

- [ ] **Fix pattern: nullsafe operator `?->` (PHP 8.0+)**

  Replace:
  ```php
  $result = $obj?->method();
  ```
  With:
  ```php
  $result = isset( $obj ) ? $obj->method() : null;
  ```

- [ ] **Fix pattern: `match` expression (PHP 8.0+)**

  Replace:
  ```php
  $val = match ( $x ) {
      'a' => 1,
      'b' => 2,
      default => 0,
  };
  ```
  With:
  ```php
  switch ( $x ) {
      case 'a':
          $val = 1;
          break;
      case 'b':
          $val = 2;
          break;
      default:
          $val = 0;
  }
  ```

- [ ] **Fix pattern: named arguments (PHP 8.0+)**

  Replace:
  ```php
  array_slice( array: $arr, offset: 2 )
  ```
  With:
  ```php
  array_slice( $arr, 2 )
  ```

- [ ] **Verify clean after all fixes**

  ```bash
  composer phpcs
  ```

  Expected: no output, exit code 0.

---

### Task 4: Expand CI matrix and pin PHPStan to PHP 8.1

**Files:**
- Modify: `.github/workflows/ci.yml`

- [ ] **Step 1: Update the matrix and add PHPStan guard**

  Replace the entire contents of `.github/workflows/ci.yml` with:

  ```yaml
  name: CI

  on:
    push:
      branches: [ master ]
    pull_request:

  jobs:
    ci:
      name: PHP ${{ matrix.php }} — PHPCS + PHPStan + Tests
      runs-on: ubuntu-latest

      strategy:
        matrix:
          php: ['7.4', '8.1', '8.3']

      steps:
        - name: Checkout
          uses: actions/checkout@v4

        - name: Setup PHP
          uses: shivammathur/setup-php@v2
          with:
            php-version: ${{ matrix.php }}
            extensions: mbstring, xml
            coverage: none
            ini-values: memory_limit=1G

        - name: Cache Composer dependencies
          uses: actions/cache@v4
          with:
            path: vendor
            key: composer-${{ matrix.php }}-${{ hashFiles('composer.lock') }}
            restore-keys: composer-${{ matrix.php }}-

        - name: Install Composer dependencies
          run: composer install --no-interaction --prefer-dist --optimize-autoloader

        - name: PHPCS
          run: composer phpcs

        - name: PHPStan
          if: matrix.php == '8.1'
          run: composer phpstan

        - name: PHPUnit
          run: composer test
  ```

  Key changes from original:
  - Matrix: `['7.4', '8.1', '8.3']`
  - Cache key includes `${{ matrix.php }}` so each PHP version has its own Composer cache
  - PHPStan step has `if: matrix.php == '8.1'` — keeps it on the original single version as designed

- [ ] **Step 2: Verify the YAML is valid**

  ```bash
  python3 -c "import yaml, sys; yaml.safe_load(open('.github/workflows/ci.yml'))" && echo "YAML valid"
  ```

  Expected:
  ```
  YAML valid
  ```

---

### Task 5: Final local verification and commit

- [ ] **Step 1: Run full local suite**

  ```bash
  composer check
  ```

  Expected: PHPCS, PHPStan, PHPUnit all pass with exit 0.

- [ ] **Step 2: Commit all changes**

  ```bash
  git add composer.json composer.lock .phpcs.xml .github/workflows/ci.yml
  git commit -m "ci: add PHPCompatibility sniffs and expand matrix to PHP 7.4/8.1/8.3"
  ```

  If Task 3 fixes were applied, stage those files too:
  ```bash
  git add includes/
  git commit -m "fix: replace PHP 8.0+ syntax for 7.4 compatibility"
  ```
