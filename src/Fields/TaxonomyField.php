<?php
namespace CMB\Fields;

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
            return '<p>Taxonomy terms not available.</p>';
        }

        if (!isset(self::$termCache[$taxonomy])) {
            self::$termCache[$taxonomy] = get_terms([
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
            ]);
        }
        $terms = self::$termCache[$taxonomy];

        if (is_wp_error($terms) || empty($terms)) {
            return '<p>No terms found.</p>';
        }

        if ($fieldStyle === 'select') {
            $output = '<select name="' . $name . '"' . ' id="' . esc_attr($htmlId) . '"' . $this->renderAttributes() . $this->requiredAttr() . '>';
            $output .= '<option value="">&mdash; Select &mdash;</option>';
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
