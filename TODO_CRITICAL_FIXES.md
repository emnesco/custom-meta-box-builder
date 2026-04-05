# TODO: Critical Fixes

**Generated:** 2026-04-05
**Source:** Consolidated from AUDIT_ARCHITECTURE, AUDIT_SECURITY, AUDIT_WP_STANDARDS, AUDIT_PERFORMANCE, AUDIT_FRONTEND

These are immediate blockers that must be resolved before any release. They include security vulnerabilities, fatal architectural issues, and WordPress.org submission blockers.

---

## CF-001: Sanitize Imported Field Configurations

- **Title:** Import handler stores field data without sanitization (Stored XSS)
- **Description:** `AdminUI::handleImport()` sanitizes top-level keys (`title`, `postTypes`, `context`, `priority`) but stores the `fields` array verbatim from the uploaded JSON. When these fields are rendered, unsanitized values in `type`, `width`, `label`, and option keys flow into HTML output.
- **Root Cause:** The import path was added after the save path and didn't replicate the field-level sanitization logic from `handleSave()`.
- **Proposed Solution:**
  1. Extract field sanitization logic from `handleSave()` into a reusable `sanitizeFieldConfig(array $field): array` method.
  2. Apply this method recursively to all imported fields in `handleImport()`.
  3. Validate field `type` against known types; reject unknown types with a notice.
- **Affected Files:**
  - `src/Core/AdminUI.php` (handleImport, ~line 1151-1165)
- **Estimated Effort:** 3 hours
- **Priority:** P0
- **Dependencies:** None

---

## CF-002: Escape Dynamic HTML Attributes in FieldRenderer

- **Title:** Unescaped dynamic values in HTML class/data attributes
- **Description:** In `FieldRenderer::render()` (line 114) and `renderMultilingualField()` (line 171), variables `$layout`, `$field['type']`, `$width`, `$repeat`, `$required_class`, and `$conditionalAttrs` are concatenated directly into HTML without `esc_attr()`. Combined with CF-001, this creates a Stored XSS vector.
- **Root Cause:** Field configuration values were assumed to be safe because they're set by admins, but import and Admin UI builder bypass this assumption.
- **Proposed Solution:**
  ```php
  $output = '<div class="cmb-field ' . esc_attr($layout) . ' cmb-type-' . esc_attr($field['type']) . ' ' . esc_attr($repeat) . ' ' . esc_attr($width) . ' ' . esc_attr($required_class) . '"' . $conditionalAttrs . '>';
  ```
  Also escape values inside `$conditionalAttrs` construction.
- **Affected Files:**
  - `src/Core/FieldRenderer.php` (lines 114, 171, conditionalAttrs construction)
- **Estimated Effort:** 1 hour
- **Priority:** P0
- **Dependencies:** None

---

## CF-003: Add Missing Capability Check in TaxonomyMetaManager

- **Title:** TaxonomyMetaManager::saveFields() missing capability check
- **Description:** The `saveFields()` method verifies the nonce but does not check `current_user_can('edit_term', $termId)`. Both `MetaBoxManager::saveMetaBoxData()` and `UserMetaManager::saveFields()` correctly check capabilities; taxonomy save does not.
- **Root Cause:** Omission during implementation.
- **Proposed Solution:**
  ```php
  if (!current_user_can('edit_term', $termId)) {
      return;
  }
  ```
  Add after the nonce check at line 84.
- **Affected Files:**
  - `src/Core/TaxonomyMetaManager.php` (saveFields, line 84)
- **Estimated Effort:** 0.5 hours
- **Priority:** P0
- **Dependencies:** None

---

## CF-004: Create uninstall.php

- **Title:** No cleanup on plugin uninstall (WordPress.org blocker)
- **Description:** The plugin stores data in `wp_options` (key `cmb_admin_configurations`) and `wp_postmeta` but provides no cleanup when uninstalled. WordPress.org requires plugins to clean up after themselves.
- **Root Cause:** Never implemented.
- **Proposed Solution:**
  Create `uninstall.php` in plugin root:
  ```php
  <?php
  defined('WP_UNINSTALL_PLUGIN') || exit;
  delete_option('cmb_admin_configurations');
  // Optionally: delete all post meta with cmb_ prefix (with user confirmation via option)
  ```
- **Affected Files:**
  - `uninstall.php` (new file)
- **Estimated Effort:** 1 hour
- **Priority:** P0
- **Dependencies:** None

---

## CF-005: Add Activation/Deactivation Hooks

- **Title:** No activation or deactivation hooks registered
- **Description:** The plugin has no `register_activation_hook()` or `register_deactivation_hook()`. Cannot set default options, check PHP/WP version requirements, flush rewrite rules, or clean up scheduled events on deactivation.
- **Root Cause:** Never implemented.
- **Proposed Solution:**
  In `custom-meta-box-builder.php`:
  ```php
  register_activation_hook(__FILE__, function () {
      if (version_compare(PHP_VERSION, '8.1', '<')) {
          deactivate_plugins(plugin_basename(__FILE__));
          wp_die('Custom Meta Box Builder requires PHP 8.1 or higher.');
      }
      update_option('cmb_version', '2.0', false);
  });
  register_deactivation_hook(__FILE__, function () {
      // Clean transients, scheduled events
  });
  ```
- **Affected Files:**
  - `custom-meta-box-builder.php`
- **Estimated Effort:** 2 hours
- **Priority:** P0
- **Dependencies:** None

---

## CF-006: Add Required Plugin Header Fields

- **Title:** Missing mandatory plugin header fields
- **Description:** The plugin header is missing: `Text Domain`, `Domain Path`, `Requires at least`, `Requires PHP`, `License`, `Author URI`, `Plugin URI`. The `Author` field is a placeholder "Your Name".
- **Root Cause:** Plugin is in early development state.
- **Proposed Solution:**
  ```php
  /**
   * Plugin Name: Custom Meta Box Builder
   * Plugin URI:  https://example.com/custom-meta-box-builder
   * Description: Create custom meta boxes with modern PHP architecture.
   * Version:     2.0
   * Author:      [Actual Author Name]
   * Author URI:  https://example.com
   * Text Domain: custom-meta-box-builder
   * Domain Path: /languages
   * Requires at least: 6.0
   * Requires PHP: 8.1
   * License:     GPL-2.0-or-later
   * License URI: https://www.gnu.org/licenses/gpl-2.0.html
   */
  ```
- **Affected Files:**
  - `custom-meta-box-builder.php`
- **Estimated Effort:** 0.5 hours
- **Priority:** P0
- **Dependencies:** None

---

## CF-007: Implement Internationalization (i18n)

- **Title:** Complete absence of i18n -- zero translatable strings
- **Description:** The entire plugin contains zero calls to `__()`, `_e()`, `esc_html__()`, `esc_attr__()`, or any translation function. Every user-facing string in PHP and JavaScript is hardcoded in English. This is a mandatory WordPress.org requirement.
- **Root Cause:** i18n was never planned or implemented.
- **Proposed Solution:**
  1. Add `load_plugin_textdomain('custom-meta-box-builder', false, dirname(plugin_basename(__FILE__)) . '/languages')` on `plugins_loaded`.
  2. Wrap all user-facing PHP strings with `__()` / `esc_html__()` / `esc_attr__()`.
  3. Use `wp_localize_script()` or `wp_set_script_translations()` for JavaScript strings.
  4. Generate `.pot` file with `wp i18n make-pot`.
- **Affected Files:**
  - All PHP files (every user-facing string)
  - `assets/cmb-script.js` (~10 strings)
  - `assets/cmb-admin.js` (~15 strings)
  - `assets/cmb-gutenberg.js` (~3 strings)
  - `custom-meta-box-builder.php` (add load_plugin_textdomain)
- **Estimated Effort:** 16 hours
- **Priority:** P0
- **Dependencies:** CF-006 (Text Domain header)

---

## CF-008: Add ABSPATH Guard to public-api.php

- **Title:** public-api.php lacks direct access protection
- **Description:** `public-api.php` can be loaded directly via URL and does not have the standard `defined('ABSPATH') || exit;` guard. All other PHP files have this guard.
- **Root Cause:** Omission.
- **Proposed Solution:**
  Add at the top of `public-api.php`:
  ```php
  defined('ABSPATH') || exit;
  ```
- **Affected Files:**
  - `public-api.php`
- **Estimated Effort:** 0.1 hours
- **Priority:** P0
- **Dependencies:** None

---

## CF-009: Fix XSS in JavaScript File Upload Preview

- **Title:** DOM-based XSS via unsanitized filename in file upload preview
- **Description:** In `cmb-script.js` (lines 203-206), `attachment.filename` is injected via `.html()` without escaping. If a filename contains HTML/JS, it could execute.
- **Root Cause:** String-based HTML construction with untrusted data.
- **Proposed Solution:**
  Replace:
  ```javascript
  $preview.html('<a href="' + attachment.url + '">' + attachment.filename + '</a>');
  ```
  With:
  ```javascript
  $preview.empty().append(
      $('<a>').attr({href: attachment.url, target: '_blank'}).text(attachment.filename)
  );
  ```
- **Affected Files:**
  - `assets/cmb-script.js` (lines 203-206)
- **Estimated Effort:** 0.5 hours
- **Priority:** P0
- **Dependencies:** None

---

## CF-010: Fix Accessibility -- Interactive Elements Using Wrong Semantics

- **Title:** `<span>` and `<a href="#">` used as buttons -- invisible to keyboard/screen readers
- **Description:** `<span class="cmb-add-row">` (FieldRenderer.php:145) and `<span class="cmb-load-more">` (cmb-script.js:413) are clickable elements rendered as `<span>`. `<a href="#">` is used for Expand All/Collapse All. These are not focusable, not announced as buttons, and not keyboard-activatable.
- **Root Cause:** Incorrect semantic HTML choices.
- **Proposed Solution:**
  - Change `<span class="cmb-add-row">` to `<button type="button" class="cmb-add-row">`
  - Change dynamically created `<span class="cmb-load-more">` to `<button type="button">`
  - Change `<a href="#" class="cmb-expand-all">` to `<button type="button" class="cmb-expand-all">`
  - Update CSS selectors accordingly.
- **Affected Files:**
  - `src/Core/FieldRenderer.php` (line 145, expand/collapse elements)
  - `assets/cmb-script.js` (line 413, load-more creation)
  - `assets/cmb-style.css` (update selectors)
- **Estimated Effort:** 2 hours
- **Priority:** P0
- **Dependencies:** None

---

## CF-011: Fix UserField Unbounded Query

- **Title:** UserField::render() loads ALL users with no limit -- memory exhaustion risk
- **Description:** `UserField::render()` calls `get_users()` without a `number` parameter. On sites with 10,000+ users, this fetches all user rows, hydrates them into objects, and builds 10,000+ `<option>` elements. Can consume 50+ MB and cause timeouts.
- **Root Cause:** No limit parameter set on the query.
- **Proposed Solution:**
  1. Add `'number' => $this->config['limit'] ?? 100` to the query args.
  2. Add static cache keyed by role to avoid redundant queries.
  3. Document the `limit` config option.
- **Affected Files:**
  - `src/Fields/UserField.php` (render method, lines 17-23)
- **Estimated Effort:** 1 hour
- **Priority:** P0
- **Dependencies:** None

---

## CF-012: Fix BulkOperations Unbounded Query

- **Title:** BulkOperations uses `posts_per_page => -1` -- crashes on large sites
- **Description:** `BulkOperations::handleBulkUpdate()` fetches ALL posts of a type with no limit. With 100,000+ posts, this causes memory exhaustion and PHP timeouts.
- **Root Cause:** No pagination or batching implemented.
- **Proposed Solution:**
  1. Process in batches of 100-500 posts using pagination.
  2. Add `wp_cache_flush()` between batches.
  3. Add a confirmation step showing affected post count before execution.
  4. Consider using `$wpdb` for bulk set/delete operations for efficiency.
- **Affected Files:**
  - `src/Core/BulkOperations.php` (handleBulkUpdate, lines 120-126; bulkSet; bulkDelete)
- **Estimated Effort:** 4 hours
- **Priority:** P0
- **Dependencies:** None

---

## CF-013: Conditional Asset Loading

- **Title:** Core CSS/JS/media library loaded on ALL admin pages (~270KB wasted)
- **Description:** `Plugin::registerAssets()` enqueues `cmb-style.css`, `cmb-script.js`, jQuery UI Sortable, and `wp_enqueue_media()` on every admin page via `admin_enqueue_scripts` without checking the current screen.
- **Root Cause:** No screen check implemented in the enqueue callback.
- **Proposed Solution:**
  ```php
  add_action('admin_enqueue_scripts', function ($hookSuffix) {
      if (!in_array($hookSuffix, ['post.php', 'post-new.php'], true)) {
          return;
      }
      // Only enqueue media if a file field is registered
      // ... enqueue assets
  });
  ```
  Also add conditional loading for taxonomy edit screens, user profile, and options pages.
- **Affected Files:**
  - `src/Core/Plugin.php` (registerAssets, lines 25-34)
- **Estimated Effort:** 3 hours
- **Priority:** P0
- **Dependencies:** None

---

## CF-014: Boot Plugin on `plugins_loaded` Hook

- **Title:** Plugin boots at file include time instead of on a WordPress hook
- **Description:** `custom-meta-box-builder.php` instantiates `Plugin` and calls `boot()` immediately at file load. All hooks are registered before other plugins can interact, and frontend requests execute admin-only module registration.
- **Root Cause:** Missing hook-based initialization pattern.
- **Proposed Solution:**
  ```php
  add_action('plugins_loaded', function () {
      $plugin = new Plugin();
      $plugin->boot();
  });
  ```
  Also gate admin-only modules with `is_admin()` inside `boot()`.
- **Affected Files:**
  - `custom-meta-box-builder.php` (lines 15-16)
  - `src/Core/Plugin.php` (boot method)
- **Estimated Effort:** 1 hour
- **Priority:** P0
- **Dependencies:** CF-005

---

## Summary

| ID | Title | Priority | Effort (hrs) |
|----|-------|----------|--------------|
| CF-001 | Sanitize imported field configurations | P0 | 3 |
| CF-002 | Escape dynamic HTML attributes | P0 | 1 |
| CF-003 | Add taxonomy capability check | P0 | 0.5 |
| CF-004 | Create uninstall.php | P0 | 1 |
| CF-005 | Add activation/deactivation hooks | P0 | 2 |
| CF-006 | Add plugin header fields | P0 | 0.5 |
| CF-007 | Implement i18n | P0 | 16 |
| CF-008 | Add ABSPATH guard to public-api.php | P0 | 0.1 |
| CF-009 | Fix XSS in JS file preview | P0 | 0.5 |
| CF-010 | Fix semantic HTML for interactive elements | P0 | 2 |
| CF-011 | Fix UserField unbounded query | P0 | 1 |
| CF-012 | Fix BulkOperations unbounded query | P0 | 4 |
| CF-013 | Conditional asset loading | P0 | 3 |
| CF-014 | Boot on plugins_loaded hook | P0 | 1 |
| **Total** | | | **35.6** |
