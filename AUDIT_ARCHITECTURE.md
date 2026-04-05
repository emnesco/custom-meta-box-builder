# Architecture & Code Quality Audit Report

**Plugin:** Custom Meta Box Builder v2.0  
**Audit Date:** 2026-04-05  
**Auditor:** Agent 1 -- Architecture & Code Quality  
**Scope:** Full codebase review (excluding vendor/)

---

## 1. Executive Summary

Custom Meta Box Builder is a WordPress plugin providing a programmatic and visual (Admin UI) interface for registering custom meta boxes, taxonomy meta, user meta, and options pages. The codebase demonstrates reasonable use of modern PHP (PSR-4 autoloading, interfaces, abstract classes, traits) but suffers from several significant architectural issues that would impede scaling, testability, and long-term maintenance.

**Key concerns:**
- **Singleton anti-pattern** on `MetaBoxManager` creates tight coupling and makes testing difficult.
- **God-class tendencies** in `MetaBoxManager` and especially `AdminUI` (~1000 lines of mixed HTML/PHP).
- **Massive code duplication** across `TaxonomyMetaManager`, `UserMetaManager`, `OptionsManager`, and `FieldRenderer` for field resolution and rendering.
- **No dependency injection or service container**, making the system rigid and hard to extend.
- **Inadequate test coverage** -- only 3 test files with trivial assertions; no integration tests.
- **Static method overuse** throughout core classes, defeating polymorphism and testability.

Compared to mature alternatives (ACF, CMB2, Meta Box), this plugin lacks event-driven architecture, a proper field registry, storage abstraction, and hook-based extensibility patterns.

---

## 2. Detailed Findings

### 2.1 Singleton Anti-Pattern on MetaBoxManager

**Severity: Critical**  
**File:** `src/Core/MetaBoxManager.php` -- `MetaBoxManager::instance()`  
**Also accessed from:** `public-api.php`, `src/Core/ImportExport.php`, `src/Core/GutenbergPanel.php`, `src/Core/BulkOperations.php`, `src/Core/DependencyGraph.php`, `src/Core/WpCliCommands.php`

The `MetaBoxManager` uses a classic singleton (`private static ?MetaBoxManager $instance`). Every other class in the system reaches for it via `MetaBoxManager::instance()`, creating invisible, undeclared dependencies.

**Impact:**
- Unit tests must use reflection to reset the singleton (as seen in `tests/MetaBoxManagerTest.php` lines 8-12), which is fragile.
- Impossible to substitute a mock or alternative implementation.
- Creates a global mutable state that is shared across all callers without any contract.
- Prevents running parallel test suites or using the manager in isolation.

**Recommendation:** Replace with dependency injection. Have `Plugin::boot()` instantiate `MetaBoxManager` and pass it to dependents. Consider a lightweight DI container or a service locator pattern if constructor injection feels too verbose for WordPress context.

---

### 2.2 No Service Container or Dependency Injection

**Severity: Critical**  
**File:** `src/Core/Plugin.php` -- `Plugin::boot()`

The `Plugin` class instantiates and wires components imperatively:

```php
$manager = MetaBoxManager::instance();
$manager->register();
WpCliCommands::register();
GutenbergPanel::register();
ImportExport::register();
AdminUI::register();
DependencyGraph::register();
BulkOperations::register();
```

There is no container, no service registration, and no way for external code to replace, decorate, or conditionally load any of these components.

**Impact:**
- Adding a new module requires modifying `Plugin::boot()` directly (Open/Closed Principle violation).
- No lazy loading -- all modules are initialized on every request regardless of context (admin, frontend, REST, CLI).
- Tight coupling between the bootstrap and every feature module.

**Recommendation:** Introduce a service provider pattern. Each module should implement a `ServiceProvider` interface with `register()` and `boot()` methods. The `Plugin` class should iterate over registered providers. This is the pattern used by ACF's internal architecture and Laravel-based WordPress plugins.

---

### 2.3 God Class: AdminUI (~1000+ Lines of Mixed HTML/PHP)

**Severity: Critical**  
**File:** `src/Core/AdminUI.php`

This single class handles:
- Admin menu registration
- Routing (list page vs. edit page)
- Full HTML rendering of list tables, edit forms, field rows, sub-field rows
- Type picker modal rendering
- Import modal rendering
- Notice rendering
- Save handling (field parsing, option serialization)
- Delete, duplicate, toggle, export, import action handlers
- Registering saved boxes at runtime
- Helper methods for field type categories, taxonomies, roles, post type icons

**Impact:**
- Violates Single Responsibility Principle severely.
- Impossible to unit test individual pieces (rendering vs. save logic vs. routing).
- Any change to the save logic risks breaking the UI rendering and vice versa.
- HTML is echo'd directly with string concatenation, making template changes error-prone.

**Recommendation:** Split into at least 5 classes:
1. `AdminUI\Router` -- page routing and menu registration
2. `AdminUI\ListPage` -- list view rendering
3. `AdminUI\EditPage` -- edit form rendering
4. `AdminUI\FieldRowRenderer` -- field row template logic
5. `AdminUI\ActionHandler` -- save, delete, duplicate, toggle, import, export

Consider using WordPress template parts or a minimal templating approach (PHP files in a `views/` directory).

---

### 2.4 Duplicated Field Resolution Logic (DRY Violation)

**Severity: High**  
**Files:**
- `src/Core/MetaBoxManager.php` -- `resolveFieldClass()` (line 251)
- `src/Core/FieldRenderer.php` -- `render()` (line 72)
- `src/Core/TaxonomyMetaManager.php` -- `renderFields()` (line 40), `renderAddFields()` (line 68), `saveFields()` (line 91)
- `src/Core/UserMetaManager.php` -- `renderFields()` (line 33), `saveFields()` (line 61)
- `src/Core/OptionsManager.php` -- `renderPage()` (line 55), `registerSettings()` (line 81)

The pattern `$fieldClass = 'CMB\\Fields\\' . ucfirst($field['type']) . 'Field'` followed by `class_exists()` and `new $fieldClass(...)` is repeated **at least 9 times** across the codebase. Each location independently constructs field instances with slightly different config merging logic.

**Impact:**
- If the namespace or naming convention changes, every location must be updated.
- Custom field type resolution (via `MetaBoxManager::$customFieldTypes`) is only available in `MetaBoxManager::resolveFieldClass()`. The `TaxonomyMetaManager`, `UserMetaManager`, `OptionsManager`, and `FieldRenderer` all bypass custom field types entirely, using hardcoded class name construction.
- Inconsistent field instantiation across contexts.

**Recommendation:** Extract a `FieldFactory` class with a single `create(string $type, array $config): FieldInterface` method. All managers and renderers should use this factory. The factory should check custom registered types first, then fall back to the default namespace mapping.

---

### 2.5 Duplicated `flattenFields()` Method

**Severity: High**  
**Files:**
- `src/Core/MetaBoxManager.php` -- `flattenFields()` (line 152)
- `src/Core/BulkOperations.php` -- `flattenFields()` (line 182)
- `src/Core/DependencyGraph.php` -- `flattenFields()` (line 60)
- `src/Core/WpCliCommands.php` -- inline equivalent (lines 47-57)

The exact same tabs-flattening logic is copy-pasted into 4 different classes.

**Impact:**
- Bug fixes to tab flattening must be applied in 4 places.
- Inconsistent behavior if one copy diverges.

**Recommendation:** Extract to a shared utility class (e.g., `CMB\Core\FieldUtils::flattenFields()`) or add it as a method on a `MetaBoxConfig` value object.

---

### 2.6 Static Method Overuse Defeating OOP Benefits

**Severity: High**  
**Files:**
- `src/Core/AdminUI.php` -- every method is `public static`
- `src/Core/GutenbergPanel.php` -- every method is `public static`
- `src/Core/ImportExport.php` -- every method is `public static`
- `src/Core/WpCliCommands.php` -- every method is `public static`
- `src/Core/DependencyGraph.php` -- every method is `public static`
- `src/Core/BulkOperations.php` -- every method is `public static`

Six of the ten core classes are entirely static. They cannot be mocked, extended, or substituted.

**Impact:**
- No polymorphism possible. Cannot create a `MockAdminUI` for testing.
- Cannot use interfaces or abstract classes for these components.
- Encourages procedural thinking in an ostensibly OOP architecture.
- Makes dependency tracking opaque -- any code anywhere can call `AdminUI::handleSave()`.

**Recommendation:** Convert to instance methods. Have `Plugin` instantiate them and register hooks via instance method references. Reserve `static` for true utility functions with no side effects.

---

### 2.7 Missing Storage Abstraction Layer

**Severity: High**  
**Files:**
- `src/Core/MetaBoxManager.php` -- direct `update_post_meta()`, `delete_post_meta()`, `add_post_meta()`, `get_post_meta()`
- `src/Core/TaxonomyMetaManager.php` -- direct `get_term_meta()`, `update_term_meta()`
- `src/Core/UserMetaManager.php` -- direct `get_user_meta()`, `update_user_meta()`
- `src/Core/OptionsManager.php` -- direct `get_option()`
- `src/Core/BulkOperations.php` -- direct `update_post_meta()`, `delete_post_meta()`, `get_post_meta()`

Each manager directly calls WordPress meta/option functions. There is no `StorageInterface` or repository pattern.

**Impact:**
- Cannot unit test save/load logic without WordPress (or extensive mocking).
- Cannot add features like caching, logging, or encryption at the storage layer.
- Cannot swap storage backends (e.g., custom tables for performance, as Meta Box Pro does).
- Revision support, bulk operations, and standard save all duplicate storage call patterns.

**Recommendation:** Introduce a `Storage\PostMetaStorage`, `Storage\TermMetaStorage`, `Storage\UserMetaStorage`, `Storage\OptionStorage` implementing a common `StorageInterface`. This enables testability and future flexibility.

---

### 2.8 Inconsistent Rendering Strategies Across Managers

**Severity: High**  
**Files:**
- `src/Core/FieldRenderer.php` -- returns strings, uses meta cache, applies hooks/filters
- `src/Core/TaxonomyMetaManager.php` -- echoes HTML directly, no hooks, no meta cache
- `src/Core/UserMetaManager.php` -- echoes HTML directly, no hooks, no meta cache
- `src/Core/OptionsManager.php` -- echoes HTML directly, no hooks, no meta cache

The `FieldRenderer` class (used by `MetaBoxManager`) provides rich rendering features: meta caching, conditional display data attributes, multilingual support, hook integration (`cmb_before_render_field`, `cmb_field_html`, `cmb_after_render_field`), layout classes, required indicators, and descriptions.

None of these features are available in taxonomy, user, or options contexts. Those managers inline their own minimal rendering with direct `echo`.

**Impact:**
- Users get a dramatically different feature set depending on where they register fields.
- Conditional fields, multilingual support, layout classes, and extensibility hooks are post-meta only.
- Maintaining feature parity requires duplicating all FieldRenderer logic into 3 other managers.

**Recommendation:** Create a context-agnostic `FieldRenderer` (or make the existing one work without `WP_Post`). The constructor should accept a `RenderContext` object that abstracts the value source. Each manager would provide its own context implementation.

---

### 2.9 No Context-Aware Asset Loading

**Severity: Medium**  
**File:** `src/Core/Plugin.php` -- `registerAssets()` (line 24)

Assets (`cmb-style.css`, `cmb-script.js`, and WordPress media library) are enqueued on **every admin page** via `admin_enqueue_scripts` without checking the current screen.

**Impact:**
- Unnecessary CSS/JS loaded on pages that have no meta boxes (e.g., Dashboard, Settings, Plugins).
- Potential conflicts with other plugins' scripts.
- Performance overhead on large admin dashboards.

**Recommendation:** Check `get_current_screen()` before enqueuing. Only load on post edit screens that have registered meta boxes, taxonomy edit screens, user profile screens, and the plugin's own admin pages. Both ACF and CMB2 perform this check.

---

### 2.10 Missing Plugin Lifecycle Hooks

**Severity: Medium**  
**File:** `custom-meta-box-builder.php`

The main plugin file:
```php
$plugin = new Plugin();
$plugin->boot();
```

This runs immediately at file load time. There is no:
- Activation hook (`register_activation_hook`)
- Deactivation hook (`register_deactivation_hook`)
- Uninstall handler (`register_uninstall_hook` or `uninstall.php`)
- Version check or migration logic
- Minimum PHP/WordPress version checks

**Impact:**
- The `cmb_admin_configurations` option in `wp_options` is never cleaned up on uninstall.
- No way to run database migrations or schema changes between versions.
- Plugin may break silently on older PHP versions (requires PHP 8.0+ for `match`, union types, etc.) without telling the user why.

**Recommendation:** Add activation/deactivation/uninstall hooks. Add a PHP/WP version gate at the top of the main file. Store a version option to enable future migrations.

---

### 2.11 No Deferred/Conditional Module Loading

**Severity: Medium**  
**File:** `src/Core/Plugin.php` -- `boot()`

All modules are loaded unconditionally:
- `WpCliCommands::register()` checks `WP_CLI` internally, which is fine.
- `GutenbergPanel::register()` always hooks into `enqueue_block_editor_assets`.
- `ImportExport::register()` is a no-op.
- `AdminUI::register()` hooks into `admin_menu` and `admin_init` on every request.
- `BulkOperations::register()` hooks into `admin_menu` and `admin_init` on every request.
- `DependencyGraph::register()` hooks into `admin_menu` on every request.

**Impact:**
- Frontend requests (where `is_admin()` is false) still execute `Plugin::boot()`, loading class autoloads and calling static methods unnecessarily.
- REST API requests load admin UI hooks.

**Recommendation:** Gate module loading:
```php
if (is_admin()) { AdminUI::register(); BulkOperations::register(); ... }
if (defined('REST_REQUEST')) { /* REST-specific init */ }
```

---

### 2.12 FieldInterface is Too Narrow

**Severity: Medium**  
**File:** `src/Core/Contracts/FieldInterface.php`

```php
interface FieldInterface {
    public function render(): string;
    public function sanitize(mixed $value): mixed;
    public function getValue(): mixed;
    public function validate(mixed $value): array;
}
```

Missing from the interface:
- `getType(): string` -- needed for field type identification without `instanceof`
- `getConfig(): array` -- needed by external code to inspect field configuration
- `enqueueAssets(): void` -- needed for fields that require specific JS/CSS (e.g., WYSIWYG, Color, File)
- No concept of field "context" (post, term, user, option)

**Impact:**
- External code must cast to `AbstractField` or use array access to get field metadata.
- No standard way for custom field types to declare their asset dependencies.
- The interface does not capture the full contract that field implementations actually fulfill.

**Recommendation:** Expand the interface to include `getType()`, `getId()`, `getConfig()`, and optionally `enqueueAssets()`. This brings it closer to CMB2's `CMB2_Type_Base` and ACF's `acf_field` contracts.

---

### 2.13 AbstractField::getValue() Has Falsy Bug

**Severity: Medium**  
**File:** `src/Core/Contracts/Abstracts/AbstractField.php` -- `getValue()` (line 27)

```php
public function getValue(): mixed {
    if (!empty($this->config['value'])) {
        return $this->config['value'];
    }
    ...
}
```

`!empty()` treats `0`, `"0"`, `false`, and `""` as empty. A field with a legitimate value of `0` or `"0"` would fall through to the default, returning `null` instead of the actual value.

**Impact:**
- Number fields set to `0`, checkboxes set to `"0"`, or any field with a falsy but valid value will not display or save correctly.
- This is a subtle data-loss bug.

**Recommendation:** Use `array_key_exists('value', $this->config) && $this->config['value'] !== null` or simply `isset($this->config['value'])` combined with an explicit null check.

---

### 2.14 GroupField Creates New FieldRenderer Instances Per Sub-Field

**Severity: Medium**  
**File:** `src/Fields/GroupField.php` -- `group_item()` (line 63)

```php
$fieldRenderer = new FieldRenderer(get_post(get_the_ID()));
```

Inside a loop over sub-fields, a new `FieldRenderer` is created for each sub-field rendering. Additionally, `get_post(get_the_ID())` is called inside the render method, which relies on global state.

**Impact:**
- Each `FieldRenderer` initializes its own meta cache, causing redundant `get_post_meta()` calls.
- `get_the_ID()` may not return the correct ID in all contexts (e.g., AJAX, REST).
- Tight coupling between a Field class and the Renderer class creates a circular dependency concern (Fields depend on FieldRenderer, FieldRenderer creates Fields).

**Recommendation:** Pass the `FieldRenderer` instance into `GroupField` via config or a dedicated method, rather than creating new instances. The renderer should be shared within a rendering context.

---

### 2.15 Validation Pattern Regex Injection Risk

**Severity: Medium**  
**File:** `src/Core/Contracts/Abstracts/AbstractField.php` -- `validate()` (line 94)

```php
case 'pattern':
    if ($ruleParam !== null && $value !== '' && !preg_match('/' . $ruleParam . '/', (string)$value)) {
```

The regex pattern from field config is interpolated directly into `preg_match()` without escaping or validation.

**Impact:**
- A malformed regex pattern will cause a PHP warning.
- While not directly exploitable for code injection (since patterns come from field config, not user input), a misconfigured pattern could cause ReDoS (Regular Expression Denial of Service) with pathological inputs.
- No error handling if `preg_match` returns `false` (error).

**Recommendation:** Wrap in `@preg_match()` with error checking, or validate the pattern at registration time. Add a timeout or limit pattern complexity.

---

### 2.16 Password Field Stores Values in Plain Text

**Severity: Medium**  
**File:** `src/Fields/PasswordField.php`

The `PasswordField` sanitizes with `sanitize_text_field()` and stores the value directly in post meta without any hashing or encryption.

**Impact:**
- Sensitive data (passwords, API keys, secrets) stored as plain text in `wp_postmeta`.
- Visible in database dumps, backups, and via the REST API if `show_in_rest` is enabled.
- Renders the password field type unsuitable for any security-sensitive use case.

**Recommendation:** At minimum, document that this field is for display-only password inputs (e.g., API key entry), not for storing credentials. Ideally, offer an encryption option using `wp_hash()` or a symmetric encryption utility. Consider adding a `sensitive` flag that prevents REST API exposure.

---

### 2.17 ArrayAccessibleTrait is Unused and Conflicts with AbstractField

**Severity: Low**  
**File:** `src/Core/Traits/ArrayAccessibleTrait.php`

This trait declares `protected array $config = []` and provides `__get` and `__isset` magic methods. However:
- No class in the codebase uses this trait.
- `AbstractField` already declares `protected array $config` -- using both would cause a conflict.
- The trait does not implement PHP's `ArrayAccess` interface despite its name.

**Impact:**
- Dead code that misleads developers.
- The name suggests `ArrayAccess` implementation but provides magic property access instead.

**Recommendation:** Either remove this trait or implement the actual `ArrayAccess` interface (`offsetGet`, `offsetSet`, `offsetExists`, `offsetUnset`) and use it where appropriate.

---

### 2.18 Insufficient Test Coverage

**Severity: High**  
**Files:** `tests/PluginTest.php`, `tests/MetaBoxManagerTest.php`, `tests/TextFieldTest.php`

Current test suite:
- **PluginTest**: 1 test -- asserts `boot()` does not throw. No behavioral assertions.
- **MetaBoxManagerTest**: 2 tests -- singleton identity and adding a meta box. Uses reflection to test internal state.
- **TextFieldTest**: 2 tests -- render output contains "input" and sanitize strips tags.

Missing test coverage for:
- All 17 other field types (0 tests each)
- Validation logic in `AbstractField`
- Save flow (`saveMetaBoxData`, `saveField`, `sanitizeGroupValue`)
- Nonce verification
- REST API registration
- Taxonomy, User, Options managers (0 tests each)
- Import/Export logic
- Bulk Operations
- Conditional field rendering
- Tab rendering
- Multilingual field rendering
- AdminUI save handler parsing

**Impact:**
- Regressions are effectively undetectable.
- No confidence that refactoring will preserve behavior.
- The test bootstrap mocks 50+ WordPress functions with trivial implementations, making tests unreliable for validating actual integration behavior.

**Recommendation:**
1. Add unit tests for every field type's `render()`, `sanitize()`, and `validate()` methods.
2. Add integration tests using `wp-env` or WP Test Utils for save/load flows.
3. Consider using `Brain\Monkey` or `Mockery` instead of manual function mocking for cleaner WordPress function stubs.
4. Target at least 70% code coverage for core classes.

---

### 2.19 Global $_POST Access Without Abstraction

**Severity: Medium**  
**Files:**
- `src/Core/MetaBoxManager.php` -- `saveField()` (line 174): `$raw = $_POST[$fieldId] ?? '';`
- `src/Core/TaxonomyMetaManager.php` -- `saveFields()` (line 95): `$raw = $_POST[$field['id']] ?? '';`
- `src/Core/UserMetaManager.php` -- `saveFields()` (line 65): `$raw = $_POST[$field['id']] ?? '';`
- `src/Core/AdminUI.php` -- `handleSave()` (line 872+): extensive `$_POST` access
- `src/Core/BulkOperations.php` -- `handleBulkUpdate()` (line 104+): `$_POST` access

Direct `$_POST` superglobal access throughout the codebase.

**Impact:**
- Impossible to unit test save logic without populating `$_POST` globally.
- No input abstraction layer for potential future REST/AJAX save endpoints.
- Missing `wp_unslash()` in most locations (only AdminUI import uses it).

**Recommendation:** Create a `Request` wrapper or use `wp_unslash(wp_verify_nonce(...))` patterns consistently. At minimum, centralize `$_POST` access into dedicated methods that can be overridden in tests.

---

### 2.20 MetaBoxManager::deletePostMetaData Deletes All Registered Fields on Any Post Deletion

**Severity: Medium**  
**File:** `src/Core/MetaBoxManager.php` -- `deletePostMetaData()` (line 348)

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

This is hooked to `delete_post` and iterates over all registered meta boxes regardless of post type. If a meta box is registered for "page" but a "post" is deleted, it still attempts to delete meta keys that may coincidentally match.

**Impact:**
- Unnecessary database queries on every post deletion.
- Potential unintended data deletion if field IDs overlap across post types.

**Recommendation:** Filter by post type before deleting. Check if the deleted post's type is in `$metaBox['postTypes']` before processing its fields.

---

### 2.21 JavaScript Architecture Concerns

**Severity: Medium**  
**Files:** `assets/cmb-script.js`, `assets/cmb-admin.js`, `assets/cmb-gutenberg.js`

- `cmb-script.js` (448 lines) and `cmb-admin.js` (500+ lines) are monolithic jQuery files with no module structure.
- No build process (no webpack, no minification, no source maps).
- `cmb-gutenberg.js` uses `wp.element.createElement` directly instead of JSX, which is harder to maintain.
- No asset versioning based on file hash (version is hardcoded as `null` or `'2.0.0'`).
- jQuery dependency for all frontend interactions in 2026.

**Impact:**
- Browser caching may serve stale scripts after updates.
- Larger bundle size than necessary for modern browsers.
- Gutenberg integration is limited to basic field types; no support for group, file, WYSIWYG, taxonomy, post, or user fields in the sidebar panel.

**Recommendation:**
1. Add a build step (webpack or esbuild) with minification and content hashing for cache busting.
2. Consider migrating `cmb-script.js` to vanilla JS to drop the jQuery dependency.
3. Use `@wordpress/scripts` for the Gutenberg panel to enable JSX and proper React component patterns.
4. Version assets with `filemtime()` at minimum.

---

### 2.22 No Hook Documentation or Extensibility Guide

**Severity: Low**  
**Files:** Various

The plugin defines several hooks:
- Actions: `cmb_before_save_field`, `cmb_after_save_field`, `cmb_before_render_field`, `cmb_after_render_field`
- Filters: `cmb_meta_box_args`, `cmb_sanitize_{type}`, `cmb_field_html`, `cmb_field_value`
- Extension point: `MetaBoxManager::registerFieldType()`

None of these are documented in a centralized location. There is no `@hook` or `@action`/`@filter` PHPDoc annotation pattern.

**Impact:**
- Third-party developers cannot discover extension points without reading source code.
- No API stability contract -- hooks may be renamed or removed without notice.

**Recommendation:** Add PHPDoc `@hook` annotations. Create a hooks reference (or at minimum, inline documentation blocks above each `do_action`/`apply_filters` call). Consider adopting a versioned hook naming convention (e.g., `cmb/field/before_save` with `/` separators).

---

### 2.23 Composer Configuration Missing Key Metadata

**Severity: Low**  
**File:** `composer.json`

```json
{
    "name": "yourname/custom-meta-box-builder",
    "require-dev": {
        "phpunit/phpunit": "^10"
    }
}
```

Missing:
- `require.php` -- no minimum PHP version declared (plugin uses PHP 8.0+ features)
- `config.platform.php` -- no platform lock
- `scripts` section -- no `test` or `lint` commands
- `autoload-dev` for test classes
- Static analysis tools (PHPStan, Psalm)
- Coding standards tools (PHP_CodeSniffer with WordPress ruleset)

**Impact:**
- `composer install` on PHP 7.x would succeed but the plugin would fatal at runtime.
- No automated quality gates.

**Recommendation:** Add `"require": {"php": ">=8.1"}`, add `scripts` for testing and linting, add `phpstan/phpstan` to `require-dev`, add `autoload-dev` for tests namespace.

---

### 2.24 Public API Functions Use Inconsistent Instantiation Patterns

**Severity: Medium**  
**File:** `public-api.php`

```php
function add_custom_meta_box(...) {
    $manager = MetaBoxManager::instance();  // Singleton
    ...
}

function add_custom_taxonomy_meta(...) {
    static $manager = null;                 // Static local variable
    if ($manager === null) {
        $manager = new TaxonomyMetaManager();
        $manager->register();
    }
    ...
}
```

`add_custom_meta_box` uses a singleton, while `add_custom_taxonomy_meta`, `add_custom_user_meta`, and `add_custom_options_page` each use a `static` local variable with lazy initialization.

**Impact:**
- Three different instantiation patterns for four API functions.
- The `static $manager` pattern means `register()` (which hooks into WordPress) is called on first invocation, making hook timing unpredictable.
- If `add_custom_taxonomy_meta()` is called after `init`, the hooks may fire too late.

**Recommendation:** Standardize on a single pattern. Register all managers during `Plugin::boot()` and have the API functions delegate to pre-registered instances (perhaps stored in a registry or container).

---

### 2.25 GroupField::sanitize Uses Shallow Generic Sanitization

**Severity: Medium**  
**File:** `src/Fields/GroupField.php` -- `sanitize()` (line 85)

```php
public function sanitize(mixed $value): mixed {
    if (!is_array($value)) {
        return [];
    }
    return map_deep($value, 'sanitize_text_field');
}
```

This applies `sanitize_text_field` to all nested values regardless of sub-field type. A WYSIWYG sub-field would have its HTML stripped. An email sub-field would not get email-specific sanitization.

However, `MetaBoxManager::sanitizeGroupValue()` does properly iterate sub-fields and apply per-type sanitization. This means the `GroupField::sanitize()` method is only a fallback, but it is dangerously lossy if ever called directly (e.g., via `TaxonomyMetaManager` or `UserMetaManager`).

**Impact:**
- Group fields in taxonomy/user/options contexts will have all sub-field values sanitized as plain text, stripping HTML from WYSIWYG fields and not validating emails/URLs.

**Recommendation:** Move the recursive per-type sanitization logic from `MetaBoxManager::sanitizeGroupValue()` into `GroupField::sanitize()` so it works correctly in all contexts.

---

## 3. Architecture Comparison with Industry Standards

| Aspect | This Plugin | ACF | CMB2 | Meta Box |
|---|---|---|---|---|
| DI/Container | None | Internal registry | None (hooks-based) | Service container |
| Field Registry | Hardcoded class map | Centralized registry | Type map + hooks | Registry + factory |
| Storage Layer | Direct WP functions | Abstracted | Direct + filters | Abstracted (custom tables option) |
| Module Loading | Unconditional | Conditional | Conditional | Conditional |
| Test Suite | 5 trivial tests | Comprehensive | Moderate | Comprehensive |
| Build Process | None | Webpack | Grunt | Webpack |
| Extensibility Hooks | 8 hooks | 100+ hooks | 50+ hooks | 100+ hooks |

---

## 4. Priority Remediation Roadmap

### Phase 1 -- Critical (Immediate)
1. **Extract FieldFactory** to eliminate 9x duplicated field resolution.
2. **Replace singleton** on MetaBoxManager with DI.
3. **Split AdminUI** into at least 3 classes.

### Phase 2 -- High (Next Sprint)
4. **Introduce StorageInterface** to abstract meta operations.
5. **Unify rendering** across all manager types via shared FieldRenderer.
6. **Extract flattenFields** to shared utility.
7. **Add comprehensive unit tests** for all field types and core flows.
8. **Convert static classes** to instance-based with proper DI.

### Phase 3 -- Medium (Next Release)
9. **Add conditional asset loading** per screen.
10. **Add plugin lifecycle hooks** (activation, deactivation, uninstall).
11. **Fix AbstractField::getValue()** falsy value bug.
12. **Fix deletePostMetaData** post type filtering.
13. **Add build process** for JS/CSS assets.
14. **Standardize public API** instantiation patterns.

### Phase 4 -- Low (Ongoing)
15. **Document hooks** and create extensibility guide.
16. **Improve composer.json** with platform requirements and tooling.
17. **Remove/fix ArrayAccessibleTrait**.
18. **Modernize JavaScript** away from jQuery.

---

## 5. Files Reviewed

| File | Lines | Status |
|---|---|---|
| `custom-meta-box-builder.php` | 17 | Reviewed |
| `public-api.php` | 68 | Reviewed |
| `composer.json` | 15 | Reviewed |
| `src/Core/Plugin.php` | 36 | Reviewed |
| `src/Core/MetaBoxManager.php` | 397 | Reviewed |
| `src/Core/AdminUI.php` | ~1000+ | Reviewed |
| `src/Core/FieldRenderer.php` | 238 | Reviewed |
| `src/Core/GutenbergPanel.php` | 78 | Reviewed |
| `src/Core/TaxonomyMetaManager.php` | 101 | Reviewed |
| `src/Core/UserMetaManager.php` | 70 | Reviewed |
| `src/Core/OptionsManager.php` | 94 | Reviewed |
| `src/Core/ImportExport.php` | 51 | Reviewed |
| `src/Core/BulkOperations.php` | 194 | Reviewed |
| `src/Core/WpCliCommands.php` | 127 | Reviewed |
| `src/Core/DependencyGraph.php` | 144 | Reviewed |
| `src/Core/Contracts/FieldInterface.php` | 9 | Reviewed |
| `src/Core/Contracts/Abstracts/AbstractField.php` | 123 | Reviewed |
| `src/Core/Traits/MultiLanguageTrait.php` | 73 | Reviewed |
| `src/Core/Traits/ArrayAccessibleTrait.php` | 14 | Reviewed |
| `src/Fields/TextField.php` | 34 | Reviewed |
| `src/Fields/TextareaField.php` | 20 | Reviewed |
| `src/Fields/NumberField.php` | 27 | Reviewed |
| `src/Fields/EmailField.php` | 19 | Reviewed |
| `src/Fields/UrlField.php` | 19 | Reviewed |
| `src/Fields/PasswordField.php` | 20 | Reviewed |
| `src/Fields/HiddenField.php` | 18 | Reviewed |
| `src/Fields/SelectField.php` | 28 | Reviewed |
| `src/Fields/CheckboxField.php` | 21 | Reviewed |
| `src/Fields/RadioField.php` | 32 | Reviewed |
| `src/Fields/ColorField.php` | 20 | Reviewed |
| `src/Fields/DateField.php` | 26 | Reviewed |
| `src/Fields/FileField.php` | 46 | Reviewed |
| `src/Fields/WysiwygField.php` | 37 | Reviewed |
| `src/Fields/PostField.php` | 45 | Reviewed |
| `src/Fields/UserField.php` | 39 | Reviewed |
| `src/Fields/TaxonomyField.php` | 59 | Reviewed |
| `src/Fields/GroupField.php` | 98 | Reviewed |
| `tests/bootstrap.php` | 87 | Reviewed |
| `tests/PluginTest.php` | 11 | Reviewed |
| `tests/MetaBoxManagerTest.php` | 36 | Reviewed |
| `tests/TextFieldTest.php` | 24 | Reviewed |
| `assets/cmb-script.js` | 448 | Reviewed |
| `assets/cmb-admin.js` | 500+ | Reviewed |
| `assets/cmb-gutenberg.js` | 126 | Reviewed |
| `assets/cmb-style.css` | 685 | Reviewed |
| `assets/cmb-admin.css` | Large | Reviewed |

---

*End of Architecture & Code Quality Audit Report*
