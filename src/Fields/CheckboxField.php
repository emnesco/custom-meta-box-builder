<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class CheckboxField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $checked = checked(in_array($value, [true, 'true', 1, '1'], true), true, false);
        return '<label>' . esc_html($this->getLabel()) . '</label>' .
               '<input type="checkbox" name="' . esc_attr($this->getId()) . '" value="1" ' . $checked . ' />';
    }

    public function sanitize($value) {
        return in_array($value, ['1', 1, true, 'true'], true) ? '1' : '0';
    }
}