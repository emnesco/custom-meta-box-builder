<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class TextField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $attrs = $this->renderAttributes();
        $output = '';
        if (isset($this->config['repeat']) && $this->config['repeat'] === true) {
            if (empty($value)) {
                $value = [''];
            }
            foreach ($value as $val) {
                $output .= '<input type="text" name="' . esc_attr($this->getName()) . '" value="' . esc_attr($val ?? '') . '"' . $attrs . '>';
            }
        } else {
            $output .= '<input type="text" name="' . esc_attr($this->getName()) . '" value="' . esc_attr($value ?? '') . '"' . $attrs . '>';
        }
        return $output;
    }

    public function sanitize($value) {
        return sanitize_text_field($value);
    }
}