<?php
namespace CMB\Core;

class TaxonomyMetaManager {
    private array $taxonomyBoxes = [];

    public function add(string $taxonomy, array $fields): void {
        $this->taxonomyBoxes[$taxonomy] = $fields;
    }

    public function register(): void {
        foreach (array_keys($this->taxonomyBoxes) as $taxonomy) {
            add_action($taxonomy . '_edit_form_fields', [$this, 'renderFields'], 10, 1);
            add_action('edited_' . $taxonomy, [$this, 'saveFields'], 10, 1);
            add_action($taxonomy . '_add_form_fields', [$this, 'renderAddFields'], 10, 1);
            add_action('created_' . $taxonomy, [$this, 'saveFields'], 10, 1);
        }
    }

    public function renderFields($term): void {
        $taxonomy = $term->taxonomy ?? (is_string($term) ? $term : '');
        if (is_object($term)) {
            $taxonomy = $term->taxonomy;
        }
        $fields = $this->taxonomyBoxes[$taxonomy] ?? [];
        if (empty($fields)) return;

        wp_nonce_field('cmb_taxonomy_save', 'cmb_taxonomy_nonce');

        foreach ($fields as $field) {
            $value = '';
            if (is_object($term) && !empty($term->term_id)) {
                $value = get_term_meta($term->term_id, $field['id'], true);
            }

            echo '<tr class="form-field">';
            echo '<th><label for="' . esc_attr($field['id']) . '">' . esc_html($field['label'] ?? '') . '</label></th>';
            echo '<td>';

            $fieldClass = 'CMB\\Fields\\' . ucfirst($field['type']) . 'Field';
            if (class_exists($fieldClass)) {
                $instance = new $fieldClass(array_merge($field, [
                    'name' => $field['id'],
                    'html_id' => $field['id'],
                    'value' => $value,
                ]));
                echo $instance->render();
            }

            if (!empty($field['description'])) {
                echo '<p class="description">' . esc_html($field['description']) . '</p>';
            }
            echo '</td></tr>';
        }
    }

    public function renderAddFields($taxonomy): void {
        $fields = $this->taxonomyBoxes[$taxonomy] ?? [];
        if (empty($fields)) return;

        wp_nonce_field('cmb_taxonomy_save', 'cmb_taxonomy_nonce');

        foreach ($fields as $field) {
            echo '<div class="form-field">';
            echo '<label for="' . esc_attr($field['id']) . '">' . esc_html($field['label'] ?? '') . '</label>';

            $fieldClass = 'CMB\\Fields\\' . ucfirst($field['type']) . 'Field';
            if (class_exists($fieldClass)) {
                $instance = new $fieldClass(array_merge($field, [
                    'name' => $field['id'],
                    'html_id' => $field['id'],
                    'value' => $field['default'] ?? '',
                ]));
                echo $instance->render();
            }

            if (!empty($field['description'])) {
                echo '<p>' . esc_html($field['description']) . '</p>';
            }
            echo '</div>';
        }
    }

    public function saveFields(int $termId): void {
        if (!isset($_POST['cmb_taxonomy_nonce']) || !wp_verify_nonce($_POST['cmb_taxonomy_nonce'], 'cmb_taxonomy_save')) {
            return;
        }

        foreach ($this->taxonomyBoxes as $fields) {
            foreach ($fields as $field) {
                $fieldClass = 'CMB\\Fields\\' . ucfirst($field['type']) . 'Field';
                if (!class_exists($fieldClass)) continue;

                $instance = new $fieldClass($field);
                $raw = $_POST[$field['id']] ?? '';
                $sanitized = $instance->sanitize($raw);
                update_term_meta($termId, $field['id'], $sanitized);
            }
        }
    }
}
