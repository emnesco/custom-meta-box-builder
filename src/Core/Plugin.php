<?php
/**
 * Main plugin bootstrap — registers hooks, enqueues assets, and loads providers.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Core;

use CMB\Core\Contracts\ServiceProvider;

final class Plugin {
    private static ?Plugin $instance = null;
    private MetaBoxManager $manager;
    private ?TaxonomyMetaManager $taxonomyManager = null;
    private ?UserMetaManager $userMetaManager = null;
    private ?OptionsManager $optionsManager = null;

    /** @var ServiceProvider[] */
    private array $providers = [];

    public static function getInstance(): ?self {
        return self::$instance;
    }

    public function getManager(): MetaBoxManager {
        return $this->manager;
    }

    public function getTaxonomyManager(): TaxonomyMetaManager {
        if ( $this->taxonomyManager === null ) {
            $this->taxonomyManager = new TaxonomyMetaManager();
            $this->taxonomyManager->register();
        }
        return $this->taxonomyManager;
    }

    public function getUserMetaManager(): UserMetaManager {
        if ( $this->userMetaManager === null ) {
            $this->userMetaManager = new UserMetaManager();
            $this->userMetaManager->register();
        }
        return $this->userMetaManager;
    }

    public function getOptionsManager(): OptionsManager {
        if ( $this->optionsManager === null ) {
            $this->optionsManager = new OptionsManager();
            $this->optionsManager->register();
        }
        return $this->optionsManager;
    }

    public function boot(): void {
        self::$instance = $this;
        $this->manager = new MetaBoxManager();
        MetaBoxManager::setInstance( $this->manager );

        $this->loadTextDomain();
        $this->registerAssets();

        // Register AJAX search endpoints for relational fields.
        $ajax = new AjaxHandler();
        $ajax->register();

        // Local JSON sync for version-controlled field group configs.
        LocalJson::register();

        // WPGraphQL integration (conditional — only if WPGraphQL is active).
        GraphQLIntegration::register();

        // Frontend form processing.
        FrontendForm::register();

        // Gutenberg block registration.
        BlockRegistration::init();

        $this->manager->register();

        // Register saved meta boxes on all requests (needed for REST API / block editor)
        add_action( 'init', [ \CMB\Core\AdminUI\ActionHandler::class, 'registerSavedBoxes' ], 20 );

        $this->loadProviders([
            Providers\WpCliProvider::class,
            Providers\GutenbergProvider::class,
            Providers\ImportExportProvider::class,
            Providers\AdminUIProvider::class,
            Providers\DependencyGraphProvider::class,
            Providers\BulkOperationsProvider::class,
        ]);
    }

    private function loadProviders( array $providerClasses ): void {
        // Instantiate and register
        foreach ( $providerClasses as $class ) {
            /** @var ServiceProvider $provider */
            $provider = new $class();
            if ( ! $provider->isNeeded() ) {
                continue;
            }
            $provider->register( $this );
            $this->providers[] = $provider;
        }

        // Boot all registered providers
        foreach ( $this->providers as $provider ) {
            $provider->boot( $this );
        }
    }

    private function loadTextDomain(): void {
        load_plugin_textdomain(
            'custom-meta-box-builder',
            false,
            dirname( plugin_basename( dirname( __DIR__, 2 ) . '/custom-meta-box-builder.php' ) ) . '/languages'
        );
    }

    private function registerAssets(): void {
        add_action('admin_enqueue_scripts', function ( $hookSuffix ) {
            if ( ! self::shouldEnqueueAssets( $hookSuffix ) ) {
                return;
            }

            $baseUrl = plugin_dir_url(dirname(__DIR__, 2) . '/custom-meta-box-builder.php');
            $suffix  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
            $basePath = plugin_dir_path(dirname(__DIR__, 2) . '/custom-meta-box-builder.php');
            $cssFile  = 'assets/cmb-style' . $suffix . '.css';
            $jsFile   = 'assets/cmb-script' . $suffix . '.js';
            // Fall back to unminified if minified doesn't exist.
            if ( $suffix && ! file_exists( $basePath . $cssFile ) ) {
                $cssFile = 'assets/cmb-style.css';
            }
            if ( $suffix && ! file_exists( $basePath . $jsFile ) ) {
                $jsFile = 'assets/cmb-script.js';
            }
            $ver = @filemtime( $basePath . $cssFile ) ?: null;
            wp_enqueue_style('cmb-style', $baseUrl . $cssFile, [], $ver);
            $ver = @filemtime( $basePath . $jsFile ) ?: null;
            wp_enqueue_script('cmb-script', $baseUrl . $jsFile, ['jquery', 'jquery-ui-sortable'], $ver, true);
            wp_localize_script('cmb-script', 'cmbData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('cmb_ajax_nonce'),
            ]);

            // Enqueue WP media only on post/page editors where file fields may exist
            if ( in_array( $hookSuffix, [ 'post.php', 'post-new.php' ], true )
                && function_exists('wp_enqueue_media') ) {
                wp_enqueue_media();
            }
        });
    }

    private static function shouldEnqueueAssets( string $hookSuffix ): bool {
        if ( in_array( $hookSuffix, [ 'post.php', 'post-new.php' ], true ) ) {
            return true;
        }
        if ( in_array( $hookSuffix, [ 'term.php', 'edit-tags.php' ], true ) ) {
            return true;
        }
        if ( in_array( $hookSuffix, [ 'profile.php', 'user-edit.php' ], true ) ) {
            return true;
        }
        if ( strpos( $hookSuffix, 'cmb-' ) !== false || strpos( $hookSuffix, 'cmb_' ) !== false ) {
            return true;
        }
        if ( in_array( $hookSuffix, [ 'toplevel_page_cmb-options', 'settings_page_cmb-options' ], true ) ) {
            return true;
        }
        return false;
    }
}
