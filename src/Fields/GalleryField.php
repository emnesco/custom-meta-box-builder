<?php
namespace CMB\Fields;

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
                    $output .= '<img src="' . esc_url($url) . '">';
                    $output .= '<button type="button" class="cmb-gallery-remove" aria-label="Remove">&times;</button>';
                    $output .= '</div>';
                }
            }
        }
        $output .= '</div>';

        $output .= '<button type="button" class="button cmb-gallery-add" data-target="#' . esc_attr($htmlId) . '">Add Images</button>';
        $output .= '</div>';

        return $output;
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $ids = array_filter(array_map('absint', explode(',', (string) $value)));
        return implode(',', $ids);
    }
}
