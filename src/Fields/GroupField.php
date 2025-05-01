<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class GroupField extends AbstractField {
    public function render(): string {
        $groupFields = $this->config['fields'] ?? [];
        $groupLabel = $this->getLabel();
        $value = get_post_meta(get_the_ID(), $this->getId()) ?: [];

        $output = '<div class="cmb-group-wrapper">';
        $output .= '<div class="cmb-group-label">' . esc_html($groupLabel) . '</div>';
        $output .= '<div class="cmb-group-items">';

        if (empty($value)) {
            $output .= $this->renderGroupItem($groupFields, 0);
        } else {
            foreach ($value as $index => $groupItem) {
                $output .= $this->renderGroupItem($groupFields, $index, $groupItem);
            }
        }

        $output .= '</div>'; // .cmb-group-items
        $output .= '<span class="cmb-add-row">+ Add Row</span>';
        $output .= '</div>'; // .cmb-group-wrapper

        return $output;
    }

    protected function renderGroupItem(array $groupFields, int $index, array $groupItem = []): string {
        $output = '<div class="cmb-group-item">';
        foreach ($groupFields as $field) {
            $field['id'] = $this->getId() . "[$index][{$field['id']}]";
            $fieldClass = 'CMB\\Fields\\' . ucfirst($field['type']) . 'Field';
            if (class_exists($fieldClass)) {
                $instance = new $fieldClass($field);
                $output .= $instance->render();
            }
        }
        $output .= '<span class="cmb-remove-row">×</span>';
        $output .= '</div>';
        return $output;
    }

    public function sanitize($value) {
        return is_array($value) ? array_map('sanitize_text_field', $value) : [];
    }
}