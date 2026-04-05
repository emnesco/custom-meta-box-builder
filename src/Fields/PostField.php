<?php
/**
 * Post relationship field type — renders a select of posts with static caching.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class PostField extends AbstractField {
    private static array $postCache = [];

    public function render(): string {
        $value = $this->getValue();
        $htmlId = $this->config['html_id'] ?? '';
        $id_attr = $htmlId ? ' id="' . esc_attr($htmlId) . '"' : '';
        $name = esc_attr($this->getName());
        $postType = $this->config['post_type'] ?? 'post';
        $limit = $this->config['limit'] ?? 100;

        $output = '<select name="' . $name . '"' . $id_attr . $this->renderAttributes() . $this->requiredAttr() . '>';
        $output .= '<option value="">&mdash; Select &mdash;</option>';

        if (function_exists('get_posts')) {
            $cacheKey = $postType . '_' . $limit;
            if (!isset(self::$postCache[$cacheKey])) {
                self::$postCache[$cacheKey] = get_posts([
                    'post_type'      => $postType,
                    'posts_per_page' => $limit,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                    'post_status'    => 'publish',
                ]);
            }
            foreach (self::$postCache[$cacheKey] as $post) {
                $sel = selected($value, $post->ID, false);
                $output .= '<option value="' . esc_attr($post->ID) . '"' . $sel . '>' . esc_html($post->post_title) . '</option>';
            }
        }

        $output .= '</select>';
        return $output;
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        $id = absint($value);
        if ($id && function_exists('get_post') && !get_post($id)) {
            return 0;
        }
        return $id;
    }
}
