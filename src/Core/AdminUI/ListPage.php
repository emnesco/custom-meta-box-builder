<?php
namespace CMB\Core\AdminUI;

class ListPage {
    public static function renderListPage(): void {
        $configs = ActionHandler::getConfigs();

        echo '<div class="wrap cmb-admin-wrap">';

        // Header
        echo '<div class="cmb-admin-header">';
        echo '<div class="cmb-admin-header-left">';
        echo '<h1 class="wp-heading-inline">Field Groups</h1>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=cmb-builder&action=new')) . '" class="page-title-action">Add New</a>';
        echo '</div>';
        echo '<div class="cmb-admin-header-right">';
        echo '<button type="button" class="button" id="cmb-import-trigger"><span class="dashicons dashicons-upload"></span> Import</button>';
        if (!empty($configs)) {
            echo ' <a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=cmb-builder&cmb_export=all'), 'cmb_export_all')) . '" class="button"><span class="dashicons dashicons-download"></span> Export All</a>';
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
            echo '<h2>No Field Groups Yet</h2>';
            echo '<p>Create your first field group to start adding custom meta boxes to your content.</p>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=cmb-builder&action=new')) . '" class="button button-primary button-hero">Create Field Group</a>';
            echo '</div>';
            echo '</div>';
            return;
        }

        // Table
        echo '<table class="wp-list-table widefat fixed striped cmb-groups-table">';
        echo '<thead><tr>';
        echo '<th class="column-title column-primary">Title</th>';
        echo '<th class="column-fields">Fields</th>';
        echo '<th class="column-location">Location</th>';
        echo '<th class="column-context">Position</th>';
        echo '<th class="column-status">Status</th>';
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
            echo '<span class="edit"><a href="' . esc_url(admin_url('admin.php?page=cmb-builder&action=edit&id=' . urlencode($boxId))) . '">Edit</a> | </span>';
            echo '<span class="duplicate"><a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=cmb-builder&cmb_duplicate=' . urlencode($boxId)), 'cmb_duplicate_' . $boxId)) . '">Duplicate</a> | </span>';
            echo '<span class="export"><a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=cmb-builder&cmb_export=' . urlencode($boxId)), 'cmb_export_' . $boxId)) . '">Export JSON</a> | </span>';
            echo '<span class="export-php"><a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=cmb-builder&cmb_export_php=' . urlencode($boxId)), 'cmb_export_php_' . $boxId)) . '">Export PHP</a> | </span>';
            echo '<span class="delete"><a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=cmb-builder&cmb_delete=' . urlencode($boxId)), 'cmb_delete_' . $boxId)) . '" class="submitdelete" onclick="return confirm(\'Delete this field group?\')">Delete</a></span>';
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
            echo $isActive ? 'Active' : 'Inactive';
            echo '</a>';
            echo '</td>';

            echo '</tr>';
        }

        echo '</tbody></table>';

        // Pagination
        if ($totalPages > 1) {
            echo '<div class="tablenav bottom"><div class="tablenav-pages">';
            echo '<span class="displaying-num">' . $total . ' items</span>';
            $baseUrl = admin_url('admin.php?page=cmb-builder');
            if ($currentPage > 1) {
                echo ' <a class="prev-page button" href="' . esc_url(add_query_arg('paged', $currentPage - 1, $baseUrl)) . '">&lsaquo;</a>';
            }
            echo ' <span class="paging-input">' . $currentPage . ' of ' . $totalPages . '</span>';
            if ($currentPage < $totalPages) {
                echo ' <a class="next-page button" href="' . esc_url(add_query_arg('paged', $currentPage + 1, $baseUrl)) . '">&rsaquo;</a>';
            }
            echo '</div></div>';
        }

        echo '</div>';
    }

    public static function renderNotices(): void {
        if (!empty($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Field group saved successfully.</p></div>';
        }
        if (!empty($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Field group deleted.</p></div>';
        }
        if (!empty($_GET['duplicated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Field group duplicated.</p></div>';
        }
        if (!empty($_GET['imported'])) {
            $count = intval($_GET['imported']);
            echo '<div class="notice notice-success is-dismissible"><p>Imported ' . $count . ' field group(s) successfully.</p></div>';
        }
        if (!empty($_GET['toggled'])) {
            echo '<div class="notice notice-info is-dismissible"><p>Field group status updated.</p></div>';
        }
        if (!empty($_GET['error'])) {
            $errors = [
                'invalid_json'  => 'Invalid JSON format.',
                'no_data'       => 'No import data provided.',
                'import_failed' => 'Import failed. Check your JSON structure.',
            ];
            $msg = $errors[sanitize_text_field($_GET['error'])] ?? 'An error occurred.';
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($msg) . '</p></div>';
        }
    }

    public static function renderImportModal(): void {
        echo '<div class="cmb-import-overlay" id="cmb-import-modal" style="display:none">';
        echo '<div class="cmb-import-modal">';
        echo '<div class="cmb-import-header">';
        echo '<h2>Import Field Groups</h2>';
        echo '<button type="button" class="cmb-import-close" id="cmb-import-close">&times;</button>';
        echo '</div>';
        echo '<form method="post" enctype="multipart/form-data" class="cmb-import-body">';
        wp_nonce_field('cmb_import', 'cmb_import_nonce');
        echo '<input type="hidden" name="cmb_action" value="import">';
        echo '<div class="cmb-import-section">';
        echo '<label class="cmb-import-file-label">';
        echo '<span class="dashicons dashicons-media-default"></span>';
        echo '<span>Choose a JSON file or drag it here</span>';
        echo '<input type="file" name="cmb_import_file" accept=".json" class="cmb-import-file-input">';
        echo '</label>';
        echo '</div>';
        echo '<div class="cmb-import-divider"><span>or</span></div>';
        echo '<div class="cmb-import-section">';
        echo '<label>Paste JSON</label>';
        echo '<textarea name="cmb_import_json" rows="8" class="widefat code" placeholder=\'{"version":"1.0","meta_boxes":{...}}\'></textarea>';
        echo '</div>';
        echo '<div class="cmb-import-actions">';
        echo '<button type="submit" class="button button-primary">Import</button>';
        echo '<button type="button" class="button cmb-import-cancel">Cancel</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
    }
}
