<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class MessageField extends AbstractField {
    public function render(): string {
        return '<div class="cmb-message">' . wp_kses_post($this->config['content'] ?? '') . '</div>';
    }

    public function sanitize(mixed $value): mixed {
        return '';
    }

    public function getValue(): mixed {
        return null;
    }
}
