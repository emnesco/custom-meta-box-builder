<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class DividerField extends AbstractField {
    public function render(): string {
        return '<hr class="cmb-divider">';
    }

    public function sanitize(mixed $value): mixed {
        return '';
    }

    public function getValue(): mixed {
        return null;
    }
}
