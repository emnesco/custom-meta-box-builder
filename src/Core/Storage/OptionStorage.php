<?php
declare(strict_types=1);

/**
 * WordPress options storage implementation.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Storage;

defined( 'ABSPATH' ) || exit;

class OptionStorage implements StorageInterface {
    public function get( int|string $objectId, string $key, bool $single = true ): mixed {
        return get_option( $key, null );
    }

    public function set( int|string $objectId, string $key, mixed $value ): bool {
        return update_option( $key, $value );
    }

    public function delete( int|string $objectId, string $key ): bool {
        return delete_option( $key );
    }

    public function getAll( int|string $objectId ): array {
        return [];
    }
}
