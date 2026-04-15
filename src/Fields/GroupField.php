<?php
declare(strict_types=1);

/**
 * Group field type — renders repeatable/non-repeatable sub-field groups.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;
use CMB\Core\Contracts\FieldRendererInterface;
use CMB\Core\FieldFactory;
use CMB\Core\FieldRenderer;
use CMB\Core\Traits\SubFieldRenderTrait;

final class GroupField extends AbstractField {
    use SubFieldRenderTrait;

    public function render(): string {
        $value = $this->getValue();
        $name = $this->getName();
        $field = $this->config;

        // Bulk-fetch all post meta to avoid N+1 queries in sub-fields.
        $postId = get_the_ID();
        if ($postId) {
            $allMeta = get_post_meta($postId);
            if (empty($value) && !empty($allMeta[$name])) {
                $value = maybe_unserialize($allMeta[$name][0] ?? '');
            }
        }

        $collapsed = (!isset($field['collapsed']) || $field['collapsed'] !== false) ? '' : 'open';
        $rowTitleField = $field['row_title_field'] ?? '';
        $dataAttrs = '';
        if ($rowTitleField) {
            $dataAttrs .= ' data-row-title-field="' . esc_attr($rowTitleField) . '"';
        }

        $output = '';
        $output .= '<div class="cmb-group"' . $dataAttrs . '>';
        $output .= '<div class="cmb-group-items">';

        if (empty($value)) {
            $output .= $this->group_item($collapsed, $name, $field, 0, $value);
        } else {
            if (!empty($field['repeat'])) {
                foreach ($value as $index => $group) {
                    $output .= $this->group_item($collapsed, $name, $field, $index, $value);
                }
            } else {
                $output .= $this->group_item($collapsed, $name, $field, 0, $value);
            }
        }

        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    private function group_item(string $collapsed, string $name, array $field, int $index, mixed $value): string {
        $rowTitleField = $field['row_title_field'] ?? '';
        $rowTitle = $field['label'] ?? '';
        if ($rowTitleField && is_array($value) && isset($value[$index][$rowTitleField]) && $value[$index][$rowTitleField] !== '') {
            $rowTitle .= ': ' . $value[$index][$rowTitleField];
        }

        $output = '';
        $output .= '<div class="cmb-group-item ' . esc_attr($collapsed) . '" data-field-name="' . esc_attr($name) . '">';
        $output .= '<div class="cmb-group-item-header" role="button" tabindex="0" aria-expanded="' . ($collapsed === 'open' ? 'true' : 'false') . '">';
        $output .= '<span class="cmb-group-item-title"><span class="cmb-group-index cmb-sortable-handle" title="Drag to reorder">' . ($index + 1) . '</span>' . esc_html($rowTitle) . '</span>';
        $output .= '<span class="cmb-group-reorder-buttons">';
        $output .= '<button type="button" class="cmb-group-move-up" tabindex="0" aria-label="' . esc_attr__('Move item up', 'custom-meta-box-builder') . '" title="' . esc_attr__('Move up', 'custom-meta-box-builder') . '"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>';
        $output .= '<button type="button" class="cmb-group-move-down" tabindex="0" aria-label="' . esc_attr__('Move item down', 'custom-meta-box-builder') . '" title="' . esc_attr__('Move down', 'custom-meta-box-builder') . '"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>';
        $output .= '</span>';
        $output .= ' <span class="toggle-indicator" aria-hidden="true"></span>';
        $output .= '</div>';
        $output .= '<div class="cmb-group-item-body">';
        $output .= '<div class="cmb-group-fields">';

        if (!empty($field['fields'])) {
            /** @var FieldRendererInterface $fieldRenderer */
            $fieldRenderer = $this->config['_renderer'] ?? new FieldRenderer(get_post(get_the_ID()));
            foreach ($field['fields'] as $sub_field) {
                $sub_field_value = $this->sub_field_value($value, $index, $field, $sub_field);
                $parent_prefix = $fieldRenderer->getChildPrefix($name, $field, $index);

                // Add conditional data attributes
                if (!empty($sub_field['conditional'])) {
                    $sub_field['_conditional'] = $sub_field['conditional'];
                }

                $output .= $fieldRenderer->render($sub_field, $sub_field_value, $index, $parent_prefix);
            }
        }

        $output .= '</div>';
        $output .= '<div class="cmb-group-item-actions">';
        $output .= '<button type="button" class="cmb-duplicate-row" aria-label="Duplicate item" title="Duplicate"><span class="dashicons dashicons-admin-page"></span></button>';
        $output .= '<button type="button" class="cmb-remove-row" aria-label="Remove item" title="Remove"><span class="dashicons dashicons-trash"></span></button>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    }

    public function sanitize(mixed $value): mixed {
        if (!is_array($value)) {
            return [];
        }

        $subFields = $this->config['fields'] ?? [];
        if ( empty( $subFields ) ) {
            return map_deep($value, 'sanitize_text_field');
        }

        $sanitized = [];
        foreach ($value as $index => $groupData) {
            if (!is_array($groupData)) {
                continue;
            }
            $sanitizedGroup = [];
            foreach ($subFields as $subField) {
                $subId = $subField['id'];
                $subRaw = $groupData[$subId] ?? '';

                if ( !empty($subField['sanitize_callback']) && is_callable($subField['sanitize_callback']) ) {
                    $sanitizedGroup[$subId] = call_user_func($subField['sanitize_callback'], $subRaw);
                    continue;
                }

                $subInstance = FieldFactory::create($subField['type'], $subField);
                if ( null === $subInstance ) {
                    $sanitizedGroup[$subId] = sanitize_text_field( is_string($subRaw) ? $subRaw : '' );
                    continue;
                }

                $sanitizedGroup[$subId] = $subInstance->sanitize($subRaw);
            }
            $sanitized[$index] = $sanitizedGroup;
        }
        return $sanitized;
    }

    private function sub_field_value(mixed $value, int $index, array $field, array $sub_field): mixed {
        if (empty($field['repeat'])) {
            return $value[$sub_field['id']] ?? null;
        }
        return $value[$index][$sub_field['id']] ?? null;
    }
}
