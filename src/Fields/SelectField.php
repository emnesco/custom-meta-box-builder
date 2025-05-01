<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class SelectField extends AbstractField {
    public function render(): string {
        $value = $this->getValue(get_the_ID());
        $output = '<label>' . esc_html($this->getLabel()) . '</label>';
        $output .= '<select name="' . esc_attr($this->getId()) . '">';

        foreach ($this->config['options'] ?? [] as $key => $label) {
            $selected = selected($value, $key, false);
            $output .= '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }

        $output .= '</select>';
        return $output;
    }

    public function sanitize($value) {
        return array_key_exists($value, $this->config['options'] ?? []) ? $value : '';
    }
}