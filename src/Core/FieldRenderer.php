<?php
namespace CMB\Core;

use CMB\Core\Contracts\FieldInterface;

class FieldRenderer {
    protected \WP_Post $post;
    private ?array $metaCache = null;

    public function __construct(\WP_Post $post) {
        $this->post = $post;
    }

    public function getname($field, $group_index, $parent): string {
        $isFieldGroup = ($field['type'] ?? '') === 'group';
        $isFieldRepeatable = !empty($field['repeat']);

        if (is_string($parent) && $parent !== '') {
            return $parent . '[' . $field['id'] . ']';
        }

        $isParentFieldGroup = is_array($parent) && ($parent['type'] ?? '') === 'group';
        $isParentFieldRepeatable = is_array($parent) && !empty($parent['repeat']);
        $name = $parent ? $parent['id'] : $field['id'];

        if ($parent) {
            if ($isParentFieldGroup && $isParentFieldRepeatable) {
                return $name . '[' . $group_index . '][' . $field['id'] . ']';
            }
            if ($isParentFieldGroup && !$isParentFieldRepeatable) {
                return $name . '[' . $field['id'] . ']';
            }
        } else {
            if ($isFieldRepeatable && !$isFieldGroup) {
                return $name . '[]';
            }
            return $name;
        }

        return $name;
    }

    public function getChildPrefix(string $name, array $field, int $index): string {
        $isGroup = ($field['type'] ?? '') === 'group';
        $isRepeat = !empty($field['repeat']);

        if ($isGroup && $isRepeat) {
            return $name . '[' . $index . ']';
        }
        return $name;
    }

    /**
     * Generate a sanitized HTML id from a field name.
     */
    private function generateHtmlId(string $name): string {
        return 'cmb-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    }

    public function render(array $field, mixed $value = null, int $index = 0, mixed $parent = []): string {
        $name = $this->getname($field, $index, $parent);

        if (!$parent) {
            $value = $this->get_field_value($this->post->ID, $field);
        }

        $parent_is_array = is_array($parent);

        $fieldClass = 'CMB\\Fields\\' . ucfirst($field['type']) . 'Field';
        if (!class_exists($fieldClass)) {
            return '';
        }

        $htmlId = $this->generateHtmlId($name);

        /** @var FieldInterface $instance */
        $instance = new $fieldClass(array_merge($field, [
            'id' => $name,
            'name' => $name,
            'html_id' => $htmlId,
            'value' => $value,
        ]));

        $layout = isset($field['layout']) ? 'cmb-' . $field['layout'] : 'cmb-horizontal';

        $has_field_repeat = !empty($field['repeat']);
        $has_parent_repeat = $parent_is_array && !empty($parent['repeat']);
        $repeat = ($has_field_repeat || $has_parent_repeat) ? 'cmb-repeat' : '';

        $width = $field['width'] ?? '';
        $required_class = !empty($field['required']) ? 'cmb-required' : '';

        // Conditional display data attributes
        $conditionalAttrs = '';
        if (!empty($field['conditional'])) {
            $cond = $field['conditional'];
            $conditionalAttrs .= ' data-conditional-field="' . esc_attr($cond['field'] ?? '') . '"';
            $conditionalAttrs .= ' data-conditional-operator="' . esc_attr($cond['operator'] ?? '==') . '"';
            $conditionalAttrs .= ' data-conditional-value="' . esc_attr($cond['value'] ?? '') . '"';
            $conditionalAttrs .= ' style="display:none"';
        }

        // Hook: cmb_before_render_field (7.1)
        do_action('cmb_before_render_field', $field, $this->post);

        $output = '<div class="cmb-field ' . $layout . ' cmb-type-' . $field['type'] . ' ' . $repeat . ' ' . $width . ' ' . $required_class . '"' . $conditionalAttrs . '>';
            $output .= '<div class="cmb-label">';
                $output .= '<label for="' . esc_attr($htmlId) . '">' . esc_html($field['label'] ?? '');
                if (!empty($field['required'])) {
                    $output .= ' <span class="cmb-required-indicator" aria-label="required">*</span>';
                }
                $output .= '</label>';
            $output .= '</div>';
            $output .= '<div class="cmb-input">';
                // Expand/Collapse all for group fields
                if ($field['type'] === 'group' && $has_field_repeat && empty($field['repeat_fake'])) {
                    $output .= '<div class="cmb-group-actions">';
                    $output .= '<a href="#" class="cmb-expand-all">Expand All</a>';
                    $output .= '<a href="#" class="cmb-collapse-all">Collapse All</a>';
                    $output .= '</div>';
                }
                $output .= $instance->render();
                if ($has_field_repeat && empty($field['repeat_fake'])) {
                    $dataAttrs = '';
                    if (isset($field['min_rows'])) {
                        $dataAttrs .= ' data-min-rows="' . (int)$field['min_rows'] . '"';
                    }
                    if (isset($field['max_rows'])) {
                        $dataAttrs .= ' data-max-rows="' . (int)$field['max_rows'] . '"';
                    }
                    $output .= '<span class="cmb-add-row"' . $dataAttrs . '>Add Row</span>';
                    $output .= ' <span class="cmb-item-count"></span>';
                }
                if (!empty($field['description'])) {
                    $output .= '<p class="cmb-description">' . esc_html($field['description']) . '</p>';
                }
            $output .= '</div>';
         $output .= '</div>';

        // Hook: cmb_field_html filter (7.1)
        $output = apply_filters('cmb_field_html', $output, $field, $this->post);

        // Hook: cmb_after_render_field (7.1)
        do_action('cmb_after_render_field', $field, $this->post);

        return $output;
    }

    private function get_field_value(int $post_id, array $field): mixed {
        // Bulk-fetch all meta on first call
        if ($this->metaCache === null) {
            $all = get_post_meta($post_id);
            $this->metaCache = is_array($all) ? $all : [];
        }

        $key = $field['id'];
        $isCollection = ($field['type'] === 'group') || !empty($field['repeat']);

        if ($isCollection) {
            $meta = $this->metaCache[$key] ?? [];
            if (!empty($meta)) {
                // Unserialize if needed (get_post_meta without key returns raw rows)
                return array_map(function ($v) {
                    return is_serialized($v) ? maybe_unserialize($v) : $v;
                }, $meta);
            }
            return $field['type'] === 'group' ? [[]] : [''];
        }

        $meta = $this->metaCache[$key] ?? [null];
        $val = $meta[0] ?? null;
        $val = is_serialized($val) ? maybe_unserialize($val) : $val;

        // Hook: cmb_field_value filter (7.1)
        return apply_filters('cmb_field_value', $val, $key, $this->post->ID);
    }
}
