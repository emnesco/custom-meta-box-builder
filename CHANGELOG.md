# Changelog

All notable changes to Custom Meta Box Builder will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2026-04-06

### Added
- **FlexibleContentField** — layout-based content building (FEAT-014)
- **FrontendForm** — `cmb_render_form()` / `cmb_the_form()` for frontend rendering (FEAT-015)
- **BlockRegistration** — `cmb_register_block()` API for Gutenberg blocks (FEAT-020)
- **GraphQL integration** — auto-registers CMB fields in WPGraphQL schema (FEAT-021)
- **LocalJson sync** — saves field configs as JSON files in theme for version control (FEAT-022)
- **Expanded Gutenberg sidebar** — radio, color, date, toggle, file/image field mappings (FEAT-016)
- **Hook prefix migration** — dual-firing `cmbbuilder_` (new) and `cmb_` (backward compat) via `FieldUtils` helpers (DX-006)
- **CONTRIBUTING.md** — development setup, coding standards, PR checklist (DX-010)
- **PHPDoc annotations** — full docblocks on all 13 hook call sites and all 66 PHP files (DX-004, DX-005)
- Improved error messages with actionable guidance in `_doing_it_wrong()` calls (DX-007)
- Type aliases in FieldFactory for `flexible_content` and `checkbox_list` types

### Changed
- Standardized all JS files to ES6+ (`const`/`let`, arrow functions, destructuring) (FE-015)

## [2.0.0] - 2026-04-05

### Added
- **New field types:** TimeField, RangeField, ToggleField, MessageField, DividerField, ImageField, GalleryField, CheckboxListField
- **Multi-select** support in SelectField with placeholder option (FEAT-002)
- **ColorField** enhanced with wp-color-picker and alpha/rgba support (FEAT-018)
- **Public API** — `cmb_get_field()`, `cmb_the_field()`, `cmb_get_term_field()`, `cmb_get_user_field()`, `cmb_get_option()` (FEAT-001)
- **AjaxHandler** — nonce-verified search endpoints for posts, users, terms (FEAT-004)
- **LocationMatcher** — AND/OR rule matching for post_type, page_template, post_status, etc. (FEAT-005)
- **AND/OR conditional logic** — `data-conditional-groups` with group-level AND, cross-group OR/AND (FEAT-013)
- **PHP code export** from Admin UI (FEAT-017)
- **Before/after save hooks** on user and taxonomy meta managers (FEAT-019)
- **ARIA tab navigation** with arrow/Home/End keyboard support (FE-001)
- **Client-side validation** with blur handler and form submit prevention (FE-008)
- **CSS custom properties** — `:root` block with `--cmb-*` variables for theming (FE-012)
- **Two-step responsive breakpoints** at 1200px and 782px (FE-011)
- **Tab focus-visible styles** for keyboard navigation (FE-010)
- **Screen-reader-text** legends on RadioField, TaxonomyField, and group search (FE-002, FE-003)
- **Hidden input fallback** for unchecked checkbox state (FE-004)
- **Sortable + color picker re-init** on cloned rows (FE-007)
- **wp.media alert** when unavailable, clipboard `.catch()` fallback (FE-014)
- **Brain\Monkey test infrastructure** with 114 unit tests (DX-003)
- **esbuild** build/watch scripts for JS+CSS minification (DX-001)
- **GitHub Actions CI** with PHP 8.1/8.2/8.3 matrix (DX-009)
- **uninstall.php** for clean option removal (DX-012)
- **readme.txt** in WordPress.org standard format (DX-001)
- **LICENSE** file (GPL-2.0-or-later) (DX-002)
- **.distignore** for clean distribution builds (DX-008)

### Changed
- **AdminUI refactored** from monolithic class into Router, ListPage, EditPage, ActionHandler (RF-003)
- **RenderContext pattern** for unified field rendering across post/term/user/option contexts (RF-008)
- **StorageInterface** with PostMeta/TermMeta/UserMeta/OptionStorage implementations (RF-007)
- **ServiceProvider pattern** with conditional loading for modular features (RF-004)
- **FieldFactory** with type registry and FieldInterface validation (RF-001, SEC-006)
- **Gutenberg panel** uses `wp.editor` with `wp.editPost` fallback (FE-006)
- Removed unused `postId` `useSelect` in Gutenberg (FE-009)
- Removed unused `ArrayAccessibleTrait` (RF-014)

### Security
- Added `wp_unslash()` to all `$_POST` reads in BulkOperations, UserMetaManager, ActionHandler (SEC-002)
- Taxonomy nonces made taxonomy-specific to prevent cross-taxonomy CSRF (SEC-003)
- Regex pattern validation capped at 500 chars with `/u` flag (SEC-004)
- Sanitized min/max/step and option keys/labels in field config (SEC-005)
- Password field never renders stored values; shows placeholder dots (SEC-007)
- WP-CLI `setField` routes through field sanitize pipeline (SEC-008)
- 1MB size limit on textarea paste import (SEC-009)
- Cache-control headers on export responses (SEC-010)

### Performance
- Static post/term/user caches in relational fields (PERF-001, 002, 007)
- Shared FieldRenderer instance across group sub-fields (PERF-003)
- Removed unnecessary delete before set for scalar meta (PERF-004)
- `autoload=false` on config option updates (PERF-005)
- Centralized config access with static cache in ActionHandler (PERF-006)
- SCRIPT_DEBUG conditional loading with filemtime versioning (PERF-008, 009)
- Debounced conditional evaluation at 150ms (PERF-010)
- Early bail-out for unregistered post types in saveMetaBoxData (PERF-011)
- Batched revision meta copy using getAll() bulk fetch (PERF-012)
- Admin list pagination at 20 per page with hoisted get_post_types (PERF-014)
- Sortable re-index limited to affected range only (PERF-015)

### Fixed
- Color contrast improved for WCAG AA (#757575 → #595959, #787c82 → #50575e) (FE-005)
- `sanitize_callback` added to REST field registration (DX-011)
