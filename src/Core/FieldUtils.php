<?php
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
}
