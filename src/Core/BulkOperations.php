<?php
/**
 * Bulk meta operations — set, delete, and export across multiple posts.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Core;

/**
 * Bulk meta operations for multiple posts (8.7).
 *
 * Provides API and admin UI for applying meta values across multiple posts.
 */
class BulkOperations {
    private MetaBoxManager $manager;

    public function __construct( MetaBoxManager $manager ) {
        $this->manager = $manager;
    }

    public function register(): void {
        add_action('admin_menu', [$this, 'addAdminPage']);
        add_action('admin_init', [$this, 'handleBulkUpdate']);
    }

    public function addAdminPage(): void {
        add_submenu_page(
            'tools.php',
            'CMB Bulk Operations',
            'CMB Bulk Ops',
            'manage_options',
            'cmb-bulk-ops',
            [$this, 'renderPage']
        );
    }

    public function renderPage(): void {
        $boxes = $this->manager->getMetaBoxes();

        echo '<div class="wrap">';
        echo '<h1>CMB Bulk Meta Operations</h1>';
        echo '<p>Apply meta values to multiple posts at once.</p>';

        if (empty($boxes)) {
            echo '<p>No meta boxes registered.</p></div>';
            return;
        }

        echo '<form method="post">';
        wp_nonce_field('cmb_bulk_update', 'cmb_bulk_nonce');

        // Post type selector
        $allPostTypes = [];
        foreach ($boxes as $box) {
            $allPostTypes = array_merge($allPostTypes, $box['postTypes']);
        }
        $allPostTypes = array_unique($allPostTypes);

        echo '<table class="form-table">';
        echo '<tr><th>Post Type</th><td><select name="cmb_bulk_post_type">';
        foreach ($allPostTypes as $pt) {
            echo '<option value="' . esc_attr($pt) . '">' . esc_html($pt) . '</option>';
        }
        echo '</select></td></tr>';

        // Field selector
        echo '<tr><th>Field</th><td><select name="cmb_bulk_field_id">';
        foreach ($boxes as $box) {
            $fields = FieldUtils::flattenFields($box['fields']);
            foreach ($fields as $f) {
                if (($f['type'] ?? '') === 'group') continue; // Skip groups for bulk
                echo '<option value="' . esc_attr($f['id']) . '">' . esc_html(($f['label'] ?? $f['id']) . ' (' . $f['id'] . ')') . '</option>';
            }
        }
        echo '</select></td></tr>';

        // Operation
        echo '<tr><th>Operation</th><td><select name="cmb_bulk_operation">';
        echo '<option value="set">Set value</option>';
        echo '<option value="delete">Delete meta</option>';
        echo '<option value="replace">Find &amp; Replace</option>';
        echo '</select></td></tr>';

        // Value
        echo '<tr><th>Value</th><td><input type="text" name="cmb_bulk_value" class="regular-text">';
        echo '<p class="description">For "Set": the value to apply. For "Replace": the new value.</p></td></tr>';

        // Find value (for replace)
        echo '<tr><th>Find (for Replace)</th><td><input type="text" name="cmb_bulk_find" class="regular-text">';
        echo '<p class="description">Only used with "Find &amp; Replace" operation.</p></td></tr>';

        // Post filter
        echo '<tr><th>Post IDs (optional)</th><td><input type="text" name="cmb_bulk_post_ids" class="regular-text">';
        echo '<p class="description">Comma-separated. Leave empty to apply to all posts of the selected type.</p></td></tr>';

        echo '</table>';

        echo '<p><input type="submit" name="cmb_bulk_submit" class="button button-primary" value="Execute Bulk Operation" onclick="return confirm(\'This will modify multiple posts. Continue?\')"></p>';
        echo '</form>';
        echo '</div>';
    }

    public function handleBulkUpdate(): void {
        if (empty($_POST['cmb_bulk_submit'])) {
            return;
        }
        if (!isset($_POST['cmb_bulk_nonce']) || !wp_verify_nonce($_POST['cmb_bulk_nonce'], 'cmb_bulk_update')) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $postType = sanitize_text_field( wp_unslash( $_POST['cmb_bulk_post_type'] ?? '' ) );
        $fieldId = sanitize_text_field( wp_unslash( $_POST['cmb_bulk_field_id'] ?? '' ) );
        $operation = sanitize_text_field( wp_unslash( $_POST['cmb_bulk_operation'] ?? 'set' ) );
        $value = sanitize_text_field( wp_unslash( $_POST['cmb_bulk_value'] ?? '' ) );
        $find = sanitize_text_field( wp_unslash( $_POST['cmb_bulk_find'] ?? '' ) );
        $postIdsRaw = sanitize_text_field( wp_unslash( $_POST['cmb_bulk_post_ids'] ?? '' ) );

        if (empty($fieldId)) {
            return;
        }

        // Get post IDs
        $postIds = [];
        if (!empty($postIdsRaw)) {
            $postIds = array_map('absint', array_filter(explode(',', $postIdsRaw)));
        } else {
            // Batch-fetch post IDs in pages of 500 to prevent memory exhaustion
            $page = 1;
            $batchSize = 500;
            do {
                $batch = get_posts([
                    'post_type'      => $postType,
                    'posts_per_page' => $batchSize,
                    'paged'          => $page,
                    'fields'         => 'ids',
                    'post_status'    => 'any',
                ]);
                $postIds = array_merge($postIds, $batch);
                $page++;
            } while (count($batch) === $batchSize);
        }

        $count = 0;
        $batches = array_chunk($postIds, 100);
        foreach ($batches as $batch) {
            foreach ($batch as $postId) {
                switch ($operation) {
                    case 'set':
                        update_post_meta($postId, $fieldId, $value);
                        $count++;
                        break;

                    case 'delete':
                        delete_post_meta($postId, $fieldId);
                        $count++;
                        break;

                    case 'replace':
                        $current = get_post_meta($postId, $fieldId, true);
                        if (is_string($current) && strpos($current, $find) !== false) {
                            $newVal = str_replace($find, $value, $current);
                            update_post_meta($postId, $fieldId, $newVal);
                            $count++;
                        }
                        break;
                }
            }
            // Flush object cache between batches to manage memory
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
        }

        add_action('admin_notices', function () use ($count, $operation) {
            echo '<div class="notice notice-success is-dismissible"><p>Bulk ' . esc_html($operation) . ' completed: ' . $count . ' post(s) updated.</p></div>';
        });
    }

    /**
     * Programmatic bulk set for use in scripts/WP-CLI.
     */
    public function bulkSet(array $postIds, string $fieldId, mixed $value): int {
        $count = 0;
        foreach ($postIds as $postId) {
            update_post_meta((int)$postId, $fieldId, $value);
            $count++;
        }
        return $count;
    }

    /**
     * Programmatic bulk delete for use in scripts/WP-CLI.
     */
    public function bulkDelete(array $postIds, string $fieldId): int {
        $count = 0;
        foreach ($postIds as $postId) {
            delete_post_meta((int)$postId, $fieldId);
            $count++;
        }
        return $count;
    }
}
