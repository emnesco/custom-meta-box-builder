<?php
/**
 * Service provider for Gutenberg integration.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Core\Providers;

use CMB\Core\Contracts\ServiceProvider;
use CMB\Core\GutenbergPanel;
use CMB\Core\Plugin;

class GutenbergProvider implements ServiceProvider {
    public function register( Plugin $plugin ): void {}

    public function boot( Plugin $plugin ): void {
        $panel = new GutenbergPanel( $plugin->getManager() );
        $panel->register();
    }

    public function isNeeded(): bool {
        return true;
    }
}
