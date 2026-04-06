<?php
declare(strict_types=1);

/**
 * Trait for shared sub-field rendering logic between GroupField and FlexibleContentField.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Traits;

defined( 'ABSPATH' ) || exit;

use CMB\Core\FieldFactory;

/**
 * Provides a reusable sub-field rendering loop for compound field types.
 */
trait SubFieldRenderTrait {
    /**
     * Render a list of sub-fields into HTML.
     *
     * @param array  $subFields Array of sub-field configuration arrays.
     * @param string $prefix    The name prefix for sub-field inputs.
     * @param array  $rowData   The current row data to extract values from.
     * @param int|string $index The current row index.
     * @return string The rendered HTML for all sub-fields.
     */
    protected function renderSubFields(array $subFields, string $prefix, array $rowData, int|string $index): string {
        $output = '';

        foreach ($subFields as $subField) {
            if (empty($subField['id']) || empty($subField['type'])) {
                continue;
            }

            $subName = $prefix . '[' . $subField['id'] . ']';
            $subValue = $rowData[$subField['id']] ?? ($subField['default'] ?? '');

            $instance = FieldFactory::create($subField['type'], array_merge($subField, [
                'id'      => $subName,
                'name'    => $subName,
                'html_id' => 'cmb-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $subName),
                'value'   => $subValue,
            ]));

            if (null === $instance) {
                continue;
            }

            $output .= '<div class="cmb-field cmb-horizontal cmb-type-' . esc_attr($subField['type']) . '">';
            $output .= '<div class="cmb-label"><label>' . esc_html($subField['label'] ?? '') . '</label></div>';
            $output .= '<div class="cmb-input">' . $instance->render() . '</div>';
            $output .= '</div>';
        }

        return $output;
    }
}
