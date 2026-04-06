<?php
declare(strict_types=1);

/**
 * Service provider for frontend form rendering and submission.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Providers;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\ServiceProvider;
use CMB\Core\FrontendForm;
use CMB\Core\Plugin;

class FrontendProvider implements ServiceProvider {
    public function register( Plugin $plugin ): void {}

    public function boot( Plugin $plugin ): void {
        FrontendForm::register();
    }

    public function isNeeded(): bool {
        return true;
    }
}
