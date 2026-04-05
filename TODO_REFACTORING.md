# TODO: Refactoring

**Generated:** 2026-04-05
**Source:** Consolidated from AUDIT_ARCHITECTURE, AUDIT_WP_STANDARDS

Code structure improvements, OOP restructuring, decoupling, and modularization tasks.

---

## RF-001: Extract FieldFactory to Eliminate Duplicated Field Resolution

- **Title:** Field class resolution logic duplicated 9 times across 5 files
- **Description:** The pattern `$fieldClass = 'CMB\\Fields\\' . ucfirst($field['type']) . 'Field'` followed by `class_exists()` and `new $fieldClass(...)` is repeated at least 9 times. Custom field types registered via `MetaBoxManager::registerFieldType()` are only checked in `resolveFieldClass()` -- TaxonomyMetaManager, UserMetaManager, OptionsManager, and FieldRenderer all bypass custom types.
- **Root Cause:** No centralized factory or registry for field instantiation.
- **Proposed Solution:**
  Create `src/Core/FieldFactory.php`:
  ```php
  class FieldFactory {
      public static function create(string $type, array $config): FieldInterface {
          // Check custom registered types first
          // Then fall back to namespace mapping
          // Validate implements FieldInterface
      }
  }
  ```
  Replace all 9 inline resolution sites with `FieldFactory::create()`.
- **Affected Files:**
  - `src/Core/FieldFactory.php` (new)
  - `src/Core/MetaBoxManager.php` (resolveFieldClass, render sites)
  - `src/Core/FieldRenderer.php` (render)
  - `src/Core/TaxonomyMetaManager.php` (renderFields, renderAddFields, saveFields)
  - `src/Core/UserMetaManager.php` (renderFields, saveFields)
  - `src/Core/OptionsManager.php` (renderPage, registerSettings)
- **Estimated Effort:** 6 hours
- **Priority:** P0
- **Dependencies:** None

---

## RF-002: Replace MetaBoxManager Singleton with Dependency Injection

- **Title:** Singleton anti-pattern on MetaBoxManager creates global mutable state
- **Description:** `MetaBoxManager::instance()` is accessed from `public-api.php`, `ImportExport`, `GutenbergPanel`, `BulkOperations`, `DependencyGraph`, and `WpCliCommands`. This creates invisible, undeclared dependencies, prevents testing with mocks, and prevents running parallel test suites.
- **Root Cause:** Classic singleton pattern used for convenience.
- **Proposed Solution:**
  1. Remove the singleton pattern from MetaBoxManager.
  2. Instantiate in `Plugin::boot()` and pass to dependents via constructor injection.
  3. For `public-api.php`, use a global registry function or service locator:
     ```php
     function cmb_manager(): MetaBoxManager {
         return Plugin::getInstance()->getManager();
     }
     ```
- **Affected Files:**
  - `src/Core/MetaBoxManager.php` (remove singleton)
  - `src/Core/Plugin.php` (instantiate and wire)
  - `public-api.php` (use registry)
  - `src/Core/ImportExport.php`
  - `src/Core/GutenbergPanel.php`
  - `src/Core/BulkOperations.php`
  - `src/Core/DependencyGraph.php`
  - `src/Core/WpCliCommands.php`
- **Estimated Effort:** 8 hours
- **Priority:** P0
- **Dependencies:** RF-004

---

## RF-003: Split AdminUI God Class

- **Title:** AdminUI is 1000+ lines mixing routing, rendering, and data persistence
- **Description:** Single class handles: menu registration, routing, list/edit page rendering, field row templates, type picker modal, import modal, notices, save/delete/duplicate/toggle/export/import handlers, runtime box registration, and 10+ helper methods.
- **Root Cause:** All admin functionality was added to a single class incrementally.
- **Proposed Solution:**
  Split into at least 5 classes:
  1. `AdminUI\Router` -- menu registration, page routing, hook registration
  2. `AdminUI\ListPage` -- list view rendering
  3. `AdminUI\EditPage` -- edit form rendering, field row templates
  4. `AdminUI\ActionHandler` -- save, delete, duplicate, toggle, import, export
  5. `AdminUI\FieldRowRenderer` -- field row and sub-field row templates
  Move HTML templates to `views/admin/` directory.
- **Affected Files:**
  - `src/Core/AdminUI.php` (split into multiple files)
  - `src/Core/AdminUI/Router.php` (new)
  - `src/Core/AdminUI/ListPage.php` (new)
  - `src/Core/AdminUI/EditPage.php` (new)
  - `src/Core/AdminUI/ActionHandler.php` (new)
  - `src/Core/AdminUI/FieldRowRenderer.php` (new)
- **Estimated Effort:** 16 hours
- **Priority:** P1
- **Dependencies:** None

---

## RF-004: Introduce Service Provider Pattern

- **Title:** Plugin::boot() is rigid -- no lazy loading, no conditional modules
- **Description:** `boot()` imperatively loads all modules on every request. Adding a new module requires modifying `boot()` directly (OCP violation). No lazy loading -- admin UI hooks are registered on frontend requests.
- **Root Cause:** No service container or provider pattern.
- **Proposed Solution:**
  Create `ServiceProvider` interface:
  ```php
  interface ServiceProvider {
      public function register(Plugin $plugin): void; // bind services
      public function boot(Plugin $plugin): void;     // hook into WP
      public function isNeeded(): bool;                // conditional loading
  }
  ```
  Each module implements this. `Plugin` iterates over registered providers, calling `register()` then `boot()` only if `isNeeded()` returns true.
- **Affected Files:**
  - `src/Core/Contracts/ServiceProvider.php` (new interface)
  - `src/Core/Plugin.php` (refactor boot)
  - Each core module (implement ServiceProvider)
- **Estimated Effort:** 12 hours
- **Priority:** P1
- **Dependencies:** RF-002

---

## RF-005: Extract Shared flattenFields() Utility

- **Title:** flattenFields() copy-pasted into 4 classes
- **Description:** Identical tab-flattening logic is in `MetaBoxManager`, `BulkOperations`, `DependencyGraph`, and `WpCliCommands`.
- **Root Cause:** No shared utility for common operations.
- **Proposed Solution:**
  Create `src/Core/FieldUtils.php`:
  ```php
  class FieldUtils {
      public static function flattenFields(array $fields): array { ... }
  }
  ```
  Replace all 4 copies.
- **Affected Files:**
  - `src/Core/FieldUtils.php` (new)
  - `src/Core/MetaBoxManager.php` (line 152)
  - `src/Core/BulkOperations.php` (line 182)
  - `src/Core/DependencyGraph.php` (line 60)
  - `src/Core/WpCliCommands.php` (lines 47-57)
- **Estimated Effort:** 2 hours
- **Priority:** P1
- **Dependencies:** None

---

## RF-006: Convert Static Classes to Instance-Based

- **Title:** 6 of 10 core classes are entirely static -- defeats OOP benefits
- **Description:** `AdminUI`, `GutenbergPanel`, `ImportExport`, `WpCliCommands`, `DependencyGraph`, `BulkOperations` are entirely static. Cannot be mocked, extended, or substituted.
- **Root Cause:** Procedural thinking in an OOP codebase.
- **Proposed Solution:**
  Convert to instance methods. Have `Plugin` instantiate them and register hooks via instance method references:
  ```php
  $adminUI = new AdminUI($manager);
  add_action('admin_menu', [$adminUI, 'addAdminPage']);
  ```
- **Affected Files:**
  - `src/Core/AdminUI.php`
  - `src/Core/GutenbergPanel.php`
  - `src/Core/ImportExport.php`
  - `src/Core/WpCliCommands.php`
  - `src/Core/DependencyGraph.php`
  - `src/Core/BulkOperations.php`
  - `src/Core/Plugin.php`
- **Estimated Effort:** 10 hours
- **Priority:** P1
- **Dependencies:** RF-002, RF-004

---

## RF-007: Introduce Storage Abstraction Layer

- **Title:** Direct WordPress meta/option function calls scattered everywhere
- **Description:** Each manager directly calls `update_post_meta()`, `get_post_meta()`, `get_term_meta()`, `update_term_meta()`, `get_user_meta()`, `update_user_meta()`, `get_option()`. No `StorageInterface` or repository pattern.
- **Root Cause:** No abstraction layer designed.
- **Proposed Solution:**
  Create storage interfaces and implementations:
  ```
  src/Core/Storage/
    StorageInterface.php    (get, set, delete, getAll)
    PostMetaStorage.php
    TermMetaStorage.php
    UserMetaStorage.php
    OptionStorage.php
  ```
  All managers use the interface. Enables: testing, caching, encryption, custom tables in future.
- **Affected Files:**
  - `src/Core/Storage/` (new directory, 5 new files)
  - `src/Core/MetaBoxManager.php`
  - `src/Core/TaxonomyMetaManager.php`
  - `src/Core/UserMetaManager.php`
  - `src/Core/OptionsManager.php`
  - `src/Core/BulkOperations.php`
  - `src/Core/FieldRenderer.php`
- **Estimated Effort:** 16 hours
- **Priority:** P1
- **Dependencies:** RF-002

---

## RF-008: Unify Rendering Across All Manager Types

- **Title:** FieldRenderer features only available in post meta context
- **Description:** `FieldRenderer` provides meta caching, conditional display, multilingual support, hooks (`cmb_before_render_field`, `cmb_field_html`, `cmb_after_render_field`), layout classes, required indicators, and descriptions. TaxonomyMetaManager, UserMetaManager, and OptionsManager have none of these features -- they inline minimal rendering with direct `echo`.
- **Root Cause:** FieldRenderer was designed for post context only (takes `WP_Post` in constructor).
- **Proposed Solution:**
  1. Create a `RenderContext` interface with implementations for Post, Term, User, and Option contexts.
  2. Refactor `FieldRenderer` to accept `RenderContext` instead of `WP_Post`.
  3. Each manager provides its own context implementation.
  4. All contexts get hooks, conditionals, multilingual support, layout classes.
- **Affected Files:**
  - `src/Core/RenderContext.php` (new interface)
  - `src/Core/RenderContext/PostContext.php` (new)
  - `src/Core/RenderContext/TermContext.php` (new)
  - `src/Core/RenderContext/UserContext.php` (new)
  - `src/Core/RenderContext/OptionContext.php` (new)
  - `src/Core/FieldRenderer.php`
  - `src/Core/TaxonomyMetaManager.php`
  - `src/Core/UserMetaManager.php`
  - `src/Core/OptionsManager.php`
- **Estimated Effort:** 16 hours
- **Priority:** P1
- **Dependencies:** RF-007

---

## RF-009: Expand FieldInterface Contract

- **Title:** FieldInterface is too narrow -- missing essential methods
- **Description:** The interface only declares `render()`, `sanitize()`, `getValue()`, `validate()`. Missing: `getType()`, `getId()`, `getConfig()`, `enqueueAssets()`. External code must cast to `AbstractField` to access field metadata.
- **Root Cause:** Interface was designed minimally.
- **Proposed Solution:**
  ```php
  interface FieldInterface {
      public function render(): string;
      public function sanitize(mixed $value): mixed;
      public function getValue(): mixed;
      public function validate(mixed $value): array;
      public function getType(): string;
      public function getId(): string;
      public function getConfig(): array;
      public function enqueueAssets(): void;
  }
  ```
- **Affected Files:**
  - `src/Core/Contracts/FieldInterface.php`
  - `src/Core/Contracts/Abstracts/AbstractField.php` (add default implementations)
- **Estimated Effort:** 3 hours
- **Priority:** P1
- **Dependencies:** None

---

## RF-010: Fix AbstractField::getValue() Falsy Value Bug

- **Title:** getValue() treats 0, "0", false as empty -- data loss bug
- **Description:** `!empty($this->config['value'])` returns false for `0`, `"0"`, `false`, and `""`. A number field set to `0` will return the default instead of the actual value.
- **Root Cause:** Use of `!empty()` instead of explicit null/existence check.
- **Proposed Solution:**
  ```php
  public function getValue(): mixed {
      if (array_key_exists('value', $this->config) && $this->config['value'] !== null) {
          return $this->config['value'];
      }
      return $this->config['default'] ?? null;
  }
  ```
- **Affected Files:**
  - `src/Core/Contracts/Abstracts/AbstractField.php` (getValue, line 27)
- **Estimated Effort:** 0.5 hours
- **Priority:** P1
- **Dependencies:** None

---

## RF-011: Fix GroupField::sanitize() to Use Per-Type Sanitization

- **Title:** GroupField::sanitize() applies sanitize_text_field to all sub-fields
- **Description:** `map_deep($value, 'sanitize_text_field')` strips HTML from WYSIWYG sub-fields and doesn't validate emails/URLs. The proper per-type logic exists in `MetaBoxManager::sanitizeGroupValue()` but is not available when GroupField is used in taxonomy/user/options contexts.
- **Root Cause:** GroupField::sanitize() was a quick fallback that became the actual path for non-post contexts.
- **Proposed Solution:**
  Move the recursive per-type sanitization logic from `MetaBoxManager::sanitizeGroupValue()` into `GroupField::sanitize()`. Use `FieldFactory` (RF-001) to instantiate sub-fields for sanitization.
- **Affected Files:**
  - `src/Fields/GroupField.php` (sanitize method, line 85)
  - `src/Core/MetaBoxManager.php` (sanitizeGroupValue -- delegate to GroupField)
- **Estimated Effort:** 4 hours
- **Priority:** P1
- **Dependencies:** RF-001

---

## RF-012: Standardize Public API Instantiation Patterns

- **Title:** Three different instantiation patterns in public-api.php
- **Description:** `add_custom_meta_box` uses a singleton; `add_custom_taxonomy_meta`, `add_custom_user_meta`, `add_custom_options_page` each use `static` local variables with lazy initialization.
- **Root Cause:** Incremental development without consistency review.
- **Proposed Solution:**
  Register all managers during `Plugin::boot()` and have API functions delegate to pre-registered instances stored in a registry.
- **Affected Files:**
  - `public-api.php`
  - `src/Core/Plugin.php`
- **Estimated Effort:** 4 hours
- **Priority:** P1
- **Dependencies:** RF-002, RF-004

---

## RF-013: Fix deletePostMetaData Post Type Filtering

- **Title:** Post deletion triggers meta delete for ALL registered meta boxes regardless of post type
- **Description:** `deletePostMetaData()` iterates all meta boxes and deletes all field meta keys for any deleted post, regardless of whether the post type matches the meta box's `postTypes`.
- **Root Cause:** No post type check before deletion.
- **Proposed Solution:**
  ```php
  public function deletePostMetaData(int $postId): void {
      $postType = get_post_type($postId);
      foreach ($this->metaBoxes as $metaBox) {
          if (!in_array($postType, $metaBox['postTypes'], true)) {
              continue;
          }
          // ... delete fields
      }
  }
  ```
- **Affected Files:**
  - `src/Core/MetaBoxManager.php` (deletePostMetaData, line 348)
- **Estimated Effort:** 1 hour
- **Priority:** P1
- **Dependencies:** None

---

## RF-014: Remove/Fix ArrayAccessibleTrait Dead Code

- **Title:** ArrayAccessibleTrait is unused and misnamed
- **Description:** This trait declares `protected array $config = []` with `__get` and `__isset` magic methods. No class uses it. It would conflict with `AbstractField::$config`. Its name suggests `ArrayAccess` but it implements magic property access.
- **Root Cause:** Dead code from early development.
- **Proposed Solution:** Remove the trait file entirely. If array access semantics are needed later, implement the actual `ArrayAccess` interface.
- **Affected Files:**
  - `src/Core/Traits/ArrayAccessibleTrait.php` (delete)
- **Estimated Effort:** 0.5 hours
- **Priority:** P2
- **Dependencies:** None

---

## RF-015: Rename Methods to WordPress snake_case Convention

- **Title:** All method names use camelCase instead of WordPress snake_case
- **Description:** 100+ methods across all classes use `camelCase` (`addMetaBoxes`, `saveMetaBoxData`, `handleSave`, etc.) instead of WordPress standard `snake_case` (`add_meta_boxes`, `save_meta_box_data`).
- **Root Cause:** PSR-style PHP naming used instead of WordPress conventions.
- **Proposed Solution:**
  Rename all public methods to snake_case. This is a major breaking change -- consider doing it as part of a v3.0 release with a deprecation layer:
  ```php
  public function add_meta_boxes() { ... }
  /** @deprecated Use add_meta_boxes() */
  public function addMetaBoxes() { return $this->add_meta_boxes(); }
  ```
- **Affected Files:**
  - All PHP files in src/
- **Estimated Effort:** 12 hours
- **Priority:** P2
- **Dependencies:** All other refactoring tasks

---

## RF-016: Add Comprehensive Test Suite

- **Title:** Only 5 trivial tests across 3 files -- near-zero coverage
- **Description:** Current tests: PluginTest (1 assertion), MetaBoxManagerTest (2 assertions), TextFieldTest (2 assertions). Zero tests for: 17 other field types, validation logic, save flow, nonce verification, REST API, taxonomy/user/options managers, import/export, bulk operations, conditional rendering, AdminUI.
- **Root Cause:** Testing was not prioritized.
- **Proposed Solution:**
  1. Add unit tests for every field type's `render()`, `sanitize()`, `validate()`.
  2. Add integration tests for save/load flows using Brain\Monkey or WP Test Utils.
  3. Add tests for AdminUI action handlers.
  4. Target 70%+ code coverage.
  5. Add `phpstan/phpstan` and `phpcs` with WordPress ruleset to CI.
- **Affected Files:**
  - `tests/` (20+ new test files)
  - `composer.json` (add brain/monkey, phpstan, phpcs)
  - `phpunit.xml.dist` (update config)
- **Estimated Effort:** 40 hours
- **Priority:** P1
- **Dependencies:** RF-001, RF-002

---

## Summary

| ID | Title | Priority | Effort (hrs) | Dependencies |
|----|-------|----------|--------------|--------------|
| RF-001 | Extract FieldFactory | P0 | 6 | None |
| RF-002 | Replace singleton with DI | P0 | 8 | RF-004 |
| RF-003 | Split AdminUI god class | P1 | 16 | None |
| RF-004 | Service provider pattern | P1 | 12 | RF-002 |
| RF-005 | Extract flattenFields utility | P1 | 2 | None |
| RF-006 | Convert static to instance classes | P1 | 10 | RF-002, RF-004 |
| RF-007 | Storage abstraction layer | P1 | 16 | RF-002 |
| RF-008 | Unify rendering across managers | P1 | 16 | RF-007 |
| RF-009 | Expand FieldInterface | P1 | 3 | None |
| RF-010 | Fix falsy value bug | P1 | 0.5 | None |
| RF-011 | Fix GroupField sanitize | P1 | 4 | RF-001 |
| RF-012 | Standardize public API | P1 | 4 | RF-002, RF-004 |
| RF-013 | Fix deletePostMetaData filtering | P1 | 1 | None |
| RF-014 | Remove ArrayAccessibleTrait | P2 | 0.5 | None |
| RF-015 | Rename to snake_case | P2 | 12 | All |
| RF-016 | Comprehensive test suite | P1 | 40 | RF-001, RF-002 |
| **Total** | | | **151** | |
