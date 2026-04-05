# TODO: Developer Experience (DX)

**Generated:** 2026-04-05
**Source:** Consolidated from AUDIT_FEATURE_GAP, AUDIT_ARCHITECTURE, AUDIT_WP_STANDARDS

Developer experience improvements including API design, hooks, documentation, and tooling.

---

## DX-001: Add readme.txt for WordPress.org

- **Title:** No readme.txt file (WordPress.org submission blocker)
- **Description:** WordPress.org requires a `readme.txt` in standard format with description, installation, changelog, FAQ, screenshots, etc.
- **Root Cause:** Never created.
- **Proposed Solution:**
  Create `readme.txt` following [WordPress readme standard](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/):
  ```
  === Custom Meta Box Builder ===
  Contributors: ...
  Tags: meta box, custom fields, meta, post meta
  Requires at least: 6.0
  Tested up to: 6.7
  Requires PHP: 8.1
  Stable tag: 2.0
  License: GPLv2 or later

  == Description ==
  ...
  == Installation ==
  ...
  == Changelog ==
  = 2.0 =
  * Initial release
  ```
- **Affected Files:**
  - `readme.txt` (new)
- **Estimated Effort:** 3 hours
- **Priority:** P0
- **Dependencies:** None

---

## DX-002: Add LICENSE File

- **Title:** No LICENSE file in plugin root
- **Description:** `composer.json` declares `GPL-2.0-or-later` but there's no `LICENSE` or `LICENSE.txt` file. WordPress.org requires GPL-compatible licensing with a license file.
- **Root Cause:** Never created.
- **Proposed Solution:**
  Add `LICENSE` file with full GPL-2.0-or-later text.
- **Affected Files:**
  - `LICENSE` (new)
- **Estimated Effort:** 0.5 hours
- **Priority:** P0
- **Dependencies:** None

---

## DX-003: Update composer.json with Platform Requirements and Tooling

- **Title:** composer.json missing PHP version, dev tools, scripts
- **Description:** No `require.php` version constraint (plugin uses PHP 8.1+ features), no scripts section, no static analysis or coding standards tools, no `autoload-dev` for tests.
- **Root Cause:** Minimal composer setup.
- **Proposed Solution:**
  ```json
  {
      "name": "yourname/custom-meta-box-builder",
      "require": {
          "php": ">=8.1"
      },
      "require-dev": {
          "phpunit/phpunit": "^10",
          "phpstan/phpstan": "^1.0",
          "squizlabs/php_codesniffer": "^3.0",
          "wp-coding-standards/wpcs": "^3.0",
          "brain/monkey": "^2.6"
      },
      "autoload-dev": {
          "psr-4": {
              "CMB\\Tests\\": "tests/"
          }
      },
      "scripts": {
          "test": "phpunit",
          "lint": "phpcs --standard=WordPress src/",
          "analyse": "phpstan analyse src/ --level=5"
      }
  }
  ```
- **Affected Files:**
  - `composer.json`
- **Estimated Effort:** 2 hours
- **Priority:** P1
- **Dependencies:** None

---

## DX-004: Add PHPDoc Annotations to All Hooks

- **Title:** No centralized hook documentation or @hook annotations
- **Description:** The plugin defines 8 hooks (`cmb_before_save_field`, `cmb_after_save_field`, `cmb_before_render_field`, `cmb_after_render_field`, `cmb_meta_box_args`, `cmb_sanitize_{type}`, `cmb_field_html`, `cmb_field_value`) but none are documented with PHPDoc `@hook` annotations. No centralized hook reference.
- **Root Cause:** Documentation not prioritized.
- **Proposed Solution:**
  1. Add PHPDoc blocks above each `do_action` and `apply_filters` call:
     ```php
     /**
      * Fires before a field is saved.
      *
      * @since 2.0
      * @param string $fieldId The field identifier.
      * @param mixed  $value   The sanitized value.
      * @param int    $postId  The post ID.
      */
     do_action('cmb_before_save_field', $fieldId, $sanitized, $postId);
     ```
  2. Update `docs/hooks.md` with a complete reference.
- **Affected Files:**
  - `src/Core/MetaBoxManager.php`
  - `src/Core/FieldRenderer.php`
  - `docs/hooks.md`
- **Estimated Effort:** 4 hours
- **Priority:** P1
- **Dependencies:** None

---

## DX-005: Add File-Level and Class Docblocks

- **Title:** Most files missing file-level @package docblock and class docblocks
- **Description:** WordPress PHP Documentation Standards require file-level docblocks. Most files lack them. Classes like `Plugin`, `MetaBoxManager`, `FieldRenderer`, `OptionsManager`, and all Field classes have no class docblocks.
- **Root Cause:** Documentation not added during development.
- **Proposed Solution:**
  Add to each PHP file:
  ```php
  /**
   * MetaBoxManager - Core meta box registration and management.
   *
   * @package CustomMetaBoxBuilder\Core
   * @since   2.0
   */
  ```
- **Affected Files:**
  - All PHP files in `src/`
- **Estimated Effort:** 4 hours
- **Priority:** P2
- **Dependencies:** None

---

## DX-006: Rename Hook Prefix to Avoid CMB2 Conflict

- **Title:** Generic `cmb_` hook prefix may conflict with CMB2 plugin
- **Description:** Custom hooks use `cmb_` prefix which could conflict with CMB2, a widely-used plugin that also uses `cmb_` prefix for some hooks.
- **Root Cause:** Generic prefix chosen without considering ecosystem conflicts.
- **Proposed Solution:**
  Rename to `cmbbuilder_` or `custom_meta_box_` prefix. This is a breaking change -- should be done in a major version with deprecation:
  ```php
  do_action('cmbbuilder_before_save_field', ...);
  // Backwards compat:
  do_action('cmb_before_save_field', ...); // deprecated
  ```
- **Affected Files:**
  - All files with `do_action('cmb_` or `apply_filters('cmb_`
  - `docs/hooks.md`
- **Estimated Effort:** 4 hours
- **Priority:** P2
- **Dependencies:** FEAT-019

---

## DX-007: Add _doing_it_wrong() Messages with Documentation Links

- **Title:** Error messages lack documentation links or code examples
- **Description:** `_doing_it_wrong()` calls in the plugin don't include links to relevant documentation or code examples to help developers fix issues.
- **Root Cause:** Minimal error messages.
- **Proposed Solution:**
  ```php
  _doing_it_wrong(
      __METHOD__,
      sprintf('Class %s must implement FieldInterface. See: %s', $className, 'https://docs.example.com/extending'),
      '2.1'
  );
  ```
- **Affected Files:**
  - `src/Core/MetaBoxManager.php` (registerFieldType)
  - Other _doing_it_wrong sites
- **Estimated Effort:** 2 hours
- **Priority:** P2
- **Dependencies:** None

---

## DX-008: Exclude vendor/ Dev Dependencies from Distribution

- **Title:** vendor/ directory ships with phpunit and dev dependencies
- **Description:** The plugin includes `vendor/` with PHPUnit and other dev dependencies. Production builds should exclude dev dependencies or use `composer install --no-dev`.
- **Root Cause:** No build/release process.
- **Proposed Solution:**
  1. Add `.distignore` file listing files to exclude from distribution.
  2. Add a build script that runs `composer install --no-dev --optimize-autoloader`.
  3. Consider using `composer/installers` plugin or a release CI workflow.
- **Affected Files:**
  - `.distignore` (new)
  - Build script or CI workflow (new)
- **Estimated Effort:** 2 hours
- **Priority:** P1
- **Dependencies:** None

---

## DX-009: Add CI/CD Pipeline

- **Title:** No automated testing, linting, or quality gates
- **Description:** No GitHub Actions, no CI configuration. Tests, linting, and static analysis must be run manually.
- **Root Cause:** CI/CD never set up.
- **Proposed Solution:**
  Create `.github/workflows/ci.yml`:
  ```yaml
  name: CI
  on: [push, pull_request]
  jobs:
    test:
      runs-on: ubuntu-latest
      steps:
        - uses: actions/checkout@v4
        - uses: shivammathur/setup-php@v2
          with: { php-version: '8.1' }
        - run: composer install
        - run: composer test
        - run: composer lint
        - run: composer analyse
  ```
- **Affected Files:**
  - `.github/workflows/ci.yml` (new)
- **Estimated Effort:** 3 hours
- **Priority:** P1
- **Dependencies:** DX-003

---

## DX-010: Add Field Type Contribution Guide

- **Title:** No guide for contributing custom field types
- **Description:** While `docs/extending.md` exists, there's no step-by-step guide for contributing field types back to the plugin (coding standards, test requirements, PR process).
- **Root Cause:** Community contribution workflow not established.
- **Proposed Solution:**
  Create `CONTRIBUTING.md` with:
  - Development setup instructions
  - Coding standards (link to WordPress PHP CS)
  - Field type template/scaffold
  - Testing requirements
  - PR checklist
- **Affected Files:**
  - `CONTRIBUTING.md` (new)
- **Estimated Effort:** 3 hours
- **Priority:** P2
- **Dependencies:** None

---

## DX-011: Add REST API Write Validation

- **Title:** REST API registration lacks custom validation and rich schemas
- **Description:** Current REST integration uses basic `register_post_meta()` with simple type mapping. No custom REST schema for groups, no rich type definitions, no write-side validation.
- **Root Cause:** REST API integration was minimal.
- **Proposed Solution:**
  1. Add `schema` definitions per field type with proper `type`, `format`, `items` for arrays.
  2. Add `validate_callback` and `sanitize_callback` to `register_post_meta()`.
  3. Support group fields in REST with nested schema.
- **Affected Files:**
  - `src/Core/MetaBoxManager.php` (registerRestFields)
- **Estimated Effort:** 8 hours
- **Priority:** P1
- **Dependencies:** None

---

## Summary

| ID | Title | Priority | Effort (hrs) |
|----|-------|----------|--------------|
| DX-001 | Add readme.txt | P0 | 3 |
| DX-002 | Add LICENSE file | P0 | 0.5 |
| DX-003 | Update composer.json | P1 | 2 |
| DX-004 | Add PHPDoc hook annotations | P1 | 4 |
| DX-005 | Add file/class docblocks | P2 | 4 |
| DX-006 | Rename hook prefix | P2 | 4 |
| DX-007 | Add docs links to error messages | P2 | 2 |
| DX-008 | Exclude dev deps from dist | P1 | 2 |
| DX-009 | Add CI/CD pipeline | P1 | 3 |
| DX-010 | Add contribution guide | P2 | 3 |
| DX-011 | REST API write validation | P1 | 8 |
| **Total** | | | **35.5** |
