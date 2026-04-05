<?php
use CMB\Core\MetaBoxManager;
use CMB\Core\TaxonomyMetaManager;
use CMB\Core\UserMetaManager;
use CMB\Core\OptionsManager;

if (!function_exists('add_custom_meta_box')) {
    function add_custom_meta_box(
        string $id,
        string $title,
        $postTypes,
        array $fields,
        string $context = 'advanced',
        string $priority = 'default'
    ): void {
        $manager = MetaBoxManager::instance();
        $manager->add($id, $title, (array) $postTypes, $fields, $context, $priority);
    }
}

if (!function_exists('add_custom_taxonomy_meta')) {
    /**
     * Add custom meta fields to a taxonomy term edit screen.
     */
    function add_custom_taxonomy_meta(string $taxonomy, array $fields): void {
        static $manager = null;
        if ($manager === null) {
            $manager = new TaxonomyMetaManager();
            $manager->register();
        }
        $manager->add($taxonomy, $fields);
    }
}

if (!function_exists('add_custom_user_meta')) {
    /**
     * Add custom meta fields to user profile screens.
     */
    function add_custom_user_meta(array $fields): void {
        static $manager = null;
        if ($manager === null) {
            $manager = new UserMetaManager();
            $manager->register();
        }
        $manager->add($fields);
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
        static $manager = null;
        if ($manager === null) {
            $manager = new OptionsManager();
            $manager->register();
        }
        $manager->add($pageSlug, $pageTitle, $menuTitle, $fields, $capability, $parentSlug);
    }
}
