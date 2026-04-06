<?php
declare(strict_types=1);

/**
 * Interface for WPGraphQL integration.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Contracts;

defined( 'ABSPATH' ) || exit;

interface GraphQLInterface {
    /**
     * Register CMB fields with WPGraphQL.
     * Only runs if WPGraphQL plugin is active.
     */
    public static function register(): void;

    /**
     * Register all meta box fields in the GraphQL schema.
     */
    public static function registerFields(): void;
}
