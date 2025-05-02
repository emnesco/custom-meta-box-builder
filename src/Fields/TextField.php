<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class TextField extends AbstractField {
    public function render(): string {
        $value = esc_attr($this->getValue(get_the_ID()));
        return '<input type="text" name="' . esc_attr($this->getId()) . '" value="' . $value . '" />';
    }

    public function sanitize($value) {
        return sanitize_text_field($value);
    }
}