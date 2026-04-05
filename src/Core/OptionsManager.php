<?php
/**
 * Settings page registration and option field management.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Core;

use CMB\Core\RenderContext\OptionContext;

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
        $context       = new OptionContext($slug);
        $fieldRenderer = new FieldRenderer($context);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html($page['pageTitle']) . '</h1>';
        echo '<form method="post" action="options.php">';

        settings_fields('cmb_options_' . $slug);
        do_settings_sections('cmb_options_' . $slug);

        echo '<table class="form-table">';
        foreach ($page['fields'] as $field) {
            echo '<tr>';
            echo '<th><label for="cmb-' . esc_attr($field['id']) . '">' . esc_html($field['label'] ?? '') . '</label></th>';
            echo '<td>';
            echo $fieldRenderer->render($field);
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
                register_setting('cmb_options_' . $slug, $field['id'], [
                    'sanitize_callback' => function ($value) use ($field) {
                        $instance = FieldFactory::create($field['type'], $field);
                        if ( $instance ) {
                            return $instance->sanitize($value);
                        }
                        return sanitize_text_field($value);
                    },
                ]);
            }
        }
    }
}
