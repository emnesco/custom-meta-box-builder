<?php
declare(strict_types=1);

/**
 * Renders individual fields with layout, conditionals, validation, and multilingual support.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\FieldInterface;
use CMB\Core\RenderContext\RenderContextInterface;
use CMB\Core\RenderContext\PostContext;
use CMB\Core\Traits\MultiLanguageTrait;

class FieldRenderer implements Contracts\FieldRendererInterface {
    use MultiLanguageTrait;

    protected RenderContextInterface $context;
    private ?array $metaCache = null;

    public function __construct(RenderContextInterface|\WP_Post $context) {
        // Back-compat: allow a bare WP_Post to be passed (wraps it in PostContext).
        if ($context instanceof \WP_Post) {
            $this->context = new PostContext($context);
        } else {
            $this->context = $context;
        }
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
        /**
         * Filters the field configuration array before rendering.
         *
         * @since 2.0
         *
         * @param array    $field    The field configuration array.
         * @param int|string $objectId The current object ID (post, term, user, or option name).
         */
        $field = FieldUtils::applyFilters('field_config', $field, $this->context->getObjectId());

        $name = $this->getname($field, $index, $parent);

        if (!$parent) {
            $value = $this->get_field_value($field);
        }

        $parent_is_array = is_array($parent);

        $htmlId = $this->generateHtmlId($name);

        $mergedConfig = array_merge($field, [
            'id' => $name,
            'name' => $name,
            'html_id' => $htmlId,
            'value' => $value,
        ]);
        if ($field['type'] === 'group') {
            $mergedConfig['_renderer'] = $this;
        }

        /** @var FieldInterface|null $instance */
        $instance = FieldFactory::create($field['type'], $mergedConfig);
        if ( null === $instance ) {
            return '';
        }

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
            if (isset($cond['groups'])) {
                // AND/OR group format
                $conditionalAttrs .= ' data-conditional-groups="' . esc_attr(wp_json_encode($cond['groups'])) . '"';
                $conditionalAttrs .= ' data-conditional-relation="' . esc_attr($cond['relation'] ?? 'OR') . '"';
            } else {
                // Simple format (single condition)
                $conditionalAttrs .= ' data-conditional-field="' . esc_attr($cond['field'] ?? '') . '"';
                $conditionalAttrs .= ' data-conditional-operator="' . esc_attr($cond['operator'] ?? '==') . '"';
                $conditionalAttrs .= ' data-conditional-value="' . esc_attr($cond['value'] ?? '') . '"';
            }
            $conditionalAttrs .= ' style="display:none"';
        }

        // Multilingual rendering (8.3)
        if ($this->isMultilingual($field) && !$parent) {
            return $this->renderMultilingualField($field, $name, $htmlId);
        }

        // Resolve the object to pass to hooks — WP_Post for post context, objectId otherwise.
        $hookObject = $this->getHookObject();

        /**
         * Fires before a field is rendered.
         *
         * @since 2.0
         *
         * @param array          $field      The field configuration array.
         * @param \WP_Post|int|string $hookObject The post object (post context) or object ID.
         */
        FieldUtils::doAction('before_render_field', $field, $hookObject);

        // Validation data attributes for client-side validation
        $validationAttrs = '';
        if (!empty($field['required'])) {
            $validationAttrs .= ' data-validate-required="true"';
        }
        if (!empty($field['validation'])) {
            foreach ((array) $field['validation'] as $rule) {
                if (strpos($rule, ':') !== false) {
                    [$ruleName, $ruleVal] = explode(':', $rule, 2);
                    $validationAttrs .= ' data-validate-' . esc_attr($ruleName) . '="' . esc_attr($ruleVal) . '"';
                }
            }
        }

        $output = '<div class="cmb-field ' . esc_attr($layout) . ' cmb-type-' . esc_attr($field['type']) . ' ' . esc_attr($repeat) . ' ' . esc_attr($width) . ' ' . esc_attr($required_class) . '"' . $conditionalAttrs . $validationAttrs . '>';
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
                    $output .= '<button type="button" class="cmb-expand-all">' . esc_html__('Expand All', 'custom-meta-box-builder') . '</button>';
                    $output .= '<button type="button" class="cmb-collapse-all">' . esc_html__('Collapse All', 'custom-meta-box-builder') . '</button>';
                    $output .= '</div>';
                }
                // Search/filter for large groups (8.4)
                if ($field['type'] === 'group' && !empty($field['searchable'])) {
                    $searchId = $htmlId . '-search';
                    $output .= '<div class="cmb-group-search">';
                    $output .= '<label for="' . esc_attr($searchId) . '" class="screen-reader-text">' . esc_html__('Search items', 'custom-meta-box-builder') . '</label>';
                    $output .= '<input id="' . esc_attr($searchId) . '" type="text" placeholder="' . esc_attr__('Search items...', 'custom-meta-box-builder') . '" class="cmb-group-search-input">';
                    $output .= '</div>';
                }
                /**
                 * Fires before a specific field type is rendered.
                 *
                 * @since 2.2
                 *
                 * @param array          $field      The field configuration.
                 * @param FieldInterface $instance   The field instance.
                 * @param mixed          $hookObject The post/object.
                 */
                FieldUtils::doAction('render_' . $field['type'], $field, $instance, $hookObject);

                try {
                    $fieldHtml = $instance->render();

                    /**
                     * Filters the rendered output of a specific field type.
                     *
                     * @since 2.2
                     *
                     * @param string         $fieldHtml  The field HTML.
                     * @param array          $field      The field configuration.
                     * @param mixed          $hookObject The post/object.
                     */
                    $output .= FieldUtils::applyFilters('render_' . $field['type'] . '_html', $fieldHtml, $field, $hookObject);
                } catch (\Throwable $e) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        _doing_it_wrong(
                            'FieldRenderer::render',
                            sprintf('Field "%s" threw an error: %s', $field['id'] ?? 'unknown', $e->getMessage()),
                            '2.1'
                        );
                    }
                    $output .= '<!-- CMB field render error -->';
                }
                if ($has_field_repeat && empty($field['repeat_fake'])) {
                    $dataAttrs = '';
                    if (isset($field['min_rows'])) {
                        $dataAttrs .= ' data-min-rows="' . (int)$field['min_rows'] . '"';
                    }
                    if (isset($field['max_rows'])) {
                        $dataAttrs .= ' data-max-rows="' . (int)$field['max_rows'] . '"';
                    }
                    $output .= '<button type="button" class="cmb-add-row"' . $dataAttrs . '>' . esc_html__('Add Row', 'custom-meta-box-builder') . '</button>';
                    $output .= ' <span class="cmb-item-count"></span>';
                }
                if (!empty($field['description'])) {
                    $output .= '<p class="cmb-description">' . esc_html($field['description']) . '</p>';
                }
            $output .= '</div>';
         $output .= '</div>';

        /**
         * Filters the complete HTML output of a rendered field.
         *
         * @since 2.0
         *
         * @param string         $output     The field HTML markup.
         * @param array          $field      The field configuration array.
         * @param \WP_Post|int|string $hookObject The post object (post context) or object ID.
         */
        $output = FieldUtils::applyFilters('field_html', $output, $field, $hookObject);

        /**
         * Fires after a field is rendered.
         *
         * @since 2.0
         *
         * @param array          $field      The field configuration array.
         * @param \WP_Post|int|string $hookObject The post object (post context) or object ID.
         */
        FieldUtils::doAction('after_render_field', $field, $hookObject);

        return $output;
    }

    /**
     * Render a multilingual field with language tabs (8.3).
     */
    private function renderMultilingualField(array $field, string $name, string $htmlId): string {
        $locales = $this->getFieldLocales($field);
        $currentLocale = $this->getCurrentLocale();
        $layout = isset($field['layout']) ? 'cmb-' . $field['layout'] : 'cmb-horizontal';

        $output = '<div class="cmb-field ' . esc_attr($layout) . ' cmb-type-' . esc_attr($field['type']) . ' cmb-multilingual">';
        $output .= '<div class="cmb-label">';
        $output .= '<label>' . esc_html($field['label'] ?? '') . '</label>';
        $output .= '</div>';
        $output .= '<div class="cmb-input">';
        $output .= $this->renderLanguageTabs($field['id'], $locales, $currentLocale);

        foreach ($locales as $locale) {
            $localizedKey = $this->getLocalizedKey($field['id'], $locale);
            $localizedValue = $this->get_field_value(array_merge($field, ['id' => $localizedKey]));
            $active = ($locale === $currentLocale) ? ' cmb-lang-panel-active' : '';

            $instance = FieldFactory::create($field['type'], array_merge($field, [
                'id' => $localizedKey,
                'name' => $localizedKey,
                'html_id' => $htmlId . '-' . $locale,
                'value' => $localizedValue,
            ]));
            if ( null === $instance ) {
                continue;
            }

            $tabId = 'cmb-lang-tab-' . esc_attr($field['id']) . '-' . esc_attr($locale);
            $panelId = 'cmb-lang-panel-' . esc_attr($field['id']) . '-' . esc_attr($locale);
            $output .= '<div class="cmb-lang-panel' . $active . '" id="' . $panelId . '" data-lang="' . esc_attr($locale) . '" role="tabpanel" aria-labelledby="' . $tabId . '">';
            $output .= $instance->render();
            $output .= '</div>';
        }

        $output .= $this->closeLanguageTabs();

        if (!empty($field['description'])) {
            $output .= '<p class="cmb-description">' . esc_html($field['description']) . '</p>';
        }
        $output .= '</div></div>';

        return $output;
    }

    /**
     * Return the object to pass to hooks.
     * For post context, returns WP_Post for back-compatibility.
     * For other contexts, returns the object ID.
     */
    private function getHookObject(): mixed {
        if ($this->context instanceof PostContext) {
            return $this->context->getPost();
        }
        return $this->context->getObjectId();
    }

    /**
     * Retrieve a field value via the context storage.
     *
     * For option context, OptionStorage::getAll() returns [] (options are keyed by
     * option name rather than a single object), so we fall back to get_option()
     * on a per-key basis which OptionStorage::get() already handles correctly.
     */
    private function get_field_value(array $field): mixed {
        $objectId = $this->context->getObjectId();
        $storage  = $this->context->getStorage();
        $key      = $field['id'];
        $isCollection = ($field['type'] === 'group') || !empty($field['repeat']);

        // Populate cache on first call.
        // OptionStorage::getAll() intentionally returns [] because WP options are
        // independent keys; in that case we skip the cache and always call get().
        if ($this->metaCache === null) {
            $all = $storage->getAll($objectId);
            $this->metaCache = is_array($all) ? $all : [];
        }

        if ($isCollection) {
            // If metaCache has data for this key, use it.
            if (!empty($this->metaCache[$key])) {
                $meta = $this->metaCache[$key];
                return array_map(function ($v) {
                    return self::safeUnserialize($v);
                }, $meta);
            }

            // For option context (empty cache) or missing key, fall back to storage->get().
            if (empty($this->metaCache)) {
                $val = $storage->get($objectId, $key, false);
                if (!empty($val)) {
                    return is_array($val) ? $val : [$val];
                }
            }

            return $field['type'] === 'group' ? [[]] : [''];
        }

        // Scalar field.
        if (!empty($this->metaCache[$key])) {
            $meta = $this->metaCache[$key];
            $val  = $meta[0] ?? null;
            $val  = self::safeUnserialize($val);
        } else {
            // Option context or key not yet in cache — call storage directly.
            $val = $storage->get($objectId, $key);
            $val = self::safeUnserialize($val);
        }

        /**
         * Filters a field value after retrieval from storage.
         *
         * @since 2.0
         *
         * @param mixed      $val      The retrieved field value.
         * @param string     $key      The field key / meta key.
         * @param int|string $objectId The object ID (post, term, user, or option name).
         */
        return FieldUtils::applyFilters('field_value', $val, $key, $objectId);
    }

    /**
     * Safely unserialize a value, rejecting PHP objects to prevent object injection.
     */
    private static function safeUnserialize( mixed $value ): mixed {
        if ( ! is_string( $value ) || ! is_serialized( $value ) ) {
            return $value;
        }

        $unserialized = maybe_unserialize( $value );

        // Reject objects — only arrays and scalars are safe for meta values.
        if ( is_object( $unserialized ) ) {
            return $value; // Return original string rather than instantiated object.
        }

        // Recursively check arrays for nested objects.
        if ( is_array( $unserialized ) ) {
            array_walk_recursive( $unserialized, function ( &$item ) use ( $value ) {
                if ( is_object( $item ) ) {
                    $item = null;
                }
            });
        }

        return $unserialized;
    }
}
