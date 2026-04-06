<?php
declare(strict_types=1);

/**
 * Email field type — renders an email input with sanitization.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;

class EmailField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $htmlId = $this->config['html_id'] ?? '';
        $id_attr = $htmlId ? ' id="' . esc_attr($htmlId) . '"' : '';
        return '<input type="email" name="' . esc_attr($this->getName()) . '"' . $id_attr . ' value="' . esc_attr($value ?? '') . '"' . $this->renderAttributes() . $this->requiredAttr() . '>';
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        return sanitize_email($value);
    }
}
