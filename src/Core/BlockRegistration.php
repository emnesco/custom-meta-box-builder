<?php
declare(strict_types=1);

/**
 * Gutenberg block registration — allows defining custom blocks using CMB fields.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.1
 */

namespace CMB\Core;

defined( 'ABSPATH' ) || exit;

class BlockRegistration {
    /** @var array<string, array> Registered blocks. */
    private static array $blocks = [];

    /**
     * Register a custom Gutenberg block backed by CMB fields.
     *
     * @param string $blockId Block identifier (e.g. 'hero-section').
     * @param array  $config  Block configuration:
     *   - title: (string) Block title.
     *   - description: (string) Block description.
     *   - category: (string) Block category (default 'common').
     *   - icon: (string) Dashicon name (default 'block-default').
     *   - fields: (array) CMB field definitions.
     *   - render_callback: (callable) PHP render callback.
     *   - render_template: (string) PHP template file path.
     */
    public static function register(string $blockId, array $config): void {
        self::$blocks[$blockId] = $config;
    }

    /**
     * Initialize block registration with WordPress.
     * Call from Plugin::boot().
     */
    public static function init(): void {
        if (empty(self::$blocks)) {
            return;
        }

        add_action('init', [self::class, 'registerBlocks']);
    }

    /**
     * Register all blocks with WordPress.
     */
    public static function registerBlocks(): void {
        foreach (self::$blocks as $blockId => $config) {
            $namespace = 'cmb/' . sanitize_title($blockId);

            $attributes = self::buildAttributes($config['fields'] ?? []);

            register_block_type($namespace, [
                'api_version'     => 2,
                'title'           => $config['title'] ?? $blockId,
                'description'     => $config['description'] ?? '',
                'category'        => $config['category'] ?? 'common',
                'icon'            => $config['icon'] ?? 'block-default',
                'attributes'      => $attributes,
                'render_callback' => function ($blockAttributes, $content) use ($config, $blockId) {
                    return self::renderBlock($blockId, $config, $blockAttributes);
                },
            ]);
        }
    }

    /**
     * Build block attributes from CMB field definitions.
     */
    private static function buildAttributes(array $fields): array {
        $attributes = [];

        foreach ($fields as $field) {
            if (empty($field['id']) || empty($field['type'])) {
                continue;
            }

            $type = match ($field['type']) {
                'number', 'range'    => 'number',
                'checkbox', 'toggle' => 'boolean',
                'gallery', 'checkbox_list' => 'array',
                default              => 'string',
            };

            $attr = ['type' => $type];
            if (isset($field['default'])) {
                $attr['default'] = $field['default'];
            }

            $attributes[$field['id']] = $attr;
        }

        return $attributes;
    }

    /**
     * Render a block using the configured callback or template.
     */
    private static function renderBlock(string $blockId, array $config, array $attributes): string {
        if (!empty($config['render_callback']) && is_callable($config['render_callback'])) {
            ob_start();
            call_user_func($config['render_callback'], $attributes, '', $blockId);
            return ob_get_clean();
        }

        if (isset($config['render_template'])) {
            $templatePath = realpath($config['render_template']);
            $themePath = realpath(get_stylesheet_directory());
            $pluginPath = realpath(dirname(__DIR__, 2));
            if (!$templatePath || (!str_starts_with($templatePath, $themePath) && !str_starts_with($templatePath, $pluginPath))) {
                return ''; // Invalid template path
            }
            ob_start();
            // Make $block available to the template.
            $block = [
                'id'   => $blockId,
                'data' => $attributes,
            ];
            include $templatePath;
            return ob_get_clean();
        }

        // Default: render attributes as a simple list.
        $output = '<div class="cmb-block cmb-block-' . esc_attr($blockId) . '">';
        foreach ($attributes as $key => $value) {
            if (is_scalar($value)) {
                $output .= '<div class="cmb-block-field">' . esc_html($value) . '</div>';
            }
        }
        $output .= '</div>';

        return $output;
    }

    /**
     * Get all registered blocks.
     */
    public static function getBlocks(): array {
        return self::$blocks;
    }
}
