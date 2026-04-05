# Advanced Features

[Back to README](../README.md) | [Configuration Reference](configuration-reference.md)

---

## Tabs

Organize fields into tabbed sections within a single meta box:

```php
add_custom_meta_box('cmb-product', 'Product', 'product', [
    'tabs' => [
        'basic' => [
            'label'  => 'Basic Info',
            'fields' => [
                ['id' => 'name',  'type' => 'text',   'label' => 'Name'],
                ['id' => 'price', 'type' => 'number', 'label' => 'Price'],
            ],
        ],
        'media' => [
            'label'  => 'Media',
            'fields' => [
                ['id' => 'image',   'type' => 'file',     'label' => 'Image'],
                ['id' => 'gallery', 'type' => 'textarea', 'label' => 'Gallery IDs'],
            ],
        ],
        'seo' => [
            'label'  => 'SEO',
            'fields' => [
                ['id' => 'meta_title', 'type' => 'text',     'label' => 'Meta Title'],
                ['id' => 'meta_desc',  'type' => 'textarea', 'label' => 'Meta Description'],
            ],
        ],
    ],
]);
```

Tab switching is handled via JavaScript. All tab content is rendered in the DOM; only the active panel is visible. All fields across all tabs are saved together.

---

## Conditional Field Display

Show or hide fields based on another field's value:

```php
[
    'id'      => 'payment_method',
    'type'    => 'select',
    'label'   => 'Payment Method',
    'options' => ['cash' => 'Cash', 'card' => 'Credit Card', 'bank' => 'Bank Transfer'],
],
[
    'id'    => 'card_number',
    'type'  => 'text',
    'label' => 'Card Number',
    'conditional' => [
        'field'    => 'payment_method',
        'operator' => '==',
        'value'    => 'card',
    ],
],
[
    'id'    => 'bank_name',
    'type'  => 'text',
    'label' => 'Bank Name',
    'conditional' => [
        'field'    => 'payment_method',
        'operator' => '==',
        'value'    => 'bank',
    ],
],
```

### Operators

| Operator | Description |
|---|---|
| `==` | Equals (default) |
| `!=` | Not equals |
| `contains` | Source value contains the target string |
| `!empty` | Source value is not empty |
| `empty` | Source value is empty |

Conditional fields are hidden initially and revealed via JavaScript. Hidden fields are still submitted in the form.

---

## Taxonomy Term Meta

Add custom fields to taxonomy term edit screens:

```php
add_custom_taxonomy_meta('category', [
    ['id' => 'cat_icon',  'type' => 'file',  'label' => 'Category Icon'],
    ['id' => 'cat_color', 'type' => 'color', 'label' => 'Category Color'],
]);
```

Fields appear on both the "Add New" and "Edit" term screens. Data is stored via `update_term_meta()` and retrieved via `get_term_meta()`.

---

## User Profile Meta

Add custom fields to user profile pages:

```php
add_custom_user_meta([
    ['id' => 'user_twitter',  'type' => 'url',  'label' => 'Twitter URL'],
    ['id' => 'user_bio_long', 'type' => 'wysiwyg', 'label' => 'Extended Bio'],
]);
```

Fields appear under "Additional Information" on both your own profile and when editing other users. Data is stored via `update_user_meta()`.

---

## Options Pages

Create admin settings pages for global (non-post-specific) options:

```php
// Top-level menu page
add_custom_options_page(
    'my-settings',
    'My Plugin Settings',
    'My Settings',
    [
        ['id' => 'site_logo',    'type' => 'file',  'label' => 'Site Logo'],
        ['id' => 'footer_text',  'type' => 'textarea', 'label' => 'Footer Text'],
        ['id' => 'accent_color', 'type' => 'color', 'label' => 'Accent Color'],
    ]
);

// Sub-menu page under an existing parent
add_custom_options_page(
    'my-advanced',
    'Advanced Settings',
    'Advanced',
    [
        ['id' => 'api_endpoint', 'type' => 'url', 'label' => 'API Endpoint'],
    ],
    'manage_options',
    'my-settings'  // Parent slug
);
```

Data is stored via WordPress `register_setting()` / `get_option()`:

```php
$logo = get_option('site_logo');
$color = get_option('accent_color');
```

---

## Multi-language Fields

Enable per-locale values for fields that need translation:

```php
[
    'id'           => 'product_name',
    'type'         => 'text',
    'label'        => 'Product Name',
    'multilingual' => true,
    'locales'      => ['en', 'fr', 'es'],
]
```

This renders language tabs above the field input. Values are stored as separate meta keys: `product_name_en`, `product_name_fr`, `product_name_es`.

Retrieve localized values:

```php
$name_en = get_post_meta($post_id, 'product_name_en', true);
$name_fr = get_post_meta($post_id, 'product_name_fr', true);
```

---

## Revision Support

Meta box field values are automatically copied to post revisions and restored when a revision is restored. This uses the WordPress hooks:

- `wp_creating_autosave` / `_wp_put_post_revision` — copies meta to revision
- `wp_restore_post_revision` — restores meta from revision

No configuration needed; it works automatically for all registered fields.

---

## REST API Integration

Expose field values via the WordPress REST API:

```php
[
    'id'           => 'product_price',
    'type'         => 'number',
    'label'        => 'Price',
    'show_in_rest' => true,
]
```

This calls `register_post_meta()` with `'show_in_rest' => true`. The field value will appear in the post's `meta` object in REST responses.

---

## Gutenberg Sidebar Panel

Display meta box fields in the block editor sidebar instead of below the editor:

```php
add_custom_meta_box('cmb-seo', 'SEO Settings', 'post', [
    ['id' => 'meta_title', 'type' => 'text', 'label' => 'Meta Title'],
    ['id' => 'meta_desc',  'type' => 'textarea', 'label' => 'Meta Description'],
], 'normal', 'default');
```

Add `'gutenberg_panel' => true` to the meta box config (passed as part of the fields array context). The plugin registers a `PluginDocumentSettingPanel` component that renders the fields using native Gutenberg components (`TextControl`, `SelectControl`, `ToggleControl`, etc.).

---

## WP-CLI Commands

Manage meta box data from the command line:

```bash
# List all registered meta boxes
wp cmb list

# Get a field value
wp cmb get 123 product_price

# Set a field value
wp cmb set 123 product_price "29.99"

# Set a complex value (JSON)
wp cmb set 123 product_variants '[{"sku":"ABC","price":"10"}]'
```

---

## Import/Export

Export and import meta box configurations as JSON via **Tools > CMB Import/Export** in the admin.

### Export
Generates a JSON file containing all registered meta box configurations.

### Import
Upload a JSON file or paste JSON to register meta box configurations.

### Programmatic API

```php
use CMB\Core\ImportExport;

// Export to JSON string
$json = ImportExport::exportToJson();

// Import from JSON string (returns count of imported boxes)
$count = ImportExport::importFromJson($json);
```

---

## Admin UI Builder

Create meta boxes without code via **Meta Box Builder** in the admin menu. The UI provides:

- CRUD interface for meta box configurations
- Dynamic field row builder (add/remove fields with type, label, description)
- Post type, context, and priority selection
- Configurations stored in `wp_options` and auto-registered on `init`

---

## Field Dependency Graph

Visualize field conditional dependencies via **Tools > CMB Field Graph**. Shows:

- All registered meta boxes and their fields
- Conditional logic relationships (source → target)
- Operators and values used in conditions

### Programmatic API

```php
use CMB\Core\DependencyGraph;

$data = DependencyGraph::getDependencyData();
// Returns: ['meta_box_id' => ['title' => '...', 'fields' => N, 'dependencies' => [...]]]
```

---

## Bulk Meta Operations

Apply meta values across multiple posts via **Tools > CMB Bulk Ops**:

- **Set** — set a field value on all posts of a type
- **Delete** — remove a field from all posts
- **Find & Replace** — replace text within field values

### Programmatic API

```php
use CMB\Core\BulkOperations;

// Set a value on multiple posts
BulkOperations::bulkSet([1, 2, 3], 'product_status', 'published');

// Delete a field from multiple posts
BulkOperations::bulkDelete([1, 2, 3], 'old_field');
```

---

## Unsaved Changes Warning

The plugin automatically tracks changes to meta box fields. If the user tries to navigate away with unsaved changes, a browser confirmation dialog is shown. This uses the `beforeunload` event and is enabled by default.

---

## Lazy Loading

For groups with 20+ repeater items, the plugin automatically hides excess items and shows a "Load more" button. Items are revealed in batches of 20. This prevents the editor from becoming slow with very large repeater groups.

---

## Next Steps

- [Hooks Reference](hooks.md) — customize behavior with actions and filters
- [Extending](extending.md) — create custom field types
- [Configuration Reference](configuration-reference.md) — all config keys
