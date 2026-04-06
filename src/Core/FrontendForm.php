<?php
declare(strict_types=1);

/**
 * Frontend form rendering — allows meta box forms to be rendered on the frontend.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.1
 */

namespace CMB\Core;

defined( 'ABSPATH' ) || exit;

use CMB\Core\RenderContext\PostContext;

class FrontendForm {
    /**
     * Render a meta box form on the frontend.
     *
     * @param string   $metaBoxId The meta box ID to render.
     * @param int|null $postId    The post ID (defaults to current post).
     * @param array    $args      Additional arguments (submit_text, form_id, method).
     * @return string The form HTML.
     */
    public static function render(string $metaBoxId, ?int $postId = null, array $args = []): string {
        $manager = MetaBoxManager::getInstance();
        if (null === $manager) {
            return '';
        }

        $metaBoxes = $manager->getMetaBoxes();
        if (!isset($metaBoxes[$metaBoxId])) {
            return '';
        }

        $metaBox = $metaBoxes[$metaBoxId];
        $postId = $postId ?: get_the_ID();
        $post = get_post($postId);
        if (!$post) {
            return '';
        }

        // Enqueue frontend assets.
        self::enqueueAssets();

        $submitText = $args['submit_text'] ?? __('Update', 'custom-meta-box-builder');
        $formId = $args['form_id'] ?? 'cmb-frontend-form-' . $metaBoxId;
        $method = $args['method'] ?? 'post';

        $fieldRenderer = new FieldRenderer(new PostContext($post));
        $fields = FieldUtils::flattenFields($metaBox['fields']);

        $output = '<form id="' . esc_attr($formId) . '" class="cmb-frontend-form cmb-container" method="' . esc_attr($method) . '">';
        $output .= wp_nonce_field('cmb_frontend_save_' . $metaBoxId . '_' . $postId, 'cmb_frontend_nonce', true, false);
        $output .= '<input type="hidden" name="cmb_frontend_box_id" value="' . esc_attr($metaBoxId) . '">';
        $output .= '<input type="hidden" name="cmb_frontend_post_id" value="' . esc_attr($postId) . '">';

        $output .= '<div class="cmb-fields">';
        foreach ($fields as $field) {
            $output .= $fieldRenderer->render($field);
        }
        $output .= '</div>';

        $output .= '<div class="cmb-frontend-submit">';
        $output .= '<button type="submit" class="button cmb-submit">' . esc_html($submitText) . '</button>';
        $output .= '</div>';

        $output .= '</form>';

        return $output;
    }

    /**
     * Enqueue frontend form assets.
     */
    private static function enqueueAssets(): void {
        $pluginUrl = plugin_dir_url(dirname(__DIR__) . '/../custom-meta-box-builder.php');
        $pluginPath = plugin_dir_path(dirname(__DIR__) . '/../custom-meta-box-builder.php');

        $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

        $cssFile = 'assets/cmb-style' . $suffix . '.css';
        if (!file_exists($pluginPath . $cssFile)) {
            $cssFile = 'assets/cmb-style.css';
        }
        wp_enqueue_style('cmb-style', $pluginUrl . $cssFile, [], filemtime($pluginPath . $cssFile));

        $jsFile = 'assets/cmb-script' . $suffix . '.js';
        if (!file_exists($pluginPath . $jsFile)) {
            $jsFile = 'assets/cmb-script.js';
        }
        wp_enqueue_script('cmb-script', $pluginUrl . $jsFile, ['jquery'], filemtime($pluginPath . $jsFile), true);

        // Enqueue media library if needed.
        if (function_exists('wp_enqueue_media')) {
            wp_enqueue_media();
        }
    }

    /**
     * Process frontend form submission.
     * Hook into 'init' to handle POST data.
     */
    public static function processSubmission(): void {
        if (empty($_POST['cmb_frontend_box_id']) || empty($_POST['cmb_frontend_nonce'])) {
            return;
        }

        $metaBoxId = sanitize_text_field(wp_unslash($_POST['cmb_frontend_box_id']));
        $postId = intval($_POST['cmb_frontend_post_id'] ?? 0);

        if (!wp_verify_nonce($_POST['cmb_frontend_nonce'], 'cmb_frontend_save_' . $metaBoxId . '_' . $postId)) {
            return;
        }

        // Check that the user can edit this post.
        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        $manager = MetaBoxManager::getInstance();
        if (null === $manager) {
            return;
        }

        $metaBoxes = $manager->getMetaBoxes();
        if (!isset($metaBoxes[$metaBoxId])) {
            return;
        }

        $metaBox = $metaBoxes[$metaBoxId];
        $fields = FieldUtils::flattenFields($metaBox['fields']);

        foreach ($fields as $field) {
            if (empty($field['id']) || empty($field['type'])) {
                continue;
            }

            $fieldClass = FieldFactory::resolveClass($field['type']);
            if (null === $fieldClass) {
                continue;
            }

            $instance = new $fieldClass($field);
            $raw = wp_unslash($_POST[$field['id']] ?? '');

            // SEC-N05: Validate attachment IDs for file/image/gallery fields.
            if (in_array($field['type'], ['file', 'image', 'gallery'], true)) {
                $attachmentIds = is_array($raw) ? $raw : [$raw];
                $validIds = [];
                foreach ($attachmentIds as $id) {
                    $id = intval($id);
                    if ($id && get_post($id)) {
                        $validIds[] = $id;
                    }
                }
                $raw = is_array($raw) ? $validIds : ($validIds[0] ?? '');
            }

            $errors = $instance->validate($raw);
            if (!empty($errors)) {
                continue;
            }

            $sanitized = $instance->sanitize($raw);
            update_post_meta($postId, $field['id'], $sanitized);
        }

        /** @since 2.1 Fires after a frontend form has been processed. */
        do_action('cmbbuilder_frontend_form_saved', $metaBoxId, $postId);
    }

    /**
     * Register the frontend form handler.
     */
    public static function register(): void {
        add_action('init', [self::class, 'processSubmission']);
    }
}
