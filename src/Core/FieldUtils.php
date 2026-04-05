<?php
/**
 * Utility functions for field configuration analysis.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Core;

/**
 * Shared utility methods for field operations.
 */
class FieldUtils {
    /**
     * Flatten tabbed field structures into a single-level array.
     *
     * If the fields array contains a 'tabs' key, extracts all fields
     * from each tab. Otherwise returns the fields array unchanged.
     */
    public static function flattenFields( array $fields ): array {
        if ( empty( $fields['tabs'] ) ) {
            return $fields;
        }

        $flat = [];
        foreach ( $fields['tabs'] as $tab ) {
            foreach ( $tab['fields'] ?? [] as $field ) {
                $flat[] = $field;
            }
        }
        return $flat;
    }

    /**
     * Fire an action with both the new cmbbuilder_ prefix and deprecated cmb_ prefix.
     *
     * @since 2.1
     *
     * @param string $hookSuffix The hook name without prefix (e.g. 'before_save_field').
     * @param mixed  ...$args    Arguments to pass to the hook.
     */
    public static function doAction( string $hookSuffix, mixed ...$args ): void {
        do_action( 'cmbbuilder_' . $hookSuffix, ...$args );
        do_action( 'cmb_' . $hookSuffix, ...$args );
    }

    /**
     * Apply filters with both the new cmbbuilder_ prefix and deprecated cmb_ prefix.
     *
     * The new prefix runs first. Then the deprecated prefix runs on its result.
     *
     * @since 2.1
     *
     * @param string $hookSuffix The hook name without prefix (e.g. 'field_config').
     * @param mixed  $value      The value to filter.
     * @param mixed  ...$args    Additional arguments.
     * @return mixed The filtered value.
     */
    public static function applyFilters( string $hookSuffix, mixed $value, mixed ...$args ): mixed {
        $value = apply_filters( 'cmbbuilder_' . $hookSuffix, $value, ...$args );
        return apply_filters( 'cmb_' . $hookSuffix, $value, ...$args );
    }
}
