<?php
namespace CMB\Core;

class AjaxHandler {
    public function register(): void {
        add_action('wp_ajax_cmb_search_posts', [$this, 'searchPosts']);
        add_action('wp_ajax_cmb_search_users', [$this, 'searchUsers']);
        add_action('wp_ajax_cmb_search_terms', [$this, 'searchTerms']);
    }

    public function searchPosts(): void {
        check_ajax_referer('cmb_ajax_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized', 403);
        }

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

        $results = array_map(function ($post) {
            return ['id' => $post->ID, 'text' => $post->post_title];
        }, $posts);

        wp_send_json(['results' => $results]);
    }

    public function searchUsers(): void {
        check_ajax_referer('cmb_ajax_nonce', 'nonce');
        if (!current_user_can('list_users')) {
            wp_send_json_error('Unauthorized', 403);
        }

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

        $results = array_map(function ($user) {
            return ['id' => $user->ID, 'text' => $user->display_name];
        }, $users);

        wp_send_json(['results' => $results]);
    }

    public function searchTerms(): void {
        check_ajax_referer('cmb_ajax_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $search   = sanitize_text_field(wp_unslash($_GET['q'] ?? ''));
        $taxonomy = sanitize_text_field(wp_unslash($_GET['taxonomy'] ?? 'category'));

        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'search'     => $search,
            'hide_empty' => false,
            'number'     => 20,
        ]);

        if (is_wp_error($terms)) {
            wp_send_json(['results' => []]);
            return;
        }

        $results = array_map(function ($term) {
            return ['id' => $term->term_id, 'text' => $term->name];
        }, $terms);

        wp_send_json(['results' => $results]);
    }
}
