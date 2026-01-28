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
                        if(isset($field['fields']) && $field['type']==='group' && !isset($field['repeat']) ){
                            $field['repeat'] = true;
                            $field['repeat_fake'] = true;
                        }
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
        foreach ($this->metaBoxes as $metaBox) {
            foreach ($metaBox['fields'] as $field) {
                $fieldClass = 'CMB\\Fields\\' . ucfirst($field['type']) . 'Field';
                if (class_exists($fieldClass)) {
                     /** @var FieldInterface $instance */
                    $instance = new $fieldClass($field);
                    $fieldId = $field['id'];
                    $sanitized = $instance->sanitize($_POST[$fieldId] ?? '');
                    delete_post_meta($postId, $fieldId); 
                    if (is_array($sanitized)) {
                        foreach ($sanitized as $s) {
                             add_post_meta($postId, $fieldId, $s);
                        }
                    } else {
                         update_post_meta($postId, $fieldId, $sanitized);
                    }
                }
            }
        }
    }
}