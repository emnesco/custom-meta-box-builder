<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class TextField extends AbstractField {
    public function render(): string {
        $value = esc_attr($this->getValue());

        if( $this->config['repeat'] === true) {
            dump($this->getName());
            dump($value);
            dump($this->config);

        }
        return '<input type="text" name="' . esc_attr($this->getName()) . '" value="' . esc_attr($value) . '">';
    }

    public function sanitize($value) {
        return sanitize_text_field($value);
    }
}