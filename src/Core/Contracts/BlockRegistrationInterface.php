<?php
declare(strict_types=1);

/**
 * Interface for Gutenberg block registration backed by CMB fields.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\Contracts;

defined( 'ABSPATH' ) || exit;

interface BlockRegistrationInterface {
    /**
     * Register a custom Gutenberg block backed by CMB fields.
     *
     * @param string $blockId Block identifier (e.g. 'hero-section').
     * @param array  $config  Block configuration.
     */
    public static function register(string $blockId, array $config): void;

    /**
     * Initialize block registration with WordPress.
     */
    public static function init(): void;

    /**
     * Register all blocks with WordPress.
     */
    public static function registerBlocks(): void;

    /**
     * Get all registered blocks.
     *
     * @return array<string, array>
     */
    public static function getBlocks(): array;
}
