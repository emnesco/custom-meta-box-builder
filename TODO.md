# Custom Meta Box Builder — TODO

**Based on:** v2.1 Re-Audit (2026-04-06)
**Sources:** AUDIT_SECURITY.md, AUDIT_ARCHITECTURE.md, AUDIT_PERFORMANCE.md, AUDIT_WP_STANDARDS.md, AUDIT_FRONTEND.md, AUDIT_FEATURE_GAP.md
**Legend:** `[ ]` = pending, `[x]` = done, `[~]` = partial/in-progress

---

## Audit Scorecards (Current → Target)

| Area | Current | Target |
|---|---|---|
| Security | 0C/1H/10M/9L | 0C/0H/0M |
| Architecture | 5.9/10 | 8/10 |
| Performance | 6.4/10 | 8/10 |
| WP Standards | 6.7/10 | 9/10 |
| Frontend/A11y | 7.0/10 | 9/10 |
| Feature Parity (ACF) | 65% | 85% |
| WCAG 2.1 AA | PARTIAL FAIL | PASS |
| WP.org Ready | 3 blockers | 0 blockers |

---

## Phase 1: WordPress.org Blockers (P0)

> These 3 items block WordPress.org plugin directory submission.

### 1.1 ABSPATH Guards on All `src/` Files
- [ ] Add `defined( 'ABSPATH' ) || exit;` to all 66 PHP files in `src/`
- **Ref:** WPS-C01
- **Effort:** Trivial (script-able)
- **Files:** All `src/**/*.php`

### 1.2 i18n: Wrap All Hardcoded Strings
- [ ] Wrap ~80+ hardcoded strings with `__()` / `esc_html__()` using text domain `custom-meta-box-builder`
- [ ] `ListPage.php` — "Add New", "Title", "Post Types", "Actions", "Edit", "Delete", "Duplicate"
- [ ] `EditPage.php` — "Field Type", "Field ID", "Label", "Required", "Add Field", "Save Meta Box"
- [ ] `FieldRenderer.php` — "Search...", "Select...", "No results found"
- [ ] `FlexibleContentField.php` — "Add Layout", "Remove", "Collapse"
- [ ] `FrontendForm.php` — "Submit", "Processing..."
- [ ] `BlockRegistration.php` — block labels and descriptions
- [ ] Generate `.pot` file for translators
- **Ref:** WPS-C03, WPS-H01, WPS-L01
- **Effort:** Medium (3-4 hours)

### 1.3 WP_Filesystem for All File I/O
- [ ] Refactor `LocalJson::saveToFile()` to use `$wp_filesystem->put_contents()`
- [ ] Refactor `LocalJson::syncFromFiles()` to use `$wp_filesystem->get_contents()` and `$wp_filesystem->dirlist()`
- [ ] Replace all `json_encode()` with `wp_json_encode()`
- **Ref:** WPS-C02, WPS-C04, WPS-H05
- **Effort:** Small (2-3 hours)

---

## Phase 2: Security Hardening (P0–P1)

### 2.1 P0 — Must Fix Before Production

- [ ] **SEC-R01:** Deep recursive sanitization on imported field configurations
  - File: `src/Core/AdminUI/ActionHandler.php`
  - Recursive `sanitize_text_field()` on all nested string values in imported field arrays
  - Effort: Medium

- [ ] **SEC-N02:** Add capability checks to AJAX search endpoints
  - File: `src/Core/AjaxHandler.php`
  - Add `current_user_can('edit_posts')` to `cmb_search_posts`
  - Add `current_user_can('list_users')` to `cmb_search_users`
  - Add `current_user_can('edit_posts')` to `cmb_search_terms`
  - Effort: Small

- [ ] **SEC-N03:** Set proper REST API `auth_callback`
  - File: `src/Core/MetaBoxManager.php`
  - Set `auth_callback` to check `current_user_can('edit_post', $post_id)`
  - Effort: Small

### 2.2 P1 — Important Security Fixes

- [ ] **SEC-N05:** Validate attachment IDs on frontend form submissions
  - File: `src/Core/FrontendForm.php`
  - Verify attachment exists and belongs to current user
  - Effort: Small

- [ ] **SEC-N06:** Sanitize meta box ID in LocalJson file path construction
  - File: `src/Core/LocalJson.php`
  - Apply `sanitize_file_name()` to prevent path traversal
  - Effort: Trivial

- [ ] **SEC-N07:** Validate `render_template` path in BlockRegistration
  - File: `src/Core/BlockRegistration.php`
  - Use `realpath()` + `str_starts_with()` to ensure path is within theme/plugin
  - Effort: Small

- [ ] **SEC-N09:** Include post ID in frontend form nonce action
  - File: `src/Core/FrontendForm.php`
  - Change to `'cmb_frontend_save_' . $metaBoxId . '_' . $postId`
  - Effort: Trivial

- [ ] **SEC-N10:** Validate FlexibleContent layout type names on save
  - File: `src/Fields/FlexibleContentField.php`
  - Validate against `array_keys($this->config['layouts'])`
  - Effort: Trivial

### 2.3 P2 — Low-Priority Security

- [ ] **SEC-N01:** Guard against CRLF injection in export Content-Disposition header
  - File: `src/Core/ImportExport.php`
  - Add `str_replace(["\r", "\n"], '', $filename)`
  - Effort: Trivial

- [ ] **SEC-N04:** Validate JSON import schema (require `meta_boxes`, `version` keys)
  - File: `src/Core/AdminUI/ActionHandler.php`
  - Effort: Small

- [ ] **SEC-N08:** Add `show_in_graphql` config option with permission checks
  - File: `src/Core/GraphQLIntegration.php`
  - Respect `post_status` in resolve callbacks
  - Effort: Medium

- [ ] **SEC-L01:** Remove `@` suppression on `preg_match` — use return value check
  - File: `src/Core/AbstractField.php`

- [ ] **SEC-L02:** Remove `@` suppression on `filemtime` — use `file_exists()` guard
  - File: `src/Core/Plugin.php`

- [ ] **SEC-L03:** Add rate limiting on AJAX search endpoints
  - File: `src/Core/AjaxHandler.php`

- [ ] **SEC-L04:** Add `autocomplete="off"` to password field
  - File: `src/Fields/PasswordField.php`

- [ ] **SEC-L08:** Replace jQuery `.html()` with safe DOM manipulation
  - File: `assets/cmb-script.js`

---

## Phase 3: Critical Architecture Fixes

### 3.1 God Class / Method Decomposition

- [ ] **ARCH-C01:** Split ActionHandler (~550 lines) into:
  - `ImportExportHandler` — import/export logic
  - `BulkActionHandler` — bulk operations
  - `MetaBoxRegistrar` — meta box registration
  - Target: each handler <200 lines
  - Effort: Medium

- [ ] **ARCH-C02:** Extract from `handleSave()` (~154 lines):
  - `validateConfig()` — field validation
  - `assembleConfig()` — config assembly
  - `persistConfig()` — option update + hooks
  - Effort: Small

### 3.2 Dependency & Pattern Fixes

- [ ] **ARCH-C03:** Break circular dependency GroupField → FieldRenderer → FieldFactory → GroupField
  - Introduce `FieldRendererInterface` and inject into GroupField
  - Effort: Medium

- [ ] **ARCH-C04:** Remove error suppression operators
  - Replace `@preg_match` in `AbstractField.php` with return value check
  - Replace `@filemtime` in `Plugin.php` with `file_exists()` guard
  - Effort: Trivial

- [ ] **ARCH-C05:** Validate `render_template` include path in BlockRegistration
  - Use `realpath()` + directory check
  - Effort: Small

- [ ] **ARCH-C06:** Convert static modules to instance classes + ServiceProvider
  - `LocalJson.php` → instance class + `LocalJsonProvider`
  - `GraphQLIntegration.php` → instance class + `GraphQLProvider`
  - `BlockRegistration.php` → instance class + `BlockProvider`
  - `FrontendForm.php` → instance class + `FrontendProvider`
  - Effort: Medium

### 3.3 High-Priority Architecture

- [ ] **ARCH-H01:** Fix naming — rename `Checkbox_listField` → `CheckboxListField`
  - Update `FieldFactory::$typeAliases` accordingly
  - Effort: Small

- [ ] **ARCH-H02:** Create interfaces for new modules
  - `FrontendFormInterface`, `BlockRegistrationInterface`, `GraphQLInterface`, `LocalJsonInterface`
  - Effort: Small

- [ ] **ARCH-H03:** Add `_deprecated_hook()` on legacy `cmb_` prefix
  - File: `src/Core/FieldUtils.php`
  - Add `CMBB_LEGACY_HOOKS` constant to disable
  - Plan removal for v3.0
  - Effort: Small

- [ ] **ARCH-H04:** Remove `MetaBoxManager::setInstance()` / `::instance()` static accessors
  - Migrate all callers to use Plugin DI
  - Effort: Medium

- [ ] **ARCH-H05:** Add try/catch error boundaries in field rendering/saving
  - Log errors with `_doing_it_wrong()` instead of crashing
  - Effort: Small

- [ ] **ARCH-H06:** Extract `AbstractMetaManager` from TaxonomyMeta/UserMeta/OptionsManager
  - Eliminate ~60% duplicated code
  - Effort: Medium

---

## Phase 4: Performance Optimization

### 4.1 Critical Performance

- [ ] **PERF-C01:** Fix static cache key collisions in relational fields
  - Files: `PostField.php`, `TaxonomyField.php`, `UserField.php`
  - Use `md5(serialize($queryArgs))` as cache key
  - Effort: Small

- [ ] **PERF-C02:** Defer config loading in `registerSavedBoxes()`
  - File: `src/Core/AdminUI/ActionHandler.php`
  - Only load config when meta box screen detected
  - Effort: Small

- [ ] **PERF-C03:** Cache LocalJson file sync with transient (5-min TTL)
  - File: `src/Core/LocalJson.php`
  - Only rescan when transient expires or config saved
  - Effort: Small

- [ ] **PERF-C04:** Add `CMBB_LEGACY_HOOKS` constant to disable dual-prefix overhead
  - File: `src/Core/FieldUtils.php`
  - Default: `true` (backward compat), set `false` for performance
  - Effort: Small

### 4.2 High Performance

- [ ] **PERF-H01:** Fix N+1 in GroupField sub-field rendering
  - Use `get_post_meta($postId)` (no key) to bulk-fetch all meta
  - Resolve sub-field values from cached result
  - Effort: Medium

- [ ] **PERF-H02:** Remove redundant `get_post_meta()` pre-check before `update_post_meta()`
  - File: `src/Core/MetaBoxManager.php`
  - WordPress handles "don't update if same" internally
  - Effort: Trivial

### 4.3 Medium Performance

- [ ] **PERF-M01:** Increase conditional debounce to 250ms for complex chains
  - File: `assets/cmb-script.js`

- [ ] **PERF-M02:** Cache DOM selectors in conditional evaluation
  - File: `assets/cmb-script.js`

- [ ] **PERF-M03:** Chunk BulkOperations into batches
  - File: `src/Core/BulkOperations.php`

- [ ] **PERF-M05:** Batch GraphQL resolve callbacks
  - File: `src/Core/GraphQLIntegration.php`
  - Use single `get_post_meta($postId)` call for all fields

- [ ] **PERF-M06:** FrontendForm: only enqueue assets for field types actually used
  - File: `src/Core/FrontendForm.php`

- [ ] **PERF-M07:** Set `autoload=false` on initial option creation (not just update)
  - File: `src/Core/AdminUI/ActionHandler.php`

---

## Phase 5: Accessibility / WCAG 2.1 AA (P0)

> 4 criteria currently failing, 2 partial. Target: full WCAG 2.1 AA compliance.

### 5.1 Critical (Currently Failing)

- [ ] **FE-C01:** Add ARIA roles to language tabs (`role="tablist"`, `role="tab"`, `role="tabpanel"`)
  - File: `src/Core/Traits/MultiLanguageTrait.php`
  - WCAG 4.1.2

- [ ] **FE-C02:** Remove inline `oninput` from RangeField — move to enqueued JS
  - File: `src/Fields/RangeField.php`
  - CSP compliance

- [ ] **FE-C03:** Add `alt` attributes to all image previews
  - Files: `ImageField.php`, `GalleryField.php`, `FileField.php`
  - WCAG 1.1.1

- [ ] **FE-C04:** Remove inline `onclick` handlers from admin pages — move to enqueued JS
  - File: `src/Core/AdminUI/ListPage.php`
  - Use `data-action="delete"` + JS handler with `wp.i18n.__()`
  - CSP compliance

### 5.2 High (Partially Failing)

- [ ] **FE-H01:** Fix label associations on FlexibleContent cloned sub-fields
  - File: `src/Fields/FlexibleContentField.php`
  - WCAG 3.3.2

- [ ] **FE-H02:** Replace `.innerHTML` cloning with `<template>.content.cloneNode(true)`
  - File: `assets/cmb-script.js`
  - XSS prevention + DOM safety

- [ ] **FE-H03:** Add `aria-invalid="true"` and `aria-describedby` to validated fields
  - Files: all field types + `assets/cmb-script.js`
  - WCAG 3.3.1

- [ ] **FE-H04:** Add keyboard navigation for group/repeater row controls
  - Files: `GroupField.php`, `assets/cmb-script.js`
  - Add up/down arrow buttons as keyboard alternative to drag handle
  - WCAG 2.1.1

- [ ] **FE-H06:** Add Escape key handler to close modal dialogs
  - Files: `assets/cmb-admin.js`, `assets/cmb-script.js`
  - WCAG 2.1.2

- [ ] **FE-H07:** Add ARIA `role="listbox"` / `role="option"` to FlexibleContent layout picker
  - File: `assets/cmb-script.js`
  - Add arrow key navigation

### 5.3 Medium Accessibility

- [ ] **FE-M02:** Add `prefers-reduced-motion` media query
  - File: `assets/cmb-style.css`

- [ ] **FE-M03:** Implement focus trap in modal dialogs
  - File: `assets/cmb-admin.js`

- [ ] **FE-M04:** Add keyboard alternative for gallery drag-and-drop
  - File: `assets/cmb-script.js`

- [ ] **FE-M06:** Add `loading="lazy"` on image previews
  - Files: `ImageField.php`, `GalleryField.php`

---

## Phase 6: WordPress Coding Standards

### 6.1 High Priority

- [ ] **WPS-H02:** Move inline JS confirmation to enqueued JS
  - File: `src/Core/AdminUI/ListPage.php`
  - Use `wp.i18n.__()` for translatable confirmation messages

- [ ] **WPS-H03:** Convert ~200+ comparisons to Yoda conditions
  - Files: throughout codebase
  - Effort: Medium (bulk find-replace + manual review)

- [ ] **WPS-H04:** Remove `@` error suppression operators
  - Files: `AbstractField.php`, `Plugin.php`

- [ ] **WPS-H06:** Add type-specific REST sanitize_callback for complex fields
  - File: `src/Core/MetaBoxManager.php`
  - Group, FlexibleContent need dedicated sanitizers

### 6.2 Medium Priority

- [ ] **WPS-M01:** Add transient caching for expensive operations
  - Files: `LocalJson.php`, `ActionHandler.php`

- [ ] **WPS-M04:** Use `object_subtype` parameter in `register_post_meta()`
  - File: `src/Core/MetaBoxManager.php`

- [ ] **WPS-M05:** Replace `wp_localize_script()` with `wp_add_inline_script()`
  - File: `src/Core/Plugin.php`

- [ ] **WPS-M07:** Auto-sync `readme.txt` version with plugin header
  - Files: `readme.txt`, `custom-meta-box-builder.php`

### 6.3 Low Priority

- [ ] **WPS-L02:** Set `"type": "wordpress-plugin"` in `composer.json`

---

## Phase 7: Missing Features — v2.2 Quick Wins

### 7.1 Formatted Value API (GAP-001)

- [ ] Add `format()` method to `FieldInterface`
- [ ] Implement `format()` in each field type:
  - ImageField → return `wp_get_attachment_url()` instead of ID
  - GalleryField → return array of attachment objects
  - FileField → return attachment array with URL, title, etc.
  - FlexibleContentField → return formatted nested arrays
  - GroupField → return formatted sub-field values
- [ ] Create `cmb_get_field_formatted()` public API function
- **Ref:** GAP-001
- **Effort:** Small (4-6 hours)

### 7.2 New Field Types (GAP-002)

- [ ] **LinkField** — URL, title, target picker (like ACF's link field)
  - Priority: P0, Effort: Small

- [ ] **ButtonGroupField** — radio-like with button UI, `aria-pressed`
  - Priority: P1, Effort: Small

- [ ] **oEmbedField** — URL input with `wp_oembed_get()` preview
  - Priority: P1, Effort: Medium

- [ ] **TabField** — group fields under inline tabs within a meta box
  - Priority: P1, Effort: Medium

- [ ] **AccordionField** — collapsible field groups
  - Priority: P2, Effort: Medium

- [ ] **GoogleMapField** — Maps API integration with lat/lng/zoom
  - Priority: P2, Effort: Large

- [ ] **CloneField** — reference existing field groups
  - Priority: P3, Effort: Large

### 7.3 Developer Hooks (GAP-006)

- [ ] Add per-field-type hooks:
  - `cmbbuilder_render_{type}` — customize rendering per type
  - `cmbbuilder_sanitize_{type}` — type-specific sanitization
  - `cmbbuilder_validate_{type}` — type-specific validation
  - `cmbbuilder_format_value` — field value formatting
  - `cmbbuilder_field_choices_{type}` — dynamic choice options
  - `cmbbuilder_pre_save_all` — before all fields saved
  - `cmbbuilder_post_save_all` — after all fields saved
  - `cmbbuilder_enqueue_scripts` — field-specific asset loading
- **Ref:** GAP-006
- **Effort:** Small (2-3 hours)

---

## Phase 8: Missing Features — v2.3

### 8.1 REST API Schema (GAP-003)

- [ ] Create JSON Schema definitions for complex types:
  - Group → Object type with property schema per sub-field
  - FlexibleContent → Array of Union types per layout
  - Gallery → Array of attachment objects (not comma-separated IDs)
- [ ] Expand `getRestType()` for all field types (range→number, toggle→boolean, etc.)
- [ ] Add `'rest_write' => false` config option for read-only fields
- **Effort:** Medium (6-8 hours)

### 8.2 GraphQL Types (GAP-004)

- [ ] Create custom GraphQL type definitions:
  - Groups → `ObjectType` with sub-field properties
  - FlexibleContent → Union types per layout
  - Gallery → array of attachment objects with URLs
  - File → attachment object (not just ID)
- [ ] Add mutation support for writable fields
- **Effort:** Medium (8-10 hours)

### 8.3 Gutenberg Sidebar Expansion (GAP-005)

- [ ] Add Gutenberg sidebar support for:
  - Group/repeater fields
  - Gallery field
  - WYSIWYG field
  - Multi-select field
  - Conditional logic within sidebar
- **Effort:** Large (20-30 hours)

### 8.4 WPML/Polylang Integration (GAP-007)

- [ ] Create `wpml-config.xml` for field registration
- [ ] Add Polylang `pll_register_string()` integration
- **Effort:** Medium (8-10 hours)

### 8.5 WP-CLI Expansion (GAP-008)

- [ ] Add commands: `export`, `import`, `delete`, `get-term`, `get-user`, `get-option`
- [ ] Add `--format=json|csv|table` output option
- [ ] Use WP-CLI's built-in `Formatter` for output
- **Effort:** Small (3-4 hours)

### 8.6 Frontend Form Improvements (GAP-009)

- [ ] AJAX form submission endpoint
- [ ] Test all field types in frontend context (groups, flexible content)
- [ ] Add `capability` parameter for access control
- [ ] File upload validation with user-facing messages
- **Effort:** Medium (6-8 hours)

### 8.7 LocalJson Improvements (GAP-010)

- [ ] Add `_modified` timestamp comparison for conflict detection
- [ ] Add admin notice when DB and JSON configs conflict
- [ ] Add bi-directional sync with conflict resolution UI
- **Effort:** Medium (4-6 hours)

---

## Phase 9: Architecture Medium-Priority

### 9.1 Code Quality

- [ ] **ARCH-M01:** Add `declare(strict_types=1)` to all PHP files
- [ ] **ARCH-M03:** Make `FieldFactory::$typeAliases` extensible via filter
- [ ] **ARCH-M07:** Split `FrontendForm::processSubmission()` — separate auth, save, redirect
- [ ] **ARCH-M08:** Fix GraphQL type mapping — complex types shouldn't map to String
- [ ] **ARCH-M09:** Add caching to `LocalJson::syncFromFiles()`
- [ ] **ARCH-M11:** Add abstract `format()` method contract on `AbstractField`
- [ ] **ARCH-M12:** Extract base search method in `AjaxHandler` to reduce 80% duplication
- [ ] **ARCH-M14:** Create `ImportExportInterface` for format-swappable import/export
- [ ] **ARCH-M15:** Use WP-CLI's `Formatter` in `WpCliCommands`
- [ ] **ARCH-M19:** Extract shared rendering logic between FlexibleContentField and GroupField

### 9.2 Build & Dist

- [ ] **ARCH-L04:** Add `AUDIT_*.md` and `TODO*.md` to `.distignore`
- [ ] **ARCH-L07:** Sync `readme.txt` changelog with `CHANGELOG.md`
- [ ] **ARCH-L08:** Configure git pre-commit hooks for code style

---

## Phase 10: v3.0 Major Features

- [ ] Full Gutenberg canvas/inline editing (not just sidebar)
- [ ] Custom database table support for high-performance storage
- [ ] Advanced conditional logic: nested conditionals, conditional validation
- [ ] Remove legacy `cmb_` hook prefix (after deprecation period)
- [ ] Clone/Reference field type
- [ ] Google Maps field type

---

## Phase 11: Low-Priority Polish

### Performance
- [ ] **PERF-L01:** Integrate `wp_cache_get/set` for config lookups
- [ ] **PERF-L02:** Replace `wp_localize_script()` with `wp_add_inline_script()`
- [ ] **PERF-L04:** Lazy load color picker / date picker assets
- [ ] **PERF-L05:** Enable tree-shaking in esbuild config

### Frontend
- [ ] **FE-M01:** Respect `prefers-color-scheme` in color picker
- [ ] **FE-M05:** Hide interactive controls in print stylesheet
- [ ] **FE-M07:** Remove `!important` CSS declarations
- [ ] **FE-L01:** Dark mode support for admin meta boxes
- [ ] **FE-L02:** Ensure touch targets ≥ 44px on mobile
- [ ] **FE-L03:** Add CSS transition on conditional field show/hide
- [ ] **FE-L04:** Remove `console.log()` debug statements from JS

### Architecture
- [ ] **ARCH-L01:** Fix inconsistent `@since` tags in PHPDoc
- [ ] **ARCH-L02:** Add `final` keyword on leaf classes
- [ ] **ARCH-L03:** Add `conflict` section to `composer.json`
- [ ] **ARCH-L06:** Add PHP 8.4 compatibility notes

### Security
- [ ] **SEC-L05:** Strip internal `_modified` timestamps from export JSON
- [ ] **SEC-L06:** Add audit trail logging to WP-CLI commands
- [ ] **SEC-L09:** Add subresource integrity to enqueued scripts

---

## Summary by Effort

| Effort | Count | Items |
|---|---|---|
| Trivial | 12 | ABSPATH guards, path sanitize, nonce fix, layout validation, CRLF guard, error suppression, etc. |
| Small | 28 | AJAX caps, REST auth, attachment validation, naming fix, hooks, cache keys, etc. |
| Medium | 22 | i18n strings, WP_Filesystem, ActionHandler split, circular deps, N+1 fix, REST schema, etc. |
| Large | 6 | Gutenberg expansion, Google Maps, Clone field, static→DI modules, etc. |
| **Total** | **68** | |

## Priority Matrix

| Priority | Count | Description |
|---|---|---|
| **P0 (WP.org Blockers)** | 3 | ABSPATH, i18n, WP_Filesystem |
| **P0 (Security)** | 3 | Import sanitization, AJAX caps, REST auth |
| **P1 (Security + A11y)** | 15 | Frontend security, WCAG critical/high failures |
| **P1 (Architecture)** | 6 | God class, circular deps, error boundaries |
| **P1 (Performance)** | 6 | Cache keys, config loading, N+1 |
| **P2 (Features)** | 12 | Field types, hooks, formatted API |
| **P2 (Standards)** | 10 | Yoda, REST sanitize, transients |
| **P3 (Polish)** | 13 | Dark mode, touch targets, tree-shaking |
