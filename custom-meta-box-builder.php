<?php
/**
 * Plugin Name:       Custom Meta Box Builder
 * Plugin URI:        https://emnes.co/plugins/custom-meta-box-builder
 * Description:       Create custom meta boxes with modern PHP architecture.
 * Version:           2.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            EMNES Lab
 * Author URI:        https://emnes.co
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       custom-meta-box-builder
 * Domain Path:       /languages
 */

 defined('ABSPATH') || exit;

 // PHP version check
 if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
     add_action( 'admin_notices', function () {
         echo '<div class="notice notice-error"><p>';
         echo esc_html(
             sprintf(
                 'Custom Meta Box Builder requires PHP 8.1 or higher. You are running PHP %s. Please upgrade PHP to use this plugin.',
                 PHP_VERSION
             )
         );
         echo '</p></div>';
     } );
     return;
 }

 require_once __DIR__ . '/vendor/autoload.php';

 use CMB\Core\Plugin;

 register_activation_hook( __FILE__, function () {
     if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
         deactivate_plugins( plugin_basename( __FILE__ ) );
         wp_die(
             esc_html(
                 sprintf(
                     'Custom Meta Box Builder requires PHP 8.1 or higher. You are running PHP %s.',
                     PHP_VERSION
                 )
             ),
             'Plugin Activation Error',
             [ 'back_link' => true ]
         );
     }
     update_option( 'cmb_plugin_version', '2.0' );
     flush_rewrite_rules();
 } );

 register_deactivation_hook( __FILE__, function () {
     flush_rewrite_rules();
 } );

 add_action( 'plugins_loaded', function () {
     $plugin = new Plugin();
     $plugin->boot();
 } );
