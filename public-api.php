<?php
defined( 'ABSPATH' ) || exit;

use CMB\Core\Plugin;

if (!function_exists('add_custom_meta_box')) {
    /**
     * Register a custom meta box for post types.
     */
    function add_custom_meta_box(
        string $id,
        string $title,
        $postTypes,
        array $fields,
        string $context = 'advanced',
        string $priority = 'default'
    ): void {
        $plugin = Plugin::getInstance();
        if ( $plugin ) {
            $plugin->getManager()->add($id, $title, (array) $postTypes, $fields, $context, $priority);
        }
    }
}

if (!function_exists('add_custom_taxonomy_meta')) {
    /**
     * Add custom meta fields to a taxonomy term edit screen.
     */
    function add_custom_taxonomy_meta(string $taxonomy, array $fields): void {
        $plugin = Plugin::getInstance();
        if ( $plugin ) {
            $plugin->getTaxonomyManager()->add($taxonomy, $fields);
        }
    }
}

if (!function_exists('add_custom_user_meta')) {
    /**
     * Add custom meta fields to user profile screens.
     */
    function add_custom_user_meta(array $fields): void {
        $plugin = Plugin::getInstance();
        if ( $plugin ) {
            $plugin->getUserMetaManager()->add($fields);
        }
    }
}

if (!function_exists('add_custom_options_page')) {
    /**
     * Add a custom options page with fields.
     */
    function add_custom_options_page(
        string $pageSlug,
        string $pageTitle,
        string $menuTitle,
        array $fields,
        string $capability = 'manage_options',
        string $parentSlug = ''
    ): void {
        $plugin = Plugin::getInstance();
        if ( $plugin ) {
            $plugin->getOptionsManager()->add($pageSlug, $pageTitle, $menuTitle, $fields, $capability, $parentSlug);
        }
    }
}

// === Value Retrieval API ===

if (!function_exists('cmb_get_field')) {
    /**
     * Get a post meta field value.
     */
    function cmb_get_field(string $fieldId, ?int $postId = null): mixed {
        $postId = $postId ?: get_the_ID();
        $value = get_post_meta($postId, $fieldId, true);
        return apply_filters('cmbbuilder_get_field_value', $value, $fieldId, $postId);
    }
}

if (!function_exists('cmb_the_field')) {
    /**
     * Echo a post meta field value (escaped).
     */
    function cmb_the_field(string $fieldId, ?int $postId = null): void {
        $value = cmb_get_field($fieldId, $postId);
        if (is_array($value)) {
            echo esc_html(implode(', ', $value));
        } else {
            echo esc_html((string) $value);
        }
    }
}

if (!function_exists('cmb_get_term_field')) {
    /**
     * Get a term meta field value.
     */
    function cmb_get_term_field(string $fieldId, int $termId): mixed {
        $value = get_term_meta($termId, $fieldId, true);
        return apply_filters('cmbbuilder_get_term_field_value', $value, $fieldId, $termId);
    }
}

if (!function_exists('cmb_get_user_field')) {
    /**
     * Get a user meta field value.
     */
    function cmb_get_user_field(string $fieldId, ?int $userId = null): mixed {
        $userId = $userId ?: get_current_user_id();
        $value = get_user_meta($userId, $fieldId, true);
        return apply_filters('cmbbuilder_get_user_field_value', $value, $fieldId, $userId);
    }
}

if (!function_exists('cmb_get_option')) {
    /**
     * Get an options page field value.
     */
    function cmb_get_option(string $fieldId): mixed {
        $value = get_option($fieldId);
        return apply_filters('cmbbuilder_get_option_value', $value, $fieldId);
    }
}

if (!function_exists('cmb_register_block')) {
    /**
     * Register a custom Gutenberg block backed by CMB fields.
     *
     * @param string $blockId Block identifier.
     * @param array  $config  Block configuration (title, fields, render_callback/render_template).
     */
    function cmb_register_block(string $blockId, array $config): void {
        \CMB\Core\BlockRegistration::register($blockId, $config);
    }
}

if (!function_exists('cmb_render_form')) {
    /**
     * Render a meta box form on the frontend.
     *
     * @param string   $metaBoxId The meta box ID.
     * @param int|null $postId    Optional post ID (defaults to current post).
     * @param array    $args      Optional arguments (submit_text, form_id, method).
     * @return string The form HTML.
     */
    function cmb_render_form(string $metaBoxId, ?int $postId = null, array $args = []): string {
        return \CMB\Core\FrontendForm::render($metaBoxId, $postId, $args);
    }
}

if (!function_exists('cmb_the_form')) {
    /**
     * Echo a meta box form on the frontend.
     */
    function cmb_the_form(string $metaBoxId, ?int $postId = null, array $args = []): void {
        echo cmb_render_form($metaBoxId, $postId, $args);
    }
}
