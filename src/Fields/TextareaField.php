<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class TextareaField extends AbstractField {
    public function render(): string {
        $value = esc_textarea($this->getValue() ?? '');
        return '<textarea name="' . esc_attr($this->getName()) . '"' . $this->renderAttributes() . '>' . $value . '</textarea>';
    }

    public function sanitize($value) {
        return sanitize_textarea_field($value);
    }
}