<?php
declare(strict_types=1);

/**
 * Service provider for bulk operations feature.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Providers;

defined( 'ABSPATH' ) || exit;

use CMB\Core\BulkOperations;
use CMB\Core\Contracts\ServiceProvider;
use CMB\Core\Plugin;

class BulkOperationsProvider implements ServiceProvider {
    public function register( Plugin $plugin ): void {}

    public function boot( Plugin $plugin ): void {
        $bulkOps = new BulkOperations( $plugin->getManager() );
        $bulkOps->register();
    }

    public function isNeeded(): bool {
        return is_admin();
    }
}
