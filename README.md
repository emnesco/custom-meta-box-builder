# Custom Meta Box Builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/emneslab/custom-meta-box-builder.svg?style=flat-square)](https://packagist.org/packages/emneslab/custom-meta-box-builder)
[![PHP Version](https://img.shields.io/packagist/php-v/emneslab/custom-meta-box-builder.svg?style=flat-square)](https://packagist.org/packages/emneslab/custom-meta-box-builder)
[![License](https://img.shields.io/packagist/l/emneslab/custom-meta-box-builder.svg?style=flat-square)](LICENSE)

A modern, developer-friendly WordPress meta box builder with 30+ field types, a visual admin UI, nested/repeatable groups, conditional logic, REST API, Gutenberg sidebar, WP-CLI, and more.

## Installation

```bash
composer require emneslab/custom-meta-box-builder
```

> **Note:** This package uses `composer/installers` with type `wordpress-plugin`. In a standard WordPress setup it installs to `wp-content/plugins/`. In [Bedrock](https://roots.io/bedrock/) it installs to `web/app/plugins/`.

Activate the plugin:

```bash
wp plugin activate custom-meta-box-builder
```

## Requirements

- PHP 8.1+
- WordPress 6.0+

## Quick Start

### Register a meta box via PHP

```php
add_custom_meta_box('cmb-book-details', 'Book Details', ['post', 'page'], [
    ['id' => 'book_title',   'type' => 'text',     'label' => 'Book Title', 'required' => true],
    ['id' => 'book_summary', 'type' => 'textarea', 'label' => 'Summary'],
    ['id' => 'book_genre',   'type' => 'select',   'label' => 'Genre', 'options' => [
        'fiction'     => 'Fiction',
        'non_fiction' => 'Non-Fiction',
        'sci_fi'     => 'Science Fiction',
    ]],
    ['id' => 'book_price', 'type' => 'number', 'label' => 'Price'],
], 'normal', 'high');
```

### Retrieve saved data

```php
// Using the public API
$title = cmb_get_field('book_title');
cmb_the_field('book_title'); // echoes escaped value

// Or standard WordPress
$title = get_post_meta(get_the_ID(), 'book_title', true);
```

### Visual Admin Builder

Navigate to **CMB Builder** in the WordPress admin to create field groups without code. The builder includes:

- Drag-and-drop field ordering
- Live PHP code generation
- Template code snippets (Result tab)
- Import/Export (JSON)

## Features

- **30+ field types** — text, textarea, number, select, checkbox, radio, file, image, gallery, color, date, time, range, toggle, password, post, user, taxonomy, group, flexible content, message, divider, and more
- **Repeatable fields** — any field can be made repeatable
- **Nested groups** — unlimited depth group-in-group nesting
- **Drag-and-drop** — sortable repeater rows
- **Tabs** — organize fields into tabbed sections
- **Conditional logic** — show/hide fields based on other field values (AND/OR)
- **Validation** — required, email, url, min, max, numeric, pattern rules
- **Flexible layouts** — horizontal, inline, responsive width classes (25%–100%)
- **Taxonomy meta** — add fields to category/tag edit screens
- **User profile meta** — add fields to user profile pages
- **Options pages** — create admin settings pages
- **REST API** — expose fields via `show_in_rest`
- **Gutenberg sidebar** — display fields in block editor sidebar
- **WP-CLI** — `wp cmb list`, `wp cmb get`, `wp cmb set`
- **Frontend forms** — `cmb_render_form()` / `cmb_the_form()`
- **Gutenberg blocks** — `cmb_register_block()` API
- **WPGraphQL** — auto-register fields in GraphQL schema
- **Import/Export** — JSON configurations
- **Multi-language** — per-locale field values
- **Bulk operations** — batch meta updates
- **Extensible** — create custom field types by extending `AbstractField`

## Field Types

| Category | Types |
|----------|-------|
| **Basic** | Text, Textarea, Number, Email, URL, Password, Hidden |
| **Content** | WYSIWYG |
| **Choice** | Select, Radio, Checkbox, Checkbox List, Toggle, Button Group |
| **Date & Color** | Date, Time, Color |
| **Media** | File, Image, Gallery |
| **Relational** | Post, Taxonomy, User |
| **Layout** | Group, Flexible Content, Message, Divider |
| **Special** | Link, oEmbed, Range |

## Extending

Create a custom field type:

```php
namespace MyPlugin\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class RatingField extends AbstractField {
    public function render(): void {
        $value = $this->getValue();
        printf(
            '<input type="range" name="%s" value="%s" min="1" max="5" step="1">',
            esc_attr($this->getNameAttribute()),
            esc_attr($value)
        );
    }

    public function sanitize(mixed $value): mixed {
        return max(1, min(5, (int) $value));
    }
}
```

Register it:

```php
use CMB\Core\FieldFactory;

FieldFactory::registerType('rating', \MyPlugin\Fields\RatingField::class);
```

## Hooks

All hooks fire with the `cmbbuilder_` prefix (legacy `cmb_` prefix is deprecated):

| Hook | Type | Description |
|------|------|-------------|
| `cmbbuilder_before_render_field` | Action | Before a field renders |
| `cmbbuilder_after_render_field` | Action | After a field renders |
| `cmbbuilder_before_save_field` | Action | Before a field value is saved |
| `cmbbuilder_after_save_field` | Action | After a field value is saved |
| `cmbbuilder_sanitize_{type}` | Filter | Custom sanitization per field type |
| `cmbbuilder_field_config` | Filter | Modify field config before rendering |
| `cmbbuilder_meta_box_config` | Filter | Modify meta box config at registration |
| `cmbbuilder_admin_box_saved` | Action | After an admin UI field group is saved |

## Documentation

See the [docs/](docs/) directory:

- [Getting Started](docs/getting-started.md)
- [Field Types](docs/field-types.md)
- [Groups & Repeaters](docs/groups-and-repeaters.md)
- [Configuration Reference](docs/configuration-reference.md)
- [Hooks Reference](docs/hooks.md)
- [Extending](docs/extending.md)
- [Advanced Features](docs/advanced-features.md)
- [Testing](docs/testing.md)

## Testing

```bash
composer install
composer test
```

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
