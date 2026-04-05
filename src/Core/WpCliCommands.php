<?php
/**
 * WP-CLI commands for field CRUD operations.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
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
    private MetaBoxManager $manager;

    public function __construct( MetaBoxManager $manager ) {
        $this->manager = $manager;
    }

    public function register(): void {
        if (!defined('WP_CLI') || !WP_CLI) {
            return;
        }
        if (!class_exists('WP_CLI')) {
            return;
        }

        \WP_CLI::add_command('cmb list', [$this, 'listBoxes']);
        \WP_CLI::add_command('cmb get', [$this, 'getField']);
        \WP_CLI::add_command('cmb set', [$this, 'setField']);
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
    public function listBoxes(array $args, array $assocArgs): void {
        $boxes = $this->manager->getMetaBoxes();

        if (empty($boxes)) {
            \WP_CLI::warning('No meta boxes registered.');
            return;
        }

        $items = [];
        foreach ($boxes as $id => $box) {
            $flatFields = FieldUtils::flattenFields($box['fields']);
            $fields = [];
            foreach ($flatFields as $f) {
                $fields[] = $f['id'] ?? '(no id)';
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
    public function getField(array $args, array $assocArgs): void {
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
    public function setField(array $args, array $assocArgs): void {
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

        // Sanitize through the field pipeline if a matching field config exists.
        $fieldConfig = $this->findFieldConfig($fieldId);
        if ($fieldConfig !== null) {
            $instance = FieldFactory::create($fieldConfig['type'], $fieldConfig);
            if ($instance !== null) {
                $value = $instance->sanitize($value);
            }
        }

        update_post_meta($postId, $fieldId, $value);
        \WP_CLI::success(sprintf('Updated "%s" on post %d.', $fieldId, $postId));
    }

    /**
     * Find a field config by ID across all registered meta boxes.
     */
    private function findFieldConfig(string $fieldId): ?array {
        foreach ($this->manager->getMetaBoxes() as $box) {
            $fields = FieldUtils::flattenFields($box['fields']);
            foreach ($fields as $field) {
                if (($field['id'] ?? '') === $fieldId) {
                    return $field;
                }
            }
        }
        return null;
    }
}
