<?php
declare(strict_types=1);

/**
 * Global settings page for the CMB Builder plugin.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.2
 */

namespace CMB\Core\AdminUI;

defined( 'ABSPATH' ) || exit;

class SettingsPage {
    private const OPTION_KEY = 'cmb_builder_settings';

    public static function getSettings(): array {
        $defaults = [
            'debug_mode' => false,
        ];
        $saved = get_option( self::OPTION_KEY, [] );
        return wp_parse_args( $saved, $defaults );
    }

    public static function isDebugEnabled(): bool {
        $settings = self::getSettings();
        return ! empty( $settings['debug_mode'] );
    }

    public static function handleSave(): void {
        if ( empty( $_POST['cmb_settings_nonce'] ) ) {
            return;
        }
        if ( ! wp_verify_nonce( $_POST['cmb_settings_nonce'], 'cmb_save_settings' ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = [
            'debug_mode' => ! empty( $_POST['cmb_debug_mode'] ),
        ];

        update_option( self::OPTION_KEY, $settings, false );

        wp_safe_redirect( add_query_arg( 'cmb-updated', '1', wp_get_referer() ?: admin_url( 'admin.php?page=cmb-settings' ) ) );
        exit;
    }

    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'custom-meta-box-builder' ) );
        }

        $settings = self::getSettings();
        $updated  = ! empty( $_GET['cmb-updated'] );
        ?>
        <div class="wrap cmb-admin-wrap cmb-settings-wrap">
            <div class="cmb-admin-header">
                <div class="cmb-admin-header-left">
                    <div class="cmb-admin-icon">
                        <span class="dashicons dashicons-editor-table"></span>
                    </div>
                    <h1><?php esc_html_e( 'CMB Builder Settings', 'custom-meta-box-builder' ); ?></h1>
                </div>
            </div>

            <?php if ( $updated ) : ?>
                <div class="cmb-notice cmb-notice-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e( 'Settings saved successfully.', 'custom-meta-box-builder' ); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="cmb_save_settings">
                <?php wp_nonce_field( 'cmb_save_settings', 'cmb_settings_nonce' ); ?>

                <div class="cmb-settings-card">
                    <div class="cmb-settings-card-header">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <h2><?php esc_html_e( 'Developer Tools', 'custom-meta-box-builder' ); ?></h2>
                    </div>
                    <div class="cmb-settings-card-body">
                        <div class="cmb-settings-row">
                            <div class="cmb-settings-label-col">
                                <label for="cmb_debug_mode">
                                    <?php esc_html_e( 'Debug Mode', 'custom-meta-box-builder' ); ?>
                                </label>
                                <p class="cmb-settings-description">
                                    <?php esc_html_e( 'When enabled, displays a beautified dump of saved meta values at the bottom of each field group on the post editor.', 'custom-meta-box-builder' ); ?>
                                </p>
                            </div>
                            <div class="cmb-settings-field-col">
                                <label class="cmb-toggle">
                                    <input type="checkbox"
                                           id="cmb_debug_mode"
                                           name="cmb_debug_mode"
                                           value="1"
                                           <?php checked( $settings['debug_mode'] ); ?>>
                                    <span class="cmb-toggle-slider"></span>
                                    <span class="cmb-toggle-label">
                                        <?php echo $settings['debug_mode']
                                            ? esc_html__( 'Enabled', 'custom-meta-box-builder' )
                                            : esc_html__( 'Disabled', 'custom-meta-box-builder' ); ?>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cmb-settings-footer">
                    <button type="submit" class="cmb-btn cmb-btn-primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php esc_html_e( 'Save Settings', 'custom-meta-box-builder' ); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
}
