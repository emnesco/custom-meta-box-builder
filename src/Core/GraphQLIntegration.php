<?php
/**
 * WPGraphQL integration — exposes CMB fields in the GraphQL schema.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.1
 */
namespace CMB\Core;

class GraphQLIntegration {
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
        if ($manager === null) {
            return;
        }

        foreach ($manager->getMetaBoxes() as $boxId => $metaBox) {
            $fields = FieldUtils::flattenFields($metaBox['fields']);

            foreach ($fields as $field) {
                if (empty($field['id']) || empty($field['type'])) {
                    continue;
                }

                // Skip non-storable fields.
                if (in_array($field['type'], ['message', 'divider'], true)) {
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
                            return get_post_meta($post->databaseId, $fieldId, true);
                        },
                    ]);
                }
            }
        }
    }

    /**
     * Map CMB field type to GraphQL type.
     */
    private static function mapFieldType(string $type): string {
        return match ($type) {
            'number', 'range'         => 'Float',
            'checkbox', 'toggle'      => 'Boolean',
            'gallery', 'checkbox_list' => ['list_of' => 'String'],
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
