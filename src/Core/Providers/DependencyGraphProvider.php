<?php
declare(strict_types=1);

/**
 * Service provider for dependency graph feature.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Providers;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\ServiceProvider;
use CMB\Core\DependencyGraph;
use CMB\Core\Plugin;

class DependencyGraphProvider implements ServiceProvider {
    public function register( Plugin $plugin ): void {}

    public function boot( Plugin $plugin ): void {
        $graph = new DependencyGraph( $plugin->getManager() );
        $graph->register();
    }

    public function isNeeded(): bool {
        return is_admin();
    }
}
