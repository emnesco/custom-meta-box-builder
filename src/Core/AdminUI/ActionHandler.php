<?php
/**
 * Admin CRUD handler — saves, deletes, imports, and exports configurations.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Core\AdminUI;

use CMB\Core\MetaBoxManager;

class ActionHandler {
    private const OPTION_KEY = 'cmb_admin_configurations';
    private static ?array $configCache = null;

    public static function getConfigs(): array {
        if (self::$configCache === null) {
            self::$configCache = get_option(self::OPTION_KEY, []);
        }
        return self::$configCache;
    }

    public static function saveConfigs(array $configs): void {
        update_option(self::OPTION_KEY, $configs, false);
        self::$configCache = $configs;
    }

    public static function handleSave(): void {
        if (empty($_POST['cmb_builder_submit'])) {
            return;
        }
        if (!isset($_POST['cmb_builder_nonce']) || !wp_verify_nonce($_POST['cmb_builder_nonce'], 'cmb_builder_save')) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $editing   = sanitize_text_field( wp_unslash( $_POST['cmb_editing'] ?? '' ) );
        $id        = $editing ?: sanitize_text_field( wp_unslash( $_POST['cmb_box_id'] ?? '' ) );
        $title     = sanitize_text_field( wp_unslash( $_POST['cmb_box_title'] ?? '' ) );
        $postTypes = array_map('sanitize_text_field', wp_unslash( $_POST['cmb_box_post_types'] ?? ['post'] ) );
        $context   = sanitize_text_field( wp_unslash( $_POST['cmb_box_context'] ?? 'advanced' ) );
        $priority  = sanitize_text_field( wp_unslash( $_POST['cmb_box_priority'] ?? 'default' ) );
        $active    = !empty($_POST['cmb_active']);
        $showRest  = !empty($_POST['cmb_show_in_rest']);

        // Auto-generate ID from title if empty
        if (empty($id) && !empty($title)) {
            $id = sanitize_title($title);
            $id = str_replace('-', '_', $id);
        }

        if (empty($id) || empty($title)) {
            return;
        }

        $fields = [];
        $rawFields = wp_unslash( $_POST['cmb_fields'] ?? [] );
        if (!empty($rawFields) && is_array($rawFields)) {
            foreach ($rawFields as $f) {
                if (empty($f['id'])) {
                    continue;
                }
                $field = [
                    'id'          => sanitize_text_field($f['id']),
                    'type'        => sanitize_text_field($f['type'] ?? 'text'),
                    'label'       => sanitize_text_field($f['label'] ?? ''),
                    'description' => sanitize_text_field($f['description'] ?? ''),
                    'required'    => !empty($f['required']),
                    'placeholder' => sanitize_text_field($f['placeholder'] ?? ''),
                    'default_value' => sanitize_text_field($f['default_value'] ?? ($f['default_value_dc'] ?? '')),
                    'rows'        => intval($f['rows'] ?? 0) ?: '',
                    'min'         => sanitize_text_field($f['min'] ?? ''),
                    'max'         => sanitize_text_field($f['max'] ?? ''),
                    'step'        => sanitize_text_field($f['step'] ?? ''),
                    'post_type'   => sanitize_text_field($f['post_type'] ?? ''),
                    'taxonomy'    => sanitize_text_field($f['taxonomy'] ?? ''),
                    'role'        => sanitize_text_field($f['role'] ?? ''),
                    'width'       => sanitize_text_field($f['width'] ?? '100'),
                    'repeatable'  => !empty($f['repeatable']),
                    'collapsed'   => !empty($f['collapsed']),
                    'min_rows'    => intval($f['min_rows'] ?? 0) ?: '',
                    'max_rows'    => intval($f['max_rows'] ?? 0) ?: '',
                ];

                // Parse options textarea into associative array
                if (!empty($f['options']) && in_array($field['type'], ['select', 'radio'], true)) {
                    $optLines = explode("\n", trim($f['options']));
                    $opts = [];
                    foreach ($optLines as $line) {
                        $line = trim($line);
                        if ($line === '') continue;
                        if (strpos($line, '|') !== false) {
                            [$val, $lbl] = explode('|', $line, 2);
                            $opts[sanitize_text_field(trim($val))] = sanitize_text_field(trim($lbl));
                        } else {
                            $opts[sanitize_title($line)] = sanitize_text_field($line);
                        }
                    }
                    $field['options'] = $opts;
                }

                // Process sub-fields for group type
                if ($field['type'] === 'group' && !empty($f['sub_fields']) && is_array($f['sub_fields'])) {
                    $subFields = [];
                    foreach ($f['sub_fields'] as $sf) {
                        if (empty($sf['id'])) continue;
                        $sub = [
                            'id'          => sanitize_text_field($sf['id']),
                            'type'        => sanitize_text_field($sf['type'] ?? 'text'),
                            'label'       => sanitize_text_field($sf['label'] ?? ''),
                            'description' => sanitize_text_field($sf['description'] ?? ''),
                            'required'    => !empty($sf['required']),
                            'placeholder' => sanitize_text_field($sf['placeholder'] ?? ''),
                            'default_value' => sanitize_text_field($sf['default_value'] ?? ''),
                        ];
                        // Parse sub-field options
                        if (!empty($sf['options']) && in_array($sub['type'], ['select', 'radio'], true)) {
                            $optLines = explode("\n", trim($sf['options']));
                            $sOpts = [];
                            foreach ($optLines as $line) {
                                $line = trim($line);
                                if ($line === '') continue;
                                if (strpos($line, '|') !== false) {
                                    [$val, $lbl] = explode('|', $line, 2);
                                    $sOpts[sanitize_text_field(trim($val))] = sanitize_text_field(trim($lbl));
                                } else {
                                    $sOpts[sanitize_title($line)] = sanitize_text_field($line);
                                }
                            }
                            $sub['options'] = $sOpts;
                        }
                        $sub = array_filter($sub, function($v) {
                            return $v !== '' && $v !== null && $v !== false;
                        });
                        $sub['id'] = sanitize_text_field($sf['id']);
                        $sub['type'] = sanitize_text_field($sf['type'] ?? 'text');
                        $subFields[] = $sub;
                    }
                    if (!empty($subFields)) {
                        $field['sub_fields'] = $subFields;
                    }
                }

                // Clean up empty values
                $field = array_filter($field, function($v) {
                    return $v !== '' && $v !== null;
                });
                // Always keep id and type
                $field['id'] = sanitize_text_field($f['id']);
                $field['type'] = sanitize_text_field($f['type'] ?? 'text');
                // Preserve sub_fields array (array_filter may remove empty arrays but not populated ones)
                if (!empty($f['sub_fields']) && $field['type'] === 'group') {
                    $field['sub_fields'] = $field['sub_fields'] ?? [];
                }

                $fields[] = $field;
            }
        }

        $configs = self::getConfigs();
        $config = [
            'id'           => $id,
            'title'        => $title,
            'postTypes'    => $postTypes,
            'fields'       => $fields,
            'context'      => $context,
            'priority'     => $priority,
            'active'       => $active,
            'show_in_rest' => $showRest,
            '_modified'    => time(),
        ];
        $configs[$id] = $config;
        self::saveConfigs($configs);

        /** @since 2.1 Fires after a field group config is saved. */
        do_action('cmbbuilder_config_saved', $id, $config);

        wp_safe_redirect(admin_url('admin.php?page=cmb-builder&action=edit&id=' . urlencode($id) . '&updated=1'));
        exit;
    }

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

        $configs = self::getConfigs();
        unset($configs[$id]);
        self::saveConfigs($configs);

        /** @since 2.1 Fires after a field group config is deleted. */
        do_action('cmbbuilder_config_deleted', $id);

        wp_safe_redirect(admin_url('admin.php?page=cmb-builder&deleted=1'));
        exit;
    }

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

        $configs = self::getConfigs();
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
        $configs[$newId]['title'] .= ' (Copy)';
        self::saveConfigs($configs);

        wp_safe_redirect(admin_url('admin.php?page=cmb-builder&duplicated=1'));
        exit;
    }

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

        $configs = self::getConfigs();
        if (!isset($configs[$id])) {
            return;
        }

        $configs[$id]['active'] = !($configs[$id]['active'] ?? true);
        self::saveConfigs($configs);

        wp_safe_redirect(admin_url('admin.php?page=cmb-builder&toggled=1'));
        exit;
    }

    public static function handleExport(): void {
        if (empty($_GET['cmb_export']) || empty($_GET['page']) || $_GET['page'] !== 'cmb-builder') {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $exportId = sanitize_text_field($_GET['cmb_export']);
        $configs  = get_option(self::OPTION_KEY, []);

        if ($exportId === 'all') {
            if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'cmb_export_all')) {
                return;
            }
            $data = $configs;
            $filename = 'cmb-all-field-groups-' . gmdate('Y-m-d') . '.json';
        } else {
            if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'cmb_export_' . $exportId)) {
                return;
            }
            if (!isset($configs[$exportId])) {
                return;
            }
            $data = [$exportId => $configs[$exportId]];
            $filename = 'cmb-' . $exportId . '-' . gmdate('Y-m-d') . '.json';
        }

        $export = [
            'version'      => '2.0',
            'plugin'       => 'custom-meta-box-builder',
            'exported_at'  => gmdate('Y-m-d\TH:i:s\Z'),
            'field_groups' => $data,
        ];

        $json = wp_json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        echo $json;
        exit;
    }

    public static function handleExportPhp(): void {
        if (empty($_GET['cmb_export_php']) || empty($_GET['page']) || $_GET['page'] !== 'cmb-builder') {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $exportId = sanitize_text_field($_GET['cmb_export_php']);
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'cmb_export_php_' . $exportId)) {
            return;
        }

        $configs = self::getConfigs();
        if (!isset($configs[$exportId])) {
            return;
        }

        $box = $configs[$exportId];
        $fields = var_export($box['fields'] ?? [], true);
        $postTypes = var_export($box['postTypes'] ?? ['post'], true);

        $php = "<?php\n";
        $php .= "// Generated by Custom Meta Box Builder on " . gmdate('Y-m-d') . "\n\n";
        $php .= "add_custom_meta_box(\n";
        $php .= "    " . var_export($exportId, true) . ",\n";
        $php .= "    " . var_export($box['title'] ?? '', true) . ",\n";
        $php .= "    " . $postTypes . ",\n";
        $php .= "    " . $fields . ",\n";
        $php .= "    " . var_export($box['context'] ?? 'advanced', true) . ",\n";
        $php .= "    " . var_export($box['priority'] ?? 'default', true) . "\n";
        $php .= ");\n";

        $filename = 'cmb-' . $exportId . '.php';
        header('Content-Type: application/x-php');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($php));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        echo $php;
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
        if (!empty($_FILES['cmb_import_file']['tmp_name'])) {
            $fileSize = $_FILES['cmb_import_file']['size'] ?? 0;
            if ($fileSize > 1024 * 1024) {
                wp_safe_redirect(admin_url('admin.php?page=cmb-builder&error=import_failed'));
                exit;
            }
            $json = file_get_contents($_FILES['cmb_import_file']['tmp_name']);
        } elseif (!empty($_POST['cmb_import_json'])) {
            $json = wp_unslash($_POST['cmb_import_json']);
            if (strlen($json) > 1024 * 1024) {
                wp_safe_redirect(admin_url('admin.php?page=cmb-builder&error=import_failed'));
                exit;
            }
        }

        if (empty($json)) {
            wp_safe_redirect(admin_url('admin.php?page=cmb-builder&error=no_data'));
            exit;
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_safe_redirect(admin_url('admin.php?page=cmb-builder&error=invalid_json'));
            exit;
        }

        // Support both v1 (meta_boxes) and v2 (field_groups) format
        $groups = $data['field_groups'] ?? $data['meta_boxes'] ?? null;
        if (empty($groups) || !is_array($groups)) {
            wp_safe_redirect(admin_url('admin.php?page=cmb-builder&error=import_failed'));
            exit;
        }

        $configs = self::getConfigs();
        $count   = 0;

        foreach ($groups as $id => $box) {
            if (empty($box['title'])) {
                continue;
            }
            $configs[$id] = [
                'title'        => sanitize_text_field($box['title']),
                'postTypes'    => array_map('sanitize_text_field', $box['postTypes'] ?? ['post']),
                'fields'       => self::sanitizeImportedFields($box['fields'] ?? []),
                'context'      => sanitize_text_field($box['context'] ?? 'advanced'),
                'priority'     => sanitize_text_field($box['priority'] ?? 'default'),
                'active'       => $box['active'] ?? true,
                'show_in_rest' => $box['show_in_rest'] ?? false,
            ];
            $count++;
        }

        self::saveConfigs($configs);

        wp_safe_redirect(admin_url('admin.php?page=cmb-builder&imported=' . $count));
        exit;
    }

    private static function sanitizeImportedFields(array $fields): array {
        $sanitized = [];
        foreach ($fields as $field) {
            if (!is_array($field) || empty($field['id'])) {
                continue;
            }
            $clean = [
                'id'            => sanitize_text_field($field['id']),
                'type'          => sanitize_text_field($field['type'] ?? 'text'),
                'label'         => sanitize_text_field($field['label'] ?? ''),
                'description'   => sanitize_text_field($field['description'] ?? ''),
                'required'      => !empty($field['required']),
                'placeholder'   => sanitize_text_field($field['placeholder'] ?? ''),
                'default_value' => sanitize_text_field($field['default_value'] ?? ''),
                'width'         => sanitize_text_field($field['width'] ?? ''),
            ];

            // Numeric fields
            if (isset($field['rows']))     $clean['rows']     = intval($field['rows']);
            if (isset($field['min']))      $clean['min']      = sanitize_text_field((string)($field['min'] ?? ''));
            if (isset($field['max']))      $clean['max']      = sanitize_text_field((string)($field['max'] ?? ''));
            if (isset($field['step']))     $clean['step']     = sanitize_text_field((string)($field['step'] ?? ''));
            if (isset($field['min_rows'])) $clean['min_rows'] = intval($field['min_rows']);
            if (isset($field['max_rows'])) $clean['max_rows'] = intval($field['max_rows']);

            // Relational fields
            if (!empty($field['post_type'])) $clean['post_type'] = sanitize_text_field($field['post_type']);
            if (!empty($field['taxonomy']))  $clean['taxonomy']  = sanitize_text_field($field['taxonomy']);
            if (!empty($field['role']))      $clean['role']      = sanitize_text_field($field['role']);

            // Boolean flags
            if (!empty($field['repeatable'])) $clean['repeatable'] = true;
            if (isset($field['collapsed']))   $clean['collapsed']  = (bool)$field['collapsed'];

            // Options (select/radio)
            if (!empty($field['options']) && is_array($field['options'])) {
                $opts = [];
                foreach ($field['options'] as $k => $v) {
                    $opts[sanitize_text_field((string)$k)] = sanitize_text_field((string)$v);
                }
                $clean['options'] = $opts;
            }

            // Recursively sanitize sub-fields for groups
            if (!empty($field['sub_fields']) && is_array($field['sub_fields'])) {
                $clean['sub_fields'] = self::sanitizeImportedFields($field['sub_fields']);
            }
            if (!empty($field['fields']) && is_array($field['fields'])) {
                $clean['fields'] = self::sanitizeImportedFields($field['fields']);
            }

            // Remove empty values to keep storage clean
            $clean = array_filter($clean, function ($v) {
                return $v !== '' && $v !== null && $v !== 0;
            });
            // Always keep id and type
            $clean['id']   = sanitize_text_field($field['id']);
            $clean['type'] = sanitize_text_field($field['type'] ?? 'text');

            $sanitized[] = $clean;
        }
        return $sanitized;
    }

    public static function registerSavedBoxes(): void {
        $configs = self::getConfigs();
        if (empty($configs)) {
            return;
        }

        $manager = MetaBoxManager::instance();
        foreach ($configs as $id => $box) {
            // Skip inactive groups
            if (isset($box['active']) && !$box['active']) {
                continue;
            }

            $fields = self::transformFieldsForRegistration($box['fields'] ?? []);

            $manager->add(
                $id,
                $box['title'],
                $box['postTypes'],
                $fields,
                $box['context'] ?? 'advanced',
                $box['priority'] ?? 'default'
            );
        }
    }

    private static function transformFieldsForRegistration(array $fields): array {
        $transformed = [];
        foreach ($fields as $field) {
            $f = [
                'id'   => $field['id'],
                'type' => $field['type'],
            ];

            if (!empty($field['label']))       $f['label']       = $field['label'];
            if (!empty($field['description'])) $f['description'] = $field['description'];
            if (!empty($field['required']))    $f['required']    = true;
            if (!empty($field['placeholder'])) $f['placeholder'] = $field['placeholder'];
            if (!empty($field['default_value'])) $f['default']   = $field['default_value'];
            if (!empty($field['options']))      $f['options']     = $field['options'];
            if (!empty($field['rows']))         $f['rows']        = (int) $field['rows'];
            if ($field['min'] !== '' && isset($field['min'])) $f['min'] = $field['min'];
            if ($field['max'] !== '' && isset($field['max'])) $f['max'] = $field['max'];
            if (!empty($field['step']))         $f['step']        = $field['step'];
            if (!empty($field['post_type']))    $f['post_type']   = $field['post_type'];
            if (!empty($field['taxonomy']))     $f['taxonomy']    = $field['taxonomy'];
            if (!empty($field['role']))         $f['role']        = $field['role'];
            if (!empty($field['repeatable']))   $f['repeat']      = true;
            if (isset($field['collapsed']))     $f['collapsed']   = (bool) $field['collapsed'];
            if (!empty($field['min_rows']))     $f['min_rows']    = (int) $field['min_rows'];
            if (!empty($field['max_rows']))     $f['max_rows']    = (int) $field['max_rows'];

            // Map sub_fields to 'fields' key expected by GroupField, and set repeat
            if ($field['type'] === 'group' && !empty($field['sub_fields'])) {
                $f['fields'] = self::transformFieldsForRegistration($field['sub_fields']);
                $f['repeat'] = true; // Groups are always repeatable
            }

            $transformed[] = $f;
        }
        return $transformed;
    }
}
