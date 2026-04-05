<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class HiddenField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        return '<input type="hidden" name="' . esc_attr($this->getName()) . '" value="' . esc_attr($value ?? '') . '"' . $this->renderAttributes() . '>';
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        }
        return sanitize_text_field($value);
    }
}
