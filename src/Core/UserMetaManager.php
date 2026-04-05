<?php
namespace CMB\Core;

class UserMetaManager {
    private array $fields = [];

    public function add(array $fields): void {
        $this->fields = array_merge($this->fields, $fields);
    }

    public function register(): void {
        add_action('show_user_profile', [$this, 'renderFields']);
        add_action('edit_user_profile', [$this, 'renderFields']);
        add_action('personal_options_update', [$this, 'saveFields']);
        add_action('edit_user_profile_update', [$this, 'saveFields']);
    }

    public function renderFields($user): void {
        if (empty($this->fields)) return;

        echo '<h2>Additional Information</h2>';
        echo '<table class="form-table">';

        wp_nonce_field('cmb_user_save', 'cmb_user_nonce');

        foreach ($this->fields as $field) {
            $value = get_user_meta($user->ID, $field['id'], true);

            echo '<tr>';
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

        echo '</table>';
    }

    public function saveFields(int $userId): void {
        if (!isset($_POST['cmb_user_nonce']) || !wp_verify_nonce($_POST['cmb_user_nonce'], 'cmb_user_save')) {
            return;
        }
        if (!current_user_can('edit_user', $userId)) {
            return;
        }

        foreach ($this->fields as $field) {
            $fieldClass = 'CMB\\Fields\\' . ucfirst($field['type']) . 'Field';
            if (!class_exists($fieldClass)) continue;

            $instance = new $fieldClass($field);
            $raw = $_POST[$field['id']] ?? '';
            $sanitized = $instance->sanitize($raw);
            update_user_meta($userId, $field['id'], $sanitized);
        }
    }
}
