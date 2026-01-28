<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class TextareaField extends AbstractField {
    public function render(): string {
        $value = esc_textarea($this->getValue());
        return '<label>' . esc_html($this->getLabel()) . '<textarea name="' . esc_attr($this->getId()) . '">' . $value . '</textarea></label>' .
               '';
    }

    public function sanitize($value) {
        return sanitize_textarea_field($value);
    }
}