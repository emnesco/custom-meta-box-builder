<?php
declare(strict_types=1);

/**
 * Interface for import/export of meta box configurations.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Contracts;

defined( 'ABSPATH' ) || exit;

interface ImportExportInterface {
    /**
     * Export configuration programmatically as JSON.
     *
     * @return string JSON-encoded configuration.
     */
    public function exportToJson(): string;

    /**
     * Import configuration programmatically from JSON.
     *
     * @param string $json The JSON string to import.
     * @return int Number of configurations imported.
     */
    public function importFromJson(string $json): int;
}
