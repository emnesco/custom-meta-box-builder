<?php
require dirname(__DIR__) . '/vendor/autoload.php';

define('WP_USE_THEMES', false);

// Mock basic WordPress functions if necessary here
if (!function_exists('add_action')) {
    function add_action(...$args) {}
    function add_meta_box(...$args) {}
    function update_post_meta(...$args) {}
    function delete_post_meta(...$args) {}
    function add_post_meta(...$args) {}
    function get_post_meta(...$args) { return ''; }
    function checked($checked, $current = true, $echo = true) {
        return ($checked == $current) ? 'checked="checked"' : '';
    }
    function selected($selected, $current = true, $echo = true) {
        return ($selected == $current) ? 'selected="selected"' : '';
    }
    function esc_attr($text) { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }
    function esc_html($text) { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }
    function esc_textarea($text) { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }
    function wp_nonce_field($action, $name, $referer = true, $echo = true) {}
    function wp_verify_nonce($nonce, $action) { return true; }
    function current_user_can(...$args) { return true; }
    function sanitize_text_field($str) { return strip_tags((string)$str); }
    function sanitize_textarea_field($str) { return strip_tags((string)$str); }
    function sanitize_email($email) { return filter_var((string)$email, FILTER_SANITIZE_EMAIL); }
    function esc_url_raw($url) { return filter_var((string)$url, FILTER_SANITIZE_URL); }
    function absint($val) { return abs((int)$val); }
    function wp_kses_post($data) { return $data; }
    function map_deep($value, $callback) {
        if (is_array($value)) {
            foreach ($value as $index => $item) {
                $value[$index] = map_deep($item, $callback);
            }
        } else {
            $value = call_user_func($callback, $value);
        }
        return $value;
    }
    function _doing_it_wrong($function, $message, $version) {}
    function plugin_dir_url($file) { return '/wp-content/plugins/custom-meta-box-builder/'; }
    function wp_enqueue_style(...$args) {}
    function wp_enqueue_script(...$args) {}
    function register_post_meta(...$args) {}
    function get_post($id = null) { return null; }
    function get_the_ID() { return 0; }
    function is_serialized($data) { return false; }
    function maybe_unserialize($data) { return $data; }
    function get_posts($args = []) { return []; }
    function get_users($args = []) { return []; }
    function get_terms($args = []) { return []; }
    function is_wp_error($thing) { return false; }
    function wp_get_attachment_image_url($id, $size = 'thumbnail') { return ''; }
    function wp_get_attachment_url($id) { return ''; }
    function wp_enqueue_media() {}
    function get_term_meta($termId, $key = '', $single = false) { return $single ? '' : []; }
    function update_term_meta($termId, $key, $value) { return true; }
    function get_user_meta($userId, $key = '', $single = false) { return $single ? '' : []; }
    function update_user_meta($userId, $key, $value) { return true; }
    function get_option($option, $default = false) { return $default; }
    function update_option($option, $value) { return true; }
    function register_setting($optionGroup, $optionName, $args = []) {}
    function add_settings_section(...$args) {}
    function settings_fields($optionGroup) {}
    function do_settings_sections($page) {}
    function submit_button($text = 'Save Changes') { echo '<input type="submit" value="' . $text . '">'; }
    function add_menu_page(...$args) {}
    function add_submenu_page(...$args) {}
    function wp_is_post_revision($postId) { return false; }
    function __return_false() { return false; }
    function do_action($tag, ...$args) {}
    function apply_filters($tag, $value, ...$args) { return $value; }
    function get_post_type($post = null) { return 'post'; }
    function wp_localize_script(...$args) {}
}

if (!defined('DOING_AUTOSAVE')) {
    define('DOING_AUTOSAVE', false);
}
