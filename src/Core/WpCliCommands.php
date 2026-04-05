<?php
namespace CMB\Core;

/**
 * WP-CLI commands for Custom Meta Box Builder (7.5).
 *
 * Commands:
 *   wp cmb list               — List registered meta boxes
 *   wp cmb get <post_id> <field_id> — Get a meta value
 *   wp cmb set <post_id> <field_id> <value> — Set a meta value
 */
class WpCliCommands {
    public static function register(): void {
        if (!defined('WP_CLI') || !WP_CLI) {
            return;
        }
        if (!class_exists('WP_CLI')) {
            return;
        }

        \WP_CLI::add_command('cmb list', [self::class, 'listBoxes']);
        \WP_CLI::add_command('cmb get', [self::class, 'getField']);
        \WP_CLI::add_command('cmb set', [self::class, 'setField']);
    }

    /**
     * List all registered meta boxes.
     *
     * ## EXAMPLES
     *     wp cmb list
     *
     * @param array $args
     * @param array $assocArgs
     */
    public static function listBoxes(array $args, array $assocArgs): void {
        $manager = MetaBoxManager::instance();
        $boxes = $manager->getMetaBoxes();

        if (empty($boxes)) {
            \WP_CLI::warning('No meta boxes registered.');
            return;
        }

        $items = [];
        foreach ($boxes as $id => $box) {
            $fields = [];
            if (!empty($box['fields']['tabs'])) {
                foreach ($box['fields']['tabs'] as $tab) {
                    foreach ($tab['fields'] ?? [] as $f) {
                        $fields[] = $f['id'] ?? '(no id)';
                    }
                }
            } else {
                foreach ($box['fields'] as $f) {
                    $fields[] = $f['id'] ?? '(no id)';
                }
            }
            $items[] = [
                'ID' => $id,
                'Title' => $box['title'],
                'Post Types' => implode(', ', $box['postTypes']),
                'Fields' => implode(', ', $fields),
                'Context' => $box['context'] ?? 'advanced',
                'Priority' => $box['priority'] ?? 'default',
            ];
        }

        $formatter = new \WP_CLI\Formatter($assocArgs, ['ID', 'Title', 'Post Types', 'Fields', 'Context', 'Priority']);
        $formatter->display_items($items);
    }

    /**
     * Get a meta box field value.
     *
     * ## EXAMPLES
     *     wp cmb get 123 my_field
     *
     * @param array $args
     * @param array $assocArgs
     */
    public static function getField(array $args, array $assocArgs): void {
        if (count($args) < 2) {
            \WP_CLI::error('Usage: wp cmb get <post_id> <field_id>');
            return;
        }

        $postId = (int) $args[0];
        $fieldId = $args[1];

        $value = get_post_meta($postId, $fieldId, true);

        if (is_array($value) || is_object($value)) {
            \WP_CLI::line(json_encode($value, JSON_PRETTY_PRINT));
        } else {
            \WP_CLI::line((string) $value);
        }
    }

    /**
     * Set a meta box field value.
     *
     * ## EXAMPLES
     *     wp cmb set 123 my_field "new value"
     *
     * @param array $args
     * @param array $assocArgs
     */
    public static function setField(array $args, array $assocArgs): void {
        if (count($args) < 3) {
            \WP_CLI::error('Usage: wp cmb set <post_id> <field_id> <value>');
            return;
        }

        $postId = (int) $args[0];
        $fieldId = $args[1];
        $value = $args[2];

        // Try JSON decode for complex values
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
            $value = $decoded;
        }

        update_post_meta($postId, $fieldId, $value);
        \WP_CLI::success(sprintf('Updated "%s" on post %d.', $fieldId, $postId));
    }
}
