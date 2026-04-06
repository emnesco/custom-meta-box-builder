<?php
declare(strict_types=1);

/**
 * Dependency resolution and topological sorting for field configurations.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Field dependency graph visualization for developer tools (8.6).
 *
 * Generates a visual representation of field dependencies (conditional logic)
 * to help developers debug and understand field relationships.
 */
class DependencyGraph {
    private MetaBoxManager $manager;

    public function __construct( MetaBoxManager $manager ) {
        $this->manager = $manager;
    }

    public function register(): void {
        add_action('admin_menu', [$this, 'addAdminPage']);
    }

    public function addAdminPage(): void {
        add_submenu_page(
            'tools.php',
            __('CMB Dependency Graph', 'custom-meta-box-builder'),
            __('CMB Field Graph', 'custom-meta-box-builder'),
            'manage_options',
            'cmb-dependency-graph',
            [$this, 'renderPage']
        );
    }

    public function renderPage(): void {
        $boxes = $this->manager->getMetaBoxes();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('CMB Field Dependency Graph', 'custom-meta-box-builder') . '</h1>';
        echo '<p>' . esc_html__('Visual representation of field dependencies (conditional logic) across all meta boxes.', 'custom-meta-box-builder') . '</p>';

        if (empty($boxes)) {
            echo '<p>' . esc_html__('No meta boxes registered.', 'custom-meta-box-builder') . '</p></div>';
            return;
        }

        foreach ($boxes as $id => $box) {
            echo '<h2>' . esc_html($box['title']) . ' <code>' . esc_html($id) . '</code></h2>';

            $fields = FieldUtils::flattenFields($box['fields']);
            $dependencies = $this->extractDependencies($fields);

            if (empty($dependencies)) {
                echo '<p style="color:#999">' . esc_html__('No conditional dependencies in this meta box.', 'custom-meta-box-builder') . '</p>';
                echo $this->renderFieldList($fields);
                continue;
            }

            echo '<div class="cmb-graph-container" style="background:#f9f9f9;border:1px solid #ddd;padding:20px;margin-bottom:20px;overflow-x:auto">';
            echo $this->renderGraph($fields, $dependencies);
            echo '</div>';
            echo $this->renderFieldList($fields);
        }

        echo '</div>';
    }

    private function extractDependencies(array $fields): array {
        $deps = [];
        foreach ($fields as $field) {
            if (!empty($field['conditional'])) {
                $deps[] = [
                    'source' => $field['conditional']['field'] ?? '',
                    'target' => $field['id'] ?? '',
                    'operator' => $field['conditional']['operator'] ?? '==',
                    'value' => $field['conditional']['value'] ?? '',
                ];
            }
        }
        return $deps;
    }

    private function renderGraph(array $fields, array $dependencies): string {
        $output = '<div style="font-family:monospace;font-size:13px">';

        // Build adjacency display
        foreach ($dependencies as $dep) {
            $output .= '<div style="margin:8px 0;padding:8px;background:#fff;border-left:3px solid #2271b1">';
            $output .= '<strong>' . esc_html($dep['source']) . '</strong>';
            $output .= ' <span style="color:#888">' . esc_html($dep['operator']) . '</span> ';
            $output .= '<code>' . esc_html($dep['value']) . '</code>';
            /* translators: %s: target field name */
            $output .= ' &rarr; ' . sprintf(esc_html__('shows %s', 'custom-meta-box-builder'), '<strong>' . esc_html($dep['target']) . '</strong>');
            $output .= '</div>';
        }

        $output .= '</div>';
        return $output;
    }

    private function renderFieldList(array $fields): string {
        /* translators: %d: number of fields */
        $output = '<details style="margin-bottom:15px"><summary style="cursor:pointer;color:#2271b1">' . sprintf(esc_html__('All fields (%d)', 'custom-meta-box-builder'), count($fields)) . '</summary>';
        $output .= '<table class="wp-list-table widefat fixed striped" style="margin-top:8px">';
        $output .= '<thead><tr><th>' . esc_html__('ID', 'custom-meta-box-builder') . '</th><th>' . esc_html__('Type', 'custom-meta-box-builder') . '</th><th>' . esc_html__('Label', 'custom-meta-box-builder') . '</th><th>' . esc_html__('Conditional', 'custom-meta-box-builder') . '</th></tr></thead><tbody>';
        foreach ($fields as $field) {
            $cond = '';
            if (!empty($field['conditional'])) {
                $c = $field['conditional'];
                $cond = ($c['field'] ?? '') . ' ' . ($c['operator'] ?? '==') . ' ' . ($c['value'] ?? '');
            }
            $output .= '<tr>';
            $output .= '<td><code>' . esc_html($field['id'] ?? '') . '</code></td>';
            $output .= '<td>' . esc_html($field['type'] ?? '') . '</td>';
            $output .= '<td>' . esc_html($field['label'] ?? '') . '</td>';
            $output .= '<td>' . ($cond ? esc_html($cond) : '<span style="color:#999">—</span>') . '</td>';
            $output .= '</tr>';
        }
        $output .= '</tbody></table></details>';
        return $output;
    }

    /**
     * Get dependency data as array (for programmatic use or REST).
     */
    public function getDependencyData(): array {
        $boxes = $this->manager->getMetaBoxes();

        $result = [];
        foreach ($boxes as $id => $box) {
            $fields = FieldUtils::flattenFields($box['fields']);
            $result[$id] = [
                'title' => $box['title'],
                'fields' => count($fields),
                'dependencies' => $this->extractDependencies($fields),
            ];
        }
        return $result;
    }
}
