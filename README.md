# Custom Meta Box Builder

A modern, developer-friendly WordPress plugin for creating custom meta boxes using a clean PHP configuration array. Built with PSR-4 autoloading, an OOP architecture, and support for nested/repeatable field groups.

## Features

- **Declarative field definitions** — define meta boxes with simple PHP arrays
- **5 built-in field types** — text, textarea, select, checkbox, group
- **Repeatable fields** — any field or group can be made repeatable with `'repeat' => true`
- **Nested groups** — unlimited depth group-in-group nesting with correct name serialization
- **Flexible layouts** — horizontal (default), inline, and responsive width classes
- **Custom HTML attributes** — pass arbitrary attributes to any field input
- **Nonce-protected saves** — automatic nonce verification and input sanitization
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
        'id'    => 'book_title',
        'type'  => 'text',
        'label' => 'Book Title',
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
]);
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
| [Field Types](docs/field-types.md) | All built-in field types and their options |
| [Groups & Repeaters](docs/groups-and-repeaters.md) | Nested groups, repeatable fields, and deep nesting |
| [Configuration Reference](docs/configuration-reference.md) | Every config key explained |
| [Architecture](docs/architecture.md) | Plugin internals, class diagram, and data flow |
| [Extending](docs/extending.md) | How to create your own custom field types |
| [Testing](docs/testing.md) | Running and writing PHPUnit tests |

## Project Structure

```
custom-meta-box-builder/
├── custom-meta-box-builder.php   # Plugin entry point
├── public-api.php                # Global add_custom_meta_box() helper
├── composer.json                 # PSR-4 autoload config
├── phpunit.xml.dist              # PHPUnit configuration
├── assets/
│   ├── cmb-style.css             # Admin UI styles
│   └── cmb-script.js             # Repeater/group JS logic
├── src/
│   ├── Core/
│   │   ├── Plugin.php            # Boot & asset registration
│   │   ├── MetaBoxManager.php    # Meta box registration & save logic
│   │   ├── FieldRenderer.php     # Field rendering & name resolution
│   │   ├── Contracts/
│   │   │   ├── FieldInterface.php        # Field contract
│   │   │   └── Abstracts/
│   │   │       └── AbstractField.php     # Base field implementation
│   │   └── Traits/
│   │       └── ArrayAccessibleTrait.php  # Magic property access
│   └── Fields/
│       ├── TextField.php
│       ├── TextareaField.php
│       ├── SelectField.php
│       ├── CheckboxField.php
│       └── GroupField.php
├── tests/
│   ├── bootstrap.php
│   ├── PluginTest.php
│   ├── MetaBoxManagerTest.php
│   └── TextFieldTest.php
└── docs/                         # Documentation
```

## License

GPL-2.0-or-later
