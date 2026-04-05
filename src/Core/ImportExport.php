<?php
namespace CMB\Core;

/**
 * Import/Export meta box configurations as JSON (8.1).
 */
class ImportExport {
    /**
     * Register programmatic API only.
     * The admin UI for import/export is handled by AdminUI.
     */
    public static function register(): void {
        // No admin page needed — AdminUI handles the UI.
    }

    /**
     * Export configuration programmatically (for API use).
     */
    public static function exportToJson(): string {
        $manager = MetaBoxManager::instance();
        $boxes = $manager->getMetaBoxes();

        return json_encode([
            'version' => '1.0',
            'plugin' => 'custom-meta-box-builder',
            'exported_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'meta_boxes' => $boxes,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Import configuration programmatically (for API use).
     */
    public static function importFromJson(string $json): int {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data['meta_boxes'])) {
            return 0;
        }

        $manager = MetaBoxManager::instance();
        $count = 0;
        foreach ($data['meta_boxes'] as $id => $box) {
            if (empty($box['title']) || empty($box['postTypes']) || empty($box['fields'])) {
                continue;
            }
            $manager->add($id, $box['title'], $box['postTypes'], $box['fields'], $box['context'] ?? 'advanced', $box['priority'] ?? 'default');
            $count++;
        }
        return $count;
    }
}
