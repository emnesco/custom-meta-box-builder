<?php
declare(strict_types=1);

/**
 * Gallery field type — multi-image selection with comma-separated IDs.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;

class GalleryField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $htmlId = $this->config['html_id'] ?? 'cmb-gallery';
        $name = esc_attr($this->getName());
        $ids = is_string($value) ? $value : '';
        $idArray = array_filter(array_map('absint', explode(',', $ids)));

        $output = '<div class="cmb-gallery-field">';
        $output .= '<input type="hidden" name="' . $name . '" id="' . esc_attr($htmlId) . '" value="' . esc_attr($ids) . '" class="cmb-gallery-ids">';

        $output .= '<div class="cmb-gallery-preview">';
        if (function_exists('wp_get_attachment_image_url')) {
            foreach ($idArray as $attId) {
                $url = wp_get_attachment_image_url($attId, 'thumbnail');
                if ($url) {
                    $output .= '<div class="cmb-gallery-thumb" data-id="' . $attId . '">';
                    $alt = get_post_meta($attId, '_wp_attachment_image_alt', true);
                    $output .= '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt ?: '') . '" loading="lazy">';
                    $output .= '<button type="button" class="cmb-gallery-remove" aria-label="' . esc_attr__('Remove', 'custom-meta-box-builder') . '">&times;</button>';
                    $output .= '</div>';
                }
            }
        }
        $output .= '</div>';

        $output .= '<button type="button" class="button cmb-gallery-add" data-target="#' . esc_attr($htmlId) . '">' . esc_html__('Add Images', 'custom-meta-box-builder') . '</button>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Format: return array of attachment objects instead of comma-separated IDs.
     */
    public function format(mixed $value): mixed {
        $ids = is_string($value) ? array_filter(array_map('absint', explode(',', $value))) : [];
        if (empty($ids) || !function_exists('wp_get_attachment_url')) {
            return $value;
        }
        $attachments = [];
        foreach ($ids as $id) {
            $url = wp_get_attachment_url($id);
            if ($url) {
                $attachments[] = [
                    'ID'    => $id,
                    'url'   => $url,
                    'alt'   => get_post_meta($id, '_wp_attachment_image_alt', true),
                    'title' => get_the_title($id),
                    'sizes' => [
                        'thumbnail' => wp_get_attachment_image_url($id, 'thumbnail'),
                        'medium'    => wp_get_attachment_image_url($id, 'medium'),
                        'full'      => wp_get_attachment_image_url($id, 'full'),
                    ],
                ];
            }
        }
        return $attachments;
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $ids = array_filter(array_map('absint', explode(',', (string) $value)));
        return implode(',', $ids);
    }
}
