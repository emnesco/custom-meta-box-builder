<?php
namespace CMB\Core;
use CMB\Core\Contracts\FieldInterface;

class MetaBoxManager {
    private array $metaBoxes = [];
    private static ?MetaBoxManager $instance = null;

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register(): void {
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post', [$this, 'saveMetaBoxData']);
        add_action('delete_post', [$this, 'deletePostMetaData']);
    }

    public function add(string $id, string $title, array $postTypes, array $fields): void {
        $this->metaBoxes[$id] = compact('title', 'postTypes', 'fields');
    }

    public function addMetaBoxes(): void {
        foreach ($this->metaBoxes as $id => $metaBox) {
            foreach ($metaBox['postTypes'] as $postType) {
                add_meta_box($id, $metaBox['title'], function ($post) use ($id, $metaBox) {
                    $fieldRenderer = new FieldRenderer($post);
                    echo '<div class="cmb-container cmb-fields">';
                    foreach ($metaBox['fields'] as $field) {
                        // Clone to avoid mutating original config
                        $fieldCopy = $field;
                        if (isset($fieldCopy['fields']) && $fieldCopy['type'] === 'group' && !isset($fieldCopy['repeat'])) {
                            $fieldCopy['repeat'] = true;
                            $fieldCopy['repeat_fake'] = true;
                        }
                        echo $fieldRenderer->render($fieldCopy);
                    }
                    echo '</div>';
                    wp_nonce_field('cmb_save_' . $id, 'cmb_nonce_' . $id);
                }, $postType);
            }
        }
    }

    public function saveMetaBoxData(int $postId): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $postId)) return;

        foreach ($this->metaBoxes as $id => $metaBox) {
            $nonceField = 'cmb_nonce_' . $id;
            $nonceAction = 'cmb_save_' . $id;
            if (!isset($_POST[$nonceField]) || !wp_verify_nonce($_POST[$nonceField], $nonceAction)) {
                continue;
            }

            foreach ($metaBox['fields'] as $field) {
                $this->saveField($postId, $field);
            }
        }
    }

    private function saveField(int $postId, array $field): void {
        $fieldClass = $this->resolveFieldClass($field['type']);
        if (!$fieldClass) {
            return;
        }

        $fieldId = $field['id'];
        /** @var FieldInterface $instance */
        $instance = new $fieldClass($field);
        $raw = $_POST[$fieldId] ?? '';
        $sanitized = $this->sanitizeFieldValue($instance, $field, $raw);

        delete_post_meta($postId, $fieldId);
        if (is_array($sanitized)) {
            foreach ($sanitized as $s) {
                add_post_meta($postId, $fieldId, $s);
            }
        } else {
            update_post_meta($postId, $fieldId, $sanitized);
        }
    }

    /**
     * Recursively sanitize field values, using proper field classes for nested groups.
     */
    private function sanitizeFieldValue(FieldInterface $instance, array $field, $raw) {
        if ($field['type'] === 'group' && is_array($raw) && !empty($field['fields'])) {
            return $this->sanitizeGroupValue($field, $raw);
        }
        return $instance->sanitize($raw);
    }

    /**
     * Sanitize group values by instantiating each sub-field's class.
     */
    private function sanitizeGroupValue(array $field, array $rawGroups): array {
        $sanitized = [];
        foreach ($rawGroups as $index => $groupData) {
            if (!is_array($groupData)) {
                continue;
            }
            $sanitizedGroup = [];
            foreach ($field['fields'] as $subField) {
                $subId = $subField['id'];
                $subRaw = $groupData[$subId] ?? '';
                $subClass = $this->resolveFieldClass($subField['type']);
                if (!$subClass) {
                    $sanitizedGroup[$subId] = sanitize_text_field(is_string($subRaw) ? $subRaw : '');
                    continue;
                }
                $subInstance = new $subClass($subField);
                if ($subField['type'] === 'group' && is_array($subRaw) && !empty($subField['fields'])) {
                    $sanitizedGroup[$subId] = $this->sanitizeGroupValue($subField, $subRaw);
                } else {
                    $sanitizedGroup[$subId] = $subInstance->sanitize($subRaw);
                }
            }
            $sanitized[$index] = $sanitizedGroup;
        }
        return $sanitized;
    }

    private function resolveFieldClass(string $type): ?string {
        $fieldClass = 'CMB\\Fields\\' . ucfirst($type) . 'Field';
        if (!class_exists($fieldClass)) {
            if (function_exists('_doing_it_wrong')) {
                _doing_it_wrong(
                    __METHOD__,
                    sprintf('CMB field type "%s" does not have a corresponding class "%s".', $type, $fieldClass),
                    '2.1'
                );
            }
            return null;
        }
        return $fieldClass;
    }

    public function deletePostMetaData(int $postId): void {
        foreach ($this->metaBoxes as $metaBox) {
            foreach ($metaBox['fields'] as $field) {
                delete_post_meta($postId, $field['id']);
            }
        }
    }
}
