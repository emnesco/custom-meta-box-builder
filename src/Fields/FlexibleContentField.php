<?php
declare(strict_types=1);

/**
 * Flexible content field type — allows content editors to build pages from pre-defined layout blocks.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.1
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;
use CMB\Core\FieldFactory;
use CMB\Core\FieldRenderer;
use CMB\Core\Traits\SubFieldRenderTrait;

final class FlexibleContentField extends AbstractField {
    use SubFieldRenderTrait;

    public function render(): string {
        $layouts = $this->config['layouts'] ?? [];
        $values = $this->getValue();
        $name = $this->getName();
        $htmlId = $this->config['html_id'] ?? '';

        if (!is_array($values)) {
            $values = [];
        }

        $output = '<div class="cmb-flexible-content" data-name="' . esc_attr($name) . '">';

        // Existing rows
        $output .= '<div class="cmb-flexible-items">';
        foreach ($values as $index => $row) {
            $layoutKey = $row['_layout'] ?? '';
            $layout = $this->findLayout($layouts, $layoutKey);
            if (!$layout) {
                continue;
            }
            $output .= $this->renderRow($name, $index, $layout, $row);
        }
        $output .= '</div>';

        // Add layout picker
        $output .= '<div class="cmb-flexible-add">';
        $output .= '<button type="button" class="button cmb-flexible-add-btn">' . esc_html__('Add Layout', 'custom-meta-box-builder') . '</button>';
        $output .= '<div class="cmb-flexible-layout-picker" style="display:none">';
        foreach ($layouts as $lKey => $layout) {
            $output .= '<button type="button" class="cmb-flexible-layout-option" '
                     . 'data-layout="' . esc_attr($lKey) . '" '
                     . 'data-layout-label="' . esc_attr($layout['label'] ?? $lKey) . '">';
            if (!empty($layout['icon'])) {
                $output .= '<span class="dashicons ' . esc_attr($layout['icon']) . '"></span> ';
            }
            $output .= esc_html($layout['label'] ?? $lKey);
            $output .= '</button>';
        }
        $output .= '</div>';
        $output .= '</div>';

        // Template store (hidden, used by JS to clone new rows via template.content.cloneNode)
        foreach ($layouts as $lKey => $layout) {
            $output .= '<template class="cmb-flexible-template" data-layout="' . esc_attr($lKey) . '">';
            $output .= $this->renderRow($name, '{{INDEX}}', $layout, []);
            $output .= '</template>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Render a single flexible content row.
     */
    private function renderRow(string $baseName, int|string $index, array $layout, array $rowData): string {
        $layoutKey = $layout['key'] ?? '';
        $prefix = $baseName . '[' . $index . ']';

        $output = '<div class="cmb-flexible-item cmb-group-item" data-layout="' . esc_attr($layoutKey) . '">';
        $output .= '<div class="cmb-group-item-header cmb-sortable-handle" role="button" tabindex="0" aria-expanded="true">';
        $output .= '<span class="cmb-group-index">' . $index . '</span> ';
        $output .= '<span class="cmb-flexible-layout-label">' . esc_html($layout['label'] ?? $layoutKey) . '</span>';
        $output .= '<span class="cmb-group-item-actions">';
        $output .= '<button type="button" class="cmb-remove-row" title="' . esc_attr__('Remove', 'custom-meta-box-builder') . '">&times;</button>';
        $output .= '</span>';
        $output .= '</div>';

        $output .= '<div class="cmb-group-item-body">';
        $output .= '<input type="hidden" name="' . esc_attr($prefix . '[_layout]') . '" value="' . esc_attr($layoutKey) . '">';

        // Render sub-fields for this layout using shared trait
        $output .= $this->renderSubFields($layout['fields'] ?? [], $prefix, $rowData, $index);

        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Find a layout definition by key.
     */
    private function findLayout(array $layouts, string $key): ?array {
        if (isset($layouts[$key])) {
            $layout = $layouts[$key];
            $layout['key'] = $key;
            return $layout;
        }
        return null;
    }

    public function sanitize(mixed $value): mixed {
        if (!is_array($value)) {
            return [];
        }

        $layouts = $this->config['layouts'] ?? [];
        // SEC-N10: Pre-compute valid layout names for validation.
        $validLayouts = array_keys($layouts);
        $sanitized = [];

        foreach ($value as $row) {
            if (!is_array($row) || empty($row['_layout'])) {
                continue;
            }

            $layoutKey = sanitize_text_field($row['_layout']);

            // SEC-N10: Validate submitted layout name against registered layouts.
            if (!in_array($layoutKey, $validLayouts, true)) {
                continue;
            }

            $layout = $this->findLayout($layouts, $layoutKey);
            if (!$layout) {
                continue;
            }

            $sanitizedRow = ['_layout' => $layoutKey];
            foreach ($layout['fields'] ?? [] as $subField) {
                $subValue = $row[$subField['id']] ?? '';
                $instance = FieldFactory::create($subField['type'], $subField);
                if (null !== $instance) {
                    $sanitizedRow[$subField['id']] = $instance->sanitize($subValue);
                }
            }
            $sanitized[] = $sanitizedRow;
        }

        return $sanitized;
    }
}
