<?php
declare(strict_types=1);

/**
 * Abstract base class for meta managers (taxonomy, user, options).
 *
 * Provides shared rendering and save logic to reduce duplication
 * across TaxonomyMetaManager, UserMetaManager, and OptionsManager.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Contracts\Abstracts;

defined( 'ABSPATH' ) || exit;

use CMB\Core\FieldFactory;
use CMB\Core\FieldRenderer;
use CMB\Core\FieldUtils;
use CMB\Core\RenderContext\RenderContextInterface;
use CMB\Core\Storage\StorageInterface;

abstract class AbstractMetaManager {
    protected StorageInterface $storage;

    /**
     * Register WordPress hooks for this meta manager.
     */
    abstract public function register(): void;

    /**
     * Render fields using a FieldRenderer within a given context.
     *
     * @param array                  $fields  The field configurations.
     * @param RenderContextInterface $context The render context.
     * @param string                 $wrapper The HTML wrapper format ('table_row' or 'div').
     */
    protected function renderFieldSet(array $fields, RenderContextInterface $context, string $wrapper = 'table_row'): void {
        $fieldRenderer = new FieldRenderer($context);

        foreach ($fields as $field) {
            if ($wrapper === 'table_row') {
                echo '<tr class="form-field">';
                echo '<th><label for="cmb-' . esc_attr($field['id']) . '">' . esc_html($field['label'] ?? '') . '</label></th>';
                echo '<td>';
                echo $fieldRenderer->render($field);
                echo '</td></tr>';
            } else {
                echo '<div class="form-field">';
                echo '<label for="cmb-' . esc_attr($field['id']) . '">' . esc_html($field['label'] ?? '') . '</label>';
                echo $fieldRenderer->render($field);
                echo '</div>';
            }
        }
    }

    /**
     * Save an array of fields for a given object ID.
     *
     * @param array  $fields   The field configurations.
     * @param int    $objectId The object ID (term, user, etc.).
     * @param string $hookPrefix The hook prefix for before/after save actions (e.g. 'taxonomy', 'user').
     */
    protected function saveFieldSet(array $fields, int $objectId, string $hookPrefix): void {
        foreach ($fields as $field) {
            $instance = FieldFactory::create($field['type'], $field);
            if (null === $instance) {
                continue;
            }
            $raw = wp_unslash($_POST[$field['id']] ?? '');

            /** Fires before a meta field is saved. */
            FieldUtils::doAction('before_save_' . $hookPrefix . '_field', $field['id'], $raw, $objectId, $field);

            $sanitized = $instance->sanitize($raw);
            $this->storage->set($objectId, $field['id'], $sanitized);

            /** Fires after a meta field has been saved. */
            FieldUtils::doAction('after_save_' . $hookPrefix . '_field', $field['id'], $sanitized, $objectId, $field);
        }
    }
}
