<?php
declare(strict_types=1);

/**
 * AJAX search endpoints for posts, users, and terms with nonce verification.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core;

defined( 'ABSPATH' ) || exit;

class AjaxHandler {
    public function register(): void {
        add_action('wp_ajax_cmb_search_posts', [$this, 'searchPosts']);
        add_action('wp_ajax_cmb_search_users', [$this, 'searchUsers']);
        add_action('wp_ajax_cmb_search_terms', [$this, 'searchTerms']);
    }

    /**
     * SEC-L03: Simple transient-based rate limiter for AJAX search endpoints.
     * Returns true if the request should be blocked.
     */
    private function isRateLimited(): bool {
        $key = 'cmb_search_' . get_current_user_id();
        if (get_transient($key)) {
            return true;
        }
        set_transient($key, 1, 1); // 1-second expiry
        return false;
    }

    public function searchPosts(): void {
        $this->searchEntities('edit_posts', function () {
            $search   = sanitize_text_field(wp_unslash($_GET['q'] ?? ''));
            $postType = sanitize_text_field(wp_unslash($_GET['post_type'] ?? 'post'));

            $posts = get_posts([
                'post_type'      => $postType,
                's'              => $search,
                'posts_per_page' => 20,
                'orderby'        => 'title',
                'order'          => 'ASC',
                'post_status'    => 'publish',
            ]);

            return array_map(function ($post) {
                return ['id' => $post->ID, 'text' => $post->post_title];
            }, $posts);
        });
    }

    public function searchUsers(): void {
        $this->searchEntities('list_users', function () {
            $search = sanitize_text_field(wp_unslash($_GET['q'] ?? ''));
            $role   = sanitize_text_field(wp_unslash($_GET['role'] ?? ''));

            $args = [
                'search'         => '*' . $search . '*',
                'search_columns' => ['user_login', 'user_email', 'display_name'],
                'number'         => 20,
                'orderby'        => 'display_name',
            ];
            if ($role) {
                $args['role'] = $role;
            }
            $users = get_users($args);

            return array_map(function ($user) {
                return ['id' => $user->ID, 'text' => $user->display_name];
            }, $users);
        });
    }

    public function searchTerms(): void {
        $this->searchEntities('edit_posts', function () {
            $search   = sanitize_text_field(wp_unslash($_GET['q'] ?? ''));
            $taxonomy = sanitize_text_field(wp_unslash($_GET['taxonomy'] ?? 'category'));

            $terms = get_terms([
                'taxonomy'   => $taxonomy,
                'search'     => $search,
                'hide_empty' => false,
                'number'     => 20,
            ]);

            if (is_wp_error($terms)) {
                return [];
            }

            return array_map(function ($term) {
                return ['id' => $term->term_id, 'text' => $term->name];
            }, $terms);
        });
    }

    /**
     * Base search method that handles nonce verification, capability check,
     * rate limiting, and JSON response for all search endpoints.
     *
     * @param string   $capability The required capability.
     * @param callable $queryFn    A callback that performs the query and returns results array.
     */
    private function searchEntities(string $capability, callable $queryFn): void {
        check_ajax_referer('cmb_ajax_nonce', 'nonce');
        if (!current_user_can($capability)) {
            wp_send_json_error('Unauthorized', 403);
        }
        if ($this->isRateLimited()) {
            wp_send_json_error(__('Too many requests. Please wait.', 'custom-meta-box-builder'), 429);
        }

        $results = $queryFn();
        wp_send_json(['results' => $results]);
    }
}
