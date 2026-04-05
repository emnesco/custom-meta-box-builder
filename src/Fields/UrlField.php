<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class UrlField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $htmlId = $this->config['html_id'] ?? '';
        $id_attr = $htmlId ? ' id="' . esc_attr($htmlId) . '"' : '';
        return '<input type="url" name="' . esc_attr($this->getName()) . '"' . $id_attr . ' value="' . esc_attr($value ?? '') . '"' . $this->renderAttributes() . $this->requiredAttr() . '>';
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        return esc_url_raw($value);
    }
}
