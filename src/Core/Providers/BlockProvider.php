<?php
declare(strict_types=1);

/**
 * Service provider for Gutenberg block registration.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Providers;

defined( 'ABSPATH' ) || exit;

use CMB\Core\BlockRegistration;
use CMB\Core\Contracts\ServiceProvider;
use CMB\Core\Plugin;

class BlockProvider implements ServiceProvider {
    public function register( Plugin $plugin ): void {}

    public function boot( Plugin $plugin ): void {
        BlockRegistration::init();
    }

    public function isNeeded(): bool {
        return true;
    }
}
