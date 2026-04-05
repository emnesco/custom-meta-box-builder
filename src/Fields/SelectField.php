<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class SelectField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $output = '<select name="' . esc_attr($this->getName()) . '"' . $this->renderAttributes() . '>';

        foreach ($this->config['options'] ?? [] as $key => $label) {
            $selected = selected($value, $key, false);
            $output .= '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }

        $output .= '</select>';
        return $output;
    }

    public function sanitize($value) {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        return array_key_exists($value, $this->config['options'] ?? []) ? $value : '';
    }
}