<?php
declare(strict_types=1);

/**
 * Interface for local JSON sync of field group configurations.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Contracts;

defined( 'ABSPATH' ) || exit;

interface LocalJsonInterface {
    /**
     * Get the JSON save directory path.
     *
     * @return string
     */
    public static function getSavePath(): string;

    /**
     * Get all JSON load paths.
     *
     * @return string[]
     */
    public static function getLoadPaths(): array;

    /**
     * Save a field group config to a JSON file.
     *
     * @param string $groupId The field group ID.
     * @param array  $config  The field group configuration.
     * @return bool True on success.
     */
    public static function save(string $groupId, array $config): bool;

    /**
     * Delete a JSON file for a field group.
     *
     * @param string $groupId The field group ID.
     * @return bool True on success.
     */
    public static function delete(string $groupId): bool;

    /**
     * Load all JSON configs from the load paths.
     *
     * @return array<string, array> Keyed by group ID.
     */
    public static function loadAll(): array;

    /**
     * Sync JSON files with database configs.
     */
    public static function sync(): void;

    /**
     * Register hooks for auto-sync.
     */
    public static function register(): void;
}
