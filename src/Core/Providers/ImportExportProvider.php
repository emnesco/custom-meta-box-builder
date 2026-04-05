<?php
namespace CMB\Core\Providers;

use CMB\Core\Contracts\ServiceProvider;
use CMB\Core\ImportExport;
use CMB\Core\Plugin;

class ImportExportProvider implements ServiceProvider {
    public function register( Plugin $plugin ): void {}

    public function boot( Plugin $plugin ): void {
        $importExport = new ImportExport( $plugin->getManager() );
        $importExport->register();
    }

    public function isNeeded(): bool {
        return is_admin();
    }
}
