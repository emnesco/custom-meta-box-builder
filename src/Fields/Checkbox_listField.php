<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class Checkbox_listField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $currentValues = is_array($value) ? $value : [$value];
        $htmlId = $this->config['html_id'] ?? '';
        $name = esc_attr($this->getName()) . '[]';

        $output = '<fieldset class="cmb-checkbox-list">';
        $output .= '<legend class="screen-reader-text">' . esc_html($this->config['label'] ?? '') . '</legend>';
        foreach ($this->config['options'] ?? [] as $key => $label) {
            $optionId = esc_attr(($htmlId ?: 'cmb-cblist') . '-' . $key);
            $isChecked = in_array((string) $key, array_map('strval', $currentValues), true) ? ' checked' : '';
            $output .= '<label for="' . $optionId . '">';
            $output .= '<input type="checkbox" id="' . $optionId . '" name="' . $name . '" value="' . esc_attr($key) . '"' . $isChecked . '> ';
            $output .= esc_html($label);
            $output .= '</label><br>';
        }
        $output .= '</fieldset>';
        return $output;
    }

    public function sanitize(mixed $value): mixed {
        if (!is_array($value)) {
            return [];
        }
        $options = $this->config['options'] ?? [];
        return array_values(array_filter($value, function ($v) use ($options) {
            return array_key_exists($v, $options);
        }));
    }
}
