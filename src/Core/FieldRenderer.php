<?php
namespace CMB\Core;

use CMB\Core\Contracts\FieldInterface;

class FieldRenderer {
    protected \WP_Post $post;

    public function __construct(\WP_Post $post) {
        $this->post = $post;
    }


    public function render( $field, $value = null, $group_index = 0, $parent_name = null, $parent = []) {
        
        $name = $parent_name ?? $field['id'];

        $isFieldGroup = (isset($field['type']) && $field['type'] === 'group') ? true : false;
        $isFieldRepeatable = (isset($field['repeat']) && $field['repeat'] === true) ? true : false;

        $isParentFieldGroup = (isset($parent['type']) && $parent['type'] === 'group') ? true : false;
        $isParentFieldRepeatable = (isset($parent['repeat']) && $parent['repeat'] === true) ? true : false;


        // Build correct input name depending on nesting and repeat status
        if (empty($parent) && ($isFieldGroup || $isFieldRepeatable)) {
            if($isFieldRepeatable && !$isFieldGroup) {
                $current_name = $name . '[]';
            } else {
                $current_name = $name . '[' . $group_index . ']';
            }
        } elseif (empty($parent)) {
            $current_name = $name;
        } elseif ($isFieldGroup || $isFieldRepeatable) {
            if($isFieldGroup && $isFieldRepeatable) {
                $current_name = $name . '[' . $field['id'] . '][' . $group_index . ']';
            } elseif($isFieldGroup && !$isFieldRepeatable) {
                $current_name = $name . '[' . $field['id'] . ']';
            }
        } else {
            $current_name = $name . '[' . $field['id'] . ']';
        }

        if (!$parent_name) {
            $value = $this->get_field_value($this->post->ID, $field);
        }

        
        $layout = isset($field['layout']) ? 'cmb-'.$field['layout'] : 'cmb-horizontal';
        $repeat = (isset($field['repeat']) && $field['repeat'] === true || isset($parent['repeat']) && $parent['repeat'] === true) ? 'cmb-repeat' : '';

        $fieldClass = 'CMB\\Fields\\' . ucfirst($field['type']) . 'Field';
        if (!class_exists($fieldClass)) {
            return '';
        }

        /** @var FieldInterface $instance */
        $instance = new $fieldClass(array_merge($field, [
            'id' => $current_name,
            'value' => $value,
            'name' => $current_name
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
                $output .= $current_name;
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



    private function get_field_value($post_id, $field) {
        if ($field['type'] === 'group' || (isset($field['repeat']) && $field['repeat'] === true)) {
            return get_post_meta($post_id, $field['id']) ? : array(0);
        }
        return get_post_meta($post_id, $field['id'], true);
    }

}