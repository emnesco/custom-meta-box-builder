<?php
declare(strict_types=1);

/**
 * Link field type — URL, title, and target selection (similar to ACF's link field).
 *
 * @package CustomMetaBoxBuilder
 * @since   2.2
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;

class LinkField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $name = esc_attr( $this->getName() );
        $htmlId = $this->config['html_id'] ?? 'cmb-link';

        $url    = '';
        $title  = '';
        $target = '';
        if ( is_array( $value ) ) {
            $url    = $value['url'] ?? '';
            $title  = $value['title'] ?? '';
            $target = $value['target'] ?? '';
        }

        $output = '<div class="cmb-link-field">';

        $output .= '<p><label for="' . esc_attr( $htmlId ) . '-url">' . esc_html__( 'URL', 'custom-meta-box-builder' ) . '</label><br>';
        $output .= '<input type="url" id="' . esc_attr( $htmlId ) . '-url" name="' . $name . '[url]" value="' . esc_attr( $url ) . '" class="widefat"' . $this->requiredAttr() . $this->renderAttributes() . '></p>';

        $output .= '<p><label for="' . esc_attr( $htmlId ) . '-title">' . esc_html__( 'Link Text', 'custom-meta-box-builder' ) . '</label><br>';
        $output .= '<input type="text" id="' . esc_attr( $htmlId ) . '-title" name="' . $name . '[title]" value="' . esc_attr( $title ) . '" class="widefat"></p>';

        $output .= '<p><label for="' . esc_attr( $htmlId ) . '-target">';
        $output .= '<input type="checkbox" id="' . esc_attr( $htmlId ) . '-target" name="' . $name . '[target]" value="_blank"' . checked( $target, '_blank', false ) . '> ';
        $output .= esc_html__( 'Open in new tab', 'custom-meta-box-builder' );
        $output .= '</label></p>';

        $output .= '</div>';

        return $output;
    }

    public function sanitize( mixed $value ): mixed {
        if ( ! is_array( $value ) ) {
            return [
                'url'    => '',
                'title'  => '',
                'target' => '',
            ];
        }

        return [
            'url'    => esc_url_raw( $value['url'] ?? '' ),
            'title'  => sanitize_text_field( $value['title'] ?? '' ),
            'target' => ( $value['target'] ?? '' ) === '_blank' ? '_blank' : '',
        ];
    }

    /**
     * Format the raw value for frontend display.
     */
    public function format( mixed $value ): mixed {
        if ( ! is_array( $value ) || empty( $value['url'] ) ) {
            return $value;
        }

        return [
            'url'    => $value['url'],
            'title'  => $value['title'] ?? '',
            'target' => $value['target'] ?? '',
        ];
    }
}
