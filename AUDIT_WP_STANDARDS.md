# WordPress Standards & Compliance Audit Report

**Plugin:** Custom Meta Box Builder v2.1
**Audit Date:** 2026-04-06 (Re-audit)
**Previous Audit:** 2026-04-05 (v2.0)
**Auditor:** WordPress Standards Agent

---

## 1. Executive Summary

**Overall Rating: IMPROVED** (from v2.0, but still has blockers for WordPress.org submission)

The v2.1 codebase resolves many critical v2.0 findings: `uninstall.php` exists, plugin headers are complete, activation/deactivation hooks are registered, `wp_unslash()` is applied, and i18n infrastructure is in place via `load_plugin_textdomain()`.

However, several standards violations remain that would block WordPress.org directory acceptance.

**Findings Summary:**
| Severity | Count | Change from v2.0 |
|----------|-------|-------------------|
| Critical | 4     | -1                |
| High     | 6     | -3                |
| Medium   | 7     | -5                |
| Low      | 2     | -6                |

---

## 2. Resolved Findings from v2.0

| v2.0 Finding | Resolution |
|---|---|
| No `uninstall.php` | Created with clean option removal (CF-004) |
| Missing plugin headers | All required headers added (CF-006) |
| No activation/deactivation hooks | `register_activation_hook` / `register_deactivation_hook` added (CF-005) |
| Zero translatable strings | `load_plugin_textdomain()` + text domain infrastructure (CF-007) |
| No `wp_unslash()` | Applied to all `$_POST` reads (SEC-002) |
| Assets on all admin pages | Conditional loading via `shouldEnqueueAssets()` (CF-013) |
| No cache-busting on assets | `filemtime()` versioning (PERF-009) |
| Plugin not booting on `plugins_loaded` | Moved to `plugins_loaded` hook (CF-014) |

---

## 3. Critical Findings

### WPS-C01: Missing ABSPATH Guards in `src/` Files

**Severity: Critical**
**Files:** All 66 PHP files in `src/` directory

While `public-api.php` and the main plugin file have `defined('ABSPATH') || exit;`, the PSR-4 autoloaded class files in `src/` do not. WordPress.org plugin review requires every PHP file to have an ABSPATH guard to prevent direct access.

**Note:** Files with `namespace` declarations and no executable code outside the class are lower risk, but the WordPress.org reviewer will still flag this.

**Recommendation:** Add `defined( 'ABSPATH' ) || exit;` as the first line after `<?php` in all `src/` files. This can be automated with a simple script.

---

### WPS-C02: File Operations Not Using WP_Filesystem

**Severity: Critical**
**Files:** `src/Core/LocalJson.php`, `src/Core/ImportExport.php`

`LocalJson::saveToFile()` uses `file_put_contents()` directly. `LocalJson::syncFromFiles()` uses `file_get_contents()` and `glob()`. WordPress coding standards require `WP_Filesystem` API for all file operations.

```php
// Current (non-compliant):
file_put_contents($path, json_encode($config));

// Required:
global $wp_filesystem;
WP_Filesystem();
$wp_filesystem->put_contents($path, wp_json_encode($config));
```

**Recommendation:** Refactor all file I/O to use `WP_Filesystem`. Use `wp_json_encode()` instead of `json_encode()`.

---

### WPS-C03: Hardcoded Strings Without Text Domain

**Severity: Critical**
**Files:** Multiple render methods across field types and admin pages

Despite the i18n infrastructure being in place, many user-facing strings are still hardcoded without `__()` / `_e()`:

- `ListPage.php`: "Add New", "Title", "Post Types", "Actions", "Edit", "Delete", "Duplicate"
- `EditPage.php`: "Field Type", "Field ID", "Label", "Required", "Add Field", "Save Meta Box"
- `FieldRenderer.php`: "Search...", "Select...", "No results found"
- `FlexibleContentField.php`: "Add Layout", "Remove", "Collapse"
- `FrontendForm.php`: "Submit", "Processing..."
- `BlockRegistration.php`: Block labels and descriptions

**Count:** ~80+ hardcoded strings across 15+ files.

**Recommendation:** Wrap all user-facing strings with `__('string', 'custom-meta-box-builder')`. Use `esc_html__()` for output contexts.

---

### WPS-C04: Direct `json_encode()` Instead of `wp_json_encode()`

**Severity: Critical**
**Files:** `LocalJson.php`, `ImportExport.php`, `ActionHandler.php`

WordPress provides `wp_json_encode()` which handles encoding errors gracefully. Direct `json_encode()` can silently return `false` on encoding failures.

**Recommendation:** Replace all `json_encode()` calls with `wp_json_encode()`.

---

## 4. High Findings

### WPS-H01: i18n Violations in AdminUI Pages

**Severity: High**
**Files:** `src/Core/AdminUI/ListPage.php`, `src/Core/AdminUI/EditPage.php`

Admin pages render HTML with hardcoded English strings for table headers, buttons, labels, and messages. These are the most visible strings to users.

---

### WPS-H02: Inline JS Confirmation Dialogs

**Severity: High**
**Files:** `src/Core/AdminUI/ListPage.php`

Delete confirmation uses inline `onclick="return confirm('Are you sure?')"` instead of properly enqueued JavaScript with `wp_localize_script()` for the confirmation message.

**Recommendation:** Move confirmation logic to enqueued JS file. Use `wp.i18n.__()` for the message string.

---

### WPS-H03: No Yoda Conditions

**Severity: High**
**Files:** Throughout codebase

WordPress Coding Standards require Yoda conditions (constant on left side of comparisons):

```php
// Current (non-Yoda):
if ($value === null)
if ($type === 'text')

// Required (Yoda):
if (null === $value)
if ('text' === $type)
```

**Count:** ~200+ non-Yoda comparisons across the codebase.

---

### WPS-H04: Error Suppression Operators

**Severity: High**
**Files:** `AbstractField.php` (`@preg_match`), `Plugin.php` (`@filemtime`)

WordPress coding standards prohibit the `@` error suppression operator. Use proper error checking instead.

---

### WPS-H05: Missing `wp_json_encode()` Usage

**Severity: High**
**Files:** `LocalJson.php`, `ActionHandler.php`, `ImportExport.php`

Multiple instances of `json_encode()` should be `wp_json_encode()`.

---

### WPS-H06: `register_rest_field()` Missing `sanitize_callback`

**Severity: High**
**File:** `src/Core/MetaBoxManager.php`

REST field registration added `sanitize_callback` (DX-011) but the implementation may not cover all field types consistently. Complex types (group, flexible content) need type-specific sanitization in REST context.

---

## 5. Medium Findings (7)

| ID | Description | File(s) |
|---|---|---|
| WPS-M01 | No transient caching for expensive operations | `LocalJson.php`, `ActionHandler.php` |
| WPS-M02 | `get_option()` without default parameter in some calls | Multiple |
| WPS-M03 | Mixed tabs/spaces indentation in some files | `cmb-script.js` |
| WPS-M04 | `register_post_meta()` should use `object_subtype` parameter | `MetaBoxManager.php` |
| WPS-M05 | No `wp_add_inline_script()` — uses `wp_localize_script()` | `Plugin.php` |
| WPS-M06 | Missing `load_plugin_textdomain()` domain path validation | `Plugin.php` |
| WPS-M07 | `readme.txt` version doesn't auto-sync with plugin header | `readme.txt` |

---

## 6. Low Findings (2)

| ID | Description |
|---|---|
| WPS-L01 | No `.pot` file generated for translators |
| WPS-L02 | `composer.json` missing `wordpress-plugin` type |

---

## 7. WordPress.org Submission Readiness

| Requirement | Status | Blocker? |
|---|---|---|
| ABSPATH guards in all files | Partial (root only) | **YES** |
| Plugin headers complete | Pass | No |
| `uninstall.php` exists | Pass | No |
| Text domain in all strings | Fail (~80 missing) | **YES** |
| `WP_Filesystem` for file I/O | Fail | **YES** |
| No direct `$_POST`/`$_GET` | Pass | No |
| Proper escaping on output | Mostly pass | Minor issues |
| No PHP errors/warnings | Pass | No |
| GPL-compatible license | Pass | No |
| `readme.txt` format | Pass | No |

**Verdict:** 3 blockers remain for WordPress.org submission. Estimated effort to resolve: 4-6 hours.

---

## 8. Compliance Scorecard

| Dimension | v2.0 Score | v2.1 Score | Notes |
|---|---|---|---|
| Plugin structure | 3/10 | 8/10 | Headers, uninstall, activation hooks |
| Internationalization | 0/10 | 3/10 | Infrastructure exists, strings not wrapped |
| Coding standards | 4/10 | 6/10 | Better but Yoda/ABSPATH missing |
| Data handling | 5/10 | 8/10 | wp_unslash, sanitize, nonces improved |
| Asset management | 3/10 | 8/10 | Conditional, versioned, minified |
| REST API compliance | 4/10 | 7/10 | sanitize_callback added |
| **Overall** | **3.2/10** | **6.7/10** | **Major improvement** |
