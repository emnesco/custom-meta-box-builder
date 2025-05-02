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
                    $renderer = new FieldRenderer($post);
                    echo '<div class="cmb-container cmb-fields inside">';
                    foreach ($metaBox['fields'] as $field) {
                        echo $renderer->render($field);
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
                    $sanitized = $instance->sanitize($_POST[$field['id']] ?? '');
                    update_post_meta($postId, $field['id'], $sanitized);
                }
            }
        }
    }
}