<?php
/**
 * Select field type — supports single and multi-select with placeholder.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class SelectField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $htmlId = $this->config['html_id'] ?? '';
        $id_attr = $htmlId ? ' id="' . esc_attr($htmlId) . '"' : '';
        $isMultiple = !empty($this->config['multiple']);
        $name = esc_attr($this->getName());

        $attrs = $this->renderAttributes() . $this->requiredAttr();
        if ($isMultiple) {
            $attrs .= ' multiple';
            $name .= '[]';
            $currentValues = is_array($value) ? $value : [$value];
        }

        $output = '<select name="' . $name . '"' . $id_attr . $attrs . '>';

        if (!$isMultiple) {
            $output .= '<option value="">&mdash; Select &mdash;</option>';
        }

        foreach ($this->config['options'] ?? [] as $key => $label) {
            if ($isMultiple) {
                $sel = in_array((string) $key, array_map('strval', $currentValues), true) ? ' selected="selected"' : '';
            } else {
                $selAttr = selected($value, $key, false);
                $sel = $selAttr ? ' ' . $selAttr : '';
            }
            $output .= '<option value="' . esc_attr($key) . '"' . $sel . '>' . esc_html($label) . '</option>';
        }

        $output .= '</select>';
        return $output;
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_filter(array_map(function ($v) {
                return array_key_exists($v, $this->config['options'] ?? []) ? $v : null;
            }, $value));
        }
        return array_key_exists($value, $this->config['options'] ?? []) ? $value : '';
    }
}
