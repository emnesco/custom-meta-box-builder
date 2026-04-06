<?php
declare(strict_types=1);

/**
 * Abstract service provider for modular feature registration.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Contracts;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Plugin;

/**
 * Service provider interface for modular plugin components.
 */
interface ServiceProvider {
    /**
     * Register services and bindings.
     */
    public function register( Plugin $plugin ): void;

    /**
     * Hook into WordPress after all providers are registered.
     */
    public function boot( Plugin $plugin ): void;

    /**
     * Whether this provider should be loaded on the current request.
     */
    public function isNeeded(): bool;
}
