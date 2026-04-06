<?php
declare(strict_types=1);

/**
 * Radio button field type — renders radio options within a fieldset.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;

class RadioField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $name = esc_attr($this->getName());
        $htmlId = $this->config['html_id'] ?? 'cmb-radio';
        $output = '<fieldset class="cmb-radio-group">';
        $output .= '<legend class="screen-reader-text">' . esc_html($this->config['label'] ?? '') . '</legend>';

        foreach ($this->config['options'] ?? [] as $key => $label) {
            $optionId = esc_attr($htmlId . '-' . $key);
            $checkedAttr = checked($value, $key, false);
            $output .= '<label for="' . $optionId . '">';
            $output .= '<input type="radio" id="' . $optionId . '" name="' . $name . '" value="' . esc_attr($key) . '" ' . $checkedAttr . $this->requiredAttr() . '> ';
            $output .= esc_html($label);
            $output .= '</label><br>';
        }

        $output .= '</fieldset>';
        return $output;
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        return array_key_exists($value, $this->config['options'] ?? []) ? $value : '';
    }
}
