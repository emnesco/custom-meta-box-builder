<?php
namespace CMB\Core;

/**
 * Gutenberg sidebar panel support (7.6).
 *
 * Registers meta box fields as a Gutenberg sidebar panel using
 * the WordPress block editor's PluginDocumentSettingPanel.
 */
class GutenbergPanel {
    private MetaBoxManager $manager;

    public function __construct( MetaBoxManager $manager ) {
        $this->manager = $manager;
    }

    public function register(): void {
        add_action('enqueue_block_editor_assets', [$this, 'enqueueEditorAssets']);
    }

    public function enqueueEditorAssets(): void {
        $boxes = $this->manager->getMetaBoxes();

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
                        $fields[] = $this->fieldToJsConfig($f);
                    }
                }
            } else {
                foreach ($rawFields as $f) {
                    $fields[] = $this->fieldToJsConfig($f);
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

        $baseUrl  = plugin_dir_url(dirname(__DIR__, 1) . '/../custom-meta-box-builder.php');
        $basePath = plugin_dir_path(dirname(__DIR__, 1) . '/../custom-meta-box-builder.php');
        $suffix   = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
        $jsFile   = 'assets/cmb-gutenberg' . $suffix . '.js';
        if ( $suffix && ! file_exists( $basePath . $jsFile ) ) {
            $jsFile = 'assets/cmb-gutenberg.js';
        }
        wp_enqueue_script(
            'cmb-gutenberg-panel',
            $baseUrl . $jsFile,
            ['wp-plugins', 'wp-editor', 'wp-edit-post', 'wp-components', 'wp-data', 'wp-element'],
            @filemtime( $basePath . $jsFile ) ?: null,
            true
        );

        wp_localize_script('cmb-gutenberg-panel', 'cmbGutenbergPanels', $sidebarBoxes);
    }

    private function fieldToJsConfig(array $field): array {
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
