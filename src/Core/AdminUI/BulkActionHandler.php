<?php
declare(strict_types=1);

/**
 * Handles bulk operations: duplicate, toggle, and delete for meta box configurations.
 *
 * Extracted from ActionHandler to keep each handler under 200 lines.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\AdminUI;

defined( 'ABSPATH' ) || exit;

class BulkActionHandler {
    /**
     * Handle deletion of a field group configuration.
     */
    public static function handleDelete(): void {
        if (empty($_GET['cmb_delete']) || empty($_GET['page']) || $_GET['page'] !== 'cmb-builder') {
            return;
        }

        $id = sanitize_text_field($_GET['cmb_delete']);
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'cmb_delete_' . $id)) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $configs = ActionHandler::getConfigs();
        unset($configs[$id]);
        ActionHandler::saveConfigs($configs);

        /** @since 2.1 Fires after a field group config is deleted. */
        do_action('cmbbuilder_config_deleted', $id);

        wp_safe_redirect(admin_url('admin.php?page=cmb-builder&deleted=1'));
        exit;
    }

    /**
     * Handle duplication of a field group configuration.
     */
    public static function handleDuplicate(): void {
        if (empty($_GET['cmb_duplicate']) || empty($_GET['page']) || $_GET['page'] !== 'cmb-builder') {
            return;
        }

        $id = sanitize_text_field($_GET['cmb_duplicate']);
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'cmb_duplicate_' . $id)) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $configs = ActionHandler::getConfigs();
        if (!isset($configs[$id])) {
            return;
        }

        $newId = $id . '_copy';
        $counter = 1;
        while (isset($configs[$newId])) {
            $newId = $id . '_copy_' . $counter;
            $counter++;
        }

        $configs[$newId] = $configs[$id];
        /* translators: suffix appended to duplicated field group title */
        $configs[$newId]['title'] .= ' ' . __('(Copy)', 'custom-meta-box-builder');
        ActionHandler::saveConfigs($configs);

        wp_safe_redirect(admin_url('admin.php?page=cmb-builder&duplicated=1'));
        exit;
    }

    /**
     * Handle toggling the active state of a field group configuration.
     */
    public static function handleToggle(): void {
        if (empty($_GET['cmb_toggle']) || empty($_GET['page']) || $_GET['page'] !== 'cmb-builder') {
            return;
        }

        $id = sanitize_text_field($_GET['cmb_toggle']);
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'cmb_toggle_' . $id)) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $configs = ActionHandler::getConfigs();
        if (!isset($configs[$id])) {
            return;
        }

        $configs[$id]['active'] = !($configs[$id]['active'] ?? true);
        ActionHandler::saveConfigs($configs);

        wp_safe_redirect(admin_url('admin.php?page=cmb-builder&toggled=1'));
        exit;
    }
}
