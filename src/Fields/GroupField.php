<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;
use CMB\Core\FieldRenderer;

class GroupField extends AbstractField {


    /**
     * Render a group field container and its nested fields.
     */
    public function render(): string  {

        
        $value = $this->getValue();
        $name = $this->getName();
        $field = $this->config;

        $collapsed = isset($field['collapsed']) && $field['collapsed'] !== true ? 'open' : 'open';
        $output = '';
        $output .=  '<div class="cmb-group">';
        $output .=  '<div class="cmb-group-items">';

        if (empty($value)) {
            $output .= $this->group_item($collapsed, $name, $field, 0, $value);
        } else {
            if(isset($field['repeat']) && $field['repeat'] === true) {
                foreach ($value as $index => $group) {
                    $output .= $this->group_item($collapsed, $name, $field, $index, $value);
                }
            } else {
                $output .= $this->group_item($collapsed, $name, $field, 0, $value);
            }
        }

        $output .=  '</div>';
        $output .=  '</div>';

        return $output;
    }

    /**
     * Render a single group item.
     */
    function group_item($collapsed, $name, $field, $index, $value) {
        $output = '';
        $output .=  '<div class="cmb-group-item ' . esc_attr($collapsed) . '" data-field-name="' . esc_attr($name) . '">';
        $output .=  '<div class="cmb-group-item-header">' . esc_html($field['label']) . '</div>';
        $output .=  '<div class="cmb-group-item-body">';
        $output .=  '<div class="cmb-group-index">' . ($index) . '</div>';
        $output .=  '<div class="cmb-group-fields">';




        foreach ($field['fields'] as $sub_field) {




            $sub_field_value = $this->sub_field_value($value, $index, $field, $sub_field);
             $fieldRenderer = new FieldRenderer(get_post(get_the_ID()));
           $output .= $fieldRenderer->render($sub_field, $sub_field_value, $index, $name, $field);
        }
        $output .=  '</div>';
        $output .=  '<span class="cmb-remove-row">×</span>';
        $output .=  '</div>';
        $output .=  '</div>';
        return $output;

    }


    public function sanitize($value) {
        if (!is_array($value)) {
            return [];
        }
        // map_deep handles the recursion for you
        return map_deep($value, 'sanitize_text_field');
    }
    /**
     * Fetch field value from post meta.
     */
   function sub_field_value($value, $index, $field, $sub_field) {


if(!isset($field['repeat']) || (isset($field['repeat']) && $field['repeat'] === false)) {
         return $value[$sub_field['id']] ?? null;
}



        return $value[$index][$sub_field['id']] ?? null;
    }

}