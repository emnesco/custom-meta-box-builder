<?php
declare(strict_types=1);

/**
 * Toggle/switch field type — renders a CSS toggle for boolean values.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;

class ToggleField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $htmlId = $this->config['html_id'] ?? '';
        $id_attr = $htmlId ? $htmlId : 'cmb-toggle';
        $name = esc_attr($this->getName());
        $checked = !empty($value) ? ' checked' : '';

        $output = '<label class="cmb-toggle-switch" for="' . esc_attr($id_attr) . '">';
        $output .= '<input type="hidden" name="' . $name . '" value="0">';
        $output .= '<input type="checkbox" id="' . esc_attr($id_attr) . '" name="' . $name . '" value="1"' . $checked . '>';
        $output .= '<span class="cmb-toggle-slider"></span>';
        $output .= '</label>';
        return $output;
    }

    public function sanitize(mixed $value): mixed {
        return $value ? '1' : '0';
    }
}
