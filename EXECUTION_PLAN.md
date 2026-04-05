# Execution Plan

**Project:** Custom Meta Box Builder v2.0 → v3.0 Transformation
**Started:** 2026-04-05
**Goal:** Production-ready, WordPress.org compliant, competitive with ACF/CMB2/Meta Box

---

## Phase 0: Critical Stabilization (ALL P0)

| ID | Task | Status | Dependencies |
|----|------|--------|-------------|
| CF-008 | Add ABSPATH guard to public-api.php | Pending | None |
| CF-003 | Add taxonomy capability check | Pending | None |
| CF-002 | Escape dynamic HTML attributes in FieldRenderer | Pending | None |
| CF-001 | Sanitize imported field configurations | Pending | None |
| CF-009 | Fix XSS in JS file upload preview | Pending | None |
| CF-011 | Fix UserField unbounded query | Pending | None |
| CF-012 | Fix BulkOperations unbounded query | Pending | None |
| CF-010 | Fix semantic HTML for interactive elements | Pending | None |
| CF-013 | Conditional asset loading per screen | Pending | None |
| CF-014 | Boot plugin on plugins_loaded hook | Pending | None |
| CF-005 | Add activation/deactivation hooks | Pending | CF-014 |
| CF-004 | Create uninstall.php | Pending | None |
| CF-006 | Add required plugin header fields | Pending | None |
| CF-007 | Add i18n infrastructure | Pending | CF-006 |

## Phase 1: Core Architecture (BLOCKING)

| ID | Task | Status | Dependencies |
|----|------|--------|-------------|
| RF-001 | Extract FieldFactory | Pending | None |
| RF-002 | Replace singleton with DI | Pending | RF-004 |
| RF-005 | Extract flattenFields utility | Pending | None |
| RF-009 | Expand FieldInterface | Pending | None |
| RF-010 | Fix AbstractField::getValue() falsy bug | Pending | None |
| RF-013 | Fix deletePostMetaData post type filter | Pending | None |
| RF-011 | Fix GroupField::sanitize() per-type | Pending | RF-001 |
| RF-003 | Split AdminUI god class | Pending | None |
| RF-004 | Service provider pattern | Pending | RF-002 |
| RF-006 | Convert static to instance classes | Pending | RF-002 |
| RF-007 | Storage abstraction layer | Pending | RF-002 |
| RF-008 | Unify rendering across managers | Pending | RF-007 |
| RF-012 | Standardize public API | Pending | RF-002 |
| RF-014 | Remove ArrayAccessibleTrait | Pending | None |

## Phase 2: Core API Layer (P0 Features)

| ID | Task | Status | Dependencies |
|----|------|--------|-------------|
| FEAT-001 | Value retrieval API (get_field) | Pending | None |
| FEAT-002 | Multi-select field | Pending | None |
| FEAT-003 | Checkbox list field | Pending | None |
| FEAT-004 | AJAX relational fields | Pending | PERF-008 |
| FEAT-005 | Richer location rules | Pending | None |

## Phase 3: Performance & Data Layer

| ID | Task | Status | Dependencies |
|----|------|--------|-------------|
| PERF-001 | PostField static cache | Pending | None |
| PERF-002 | TaxonomyField static cache | Pending | None |
| PERF-003 | Share FieldRenderer in GroupField | Pending | None |
| PERF-004 | Optimize save pattern | Pending | None |
| PERF-005 | Set autoload=false on config | Pending | CF-005 |
| PERF-006 | Centralize get_option cache | Pending | None |
| PERF-007 | Transient caching for queries | Pending | PERF-001 |
| PERF-008 | Asset minification pipeline | Pending | None |
| PERF-009 | Asset version cache busting | Pending | None |
| PERF-010 | Debounce conditionals | Pending | None |
| PERF-011 | Scope save_post to post types | Pending | None |

## Phase 4-7: See TODO files for full task lists
