<?php
namespace CMB\Core;

/**
 * Import/Export meta box configurations as JSON (8.1).
 */
class ImportExport {
    public static function register(): void {
        add_action('admin_menu', [self::class, 'addAdminPage']);
        add_action('admin_init', [self::class, 'handleImport']);
        add_action('admin_init', [self::class, 'handleExport']);
    }

    public static function addAdminPage(): void {
        add_submenu_page(
            'tools.php',
            'CMB Import/Export',
            'CMB Import/Export',
            'manage_options',
            'cmb-import-export',
            [self::class, 'renderPage']
        );
    }

    public static function renderPage(): void {
        echo '<div class="wrap">';
        echo '<h1>CMB Import/Export</h1>';

        // Export section
        echo '<h2>Export</h2>';
        echo '<p>Export all registered meta box configurations as JSON.</p>';
        echo '<form method="post">';
        wp_nonce_field('cmb_export', 'cmb_export_nonce');
        echo '<input type="hidden" name="cmb_action" value="export">';
        echo '<p><button type="submit" class="button button-primary">Export Configuration</button></p>';
        echo '</form>';

        // Import section
        echo '<h2>Import</h2>';
        echo '<p>Import meta box configurations from a JSON file or paste JSON below.</p>';
        echo '<form method="post" enctype="multipart/form-data">';
        wp_nonce_field('cmb_import', 'cmb_import_nonce');
        echo '<input type="hidden" name="cmb_action" value="import">';
        echo '<p><label>Upload JSON file:<br><input type="file" name="cmb_import_file" accept=".json"></label></p>';
        echo '<p><label>Or paste JSON:<br><textarea name="cmb_import_json" rows="10" cols="80" class="large-text code"></textarea></label></p>';
        echo '<p><button type="submit" class="button button-primary">Import Configuration</button></p>';
        echo '</form>';

        echo '</div>';
    }

    public static function handleExport(): void {
        if (empty($_POST['cmb_action']) || $_POST['cmb_action'] !== 'export') {
            return;
        }
        if (!isset($_POST['cmb_export_nonce']) || !wp_verify_nonce($_POST['cmb_export_nonce'], 'cmb_export')) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $manager = MetaBoxManager::instance();
        $boxes = $manager->getMetaBoxes();

        $export = [
            'version' => '1.0',
            'plugin' => 'custom-meta-box-builder',
            'exported_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'meta_boxes' => $boxes,
        ];

        $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="cmb-config-' . gmdate('Y-m-d') . '.json"');
        header('Content-Length: ' . strlen($json));
        echo $json;
        exit;
    }

    public static function handleImport(): void {
        if (empty($_POST['cmb_action']) || $_POST['cmb_action'] !== 'import') {
            return;
        }
        if (!isset($_POST['cmb_import_nonce']) || !wp_verify_nonce($_POST['cmb_import_nonce'], 'cmb_import')) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $json = '';

        // Try file upload first
        if (!empty($_FILES['cmb_import_file']['tmp_name'])) {
            $json = file_get_contents($_FILES['cmb_import_file']['tmp_name']);
        } elseif (!empty($_POST['cmb_import_json'])) {
            $json = wp_unslash($_POST['cmb_import_json']);
        }

        if (empty($json)) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>No JSON data provided.</p></div>';
            });
            return;
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data['meta_boxes'])) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>Invalid JSON or missing meta_boxes key.</p></div>';
            });
            return;
        }

        $manager = MetaBoxManager::instance();
        $count = 0;
        foreach ($data['meta_boxes'] as $id => $box) {
            if (empty($box['title']) || empty($box['postTypes']) || empty($box['fields'])) {
                continue;
            }
            $manager->add(
                $id,
                $box['title'],
                $box['postTypes'],
                $box['fields'],
                $box['context'] ?? 'advanced',
                $box['priority'] ?? 'default'
            );
            $count++;
        }

        add_action('admin_notices', function () use ($count) {
            echo '<div class="notice notice-success"><p>Imported ' . $count . ' meta box(es).</p></div>';
        });
    }

    /**
     * Export configuration programmatically (for API use).
     */
    public static function exportToJson(): string {
        $manager = MetaBoxManager::instance();
        $boxes = $manager->getMetaBoxes();

        return json_encode([
            'version' => '1.0',
            'plugin' => 'custom-meta-box-builder',
            'exported_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'meta_boxes' => $boxes,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Import configuration programmatically (for API use).
     */
    public static function importFromJson(string $json): int {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data['meta_boxes'])) {
            return 0;
        }

        $manager = MetaBoxManager::instance();
        $count = 0;
        foreach ($data['meta_boxes'] as $id => $box) {
            if (empty($box['title']) || empty($box['postTypes']) || empty($box['fields'])) {
                continue;
            }
            $manager->add($id, $box['title'], $box['postTypes'], $box['fields'], $box['context'] ?? 'advanced', $box['priority'] ?? 'default');
            $count++;
        }
        return $count;
    }
}
