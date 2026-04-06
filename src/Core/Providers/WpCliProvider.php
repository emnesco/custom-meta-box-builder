<?php
declare(strict_types=1);

/**
 * Service provider for WP-CLI commands.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Providers;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\ServiceProvider;
use CMB\Core\Plugin;
use CMB\Core\WpCliCommands;

class WpCliProvider implements ServiceProvider {
    public function register( Plugin $plugin ): void {}

    public function boot( Plugin $plugin ): void {
        $commands = new WpCliCommands( $plugin->getManager() );
        $commands->register();
    }

    public function isNeeded(): bool {
        return defined( 'WP_CLI' ) && WP_CLI;
    }
}
