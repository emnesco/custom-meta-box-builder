<?php
declare(strict_types=1);

/**
 * User relationship field type — renders a select of users with static caching.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Fields;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Contracts\Abstracts\AbstractField;

class UserField extends AbstractField {
    private static array $userCache = [];

    public function render(): string {
        $value = $this->getValue();
        $htmlId = $this->config['html_id'] ?? '';
        $id_attr = $htmlId ? ' id="' . esc_attr($htmlId) . '"' : '';
        $name = esc_attr($this->getName());
        $role = $this->config['role'] ?? '';
        $limit = $this->config['limit'] ?? 100;

        $output = '<select name="' . $name . '"' . $id_attr . $this->renderAttributes() . $this->requiredAttr() . '>';
        $output .= '<option value="">&mdash; ' . esc_html__('Select User', 'custom-meta-box-builder') . ' &mdash;</option>';

        if (function_exists('get_users')) {
            $args = [
                    'orderby' => 'display_name',
                    'order'   => 'ASC',
                    'number'  => $limit,
                ];
                if ($role) {
                    $args['role'] = $role;
                }
            $cacheKey = md5(wp_json_encode($args));
            if (!isset(self::$userCache[$cacheKey])) {
                self::$userCache[$cacheKey] = get_users($args);
            }
            $users = self::$userCache[$cacheKey];
            foreach ($users as $user) {
                $sel = selected($value, $user->ID, false);
                $output .= '<option value="' . esc_attr($user->ID) . '"' . $sel . '>' . esc_html($user->display_name) . '</option>';
            }
        }

        $output .= '</select>';
        return $output;
    }

    public function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            return array_map('absint', $value);
        }
        return absint($value);
    }
}
