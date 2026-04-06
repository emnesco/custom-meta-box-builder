<?php
declare(strict_types=1);

/**
 * Interface for field rendering services.
 *
 * Breaks the circular dependency GroupField -> FieldRenderer -> FieldFactory -> GroupField
 * by allowing GroupField to depend on this abstraction instead of the concrete FieldRenderer.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Contracts;

defined( 'ABSPATH' ) || exit;

interface FieldRendererInterface {
    /**
     * Render a single field.
     *
     * @param array $field  The field configuration array.
     * @param mixed $value  The current field value.
     * @param int   $index  The repeater row index.
     * @param mixed $parent The parent field or prefix string.
     * @return string The rendered HTML.
     */
    public function render(array $field, mixed $value = null, int $index = 0, mixed $parent = []): string;

    /**
     * Get the child name prefix for a sub-field within a group.
     *
     * @param string $name  The parent field name.
     * @param array  $field The parent field configuration.
     * @param int    $index The repeater row index.
     * @return string The child prefix.
     */
    public function getChildPrefix(string $name, array $field, int $index): string;
}
