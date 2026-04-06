<?php
declare(strict_types=1);

/**
 * Checkbox field type — renders a single boolean toggle.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;

class CheckboxField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $checked = checked(in_array($value, [true, 'true', 1, '1'], true), true, false);
        $htmlId = $this->config['html_id'] ?? '';
        $id_attr = $htmlId ? ' id="' . esc_attr($htmlId) . '"' : '';
        return '<input type="hidden" name="' . esc_attr($this->getName()) . '" value="0">'
             . '<input type="checkbox" name="' . esc_attr($this->getName()) . '"' . $id_attr . ' value="1" ' . $checked . $this->renderAttributes() . ' />';
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        return in_array($value, ['1', 1, true, 'true'], true) ? '1' : '0';
    }
}
