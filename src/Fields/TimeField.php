<?php
declare(strict_types=1);

/**
 * Time field type — renders an HTML5 time input with HH:MM validation.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;

class TimeField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $htmlId = $this->config['html_id'] ?? '';
        $id_attr = $htmlId ? ' id="' . esc_attr($htmlId) . '"' : '';
        return '<input type="time" name="' . esc_attr($this->getName()) . '"' . $id_attr . ' value="' . esc_attr($value ?? '') . '"' . $this->renderAttributes() . $this->requiredAttr() . '>';
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        $value = sanitize_text_field($value);
        return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value) ? $value : '';
    }
}
