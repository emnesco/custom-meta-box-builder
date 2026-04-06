<?php
declare(strict_types=1);

/**
 * Range/slider field type — renders an HTML5 range input with value display.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;

final class RangeField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $htmlId = $this->config['html_id'] ?? '';
        $id_attr = $htmlId ? ' id="' . esc_attr($htmlId) . '"' : '';
        $min = $this->config['min'] ?? 0;
        $max = $this->config['max'] ?? 100;
        $step = $this->config['step'] ?? 1;
        $name = esc_attr($this->getName());

        $output = '<div class="cmb-range-wrapper">';
        $output .= '<input type="range" name="' . $name . '"' . $id_attr . ' value="' . esc_attr($value ?? $min) . '" min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" step="' . esc_attr($step) . '"' . $this->renderAttributes() . ' data-cmb-range-output="true">';
        $output .= '<span class="cmb-range-value">' . esc_html($value ?? $min) . '</span>';
        $output .= '</div>';
        return $output;
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        $num = floatval($value);
        $min = floatval($this->config['min'] ?? 0);
        $max = floatval($this->config['max'] ?? 100);
        return max($min, min($max, $num));
    }
}
