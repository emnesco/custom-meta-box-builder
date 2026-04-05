<?php
/**
 * File upload field type — integrates with WordPress media library.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class FileField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $htmlId = $this->config['html_id'] ?? 'cmb-file';
        $name = esc_attr($this->getName());
        $attachmentId = absint($value ?? 0);
        $buttonText = $this->config['button_text'] ?? 'Select File';
        $previewType = $this->config['preview'] ?? 'image';

        $output = '<div class="cmb-file-field">';
        $output .= '<input type="hidden" name="' . $name . '" id="' . esc_attr($htmlId) . '" value="' . $attachmentId . '" class="cmb-file-id">';

        // Preview
        $output .= '<div class="cmb-file-preview">';
        if ($attachmentId && $previewType === 'image') {
            $url = function_exists('wp_get_attachment_image_url') ? wp_get_attachment_image_url($attachmentId, 'thumbnail') : '';
            if ($url) {
                $output .= '<img src="' . esc_url($url) . '" style="max-width:150px;max-height:150px;">';
            }
        } elseif ($attachmentId) {
            $url = function_exists('wp_get_attachment_url') ? wp_get_attachment_url($attachmentId) : '';
            if ($url) {
                $output .= '<a href="' . esc_url($url) . '" target="_blank">' . esc_html(basename($url)) . '</a>';
            }
        }
        $output .= '</div>';

        $output .= '<button type="button" class="button cmb-file-upload" data-target="#' . esc_attr($htmlId) . '">' . esc_html($buttonText) . '</button>';
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
