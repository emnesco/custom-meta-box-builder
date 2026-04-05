<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;
use CMB\Core\FieldRenderer;

class GroupField extends AbstractField {

    public function render(): string {
        $value = $this->getValue();
        $name = $this->getName();
        $field = $this->config;

        $collapsed = !empty($field['collapsed']) ? '' : 'open';
        $output = '';
        $output .= '<div class="cmb-group">';
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
        $output = '';
        $output .= '<div class="cmb-group-item ' . esc_attr($collapsed) . '" data-field-name="' . esc_attr($name) . '">';
        $output .= '<div class="cmb-group-item-header" role="button" tabindex="0" aria-expanded="' . ($collapsed === 'open' ? 'true' : 'false') . '">' . esc_html($field['label'] ?? '') . ' <span class="toggle-indicator" aria-hidden="true"></span></div>';
        $output .= '<div class="cmb-group-item-body">';
        $output .= '<div class="cmb-group-index">' . $index . '</div>';
        $output .= '<div class="cmb-group-fields">';

        if (!empty($field['fields'])) {
            foreach ($field['fields'] as $sub_field) {
                $sub_field_value = $this->sub_field_value($value, $index, $field, $sub_field);
                $fieldRenderer = new FieldRenderer(get_post(get_the_ID()));
                $parent_prefix = $fieldRenderer->getChildPrefix($name, $field, $index);
                $output .= $fieldRenderer->render($sub_field, $sub_field_value, $index, $parent_prefix);
            }
        }

        $output .= '</div>';
        $output .= '<button type="button" class="cmb-remove-row" aria-label="Remove item">&times;</button>';
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    }

    public function sanitize(mixed $value): mixed {
        if (!is_array($value)) {
            return [];
        }
        return map_deep($value, 'sanitize_text_field');
    }

    private function sub_field_value(mixed $value, int $index, array $field, array $sub_field): mixed {
        if (empty($field['repeat'])) {
            return $value[$sub_field['id']] ?? null;
        }
        return $value[$index][$sub_field['id']] ?? null;
    }
}
