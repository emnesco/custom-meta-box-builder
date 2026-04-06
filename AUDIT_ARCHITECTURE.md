# Architecture & Code Quality Audit Report

**Plugin:** Custom Meta Box Builder v2.1
**Audit Date:** 2026-04-06 (Re-audit)
**Previous Audit:** 2026-04-05 (v2.0)
**Auditor:** Architecture & Code Quality Agent
**Scope:** Full codebase review (66 PHP files, excluding vendor/)

---

## 1. Executive Summary

The v2.1 re-audit acknowledges **significant architectural improvements** over v2.0:

**Resolved from v2.0:**
- Singleton anti-pattern replaced with DI via `Plugin::getInstance()` + `ServiceProvider` pattern
- AdminUI god class split into Router, ListPage, EditPage, ActionHandler (RF-003)
- Service container pattern via `ServiceProvider` with conditional `isNeeded()` loading (RF-004)
- Storage abstraction with `StorageInterface` and PostMeta/TermMeta/UserMeta/OptionStorage (RF-007)
- `RenderContext` pattern unifying field rendering across managers (RF-008)
- `FieldFactory` with type registry and `FieldInterface` validation (RF-001)
- `ArrayAccessibleTrait` removed (RF-014)
- Test infrastructure expanded from 3 to 114 unit tests (DX-003)

**Remaining concerns:**
- `ActionHandler` has grown to ~550 lines (new god class)
- `handleSave()` method at 154 lines exceeds recommended limits
- Circular dependency: GroupField → FieldRenderer → FieldFactory → GroupField
- Static method overuse persists in utility classes
- New modules (GraphQL, LocalJson, BlockRegistration) use static-heavy patterns

**Findings Summary:**
| Severity | Count | Change from v2.0 |
|----------|-------|-------------------|
| Critical | 6     | New patterns       |
| High     | 6     | Shifted focus      |
| Medium   | 19    | More granular      |
| Low      | 8     | Deeper analysis    |

---

## 2. Resolved Findings from v2.0

| v2.0 Finding | Resolution |
|---|---|
| Singleton anti-pattern on MetaBoxManager | Replaced with Plugin instance + DI (RF-002) |
| No service container | ServiceProvider pattern with conditional loading (RF-004) |
| AdminUI god class (~1000 lines) | Split into Router, ListPage, EditPage, ActionHandler (RF-003) |
| Code duplication in field rendering | RenderContext pattern (RF-008) |
| No storage abstraction | StorageInterface with 4 implementations (RF-007) |
| No field registry | FieldFactory with type registry + FieldInterface (RF-001) |
| Minimal test coverage (3 tests) | 114 unit tests with Brain\Monkey (DX-003) |

---

## 3. Critical Findings

### ARCH-C01: ActionHandler God Class

**Severity: Critical**
**File:** `src/Core/AdminUI/ActionHandler.php` (~550 lines)

The AdminUI split moved complexity from the old monolithic class into ActionHandler, which now handles: save, delete, duplicate, import, export, bulk actions, and meta box registration. This is effectively a new god class.

**Recommendation:** Extract `ImportExportHandler`, `BulkActionHandler`, and `MetaBoxRegistrar` as separate classes. Each handler should be <200 lines.

---

### ARCH-C02: handleSave() Method Complexity

**Severity: Critical**
**File:** `src/Core/AdminUI/ActionHandler.php` — `handleSave()` (~154 lines)

Single method handles field validation, config assembly, option update, hook firing, and redirect. Cyclomatic complexity is high.

**Recommendation:** Extract `validateConfig()`, `assembleConfig()`, and `persistConfig()` private methods.

---

### ARCH-C03: Circular Dependency Chain

**Severity: Critical**
**Files:** `GroupField.php` → `FieldRenderer.php` → `FieldFactory.php` → `GroupField.php`

GroupField creates a FieldRenderer to render sub-fields. FieldRenderer uses FieldFactory to instantiate fields. FieldFactory can create GroupField instances. This circular chain makes isolated testing impossible.

**Recommendation:** Introduce a `FieldRendererInterface` and inject it into GroupField, breaking the concrete dependency.

---

### ARCH-C04: Silent Error Suppression

**Severity: Critical**
**Files:** `AbstractField.php` (`@preg_match`), `Plugin.php` (`@filemtime`)

The `@` error suppression operator hides failures silently. A malformed regex pattern or missing file produces no diagnostic output.

**Recommendation:** Use `preg_match()` return value check and `file_exists()` guard instead of `@`.

---

### ARCH-C05: Unsafe Include in BlockRegistration

**Severity: Critical**
**File:** `src/Core/BlockRegistration.php`

The `renderBlock()` method uses `include $config['render_template']` with the path sourced from block configuration. If configs are stored in the database (via admin UI), this is a file inclusion vulnerability.

**Recommendation:** Validate path with `realpath()` and ensure it's within the theme/plugin directory.

---

### ARCH-C06: Static-Heavy New Modules

**Severity: Critical**
**Files:** `LocalJson.php`, `GraphQLIntegration.php`, `BlockRegistration.php`, `FrontendForm.php`

All four new v2.1 modules use exclusively static methods and class-level state. This contradicts the DI pattern established in the core refactoring and makes these modules untestable.

**Recommendation:** Convert to instance classes and register via ServiceProvider pattern, consistent with existing architecture.

---

## 4. High Findings

### ARCH-H01: Inconsistent Naming Conventions

**Severity: High**

Mixed naming patterns across the codebase:
- `Checkbox_listField` (underscore in class name) vs `CheckboxListField` convention
- `handleSave()` vs `registerSavedBoxes()` vs `register()` — inconsistent verb patterns
- `FieldUtils::doAction()` vs `FieldUtils::applyFilters()` — correct but `do_action` wrapper naming could collide with expectations

---

### ARCH-H02: No Interface for New Modules

**Severity: High**
**Files:** `FrontendForm.php`, `BlockRegistration.php`, `GraphQLIntegration.php`, `LocalJson.php`

None of the v2.1 modules implement interfaces, unlike the core storage layer which properly uses `StorageInterface`. This inconsistency means these modules can't be swapped or mocked.

---

### ARCH-H03: FieldUtils Dual-Prefix Hooks Performance

**Severity: High**
**File:** `src/Core/FieldUtils.php`

Every hook fires twice (once with `cmbbuilder_` prefix, once with `cmb_` prefix). On pages with many fields, this doubles the hook execution overhead. No deprecation timeline or mechanism to eventually remove the legacy prefix.

**Recommendation:** Add `_deprecated_hook()` call on the `cmb_` prefix to signal migration path. Plan removal in v3.0.

---

### ARCH-H04: MetaBoxManager Static Instance Retained

**Severity: High**
**File:** `src/Core/MetaBoxManager.php`

While `Plugin::getInstance()` provides DI, `MetaBoxManager::setInstance()` / `MetaBoxManager::instance()` static accessors still exist and are used by legacy code paths.

---

### ARCH-H05: No Error Boundary Pattern

**Severity: High**

Exceptions in field rendering, saving, or AJAX handlers propagate uncaught. A single broken field can crash the entire post editor.

**Recommendation:** Wrap field operations in try/catch with `_doing_it_wrong()` logging.

---

### ARCH-H06: Code Duplication in Manager Classes

**Severity: High**
**Files:** `TaxonomyMetaManager.php`, `UserMetaManager.php`, `OptionsManager.php`

Despite the `RenderContext` pattern, the three managers still have ~60% duplicated code for field iteration, save handling, and hook registration. An abstract `AbstractMetaManager` would eliminate this.

---

## 5. Medium Findings (19)

| ID | Description | File(s) |
|---|---|---|
| ARCH-M01 | No PSR-12 strict types declarations | All 66 PHP files |
| ARCH-M02 | Mixed return type conventions (nullable vs union) | Multiple fields |
| ARCH-M03 | `FieldFactory::$typeAliases` is a static array — not extensible | `FieldFactory.php` |
| ARCH-M04 | Plugin boot order not documented | `Plugin.php` |
| ARCH-M05 | ServiceProvider `boot()` vs `register()` distinction unclear | `Contracts/ServiceProvider.php` |
| ARCH-M06 | No event/observer pattern for config changes | `ActionHandler.php` |
| ARCH-M07 | `FrontendForm::processSubmission()` mixes concerns (auth + save + redirect) | `FrontendForm.php` |
| ARCH-M08 | `GraphQLIntegration` maps all complex types to String | `GraphQLIntegration.php` |
| ARCH-M09 | `LocalJson::syncFromFiles()` runs on every `admin_init` (no caching) | `LocalJson.php` |
| ARCH-M10 | Trait usage (`MultiLanguageTrait`, `ConditionalTrait`) creates hidden coupling | Multiple |
| ARCH-M11 | No abstract method contracts on `AbstractField` for `format()` | `AbstractField.php` |
| ARCH-M12 | `AjaxHandler` search methods share 80% code — extract base search | `AjaxHandler.php` |
| ARCH-M13 | `DependencyGraph` tightly coupled to specific field types | `DependencyGraph.php` |
| ARCH-M14 | No interface for `ImportExport` — can't swap JSON for other formats | `ImportExport.php` |
| ARCH-M15 | `WpCliCommands` not using WP-CLI's built-in `Formatter` for output | `WpCliCommands.php` |
| ARCH-M16 | Autoloader registered in main plugin file, not in a dedicated loader | `custom-meta-box-builder.php` |
| ARCH-M17 | Test doubles not used consistently — some tests mock, others don't | `tests/` |
| ARCH-M18 | No changelog automation or version constant sync | `CHANGELOG.md` vs plugin header |
| ARCH-M19 | `FlexibleContentField` duplicates GroupField rendering logic | `FlexibleContentField.php` |

---

## 6. Low Findings (8)

| ID | Description |
|---|---|
| ARCH-L01 | PHPDoc `@since` tags inconsistent (some say 2.0, some 1.0) |
| ARCH-L02 | No `final` keyword on leaf classes that shouldn't be extended |
| ARCH-L03 | `composer.json` missing `conflict` section for incompatible plugin versions |
| ARCH-L04 | `.distignore` doesn't exclude `AUDIT_*.md` files |
| ARCH-L05 | `CONTRIBUTING.md` references features not yet stable |
| ARCH-L06 | No PHP 8.4 compatibility notes |
| ARCH-L07 | `readme.txt` changelog section doesn't match `CHANGELOG.md` |
| ARCH-L08 | Git hooks not configured for code style enforcement |

---

## 7. Architecture Scorecard

| Dimension | v2.0 Score | v2.1 Score | Notes |
|---|---|---|---|
| Separation of Concerns | 3/10 | 7/10 | AdminUI split, ServiceProvider, RenderContext |
| Dependency Management | 2/10 | 6/10 | DI via Plugin, but static modules remain |
| Testability | 2/10 | 6/10 | 114 tests, Brain\Monkey, but circular deps |
| Extensibility | 4/10 | 7/10 | FieldFactory, hooks, but new modules not extensible |
| Code Duplication | 3/10 | 5/10 | RenderContext helped, managers still duplicate |
| Naming Consistency | 5/10 | 6/10 | Improved but Checkbox_listField persists |
| Error Handling | 3/10 | 4/10 | `_doing_it_wrong()` added, but no boundaries |
| **Overall** | **3.1/10** | **5.9/10** | **Significant improvement** |
