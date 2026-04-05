<?php
/**
 * Meta box registration, rendering, and save logic for post types.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Core;
use CMB\Core\Contracts\FieldInterface;
use CMB\Core\RenderContext\PostContext;
use CMB\Core\Storage\PostMetaStorage;
use CMB\Core\Storage\StorageInterface;

class MetaBoxManager {
    private array $metaBoxes = [];
    private static ?MetaBoxManager $instance = null;
    private array $validationErrors = [];
    private StorageInterface $storage;

    public function __construct( ?StorageInterface $storage = null ) {
        $this->storage = $storage ?? new PostMetaStorage();
    }

    public function getStorage(): StorageInterface {
        return $this->storage;
    }

    /**
     * Set the shared instance (called by Plugin::boot()).
     */
    public static function setInstance( self $instance ): void {
        self::$instance = $instance;
    }

    /**
     * Get the shared instance.
     * Prefer constructor injection for new code.
     */
    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a custom field type class (7.2).
     * Delegates to FieldFactory for centralized type registry.
     */
    public static function registerFieldType(string $type, string $className): void {
        FieldFactory::registerType($type, $className);
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
        // REST API integration (7.3)
        add_action('init', [$this, 'registerRestFields']);
    }

    public function add(
        string $id,
        string $title,
        array $postTypes,
        array $fields,
        string $context = 'advanced',
        string $priority = 'default'
    ): void {
        // Config validation (7.4)
        $this->validateFieldConfigs($fields, $id);
        $this->metaBoxes[$id] = compact('title', 'postTypes', 'fields', 'context', 'priority');
    }

    public function getMetaBoxes(): array {
        return $this->metaBoxes;
    }

    public function addMetaBoxes(): void {
        foreach ($this->metaBoxes as $id => $metaBox) {
            /**
             * Filters the meta box arguments before registration.
             *
             * @since 2.0
             *
             * @param array  $metaBox The meta box configuration (title, postTypes, fields, context, priority).
             * @param string $id      The meta box identifier.
             */
            $metaBox = FieldUtils::applyFilters('meta_box_args', $metaBox, $id);

            foreach ($metaBox['postTypes'] as $postType) {
                add_meta_box(
                    $id,
                    $metaBox['title'],
                    function ($post) use ($id, $metaBox) {
                        // Location rules check
                        if (!empty($metaBox['location']) && !LocationMatcher::matches($metaBox['location'], $post)) {
                            return;
                        }
                        $fieldRenderer = new FieldRenderer(new PostContext($post));
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
        echo '<ul class="cmb-tab-nav" role="tablist">';
        $first = true;
        foreach ($tabs as $tabId => $tab) {
            $active = $first ? ' cmb-tab-active' : '';
            $selected = $first ? 'true' : 'false';
            $tabIndex = $first ? '0' : '-1';
            echo '<li class="cmb-tab-nav-item' . $active . '" role="presentation" data-tab="' . esc_attr($tabId) . '">';
            echo '<a href="#cmb-tab-' . esc_attr($tabId) . '" role="tab" id="cmb-tab-trigger-' . esc_attr($tabId) . '" aria-selected="' . $selected . '" aria-controls="cmb-tab-' . esc_attr($tabId) . '" tabindex="' . $tabIndex . '">' . esc_html($tab['label'] ?? $tabId) . '</a>';
            echo '</li>';
            $first = false;
        }
        echo '</ul>';

        $first = true;
        foreach ($tabs as $tabId => $tab) {
            $active = $first ? ' cmb-tab-panel-active' : '';
            $hidden = $first ? '' : ' hidden';
            echo '<div class="cmb-tab-panel' . $active . '" id="cmb-tab-' . esc_attr($tabId) . '" role="tabpanel" aria-labelledby="cmb-tab-trigger-' . esc_attr($tabId) . '"' . $hidden . '>';
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

        $postType = get_post_type($postId);
        if (!$postType) return;

        foreach ($this->metaBoxes as $id => $metaBox) {
            if (!in_array($postType, $metaBox['postTypes'], true)) {
                continue;
            }
            $nonceField = 'cmb_nonce_' . $id;
            $nonceAction = 'cmb_save_' . $id;
            if (!isset($_POST[$nonceField]) || !wp_verify_nonce($_POST[$nonceField], $nonceAction)) {
                continue;
            }

            $fields = FieldUtils::flattenFields($metaBox['fields']);
            foreach ($fields as $field) {
                $this->saveField($postId, $field);
            }
        }
    }

    /**
     * Flatten fields from tabs or regular array into a single list.
     */

    private function saveField(int $postId, array $field): void {
        $fieldClass = $this->resolveFieldClass($field['type']);
        if (!$fieldClass) {
            return;
        }

        $fieldId = $field['id'];
        /** @var FieldInterface $instance */
        $instance = new $fieldClass($field);
        $raw = wp_unslash( $_POST[$fieldId] ?? '' );

        // Validate
        $errors = $instance->validate($raw);
        if (!empty($errors)) {
            $this->validationErrors = array_merge($this->validationErrors, $errors);
            return;
        }

        /**
         * Fires before a post meta field is saved.
         *
         * @since 2.0
         *
         * @param string $fieldId The field identifier / meta key.
         * @param mixed  $raw     The raw unsanitized value from $_POST.
         * @param int    $postId  The post ID.
         * @param array  $field   The field configuration array.
         */
        FieldUtils::doAction('before_save_field', $fieldId, $raw, $postId, $field);

        // Sanitize (support custom callback)
        if (!empty($field['sanitize_callback']) && is_callable($field['sanitize_callback'])) {
            $sanitized = call_user_func($field['sanitize_callback'], $raw);
        } else {
            $sanitized = $this->sanitizeFieldValue($instance, $field, $raw);
        }

        /**
         * Filters the sanitized value for a specific field type.
         *
         * The dynamic portion of the hook name, `$field['type']`, refers to
         * the field type (e.g. 'text', 'select', 'group').
         *
         * @since 2.0
         *
         * @param mixed  $sanitized The sanitized value.
         * @param mixed  $raw       The raw unsanitized value.
         * @param array  $field     The field configuration array.
         * @param int    $postId    The post ID.
         */
        $sanitized = FieldUtils::applyFilters('sanitize_' . $field['type'], $sanitized, $raw, $field, $postId);

        // Enforce max_rows
        if (is_array($sanitized) && isset($field['max_rows'])) {
            $sanitized = array_slice($sanitized, 0, (int)$field['max_rows']);
        }

        if (is_array($sanitized)) {
            $this->storage->delete($postId, $fieldId);
            foreach ($sanitized as $s) {
                add_post_meta($postId, $fieldId, $s);
            }
        } else {
            $this->storage->set($postId, $fieldId, $sanitized);
        }

        /**
         * Fires after a post meta field has been saved.
         *
         * @since 2.0
         *
         * @param string $fieldId   The field identifier / meta key.
         * @param mixed  $sanitized The sanitized value that was saved.
         * @param int    $postId    The post ID.
         * @param array  $field     The field configuration array.
         */
        FieldUtils::doAction('after_save_field', $fieldId, $sanitized, $postId, $field);
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
        return FieldFactory::resolveClass($type);
    }

    /**
     * Validate field configurations at registration time (7.4).
     */
    private function validateFieldConfigs(array $fields, string $metaBoxId): void {
        if (!empty($fields['tabs'])) {
            foreach ($fields['tabs'] as $tabId => $tab) {
                foreach ($tab['fields'] ?? [] as $field) {
                    $this->validateSingleFieldConfig($field, $metaBoxId);
                }
            }
            return;
        }

        foreach ($fields as $field) {
            $this->validateSingleFieldConfig($field, $metaBoxId);
        }
    }

    private function validateSingleFieldConfig(array $field, string $metaBoxId): void {
        if (empty($field['id'])) {
            if (function_exists('_doing_it_wrong')) {
                _doing_it_wrong(
                    __METHOD__,
                    sprintf('Meta box "%s" has a field without an "id" key. Every field requires a unique "id".', $metaBoxId),
                    '2.1'
                );
            }
        }
        if (empty($field['type'])) {
            if (function_exists('_doing_it_wrong')) {
                _doing_it_wrong(
                    __METHOD__,
                    sprintf('Meta box "%s" field "%s" is missing a "type" key. Supported types: text, textarea, number, select, checkbox, radio, etc.', $metaBoxId, $field['id'] ?? '(unknown)'),
                    '2.1'
                );
            }
        }
        // Validate sub-fields in groups
        if (($field['type'] ?? '') === 'group' && !empty($field['fields'])) {
            foreach ($field['fields'] as $subField) {
                $this->validateSingleFieldConfig($subField, $metaBoxId);
            }
        }
    }

    /**
     * Register fields for REST API (7.3).
     */
    public function registerRestFields(): void {
        foreach ($this->metaBoxes as $metaBox) {
            $fields = FieldUtils::flattenFields($metaBox['fields']);
            foreach ($fields as $field) {
                if (empty($field['show_in_rest'])) {
                    continue;
                }
                foreach ($metaBox['postTypes'] as $postType) {
                    $fieldCopy = $field;
                    $restArgs = [
                        'show_in_rest' => true,
                        'single' => empty($field['repeat']) && ($field['type'] ?? '') !== 'group',
                        'type' => $this->getRestType($field['type'] ?? 'text'),
                        'description' => $field['description'] ?? '',
                        'sanitize_callback' => function ($value) use ($fieldCopy) {
                            $instance = FieldFactory::create($fieldCopy['type'], $fieldCopy);
                            return $instance ? $instance->sanitize($value) : $value;
                        },
                    ];
                    register_post_meta($postType, $field['id'], $restArgs);
                }
            }
        }
    }

    private function getRestType(string $fieldType): string {
        return match ($fieldType) {
            'number' => 'number',
            'checkbox' => 'boolean',
            'group' => 'object',
            default => 'string',
        };
    }

    public function deletePostMetaData(int $postId): void {
        $postType = get_post_type( $postId );
        if ( ! $postType ) {
            return;
        }

        foreach ($this->metaBoxes as $metaBox) {
            if ( ! in_array( $postType, $metaBox['postTypes'], true ) ) {
                continue;
            }
            $fields = FieldUtils::flattenFields($metaBox['fields']);
            foreach ($fields as $field) {
                $this->storage->delete($postId, $field['id']);
            }
        }
    }

    // === Revision Support (6.8) ===
    public function copyMetaToRevision(int $revisionId): void {
        $parentId = function_exists('wp_is_post_revision') ? wp_is_post_revision($revisionId) : false;
        if (!$parentId) return;

        $fieldKeys = $this->getAllFieldKeys();
        if (empty($fieldKeys)) return;

        $allMeta = $this->storage->getAll($parentId);
        foreach ($fieldKeys as $key) {
            if (!isset($allMeta[$key])) continue;
            foreach ($allMeta[$key] as $value) {
                add_post_meta($revisionId, $key, $value);
            }
        }
    }

    public function restoreMetaFromRevision(int $postId, int $revisionId): void {
        $fieldKeys = $this->getAllFieldKeys();
        if (empty($fieldKeys)) return;

        $allMeta = $this->storage->getAll($revisionId);
        foreach ($fieldKeys as $key) {
            $this->storage->delete($postId, $key);
            if (!isset($allMeta[$key])) continue;
            foreach ($allMeta[$key] as $value) {
                add_post_meta($postId, $key, $value);
            }
        }
    }

    private function getAllFieldKeys(): array {
        $keys = [];
        foreach ($this->metaBoxes as $metaBox) {
            $fields = FieldUtils::flattenFields($metaBox['fields']);
            foreach ($fields as $field) {
                $keys[] = $field['id'];
            }
        }
        return array_unique($keys);
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
