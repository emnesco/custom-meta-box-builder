<?php
namespace CMB\Core;

use CMB\Core\Contracts\FieldInterface;

class MetaBoxManager {
    private array $metaBoxes = [];

    public function register(): void {
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post', [$this, 'saveMetaBoxData']);
    }

    public function add(string $id, string $title, array $postTypes, array $fields): void {
        $this->metaBoxes[$id] = compact('title', 'postTypes', 'fields');
    }

    public function addMetaBoxes(): void {
        foreach ($this->metaBoxes as $id => $metaBox) {
            foreach ($metaBox['postTypes'] as $postType) {
                add_meta_box($id, $metaBox['title'], function ($post) use ($metaBox) {
                    $fieldRenderer = new FieldRenderer($post);
                    echo '<div class="cmb-container cmb-fields">';
                    foreach ($metaBox['fields'] as $field) {
                        echo $fieldRenderer->render($field);
                    }
                    echo '</div>';
                    wp_nonce_field('cmb_nonce', 'cmb_nonce');
                }, $postType);
            }
        }
    }





    
    public function saveMetaBoxData(int $postId): void {
        if (!isset($_POST['cmb_nonce']) || !wp_verify_nonce($_POST['cmb_nonce'], 'cmb_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;



        foreach ($this->metaBoxes as $meta_box) {
            foreach ($meta_box['fields'] as $field) {
                $this->save_field_data($postId, $field);
            }
        }

    }





    /**
     * Save individual field data, supports group fields.
     */
    private function save_field_data($post_id, $field, $parent_name = '') {
        $input_name = $parent_name ? $parent_name . '[' . $field['id'] . ']' : $field['id'];


            if ($field['type'] !== 'group' && (isset($field['repeat']) && $field['repeat'] === true)) {

                
            delete_post_meta($post_id, $field['id']);

                foreach ($_POST['test-repeat-text'] as $key => $valuec) {

dump($valuec );

if($valuec){

                    add_post_meta($post_id, $field['id'], $valuec);

}
                }

            }

        elseif ($field['type'] === 'group' ) {
            $values = $_POST[$field['id']] ?? array();
            $sanitized = array();

            foreach ($values as $index => $group) {
                $sanitized_group = array();
                foreach ($field['fields'] as $sub_field) {
                    $sanitized_group[$sub_field['id']] = $this->sanitize_field(
                        $sub_field,
                        $group[$sub_field['id']] ?? '',
                        $input_name . '[' . $index . ']'
                    );
                }
                $sanitized[] = $sanitized_group;
            }

            delete_post_meta($post_id, $field['id']);
            foreach ($sanitized as $key => $value) {
                add_post_meta($post_id, $field['id'], $value);
            }

        } else {
            $value = $_POST[$field['id']] ?? '';
            update_post_meta($post_id, $field['id'], $this->sanitize_field($field, $value));
        }
    }

    /**
     * Sanitize field value based on type.
     */
    private function sanitize_field($field, $value, $parent_name = '') {
        switch ($field['type']) {
            case 'text':
                return sanitize_text_field($value);
            case 'textarea':
                return sanitize_textarea_field($value);
            case 'select':
                return array_key_exists($value, $field['options']) ? $value : '';
            case 'group':
                return array_map(function($group) use ($field) {
                    $sanitized_group = array();
                    if (!is_array($group)) return array();
                    foreach ($field['fields'] as $sub_field) {
                        if (!isset($group[$sub_field['id']])) continue;
                        $sanitized_group[$sub_field['id']] = $this->sanitize_field(
                            $sub_field,
                            $group[$sub_field['id']]
                        );
                    }
                    return $sanitized_group;
                }, (array) $value);
            default:
                return sanitize_text_field($value);
        }
    }



}