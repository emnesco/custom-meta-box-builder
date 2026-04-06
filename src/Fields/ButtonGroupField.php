<?php
declare(strict_types=1);

/**
 * Button group field type — renders choice options as clickable buttons with aria-pressed.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.2
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;

class ButtonGroupField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $name = esc_attr( $this->getName() );
        $htmlId = $this->config['html_id'] ?? 'cmb-button-group';

        $output = '<div class="cmb-button-group-field" role="radiogroup" aria-label="' . esc_attr( $this->config['label'] ?? '' ) . '">';
        $output .= '<input type="hidden" name="' . $name . '" value="' . esc_attr( (string) $value ) . '" class="cmb-button-group-value">';

        foreach ( $this->config['options'] ?? [] as $key => $label ) {
            $isSelected = ( (string) $value === (string) $key );
            $activeClass = $isSelected ? ' active' : '';
            $output .= '<button type="button"';
            $output .= ' class="button cmb-button-group-btn' . $activeClass . '"';
            $output .= ' data-value="' . esc_attr( $key ) . '"';
            $output .= ' aria-pressed="' . ( $isSelected ? 'true' : 'false' ) . '"';
            $output .= '>' . esc_html( $label ) . '</button>';
        }

        $output .= '</div>';

        return $output;
    }

    public function sanitize( mixed $value ): mixed {
        if ( is_array( $value ) ) {
            return array_map( [ $this, 'sanitize' ], $value );
        }
        return array_key_exists( $value, $this->config['options'] ?? [] ) ? $value : '';
    }
}
