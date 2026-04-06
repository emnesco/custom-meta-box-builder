<?php
declare(strict_types=1);

/**
 * Admin list page — renders the meta box configuration list with pagination.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\AdminUI;

defined( 'ABSPATH' ) || exit;

class ListPage {
    public static function renderListPage(): void {
        $configs = ActionHandler::getConfigs();

        echo '<div class="wrap cmb-admin-wrap">';

        // Header
        echo '<div class="cmb-admin-header">';
        echo '<div class="cmb-admin-header-left">';
        echo '<h1 class="wp-heading-inline">' . esc_html__('Field Groups', 'custom-meta-box-builder') . '</h1>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=cmb-builder&action=new')) . '" class="page-title-action">' . esc_html__('Add New', 'custom-meta-box-builder') . '</a>';
        echo '</div>';
        echo '<div class="cmb-admin-header-right">';
        echo '<button type="button" class="button" id="cmb-import-trigger"><span class="dashicons dashicons-upload"></span> ' . esc_html__('Import', 'custom-meta-box-builder') . '</button>';
        if (!empty($configs)) {
            echo ' <a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=cmb-builder&cmb_export=all'), 'cmb_export_all')) . '" class="button"><span class="dashicons dashicons-download"></span> ' . esc_html__('Export All', 'custom-meta-box-builder') . '</a>';
        }
        echo '</div>';
        echo '</div>';

        // Notices
        self::renderNotices();

        // Import modal
        self::renderImportModal();

        // Empty state
        if (empty($configs)) {
            echo '<div class="cmb-empty-state-page">';
            echo '<div class="cmb-empty-icon"><span class="dashicons dashicons-editor-table"></span></div>';
            echo '<h2>' . esc_html__('No Field Groups Yet', 'custom-meta-box-builder') . '</h2>';
            echo '<p>' . esc_html__('Create your first field group to start adding custom meta boxes to your content.', 'custom-meta-box-builder') . '</p>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=cmb-builder&action=new')) . '" class="button button-primary button-hero">' . esc_html__('Create Field Group', 'custom-meta-box-builder') . '</a>';
            echo '</div>';
            echo '</div>';
            return;
        }

        // Table
        echo '<table class="wp-list-table widefat fixed striped cmb-groups-table">';
        echo '<thead><tr>';
        echo '<th class="column-title column-primary">' . esc_html__('Title', 'custom-meta-box-builder') . '</th>';
        echo '<th class="column-fields">' . esc_html__('Fields', 'custom-meta-box-builder') . '</th>';
        echo '<th class="column-location">' . esc_html__('Location', 'custom-meta-box-builder') . '</th>';
        echo '<th class="column-context">' . esc_html__('Position', 'custom-meta-box-builder') . '</th>';
        echo '<th class="column-status">' . esc_html__('Status', 'custom-meta-box-builder') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        $allPt = get_post_types(['public' => true], 'objects');

        // Pagination
        $perPage   = 20;
        $total     = count($configs);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $currentPage = max(1, min($totalPages, (int) ($_GET['paged'] ?? 1)));
        $offset    = ($currentPage - 1) * $perPage;
        $pagedConfigs = array_slice($configs, $offset, $perPage, true);

        foreach ($pagedConfigs as $boxId => $box) {
            $fieldCount = count($box['fields'] ?? []);
            $isActive   = $box['active'] ?? true;
            $postTypes  = $box['postTypes'] ?? [];
            $context    = ucfirst($box['context'] ?? 'advanced');

            echo '<tr class="' . ($isActive ? '' : 'cmb-inactive-row') . '">';

            // Title + row actions
            echo '<td class="column-title column-primary">';
            echo '<strong><a href="' . esc_url(admin_url('admin.php?page=cmb-builder&action=edit&id=' . urlencode($boxId))) . '" class="row-title">' . esc_html($box['title']) . '</a></strong>';
            echo '<code class="cmb-box-id">' . esc_html($boxId) . '</code>';
            echo '<div class="row-actions">';
            echo '<span class="edit"><a href="' . esc_url(admin_url('admin.php?page=cmb-builder&action=edit&id=' . urlencode($boxId))) . '">' . esc_html__('Edit', 'custom-meta-box-builder') . '</a> | </span>';
            echo '<span class="duplicate"><a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=cmb-builder&cmb_duplicate=' . urlencode($boxId)), 'cmb_duplicate_' . $boxId)) . '">' . esc_html__('Duplicate', 'custom-meta-box-builder') . '</a> | </span>';
            echo '<span class="export"><a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=cmb-builder&cmb_export=' . urlencode($boxId)), 'cmb_export_' . $boxId)) . '">' . esc_html__('Export JSON', 'custom-meta-box-builder') . '</a> | </span>';
            echo '<span class="export-php"><a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=cmb-builder&cmb_export_php=' . urlencode($boxId)), 'cmb_export_php_' . $boxId)) . '">' . esc_html__('Export PHP', 'custom-meta-box-builder') . '</a> | </span>';
            echo '<span class="delete"><a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=cmb-builder&cmb_delete=' . urlencode($boxId)), 'cmb_delete_' . $boxId)) . '" class="submitdelete" data-confirm="' . esc_attr(__('Delete this field group?', 'custom-meta-box-builder')) . '">' . esc_html__('Delete', 'custom-meta-box-builder') . '</a></span>';
            echo '</div>';
            echo '</td>';

            // Fields count
            echo '<td class="column-fields"><span class="cmb-field-count">' . $fieldCount . '</span></td>';

            // Location
            echo '<td class="column-location">';
            $ptLabels = [];
            foreach ($postTypes as $pt) {
                $ptLabels[] = isset($allPt[$pt]) ? $allPt[$pt]->labels->singular_name : $pt;
            }
            echo esc_html(implode(', ', $ptLabels));
            echo '</td>';

            // Context
            echo '<td class="column-context">' . esc_html($context) . '</td>';

            // Status
            $toggleUrl = wp_nonce_url(
                admin_url('admin.php?page=cmb-builder&cmb_toggle=' . urlencode($boxId)),
                'cmb_toggle_' . $boxId
            );
            echo '<td class="column-status">';
            echo '<a href="' . esc_url($toggleUrl) . '" class="cmb-status-badge ' . ($isActive ? 'active' : 'inactive') . '">';
            echo $isActive ? esc_html__('Active', 'custom-meta-box-builder') : esc_html__('Inactive', 'custom-meta-box-builder');
            echo '</a>';
            echo '</td>';

            echo '</tr>';
        }

        echo '</tbody></table>';

        // Pagination
        if ($totalPages > 1) {
            echo '<div class="tablenav bottom"><div class="tablenav-pages">';
            /* translators: %d: number of items */
            echo '<span class="displaying-num">' . sprintf(esc_html__('%d items', 'custom-meta-box-builder'), $total) . '</span>';
            $baseUrl = admin_url('admin.php?page=cmb-builder');
            if ($currentPage > 1) {
                echo ' <a class="prev-page button" href="' . esc_url(add_query_arg('paged', $currentPage - 1, $baseUrl)) . '">&lsaquo;</a>';
            }
            /* translators: 1: current page, 2: total pages */
            echo ' <span class="paging-input">' . sprintf(esc_html__('%1$s of %2$s', 'custom-meta-box-builder'), $currentPage, $totalPages) . '</span>';
            if ($currentPage < $totalPages) {
                echo ' <a class="next-page button" href="' . esc_url(add_query_arg('paged', $currentPage + 1, $baseUrl)) . '">&rsaquo;</a>';
            }
            echo '</div></div>';
        }

        echo '</div>';
    }

    public static function renderNotices(): void {
        if (!empty($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Field group saved successfully.', 'custom-meta-box-builder') . '</p></div>';
        }
        if (!empty($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Field group deleted.', 'custom-meta-box-builder') . '</p></div>';
        }
        if (!empty($_GET['duplicated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Field group duplicated.', 'custom-meta-box-builder') . '</p></div>';
        }
        if (!empty($_GET['imported'])) {
            $count = intval($_GET['imported']);
            /* translators: %d: number of field groups imported */
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('Imported %d field group(s) successfully.', 'custom-meta-box-builder'), $count) . '</p></div>';
        }
        if (!empty($_GET['toggled'])) {
            echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__('Field group status updated.', 'custom-meta-box-builder') . '</p></div>';
        }
        if (!empty($_GET['error'])) {
            $errors = [
                'invalid_json'  => __('Invalid JSON format.', 'custom-meta-box-builder'),
                'no_data'       => __('No import data provided.', 'custom-meta-box-builder'),
                'import_failed' => __('Import failed. Check your JSON structure.', 'custom-meta-box-builder'),
            ];
            $msg = $errors[sanitize_text_field($_GET['error'])] ?? __('An error occurred.', 'custom-meta-box-builder');
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($msg) . '</p></div>';
        }
    }

    public static function renderImportModal(): void {
        echo '<div class="cmb-import-overlay" id="cmb-import-modal" style="display:none">';
        echo '<div class="cmb-import-modal">';
        echo '<div class="cmb-import-header">';
        echo '<h2>' . esc_html__('Import Field Groups', 'custom-meta-box-builder') . '</h2>';
        echo '<button type="button" class="cmb-import-close" id="cmb-import-close">&times;</button>';
        echo '</div>';
        echo '<form method="post" enctype="multipart/form-data" class="cmb-import-body">';
        wp_nonce_field('cmb_import', 'cmb_import_nonce');
        echo '<input type="hidden" name="cmb_action" value="import">';
        echo '<div class="cmb-import-section">';
        echo '<label class="cmb-import-file-label">';
        echo '<span class="dashicons dashicons-media-default"></span>';
        echo '<span>' . esc_html__('Choose a JSON file or drag it here', 'custom-meta-box-builder') . '</span>';
        echo '<input type="file" name="cmb_import_file" accept=".json" class="cmb-import-file-input">';
        echo '</label>';
        echo '</div>';
        echo '<div class="cmb-import-divider"><span>' . esc_html__('or', 'custom-meta-box-builder') . '</span></div>';
        echo '<div class="cmb-import-section">';
        echo '<label>' . esc_html__('Paste JSON', 'custom-meta-box-builder') . '</label>';
        echo '<textarea name="cmb_import_json" rows="8" class="widefat code" placeholder=\'{"version":"1.0","meta_boxes":{...}}\'></textarea>';
        echo '</div>';
        echo '<div class="cmb-import-actions">';
        echo '<button type="submit" class="button button-primary">' . esc_html__('Import', 'custom-meta-box-builder') . '</button>';
        echo '<button type="button" class="button cmb-import-cancel">' . esc_html__('Cancel', 'custom-meta-box-builder') . '</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
    }
}
