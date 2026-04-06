<?php
declare(strict_types=1);

/**
 * Legacy AdminUI stub — delegates to AdminUI\Router.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core;

defined( 'ABSPATH' ) || exit;

use CMB\Core\AdminUI\Router;

class AdminUI {
    public static function register(): void {
        Router::register();
    }
}
