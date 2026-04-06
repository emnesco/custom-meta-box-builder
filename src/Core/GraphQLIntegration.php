<?php
declare(strict_types=1);

/**
 * WPGraphQL integration — exposes CMB fields in the GraphQL schema.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.1
 */

namespace CMB\Core;

defined( 'ABSPATH' ) || exit;

class GraphQLIntegration implements Contracts\GraphQLInterface {
    /**
     * In-memory cache of all post meta keyed by post ID.
     * Populated once per post via get_post_meta($id) with no key,
     * so individual field resolvers avoid redundant DB queries.
     *
     * @var array<int, array<string, mixed>>
     */
    private static array $metaCache = [];

    /**
     * Fetch all meta for a post in a single query and cache the result.
     *
     * @param int $postId The post ID.
     * @return array<string, mixed> All meta values keyed by meta key.
     */
    private static function getAllMeta(int $postId): array {
        if (!isset(self::$metaCache[$postId])) {
            self::$metaCache[$postId] = get_post_meta($postId);
        }
        return self::$metaCache[$postId];
    }

    /**
     * Register CMB fields with WPGraphQL.
     * Only runs if WPGraphQL plugin is active.
     */
    public static function register(): void {
        if (!class_exists('WPGraphQL')) {
            return;
        }

        add_action('graphql_register_types', [self::class, 'registerFields']);
    }

    /**
     * Register all meta box fields in the GraphQL schema.
     */
    public static function registerFields(): void {
        $manager = MetaBoxManager::getInstance();
        if (null === $manager) {
            return;
        }

        foreach ($manager->getMetaBoxes() as $boxId => $metaBox) {
            // SEC-N08: Respect show_in_graphql config per meta box.
            if (isset($metaBox['show_in_graphql']) && !$metaBox['show_in_graphql']) {
                continue;
            }

            $fields = FieldUtils::flattenFields($metaBox['fields']);

            foreach ($fields as $field) {
                if (empty($field['id']) || empty($field['type'])) {
                    continue;
                }

                // Skip non-storable fields.
                if (in_array($field['type'], ['message', 'divider'], true)) {
                    continue;
                }

                // SEC-N08: Respect show_in_graphql config per field.
                if (isset($field['show_in_graphql']) && !$field['show_in_graphql']) {
                    continue;
                }

                $graphqlType = self::mapFieldType($field['type']);
                $fieldId = $field['id'];

                foreach ($metaBox['postTypes'] as $postType) {
                    $graphqlPostType = self::getGraphQLTypeName($postType);
                    if (!$graphqlPostType) {
                        continue;
                    }

                    register_graphql_field($graphqlPostType, self::sanitizeFieldName($fieldId), [
                        'type'        => $graphqlType,
                        'description' => $field['label'] ?? $fieldId,
                        'resolve'     => function ($post) use ($fieldId) {
                            // SEC-N08: Only expose meta from published posts.
                            $postObj = get_post($post->databaseId);
                            if (!$postObj || $postObj->post_status !== 'publish') {
                                return null;
                            }
                            // PERF-M05: Fetch all meta at once, resolve individual fields from cache.
                            $allMeta = self::getAllMeta($post->databaseId);
                            if (!isset($allMeta[$fieldId])) {
                                return null;
                            }
                            // get_post_meta with no key returns arrays of values; single = first element.
                            return maybe_unserialize($allMeta[$fieldId][0] ?? null);
                        },
                    ]);
                }
            }
        }
    }

    /**
     * Map CMB field type to GraphQL type.
     */
    /**
     * Map CMB field type to GraphQL type.
     *
     * @return string|array GraphQL type or type definition array.
     */
    private static function mapFieldType(string $type): string|array {
        return match ($type) {
            'number', 'range'          => 'Float',
            'checkbox', 'toggle'       => 'Boolean',
            'group', 'link'            => 'String', // JSON-encoded object
            'flexible_content'         => 'String', // JSON-encoded string
            'gallery'                  => ['list_of' => 'Int'], // list of attachment IDs
            'checkbox_list'            => ['list_of' => 'String'],
            default                    => 'String',
        };
    }

    /**
     * Get the GraphQL type name for a post type.
     */
    private static function getGraphQLTypeName(string $postType): ?string {
        $postTypeObject = get_post_type_object($postType);
        if (!$postTypeObject) {
            return null;
        }

        // WPGraphQL stores its type name in show_in_graphql / graphql_single_name.
        if (!empty($postTypeObject->graphql_single_name)) {
            return ucfirst($postTypeObject->graphql_single_name);
        }

        // Standard WordPress types.
        return match ($postType) {
            'post' => 'Post',
            'page' => 'Page',
            'attachment' => 'MediaItem',
            default => null,
        };
    }

    /**
     * Sanitize a field name for GraphQL (camelCase, no special chars).
     */
    private static function sanitizeFieldName(string $name): string {
        // Convert snake_case to camelCase.
        $name = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
        $parts = explode('_', $name);
        $first = array_shift($parts);
        return $first . implode('', array_map('ucfirst', $parts));
    }
}
