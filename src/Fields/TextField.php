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

final class TextField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        if (is_array($value)) {
            $value = $value[0] ?? '';
        }
        $attrs = $this->renderAttributes();
        $req = $this->requiredAttr();
        $htmlId = $this->config['html_id'] ?? '';
        $id_attr = $htmlId ? ' id="' . esc_attr($htmlId) . '"' : '';
        return '<input type="text" name="' . esc_attr($this->getName()) . '"' . $id_attr . ' value="' . esc_attr($value ?? '') . '"' . $attrs . $req . '>';
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        }
        return sanitize_text_field($value);
    }
}
