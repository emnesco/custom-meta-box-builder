<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class SelectField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $htmlId = $this->config['html_id'] ?? '';
        $id_attr = $htmlId ? ' id="' . esc_attr($htmlId) . '"' : '';
        $output = '<select name="' . esc_attr($this->getName()) . '"' . $id_attr . $this->renderAttributes() . $this->requiredAttr() . '>';

        foreach ($this->config['options'] ?? [] as $key => $label) {
            $selected = selected($value, $key, false);
            $output .= '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }

        $output .= '</select>';
        return $output;
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        return array_key_exists($value, $this->config['options'] ?? []) ? $value : '';
    }
}
