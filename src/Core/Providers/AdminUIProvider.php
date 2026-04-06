<?php
declare(strict_types=1);

/**
 * Service provider for the admin UI builder feature.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Providers;

defined( 'ABSPATH' ) || exit;

use CMB\Core\AdminUI;
use CMB\Core\Contracts\ServiceProvider;
use CMB\Core\Plugin;

class AdminUIProvider implements ServiceProvider {
    public function register( Plugin $plugin ): void {}

    public function boot( Plugin $plugin ): void {
        AdminUI::register();
    }

    public function isNeeded(): bool {
        return is_admin();
    }
}
