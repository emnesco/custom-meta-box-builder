<?php
declare(strict_types=1);

/**
 * JSON import/export of meta box configurations.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Import/Export meta box configurations as JSON (8.1).
 */
class ImportExport implements Contracts\ImportExportInterface {
    private MetaBoxManager $manager;

    public function __construct( MetaBoxManager $manager ) {
        $this->manager = $manager;
    }

    /**
     * Register programmatic API only.
     * The admin UI for import/export is handled by AdminUI.
     */
    public function register(): void {
        // No admin page needed — AdminUI handles the UI.
    }

    /**
     * Export configuration programmatically (for API use).
     */
    public function exportToJson(): string {
        $boxes = $this->manager->getMetaBoxes();

        // SEC-L05: Strip internal _modified timestamps from export data.
        $boxes = self::stripModifiedKeys($boxes);

        return wp_json_encode([
            'version' => '1.0',
            'plugin' => 'custom-meta-box-builder',
            'exported_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'meta_boxes' => $boxes,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * SEC-L05: Recursively strip _modified keys from data before export.
     */
    private static function stripModifiedKeys(array $data): array {
        unset($data['_modified']);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::stripModifiedKeys($value);
            }
        }
        return $data;
    }

    /**
     * Import configuration programmatically (for API use).
     */
    public function importFromJson(string $json): int {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data['meta_boxes'])) {
            return 0;
        }

        $count = 0;
        foreach ($data['meta_boxes'] as $id => $box) {
            if (empty($box['title']) || empty($box['postTypes']) || empty($box['fields'])) {
                continue;
            }
            $this->manager->add($id, $box['title'], $box['postTypes'], $box['fields'], $box['context'] ?? 'advanced', $box['priority'] ?? 'default');
            $count++;
        }
        return $count;
    }
}
