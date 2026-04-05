<?php
/**
 * Legacy AdminUI stub — delegates to AdminUI\Router.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Core;

use CMB\Core\AdminUI\Router;

class AdminUI {
    public static function register(): void {
        Router::register();
    }
}
