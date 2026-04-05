<?php
/**
 * User meta storage implementation.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Core\Storage;

class UserMetaStorage implements StorageInterface {
    public function get( int|string $objectId, string $key, bool $single = true ): mixed {
        return get_user_meta( (int) $objectId, $key, $single );
    }

    public function set( int|string $objectId, string $key, mixed $value ): bool {
        return (bool) update_user_meta( (int) $objectId, $key, $value );
    }

    public function delete( int|string $objectId, string $key ): bool {
        return delete_user_meta( (int) $objectId, $key );
    }

    public function getAll( int|string $objectId ): array {
        $meta = get_user_meta( (int) $objectId );
        return is_array( $meta ) ? $meta : [];
    }
}
