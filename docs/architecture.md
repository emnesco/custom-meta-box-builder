# Architecture

[Back to README](../README.md)

This document explains the plugin's internal design, class responsibilities, and data flow.

## Class Diagram

```
FieldInterface (interface)
  │  render(): string
  │  sanitize($value)
  │  getValue()
  │
  └── AbstractField (abstract)
        │  config: array
        │  getName(), getId(), getLabel()
        │  getValue(), renderAttributes()
        │
        ├── TextField
        ├── TextareaField
        ├── SelectField
        ├── CheckboxField
        └── GroupField

Plugin
  └── boot()
        ├── registerAssets()  → enqueues CSS/JS
        └── new MetaBoxManager() → register()

MetaBoxManager
  │  metaBoxes: array
  │  add(), register()
  │  addMetaBoxes()     → WordPress add_meta_boxes hook
  └── saveMetaBoxData() → WordPress save_post hook

FieldRenderer
  │  post: WP_Post
  │  render(), getname(), getChildPrefix()
  └── get_field_value()
```

## Boot Sequence

1. **Entry point** — `custom-meta-box-builder.php` loads Composer autoload, creates a `Plugin` instance, and calls `boot()`.

2. **Asset registration** — `Plugin::registerAssets()` hooks into `admin_enqueue_scripts` to load `cmb-style.css` and `cmb-script.js` on all admin pages.

3. **Manager setup** — `Plugin::boot()` creates a `MetaBoxManager` and calls `register()`, which hooks:
   - `add_meta_boxes` → `addMetaBoxes()`
   - `save_post` → `saveMetaBoxData()`

4. **Public API** — `public-api.php` defines `add_custom_meta_box()`, a global helper that creates its own `MetaBoxManager` instance (stored in `$cmb_meta_box_manager` global) for external usage.

## Rendering Flow

When WordPress fires the `add_meta_boxes` action:

```
addMetaBoxes()
  └── foreach metaBox → foreach postType
        └── add_meta_box(callback)
              └── callback($post)
                    └── FieldRenderer($post)
                          └── foreach field
                                ├── getname()         → resolve name attribute
                                ├── get_field_value()  → fetch from post_meta
                                └── FieldInstance->render()
                                      └── (GroupField recurses back into FieldRenderer)
```

### Name Resolution

`FieldRenderer::getname()` resolves the HTML `name` attribute based on context:

- **No parent** — returns `field_id` (or `field_id[]` if repeatable)
- **Array parent** (first-level group) — returns `parent_id[index][field_id]` or `parent_id[field_id]`
- **String parent** (deep nesting) — returns `prefix[field_id]`

`FieldRenderer::getChildPrefix()` builds the prefix string passed to children:

- Repeatable group at index N → `name[N]`
- Non-repeatable group → `name`

This two-method approach allows unlimited nesting depth.

## Save Flow

When WordPress fires the `save_post` action:

```
saveMetaBoxData($postId)
  ├── verify nonce
  ├── check not autosave
  └── foreach metaBox → foreach field
        ├── resolve field class (CMB\Fields\{Type}Field)
        ├── instantiate field
        ├── sanitize($_POST[$fieldId])
        ├── delete_post_meta($postId, $fieldId)
        └── if array → add_post_meta() for each value
            else → update_post_meta()
```

### Why delete + add/update?

For repeatable fields that store multiple meta rows, the save process first deletes all existing rows for that key, then re-adds each value. This ensures removed items are cleaned up and the count stays accurate.

## Contracts & Abstractions

### FieldInterface

The core contract every field must satisfy:

| Method | Purpose |
|---|---|
| `render(): string` | Return the HTML for the field input |
| `sanitize($value)` | Clean and validate the submitted value |
| `getValue()` | Resolve the current value from config |

### AbstractField

Provides shared logic so field implementations only need to define `render()` and `sanitize()`:

- Stores the `$config` array
- Provides `getName()`, `getId()`, `getLabel()`
- `getValue()` returns the config value or a sensible default (empty array for groups/repeaters, null for scalars)
- `renderAttributes()` converts the `attributes` config key into an HTML attribute string

### ArrayAccessibleTrait

An optional trait (available but not used by AbstractField directly) that adds `__get` and `__isset` magic methods for convenient `$field->id` syntax instead of `$field->config['id']`.

## Asset Pipeline

| Asset | Purpose |
|---|---|
| `cmb-style.css` | Admin UI: field layout, group styling, responsive breakpoints, add/remove buttons |
| `cmb-script.js` | jQuery-based: repeater cloning, name index updating, group toggle, row removal |

Both are enqueued on all admin pages via `admin_enqueue_scripts`. The JS depends on jQuery (bundled with WordPress).

---

## Next Steps

- [Extending](extending.md) — how the architecture supports custom fields
- [Testing](testing.md) — the test setup
