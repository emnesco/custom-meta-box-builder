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

= What PHP versions are supported? =

Tested up to PHP 8.4. Requires PHP 8.1+.

== Changelog ==

= 2.1.0 =
* Added FlexibleContentField for layout-based content building
* Added FrontendForm for frontend rendering via cmb_render_form() / cmb_the_form()
* Added BlockRegistration API for Gutenberg blocks via cmb_register_block()
* Added WPGraphQL integration for auto-registering CMB fields in GraphQL schema
* Added LocalJson sync for saving field configs as JSON files for version control
* Added expanded Gutenberg sidebar support: radio, color, date, toggle, file/image fields
* Added hook prefix migration with dual-firing cmbbuilder_ (new) and cmb_ (backward compat)
* Added PHPDoc annotations on all hook call sites and all PHP files
* Added improved error messages with actionable guidance in _doing_it_wrong() calls
* Added type aliases in FieldFactory for flexible_content and checkbox_list types
* Changed: Standardized all JS files to ES6+ (const/let, arrow functions, destructuring)

= 2.0.0 =
* Added new field types: time, range, toggle, message, divider, image, gallery, checkbox_list
* Added multi-select support to select field with placeholder
* Added ColorField with wp-color-picker and alpha/rgba support
* Added public API: cmb_get_field(), cmb_the_field(), cmb_get_term_field(), cmb_get_user_field(), cmb_get_option()
* Added AjaxHandler with nonce-verified search endpoints for posts, users, terms
* Added LocationMatcher with AND/OR rule matching for conditional meta box display
* Added AND/OR conditional logic with data-conditional-groups
* Added PHP code export from Admin UI
* Added before/after save hooks on user and taxonomy meta managers
* Added ARIA tab navigation with keyboard support
* Added client-side validation with blur handler and form submit prevention
* Added CSS custom properties for theming
* Added Brain\Monkey test infrastructure with unit tests
* Added esbuild scripts for JS+CSS minification
* Added GitHub Actions CI with PHP 8.1/8.2/8.3 matrix
* AdminUI refactored into Router, ListPage, EditPage, ActionHandler
* RenderContext pattern for unified field rendering across post/term/user/option contexts
* StorageInterface with PostMeta/TermMeta/UserMeta/OptionStorage implementations
* ServiceProvider pattern with conditional loading for modular features
* Security: wp_unslash on all POST reads, taxonomy-specific nonces, regex validation
* Performance: static caching, filemtime versioning, debounced conditionals
* Accessibility: fieldset legends, focus styles, color contrast fixes
