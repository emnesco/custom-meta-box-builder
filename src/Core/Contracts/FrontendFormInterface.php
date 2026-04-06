<?php
declare(strict_types=1);

/**
 * Interface for frontend form rendering and submission handling.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Contracts;

defined( 'ABSPATH' ) || exit;

interface FrontendFormInterface {
    /**
     * Render a meta box form on the frontend.
     *
     * @param string   $metaBoxId The meta box ID to render.
     * @param int|null $postId    The post ID (defaults to current post).
     * @param array    $args      Additional arguments (submit_text, form_id, method).
     * @return string The form HTML.
     */
    public static function render(string $metaBoxId, ?int $postId = null, array $args = []): string;

    /**
     * Process frontend form submission.
     */
    public static function processSubmission(): void;

    /**
     * Register the frontend form handler.
     */
    public static function register(): void;
}
