<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class PasswordField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $htmlId = $this->config['html_id'] ?? '';
        $id_attr = $htmlId ? ' id="' . esc_attr($htmlId) . '"' : '';
        $placeholder = '';
        if (!empty($value)) {
            $placeholder = ' placeholder="' . esc_attr('••••••••') . '"';
        }
        return '<input type="password" name="' . esc_attr($this->getName()) . '"' . $id_attr . ' value=""' . $placeholder . $this->renderAttributes() . $this->requiredAttr() . '>';
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        }
        return sanitize_text_field($value);
    }
}
