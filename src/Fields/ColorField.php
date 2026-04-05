<?php
/**
 * Color picker field type with alpha/rgba support via wp-color-picker.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class ColorField extends AbstractField {
    public function render(): string {
        $value = $this->getValue() ?? '#000000';
        $htmlId = $this->config['html_id'] ?? '';
        $id_attr = $htmlId ? ' id="' . esc_attr($htmlId) . '"' : '';
        $alpha = !empty($this->config['alpha']) ? ' data-alpha-enabled="true"' : '';
        return '<input type="text" name="' . esc_attr($this->getName()) . '"' . $id_attr . ' value="' . esc_attr($value) . '" class="cmb-color-picker"' . $alpha . $this->renderAttributes() . '>';
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        // Accept hex (#rrggbb), hex with alpha (#rrggbbaa), and rgba()
        if (preg_match('/^#[a-fA-F0-9]{6}([a-fA-F0-9]{2})?$/', $value)) {
            return $value;
        }
        if (preg_match('/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(,\s*[\d.]+\s*)?\)$/', $value)) {
            return $value;
        }
        return '#000000';
    }

    public function enqueueAssets(): void {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }
}
