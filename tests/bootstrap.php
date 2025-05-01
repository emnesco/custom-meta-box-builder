<?php
require dirname(__DIR__) . '/vendor/autoload.php';

define('WP_USE_THEMES', false);

// Mock basic WordPress functions if necessary here
if (!function_exists('add_action')) {
    function add_action(...$args) {}
    function add_meta_box(...$args) {}
    function update_post_meta(...$args) {}
    function get_post_meta(...$args) { return ''; }
    function checked($checked, $current = true, $echo = true) {
        return ($checked == $current) ? 'checked="checked"' : ''; 
    }
    function selected($selected, $current = true, $echo = true) {
        return ($selected == $current) ? 'selected="selected"' : ''; 
    }
    function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
    function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
    function esc_textarea($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
    function wp_nonce_field($action, $name, $referer = true, $echo = true) {}
    function wp_verify_nonce($nonce, $action) { return true; }
    function defined($name) { return false; }
}