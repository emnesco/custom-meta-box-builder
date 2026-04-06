<?php
declare(strict_types=1);

/**
 * Textarea field type — renders a multi-line text input.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;

final class TextareaField extends AbstractField {
    public function render(): string {
        $value = esc_textarea($this->getValue() ?? '');
        $htmlId = $this->config['html_id'] ?? '';
        $id_attr = $htmlId ? ' id="' . esc_attr($htmlId) . '"' : '';
        return '<textarea name="' . esc_attr($this->getName()) . '"' . $id_attr . $this->renderAttributes() . $this->requiredAttr() . '>' . $value . '</textarea>';
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map('sanitize_textarea_field', $value);
        }
        return sanitize_textarea_field($value);
    }
}
