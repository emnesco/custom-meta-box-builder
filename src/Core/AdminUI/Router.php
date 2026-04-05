<?php
namespace CMB\Core\AdminUI;

class Router {
    private const OPTION_KEY = 'cmb_admin_configurations';

    public static function register(): void {
        add_action('admin_menu', [self::class, 'addAdminPage']);
        add_action('admin_init', [ActionHandler::class, 'handleSave']);
        add_action('admin_init', [ActionHandler::class, 'handleDelete']);
        add_action('admin_init', [ActionHandler::class, 'handleDuplicate']);
        add_action('admin_init', [ActionHandler::class, 'handleToggle']);
        add_action('admin_init', [ActionHandler::class, 'handleExport']);
        add_action('admin_init', [ActionHandler::class, 'handleExportPhp']);
        add_action('admin_init', [ActionHandler::class, 'handleImport']);
    }

    public static function addAdminPage(): void {
        $hook = add_menu_page(
            'CMB Builder',
            'CMB Builder',
            'manage_options',
            'cmb-builder',
            [self::class, 'renderPage'],
            'dashicons-editor-table',
            80
        );

        add_action('admin_enqueue_scripts', function ($hookSuffix) use ($hook) {
            if ($hookSuffix !== $hook) {
                return;
            }
            $baseUrl  = plugin_dir_url(dirname(__DIR__, 3) . '/custom-meta-box-builder.php');
            $basePath = plugin_dir_path(dirname(__DIR__, 3) . '/custom-meta-box-builder.php');
            $suffix   = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
            $cssFile  = 'assets/cmb-admin' . $suffix . '.css';
            $jsFile   = 'assets/cmb-admin' . $suffix . '.js';
            if ( $suffix && ! file_exists( $basePath . $cssFile ) ) {
                $cssFile = 'assets/cmb-admin.css';
            }
            if ( $suffix && ! file_exists( $basePath . $jsFile ) ) {
                $jsFile = 'assets/cmb-admin.js';
            }
            wp_enqueue_style('cmb-admin', $baseUrl . $cssFile, [], @filemtime( $basePath . $cssFile ) ?: null);
            wp_enqueue_script('cmb-admin', $baseUrl . $jsFile, ['jquery', 'jquery-ui-sortable'], @filemtime( $basePath . $jsFile ) ?: null, true);
            wp_enqueue_style('dashicons');

            $postTypes = get_post_types(['public' => true], 'objects');
            $ptList = [];
            foreach ($postTypes as $slug => $obj) {
                $ptList[$slug] = $obj->labels->singular_name;
            }

            wp_localize_script('cmb-admin', 'cmbAdmin', [
                'fieldTypes'  => self::getFieldTypesFlat(),
                'fieldGroups' => self::getFieldTypeCategories(),
                'postTypes'   => $ptList,
                'taxonomies'  => self::getTaxonomyList(),
                'roles'       => self::getRoleList(),
                'nonce'       => wp_create_nonce('cmb_builder_save'),
                'pageUrl'     => admin_url('admin.php?page=cmb-builder'),
            ]);
        });
    }

    public static function renderPage(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $action = sanitize_text_field($_GET['action'] ?? '');
        $id     = sanitize_text_field($_GET['id'] ?? '');

        if ($action === 'new' || $action === 'edit') {
            EditPage::renderEditPage($action, $id);
        } else {
            ListPage::renderListPage();
        }
    }

    public static function getFieldTypeCategories(): array {
        return [
            [
                'label' => 'Basic',
                'types' => [
                    'text'     => ['label' => 'Text',     'icon' => 'dashicons-editor-textcolor'],
                    'textarea' => ['label' => 'Textarea', 'icon' => 'dashicons-editor-paragraph'],
                    'number'   => ['label' => 'Number',   'icon' => 'dashicons-calculator'],
                    'email'    => ['label' => 'Email',    'icon' => 'dashicons-email'],
                    'url'      => ['label' => 'URL',      'icon' => 'dashicons-admin-links'],
                    'password' => ['label' => 'Password', 'icon' => 'dashicons-lock'],
                    'hidden'   => ['label' => 'Hidden',   'icon' => 'dashicons-hidden'],
                ],
            ],
            [
                'label' => 'Content',
                'types' => [
                    'wysiwyg' => ['label' => 'WYSIWYG Editor', 'icon' => 'dashicons-edit-large'],
                ],
            ],
            [
                'label' => 'Choice',
                'types' => [
                    'select'   => ['label' => 'Select',   'icon' => 'dashicons-arrow-down-alt2'],
                    'radio'    => ['label' => 'Radio',    'icon' => 'dashicons-marker'],
                    'checkbox' => ['label' => 'Checkbox', 'icon' => 'dashicons-yes-alt'],
                ],
            ],
            [
                'label' => 'Date & Color',
                'types' => [
                    'date'  => ['label' => 'Date Picker',  'icon' => 'dashicons-calendar-alt'],
                    'color' => ['label' => 'Color Picker', 'icon' => 'dashicons-art'],
                ],
            ],
            [
                'label' => 'Media',
                'types' => [
                    'file' => ['label' => 'File Upload', 'icon' => 'dashicons-media-default'],
                ],
            ],
            [
                'label' => 'Relational',
                'types' => [
                    'post'     => ['label' => 'Post Select',     'icon' => 'dashicons-admin-post'],
                    'taxonomy' => ['label' => 'Taxonomy Select', 'icon' => 'dashicons-category'],
                    'user'     => ['label' => 'User Select',     'icon' => 'dashicons-admin-users'],
                ],
            ],
            [
                'label' => 'Layout',
                'types' => [
                    'group' => ['label' => 'Repeater Group', 'icon' => 'dashicons-grid-view'],
                ],
            ],
        ];
    }

    public static function getFieldTypesFlat(): array {
        $flat = [];
        foreach (self::getFieldTypeCategories() as $cat) {
            foreach ($cat['types'] as $key => $info) {
                $flat[$key] = $info;
            }
        }
        return $flat;
    }

    public static function getPostTypeIcon(string $slug): string {
        $icons = [
            'post'       => 'dashicons-admin-post',
            'page'       => 'dashicons-admin-page',
            'attachment' => 'dashicons-admin-media',
        ];
        return $icons[$slug] ?? 'dashicons-admin-generic';
    }

    public static function getTaxonomyList(): array {
        $list = [];
        foreach (get_taxonomies(['public' => true], 'objects') as $slug => $obj) {
            $list[$slug] = $obj->labels->singular_name;
        }
        return $list;
    }

    public static function getRoleList(): array {
        $list = ['all' => 'All Roles'];
        foreach (wp_roles()->roles as $slug => $data) {
            $list[$slug] = $data['name'];
        }
        return $list;
    }
}
