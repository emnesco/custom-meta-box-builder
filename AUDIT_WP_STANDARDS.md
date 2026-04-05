# WordPress Standards & Compliance Audit Report

**Plugin:** Custom Meta Box Builder  
**Version:** 2.0  
**Audit Date:** 2026-04-05  
**Auditor:** Agent 2 (WordPress Standards & Compliance)

---

## 1. Executive Summary

The Custom Meta Box Builder plugin provides a well-architected PHP codebase with good separation of concerns, a clean field abstraction layer, and solid sanitization across most field types. However, the audit reveals **several critical and high-severity issues** that would prevent acceptance in the WordPress.org plugin directory and pose security or reliability risks in production.

**Key concerns:**

- Complete absence of internationalization (i18n) -- zero translatable strings
- No `uninstall.php` or activation/deactivation hooks
- Missing plugin header fields (Text Domain, Requires PHP, Requires at least, etc.)
- Assets enqueued globally on all admin pages instead of targeted screens
- Imported field data is stored without deep sanitization
- Direct `$_POST` / `$_GET` superglobal access without `wp_unslash()` in several locations
- Missing capability checks in TaxonomyMetaManager save handler
- No version-stamped asset handles (cache-busting)

**Finding Summary by Severity:**

| Severity | Count |
|----------|-------|
| Critical | 5     |
| High     | 9     |
| Medium   | 12    |
| Low      | 8     |

---

## 2. Detailed Findings

### 2.1 Plugin Structure & WordPress.org Requirements

#### CRITICAL: No `uninstall.php` file

**File:** (missing)  
**Standard:** [Plugin Handbook - Uninstall Methods](https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/)

The plugin stores data in `wp_options` (key `cmb_admin_configurations`) and `wp_postmeta`, but provides no cleanup mechanism when uninstalled. WordPress.org requires plugins to clean up after themselves.

**Impact:** Orphaned data left in the database permanently. WordPress.org plugin review will reject this.

---

#### CRITICAL: No activation/deactivation hooks

**File:** `custom-meta-box-builder.php`, `src/Core/Plugin.php`  
**Standard:** [Plugin Handbook - Activation/Deactivation Hooks](https://developer.wordpress.org/plugins/plugin-basics/activation-deactivation-hooks/)

No calls to `register_activation_hook()` or `register_deactivation_hook()` exist anywhere in the plugin. These are required for:

- Setting default options
- Flushing rewrite rules
- Version checks / upgrade routines
- Cleaning up scheduled events or transients on deactivation

**Impact:** No ability to perform setup or teardown. WordPress.org plugin review requirement.

---

#### CRITICAL: Missing required plugin header fields

**File:** `custom-meta-box-builder.php`

```php
/**
 * Plugin Name: Custom Meta Box Builder
 * Description: Create custom meta boxes with modern PHP architecture.
 * Version: 2.0
 * Author: Your Name
 */
```

Missing headers:
- `Text Domain` -- required for i18n (WordPress.org mandatory)
- `Domain Path` -- needed if translation files are in a subdirectory
- `Requires at least` -- minimum WordPress version
- `Requires PHP` -- minimum PHP version (the plugin uses PHP 8.1+ features like `mixed` type, `match`, named arguments)
- `License` -- must be stated in header (present in composer.json but not in plugin header)
- `Author URI`
- `Plugin URI`

**Standard:** [Plugin Header Requirements](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/)

---

#### HIGH: No `defined('ABSPATH')` guard in public-api.php

**File:** `public-api.php`

This file can be loaded directly via URL and does not have the standard `defined('ABSPATH') || exit;` guard.

**Impact:** Direct file access is possible, which could trigger PHP errors or expose information.

---

#### HIGH: Plugin class instantiated at include time

**File:** `custom-meta-box-builder.php`, lines 15-16

```php
$plugin = new Plugin();
$plugin->boot();
```

The Plugin class is instantiated immediately at file load time rather than hooking into `plugins_loaded`. This means:
- All hooks are registered before other plugins can interact
- No opportunity for other plugins to unhook or modify behavior early
- Boot order issues with dependent plugins

**Standard:** WordPress best practice is to initialize on `plugins_loaded` hook.

---

### 2.2 Internationalization (i18n)

#### CRITICAL: Complete absence of i18n

**Files:** All PHP files  
**Standard:** [WordPress i18n](https://developer.wordpress.org/plugins/internationalization/)

The entire plugin contains **zero** calls to `__()`, `_e()`, `esc_html__()`, `esc_attr__()`, `_n()`, or any other WordPress translation function. Every user-facing string is hardcoded in English.

Examples of untranslated strings:

| File | String |
|------|--------|
| `MetaBoxManager.php:390` | `'Meta Box Validation Errors:'` |
| `UserMetaManager.php:21` | `'Additional Information'` |
| `BulkOperations.php:29` | `'CMB Bulk Meta Operations'` |
| `AdminUI.php:89` | `'Field Groups'` |
| `AdminUI.php:90` | `'Add New'` |
| `AdminUI.php:109` | `'No Field Groups Yet'` |
| `FieldRenderer.php:126` | `'Expand All'`, `'Collapse All'` |
| `FieldRenderer.php:133` | `'Search items...'` |
| `GroupField.php:57` | `'Drag to reorder'` |
| `FileField.php:12` | `'Select File'` |
| `DependencyGraph.php:31` | `'Visual representation of field dependencies...'` |
| `cmb-script.js:149` | `'No items yet. Click "Add Row" to add one.'` |
| `cmb-script.js:129` | `'Remove this item?'` |

**Impact:** Plugin cannot be translated. This is a **mandatory requirement** for WordPress.org submission.

---

#### CRITICAL: No text domain declared or loaded

**File:** `custom-meta-box-builder.php`

No call to `load_plugin_textdomain()` exists. No `Text Domain` header is present.

**Standard:** WordPress.org Plugin Guidelines, Section 7.

---

### 2.3 Security: Nonce Verification, Sanitization, Escaping

#### HIGH: Missing `wp_unslash()` on `$_POST` / `$_GET` data

**Files:** Multiple  
**Standard:** [Data Validation - wp_unslash](https://developer.wordpress.org/apis/security/data-validation/#input-validation)

WordPress adds magic quotes to superglobals. All `$_POST` and `$_GET` values must be passed through `wp_unslash()` before use.

Affected locations:

- `MetaBoxManager.php:174` -- `$raw = $_POST[$fieldId] ?? '';`
- `MetaBoxManager.php:138` -- `$_POST[$nonceField]`
- `TaxonomyMetaManager.php:95` -- `$raw = $_POST[$field['id']] ?? '';`
- `UserMetaManager.php:65` -- `$raw = $_POST[$field['id']] ?? '';`
- `AdminUI.php:69` -- `$_GET['action']` (sanitized but not unslashed)
- `BulkOperations.php:104-109` -- Multiple `$_POST` reads

**Impact:** Saved data may contain unwanted backslashes. Nonce verification may fail on values containing quotes.

---

#### HIGH: Missing capability check in `TaxonomyMetaManager::saveFields()`

**File:** `src/Core/TaxonomyMetaManager.php`, line 84

```php
public function saveFields(int $termId): void {
    if (!isset($_POST['cmb_taxonomy_nonce']) || !wp_verify_nonce(...)) {
        return;
    }
    // NO capability check here
    foreach ($this->taxonomyBoxes as $fields) { ... }
}
```

Unlike `UserMetaManager::saveFields()` which correctly checks `current_user_can('edit_user', $userId)`, the taxonomy save handler only verifies the nonce but does not check if the user has the `manage_categories` (or equivalent) capability.

**Standard:** [Checking User Capabilities](https://developer.wordpress.org/apis/security/checking-user-capabilities/)

**Impact:** Any authenticated user who can trigger the form submission could modify taxonomy meta.

---

#### HIGH: Imported field configurations stored without deep sanitization

**File:** `src/Core/AdminUI.php`, line 1158

```php
'fields' => $box['fields'] ?? [],
```

During import, field definitions from uploaded JSON files are stored in `wp_options` with only the top-level `title`, `postTypes`, `context`, and `priority` sanitized. The nested `fields` array (which can contain arbitrary data including malicious HTML in labels, descriptions, option values, etc.) is stored as-is.

When these fields are later rendered, the label and description are escaped with `esc_html()`, but:
- Field IDs are used as HTML `name` attributes and meta keys -- malicious IDs could cause injection
- Option keys/values in select/radio fields are stored without sanitization
- Custom `sanitize_callback` strings could be stored and later called via `call_user_func`

**Impact:** Stored XSS or arbitrary code execution if an admin imports a malicious JSON file.

---

#### MEDIUM: Nonce field name collision potential

**File:** `src/Core/MetaBoxManager.php`, lines 90, 138

Nonce fields use the pattern `cmb_nonce_{$id}` where `$id` is user-supplied. If a meta box ID contains special characters, the nonce field name could conflict with other fields.

**Impact:** Low probability but could cause save failures.

---

#### MEDIUM: Direct `file_get_contents()` usage on uploaded file

**File:** `src/Core/AdminUI.php`, line 1125

```php
$json = file_get_contents($_FILES['cmb_import_file']['tmp_name']);
```

While the file is from `$_FILES` tmp directory, WordPress provides `WP_Filesystem` for file operations. The import also doesn't check the file extension or MIME type.

**Standard:** [WordPress Filesystem API](https://developer.wordpress.org/apis/filesystem/)

---

#### MEDIUM: Validation rule `pattern` allows unescaped regex from config

**File:** `src/Core/Contracts/Abstracts/AbstractField.php`, line 94

```php
case 'pattern':
    if ($ruleParam !== null && $value !== '' && !preg_match('/' . $ruleParam . '/', (string)$value)) {
```

User-supplied regex pattern is used directly in `preg_match()` without `preg_quote()` or validation. Malformed patterns could cause PHP warnings or ReDoS.

**Impact:** PHP warnings on invalid regex; potential denial of service with crafted patterns.

---

### 2.4 Asset Enqueueing

#### HIGH: Admin scripts and styles loaded on ALL admin pages

**File:** `src/Core/Plugin.php`, lines 25-31

```php
add_action('admin_enqueue_scripts', function () {
    $baseUrl = plugin_dir_url(...);
    wp_enqueue_style('cmb-style', $baseUrl . 'assets/cmb-style.css');
    wp_enqueue_script('cmb-script', $baseUrl . 'assets/cmb-script.js', ['jquery', 'jquery-ui-sortable'], null, true);
    if (function_exists('wp_enqueue_media')) {
        wp_enqueue_media();
    }
});
```

The `admin_enqueue_scripts` callback does not check the `$hook_suffix` parameter to restrict loading to relevant pages. This means:
- CSS and JS are loaded on every single admin page
- `wp_enqueue_media()` is called on every admin page (this is expensive)
- jQuery UI Sortable is loaded everywhere

**Standard:** [Plugin Handbook - Enqueuing](https://developer.wordpress.org/plugins/javascript/enqueuing/) -- "Only add scripts and styles on the pages where they are needed."

**Impact:** Performance degradation across the entire admin area. Potential CSS/JS conflicts with other plugins.

---

#### MEDIUM: No version parameter for cache busting on main assets

**File:** `src/Core/Plugin.php`, lines 27-28

```php
wp_enqueue_style('cmb-style', $baseUrl . 'assets/cmb-style.css');
wp_enqueue_script('cmb-script', $baseUrl . 'assets/cmb-script.js', ['jquery', 'jquery-ui-sortable'], null, true);
```

Both the style and script use `null` as the version parameter. This means WordPress will append its own version, which does not change when plugin assets are updated.

The AdminUI assets correctly use `'2.0.0'` as the version, but the main plugin assets do not.

**Standard:** Best practice is to use the plugin version or file modification time.

---

#### MEDIUM: Gutenberg script path construction is fragile

**File:** `src/Core/GutenbergPanel.php`, line 57

```php
plugin_dir_url(dirname(__DIR__, 1) . '/../custom-meta-box-builder.php')
```

This relies on a specific directory structure and the main plugin filename. A rename or reorganization would break it silently.

**Standard:** Use `plugin_dir_url(__FILE__)` or define a constant in the main plugin file.

---

### 2.5 WordPress Coding Standards (PHP)

#### HIGH: Method naming violates WordPress standards

**Standard:** [WordPress PHP Coding Standards - Naming Conventions](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#naming-conventions) -- method names should use `snake_case`.

Violations:

| File | Method |
|------|--------|
| `Plugin.php` | `registerAssets()` |
| `MetaBoxManager.php` | `addMetaBoxes()`, `saveMetaBoxData()`, `deletePostMetaData()`, `copyMetaToRevision()`, `restoreMetaFromRevision()`, `showValidationErrors()`, `registerRestFields()`, `resolveFieldClass()`, `sanitizeFieldValue()`, `sanitizeGroupValue()`, `validateFieldConfigs()`, `validateSingleFieldConfig()`, `getRestType()` |
| `AdminUI.php` | `addAdminPage()`, `renderPage()`, `renderListPage()`, `renderEditPage()`, `handleSave()`, `handleDelete()`, `handleDuplicate()`, `handleToggle()`, `handleExport()`, `handleImport()`, `registerSavedBoxes()`, `renderFieldRow()`, `renderSubFieldRow()`, `renderFieldTypePicker()`, `renderImportModal()`, `renderNotices()`, `transformFieldsForRegistration()`, `getFieldTypeCategories()`, `getFieldTypesFlat()`, `getPostTypeIcon()`, `getTaxonomyList()`, `getRoleList()` |
| `FieldRenderer.php` | `getname()` (also inconsistent casing), `getChildPrefix()`, `generateHtmlId()`, `renderMultilingualField()`, `isMultilingual()`, `getFieldLocales()`, `getCurrentLocale()`, `getLocalizedKey()`, `renderLanguageTabs()`, `closeLanguageTabs()` |
| `GutenbergPanel.php` | `enqueueEditorAssets()`, `fieldToJsConfig()` |
| `OptionsManager.php` | `addMenuPages()`, `registerSettings()`, `renderPage()` |
| `DependencyGraph.php` | `addAdminPage()`, `renderPage()`, `extractDependencies()`, `renderGraph()`, `renderFieldList()`, `getDependencyData()` |
| `BulkOperations.php` | `addAdminPage()`, `renderPage()`, `handleBulkUpdate()`, `bulkSet()`, `bulkDelete()`, `flattenFields()` |
| `WpCliCommands.php` | `listBoxes()`, `getField()`, `setField()` |
| `AbstractField.php` | `getName()`, `getId()`, `getLabel()`, `getValue()`, `renderAttributes()`, `isRequired()`, `requiredAttr()` |

Note: `FieldRenderer.php` has `getname()` (no camelCase on "name") which is also inconsistent with its own naming convention.

**Impact:** Fails PHPCS with WordPress-Core ruleset. Inconsistency reduces maintainability.

---

#### MEDIUM: Missing file-level docblocks

**Standard:** [WordPress PHP Documentation Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#php-documentation-standards)

Most files lack the file-level `@package` docblock. Files that do have class docblocks are inconsistent (some have them, some don't).

Files missing class docblocks: `Plugin.php`, `MetaBoxManager.php`, `FieldRenderer.php`, `OptionsManager.php`, `UserMetaManager.php`, `TaxonomyMetaManager.php`, all Field classes.

---

#### MEDIUM: Use of `compact()` is discouraged

**File:** `src/Core/MetaBoxManager.php`, line 54; `src/Core/OptionsManager.php`, line 8

```php
$this->metaBoxes[$id] = compact('title', 'postTypes', 'fields', 'context', 'priority');
```

**Standard:** WordPress Coding Standards discourage `compact()` due to reduced readability and IDE refactoring issues. Explicit array construction is preferred.

---

#### MEDIUM: Yoda conditions not used

**Standard:** [WordPress PHP Coding Standards - Yoda Conditions](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#yoda-conditions)

Throughout the codebase, comparisons use `$variable === 'value'` instead of `'value' === $variable`. Examples:

- `MetaBoxManager.php:132` -- `if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;`
- `AdminUI.php:72` -- `if ($action === 'new' || $action === 'edit')`
- `BulkOperations.php:94` -- `if (empty($_POST['cmb_bulk_submit']))`

---

#### LOW: Single-line `if` statements without braces

**Standard:** [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#brace-style)

Multiple instances of single-line returns without braces:

- `MetaBoxManager.php:132` -- `if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;`
- `MetaBoxManager.php:133` -- `if (!current_user_can('edit_post', $postId)) return;`
- `TaxonomyMetaManager.php:26` -- `if (empty($fields)) return;`
- `UserMetaManager.php:19` -- `if (empty($this->fields)) return;`

---

#### LOW: Mixed use of single quotes and double quotes

Throughout the codebase, there is inconsistent use of quote styles. WordPress standard prefers single quotes for strings that don't need variable interpolation.

---

### 2.6 Hooks Usage

#### MEDIUM: Hooks registered in constructor/boot without priority control

**File:** `src/Core/Plugin.php`

The `boot()` method directly calls `register()` on multiple classes without using a WordPress hook for initialization. This creates a rigid load order.

```php
public function boot(): void {
    $this->registerAssets();
    $manager = MetaBoxManager::instance();
    $manager->register();
    WpCliCommands::register();
    // ...
}
```

**Recommendation:** Initialize on `plugins_loaded` with a defined priority, and use `init` for registration of post types, taxonomies, and meta.

---

#### MEDIUM: Filter hook `cmb_field_html` returns unescaped output from external filters

**File:** `src/Core/FieldRenderer.php`, line 155

```php
$output = apply_filters('cmb_field_html', $output, $field, $this->post);
```

The filtered output is echoed directly without re-escaping. If a third-party callback introduces unsafe HTML, it will be rendered.

**Impact:** Third-party filters can inject arbitrary HTML. This is standard WordPress behavior for filters, but the documentation should warn filter users about escaping responsibility.

---

#### LOW: Hook naming convention

Custom hooks use `cmb_` prefix consistently, which is good. However, WordPress coding standards recommend using the plugin slug as prefix to avoid conflicts. `cmb` is generic and could conflict with other plugins (e.g., CMB2, a popular existing plugin).

**Recommendation:** Use a more unique prefix like `custom_meta_box_builder_` or `cmbbuilder_`.

---

### 2.7 Database Interactions

#### MEDIUM: `delete_post_meta()` then `add_post_meta()` pattern is not atomic

**File:** `src/Core/MetaBoxManager.php`, lines 201-208

```php
delete_post_meta($postId, $fieldId);
if (is_array($sanitized)) {
    foreach ($sanitized as $s) {
        add_post_meta($postId, $fieldId, $s);
    }
} else {
    update_post_meta($postId, $fieldId, $sanitized);
}
```

If the process crashes between `delete_post_meta()` and `add_post_meta()`, data is lost. A safer approach would be to use a transaction or compare-and-swap with `update_post_meta()`.

---

#### MEDIUM: Bulk operations with `posts_per_page => -1`

**File:** `src/Core/BulkOperations.php`, line 121

```php
$posts = get_posts([
    'post_type' => $postType,
    'posts_per_page' => -1,
    'fields' => 'ids',
    'post_status' => 'any',
]);
```

Using `-1` fetches all posts of a type, which can cause memory exhaustion on large sites.

**Impact:** Out-of-memory errors on sites with tens of thousands of posts.

---

#### LOW: No use of `$wpdb->prepare()` (positive finding)

The plugin correctly uses WordPress API functions (`get_post_meta`, `update_post_meta`, `get_option`, `update_option`) instead of raw `$wpdb` queries. This is good practice.

---

### 2.8 JavaScript Standards

#### MEDIUM: jQuery dependency for core functionality

**Files:** `assets/cmb-script.js`, `assets/cmb-admin.js`

Both scripts depend on jQuery. While acceptable, modern WordPress development encourages vanilla JS or `@wordpress/scripts` tooling for new plugins.

---

#### MEDIUM: HTML concatenation without escaping in JS

**File:** `assets/cmb-script.js`, lines 203-207

```javascript
$preview.html('<img src="' + attachment.sizes.thumbnail.url + '" ...>');
$preview.html('<a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a>');
```

**File:** `assets/cmb-admin.js`, lines 370+ (field template HTML construction)

Field template HTML is built via string concatenation. While the data comes from WordPress internal APIs (media library, localized config), the pattern does not escape HTML entities in values like filenames or labels.

**Impact:** If an attachment filename contains `<script>`, it could be rendered. Low probability since WP sanitizes filenames, but the pattern is unsafe.

**Recommendation:** Use `jQuery.text()` for text content or build DOM nodes programmatically.

---

#### LOW: `var` used instead of `const`/`let` in Gutenberg JS

**File:** `assets/cmb-gutenberg.js`

Uses `var` declarations throughout, which is acceptable but outdated. The block editor ecosystem uses ES6+ (`const`/`let`).

---

#### LOW: No source maps or build process for JS

JavaScript files are written as raw, unminified source. For production, these should be minified and source maps provided for debugging.

---

### 2.9 CSS Standards

#### LOW: `!important` used in styles

**File:** `assets/cmb-style.css`, lines 292-303

```css
.cmb-remove-row:hover {
    background-color: #fcecec !important;
    color: #cc1818 !important;
}
```

`!important` overrides indicate specificity issues. These should be resolved with more specific selectors.

---

#### LOW: Inline styles in PHP output

Multiple PHP files output inline `style` attributes:

- `DependencyGraph.php:51` -- `style="background:#f9f9f9;border:1px solid #ddd;..."`
- `DependencyGraph.php:93` -- `style="margin:8px 0;padding:8px;..."`
- `FieldRenderer.php:103` -- `style="display:none"`

**Recommendation:** Move to CSS classes for better maintainability and CSP compliance.

---

### 2.10 Additional WordPress.org Plugin Review Requirements

#### HIGH: No `readme.txt` file

**Standard:** [WordPress.org Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/)

A `readme.txt` file in WordPress standard format is mandatory for plugin directory submission. It must include: description, installation instructions, changelog, FAQ, etc.

---

#### MEDIUM: Placeholder author name

**File:** `custom-meta-box-builder.php`

```php
 * Author: Your Name
```

This is a placeholder and should be replaced with the actual author information.

---

#### MEDIUM: No license file

The `composer.json` declares `GPL-2.0-or-later` but there is no `LICENSE` or `LICENSE.txt` file in the plugin root. WordPress.org requires GPL-compatible licensing.

---

#### MEDIUM: `vendor/` directory included

The plugin ships with `vendor/` directory (includes phpunit and dependencies). For distribution:
- Dev dependencies should not be included in production builds
- The `vendor/autoload.php` dependency means `composer install` must be run, or vendor must be committed

---

---

## 3. Summary Table

| # | Severity | Category | Finding | File(s) |
|---|----------|----------|---------|---------|
| 1 | Critical | Structure | No `uninstall.php` | (missing) |
| 2 | Critical | Structure | No activation/deactivation hooks | `custom-meta-box-builder.php` |
| 3 | Critical | Structure | Missing required plugin headers | `custom-meta-box-builder.php` |
| 4 | Critical | i18n | Zero translatable strings | All PHP files |
| 5 | Critical | i18n | No text domain declared or loaded | `custom-meta-box-builder.php` |
| 6 | High | Security | Missing `wp_unslash()` on superglobals | Multiple files |
| 7 | High | Security | Missing capability check in taxonomy save | `TaxonomyMetaManager.php` |
| 8 | High | Security | Unsanitized field data in import | `AdminUI.php` |
| 9 | High | Structure | No ABSPATH guard in `public-api.php` | `public-api.php` |
| 10 | High | Structure | Plugin boots at include time, not on hook | `custom-meta-box-builder.php` |
| 11 | High | Assets | Scripts/styles loaded on all admin pages | `Plugin.php` |
| 12 | High | Standards | Method naming violates WordPress conventions | All PHP files |
| 13 | High | Structure | No `readme.txt` | (missing) |
| 14 | High | Assets | `wp_enqueue_media()` on every admin page | `Plugin.php` |
| 15 | Medium | Security | Nonce field name collision potential | `MetaBoxManager.php` |
| 16 | Medium | Security | Direct `file_get_contents()` on upload | `AdminUI.php` |
| 17 | Medium | Security | Unvalidated regex pattern in validation | `AbstractField.php` |
| 18 | Medium | Assets | No version parameter on main assets | `Plugin.php` |
| 19 | Medium | Assets | Fragile path construction for Gutenberg | `GutenbergPanel.php` |
| 20 | Medium | Standards | Missing file/class docblocks | Multiple files |
| 21 | Medium | Standards | `compact()` usage | `MetaBoxManager.php`, `OptionsManager.php` |
| 22 | Medium | Standards | No Yoda conditions | Multiple files |
| 23 | Medium | Hooks | No priority control on initialization | `Plugin.php` |
| 24 | Medium | Hooks | Unescaped output from `cmb_field_html` filter | `FieldRenderer.php` |
| 25 | Medium | Database | Non-atomic delete+add meta pattern | `MetaBoxManager.php` |
| 26 | Medium | Database | `posts_per_page => -1` in bulk ops | `BulkOperations.php` |
| 27 | Medium | JS | HTML concatenation without escaping | `cmb-script.js`, `cmb-admin.js` |
| 28 | Medium | Structure | Placeholder author, no LICENSE file | `custom-meta-box-builder.php` |
| 29 | Medium | Structure | `vendor/` with dev dependencies | Root directory |
| 30 | Low | Standards | Single-line if without braces | Multiple files |
| 31 | Low | Standards | Inconsistent quote usage | Multiple files |
| 32 | Low | Hooks | Generic `cmb_` prefix may conflict with CMB2 | Multiple files |
| 33 | Low | JS | `var` instead of `const`/`let` in Gutenberg | `cmb-gutenberg.js` |
| 34 | Low | JS | No minification or source maps | `assets/*.js` |
| 35 | Low | CSS | `!important` usage | `cmb-style.css` |
| 36 | Low | CSS | Inline styles in PHP output | `DependencyGraph.php`, `FieldRenderer.php` |

---

## 4. Positive Findings

The following aspects are implemented correctly and deserve recognition:

1. **Sanitization on save:** Every field type implements a `sanitize()` method with appropriate WordPress sanitization functions (`sanitize_text_field`, `sanitize_email`, `esc_url_raw`, `wp_kses_post`, `absint`, etc.)
2. **Output escaping in field rendering:** Field output consistently uses `esc_attr()`, `esc_html()`, `esc_url()`, `esc_textarea()`, and WordPress helpers like `selected()` and `checked()`.
3. **Nonce verification:** Present in all major form handlers (`MetaBoxManager::saveMetaBoxData`, `AdminUI::handleSave/Delete/Duplicate/Toggle/Import`, `BulkOperations::handleBulkUpdate`, `UserMetaManager::saveFields`, `TaxonomyMetaManager::saveFields`).
4. **Capability checks:** Present in most handlers (`edit_post`, `manage_options`, `edit_user`). Only `TaxonomyMetaManager` is missing one.
5. **No direct SQL queries:** All database interactions use WordPress API functions.
6. **Proper use of `wp_safe_redirect()` followed by `exit`:** All redirect flows correctly terminate execution.
7. **Proper use of `wp_nonce_url()` for action links:** Admin action links correctly include nonces.
8. **Conditional loading of WP-CLI commands:** `WpCliCommands::register()` correctly checks `defined('WP_CLI')`.
9. **AdminUI assets are conditionally loaded:** The admin builder page correctly checks `$hookSuffix` (line 36).

---

## 5. Priority Remediation Roadmap

### Phase 1 (Blockers -- Must fix before any release)
1. Add `Text Domain` header and wrap all user-facing strings with `__()` / `esc_html__()` / `esc_attr__()`
2. Call `load_plugin_textdomain()` in `plugins_loaded` hook
3. Create `uninstall.php` to clean up `cmb_admin_configurations` option
4. Add `register_activation_hook()` / `register_deactivation_hook()`
5. Add missing plugin header fields
6. Add `defined('ABSPATH') || exit;` to `public-api.php`
7. Add capability check in `TaxonomyMetaManager::saveFields()`

### Phase 2 (Security & Performance)
8. Add `wp_unslash()` to all `$_POST`/`$_GET` reads
9. Sanitize imported field configurations deeply
10. Restrict asset loading to relevant admin screens
11. Add version parameters to enqueued assets

### Phase 3 (Standards Compliance)
12. Rename methods to `snake_case` per WordPress coding standards
13. Add file-level and class docblocks
14. Add `readme.txt`
15. Add `LICENSE` file
16. Use Yoda conditions
17. Adopt proper brace style

---

*End of audit report.*
