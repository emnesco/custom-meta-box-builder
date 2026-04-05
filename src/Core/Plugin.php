<?php
namespace CMB\Core;

final class Plugin {
    public function boot(): void {
        $this->registerAssets();

        $manager = MetaBoxManager::instance();
        $manager->register();
    }

    private function registerAssets(): void {
        add_action('admin_enqueue_scripts', function () {
            $baseUrl = plugin_dir_url(dirname(__DIR__, 2) . '/custom-meta-box-builder.php');
            wp_enqueue_style('cmb-style', $baseUrl . 'assets/cmb-style.css');
            wp_enqueue_script('cmb-script', $baseUrl . 'assets/cmb-script.js', ['jquery'], null, true);

            // Enqueue WP media for file/image fields
            if (function_exists('wp_enqueue_media')) {
                wp_enqueue_media();
            }
        });
    }
}
