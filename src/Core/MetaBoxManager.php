<?php
namespace CMB\Core;
use CMB\Core\Contracts\FieldInterface;

class MetaBoxManager {
    private array $metaBoxes = [];
    private static ?MetaBoxManager $instance = null;
    private array $validationErrors = [];

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
        add_action('admin_notices', [$this, 'showValidationErrors']);
        // Revision support
        add_action('wp_creating_autosave', [$this, 'copyMetaToRevision']);
        add_action('_wp_put_post_revision', [$this, 'copyMetaToRevision']);
        add_action('wp_restore_post_revision', [$this, 'restoreMetaFromRevision'], 10, 2);
    }

    public function add(
        string $id,
        string $title,
        array $postTypes,
        array $fields,
        string $context = 'advanced',
        string $priority = 'default'
    ): void {
        $this->metaBoxes[$id] = compact('title', 'postTypes', 'fields', 'context', 'priority');
    }

    public function getMetaBoxes(): array {
        return $this->metaBoxes;
    }

    public function addMetaBoxes(): void {
        foreach ($this->metaBoxes as $id => $metaBox) {
            foreach ($metaBox['postTypes'] as $postType) {
                add_meta_box(
                    $id,
                    $metaBox['title'],
                    function ($post) use ($id, $metaBox) {
                        $fieldRenderer = new FieldRenderer($post);
                        $hasTabs = !empty($metaBox['fields']['tabs']);

                        echo '<div class="cmb-container cmb-fields">';

                        if ($hasTabs) {
                            $this->renderTabs($fieldRenderer, $metaBox['fields']['tabs']);
                        } else {
                            foreach ($metaBox['fields'] as $field) {
                                $fieldCopy = $field;
                                if (isset($fieldCopy['fields']) && $fieldCopy['type'] === 'group' && !isset($fieldCopy['repeat'])) {
                                    $fieldCopy['repeat'] = true;
                                    $fieldCopy['repeat_fake'] = true;
                                }
                                echo $fieldRenderer->render($fieldCopy);
                            }
                        }

                        echo '</div>';
                        wp_nonce_field('cmb_save_' . $id, 'cmb_nonce_' . $id);
                    },
                    $postType,
                    $metaBox['context'],
                    $metaBox['priority']
                );
            }
        }
    }

    private function renderTabs(FieldRenderer $fieldRenderer, array $tabs): void {
        echo '<div class="cmb-tabs">';
        echo '<ul class="cmb-tab-nav">';
        $first = true;
        foreach ($tabs as $tabId => $tab) {
            $active = $first ? ' cmb-tab-active' : '';
            echo '<li class="cmb-tab-nav-item' . $active . '" data-tab="' . esc_attr($tabId) . '">';
            echo '<a href="#cmb-tab-' . esc_attr($tabId) . '">' . esc_html($tab['label'] ?? $tabId) . '</a>';
            echo '</li>';
            $first = false;
        }
        echo '</ul>';

        $first = true;
        foreach ($tabs as $tabId => $tab) {
            $active = $first ? ' cmb-tab-panel-active' : '';
            echo '<div class="cmb-tab-panel' . $active . '" id="cmb-tab-' . esc_attr($tabId) . '">';
            foreach ($tab['fields'] ?? [] as $field) {
                $fieldCopy = $field;
                if (isset($fieldCopy['fields']) && $fieldCopy['type'] === 'group' && !isset($fieldCopy['repeat'])) {
                    $fieldCopy['repeat'] = true;
                    $fieldCopy['repeat_fake'] = true;
                }
                echo $fieldRenderer->render($fieldCopy);
            }
            echo '</div>';
            $first = false;
        }
        echo '</div>';
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

            $fields = $this->flattenFields($metaBox['fields']);
            foreach ($fields as $field) {
                $this->saveField($postId, $field);
            }
        }
    }

    /**
     * Flatten fields from tabs or regular array into a single list.
     */
    private function flattenFields(array $fields): array {
        if (!empty($fields['tabs'])) {
            $flat = [];
            foreach ($fields['tabs'] as $tab) {
                foreach ($tab['fields'] ?? [] as $field) {
                    $flat[] = $field;
                }
            }
            return $flat;
        }
        return $fields;
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

        // Validate
        $errors = $instance->validate($raw);
        if (!empty($errors)) {
            $this->validationErrors = array_merge($this->validationErrors, $errors);
            return;
        }

        // Sanitize (support custom callback)
        if (!empty($field['sanitize_callback']) && is_callable($field['sanitize_callback'])) {
            $sanitized = call_user_func($field['sanitize_callback'], $raw);
        } else {
            $sanitized = $this->sanitizeFieldValue($instance, $field, $raw);
        }

        // Enforce max_rows
        if (is_array($sanitized) && isset($field['max_rows'])) {
            $sanitized = array_slice($sanitized, 0, (int)$field['max_rows']);
        }

        delete_post_meta($postId, $fieldId);
        if (is_array($sanitized)) {
            foreach ($sanitized as $s) {
                add_post_meta($postId, $fieldId, $s);
            }
        } else {
            update_post_meta($postId, $fieldId, $sanitized);
        }
    }

    private function sanitizeFieldValue(FieldInterface $instance, array $field, mixed $raw): mixed {
        if ($field['type'] === 'group' && is_array($raw) && !empty($field['fields'])) {
            return $this->sanitizeGroupValue($field, $raw);
        }
        return $instance->sanitize($raw);
    }

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

                if (!empty($subField['sanitize_callback']) && is_callable($subField['sanitize_callback'])) {
                    $sanitizedGroup[$subId] = call_user_func($subField['sanitize_callback'], $subRaw);
                } elseif ($subField['type'] === 'group' && is_array($subRaw) && !empty($subField['fields'])) {
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
            $fields = $this->flattenFields($metaBox['fields']);
            foreach ($fields as $field) {
                delete_post_meta($postId, $field['id']);
            }
        }
    }

    // === Revision Support (6.8) ===
    public function copyMetaToRevision(int $revisionId): void {
        $parentId = function_exists('wp_is_post_revision') ? wp_is_post_revision($revisionId) : false;
        if (!$parentId) return;

        foreach ($this->metaBoxes as $metaBox) {
            $fields = $this->flattenFields($metaBox['fields']);
            foreach ($fields as $field) {
                $values = get_post_meta($parentId, $field['id']);
                foreach ($values as $value) {
                    add_post_meta($revisionId, $field['id'], $value);
                }
            }
        }
    }

    public function restoreMetaFromRevision(int $postId, int $revisionId): void {
        foreach ($this->metaBoxes as $metaBox) {
            $fields = $this->flattenFields($metaBox['fields']);
            foreach ($fields as $field) {
                delete_post_meta($postId, $field['id']);
                $values = get_post_meta($revisionId, $field['id']);
                foreach ($values as $value) {
                    add_post_meta($postId, $field['id'], $value);
                }
            }
        }
    }

    public function showValidationErrors(): void {
        if (empty($this->validationErrors)) {
            return;
        }
        echo '<div class="notice notice-error is-dismissible"><p><strong>Meta Box Validation Errors:</strong></p><ul>';
        foreach ($this->validationErrors as $error) {
            echo '<li>' . esc_html($error) . '</li>';
        }
        echo '</ul></div>';
        $this->validationErrors = [];
    }
}
