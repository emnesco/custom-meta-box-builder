<?php
declare(strict_types=1);

/**
 * WP-CLI commands for field CRUD operations.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core;

defined( 'ABSPATH' ) || exit;

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
        \WP_CLI::add_command('cmb delete', [$this, 'deleteField']);
        \WP_CLI::add_command('cmb get-term', [$this, 'getTermField']);
        \WP_CLI::add_command('cmb get-user', [$this, 'getUserField']);
        \WP_CLI::add_command('cmb get-option', [$this, 'getOptionField']);
        \WP_CLI::add_command('cmb export', [$this, 'exportConfig']);
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
            \WP_CLI::warning(__('No meta boxes registered.', 'custom-meta-box-builder'));
            return;
        }

        $items = [];
        foreach ($boxes as $id => $box) {
            $flatFields = FieldUtils::flattenFields($box['fields']);
            $fields = [];
            foreach ($flatFields as $f) {
                $fields[] = $f['id'] ?? __('(no id)', 'custom-meta-box-builder');
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
            \WP_CLI::error(__('Usage: wp cmb get <post_id> <field_id>', 'custom-meta-box-builder'));
            return;
        }

        $postId = (int) $args[0];
        $fieldId = $args[1];

        $value = get_post_meta($postId, $fieldId, true);

        $this->outputValue($value, $assocArgs);
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
            \WP_CLI::error(__('Usage: wp cmb set <post_id> <field_id> <value>', 'custom-meta-box-builder'));
            return;
        }

        $postId = (int) $args[0];
        $fieldId = $args[1];
        $value = $args[2];

        // Try JSON decode for complex values
        $decoded = json_decode($value, true);
        if (JSON_ERROR_NONE === json_last_error() && null !== $decoded) {
            $value = $decoded;
        }

        // Sanitize through the field pipeline if a matching field config exists.
        $fieldConfig = $this->findFieldConfig($fieldId);
        if (null !== $fieldConfig) {
            $instance = FieldFactory::create($fieldConfig['type'], $fieldConfig);
            if (null !== $instance) {
                $value = $instance->sanitize($value);
            }
        }

        update_post_meta($postId, $fieldId, $value);

        // SEC-L06: Audit trail logging for WP-CLI set operations.
        $currentUser = wp_get_current_user();
        $userName = $currentUser->ID ? $currentUser->user_login : 'cli';
        error_log(sprintf(
            '[CMB Audit] SET field "%s" on post %d by user "%s"',
            $fieldId,
            $postId,
            $userName
        ));

        /* translators: 1: field ID, 2: post ID */
        \WP_CLI::success(sprintf(__('Updated "%1$s" on post %2$d.', 'custom-meta-box-builder'), $fieldId, $postId));
    }

    /**
     * Delete a meta box field value.
     *
     * ## EXAMPLES
     *     wp cmb delete 123 my_field
     */
    public function deleteField(array $args, array $assocArgs): void {
        if (count($args) < 2) {
            \WP_CLI::error('Usage: wp cmb delete <post_id> <field_id>');
            return;
        }

        $postId = (int) $args[0];
        $fieldId = $args[1];

        delete_post_meta($postId, $fieldId);

        // SEC-L06: Audit trail logging for WP-CLI delete operations.
        $currentUser = wp_get_current_user();
        $userName = $currentUser->ID ? $currentUser->user_login : 'cli';
        error_log(sprintf(
            '[CMB Audit] DELETE field "%s" from post %d by user "%s"',
            $fieldId,
            $postId,
            $userName
        ));

        \WP_CLI::success(sprintf('Deleted "%s" from post %d.', $fieldId, $postId));
    }

    /**
     * Get a term meta field value.
     *
     * ## EXAMPLES
     *     wp cmb get-term 42 my_field
     */
    public function getTermField(array $args, array $assocArgs): void {
        if (count($args) < 2) {
            \WP_CLI::error('Usage: wp cmb get-term <term_id> <field_id>');
            return;
        }
        $value = get_term_meta((int) $args[0], $args[1], true);
        $this->outputValue($value, $assocArgs);
    }

    /**
     * Get a user meta field value.
     *
     * ## EXAMPLES
     *     wp cmb get-user 1 my_field
     */
    public function getUserField(array $args, array $assocArgs): void {
        if (count($args) < 2) {
            \WP_CLI::error('Usage: wp cmb get-user <user_id> <field_id>');
            return;
        }
        $value = get_user_meta((int) $args[0], $args[1], true);
        $this->outputValue($value, $assocArgs);
    }

    /**
     * Get an option field value.
     *
     * ## EXAMPLES
     *     wp cmb get-option my_option
     */
    public function getOptionField(array $args, array $assocArgs): void {
        if (count($args) < 1) {
            \WP_CLI::error('Usage: wp cmb get-option <field_id>');
            return;
        }
        $value = get_option($args[0]);
        $this->outputValue($value, $assocArgs);
    }

    /**
     * Export all meta box configurations as JSON.
     *
     * ## OPTIONS
     *
     * [--file=<path>]
     * : Save to a file instead of stdout.
     *
     * ## EXAMPLES
     *     wp cmb export
     *     wp cmb export --file=meta-boxes.json
     */
    public function exportConfig(array $args, array $assocArgs): void {
        $boxes = $this->manager->getMetaBoxes();
        $data = [
            'version'     => '2.2',
            'plugin'      => 'custom-meta-box-builder',
            'exported_at' => gmdate('Y-m-d H:i:s'),
            'meta_boxes'  => $boxes,
        ];
        $json = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (!empty($assocArgs['file'])) {
            file_put_contents($assocArgs['file'], $json);
            \WP_CLI::success(sprintf('Exported %d meta boxes to %s.', count($boxes), $assocArgs['file']));
        } else {
            \WP_CLI::line($json);
        }
    }

    /**
     * Output a value, formatting arrays/objects as JSON.
     */
    private function outputValue(mixed $value, array $assocArgs): void {
        if (is_array($value) || is_object($value)) {
            \WP_CLI::line(wp_json_encode($value, JSON_PRETTY_PRINT));
        } else {
            \WP_CLI::line((string) $value);
        }
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
