<?php
declare(strict_types=1);

/**
 * Image field type — dedicated image upload with inline preview.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;

class ImageField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $htmlId = $this->config['html_id'] ?? 'cmb-image';
        $name = esc_attr($this->getName());
        $attachmentId = absint($value ?? 0);
        $buttonText = $this->config['button_text'] ?? __('Select Image', 'custom-meta-box-builder');

        $output = '<div class="cmb-file-field cmb-image-field">';
        $output .= '<input type="hidden" name="' . $name . '" id="' . esc_attr($htmlId) . '" value="' . $attachmentId . '" class="cmb-file-id">';

        $output .= '<div class="cmb-file-preview cmb-image-preview">';
        if ($attachmentId && function_exists('wp_get_attachment_image_url')) {
            $url = wp_get_attachment_image_url($attachmentId, 'medium');
            if ($url) {
                $alt = get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
                $output .= '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt ?: '') . '" style="max-width:300px;max-height:200px;" loading="lazy">';
            }
        }
        $output .= '</div>';

        $output .= '<button type="button" class="button cmb-file-upload" data-target="#' . esc_attr($htmlId) . '" data-type="image">' . esc_html($buttonText) . '</button>';
        $output .= ' <button type="button" class="button cmb-file-remove" data-target="#' . esc_attr($htmlId) . '"' . ($attachmentId ? '' : ' style="display:none"') . '>' . esc_html__('Remove', 'custom-meta-box-builder') . '</button>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Format: return attachment array with URL, alt, title instead of raw ID.
     */
    public function format(mixed $value): mixed {
        $id = absint($value ?? 0);
        if (!$id || !function_exists('wp_get_attachment_url')) {
            return $value;
        }
        return [
            'ID'    => $id,
            'url'   => wp_get_attachment_url($id),
            'alt'   => get_post_meta($id, '_wp_attachment_image_alt', true),
            'title' => get_the_title($id),
            'sizes' => function_exists('wp_get_attachment_image_src') ? [
                'thumbnail' => wp_get_attachment_image_url($id, 'thumbnail'),
                'medium'    => wp_get_attachment_image_url($id, 'medium'),
                'large'     => wp_get_attachment_image_url($id, 'large'),
                'full'      => wp_get_attachment_image_url($id, 'full'),
            ] : [],
        ];
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        return absint($value);
    }
}
