<?php
/**
 * Uninstall handler for Custom Meta Box Builder.
 *
 * Runs when the plugin is deleted via the WordPress admin.
 * Removes all plugin options from the database.
 */

// Abort if not called by WordPress uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove plugin options.
delete_option( 'cmb_admin_configurations' );
delete_option( 'cmb_plugin_version' );
