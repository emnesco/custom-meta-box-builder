# Architecture

[Back to README](../README.md)

This document explains the plugin's internal design, class responsibilities, and data flow.

## Class Diagram

```
FieldInterface (interface)
  │  render(): string
  │  sanitize(mixed $value): mixed
  │  getValue(): mixed
  │  validate(mixed $value): array
  │
  └── AbstractField (abstract)
        │  config: array
        │  getName(), getId(), getLabel()
        │  getValue(), renderAttributes()
        │  validate(), isRequired(), requiredAttr()
        │
        ├── TextField          ├── NumberField
        ├── TextareaField      ├── EmailField
        ├── SelectField        ├── UrlField
        ├── CheckboxField      ├── RadioField
        ├── GroupField         ├── HiddenField
        ├── PasswordField      ├── DateField
        ├── ColorField         ├── WysiwygField
        ├── FileField          ├── PostField
        ├── TaxonomyField      └── UserField

Plugin (final)
  └── boot()
        ├── registerAssets()       → enqueues CSS/JS/media
        ├── MetaBoxManager::instance() → register()
        ├── WpCliCommands::register()
        ├── GutenbergPanel::register()
        ├── ImportExport::register()
        ├── AdminUI::register()
        ├── DependencyGraph::register()
        └── BulkOperations::register()

MetaBoxManager (singleton)
  │  metaBoxes: array
  │  customFieldTypes: array (static)
  │  validationErrors: array
  │  instance(), registerFieldType()
  │  add(), register(), getMetaBoxes()
  │  addMetaBoxes()         → add_meta_boxes hook
  │  saveMetaBoxData()      → save_post hook
  │  deletePostMetaData()   → delete_post hook
  │  registerRestFields()   → init hook
  │  copyMetaToRevision()   → revision hooks
  │  restoreMetaFromRevision()
  │  showValidationErrors() → admin_notices hook
  │  validateFieldConfigs()
  │  resolveFieldClass()    → checks custom registry, then CMB\Fields\ namespace
  └── sanitizeGroupValue()  → recursive group sanitization

FieldRenderer
  │  post: WP_Post
  │  metaCache: array (bulk meta fetch)
  │  render(), getname(), getChildPrefix()
  │  get_field_value(), generateHtmlId()
  │  renderMultilingualField()
  └── uses MultiLanguageTrait

TaxonomyMetaManager   → {taxonomy}_edit_form_fields / edited_{taxonomy}
UserMetaManager       → show_user_profile / edit_user_profile
OptionsManager        → admin_menu / admin_init (register_setting)
ImportExport          → Tools > CMB Import/Export
AdminUI               → Meta Box Builder admin page
DependencyGraph       → Tools > CMB Field Graph
BulkOperations        → Tools > CMB Bulk Ops
WpCliCommands         → wp cmb list/get/set
GutenbergPanel        → enqueue_block_editor_assets
```

## Boot Sequence

1. **Entry point** — `custom-meta-box-builder.php` loads Composer autoload, creates a `Plugin` instance, and calls `boot()`.

2. **Asset registration** — `Plugin::registerAssets()` hooks into `admin_enqueue_scripts` to load `cmb-style.css`, `cmb-script.js` (with jQuery + jQuery UI Sortable), and `wp_enqueue_media()`.

3. **Manager setup** — `Plugin::boot()` gets the `MetaBoxManager` singleton and calls `register()`, which hooks:
   - `add_meta_boxes` → `addMetaBoxes()`
   - `save_post` → `saveMetaBoxData()`
   - `delete_post` → `deletePostMetaData()`
   - `admin_notices` → `showValidationErrors()`
   - `wp_creating_autosave` / `_wp_put_post_revision` → `copyMetaToRevision()`
   - `wp_restore_post_revision` → `restoreMetaFromRevision()`
   - `init` → `registerRestFields()`

4. **Subsystem registration** — WP-CLI, Gutenberg panel, Import/Export, Admin UI, Dependency Graph, and Bulk Operations are registered.

5. **Public API** — `public-api.php` defines `add_custom_meta_box()`, `add_custom_taxonomy_meta()`, `add_custom_user_meta()`, and `add_custom_options_page()` as global helper functions.

## Rendering Flow

When WordPress fires the `add_meta_boxes` action:

```
addMetaBoxes()
  └── foreach metaBox
        ├── apply_filters('cmb_meta_box_args', $metaBox, $id)
        └── foreach postType
              └── add_meta_box(callback)
                    └── callback($post)
                          └── FieldRenderer($post)
                                ├── check for tabs → renderTabs()
                                └── foreach field
                                      ├── do_action('cmb_before_render_field')
                                      ├── getname()         → resolve name attribute
                                      ├── get_field_value()  → bulk meta cache lookup
                                      │     └── apply_filters('cmb_field_value')
                                      ├── check multilingual → renderMultilingualField()
                                      ├── FieldInstance->render()
                                      │     └── (GroupField recurses back into FieldRenderer)
                                      ├── apply_filters('cmb_field_html')
                                      └── do_action('cmb_after_render_field')
```

### Name Resolution

`FieldRenderer::getname()` resolves the HTML `name` attribute based on context:

- **No parent** — returns `field_id` (or `field_id[]` if repeatable)
- **Array parent** (first-level group) — returns `parent_id[index][field_id]` or `parent_id[field_id]`
- **String parent** (deep nesting) — returns `prefix[field_id]`

### HTML ID Generation

`FieldRenderer::generateHtmlId()` creates valid HTML IDs from field names: `cmb-` prefix + brackets replaced with hyphens.

### Meta Caching

`FieldRenderer::get_field_value()` fetches all post meta in a single `get_post_meta($post_id)` call on the first field render, then looks up subsequent fields from the cache.

## Save Flow

When WordPress fires the `save_post` action:

```
saveMetaBoxData($postId)
  ├── check DOING_AUTOSAVE
  ├── current_user_can('edit_post', $postId)
  └── foreach metaBox
        ├── verify unique nonce (cmb_nonce_{id} / cmb_save_{id})
        ├── flattenFields() (handles tabs)
        └── foreach field → saveField()
              ├── resolveFieldClass() (custom registry → CMB\Fields\ namespace)
              ├── validate() → collect errors
              ├── do_action('cmb_before_save_field')
              ├── sanitize (custom callback or field class)
              │     └── sanitizeGroupValue() for nested groups (recursive)
              ├── apply_filters('cmb_sanitize_{type}')
              ├── enforce max_rows (array_slice)
              ├── delete_post_meta + add_post_meta/update_post_meta
              └── do_action('cmb_after_save_field')
```

### Why delete + add/update?

For repeatable fields that store multiple meta rows, the save process first deletes all existing rows for that key, then re-adds each value. This ensures removed items are cleaned up and the count stays accurate.

### Recursive Group Sanitization

`sanitizeGroupValue()` iterates through each group row and each sub-field, resolving the correct field class and calling its `sanitize()` method. Supports nested groups recursively.

## Contracts & Abstractions

### FieldInterface

The core contract every field must satisfy:

| Method | Purpose |
|---|---|
| `render(): string` | Return the HTML for the field input |
| `sanitize(mixed $value): mixed` | Clean and validate the submitted value |
| `getValue(): mixed` | Resolve the current value from config |
| `validate(mixed $value): array` | Return array of validation error messages |

### AbstractField

Provides shared logic so field implementations only need to define `render()` and `sanitize()`:

- Stores the `$config` array
- `getName()`, `getId()`, `getLabel()`
- `getValue()` returns config value or `default` key, or a sensible default (empty array for groups/repeaters, null for scalars)
- `validate()` processes rules: required, email, url, min, max, numeric, pattern
- `isRequired()`, `requiredAttr()` helpers
- `renderAttributes()` converts the `attributes` config key into an HTML attribute string

### ArrayAccessibleTrait

Adds `__get` and `__isset` magic methods for `$field->id` syntax.

### MultiLanguageTrait

Provides per-locale meta key generation, language tab rendering, and locale utilities.

## Asset Pipeline

| Asset | Purpose |
|---|---|
| `cmb-style.css` | Admin UI: field layout, group styling, tabs, sortable, responsive, accessibility, print |
| `cmb-script.js` | jQuery-based: repeater cloning, sortable, group toggle, tabs, conditional logic, search, lazy loading, unsaved changes, file upload |
| `cmb-gutenberg.js` | Block editor: PluginDocumentSettingPanel components for sidebar fields |

Both admin assets are enqueued on all admin pages via `admin_enqueue_scripts`. The JS depends on jQuery and jQuery UI Sortable (bundled with WordPress). The Gutenberg script depends on `wp-plugins`, `wp-edit-post`, `wp-components`, `wp-data`, `wp-element`.

---

## Next Steps

- [Extending](extending.md) — how the architecture supports custom fields
- [Hooks Reference](hooks.md) — all developer hooks
- [Testing](testing.md) — the test setup
