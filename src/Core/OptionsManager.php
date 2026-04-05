<?php
namespace CMB\Core;

class OptionsManager {
    private array $pages = [];

    public function add(string $pageSlug, string $pageTitle, string $menuTitle, array $fields, string $capability = 'manage_options', string $parentSlug = ''): void {
        $this->pages[$pageSlug] = compact('pageTitle', 'menuTitle', 'fields', 'capability', 'parentSlug');
    }

    public function register(): void {
        add_action('admin_menu', [$this, 'addMenuPages']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addMenuPages(): void {
        foreach ($this->pages as $slug => $page) {
            if ($page['parentSlug']) {
                add_submenu_page(
                    $page['parentSlug'],
                    $page['pageTitle'],
                    $page['menuTitle'],
                    $page['capability'],
                    $slug,
                    function () use ($slug, $page) { $this->renderPage($slug, $page); }
                );
            } else {
                add_menu_page(
                    $page['pageTitle'],
                    $page['menuTitle'],
                    $page['capability'],
                    $slug,
                    function () use ($slug, $page) { $this->renderPage($slug, $page); }
                );
            }
        }
    }

    private function renderPage(string $slug, array $page): void {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($page['pageTitle']) . '</h1>';
        echo '<form method="post" action="options.php">';

        settings_fields('cmb_options_' . $slug);
        do_settings_sections('cmb_options_' . $slug);

        echo '<table class="form-table">';
        foreach ($page['fields'] as $field) {
            $value = get_option($field['id'], $field['default'] ?? '');

            echo '<tr>';
            echo '<th><label for="' . esc_attr($field['id']) . '">' . esc_html($field['label'] ?? '') . '</label></th>';
            echo '<td>';

            $fieldClass = 'CMB\\Fields\\' . ucfirst($field['type']) . 'Field';
            if (class_exists($fieldClass)) {
                $instance = new $fieldClass(array_merge($field, [
                    'name' => $field['id'],
                    'html_id' => $field['id'],
                    'value' => $value,
                ]));
                echo $instance->render();
            }

            if (!empty($field['description'])) {
                echo '<p class="description">' . esc_html($field['description']) . '</p>';
            }
            echo '</td></tr>';
        }
        echo '</table>';

        submit_button();
        echo '</form></div>';
    }

    public function registerSettings(): void {
        foreach ($this->pages as $slug => $page) {
            add_settings_section('cmb_section_' . $slug, '', '__return_false', 'cmb_options_' . $slug);

            foreach ($page['fields'] as $field) {
                $fieldClass = 'CMB\\Fields\\' . ucfirst($field['type']) . 'Field';
                register_setting('cmb_options_' . $slug, $field['id'], [
                    'sanitize_callback' => function ($value) use ($field, $fieldClass) {
                        if (class_exists($fieldClass)) {
                            $instance = new $fieldClass($field);
                            return $instance->sanitize($value);
                        }
                        return sanitize_text_field($value);
                    },
                ]);
            }
        }
    }
}
