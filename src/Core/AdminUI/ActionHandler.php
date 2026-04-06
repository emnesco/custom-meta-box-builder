<?php
declare(strict_types=1);

/**
 * Admin CRUD handler — saves, deletes, imports, and exports configurations.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\AdminUI;

defined( 'ABSPATH' ) || exit;

use CMB\Core\MetaBoxManager;

class ActionHandler {
    private const OPTION_KEY = 'cmb_admin_configurations';
    private static ?array $configCache = null;

    public static function getConfigs(): array {
        if (null === self::$configCache) {
            // PERF-L01: Check object cache before hitting the database.
            $cached = wp_cache_get(self::OPTION_KEY, 'cmb_config');
            if (false !== $cached) {
                self::$configCache = $cached;
            } else {
                // WPS-M01: Check transient cache for expensive config loading.
                $transient = get_transient('cmb_admin_configs');
                if (false !== $transient) {
                    self::$configCache = $transient;
                } else {
                    self::$configCache = get_option(self::OPTION_KEY, []);
                    set_transient('cmb_admin_configs', self::$configCache, 60);
                }
                wp_cache_set(self::OPTION_KEY, self::$configCache, 'cmb_config');
            }
        }
        return self::$configCache;
    }

    public static function saveConfigs(array $configs): void {
        // Use add_option on first save to ensure autoload is false.
        if (false === get_option(self::OPTION_KEY)) {
            add_option(self::OPTION_KEY, $configs, '', false);
        } else {
            update_option(self::OPTION_KEY, $configs, false);
        }
        self::$configCache = $configs;
        // PERF-L01: Update the object cache and clear transient on save.
        wp_cache_set(self::OPTION_KEY, $configs, 'cmb_config');
        delete_transient('cmb_admin_configs');
    }

    /**
     * Handle save of a field group configuration.
     *
     * Orchestrates validation, assembly, and persistence of the config.
     */
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

        $validated = self::validateConfig();
        if (null === $validated) {
            return;
        }

        $config = self::assembleConfig($validated);
        self::persistConfig($config);
    }

    /**
     * Validate and extract raw config values from the request.
     *
     * @return array|null Validated values or null if validation failed.
     */
    private static function validateConfig(): ?array {
        $editing   = sanitize_text_field( wp_unslash( $_POST['cmb_editing'] ?? '' ) );
        $id        = $editing ?: sanitize_text_field( wp_unslash( $_POST['cmb_box_id'] ?? '' ) );
        $title     = sanitize_text_field( wp_unslash( $_POST['cmb_box_title'] ?? '' ) );

        // Auto-generate ID from title if empty
        if (empty($id) && !empty($title)) {
            $id = sanitize_title($title);
            $id = str_replace('-', '_', $id);
        }

        if (empty($id) || empty($title)) {
            return null;
        }

        return [
            'id'        => $id,
            'title'     => $title,
            'postTypes' => array_map('sanitize_text_field', wp_unslash( $_POST['cmb_box_post_types'] ?? ['post'] ) ),
            'context'   => sanitize_text_field( wp_unslash( $_POST['cmb_box_context'] ?? 'advanced' ) ),
            'priority'  => sanitize_text_field( wp_unslash( $_POST['cmb_box_priority'] ?? 'default' ) ),
            'active'    => !empty($_POST['cmb_active']),
            'showRest'  => !empty($_POST['cmb_show_in_rest']),
        ];
    }

    /**
     * Assemble the full configuration array including processed fields.
     *
     * @param array $validated The validated config values from validateConfig().
     * @return array The assembled configuration.
     */
    private static function assembleConfig(array $validated): array {
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
                    'layout'      => sanitize_text_field($f['layout'] ?? ''),
                    'repeatable'  => !empty($f['repeatable']),
                    'collapsed'   => !empty($f['collapsed']),
                    'min_rows'    => intval($f['min_rows'] ?? 0) ?: '',
                    'max_rows'    => intval($f['max_rows'] ?? 0) ?: '',
                    'row_title_field' => sanitize_text_field($f['row_title_field'] ?? ''),
                    'searchable'  => !empty($f['searchable']),
                    'conditional_field'    => sanitize_text_field($f['conditional_field'] ?? ''),
                    'conditional_operator' => sanitize_text_field($f['conditional_operator'] ?? ''),
                    'conditional_value'    => sanitize_text_field($f['conditional_value'] ?? ''),
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

                // Process sub-fields for group type (recursive for infinite nesting)
                if ($field['type'] === 'group' && !empty($f['sub_fields']) && is_array($f['sub_fields'])) {
                    $field['sub_fields'] = self::processSubFields($f['sub_fields']);
                }

                // Clean up empty values
                $field = array_filter($field, function($v) {
                    return '' !== $v && null !== $v;
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

        return [
            'id'           => $validated['id'],
            'title'        => $validated['title'],
            'postTypes'    => $validated['postTypes'],
            'fields'       => $fields,
            'context'      => $validated['context'],
            'priority'     => $validated['priority'],
            'active'       => $validated['active'],
            'show_in_rest' => $validated['showRest'],
            '_modified'    => time(),
        ];
    }

    /**
     * Persist the assembled config to the database and redirect.
     *
     * @param array $config The assembled configuration.
     */
    private static function persistConfig(array $config): void {
        $id = $config['id'];
        $configs = self::getConfigs();
        $configs[$id] = $config;
        self::saveConfigs($configs);

        /** @since 2.1 Fires after a field group config is saved. */
        do_action('cmbbuilder_config_saved', $id, $config);

        wp_safe_redirect(admin_url('admin.php?page=cmb-builder&action=edit&id=' . urlencode($id) . '&updated=1'));
        exit;
    }

    /**
     * Delegate to BulkActionHandler::handleDelete().
     */
    public static function handleDelete(): void {
        BulkActionHandler::handleDelete();
    }

    public static function handleDuplicate(): void {
        BulkActionHandler::handleDuplicate();
    }

    public static function handleToggle(): void {
        BulkActionHandler::handleToggle();
    }

    public static function handleExport(): void {
        ImportExportHandler::handleExport();
    }

    public static function handleExportPhp(): void {
        ImportExportHandler::handleExportPhp();
    }

    public static function handleImport(): void {
        ImportExportHandler::handleImport();
    }

    /**
     * SEC-L05: Recursively strip _modified keys from data before export.
     */
    private static function stripModifiedKeys(array $data): array {
        unset($data['_modified']);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::stripModifiedKeys($value);
            }
        }
        return $data;
    }

    public static function registerSavedBoxes(): void {
        // Skip loading on non-relevant screens (e.g. front-end when not needed).
        if (is_admin() && function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen && !in_array($screen->base, ['post', 'edit', 'add'], true) && $screen->base !== 'admin_page_cmb-builder') {
                return;
            }
        }

        $configs = self::getConfigs();
        if (empty($configs)) {
            return;
        }

        $manager = MetaBoxManager::getInstance();
        if (null === $manager) {
            return;
        }
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

    /**
     * Recursively process sub-fields from form submission data.
     */
    private static function processSubFields(array $rawSubFields): array {
        $subFields = [];
        foreach ($rawSubFields as $sf) {
            if (empty($sf['id'])) continue;
            $sub = [
                'id'          => sanitize_text_field($sf['id']),
                'type'        => sanitize_text_field($sf['type'] ?? 'text'),
                'label'       => sanitize_text_field($sf['label'] ?? ''),
                'description' => sanitize_text_field($sf['description'] ?? ''),
                'required'    => !empty($sf['required']),
                'placeholder' => sanitize_text_field($sf['placeholder'] ?? ''),
                'default_value' => sanitize_text_field($sf['default_value'] ?? ''),
                'width'       => sanitize_text_field($sf['width'] ?? ''),
                'layout'      => sanitize_text_field($sf['layout'] ?? ''),
                'conditional_field'    => sanitize_text_field($sf['conditional_field'] ?? ''),
                'conditional_operator' => sanitize_text_field($sf['conditional_operator'] ?? ''),
                'conditional_value'    => sanitize_text_field($sf['conditional_value'] ?? ''),
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
            // Recursively process nested sub-fields for group type
            if ($sub['type'] === 'group' && !empty($sf['sub_fields']) && is_array($sf['sub_fields'])) {
                $sub['sub_fields'] = self::processSubFields($sf['sub_fields']);
            }
            $sub = array_filter($sub, function($v) {
                return '' !== $v && null !== $v && false !== $v;
            });
            $sub['id'] = sanitize_text_field($sf['id']);
            $sub['type'] = sanitize_text_field($sf['type'] ?? 'text');
            // Preserve sub_fields after array_filter
            if (!empty($sf['sub_fields']) && $sub['type'] === 'group') {
                $sub['sub_fields'] = $sub['sub_fields'] ?? self::processSubFields($sf['sub_fields']);
            }
            $subFields[] = $sub;
        }
        return $subFields;
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
            if (isset($field['min']) && $field['min'] !== '') $f['min'] = $field['min'];
            if (isset($field['max']) && $field['max'] !== '') $f['max'] = $field['max'];
            if (!empty($field['step']))         $f['step']        = $field['step'];
            if (!empty($field['post_type']))    $f['post_type']   = $field['post_type'];
            if (!empty($field['taxonomy']))     $f['taxonomy']    = $field['taxonomy'];
            if (!empty($field['role']))         $f['role']        = $field['role'];
            if (!empty($field['repeatable']))   $f['repeat']      = true;
            if (!empty($field['layout']))       $f['layout']      = $field['layout'];
            if (isset($field['collapsed']))     $f['collapsed']   = (bool) $field['collapsed'];
            if (!empty($field['min_rows']))     $f['min_rows']    = (int) $field['min_rows'];
            if (!empty($field['max_rows']))     $f['max_rows']    = (int) $field['max_rows'];
            if (!empty($field['row_title_field'])) $f['row_title_field'] = $field['row_title_field'];
            if (!empty($field['searchable']))   $f['searchable']  = true;

            // Build conditional config from saved fields
            if (!empty($field['conditional_field'])) {
                $f['conditional'] = [
                    'field'    => $field['conditional_field'],
                    'operator' => $field['conditional_operator'] ?? '==',
                    'value'    => $field['conditional_value'] ?? '',
                ];
            }

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
