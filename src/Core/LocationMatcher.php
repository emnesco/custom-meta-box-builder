<?php
/**
 * AND/OR location rule matching for conditional meta box display.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Core;

class LocationMatcher {
    /**
     * Check if a meta box should be shown for the current post.
     *
     * Location rules format:
     * [
     *     // OR group 1 (all rules ANDed)
     *     [
     *         ['param' => 'post_type', 'operator' => '==', 'value' => 'page'],
     *         ['param' => 'page_template', 'operator' => '==', 'value' => 'template-landing.php'],
     *     ],
     *     // OR group 2
     *     [
     *         ['param' => 'post_type', 'operator' => '==', 'value' => 'product'],
     *     ],
     * ]
     */
    public static function matches(array $locationRules, \WP_Post $post): bool {
        if (empty($locationRules)) {
            return true;
        }

        // Each top-level group is OR'd.
        foreach ($locationRules as $group) {
            if (!is_array($group)) continue;
            if (self::matchesGroup($group, $post)) {
                return true;
            }
        }
        return false;
    }

    private static function matchesGroup(array $rules, \WP_Post $post): bool {
        // All rules in a group are AND'd.
        foreach ($rules as $rule) {
            if (!is_array($rule) || empty($rule['param'])) continue;
            if (!self::matchesRule($rule, $post)) {
                return false;
            }
        }
        return true;
    }

    private static function matchesRule(array $rule, \WP_Post $post): bool {
        $param    = $rule['param'] ?? '';
        $operator = $rule['operator'] ?? '==';
        $value    = $rule['value'] ?? '';

        $actual = match ($param) {
            'post_type'      => $post->post_type,
            'page_template'  => get_page_template_slug($post->ID) ?: 'default',
            'post_status'    => $post->post_status,
            'post_format'    => get_post_format($post->ID) ?: 'standard',
            'post'           => (string) $post->ID,
            'post_category'  => self::getPostTermIds($post->ID, 'category'),
            'post_taxonomy'  => self::getPostTermIds($post->ID, $value),
            default          => null,
        };

        if ($actual === null) {
            return true; // Unknown param — don't restrict.
        }

        // For array values (categories/taxonomies), check membership.
        if (is_array($actual)) {
            return match ($operator) {
                '=='  => in_array($value, $actual, true),
                '!='  => !in_array($value, $actual, true),
                default => true,
            };
        }

        return match ($operator) {
            '=='  => (string) $actual === (string) $value,
            '!='  => (string) $actual !== (string) $value,
            default => true,
        };
    }

    private static function getPostTermIds(int $postId, string $taxonomy): array {
        $terms = wp_get_post_terms($postId, $taxonomy, ['fields' => 'ids']);
        return is_wp_error($terms) ? [] : array_map('strval', $terms);
    }
}
