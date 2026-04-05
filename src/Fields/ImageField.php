<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class ImageField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $htmlId = $this->config['html_id'] ?? 'cmb-image';
        $name = esc_attr($this->getName());
        $attachmentId = absint($value ?? 0);
        $buttonText = $this->config['button_text'] ?? 'Select Image';

        $output = '<div class="cmb-file-field cmb-image-field">';
        $output .= '<input type="hidden" name="' . $name . '" id="' . esc_attr($htmlId) . '" value="' . $attachmentId . '" class="cmb-file-id">';

        $output .= '<div class="cmb-file-preview cmb-image-preview">';
        if ($attachmentId && function_exists('wp_get_attachment_image_url')) {
            $url = wp_get_attachment_image_url($attachmentId, 'medium');
            if ($url) {
                $output .= '<img src="' . esc_url($url) . '" style="max-width:300px;max-height:200px;">';
            }
        }
        $output .= '</div>';

        $output .= '<button type="button" class="button cmb-file-upload" data-target="#' . esc_attr($htmlId) . '" data-type="image">' . esc_html($buttonText) . '</button>';
        $output .= ' <button type="button" class="button cmb-file-remove" data-target="#' . esc_attr($htmlId) . '"' . ($attachmentId ? '' : ' style="display:none"') . '>Remove</button>';
        $output .= '</div>';

        return $output;
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        return absint($value);
    }
}
