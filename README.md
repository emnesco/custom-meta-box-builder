# Custom Meta Box Builder

A modern, developer-friendly WordPress plugin for creating custom meta boxes using a clean PHP configuration array. Built with PSR-4 autoloading, an OOP architecture, and support for nested/repeatable field groups.

## Features

- **Declarative field definitions** — define meta boxes with simple PHP arrays
- **18 built-in field types** — text, textarea, select, checkbox, group, number, email, url, radio, hidden, password, date, color, wysiwyg, file, post, taxonomy, user
- **Repeatable fields** — any field or group can be made repeatable with `'repeat' => true`
- **Nested groups** — unlimited depth group-in-group nesting with correct name serialization
- **Sortable drag-and-drop** — reorder repeater rows with jQuery UI Sortable
- **Tabs** — organize fields into tabbed sections within a meta box
- **Conditional logic** — show/hide fields based on other field values
- **Validation system** — required, email, url, min, max, numeric, pattern rules with admin notices
- **Flexible layouts** — horizontal (default), inline, and responsive width classes
- **Custom HTML attributes** — pass arbitrary attributes to any field input
- **Security** — unique nonce per meta box, capability checks, recursive sanitization
- **Taxonomy meta** — add custom fields to category/tag edit screens
- **User profile meta** — add custom fields to user profile pages
- **Options pages** — create admin settings pages with global options
- **Revision support** — meta values are saved with post revisions
- **REST API integration** — expose fields via WordPress REST API
- **WP-CLI commands** — `wp cmb list`, `wp cmb get`, `wp cmb set`
- **Gutenberg sidebar panel** — display fields in the block editor sidebar
- **Developer hooks** — 8 actions/filters for customizing behavior
- **Field type registration** — register custom field types from any namespace
- **Import/Export** — export and import meta box configurations as JSON
- **Admin UI builder** — create meta boxes without code via admin interface
- **Multi-language support** — per-locale field values with language tabs
- **Bulk operations** — apply meta values across multiple posts at once
- **Extensible** — create new field types by extending a single abstract class

## Requirements

- PHP 8.0+
- WordPress 5.8+
- Composer (for autoloading)

## Quick Start

### 1. Install

Place the plugin in `wp-content/plugins/custom-meta-box-builder` (or your Bedrock equivalent) and run:

```bash
composer install
```

Activate the plugin from WP Admin or via WP-CLI:

```bash
wp plugin activate custom-meta-box-builder
```

### 2. Register a Meta Box

In your theme's `functions.php` or a custom plugin, use the global helper:

```php
add_custom_meta_box('cmb-book-details', 'Book Details', ['post', 'page'], [
    [
        'id'       => 'book_title',
        'type'     => 'text',
        'label'    => 'Book Title',
        'required' => true,
    ],
    [
        'id'    => 'book_summary',
        'type'  => 'textarea',
        'label' => 'Summary',
    ],
    [
        'id'      => 'book_genre',
        'type'    => 'select',
        'label'   => 'Genre',
        'options' => [
            'fiction'     => 'Fiction',
            'non_fiction' => 'Non-Fiction',
            'sci_fi'     => 'Science Fiction',
        ],
    ],
    [
        'id'    => 'book_price',
        'type'  => 'number',
        'label' => 'Price',
        'attributes' => ['min' => '0', 'step' => '0.01'],
    ],
], 'normal', 'high');
```

### 3. Retrieve Saved Data

```php
$title   = get_post_meta(get_the_ID(), 'book_title', true);
$summary = get_post_meta(get_the_ID(), 'book_summary', true);
$genre   = get_post_meta(get_the_ID(), 'book_genre', true);
```

## Documentation

| Document | Description |
|---|---|
| [Getting Started](docs/getting-started.md) | Installation, activation, and first meta box |
| [Field Types](docs/field-types.md) | All 18 built-in field types and their options |
| [Groups & Repeaters](docs/groups-and-repeaters.md) | Nested groups, repeatable fields, sortable rows, and deep nesting |
| [Configuration Reference](docs/configuration-reference.md) | Every config key explained |
| [Architecture](docs/architecture.md) | Plugin internals, class diagram, and data flow |
| [Extending](docs/extending.md) | How to create your own custom field types |
| [Hooks Reference](docs/hooks.md) | All actions and filters for developers |
| [Advanced Features](docs/advanced-features.md) | Tabs, conditional logic, multi-language, bulk ops, import/export |
| [Testing](docs/testing.md) | Running and writing PHPUnit tests |

## Project Structure

```
custom-meta-box-builder/
├── custom-meta-box-builder.php   # Plugin entry point
├── public-api.php                # Global helper functions
├── composer.json                 # PSR-4 autoload config
├── phpunit.xml.dist              # PHPUnit configuration
├── assets/
│   ├── cmb-style.css             # Admin UI styles
│   ├── cmb-script.js             # Repeater/group/tabs/conditional JS logic
│   └── cmb-gutenberg.js          # Gutenberg sidebar panel components
├── src/
│   ├── Core/
│   │   ├── Plugin.php            # Boot & asset registration
│   │   ├── MetaBoxManager.php    # Meta box registration, save, validation, REST
│   │   ├── FieldRenderer.php     # Field rendering, name resolution, caching
│   │   ├── TaxonomyMetaManager.php  # Taxonomy term meta support
│   │   ├── UserMetaManager.php      # User profile meta support
│   │   ├── OptionsManager.php       # Admin options pages
│   │   ├── ImportExport.php         # JSON import/export
│   │   ├── AdminUI.php              # No-code meta box builder
│   │   ├── GutenbergPanel.php       # Block editor sidebar
│   │   ├── WpCliCommands.php        # WP-CLI integration
│   │   ├── DependencyGraph.php      # Field dependency visualization
│   │   ├── BulkOperations.php       # Bulk meta operations
│   │   ├── Contracts/
│   │   │   ├── FieldInterface.php          # Field contract
│   │   │   └── Abstracts/
│   │   │       └── AbstractField.php       # Base field implementation
│   │   └── Traits/
│   │       ├── ArrayAccessibleTrait.php    # Magic property access
│   │       └── MultiLanguageTrait.php      # Per-locale field support
│   └── Fields/
│       ├── TextField.php          ├── NumberField.php
│       ├── TextareaField.php      ├── EmailField.php
│       ├── SelectField.php        ├── UrlField.php
│       ├── CheckboxField.php      ├── RadioField.php
│       ├── GroupField.php         ├── HiddenField.php
│       ├── PasswordField.php      ├── DateField.php
│       ├── ColorField.php         ├── WysiwygField.php
│       ├── FileField.php          ├── PostField.php
│       ├── TaxonomyField.php      └── UserField.php
├── tests/
│   ├── bootstrap.php
│   ├── PluginTest.php
│   ├── MetaBoxManagerTest.php
│   └── TextFieldTest.php
└── docs/                         # Documentation
```

## License

GPL-2.0-or-later
