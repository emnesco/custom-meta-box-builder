<?php
declare(strict_types=1);

/**
 * Service provider for local JSON sync feature.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Providers;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\ServiceProvider;
use CMB\Core\LocalJson;
use CMB\Core\Plugin;

class LocalJsonProvider implements ServiceProvider {
    public function register( Plugin $plugin ): void {}

    public function boot( Plugin $plugin ): void {
        LocalJson::register();
    }

    public function isNeeded(): bool {
        return is_admin();
    }
}
