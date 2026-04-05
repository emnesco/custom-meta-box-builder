<?php
use CMB\Core\MetaBoxManager;

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
