<?php
namespace CMB\Core;

/**
 * Field dependency graph visualization for developer tools (8.6).
 *
 * Generates a visual representation of field dependencies (conditional logic)
 * to help developers debug and understand field relationships.
 */
class DependencyGraph {
    public static function register(): void {
        add_action('admin_menu', [self::class, 'addAdminPage']);
    }

    public static function addAdminPage(): void {
        add_submenu_page(
            'tools.php',
            'CMB Dependency Graph',
            'CMB Field Graph',
            'manage_options',
            'cmb-dependency-graph',
            [self::class, 'renderPage']
        );
    }

    public static function renderPage(): void {
        $manager = MetaBoxManager::instance();
        $boxes = $manager->getMetaBoxes();

        echo '<div class="wrap">';
        echo '<h1>CMB Field Dependency Graph</h1>';
        echo '<p>Visual representation of field dependencies (conditional logic) across all meta boxes.</p>';

        if (empty($boxes)) {
            echo '<p>No meta boxes registered.</p></div>';
            return;
        }

        foreach ($boxes as $id => $box) {
            echo '<h2>' . esc_html($box['title']) . ' <code>' . esc_html($id) . '</code></h2>';

            $fields = self::flattenFields($box['fields']);
            $dependencies = self::extractDependencies($fields);

            if (empty($dependencies)) {
                echo '<p style="color:#999">No conditional dependencies in this meta box.</p>';
                echo self::renderFieldList($fields);
                continue;
            }

            echo '<div class="cmb-graph-container" style="background:#f9f9f9;border:1px solid #ddd;padding:20px;margin-bottom:20px;overflow-x:auto">';
            echo self::renderGraph($fields, $dependencies);
            echo '</div>';
            echo self::renderFieldList($fields);
        }

        echo '</div>';
    }

    private static function flattenFields(array $fields): array {
        if (!empty($fields['tabs'])) {
            $flat = [];
            foreach ($fields['tabs'] as $tab) {
                foreach ($tab['fields'] ?? [] as $field) {
                    $flat[] = $field;
                }
            }
            return $flat;
        }
        return $fields;
    }

    private static function extractDependencies(array $fields): array {
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

    private static function renderGraph(array $fields, array $dependencies): string {
        $output = '<div style="font-family:monospace;font-size:13px">';

        // Build adjacency display
        foreach ($dependencies as $dep) {
            $output .= '<div style="margin:8px 0;padding:8px;background:#fff;border-left:3px solid #2271b1">';
            $output .= '<strong>' . esc_html($dep['source']) . '</strong>';
            $output .= ' <span style="color:#888">' . esc_html($dep['operator']) . '</span> ';
            $output .= '<code>' . esc_html($dep['value']) . '</code>';
            $output .= ' &rarr; shows <strong>' . esc_html($dep['target']) . '</strong>';
            $output .= '</div>';
        }

        $output .= '</div>';
        return $output;
    }

    private static function renderFieldList(array $fields): string {
        $output = '<details style="margin-bottom:15px"><summary style="cursor:pointer;color:#2271b1">All fields (' . count($fields) . ')</summary>';
        $output .= '<table class="wp-list-table widefat fixed striped" style="margin-top:8px">';
        $output .= '<thead><tr><th>ID</th><th>Type</th><th>Label</th><th>Conditional</th></tr></thead><tbody>';
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
    public static function getDependencyData(): array {
        $manager = MetaBoxManager::instance();
        $boxes = $manager->getMetaBoxes();

        $result = [];
        foreach ($boxes as $id => $box) {
            $fields = self::flattenFields($box['fields']);
            $result[$id] = [
                'title' => $box['title'],
                'fields' => count($fields),
                'dependencies' => self::extractDependencies($fields),
            ];
        }
        return $result;
    }
}
