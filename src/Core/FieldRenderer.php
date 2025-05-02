<?php
namespace CMB\Core;

use CMB\Core\Contracts\FieldInterface;

class FieldRenderer {
    protected \WP_Post $post;

    public function __construct(\WP_Post $post) {
        $this->post = $post;
    }

    public function render(array $field, string $parentName = '', int $index = 0): string {
        $fullName = $this->buildName($field, $parentName, $index);
        $value = $this->getFieldValue($field['id']);

        $fieldClass = 'CMB\\Fields\\' . ucfirst($field['type']) . 'Field';
        if (!class_exists($fieldClass)) {
            return '';
        }

        /** @var FieldInterface $instance */
        $instance = new $fieldClass(array_merge($field, [
            'id' => $fullName
        ]));

        $layout = isset($field['layout']) ? 'cmb-'.$field['layout'] : 'cmb-horizontal';

        $has_field_repeat = isset($field['repeat']);
        $has_field_repeat = isset($field['repeat']) && $field['repeat'] === true;
        $has_parent_repeat = isset($parent['repeat']) && $parent['repeat'] === true;
        $repeat = ($has_field_repeat || $has_parent_repeat) ? 'cmb-repeat' : '';
        
        $output = '<div class="cmb-field ' . $layout . ' cmb-type-'.$field['type'].' ' . $repeat . '">';
            $output .= '<div class="cmb-label">';
                $output .= esc_html($field['label'] ?? '') . '</label>';
            $output .= '</div>';
            $output .= '<div class="cmb-input">';

                $output .= $instance->render();

                if ( ( isset($field['repeat']) && $field['repeat'] === true) ) {
                    $output .= '<span class="cmb-add-row">Add Row</span>';
                }

                if (!empty($field['description'])) {
                    $output .= '<p class="cmb-description">' . esc_html($field['description']) . '</p>';
                }
            $output .= '</div>';
         $output .= '</div>';

        return $output;
    }

    protected function getFieldValue(string $fieldId) {
        return get_post_meta($this->post->ID, $fieldId, true);
    }

    protected function buildName(array $field, string $parent, int $index, int $group_index = 0): string {


        $name = $parent_name ?? $field['id'];

        $isFieldGroup = ($field['type'] ?? null) === 'group';
        $isFieldRepeatable = $field['repeat'] ?? false;
        
        if (empty($parent) && ($isFieldGroup || $isFieldRepeatable)) {
            $current_name = $name . '[' . $group_index . ']';
        } elseif (empty($parent) && (!$isFieldGroup && !$isFieldRepeatable)) {
            $current_name = $name;
        } elseif (!empty($parent) && ($isFieldGroup || $isFieldRepeatable)) {
            $current_name = $name . '[' . $field['id'] . '][' . $group_index . ']';
        } else {
            $current_name = $name . '[' . $field['id'] . ']';
        }



        return $current_name;
    }
}