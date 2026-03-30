<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class TextField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $output = '';
        if( isset($this->config['repeat']) && $this->config['repeat'] === true) {
            foreach ($value as $key => $valu) {
                $output .= '<input type="text" name="' . esc_attr($this->getName()) . '" value="' . esc_attr($valu ?? '') . '">';
            }
        } else {
             $output .= '<input type="text" name="' . esc_attr($this->getName()) . '" value="' . esc_attr($value ?? '') . '">';
        }
        return $output;
    }

    public function sanitize($value) {
        return sanitize_text_field($value);
    }
}