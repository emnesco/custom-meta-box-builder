<?php
namespace CMB\Core;

use CMB\Core\RenderContext\TermContext;
use CMB\Core\Storage\StorageInterface;
use CMB\Core\Storage\TermMetaStorage;

class TaxonomyMetaManager {
    private array $taxonomyBoxes = [];
    private StorageInterface $storage;

    public function __construct( ?StorageInterface $storage = null ) {
        $this->storage = $storage ?? new TermMetaStorage();
    }

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

        wp_nonce_field('cmb_taxonomy_save_' . $taxonomy, 'cmb_taxonomy_nonce');

        if (is_object($term) && !empty($term->term_id)) {
            $context       = new TermContext((int) $term->term_id, $this->storage);
            $fieldRenderer = new FieldRenderer($context);

            foreach ($fields as $field) {
                echo '<tr class="form-field">';
                echo '<th><label for="cmb-' . esc_attr($field['id']) . '">' . esc_html($field['label'] ?? '') . '</label></th>';
                echo '<td>';
                echo $fieldRenderer->render($field);
                echo '</td></tr>';
            }
        } else {
            // Term not yet saved — render defaults without FieldRenderer (no ID available).
            foreach ($fields as $field) {
                echo '<tr class="form-field">';
                echo '<th><label for="' . esc_attr($field['id']) . '">' . esc_html($field['label'] ?? '') . '</label></th>';
                echo '<td>';

                $instance = FieldFactory::create($field['type'], array_merge($field, [
                    'name'    => $field['id'],
                    'html_id' => $field['id'],
                    'value'   => $field['default'] ?? '',
                ]));
                if ( $instance ) {
                    echo $instance->render();
                }

                if (!empty($field['description'])) {
                    echo '<p class="description">' . esc_html($field['description']) . '</p>';
                }
                echo '</td></tr>';
            }
        }
    }

    public function renderAddFields($taxonomy): void {
        $fields = $this->taxonomyBoxes[$taxonomy] ?? [];
        if (empty($fields)) return;

        wp_nonce_field('cmb_taxonomy_save_' . $taxonomy, 'cmb_taxonomy_nonce');

        // "Add term" form has no term_id yet, so use a zero-ID TermContext.
        // get_term_meta(0, ...) will return empty values, which is the correct
        // behaviour — fields render with their defaults.
        $context       = new TermContext(0, $this->storage);
        $fieldRenderer = new FieldRenderer($context);

        foreach ($fields as $field) {
            // Apply default so the field has a sensible initial value.
            $fieldWithDefault = $field;
            if (!isset($fieldWithDefault['default'])) {
                $fieldWithDefault['default'] = '';
            }

            echo '<div class="form-field">';
            echo '<label for="cmb-' . esc_attr($field['id']) . '">' . esc_html($field['label'] ?? '') . '</label>';
            echo $fieldRenderer->render($fieldWithDefault);
            echo '</div>';
        }
    }

    public function saveFields(int $termId): void {
        if (!current_user_can('edit_term', $termId)) {
            return;
        }

        foreach ($this->taxonomyBoxes as $taxonomy => $fields) {
            if (!isset($_POST['cmb_taxonomy_nonce']) || !wp_verify_nonce($_POST['cmb_taxonomy_nonce'], 'cmb_taxonomy_save_' . $taxonomy)) {
                continue;
            }
            foreach ($fields as $field) {
                $instance = FieldFactory::create($field['type'], $field);
                if ( $instance === null ) {
                    continue;
                }
                $raw = wp_unslash( $_POST[$field['id']] ?? '' );
                do_action('cmb_before_save_taxonomy_field', $field['id'], $raw, $termId, $field);
                $sanitized = $instance->sanitize($raw);
                $this->storage->set($termId, $field['id'], $sanitized);
                do_action('cmb_after_save_taxonomy_field', $field['id'], $sanitized, $termId, $field);
            }
        }
    }
}
