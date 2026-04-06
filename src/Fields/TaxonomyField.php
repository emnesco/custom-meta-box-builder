<?php
declare(strict_types=1);

/**
 * Taxonomy relationship field type — renders terms as select or checkboxes.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;

class TaxonomyField extends AbstractField {
    private static array $termCache = [];

    public function render(): string {
        $value = $this->getValue();
        $currentValues = is_array($value) ? $value : [$value];
        $htmlId = $this->config['html_id'] ?? 'cmb-taxonomy';
        $name = esc_attr($this->getName());
        $taxonomy = $this->config['taxonomy'] ?? 'category';
        $fieldStyle = $this->config['field_style'] ?? 'checkbox';

        if (!function_exists('get_terms')) {
            return '<p>' . esc_html__('Taxonomy terms not available.', 'custom-meta-box-builder') . '</p>';
        }

        $queryArgs = [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        ];
        $cacheKey = md5(wp_json_encode($queryArgs));
        if (!isset(self::$termCache[$cacheKey])) {
            self::$termCache[$cacheKey] = get_terms($queryArgs);
        }
        $terms = self::$termCache[$cacheKey];

        if (is_wp_error($terms) || empty($terms)) {
            return '<p>' . esc_html__('No terms found.', 'custom-meta-box-builder') . '</p>';
        }

        if ($fieldStyle === 'select') {
            $output = '<select name="' . $name . '"' . ' id="' . esc_attr($htmlId) . '"' . $this->renderAttributes() . $this->requiredAttr() . '>';
            $output .= '<option value="">&mdash; ' . esc_html__('Select', 'custom-meta-box-builder') . ' &mdash;</option>';
            foreach ($terms as $term) {
                $sel = selected(in_array($term->term_id, $currentValues), true, false);
                $output .= '<option value="' . esc_attr($term->term_id) . '"' . $sel . '>' . esc_html($term->name) . '</option>';
            }
            $output .= '</select>';
            return $output;
        }

        // Checkbox list (default)
        $output = '<fieldset class="cmb-taxonomy-checklist">';
        $output .= '<legend class="screen-reader-text">' . esc_html($this->config['label'] ?? '') . '</legend>';
        foreach ($terms as $term) {
            $optionId = esc_attr($htmlId . '-' . $term->term_id);
            $isChecked = in_array($term->term_id, $currentValues) ? ' checked' : '';
            $output .= '<label for="' . $optionId . '">';
            $output .= '<input type="checkbox" id="' . $optionId . '" name="' . $name . '[]" value="' . esc_attr($term->term_id) . '"' . $isChecked . '> ';
            $output .= esc_html($term->name);
            $output .= '</label><br>';
        }
        $output .= '</fieldset>';
        return $output;
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map('absint', $value);
        }
        return absint($value);
    }
}
