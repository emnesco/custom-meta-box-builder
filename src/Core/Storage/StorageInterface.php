<?php
/**
 * Interface for meta/option storage abstraction.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Core\Storage;

/**
 * Storage abstraction for meta/option persistence.
 */
interface StorageInterface {
    public function get( int|string $objectId, string $key, bool $single = true ): mixed;
    public function set( int|string $objectId, string $key, mixed $value ): bool;
    public function delete( int|string $objectId, string $key ): bool;
    public function getAll( int|string $objectId ): array;
}
