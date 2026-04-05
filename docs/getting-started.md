# Getting Started

[Back to README](../README.md)

## Prerequisites

- PHP 8.0 or higher
- WordPress 5.8 or higher
- Composer installed on your system

## Installation

### Standard WordPress

1. Clone or copy the plugin into your plugins directory:

```bash
cd wp-content/plugins/
git clone <repo-url> custom-meta-box-builder
cd custom-meta-box-builder
composer install
```

2. Activate the plugin from **WP Admin > Plugins** or via WP-CLI:

```bash
wp plugin activate custom-meta-box-builder
```

### Bedrock

The plugin directory lives at `web/app/plugins/custom-meta-box-builder`. Add it to your root `composer.json` as a path repository or manage it as a Git submodule. Then run `composer install` inside the plugin directory.

## Registering Your First Meta Box

The plugin exposes a single global function: `add_custom_meta_box()`. Call it at any point before the `add_meta_boxes` action fires (e.g., in `functions.php` or an `init`/`plugins_loaded` hook).

```php
add_custom_meta_box(
    'cmb-contact-info',           // Unique ID (prefixed with cmb- by convention)
    'Contact Information',        // Title shown in the editor
    ['post', 'page'],             // Post type(s) — string or array
    [
        [
            'id'    => 'contact_email',
            'type'  => 'text',
            'label' => 'Email Address',
        ],
        [
            'id'    => 'contact_phone',
            'type'  => 'text',
            'label' => 'Phone Number',
        ],
        [
            'id'          => 'contact_notes',
            'type'        => 'textarea',
            'label'       => 'Notes',
            'description' => 'Internal notes about this contact.',
        ],
    ]
);
```

## Function Signature

```php
add_custom_meta_box(
    string $id,            // Unique meta box identifier
    string $title,         // Display title
    string|array $postTypes,  // One or more post types
    array $fields          // Array of field configuration arrays
): void
```

The function is defined in [public-api.php](../public-api.php). It creates a `MetaBoxManager` instance (or reuses the existing global one), registers WordPress hooks, and stores the field definitions for rendering and saving.

## Retrieving Saved Data

All field values are saved as standard WordPress post meta. Use the core `get_post_meta()` function:

```php
// Single value fields (text, textarea, select, checkbox)
$email = get_post_meta($post_id, 'contact_email', true);

// Repeatable fields — stored as multiple meta rows
$phones = get_post_meta($post_id, 'contact_phone'); // returns array

// Group fields — stored as serialized arrays
$group = get_post_meta($post_id, 'my_group_id');    // returns array of groups
```

See [Groups & Repeaters](groups-and-repeaters.md) for details on how nested data is stored and retrieved.

## Next Steps

- [Field Types](field-types.md) — learn about every available field type
- [Configuration Reference](configuration-reference.md) — all configuration keys
- [Groups & Repeaters](groups-and-repeaters.md) — nested and repeatable fields
