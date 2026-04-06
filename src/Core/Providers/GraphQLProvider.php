<?php
declare(strict_types=1);

/**
 * Service provider for WPGraphQL integration.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Providers;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\ServiceProvider;
use CMB\Core\GraphQLIntegration;
use CMB\Core\Plugin;

class GraphQLProvider implements ServiceProvider {
    public function register( Plugin $plugin ): void {}

    public function boot( Plugin $plugin ): void {
        GraphQLIntegration::register();
    }

    public function isNeeded(): bool {
        return class_exists('WPGraphQL');
    }
}
