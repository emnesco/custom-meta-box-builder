<?php
namespace CMB\Core;

/**
 * Gutenberg sidebar panel support (7.6).
 *
 * Registers meta box fields as a Gutenberg sidebar panel using
 * the WordPress block editor's PluginDocumentSettingPanel.
 */
class GutenbergPanel {
    public static function register(): void {
        add_action('enqueue_block_editor_assets', [self::class, 'enqueueEditorAssets']);
    }

    public static function enqueueEditorAssets(): void {
        $manager = MetaBoxManager::instance();
        $boxes = $manager->getMetaBoxes();

        $sidebarBoxes = [];
        foreach ($boxes as $id => $box) {
            // Only include boxes configured for Gutenberg sidebar
            if (empty($box['gutenberg_panel'])) {
                continue;
            }

            $currentPostType = get_post_type();
            if ($currentPostType && !in_array($currentPostType, $box['postTypes'], true)) {
                continue;
            }

            $fields = [];
            $rawFields = $box['fields'];
            if (!empty($rawFields['tabs'])) {
                foreach ($rawFields['tabs'] as $tab) {
                    foreach ($tab['fields'] ?? [] as $f) {
                        $fields[] = self::fieldToJsConfig($f);
                    }
                }
            } else {
                foreach ($rawFields as $f) {
                    $fields[] = self::fieldToJsConfig($f);
                }
            }

            $sidebarBoxes[] = [
                'id' => $id,
                'title' => $box['title'],
                'fields' => $fields,
            ];
        }

        if (empty($sidebarBoxes)) {
            return;
        }

        wp_enqueue_script(
            'cmb-gutenberg-panel',
            plugin_dir_url(dirname(__DIR__, 1) . '/../custom-meta-box-builder.php') . 'assets/cmb-gutenberg.js',
            ['wp-plugins', 'wp-edit-post', 'wp-components', 'wp-data', 'wp-element'],
            null,
            true
        );

        wp_localize_script('cmb-gutenberg-panel', 'cmbGutenbergPanels', $sidebarBoxes);
    }

    private static function fieldToJsConfig(array $field): array {
        return [
            'id' => $field['id'],
            'type' => $field['type'] ?? 'text',
            'label' => $field['label'] ?? '',
            'description' => $field['description'] ?? '',
            'options' => $field['options'] ?? [],
            'default' => $field['default'] ?? '',
            'required' => !empty($field['required']),
        ];
    }
}
