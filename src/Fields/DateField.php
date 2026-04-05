<?php
/**
 * Date field type — renders an HTML5 date input.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class DateField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $type = $this->config['datetime'] ?? false ? 'datetime-local' : 'date';
        $htmlId = $this->config['html_id'] ?? '';
        $id_attr = $htmlId ? ' id="' . esc_attr($htmlId) . '"' : '';
        return '<input type="' . $type . '" name="' . esc_attr($this->getName()) . '"' . $id_attr . ' value="' . esc_attr($value ?? '') . '"' . $this->renderAttributes() . $this->requiredAttr() . '>';
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        $value = sanitize_text_field($value);
        // Validate ISO 8601 date or datetime-local format
        if ($value !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}(:\d{2})?)?$/', $value)) {
            return '';
        }
        return $value;
    }
}
