# TODO: Performance Optimization

**Generated:** 2026-04-05
**Source:** Consolidated from AUDIT_PERFORMANCE

Performance optimization tasks beyond the critical fixes (asset loading, UserField, BulkOps are in TODO_CRITICAL_FIXES.md).

---

## PERF-001: Add Static Cache to PostField Queries

- **Title:** PostField fires uncached get_posts() on every render
- **Description:** Every `PostField::render()` fires a full `WP_Query`. With 10 meta boxes having 2 post fields each = 20 queries. In a group repeater with 5 rows = 100 queries.
- **Root Cause:** No caching of query results.
- **Proposed Solution:**
  ```php
  private static array $postCache = [];
  public function render(): string {
      $cacheKey = $postType . '_' . $limit;
      if (!isset(self::$postCache[$cacheKey])) {
          self::$postCache[$cacheKey] = get_posts([...]);
      }
      $posts = self::$postCache[$cacheKey];
  }
  ```
- **Affected Files:**
  - `src/Fields/PostField.php` (render, lines 18-24)
- **Estimated Effort:** 1 hour
- **Priority:** P0
- **Dependencies:** None

---

## PERF-002: Add Static Cache to TaxonomyField Queries

- **Title:** TaxonomyField fires get_terms() per render without static caching
- **Description:** While WordPress caches terms internally, the PHP overhead of repeated calls and object construction is wasteful with multiple taxonomy fields for the same taxonomy.
- **Root Cause:** No application-level caching.
- **Proposed Solution:**
  Add `private static array $termCache = [];` keyed by taxonomy name.
- **Affected Files:**
  - `src/Fields/TaxonomyField.php` (render, lines 19-22)
- **Estimated Effort:** 1 hour
- **Priority:** P1
- **Dependencies:** None

---

## PERF-003: Share FieldRenderer Instance Across GroupField Rows

- **Title:** GroupField creates new FieldRenderer per repeater row
- **Description:** `group_item()` creates a new `FieldRenderer(get_post(get_the_ID()))` for each row. With 50 rows, that's 50 instances and 50 redundant meta cache builds.
- **Root Cause:** GroupField has no access to the parent FieldRenderer.
- **Proposed Solution:**
  Pass the `FieldRenderer` instance into GroupField via config or a setter method. All rows share one renderer and one meta cache.
- **Affected Files:**
  - `src/Fields/GroupField.php` (group_item, line 63)
  - `src/Core/FieldRenderer.php` (pass self to GroupField)
- **Estimated Effort:** 3 hours
- **Priority:** P1
- **Dependencies:** None

---

## PERF-004: Optimize Save Pattern -- Avoid Delete+Re-Insert

- **Title:** Every field save does DELETE then INSERT -- 40-220 queries per save
- **Description:** `saveField()` calls `delete_post_meta()` then `add_post_meta()` for every field. With 20 fields = 40+ queries. For array fields with 10 values = 220 queries. Also non-atomic -- crash between delete and insert loses data.
- **Root Cause:** Simplistic save strategy; no diff-based approach.
- **Proposed Solution:**
  1. For scalar values: use `update_post_meta()` directly (skips delete).
  2. For array values: compare with existing values, only delete/add the diff.
  3. Consider wrapping in a transaction or at least using `update_post_meta` with `$prev_value`.
- **Affected Files:**
  - `src/Core/MetaBoxManager.php` (saveField, lines 201-208)
- **Estimated Effort:** 4 hours
- **Priority:** P1
- **Dependencies:** None

---

## PERF-005: Set autoload=false on Configuration Option

- **Title:** Large serialized config option autoloaded on every request
- **Description:** `cmb_admin_configurations` stores all field groups as serialized PHP. With 100 groups at 20 fields each, this can reach 500KB-1MB. Autoloaded on every request (front-end and admin).
- **Root Cause:** `update_option()` defaults to `autoload = 'yes'`.
- **Proposed Solution:**
  ```php
  update_option(self::OPTION_KEY, $configs, false);
  ```
  Also set autoload to 'no' on activation:
  ```php
  $wpdb->update($wpdb->options, ['autoload' => 'no'], ['option_name' => 'cmb_admin_configurations']);
  ```
- **Affected Files:**
  - `src/Core/AdminUI.php` (all update_option calls)
  - Activation hook (one-time migration)
- **Estimated Effort:** 1 hour
- **Priority:** P1
- **Dependencies:** CF-005

---

## PERF-006: Centralize get_option() with Static Cache

- **Title:** get_option() called 9 times for same key across AdminUI handlers
- **Description:** `get_option(self::OPTION_KEY, [])` appears 9 times across `handleSave`, `handleDelete`, `handleDuplicate`, `handleToggle`, `handleExport`, `handleImport`, `registerSavedBoxes`, `renderListPage`, `renderEditPage`. All registered on `admin_init`.
- **Root Cause:** No centralized config accessor.
- **Proposed Solution:**
  ```php
  private static ?array $configCache = null;
  private static function getConfigs(): array {
      if (self::$configCache === null) {
          self::$configCache = get_option(self::OPTION_KEY, []);
      }
      return self::$configCache;
  }
  private static function saveConfigs(array $configs): void {
      update_option(self::OPTION_KEY, $configs, false);
      self::$configCache = $configs;
  }
  ```
- **Affected Files:**
  - `src/Core/AdminUI.php` (9 call sites)
- **Estimated Effort:** 2 hours
- **Priority:** P1
- **Dependencies:** None

---

## PERF-007: Add Transient Caching for Relational Field Queries

- **Title:** Zero use of object cache or transients anywhere
- **Description:** PostField, UserField, and TaxonomyField fetch data from DB fresh on every page load. No caching mechanism used.
- **Root Cause:** Caching was never implemented.
- **Proposed Solution:**
  ```php
  $cacheKey = 'cmb_posts_' . md5(serialize($queryArgs));
  $posts = get_transient($cacheKey);
  if ($posts === false) {
      $posts = get_posts($queryArgs);
      set_transient($cacheKey, $posts, 5 * MINUTE_IN_SECONDS);
  }
  ```
  Add cache invalidation on `save_post`, `created_term`, `profile_update` hooks.
- **Affected Files:**
  - `src/Fields/PostField.php`
  - `src/Fields/UserField.php`
  - `src/Fields/TaxonomyField.php`
  - `src/Core/Plugin.php` (cache invalidation hooks)
- **Estimated Effort:** 4 hours
- **Priority:** P1
- **Dependencies:** PERF-001

---

## PERF-008: Add Asset Minification Build Pipeline

- **Title:** Raw unminified JS/CSS shipped to production (~93.5KB uncompressed)
- **Description:** No build process exists. `cmb-script.js` (16.9KB), `cmb-style.css` (13.8KB), `cmb-admin.js` (35KB), `cmb-admin.css` (27.8KB) are all unminified.
- **Root Cause:** No build tooling configured.
- **Proposed Solution:**
  1. Add `@wordpress/scripts` or a minimal webpack/esbuild config.
  2. Produce `.min.js` / `.min.css` variants.
  3. Use `SCRIPT_DEBUG` constant to serve unminified in development.
  4. Add source maps for debugging.
- **Affected Files:**
  - `package.json` (new)
  - `webpack.config.js` or equivalent (new)
  - `src/Core/Plugin.php` (conditionally load .min variants)
  - `src/Core/AdminUI.php` (same)
  - `.gitignore` (add build output)
- **Estimated Effort:** 6 hours
- **Priority:** P1
- **Dependencies:** None

---

## PERF-009: Add Asset Version Cache Busting

- **Title:** Static or null version parameters on enqueued assets
- **Description:** Main assets use `null` version (Plugin.php), admin assets use static `'2.0.0'` (AdminUI.php), Gutenberg uses `null`. File changes won't bust browser cache.
- **Root Cause:** No dynamic versioning strategy.
- **Proposed Solution:**
  ```php
  $ver = filemtime(plugin_dir_path(__FILE__) . 'assets/cmb-style.css');
  wp_enqueue_style('cmb-style', $url, [], $ver);
  ```
  Or use a plugin version constant.
- **Affected Files:**
  - `src/Core/Plugin.php` (registerAssets)
  - `src/Core/AdminUI.php` (addAdminPage)
  - `src/Core/GutenbergPanel.php` (enqueueEditorAssets)
- **Estimated Effort:** 1 hour
- **Priority:** P1
- **Dependencies:** None

---

## PERF-010: Debounce Conditional Field Evaluation in JS

- **Title:** evaluateConditionals() fires on every keystroke
- **Description:** Every `input`/`change` event on any field triggers `evaluateConditionals()`, iterating ALL `[data-conditional-field]` elements. Causes UI jank with many conditional fields.
- **Root Cause:** No debouncing or scoping.
- **Proposed Solution:**
  ```javascript
  var conditionalTimer;
  $(document).on('input change', '...', function() {
      clearTimeout(conditionalTimer);
      conditionalTimer = setTimeout(evaluateConditionals, 150);
  });
  ```
  Also scope evaluation to related conditionals only.
- **Affected Files:**
  - `assets/cmb-script.js` (lines 305-307)
- **Estimated Effort:** 1 hour
- **Priority:** P1
- **Dependencies:** None

---

## PERF-011: Scope save_post Hook to Registered Post Types

- **Title:** save_post fires for ALL post types, iterating all meta boxes
- **Description:** `save_post` handler iterates ALL registered meta boxes checking nonces on every post save, including nav_menu_items, revisions, and auto-drafts.
- **Root Cause:** Using generic `save_post` instead of post-type-specific hooks.
- **Proposed Solution:**
  Use `save_post_{$post_type}` for each registered post type, or add early bail-out:
  ```php
  $postType = get_post_type($postId);
  $registeredTypes = array_unique(array_merge(...array_column($this->metaBoxes, 'postTypes')));
  if (!in_array($postType, $registeredTypes, true)) return;
  ```
- **Affected Files:**
  - `src/Core/MetaBoxManager.php` (register, line 33; saveMetaBoxData)
- **Estimated Effort:** 2 hours
- **Priority:** P1
- **Dependencies:** None

---

## PERF-012: Batch Revision Meta Copy Operations

- **Title:** Revision copy does individual get/add per field -- O(n) queries
- **Description:** `copyMetaToRevision()` and `restoreMetaFromRevision()` call `get_post_meta()` and `add_post_meta()` individually for each field. With 100 fields = 200 queries per revision.
- **Root Cause:** No bulk fetch/write strategy.
- **Proposed Solution:**
  Use `get_post_meta($parentId)` (no key) to bulk-fetch all meta, filter to relevant keys, then batch-write.
- **Affected Files:**
  - `src/Core/MetaBoxManager.php` (copyMetaToRevision, restoreMetaFromRevision, lines 358-384)
- **Estimated Effort:** 3 hours
- **Priority:** P2
- **Dependencies:** None

---

## PERF-013: Split Configuration Storage Per Field Group

- **Title:** Entire config re-serialized on every single save/toggle/duplicate
- **Description:** `update_option(self::OPTION_KEY, $configs)` writes ALL field groups on every operation. With 100 groups, this means serializing 100s of KB per save.
- **Root Cause:** Single monolithic option for all configs.
- **Proposed Solution:**
  Store each group individually: `update_option('cmb_config_' . $id, $boxConfig, false);`
  Maintain an index option listing all IDs.
- **Affected Files:**
  - `src/Core/AdminUI.php` (all CRUD operations)
- **Estimated Effort:** 8 hours
- **Priority:** P2
- **Dependencies:** PERF-006

---

## PERF-014: Add Pagination to Admin List Page

- **Title:** All field groups rendered in single table with no pagination
- **Description:** `renderListPage()` renders all groups. `get_post_types()` called inside loop for each row.
- **Root Cause:** No pagination implemented.
- **Proposed Solution:**
  1. Add pagination (20 per page) using WP_List_Table or custom pagination.
  2. Hoist `get_post_types()` call outside the loop.
- **Affected Files:**
  - `src/Core/AdminUI.php` (renderListPage)
- **Estimated Effort:** 4 hours
- **Priority:** P2
- **Dependencies:** None

---

## PERF-015: Optimize Sortable Re-indexing in JS

- **Title:** Sortable update re-indexes ALL items and ALL inputs
- **Description:** When a group item is drag-sorted, the handler iterates all items and all inputs to update name attributes. With 50 rows x 10 inputs = 500 DOM operations.
- **Root Cause:** Full re-index instead of partial.
- **Proposed Solution:**
  Only re-index items between the old and new position of the dragged item.
- **Affected Files:**
  - `assets/cmb-script.js` (lines 226-246)
- **Estimated Effort:** 2 hours
- **Priority:** P2
- **Dependencies:** None

---

## Summary

| ID | Title | Priority | Effort (hrs) |
|----|-------|----------|--------------|
| PERF-001 | PostField static cache | P0 | 1 |
| PERF-002 | TaxonomyField static cache | P1 | 1 |
| PERF-003 | Share FieldRenderer in GroupField | P1 | 3 |
| PERF-004 | Optimize save pattern | P1 | 4 |
| PERF-005 | Set autoload=false on config | P1 | 1 |
| PERF-006 | Centralize get_option cache | P1 | 2 |
| PERF-007 | Transient caching for queries | P1 | 4 |
| PERF-008 | Asset minification pipeline | P1 | 6 |
| PERF-009 | Asset version cache busting | P1 | 1 |
| PERF-010 | Debounce conditionals | P1 | 1 |
| PERF-011 | Scope save_post to post types | P1 | 2 |
| PERF-012 | Batch revision meta copy | P2 | 3 |
| PERF-013 | Split config storage per group | P2 | 8 |
| PERF-014 | Admin list pagination | P2 | 4 |
| PERF-015 | Optimize sortable re-indexing | P2 | 2 |
| **Total** | | | **43** |
