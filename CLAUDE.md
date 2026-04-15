# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

Custom Meta Box Builder is a WordPress plugin (v2.1.0) for creating custom meta boxes via PHP configuration arrays or an admin UI. It supports 30 field types, repeatable/nested groups, tabs, conditional logic, validation, taxonomy/user/options meta, REST API, Gutenberg sidebar, WP-CLI, and frontend forms. Requires PHP 8.1+ and WordPress 6.0+.

## Commands

```bash
# Install dependencies
composer install
npm install

# Run all tests (Brain\Monkey-based, no WordPress install needed)
vendor/bin/phpunit

# Run only the Unit test suite
vendor/bin/phpunit --testsuite Unit

# Run a single test file
vendor/bin/phpunit tests/Unit/Fields/TextFieldTest.php

# Build minified assets (JS + CSS via esbuild)
npm run build

# Watch mode for asset development
npm run watch
```

## Architecture

### Entry flow

`custom-meta-box-builder.php` bootstraps the plugin: loads Composer autoloader, then on `plugins_loaded` instantiates `CMB\Core\Plugin` and calls `boot()`. `Plugin::boot()` creates `MetaBoxManager`, registers assets, initializes core subsystems (AjaxHandler, LocalJson, GraphQL, FrontendForm, BlockRegistration), then loads service providers.

### Namespace and autoloading

PSR-4: `CMB\` maps to `src/`. All classes under `CMB\Core\` (core infrastructure) and `CMB\Fields\` (field types).

### Key abstractions

- **`FieldInterface`** (`src/Core/Contracts/FieldInterface.php`) — contract all field types implement: `render()`, `sanitize()`, `validate()`, `format()`, `enqueueAssets()`.
- **`AbstractField`** (`src/Core/Contracts/Abstracts/AbstractField.php`) — base class providing config access, validation engine, attribute rendering. New field types extend this.
- **`FieldFactory`** (`src/Core/FieldFactory.php`) — resolves type strings to class names. Supports custom types via `registerType()`, built-in aliases for non-standard names (e.g. `flexible_content`, `checkbox_list`), and convention-based resolution (`CMB\Fields\{Ucfirst}Field`).
- **`StorageInterface`** / **`RenderContextInterface`** — abstractions that let the same rendering/save logic work across post meta, term meta, user meta, and options. Four implementations each in `src/Core/Storage/` and `src/Core/RenderContext/`.
- **`ServiceProvider`** (`src/Core/Contracts/ServiceProvider.php`) — modular feature registration. Providers in `src/Core/Providers/` are conditionally loaded based on `isNeeded()` (e.g. WP-CLI only when `WP_CLI` is defined).

### Rendering pipeline

`MetaBoxManager::addMetaBoxes()` → creates WP meta box callback → `FieldRenderer::render()` → `FieldFactory::create()` → `FieldInterface::render()`. `FieldRenderer` handles name resolution for nested groups, repeater row iteration, conditional attributes, multilingual tabs, and hook firing.

### Save pipeline

`MetaBoxManager::saveMetaBoxData()` → nonce verification → for each field: `validate()` → `before_save_field` hook → `sanitize()` (with recursive group handling) → `sanitize_{type}` filter → storage write → `after_save_field` hook.

### Admin UI (no-code builder)

`src/Core/AdminUI/` — `ListPage`, `EditPage`, `ActionHandler`, `Router`, `ImportExportHandler`, `BulkActionHandler`. Saved meta box configs stored as `cmb_saved_meta_boxes` option. `ActionHandler::registerSavedBoxes()` runs on `init` to register UI-built boxes alongside code-defined ones.

### Public API

`public-api.php` provides global helper functions: `add_custom_meta_box()`, `add_custom_taxonomy_meta()`, `add_custom_user_meta()`, `add_custom_options_page()`, `cmb_get_field()`, `cmb_render_form()`, `cmb_register_block()`, etc.

### Hook system

All hooks fire with dual prefixes via `FieldUtils::doAction()` / `FieldUtils::applyFilters()`:
- Primary: `cmbbuilder_` (e.g. `cmbbuilder_before_save_field`)
- Legacy: `cmb_` (deprecated, fires with `_deprecated_hook` notice)

Set `define('CMBB_LEGACY_HOOKS', false)` to disable legacy prefix firing.

## Adding a New Field Type

1. Create `src/Fields/YourField.php` extending `AbstractField` — implement `render()` and `sanitize()`.
2. If the class name doesn't follow the `{Ucfirst}Field` convention, add an alias in `FieldFactory::$typeAliases`.
3. Add tests in `tests/Unit/Fields/YourFieldTest.php`.

## Testing Conventions

- Tests extend `Tests\TestCase` which sets up Brain\Monkey and stubs common WP functions (escaping, sanitization, `apply_filters`, `do_action`).
- WordPress functions are mocked with `Brain\Monkey\Functions\expect()` / `Functions\when()`. No WordPress install is needed.
- Patchwork is loaded before autoload in `tests/bootstrap.php` for function interception.
- Test structure mirrors `src/`: `tests/Unit/Fields/`, `tests/Unit/`.
- Legacy tests in `tests/` root (`PluginTest.php`, `MetaBoxManagerTest.php`, `TextFieldTest.php`).

## Coding Conventions

- WordPress PHP Coding Standards with PHP 8.1+ features (typed properties, union types, match expressions, named arguments).
- JavaScript: ES6+ in IIFEs with `'use strict'`.
- CSS: custom properties prefixed `--cmb-*`.
- All HTML IDs on meta boxes are prefixed with `cmb-` (enforced at registration in `MetaBoxManager::addMetaBoxes()`).
- Asset files: `assets/cmb-{name}.{css,js}` with `.min.{css,js}` built variants. Plugin auto-falls back to unminified if minified doesn't exist.
