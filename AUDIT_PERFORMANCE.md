# Performance & Scalability Audit Report

**Plugin:** Custom Meta Box Builder v2.1
**Audit Date:** 2026-04-06 (Re-audit)
**Previous Audit:** 2026-04-05 (v2.0)
**Auditor:** Performance & Scalability Agent

---

## 1. Executive Summary

**Overall Rating: IMPROVED** (from v2.0)

The v2.1 codebase addresses many of the critical v2.0 performance findings:

**Resolved:**
- Asset loading is now conditional per admin screen (CF-013, Plugin.php `shouldEnqueueAssets()`)
- Static caches added to PostField, TaxonomyField, UserField (PERF-001, 002, 007)
- Shared FieldRenderer instance in GroupField (PERF-003)
- Centralized config cache in ActionHandler (PERF-006)
- SCRIPT_DEBUG conditional loading with filemtime versioning (PERF-008, 009)
- Debounced conditional evaluation at 150ms (PERF-010)
- Batched revision meta copy (PERF-012)
- Admin list pagination at 20/page (PERF-014)
- Sortable re-index limited to affected range (PERF-015)

**Remaining concerns:**
- Static cache key collisions possible in multi-instance scenarios
- N+1 query pattern persists in some GroupField paths
- New modules add overhead (LocalJson sync on every `admin_init`, dual-prefix hooks)

**Findings Summary:**
| Severity | Count | Change from v2.0 |
|----------|-------|-------------------|
| Critical | 4     | Shifted focus      |
| High     | 2     | Reduced            |
| Medium   | 7     | Reduced            |
| Low      | 5     | Similar            |

---

## 2. Resolved Findings from v2.0

| v2.0 Finding | Resolution |
|---|---|
| P-01: CSS/JS on ALL admin pages | `shouldEnqueueAssets()` filter by hook suffix (CF-013) |
| P-02: Unbounded PostField queries | Static cache + `posts_per_page` limit (PERF-001) |
| P-03: Unbounded UserField queries | `number` parameter + static cache (CF-011, PERF-007) |
| P-04: Repeated `get_option()` calls | Centralized cache in ActionHandler (PERF-006) |
| P-07: N+1 FieldRenderer in GroupField | Shared instance (PERF-003) |
| P-08: No asset minification | esbuild pipeline with `.min` variants (PERF-008) |
| P-09: No cache busting | `filemtime()` versioning (PERF-009) |
| P-10: BulkOperations unbounded | Pagination added (CF-012) |
| P-11: `wp_enqueue_media()` everywhere | Limited to `post.php` / `post-new.php` only |

---

## 3. Critical Findings

### PERF-C01: Static Cache Key Collisions

**Severity: Critical**
**Files:** `src/Fields/PostField.php`, `src/Fields/TaxonomyField.php`, `src/Fields/UserField.php`

The static caches use field configuration as part of the cache key, but the key generation doesn't account for all config variations. Two PostField instances with different `post_status` filters but same `post_type` may share cached results.

**Impact:** Incorrect field options displayed; data integrity risk.

**Recommendation:** Use `md5(serialize($queryArgs))` as cache key instead of partial config matching.

---

### PERF-C02: Redundant `get_option()` Calls on Config

**Severity: Critical**
**File:** `src/Core/AdminUI/ActionHandler.php`

Despite PERF-006 adding a static cache, the `registerSavedBoxes()` method is called on every `init` hook (priority 20) and reads the full config option. On pages without meta boxes, this is wasted work.

**Recommendation:** Defer config loading until a meta box screen is actually detected, or use an `admin_init` hook with screen check.

---

### PERF-C03: LocalJson Sync on Every `admin_init`

**Severity: Critical**
**File:** `src/Core/LocalJson.php`

`LocalJson::syncFromFiles()` scans the theme directory for JSON files on every `admin_init` request. This involves `glob()` calls and `file_get_contents()` + `json_decode()` for each file, adding filesystem I/O to every admin page load.

**Recommendation:** Cache the JSON file list in a transient with a 5-minute TTL. Only rescan when the transient expires or when a config is saved.

---

### PERF-C04: Dual-Prefix Hook Overhead

**Severity: Critical**
**File:** `src/Core/FieldUtils.php`

Every `FieldUtils::doAction()` and `FieldUtils::applyFilters()` call fires two hooks. On a page rendering 50 fields with 4 hooks each, this means 400 hook calls instead of 200.

**Impact:** Measurable on pages with many fields; benchmarks needed.

**Recommendation:** Add a `CMBB_LEGACY_HOOKS` constant that can be set to `false` to skip the `cmb_` prefix. Default to `true` for backward compatibility.

---

## 4. High Findings

### PERF-H01: N+1 in GroupField Sub-Field Rendering

**Severity: High**
**File:** `src/Fields/GroupField.php`

Despite sharing a FieldRenderer instance (PERF-003), each repeater row still triggers individual `get_post_meta()` calls for each sub-field value. For a group with 5 sub-fields and 10 rows, this is 50 meta queries.

**Recommendation:** Use `get_post_meta($postId)` (no key) to bulk-fetch all meta for the post, then resolve sub-field values from the cached result.

---

### PERF-H02: Redundant `get_post_meta()` in Save Path

**Severity: High**
**File:** `src/Core/MetaBoxManager.php`

The save handler calls `get_post_meta()` to check existing values before `update_post_meta()`. WordPress already handles the "don't update if same" optimization internally.

**Recommendation:** Remove the pre-check and call `update_post_meta()` directly (PERF-004 was partially applied but not consistently).

---

## 5. Medium Findings (7)

| ID | Description | File |
|---|---|---|
| PERF-M01 | Debounce at 150ms may be too short for complex conditional chains | `assets/cmb-script.js` |
| PERF-M02 | DOM traversal on every conditional evaluation — should cache selectors | `assets/cmb-script.js` |
| PERF-M03 | BulkOperations processes all items in single PHP request — should chunk | `src/Core/BulkOperations.php` |
| PERF-M04 | FlexibleContentField clones entire layout HTML — heavy for large layouts | `assets/cmb-script.js` |
| PERF-M05 | GraphQL resolve callback calls `get_post_meta()` per field — no batching | `src/Core/GraphQLIntegration.php` |
| PERF-M06 | FrontendForm enqueues all assets regardless of which fields are used | `src/Core/FrontendForm.php` |
| PERF-M07 | `autoload=false` only applied on update, not on initial option creation | `ActionHandler.php` |

---

## 6. Low Findings (5)

| ID | Description |
|---|---|
| PERF-L01 | No object cache integration (wp_cache_get/set) for config lookups |
| PERF-L02 | `wp_localize_script()` outputs inline JS — `wp_add_inline_script()` preferred in WP 6.x |
| PERF-L03 | CSS custom properties re-declared in multiple files |
| PERF-L04 | No lazy loading for color picker / date picker assets |
| PERF-L05 | esbuild config not tree-shaking unused field type JS |

---

## 7. Performance Scorecard

| Metric | v2.0 | v2.1 | Notes |
|---|---|---|---|
| Asset loading efficiency | 2/10 | 8/10 | Conditional + minified + versioned |
| Query efficiency | 3/10 | 6/10 | Static caches, but N+1 remains |
| Caching strategy | 1/10 | 5/10 | Static caches added; no transient/object cache |
| Scalability (100+ boxes) | 3/10 | 6/10 | Pagination, but LocalJson scans every load |
| JS performance | 4/10 | 7/10 | Debounce, scoped re-index, minified |
| **Overall** | **2.6/10** | **6.4/10** | **Significant improvement** |

**Estimated admin page overhead:** ~50-100ms (down from 200-500ms in v2.0) for typical sites with 20-50 meta boxes.
