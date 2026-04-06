<?php
declare(strict_types=1);

/**
 * Local JSON sync — saves field group configs as JSON files for version control.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.1
 */

namespace CMB\Core;

defined( 'ABSPATH' ) || exit;

class LocalJson implements Contracts\LocalJsonInterface {
    /**
     * Directory name within the active theme for JSON files.
     */
    private const DIR_NAME = 'cmb-json';

    /**
     * Get the JSON save directory path.
     * Allows filtering via 'cmbbuilder_json_save_path'.
     */
    public static function getSavePath(): string {
        $path = get_stylesheet_directory() . '/' . self::DIR_NAME;
        return apply_filters('cmbbuilder_json_save_path', $path);
    }

    /**
     * Get all JSON load paths (child theme first, then parent theme).
     * Allows filtering via 'cmbbuilder_json_load_paths'.
     *
     * @return string[]
     */
    public static function getLoadPaths(): array {
        $paths = [];
        $child = get_stylesheet_directory() . '/' . self::DIR_NAME;
        $parent = get_template_directory() . '/' . self::DIR_NAME;

        if (is_dir($child)) {
            $paths[] = $child;
        }
        if ($child !== $parent && is_dir($parent)) {
            $paths[] = $parent;
        }

        return apply_filters('cmbbuilder_json_load_paths', $paths);
    }

    /**
     * Save a field group config to a JSON file.
     */
    public static function save(string $groupId, array $config): bool {
        global $wp_filesystem;
        WP_Filesystem();

        $dir = self::getSavePath();

        if (!wp_mkdir_p($dir)) {
            return false;
        }

        $config['_modified'] = time();
        $filename = sanitize_file_name($groupId) . '.json';
        $file = $dir . '/' . $filename;
        $json = wp_json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return (bool) $wp_filesystem->put_contents($file, $json, FS_CHMOD_FILE);
    }

    /**
     * Delete a JSON file for a field group.
     */
    public static function delete(string $groupId): bool {
        global $wp_filesystem;
        WP_Filesystem();

        $dir = self::getSavePath();
        $filename = sanitize_file_name($groupId) . '.json';
        $file = $dir . '/' . $filename;

        if ($wp_filesystem->exists($file)) {
            return wp_delete_file($file) !== false;
        }
        return true;
    }

    /**
     * Load all JSON configs from the load paths.
     *
     * @return array<string, array> Keyed by group ID.
     */
    public static function loadAll(): array {
        global $wp_filesystem;
        WP_Filesystem();

        $configs = [];
        $paths = self::getLoadPaths();

        foreach ($paths as $dir) {
            $fileList = $wp_filesystem->dirlist($dir);
            if (!is_array($fileList)) {
                continue;
            }
            foreach ($fileList as $filename => $info) {
                if ($info['type'] !== 'f' || pathinfo($filename, PATHINFO_EXTENSION) !== 'json') {
                    continue;
                }
                $file = trailingslashit($dir) . $filename;
                $content = $wp_filesystem->get_contents($file);
                if (false === $content) {
                    continue;
                }
                $data = json_decode($content, true);
                if (!is_array($data) || empty($data['id'])) {
                    continue;
                }
                // First match wins (child theme overrides parent theme).
                if (!isset($configs[$data['id']])) {
                    $data['_source'] = 'json';
                    $data['_file'] = $file;
                    $configs[$data['id']] = $data;
                }
            }
        }

        return $configs;
    }

    /**
     * Sync JSON files with database configs.
     *
     * - JSON configs not in DB → import to DB.
     * - JSON configs newer than DB → update DB.
     * - DB configs with no JSON file → leave as-is (user may not have JSON save enabled).
     */
    public static function sync(): void {
        $cached = get_transient('cmb_localjson_files');
        if (false !== $cached) {
            return;
        }

        $jsonConfigs = self::loadAll();
        if (empty($jsonConfigs)) {
            set_transient('cmb_localjson_files', [], 5 * MINUTE_IN_SECONDS);
            return;
        }

        $dbConfigs = AdminUI\ActionHandler::getConfigs();
        $changed = false;

        foreach ($jsonConfigs as $groupId => $jsonConfig) {
            $dbConfig = $dbConfigs[$groupId] ?? null;

            if (null === $dbConfig) {
                // New config from JSON — import it.
                $dbConfigs[$groupId] = $jsonConfig;
                $changed = true;
                continue;
            }

            // Check if JSON is newer.
            $jsonModified = $jsonConfig['_modified'] ?? 0;
            $dbModified = $dbConfig['_modified'] ?? 0;

            if ($jsonModified > $dbModified) {
                $dbConfigs[$groupId] = $jsonConfig;
                $changed = true;
            }
        }

        if ($changed) {
            AdminUI\ActionHandler::saveConfigs($dbConfigs);
        }

        set_transient('cmb_localjson_files', $jsonConfigs, 5 * MINUTE_IN_SECONDS);
    }

    /**
     * Hook into config saves to auto-write JSON files.
     * Call this from Plugin::boot() or a service provider.
     */
    public static function register(): void {
        // Sync on admin_init (after themes are loaded).
        add_action('admin_init', [self::class, 'sync'], 5);

        // Auto-save JSON when configs are saved via admin UI.
        add_action('cmbbuilder_config_saved', function (string $groupId, array $config) {
            self::save($groupId, $config);
            delete_transient('cmb_localjson_files');
        }, 10, 2);

        // Auto-delete JSON when a config is deleted.
        add_action('cmbbuilder_config_deleted', function (string $groupId) {
            self::delete($groupId);
            delete_transient('cmb_localjson_files');
        });
    }
}
