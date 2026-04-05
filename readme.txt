=== Custom Meta Box Builder ===
Contributors: emneslab
Tags: meta box, custom fields, meta, post meta, custom meta box
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A developer-friendly plugin for creating custom meta boxes with a visual admin builder and a powerful PHP API.

== Description ==

Custom Meta Box Builder (CMBB) provides both a visual admin interface and a PHP API for creating custom meta boxes, taxonomy meta, user meta, and options pages.

**Key Features:**

* 20+ field types: text, textarea, number, select, checkbox, radio, file, image, gallery, color, date, time, range, toggle, password, post, user, taxonomy, group, message, divider
* Repeatable fields and nested groups with drag-and-drop reordering
* Tabbed meta box layouts
* Conditional field display logic
* Multi-language support
* Gutenberg sidebar integration
* REST API field registration
* Import/export configurations (JSON and PHP)
* Admin UI builder for no-code field group creation
* Bulk operations for batch meta updates
* WP-CLI commands
* Template functions: `cmb_get_field()`, `cmb_the_field()`, `cmb_get_term_field()`, `cmb_get_user_field()`, `cmb_get_option()`

== Installation ==

1. Upload the `custom-meta-box-builder` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Use the "CMB Builder" admin menu to create field groups visually, or use the PHP API in your theme/plugin.

== Frequently Asked Questions ==

= How do I create a meta box with PHP? =

    add_custom_meta_box('my_box', 'My Meta Box', ['post'], [
        ['id' => 'subtitle', 'type' => 'text', 'label' => 'Subtitle'],
        ['id' => 'featured', 'type' => 'checkbox', 'label' => 'Featured'],
    ]);

= How do I retrieve field values in templates? =

    $subtitle = cmb_get_field('subtitle');
    cmb_the_field('subtitle'); // echoes escaped value

= Does it support Gutenberg? =

Yes. Fields with `'show_in_rest' => true` appear in the Gutenberg document sidebar.

== Changelog ==

= 2.1.0 =
* Added value retrieval API (cmb_get_field, cmb_the_field, etc.)
* Added new field types: time, range, toggle, message, divider, image, gallery, checkbox_list
* Added multi-select support to select field
* Added AJAX search endpoints for relational fields
* Added location rules for conditional meta box display
* Added PHP code export from admin UI
* Added ARIA roles and keyboard navigation for tabs
* Added asset minification build pipeline with filemtime cache busting
* Performance: static caching for post/taxonomy/user queries, optimized save pattern, scoped save_post hook
* Security: wp_unslash on all POST reads, taxonomy-specific nonces, regex validation, FieldInterface enforcement
* Accessibility: fieldset legends, focus styles, color contrast fixes
* Various bug fixes and improvements

= 2.0.0 =
* Initial release with admin UI builder
* 15 field types
* Gutenberg sidebar integration
* REST API support
* Import/export functionality
