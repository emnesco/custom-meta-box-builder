# Execution Plan

**Project:** Custom Meta Box Builder v2.0 → v3.0 Transformation
**Started:** 2026-04-05
**Completed:** 2026-04-06
**Goal:** Production-ready, WordPress.org compliant, competitive with ACF/CMB2/Meta Box

---

## Phase 0: Critical Stabilization (ALL P0)

| ID | Task | Status | Dependencies |
|----|------|--------|-------------|
| CF-008 | Add ABSPATH guard to public-api.php | Done | None |
| CF-003 | Add taxonomy capability check | Done | None |
| CF-002 | Escape dynamic HTML attributes in FieldRenderer | Done | None |
| CF-001 | Sanitize imported field configurations | Done | None |
| CF-009 | Fix XSS in JS file upload preview | Done | None |
| CF-011 | Fix UserField unbounded query | Done | None |
| CF-012 | Fix BulkOperations unbounded query | Done | None |
| CF-010 | Fix semantic HTML for interactive elements | Done | None |
| CF-013 | Conditional asset loading per screen | Done | None |
| CF-014 | Boot plugin on plugins_loaded hook | Done | None |
| CF-005 | Add activation/deactivation hooks | Done | CF-014 |
| CF-004 | Create uninstall.php | Done | None |
| CF-006 | Add required plugin header fields | Done | None |
| CF-007 | Add i18n infrastructure | Done | CF-006 |

## Phase 1: Core Architecture (BLOCKING)

| ID | Task | Status | Dependencies |
|----|------|--------|-------------|
| RF-001 | Extract FieldFactory | Done | None |
| RF-002 | Replace singleton with DI | Done | RF-004 |
| RF-005 | Extract flattenFields utility | Done | None |
| RF-009 | Expand FieldInterface | Done | None |
| RF-010 | Fix AbstractField::getValue() falsy bug | Done | None |
| RF-013 | Fix deletePostMetaData post type filter | Done | None |
| RF-011 | Fix GroupField::sanitize() per-type | Done | RF-001 |
| RF-003 | Split AdminUI god class | Done | None |
| RF-004 | Service provider pattern | Done | RF-002 |
| RF-006 | Convert static to instance classes | Done | RF-002 |
| RF-007 | Storage abstraction layer | Done | RF-002 |
| RF-008 | Unify rendering across managers | Done | RF-007 |
| RF-012 | Standardize public API | Done | RF-002 |
| RF-014 | Remove ArrayAccessibleTrait | Done | None |

## Phase 2: Core API Layer (P0 Features)

| ID | Task | Status | Dependencies |
|----|------|--------|-------------|
| FEAT-001 | Value retrieval API (get_field) | Done | None |
| FEAT-002 | Multi-select field | Done | None |
| FEAT-003 | Checkbox list field | Done | None |
| FEAT-004 | AJAX relational fields | Done | PERF-008 |
| FEAT-005 | Richer location rules | Done | None |

## Phase 3: Performance & Data Layer

| ID | Task | Status | Dependencies |
|----|------|--------|-------------|
| PERF-001 | PostField static cache | Done | None |
| PERF-002 | TaxonomyField static cache | Done | None |
| PERF-003 | Share FieldRenderer in GroupField | Done | None |
| PERF-004 | Optimize save pattern | Done | None |
| PERF-005 | Set autoload=false on config | Done | CF-005 |
| PERF-006 | Centralize get_option cache | Done | None |
| PERF-007 | UserField static cache | Done | PERF-001 |
| PERF-008 | Asset minification pipeline | Done | None |
| PERF-009 | Asset version cache busting | Done | None |
| PERF-010 | Debounce conditionals | Done | None |
| PERF-011 | Scope save_post to post types | Done | None |
| PERF-012 | Batched revision meta copy | Done | None |
| PERF-014 | Admin list pagination | Done | None |
| PERF-015 | Optimized sortable re-index | Done | None |

## Phase 4: Features

| ID | Task | Status | Dependencies |
|----|------|--------|-------------|
| FEAT-006 | Time picker field | Done | None |
| FEAT-007 | Range/slider field | Done | None |
| FEAT-008 | Toggle field | Done | None |
| FEAT-009 | Message/heading field | Done | None |
| FEAT-010 | Divider field | Done | None |
| FEAT-011 | Dedicated image field | Done | None |
| FEAT-012 | Gallery field | Done | None |
| FEAT-013 | AND/OR conditional logic | Done | None |
| FEAT-014 | Flexible content field | Done | RF-001, FEAT-001 |
| FEAT-015 | Frontend form rendering | Done | RF-008, FEAT-001 |
| FEAT-016 | Expanded Gutenberg sidebar | Done | None |
| FEAT-017 | PHP code export | Done | None |
| FEAT-018 | Color picker with alpha | Done | None |
| FEAT-019 | Additional developer hooks | Done | RF-008 |
| FEAT-020 | Gutenberg block registration | Done | FEAT-016 |
| FEAT-021 | GraphQL support (WPGraphQL) | Done | FEAT-001 |
| FEAT-022 | Local JSON sync | Done | None |

## Phase 5: Frontend

| ID | Task | Status | Dependencies |
|----|------|--------|-------------|
| FE-001 | Tab ARIA roles | Done | None |
| FE-002 | Fieldset legends | Done | None |
| FE-003 | Search input label | Done | None |
| FE-004 | Checkbox duplicate label fix | Done | None |
| FE-005 | Color contrast fixes | Done | None |
| FE-006 | Update deprecated Gutenberg API | Done | None |
| FE-007 | Re-init sortable for dynamic groups | Done | None |
| FE-008 | Client-side validation | Done | None |
| FE-009 | Remove unused postId selector | Done | None |
| FE-010 | Tab focus styles | Done | None |
| FE-011 | Lower width breakpoint | Done | None |
| FE-012 | CSS custom properties | Done | None |
| FE-014 | JS error handling | Done | None |
| FE-015 | Standardize JS ES version | Done | PERF-008 |

## Phase 6: Security

| ID | Task | Status | Dependencies |
|----|------|--------|-------------|
| SEC-002 | wp_unslash on $_POST reads | Done | None |
| SEC-003 | Taxonomy-specific nonces | Done | None |
| SEC-004 | Regex pattern length cap | Done | None |
| SEC-005 | Sanitize min/max/step fields | Done | None |
| SEC-006 | FieldInterface validation in FieldFactory | Done | None |
| SEC-007 | Password field masking | Done | None |
| SEC-008 | CLI field sanitize pipeline | Done | None |
| SEC-009 | Textarea import size limit | Done | None |
| SEC-010 | Cache-control headers on export | Done | None |

## Phase 7: Developer Experience

| ID | Task | Status | Dependencies |
|----|------|--------|-------------|
| DX-001 | Add readme.txt | Done | None |
| DX-002 | Add LICENSE file | Done | None |
| DX-003 | Update composer.json | Done | None |
| DX-004 | PHPDoc hook annotations | Done | None |
| DX-005 | File/class docblocks | Done | None |
| DX-006 | Rename hook prefix (cmbbuilder_) | Done | FEAT-019 |
| DX-007 | Docs links in error messages | Done | None |
| DX-008 | Exclude dev deps from dist | Done | None |
| DX-009 | CI/CD pipeline | Done | DX-003 |
| DX-010 | Contribution guide | Done | None |
| DX-011 | REST API write validation | Done | None |

---

## Summary

**Total tasks:** 103
**Completed:** 103
**Remaining:** 0
