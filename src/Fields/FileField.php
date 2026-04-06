<?php
declare(strict_types=1);

/**
 * File upload field type — integrates with WordPress media library.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;

final class FileField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $htmlId = $this->config['html_id'] ?? 'cmb-file';
        $name = esc_attr($this->getName());
        $attachmentId = absint($value ?? 0);
        $buttonText = $this->config['button_text'] ?? __('Select File', 'custom-meta-box-builder');
        $previewType = $this->config['preview'] ?? 'image';

        $output = '<div class="cmb-file-field">';
        $output .= '<input type="hidden" name="' . $name . '" id="' . esc_attr($htmlId) . '" value="' . $attachmentId . '" class="cmb-file-id">';

        // Preview
        $output .= '<div class="cmb-file-preview">';
        if ($attachmentId && $previewType === 'image') {
            $url = function_exists('wp_get_attachment_image_url') ? wp_get_attachment_image_url($attachmentId, 'thumbnail') : '';
            if ($url) {
                $alt = get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
                $output .= '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt ?: '') . '" style="max-width:150px;max-height:150px;">';
            }
        } elseif ($attachmentId) {
            $url = function_exists('wp_get_attachment_url') ? wp_get_attachment_url($attachmentId) : '';
            if ($url) {
                $output .= '<a href="' . esc_url($url) . '" target="_blank">' . esc_html(basename($url)) . '</a>';
            }
        }
        $output .= '</div>';

        $output .= '<button type="button" class="button cmb-file-upload" data-target="#' . esc_attr($htmlId) . '">' . esc_html($buttonText) . '</button>';
        $output .= ' <button type="button" class="button cmb-file-remove" data-target="#' . esc_attr($htmlId) . '"' . ($attachmentId ? '' : ' style="display:none"') . '>' . esc_html__('Remove', 'custom-meta-box-builder') . '</button>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Format: return attachment array with URL, filename, mime type instead of raw ID.
     */
    public function format(mixed $value): mixed {
        $id = absint($value ?? 0);
        if (!$id || !function_exists('wp_get_attachment_url')) {
            return $value;
        }
        $url = wp_get_attachment_url($id);
        return [
            'ID'       => $id,
            'url'      => $url,
            'filename' => $url ? basename($url) : '',
            'title'    => get_the_title($id),
            'mime'     => get_post_mime_type($id),
        ];
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        return absint($value);
    }
}
