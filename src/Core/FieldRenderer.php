<?php
namespace CMB\Core;

use CMB\Core\Contracts\FieldInterface;

class FieldRenderer {
    protected \WP_Post $post;

    public function __construct(\WP_Post $post) {
        $this->post = $post;
    }

    public function getname( $field, $group_index, $parent){
        $isFieldGroup = (isset($field['type']) && $field['type'] === 'group') ? true : false;
        $isFieldRepeatable = (isset($field['repeat']) && $field['repeat'] === true) ? true : false;

        // $parent may be a raw field config array or a resolved name prefix string.
        // When it is a string it already encodes full ancestor context (supports deep nesting).
        if (is_string($parent) && $parent !== '') {
            // $parent is a fully-resolved prefix like "name[0]" or "name[0][sour][0]"
            return $parent . '[' . $field['id'] . ']';
        }

        $isParentFieldGroup = (isset($parent['type']) && $parent['type'] === 'group') ? true : false;
        $isParentFieldRepeatable = (isset($parent['repeat']) && $parent['repeat'] === true) ? true : false;
        $name = $parent ? $parent['id']: $field['id'];
        if($parent) {
            if($isParentFieldGroup && $isParentFieldRepeatable ) {
                return $name  . '['.$group_index.'][' . $field['id'] . ']';
            }
            if($isParentFieldGroup && !$isParentFieldRepeatable ) {
                return $name  . '[' . $field['id'] . ']';
            }
        } else {
            if($isFieldRepeatable && !$isFieldGroup) {
                return $name . '[]';
            }
            return $name;
        }

        return $name;
    }

    /**
     * Build the name prefix that child fields of this rendered field should receive.
     * For a repeatable group rendered at $index, the prefix is e.g. "name[0]".
     * For a non-repeatable group it is "name".
     * This prefix is passed as $parent into child render() calls so that deep nesting
     * produces correct attributes like "name[0][sour][0][text]".
     */
    public function getChildPrefix( $name, $field, $index ) {
        $isGroup = isset($field['type']) && $field['type'] === 'group';
        $isRepeat = isset($field['repeat']) && $field['repeat'] === true;

        if ($isGroup && $isRepeat) {
            return $name . '[' . $index . ']';
        }
        if ($isGroup && !$isRepeat) {
            return $name;
        }
        return $name;
    }

    public function render( $field, $value = null, $index = 0, $parent = []) {

        $name = $this->getname( $field, $index, $parent);

        if (!$parent) {
            $value = $this->get_field_value($this->post->ID, $field);
        }

        $parent_is_array = is_array($parent);
        $repeat = (isset($field['repeat']) && $field['repeat'] === true || ($parent_is_array && isset($parent['repeat']) && $parent['repeat'] === true)) ? 'cmb-repeat' : '';

        $fieldClass = 'CMB\\Fields\\' . ucfirst($field['type']) . 'Field';
        if (!class_exists($fieldClass)) {
            return '';
        }

        /** @var FieldInterface $instance */
        $instance = new $fieldClass(array_merge($field, [
            'id' => $name,
            'name' => $name,
            'value' => $value,
        ]));


        $layout = isset($field['layout']) ? 'cmb-'.$field['layout'] : 'cmb-horizontal';

        $has_field_repeat = isset($field['repeat']) && $field['repeat'] === true;
        $has_parent_repeat = $parent_is_array && isset($parent['repeat']) && $parent['repeat'] === true;
        $repeat = ($has_field_repeat || $has_parent_repeat) ? 'cmb-repeat' : '';
        
        $width = isset($field['width']) ? $field['width'] : '';

        $output = '<div class="cmb-field ' . $layout . ' cmb-type-'.$field['type'].' ' . $repeat . ' '.$width.'">';
            $output .= '<div class="cmb-label">';
                $output .= '<label>' . esc_html($field['label'] ?? '') . '</label>';
            $output .= '</div>';
            $output .= '<div class="cmb-input">';
                $output .= $instance->render();
                if ( ( isset($field['repeat']) && $field['repeat'] === true && !isset($field['repeat_fake'])) ) {
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
            $meta = get_post_meta($post_id, $field['id']);
            if (!empty($meta)) {
                return $meta;
            }
            return $field['type'] === 'group' ? [[]] : [''];
        }
        return get_post_meta($post_id, $field['id'], true);
    }

}