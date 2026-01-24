<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class TextField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        if(!is_array($value)) {
            return '<input type="text" name="' . esc_attr($this->getId()) . '" value="' . $value . '" />';
        } 
        $output = '';
        foreach($value as $val) {
            $output .= '<input type="text" name="' . esc_attr($this->getId()) . '" value="' . esc_attr($val) . '" /><br />';
        }
        return $output;
    }

    public function sanitize($value) {
        return sanitize_text_field($value);
    }
}