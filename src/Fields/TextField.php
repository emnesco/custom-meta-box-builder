<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class TextField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $attrs = $this->renderAttributes();
        $output = '';
        if (isset($this->config['repeat']) && $this->config['repeat'] === true) {
            foreach ($value as $key => $valu) {
                $output .= '<input type="text" name="' . esc_attr($this->getName()) . '" value="' . esc_attr($valu ?? '') . '"' . $attrs . '>';
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