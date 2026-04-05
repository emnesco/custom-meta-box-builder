<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class WysiwygField extends AbstractField {
    public function render(): string {
        $value = $this->getValue() ?? '';
        $name = $this->getName();
        // Generate a safe editor ID (wp_editor requires only lowercase alphanumeric + underscore)
        $editorId = preg_replace('/[^a-z0-9_]/', '_', strtolower($name));

        $settings = [
            'textarea_name' => $name,
            'textarea_rows' => $this->config['attributes']['rows'] ?? 10,
            'media_buttons' => $this->config['media_buttons'] ?? true,
            'teeny'         => $this->config['teeny'] ?? false,
            'quicktags'     => $this->config['quicktags'] ?? true,
        ];

        ob_start();
        if (function_exists('wp_editor')) {
            wp_editor($value, $editorId, $settings);
        } else {
            // Fallback for non-WP context (e.g., tests)
            echo '<textarea name="' . esc_attr($name) . '" id="' . esc_attr($editorId) . '" rows="' . (int)$settings['textarea_rows'] . '">' . esc_textarea($value) . '</textarea>';
        }
        return ob_get_clean();
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        return wp_kses_post($value);
    }
}
