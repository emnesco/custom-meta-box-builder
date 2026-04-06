<?php
declare(strict_types=1);

/**
 * oEmbed field type — accepts a URL and renders an embed preview.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.2
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;

class OembedField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $name = esc_attr( $this->getName() );
        $htmlId = $this->config['html_id'] ?? 'cmb-oembed';

        $output = '<div class="cmb-oembed-field">';
        $output .= '<input type="url" id="' . esc_attr( $htmlId ) . '" name="' . $name . '" value="' . esc_attr( (string) $value ) . '" class="widefat cmb-oembed-input" placeholder="' . esc_attr__( 'Enter URL (YouTube, Vimeo, Twitter, etc.)', 'custom-meta-box-builder' ) . '"' . $this->requiredAttr() . $this->renderAttributes() . '>';

        // Render preview if value exists
        if ( $value && function_exists( 'wp_oembed_get' ) ) {
            $embed = wp_oembed_get( $value );
            if ( $embed ) {
                $output .= '<div class="cmb-oembed-preview">' . $embed . '</div>';
            }
        }

        $output .= '</div>';

        return $output;
    }

    public function sanitize( mixed $value ): mixed {
        if ( is_array( $value ) ) {
            return array_map( [ $this, 'sanitize' ], $value );
        }
        return esc_url_raw( (string) $value );
    }

    /**
     * Format the raw URL into embed HTML.
     */
    public function format( mixed $value ): mixed {
        if ( empty( $value ) || ! function_exists( 'wp_oembed_get' ) ) {
            return $value;
        }
        $embed = wp_oembed_get( $value );
        return $embed ?: $value;
    }
}
