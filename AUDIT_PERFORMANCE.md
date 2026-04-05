# Performance & Scalability Audit Report

**Plugin:** Custom Meta Box Builder v2.0  
**Audit Date:** 2026-04-05  
**Auditor:** Agent 4 -- Performance & Scalability Auditor  

---

## 1. Executive Summary

The Custom Meta Box Builder plugin has a solid foundation with some good patterns already in place (meta cache in `FieldRenderer`, lazy loading threshold in JS, conditional asset loading for admin CSS/JS). However, several **critical and high-severity** performance issues exist that will degrade the admin experience significantly at scale (100+ meta boxes, 1000+ posts). The most impactful problems are:

1. **Global asset loading** -- core CSS/JS loaded on every admin page, not just pages where meta boxes appear.
2. **Unbounded queries** in relational fields (`PostField`, `UserField`, `TaxonomyField`) that execute on every render with no caching.
3. **Repeated `get_option()` calls** in `AdminUI` action handlers -- up to 6 calls per `admin_init` for the same option.
4. **N+1 query pattern** in `GroupField::group_item()` creating a new `FieldRenderer` (and thus a new `get_post()` call) for every repeater row.
5. **`BulkOperations` uses `posts_per_page => -1`** which can crash sites with millions of posts.
6. **No object caching or transient usage anywhere** in the plugin.

Estimated total performance tax on a site with 50 meta boxes and 500 posts: **200-500ms additional load time per admin page request**, with potential for timeouts on bulk operations.

---

## 2. Detailed Findings

### 2.1 Asset Loading Strategy

#### FINDING P-01: Core CSS/JS loaded on ALL admin pages (Severity: **Critical**)

**File:** `src/Core/Plugin.php` -- `registerAssets()`  
**Lines:** 25-34

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

**Impact:** Every admin page load includes:
- `cmb-style.css` (13.8 KB)
- `cmb-script.js` (16.9 KB)
- jQuery UI Sortable dependency chain (~40 KB combined)
- **`wp_enqueue_media()` -- the WordPress media library** (~200 KB of JS + CSS)

This means the Dashboard, Users, Plugins, Settings, and all other admin pages load an additional **~270 KB of unnecessary assets**. The media library alone adds 3-5 additional HTTP requests and parses a massive JS bundle.

**Recommendation:** Conditionally load assets only on post edit screens where meta boxes are registered. Use the `$hook_suffix` parameter provided to `admin_enqueue_scripts`:

```php
add_action('admin_enqueue_scripts', function ($hookSuffix) {
    if (!in_array($hookSuffix, ['post.php', 'post-new.php'], true)) {
        return;
    }
    // ... enqueue assets
});
```

Only call `wp_enqueue_media()` if a `file` field type is actually registered.

**Expected improvement:** Eliminate ~270 KB of assets on non-post-edit admin pages. Reduces admin page load by 100-300ms on non-relevant pages.

---

#### FINDING P-02: Admin Builder CSS/JS correctly scoped (Severity: **Low** -- positive finding)

**File:** `src/Core/AdminUI.php` -- `addAdminPage()`  
**Lines:** 35-58

The admin builder assets (`cmb-admin.css` at 27.8 KB, `cmb-admin.js` at 35 KB) are correctly loaded only on the CMB Builder admin page using hook suffix comparison. This is the correct pattern.

---

#### FINDING P-03: No asset versioning for cache-busting (Severity: **Medium**)

**File:** `src/Core/Plugin.php` -- `registerAssets()`  
**Lines:** 27-28

```php
wp_enqueue_style('cmb-style', $baseUrl . 'assets/cmb-style.css');
wp_enqueue_script('cmb-script', $baseUrl . 'assets/cmb-script.js', ['jquery', 'jquery-ui-sortable'], null, true);
```

The version parameter is `null` for both assets, which means WordPress appends its own version. After plugin updates, browsers may serve stale cached files.

**Recommendation:** Use the plugin version constant or `filemtime()`:
```php
$ver = filemtime(__DIR__ . '/assets/cmb-style.css');
```

---

#### FINDING P-04: Assets are not minified (Severity: **Medium**)

**Files:** `assets/cmb-script.js` (16.9 KB), `assets/cmb-style.css` (13.8 KB), `assets/cmb-admin.js` (35 KB), `assets/cmb-admin.css` (27.8 KB)

None of the asset files are minified. Total uncompressed: ~93.5 KB. Minification would typically reduce this by 30-50%.

**Recommendation:** Add a build step (e.g., with `terser` for JS and `cssnano` for CSS) to produce `.min.js` / `.min.css` variants, and serve those in production.

**Expected improvement:** ~30-45 KB reduction in transfer size.

---

### 2.2 Database Query Optimization

#### FINDING P-05: N+1 query pattern in PostField::render() (Severity: **Critical**)

**File:** `src/Fields/PostField.php` -- `render()`  
**Lines:** 18-24

```php
$posts = get_posts([
    'post_type'      => $postType,
    'posts_per_page' => $this->config['limit'] ?? 100,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'post_status'    => 'publish',
]);
```

**Every time a `PostField` is rendered**, it fires a full `WP_Query`. If you have:
- 10 meta boxes each with 2 post fields = 20 queries per post edit page
- In a group repeater with 5 rows = 100 queries per render

There is **no static cache or transient**. The same query with the same `post_type` will execute repeatedly.

**Recommendation:**
1. Implement a static cache keyed by `$postType`:
```php
private static array $postCache = [];

public function render(): string {
    $postType = $this->config['post_type'] ?? 'post';
    $limit = $this->config['limit'] ?? 100;
    $cacheKey = $postType . '_' . $limit;

    if (!isset(self::$postCache[$cacheKey])) {
        self::$postCache[$cacheKey] = get_posts([...]);
    }
    $posts = self::$postCache[$cacheKey];
    // ...
}
```

2. Consider using a transient for expensive queries:
```php
$transientKey = 'cmb_posts_' . $postType . '_' . $limit;
$posts = get_transient($transientKey);
if ($posts === false) {
    $posts = get_posts([...]);
    set_transient($transientKey, $posts, 5 * MINUTE_IN_SECONDS);
}
```

**Expected improvement:** Reduce from O(n) queries to O(1) per unique post type. At 20 post fields = 19 fewer DB queries.

---

#### FINDING P-06: UserField::render() loads ALL users with no limit (Severity: **Critical**)

**File:** `src/Fields/UserField.php` -- `render()`  
**Lines:** 17-23

```php
$args = ['orderby' => 'display_name', 'order' => 'ASC'];
if ($role) {
    $args['role'] = $role;
}
$users = get_users($args);
```

**No `number` (limit) parameter.** On a site with 10,000 users, this fetches ALL user rows from the database, hydrates them into `WP_User` objects, and builds 10,000 `<option>` elements. This can consume 50+ MB of memory and take 5+ seconds.

Also suffers from the same no-cache issue as `PostField`.

**Recommendation:**
1. Add a `number` parameter (default 100): `$args['number'] = $this->config['limit'] ?? 100;`
2. Add static caching identical to the PostField fix.
3. For sites with many users, consider AJAX-powered search instead of a select dropdown.

**Expected improvement:** Prevents memory exhaustion; reduces query time from seconds to milliseconds.

---

#### FINDING P-07: TaxonomyField::render() fires uncached get_terms() (Severity: **High**)

**File:** `src/Fields/TaxonomyField.php` -- `render()`  
**Lines:** 19-22

```php
$terms = get_terms([
    'taxonomy'   => $taxonomy,
    'hide_empty' => false,
]);
```

While `get_terms()` does use WordPress's built-in term cache, the function still performs cache lookups and object construction on each call. With multiple taxonomy fields for the same taxonomy, this is wasteful.

**Recommendation:** Add a static cache for term queries per taxonomy:
```php
private static array $termCache = [];
```

**Expected improvement:** Minor (WordPress caches terms), but prevents redundant PHP processing.

---

#### FINDING P-08: GroupField creates new FieldRenderer per group item (Severity: **High**)

**File:** `src/Fields/GroupField.php` -- `group_item()`  
**Line:** 63

```php
$fieldRenderer = new FieldRenderer(get_post(get_the_ID()));
```

Inside `group_item()`, which is called **once per repeater row**, a new `FieldRenderer` is instantiated AND `get_post(get_the_ID())` is called. This means:
- The `$metaCache` built in `FieldRenderer::get_field_value()` is **not reused** across group items because each gets a new instance.
- `get_post()` is called once per row (though WordPress caches this internally).

With 50 repeater rows, you get 50 `FieldRenderer` instances and 50 redundant meta cache builds.

**Recommendation:** Pass the `FieldRenderer` instance into the `GroupField` constructor or render method, so all rows share one renderer and one meta cache.

**Expected improvement:** With 50 rows: saves 49 calls to `get_post_meta($post_id)` (bulk fetch). Reduces memory allocation for 49 duplicate cache arrays.

---

#### FINDING P-09: delete_post_meta + add_post_meta pattern on save (Severity: **High**)

**File:** `src/Core/MetaBoxManager.php` -- `saveField()`  
**Lines:** 201-208

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

For **every field on every save**, the plugin:
1. Deletes ALL meta rows for that key (1 DELETE query)
2. Inserts new rows (1 INSERT per array element, or 1 UPDATE for scalar)

With 20 fields, this is 20 DELETE + 20 INSERT/UPDATE = **40 queries minimum** per save. For array fields with 10 values each, it could be 20 DELETE + 200 INSERT = **220 queries**.

**Recommendation:**
- For scalar values, use `update_post_meta()` directly (skips delete).
- For array values, compare with existing values and only delete/add the diff.
- Consider batching: use `$wpdb->query()` with a single multi-row INSERT.

**Expected improvement:** Reduce save queries by 30-50% for typical use cases.

---

#### FINDING P-10: BulkOperations uses posts_per_page => -1 (Severity: **Critical**)

**File:** `src/Core/BulkOperations.php` -- `handleBulkUpdate()`  
**Lines:** 120-126

```php
$posts = get_posts([
    'post_type' => $postType,
    'posts_per_page' => -1,
    'fields' => 'ids',
    'post_status' => 'any',
]);
```

On a site with 100,000+ posts of a given type, this will:
- Attempt to load all post IDs into memory (potentially millions of rows)
- Execute individual `update_post_meta` / `delete_post_meta` for each post in a PHP loop (lines 130-151)

With 100,000 posts and a "replace" operation, that is: 100,000 `get_post_meta()` + up to 100,000 `update_post_meta()` = **200,000 queries in a single request**. This will certainly cause a PHP timeout.

**Recommendation:**
1. Process in batches of 100-500 posts using pagination.
2. For "set" and "delete" operations, use direct `$wpdb` queries:
```php
$wpdb->query($wpdb->prepare(
    "UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)",
    $value, $fieldId, $postType
));
```
3. Add a progress indicator for long-running operations.

**Expected improvement:** Prevents PHP timeouts; reduces execution time from minutes to seconds.

---

#### FINDING P-11: Revision meta copy has no batch optimization (Severity: **Medium**)

**File:** `src/Core/MetaBoxManager.php` -- `copyMetaToRevision()` and `restoreMetaFromRevision()`  
**Lines:** 358-384

Both methods iterate through ALL registered meta boxes and ALL fields, calling `get_post_meta()` and `add_post_meta()` individually for each field. With 100 fields, this is 100 reads + 100 writes = 200 queries per revision operation.

**Recommendation:** Use `get_post_meta($parentId)` (no key) to bulk-fetch all meta, then filter to only the relevant keys.

**Expected improvement:** Reduce from O(n) read queries to O(1).

---

### 2.3 Autoloaded Options & Memory

#### FINDING P-12: Large serialized option in wp_options (Severity: **High**)

**File:** `src/Core/AdminUI.php`  
**Constant:** `OPTION_KEY = 'cmb_admin_configurations'`

All field group configurations are stored in a single `wp_options` row as serialized PHP. With 100 field groups, each with 20 fields with sub-fields, this option value could easily reach **500 KB - 1 MB**.

Since `update_option()` defaults to `autoload = 'yes'`, this entire blob is loaded into memory on **every single WordPress request** (front-end and admin), even when the plugin is not needed.

**Recommendation:**
1. Set autoload to `'no'`:
```php
update_option(self::OPTION_KEY, $configs, false);
```
2. Consider splitting into individual options per field group, or use a custom database table.
3. Cache the parsed configuration in a static variable to avoid repeated `get_option()` calls.

**Expected improvement:** With 500 KB option: saves ~500 KB memory and deserialization time on every front-end page load.

---

#### FINDING P-13: Repeated get_option() calls in AdminUI handlers (Severity: **High**)

**File:** `src/Core/AdminUI.php`  
**Functions:** `handleSave()`, `handleDelete()`, `handleDuplicate()`, `handleToggle()`, `handleExport()`, `handleImport()`, `registerSavedBoxes()`, `renderListPage()`, `renderEditPage()`

The `get_option(self::OPTION_KEY, [])` call appears **9 times** across these methods. While WordPress does cache options after the first fetch, the `maybe_unserialize()` step runs each time, and all these handlers are registered on `admin_init` -- meaning their early-return checks still pay the cost of the hook registration.

More critically, `handleSave`, `handleDelete`, `handleDuplicate`, `handleToggle`, `handleExport`, and `handleImport` are ALL registered on `admin_init` with no early check. Every admin page load checks POST/GET variables in all 6 handlers even when none are applicable.

**Recommendation:**
1. Add a static cache property:
```php
private static ?array $configCache = null;
private static function getConfigs(): array {
    if (self::$configCache === null) {
        self::$configCache = get_option(self::OPTION_KEY, []);
    }
    return self::$configCache;
}
```
2. Consolidate action handlers behind a single dispatcher that checks for CMB-specific request indicators first.

**Expected improvement:** Eliminates redundant deserialization; reduces admin_init processing.

---

#### FINDING P-14: TaxonomyMetaManager::register() registers hooks before data is available (Severity: **Medium**)

**File:** `src/Core/TaxonomyMetaManager.php` -- `register()`  
**Lines:** 12-17

```php
public function register(): void {
    foreach (array_keys($this->taxonomyBoxes) as $taxonomy) {
        add_action($taxonomy . '_edit_form_fields', ...);
        // ...
    }
}
```

The `register()` method is called once in `public-api.php`, and subsequent `add()` calls do NOT register additional hooks. This means only taxonomies registered before the first `add_custom_taxonomy_meta()` call get hooks. However, it also means hooks for ALL registered taxonomies are added even if the current page has nothing to do with those taxonomies.

**Impact:** Minor -- WordPress hook system handles this efficiently.

---

### 2.4 Caching Opportunities

#### FINDING P-15: No object caching anywhere (Severity: **High**)

The plugin does not use `wp_cache_get()` / `wp_cache_set()` (object cache), nor `get_transient()` / `set_transient()` anywhere in its codebase. All data is either fetched from the database fresh or stored in static PHP variables that are lost between requests.

**Key opportunities:**
1. **PostField query results** -- cache post lists per post_type for 5 minutes
2. **UserField query results** -- cache user lists per role for 5 minutes
3. **AdminUI configurations** -- cache parsed/transformed config after `registerSavedBoxes()`
4. **DependencyGraph data** -- cache the graph structure

**Recommendation:** Implement transient caching for expensive queries with appropriate invalidation:
```php
$cacheKey = 'cmb_posts_' . md5(serialize($queryArgs));
$posts = get_transient($cacheKey);
if ($posts === false) {
    $posts = get_posts($queryArgs);
    set_transient($cacheKey, $posts, 5 * MINUTE_IN_SECONDS);
}
```

**Expected improvement:** Eliminates repeated queries for the same data across page loads; 50-80% reduction in DB queries on cached hits.

---

#### FINDING P-16: FieldRenderer metaCache is good but scoped too narrowly (Severity: **Low** -- positive finding with note)

**File:** `src/Core/FieldRenderer.php` -- `get_field_value()`  
**Lines:** 210-216

```php
if ($this->metaCache === null) {
    $all = get_post_meta($post_id);
    $this->metaCache = is_array($all) ? $all : [];
}
```

This is a **good pattern** -- bulk-fetching all post meta on first access. However, it is undermined by P-08 (GroupField creating new FieldRenderer instances per row), which causes this cache to be rebuilt repeatedly.

---

### 2.5 Large-Scale Usage Readiness

#### FINDING P-17: No pagination in admin list page (Severity: **Medium**)

**File:** `src/Core/AdminUI.php` -- `renderListPage()`  
**Lines:** 82-180

All field groups are rendered in a single table with no pagination. With 200+ field groups, the page will be slow to render and unwieldy to use.

Additionally, line 155 calls `get_post_types(['public' => true], 'objects')` inside the `foreach` loop for every field group row to resolve post type labels:
```php
$allPt = get_post_types(['public' => true], 'objects');
```

This should be hoisted outside the loop.

**Recommendation:** Add pagination (e.g., 20 per page). Hoist `get_post_types()` outside the loop.

**Expected improvement:** Faster render for large numbers of field groups.

---

#### FINDING P-18: resolveFieldClass() calls class_exists() repeatedly (Severity: **Low**)

**File:** `src/Core/MetaBoxManager.php` -- `resolveFieldClass()`  
**Lines:** 251-269

For every field on every save, `class_exists()` is called. While PHP caches class lookups internally, adding a static lookup cache would be slightly more efficient:

```php
private static array $resolvedClasses = [];
```

**Impact:** Negligible for typical use; very slight improvement with 100+ fields.

---

#### FINDING P-19: Field instantiation overhead on save (Severity: **Medium**)

**File:** `src/Core/MetaBoxManager.php` -- `saveField()`  
**Lines:** 166-212

For every field being saved, a **new field class instance** is created solely to call `validate()` and `sanitize()`:
```php
$instance = new $fieldClass($field);
```

With 100 fields, that is 100 object instantiations. The `AbstractField` constructor only sets `$this->config = $config`, so each instantiation is lightweight, but the pattern could be optimized with a field registry.

**Recommendation:** Consider making `sanitize()` and `validate()` static methods, or caching field instances.

---

#### FINDING P-20: flattenFields() duplicated across 4 classes (Severity: **Low**)

**Files:**
- `MetaBoxManager.php` line 152
- `DependencyGraph.php` line 60
- `BulkOperations.php` line 182
- (Tab flattening logic in `addMetaBoxes()`)

The same flatten logic is repeated in 4 places. This is a code quality issue, but each duplicate invocation adds minor CPU overhead. More importantly, changes to the tab structure need to be updated in 4 places.

**Recommendation:** Extract to a shared utility class or trait.

---

### 2.6 Serialization / Deserialization Overhead

#### FINDING P-21: is_serialized() check on every field value read (Severity: **Low**)

**File:** `src/Core/FieldRenderer.php` -- `get_field_value()`  
**Lines:** 224-234

```php
return array_map(function ($v) {
    return is_serialized($v) ? maybe_unserialize($v) : $v;
}, $meta);
```

When `get_post_meta($post_id)` is called without a key (bulk fetch), WordPress returns raw values that may already be unserialized. The `is_serialized()` + `maybe_unserialize()` pair runs for every meta value. With 100 meta keys and array values, this can be hundreds of regex checks.

**Impact:** Low individually, but compounds with many fields. WordPress's `get_post_meta()` with a single key already handles unserialization, so switching to individual calls with caching might avoid this.

---

#### FINDING P-22: AdminUI stores large nested arrays in wp_options (Severity: **Medium**)

**File:** `src/Core/AdminUI.php` -- `handleSave()`, line 984

```php
update_option(self::OPTION_KEY, $configs);
```

Every save serializes the ENTIRE configurations array (all field groups) and writes it to the database. With 100 field groups, this means serializing and writing potentially hundreds of KB on every single field group save/toggle/duplicate operation.

**Recommendation:** Consider per-field-group storage:
```php
update_option('cmb_config_' . $id, $boxConfig, false);
```

With a separate index option listing all IDs. This way, only the changed group is serialized and written.

**Expected improvement:** Reduces write payload by 90%+ for single-group operations.

---

### 2.7 Hook Priorities and Execution Order

#### FINDING P-23: registerSavedBoxes runs at priority 20 on init (Severity: **Low**)

**File:** `src/Core/AdminUI.php` -- `register()`  
**Line:** 22

```php
add_action('init', [self::class, 'registerSavedBoxes'], 20);
```

Priority 20 is appropriate -- it runs after standard `init` hooks (priority 10) to ensure post types and taxonomies are registered first.

---

#### FINDING P-24: save_post hook fires for ALL post types (Severity: **Medium**)

**File:** `src/Core/MetaBoxManager.php` -- `register()`  
**Line:** 33

```php
add_action('save_post', [$this, 'saveMetaBoxData']);
```

The `save_post` hook fires for every post type save, including nav_menu_items, revisions, and auto-drafts. The handler does have nonce checks to bail early, but it still iterates through ALL registered meta boxes to check nonces:

```php
foreach ($this->metaBoxes as $id => $metaBox) {
    if (!isset($_POST[$nonceField]) || !wp_verify_nonce(...)) {
        continue;
    }
    // ...
}
```

With 100 meta boxes, that is 100 nonce lookups on every post save.

**Recommendation:** Use `save_post_{$post_type}` hooks for specific post types, or add an early bail-out check:
```php
if (empty($_POST)) return; // AJAX/REST saves may not have POST data
```

---

#### FINDING P-25: delete_post deletes meta for ALL registered meta boxes (Severity: **Medium**)

**File:** `src/Core/MetaBoxManager.php` -- `deletePostMetaData()`  
**Lines:** 348-355

```php
public function deletePostMetaData(int $postId): void {
    foreach ($this->metaBoxes as $metaBox) {
        $fields = $this->flattenFields($metaBox['fields']);
        foreach ($fields as $field) {
            delete_post_meta($postId, $field['id']);
        }
    }
}
```

When any post is deleted, this attempts to delete meta for ALL registered field IDs, regardless of whether the post type matches. With 100 fields, that is 100 DELETE queries -- most of which will delete 0 rows.

**Recommendation:** Check if the post's post type matches the meta box's registered post types before deleting.

---

### 2.8 Frontend Rendering Performance

#### FINDING P-26: GroupField creates get_post(get_the_ID()) per row (Severity: **High**)

**File:** `src/Fields/GroupField.php` -- `group_item()`  
**Line:** 63

```php
$fieldRenderer = new FieldRenderer(get_post(get_the_ID()));
```

`get_the_ID()` relies on global state and may not return the correct post in all contexts. More critically, `get_post()` is called once per group row. While WordPress caches post objects, the overhead of creating a new FieldRenderer per row is the main concern (see P-08).

---

#### FINDING P-27: WysiwygField uses ob_start/ob_get_clean (Severity: **Low**)

**File:** `src/Fields/WysiwygField.php` -- `render()`  
**Lines:** 21-28

The `wp_editor()` function outputs directly, so `ob_start()` is necessary to capture it. This is correct, but output buffering has minor overhead. No fix needed -- this is the standard WordPress pattern.

---

#### FINDING P-28: evaluateConditionals() in JS runs on every input change (Severity: **Medium**)

**File:** `assets/cmb-script.js` -- `evaluateConditionals()`  
**Lines:** 270-307

```javascript
$(document).on('input change', '.cmb-container :input, .cmb-tab-panel :input', function() {
    evaluateConditionals();
});
```

Every keystroke in any input field triggers `evaluateConditionals()`, which iterates through ALL `[data-conditional-field]` elements and performs DOM queries. With 50 conditional fields and a user typing rapidly, this causes janky UI.

**Recommendation:** Debounce the handler:
```javascript
var conditionalTimer;
$(document).on('input change', '...', function() {
    clearTimeout(conditionalTimer);
    conditionalTimer = setTimeout(evaluateConditionals, 150);
});
```

Also scope the evaluation to only conditionals related to the changed field:
```javascript
var changedName = $(this).attr('name');
$('[data-conditional-field="' + changedName + '"]').each(...)
```

**Expected improvement:** Eliminates UI jank with many conditional fields.

---

#### FINDING P-29: Sortable update handler re-indexes all inputs (Severity: **Medium**)

**File:** `assets/cmb-script.js` -- sortable `update` callback  
**Lines:** 226-246

When a group item is drag-sorted, the handler iterates through **all items and all inputs** to update name attributes. With 50 rows, each containing 10 inputs, that is 500 DOM reads + 500 attribute writes.

**Recommendation:** Only re-index items whose position changed (items between the old and new position of the dragged item).

---

### 2.9 PHP Memory and CPU Patterns

#### FINDING P-30: No memory limit awareness in bulk operations (Severity: **High**)

**File:** `src/Core/BulkOperations.php`

The `handleBulkUpdate()`, `bulkSet()`, and `bulkDelete()` methods process all posts in a single PHP request with no memory management. They do not:
- Check `memory_get_usage()` against limits
- Use `wp_cache_flush()` to clear object cache periodically
- Process in batches

**Recommendation:** Process in batches of 100, with `wp_cache_flush()` every batch:
```php
$batches = array_chunk($postIds, 100);
foreach ($batches as $batch) {
    foreach ($batch as $postId) { /* ... */ }
    wp_cache_flush();
}
```

---

#### FINDING P-31: ImportExport::importFromJson() has no size limit (Severity: **Medium**)

**File:** `src/Core/ImportExport.php` -- `importFromJson()`  
**Line:** 35

```php
$data = json_decode($json, true);
```

No validation on the size of the incoming JSON. A malicious or accidental multi-MB JSON file could exhaust memory during `json_decode()`.

**Recommendation:** Add a size check before decoding:
```php
if (strlen($json) > 1024 * 1024) { // 1 MB limit
    return 0;
}
```

---

### 2.10 Lazy Loading vs. Eager Loading

#### FINDING P-32: JS lazy loading for large repeaters is well implemented (Severity: **Low** -- positive finding)

**File:** `assets/cmb-script.js`  
**Lines:** 406-433

The plugin implements a `CMB_LAZY_THRESHOLD = 20` pattern that hides items beyond 20 and shows a "Load more" button. This is a good pattern for UI performance.

**However**, the server-side still renders ALL items into the HTML. With 500 repeater rows, the server generates all 500 rows' HTML, which is then hidden by JavaScript. This means:
- Full server-side rendering cost
- Full HTML payload transferred
- JS hides elements after DOM parse

**Recommendation:** For very large repeaters (>100 items), implement server-side pagination with AJAX loading for additional rows.

---

#### FINDING P-33: All meta boxes eager-load all field values (Severity: **Medium**)

**File:** `src/Core/FieldRenderer.php`

The `metaCache` pattern bulk-fetches ALL post meta. This is efficient for the common case (most fields will be rendered). However, for meta boxes using conditional display (`conditional` config), fields that are hidden still have their values fetched and their HTML rendered (only hidden via CSS).

**Impact:** Minor for typical usage; could be significant if a meta box has 50 fields, 40 of which are conditionally hidden.

---

## 3. Summary Table

| ID | Finding | Severity | Category | Est. Impact |
|----|---------|----------|----------|-------------|
| P-01 | Assets loaded on ALL admin pages | Critical | Assets | +270 KB on non-relevant pages |
| P-02 | Admin builder assets correctly scoped | Low (OK) | Assets | N/A |
| P-03 | No asset versioning | Medium | Assets | Stale cache risk |
| P-04 | Assets not minified | Medium | Assets | ~45 KB extra transfer |
| P-05 | PostField fires uncached query per render | Critical | Database | 20+ redundant queries |
| P-06 | UserField loads ALL users, no limit | Critical | Database | Memory exhaustion risk |
| P-07 | TaxonomyField uncached get_terms() | High | Database | Minor duplicate work |
| P-08 | GroupField creates new FieldRenderer per row | High | Database/Memory | 49 wasted cache builds per 50 rows |
| P-09 | delete + re-insert pattern on save | High | Database | 40-220 queries per save |
| P-10 | BulkOps uses posts_per_page=-1 | Critical | Database | Timeout/crash on large sites |
| P-11 | Revision copy not batched | Medium | Database | O(n) queries per revision |
| P-12 | Large autoloaded option blob | High | Memory | ~500 KB per request |
| P-13 | Repeated get_option() calls | High | Memory/CPU | Redundant deserialization |
| P-14 | Hook registration timing | Medium | Hooks | Minor |
| P-15 | No object cache or transients used | High | Caching | Missed 50-80% query reduction |
| P-16 | FieldRenderer metaCache good pattern | Low (OK) | Caching | N/A |
| P-17 | No pagination in admin list | Medium | Scalability | Slow with 200+ groups |
| P-18 | resolveFieldClass() calls class_exists() | Low | CPU | Negligible |
| P-19 | Field instantiation overhead on save | Medium | CPU | 100 objects per save |
| P-20 | flattenFields() duplicated 4x | Low | Code quality | Minor redundancy |
| P-21 | is_serialized() on every value read | Low | CPU | Compounds with many fields |
| P-22 | Full config re-serialized on every save | Medium | Database/IO | Large write payload |
| P-23 | registerSavedBoxes at priority 20 | Low (OK) | Hooks | Correct |
| P-24 | save_post fires for all post types | Medium | Hooks | 100 nonce checks per save |
| P-25 | delete_post deletes all fields regardless | Medium | Database | 100 DELETE queries per delete |
| P-26 | get_post() per group row | High | Database | See P-08 |
| P-27 | WysiwygField ob_start pattern | Low (OK) | Rendering | Standard pattern |
| P-28 | Conditionals evaluate on every keystroke | Medium | JS/UX | UI jank risk |
| P-29 | Sortable re-indexes all inputs | Medium | JS/UX | Slow with many rows |
| P-30 | No memory management in bulk ops | High | Memory | OOM risk |
| P-31 | No import size limit | Medium | Memory | OOM risk |
| P-32 | JS lazy loading for repeaters | Low (OK) | Rendering | Good pattern |
| P-33 | Conditional fields still rendered server-side | Medium | Rendering | Wasted HTML |

---

## 4. Prioritized Optimization Roadmap

### Phase 1: Critical Fixes (Do Immediately)
1. **P-01:** Conditionally load assets only on post edit screens
2. **P-06:** Add limit to UserField query; add static cache
3. **P-05:** Add static cache to PostField query
4. **P-10:** Batch BulkOperations processing; add limits

### Phase 2: High-Impact Optimizations (Next Sprint)
5. **P-12:** Set autoload=false on cmb_admin_configurations option
6. **P-08/P-26:** Pass FieldRenderer instance through to GroupField
7. **P-09:** Optimize save pattern to avoid delete+re-insert
8. **P-13:** Centralize get_option() with static cache
9. **P-15:** Add transient caching for relational field queries

### Phase 3: Medium-Impact Improvements (Backlog)
10. **P-04:** Minify CSS/JS assets
11. **P-28:** Debounce conditional field evaluation
12. **P-24/P-25:** Scope save_post and delete_post to registered post types
13. **P-22:** Split config storage per field group
14. **P-30:** Add batch processing with cache flush for bulk ops

### Phase 4: Polish
15. **P-03:** Add version strings to asset enqueues
16. **P-17:** Add pagination to admin list page
17. **P-29:** Optimize sortable re-indexing
18. **P-20:** Extract shared flattenFields() utility

---

## 5. Performance Budget Recommendation

For a site with 50 meta boxes and 20 fields each:

| Metric | Current (Est.) | Target |
|--------|---------------|--------|
| Admin page load (non-post) | +270 KB assets | +0 KB |
| Post edit page DB queries | ~60-100 | ~15-20 |
| Post save DB queries | ~80-220 | ~30-50 |
| Memory usage (option blob) | ~500 KB autoloaded | 0 KB autoloaded |
| Bulk operation (1000 posts) | Timeout likely | <10 seconds |
| JS conditional evaluation | Every keystroke | Debounced 150ms |

---

*End of Performance & Scalability Audit Report*
