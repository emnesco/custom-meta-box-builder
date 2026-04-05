<?php
namespace CMB\Fields;

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
        $output .= '<option value="">&mdash; Select User &mdash;</option>';

        if (function_exists('get_users')) {
            $cacheKey = $role . '_' . $limit;
            if (!isset(self::$userCache[$cacheKey])) {
                $args = [
                    'orderby' => 'display_name',
                    'order'   => 'ASC',
                    'number'  => $limit,
                ];
                if ($role) {
                    $args['role'] = $role;
                }
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
