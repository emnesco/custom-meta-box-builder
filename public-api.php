
<?php

use CMB\Core\MetaBoxManager;

if (!function_exists('add_custom_meta_box')) {
    function add_custom_meta_box(string $id, string $title, $postTypes, array $fields): void {
        global $cmb_meta_box_manager;

        if (!isset($cmb_meta_box_manager)) {
            $cmb_meta_box_manager = new MetaBoxManager();
            $cmb_meta_box_manager->register();
        }

        $cmb_meta_box_manager->add($id, $title, (array) $postTypes, $fields);
    }
}
