<?php
namespace CMB\Core;

use CMB\Core\Contracts\FieldInterface;

class FieldRenderer {
    protected \WP_Post $post;

    public function __construct(\WP_Post $post) {
        $this->post = $post;
    }

    public function render( array $field, string $parent = '', int $index = 0, array $value = [], array $parent_field = []): string {





        $fieldClass = 'CMB\\Fields\\' . ucfirst($field['type']) . 'Field';
        if (!class_exists($fieldClass)) {
            return '';
        }


        $fullName = $this->buildName($parent_field, $field, $parent, $index);
        


        /** @var FieldInterface $instance */
        $instance = new $fieldClass(array_merge($field, [
            'id' => $fullName,
            'value' => $parent ? $value[$field['id']] : $this->getFieldValue($field),
        ]));



        $layout = isset($field['layout']) ? 'cmb-'.$field['layout'] : 'cmb-horizontal';

        $has_field_repeat = isset($field['repeat']);
        $has_field_repeat = isset($field['repeat']) && $field['repeat'] === true;
        $has_parent_repeat = isset($parent['repeat']) && $parent['repeat'] === true;
        $repeat = ($has_field_repeat || $has_parent_repeat) ? 'cmb-repeat' : '';
        
        $output = '<div class="cmb-field ' . $layout . ' cmb-type-'.$field['type'].' ' . $repeat . '">';
            $output .= '<div class="cmb-label">';
                $output .= esc_html($field['label'] ?? '');

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

    protected function getFieldValue(array $field) {
        if($field['type'] === 'group' || $field['repeat'] === true) {
            return get_post_meta($this->post->ID, $field['id']);
        }
        return get_post_meta($this->post->ID, $field['id'], true);
    }

    protected function buildName(array $parent_field = [], array $field, string $parent, int $index = 0, int $group_index = 0): string {


        $name = $parent ? $parent : $field['id'];

        $isParentFieldGroup = ($parent_field['type'] ?? null) === 'group';
        $isParentFieldRepeatable = $parent_field['repeat'] ?? false;
        $isFieldRepeatable = $field['repeat'] ?? false;



        if (empty($parent) && !$isFieldRepeatable) {
            $current_name = $name;
        } elseif (empty($parent) && $isFieldRepeatable) {
            $current_name = $name. '[' . $index . ']';
        } elseif (!empty($parent) && ($isParentFieldGroup || $isParentFieldRepeatable)) {
            $current_name = $name . '[' . $index . '][' . $field['id'] . ']';
        } else {
            $current_name = $name . '[' . $field['id'] . ']';
        }


        return $current_name;
    }
}