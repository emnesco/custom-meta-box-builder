<?php
declare(strict_types=1);

/**
 * Text field type — renders a single-line text input.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;

class TextField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $attrs = $this->renderAttributes();
        $req = $this->requiredAttr();
        $htmlId = $this->config['html_id'] ?? '';
        $output = '';
        if (isset($this->config['repeat']) && $this->config['repeat'] === true) {
            if (empty($value)) {
                $value = [''];
            }
            foreach ($value as $i => $val) {
                $id_attr = $htmlId ? ' id="' . esc_attr($htmlId . ($i > 0 ? '-' . $i : '')) . '"' : '';
                $output .= '<input type="text" name="' . esc_attr($this->getName()) . '"' . $id_attr . ' value="' . esc_attr($val ?? '') . '"' . $attrs . $req . '>';
            }
        } else {
            $id_attr = $htmlId ? ' id="' . esc_attr($htmlId) . '"' : '';
            $output .= '<input type="text" name="' . esc_attr($this->getName()) . '"' . $id_attr . ' value="' . esc_attr($value ?? '') . '"' . $attrs . $req . '>';
        }
        return $output;
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        }
        return sanitize_text_field($value);
    }
}
