<?php
namespace CMB\Core;

/**
 * Enterprise-grade Admin UI for building meta boxes without code.
 *
 * Provides a visual field group builder with drag-and-drop, per-field settings,
 * import/export, PHP code generation, and full CRUD management.
 */
class AdminUI {
    private const OPTION_KEY = 'cmb_admin_configurations';

    public static function register(): void {
        add_action('admin_menu', [self::class, 'addAdminPage']);
        add_action('admin_init', [self::class, 'handleSave']);
        add_action('admin_init', [self::class, 'handleDelete']);
        add_action('admin_init', [self::class, 'handleDuplicate']);
        add_action('admin_init', [self::class, 'handleToggle']);
        add_action('admin_init', [self::class, 'handleExport']);
        add_action('admin_init', [self::class, 'handleImport']);
        add_action('init', [self::class, 'registerSavedBoxes'], 20);
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
            $baseUrl = plugin_dir_url(dirname(__DIR__, 2) . '/custom-meta-box-builder.php');
            wp_enqueue_style('cmb-admin', $baseUrl . 'assets/cmb-admin.css', [], '2.0.0');
            wp_enqueue_script('cmb-admin', $baseUrl . 'assets/cmb-admin.js', ['jquery', 'jquery-ui-sortable'], '2.0.0', true);
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

    /* ─── Routing ─────────────────────────────────────────── */

    public static function renderPage(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $action = sanitize_text_field($_GET['action'] ?? '');
        $id     = sanitize_text_field($_GET['id'] ?? '');

        if ($action === 'new' || $action === 'edit') {
            self::renderEditPage($action, $id);
        } else {
            self::renderListPage();
        }
    }

    /* ─── List Page ───────────────────────────────────────── */

    private static function renderListPage(): void {
        $configs = get_option(self::OPTION_KEY, []);

        echo '<div class="wrap cmb-admin-wrap">';

        // Header
        echo '<div class="cmb-admin-header">';
        echo '<div class="cmb-admin-header-left">';
        echo '<h1 class="wp-heading-inline">Field Groups</h1>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=cmb-builder&action=new')) . '" class="page-title-action">Add New</a>';
        echo '</div>';
        echo '<div class="cmb-admin-header-right">';
        echo '<button type="button" class="button" id="cmb-import-trigger"><span class="dashicons dashicons-upload"></span> Import</button>';
        if (!empty($configs)) {
            echo ' <a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=cmb-builder&cmb_export=all'), 'cmb_export_all')) . '" class="button"><span class="dashicons dashicons-download"></span> Export All</a>';
        }
        echo '</div>';
        echo '</div>';

        // Notices
        self::renderNotices();

        // Import modal
        self::renderImportModal();

        // Empty state
        if (empty($configs)) {
            echo '<div class="cmb-empty-state-page">';
            echo '<div class="cmb-empty-icon"><span class="dashicons dashicons-editor-table"></span></div>';
            echo '<h2>No Field Groups Yet</h2>';
            echo '<p>Create your first field group to start adding custom meta boxes to your content.</p>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=cmb-builder&action=new')) . '" class="button button-primary button-hero">Create Field Group</a>';
            echo '</div>';
            echo '</div>';
            return;
        }

        // Table
        echo '<table class="wp-list-table widefat fixed striped cmb-groups-table">';
        echo '<thead><tr>';
        echo '<th class="column-title column-primary">Title</th>';
        echo '<th class="column-fields">Fields</th>';
        echo '<th class="column-location">Location</th>';
        echo '<th class="column-context">Position</th>';
        echo '<th class="column-status">Status</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($configs as $boxId => $box) {
            $fieldCount = count($box['fields'] ?? []);
            $isActive   = $box['active'] ?? true;
            $postTypes  = $box['postTypes'] ?? [];
            $context    = ucfirst($box['context'] ?? 'advanced');

            echo '<tr class="' . ($isActive ? '' : 'cmb-inactive-row') . '">';

            // Title + row actions
            echo '<td class="column-title column-primary">';
            echo '<strong><a href="' . esc_url(admin_url('admin.php?page=cmb-builder&action=edit&id=' . urlencode($boxId))) . '" class="row-title">' . esc_html($box['title']) . '</a></strong>';
            echo '<code class="cmb-box-id">' . esc_html($boxId) . '</code>';
            echo '<div class="row-actions">';
            echo '<span class="edit"><a href="' . esc_url(admin_url('admin.php?page=cmb-builder&action=edit&id=' . urlencode($boxId))) . '">Edit</a> | </span>';
            echo '<span class="duplicate"><a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=cmb-builder&cmb_duplicate=' . urlencode($boxId)), 'cmb_duplicate_' . $boxId)) . '">Duplicate</a> | </span>';
            echo '<span class="export"><a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=cmb-builder&cmb_export=' . urlencode($boxId)), 'cmb_export_' . $boxId)) . '">Export</a> | </span>';
            echo '<span class="delete"><a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=cmb-builder&cmb_delete=' . urlencode($boxId)), 'cmb_delete_' . $boxId)) . '" class="submitdelete" onclick="return confirm(\'Delete this field group?\')">Delete</a></span>';
            echo '</div>';
            echo '</td>';

            // Fields count
            echo '<td class="column-fields"><span class="cmb-field-count">' . $fieldCount . '</span></td>';

            // Location
            echo '<td class="column-location">';
            $ptLabels = [];
            $allPt = get_post_types(['public' => true], 'objects');
            foreach ($postTypes as $pt) {
                $ptLabels[] = isset($allPt[$pt]) ? $allPt[$pt]->labels->singular_name : $pt;
            }
            echo esc_html(implode(', ', $ptLabels));
            echo '</td>';

            // Context
            echo '<td class="column-context">' . esc_html($context) . '</td>';

            // Status
            $toggleUrl = wp_nonce_url(
                admin_url('admin.php?page=cmb-builder&cmb_toggle=' . urlencode($boxId)),
                'cmb_toggle_' . $boxId
            );
            echo '<td class="column-status">';
            echo '<a href="' . esc_url($toggleUrl) . '" class="cmb-status-badge ' . ($isActive ? 'active' : 'inactive') . '">';
            echo $isActive ? 'Active' : 'Inactive';
            echo '</a>';
            echo '</td>';

            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    /* ─── Edit Page ───────────────────────────────────────── */

    private static function renderEditPage(string $action, string $id): void {
        $configs = get_option(self::OPTION_KEY, []);
        $editBox = null;

        if ($action === 'edit' && isset($configs[$id])) {
            $editBox = $configs[$id];
        }

        $title      = $editBox['title'] ?? '';
        $boxId      = $action === 'edit' ? $id : '';
        $postTypes  = $editBox['postTypes'] ?? ['post'];
        $context    = $editBox['context'] ?? 'advanced';
        $priority   = $editBox['priority'] ?? 'default';
        $isActive   = $editBox['active'] ?? true;
        $showInRest = $editBox['show_in_rest'] ?? false;
        $fields     = $editBox['fields'] ?? [];

        echo '<div class="wrap cmb-admin-wrap cmb-admin-edit">';

        // Header
        echo '<div class="cmb-admin-header">';
        echo '<div class="cmb-admin-header-left">';
        echo '<a href="' . esc_url(admin_url('admin.php?page=cmb-builder')) . '" class="cmb-back-link"><span class="dashicons dashicons-arrow-left-alt2"></span> Field Groups</a>';
        echo '<h1>' . ($editBox ? 'Edit Field Group' : 'New Field Group') . '</h1>';
        echo '</div>';
        echo '</div>';

        self::renderNotices();

        echo '<form method="post" id="cmb-builder-form">';
        wp_nonce_field('cmb_builder_save', 'cmb_builder_nonce');
        if ($action === 'edit') {
            echo '<input type="hidden" name="cmb_editing" value="' . esc_attr($id) . '">';
        }

        echo '<div id="poststuff">';
        echo '<div id="post-body" class="metabox-holder columns-2">';

        /* ── Main Column ── */
        echo '<div id="post-body-content">';

        // Title
        echo '<div id="titlediv">';
        echo '<input type="text" name="cmb_box_title" id="cmb-title-input" class="cmb-title-input" placeholder="Field Group Title" value="' . esc_attr($title) . '" required autocomplete="off">';
        echo '</div>';

        // Tabs
        echo '<div class="cmb-editor-tabs">';
        echo '<button type="button" class="cmb-editor-tab active" data-tab="fields"><span class="dashicons dashicons-editor-table"></span> Fields</button>';
        echo '<button type="button" class="cmb-editor-tab" data-tab="code"><span class="dashicons dashicons-editor-code"></span> PHP Code</button>';
        echo '</div>';

        // Fields panel
        echo '<div class="cmb-editor-panel active" id="cmb-panel-fields">';
        echo '<div id="cmb-fields-list" class="cmb-fields-sortable">';

        if (!empty($fields)) {
            foreach ($fields as $i => $field) {
                self::renderFieldRow($i, $field);
            }
        } else {
            echo '<div class="cmb-no-fields-msg"><p>No fields yet. Click the button below to add your first field.</p></div>';
        }

        echo '</div>'; // #cmb-fields-list

        echo '<div class="cmb-add-field-area">';
        echo '<button type="button" class="button button-secondary cmb-add-field-btn" id="cmb-add-field-trigger">';
        echo '<span class="dashicons dashicons-plus-alt2"></span> Add Field';
        echo '</button>';
        echo '</div>';

        echo '</div>'; // #cmb-panel-fields

        // Code panel
        echo '<div class="cmb-editor-panel" id="cmb-panel-code">';
        echo '<div class="cmb-code-panel-header">';
        echo '<p>Use this PHP code to register this field group programmatically. Add it to your theme\'s <code>functions.php</code> or a custom plugin.</p>';
        echo '<button type="button" class="button" id="cmb-copy-code"><span class="dashicons dashicons-clipboard"></span> Copy Code</button>';
        echo '</div>';
        echo '<pre class="cmb-code-preview" id="cmb-code-output"><code>// Save the field group first to generate code.</code></pre>';
        echo '</div>'; // #cmb-panel-code

        echo '</div>'; // #post-body-content

        /* ── Sidebar ── */
        echo '<div id="postbox-container-1" class="postbox-container">';

        // Publish box
        echo '<div class="postbox cmb-publish-box">';
        echo '<div class="postbox-header"><h2>Publish</h2></div>';
        echo '<div class="inside">';
        echo '<div class="cmb-status-control">';
        echo '<label class="cmb-toggle-label">';
        echo '<input type="hidden" name="cmb_active" value="0">';
        echo '<input type="checkbox" name="cmb_active" value="1"' . ($isActive ? ' checked' : '') . '>';
        echo '<span class="cmb-toggle-switch"></span>';
        echo '<span class="cmb-toggle-text">' . ($isActive ? 'Active' : 'Inactive') . '</span>';
        echo '</label>';
        echo '</div>';
        echo '<div class="cmb-publish-actions">';
        echo '<input type="submit" name="cmb_builder_submit" class="button button-primary button-large" value="' . ($editBox ? 'Update' : 'Publish') . '">';
        if ($editBox) {
            echo ' <a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=cmb-builder&cmb_delete=' . urlencode($id)), 'cmb_delete_' . $id)) . '" class="submitdelete" onclick="return confirm(\'Delete this field group?\')">Delete</a>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';

        // Settings box
        echo '<div class="postbox">';
        echo '<div class="postbox-header"><h2>Settings</h2></div>';
        echo '<div class="inside">';

        // Meta Box ID
        echo '<div class="cmb-setting-row">';
        echo '<label for="cmb-box-id">Meta Box ID</label>';
        echo '<input type="text" name="cmb_box_id" id="cmb-box-id" class="widefat" value="' . esc_attr($boxId) . '" placeholder="e.g. my_custom_fields"' . ($action === 'edit' ? ' readonly' : ' required') . '>';
        if ($action !== 'edit') {
            echo '<p class="description">Auto-generated from title if left empty.</p>';
        }
        echo '</div>';

        // Show in REST
        echo '<div class="cmb-setting-row">';
        echo '<label class="cmb-checkbox-label">';
        echo '<input type="hidden" name="cmb_show_in_rest" value="0">';
        echo '<input type="checkbox" name="cmb_show_in_rest" value="1"' . ($showInRest ? ' checked' : '') . '>';
        echo ' Expose in REST API';
        echo '</label>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        // Location box
        echo '<div class="postbox">';
        echo '<div class="postbox-header"><h2>Location</h2></div>';
        echo '<div class="inside">';
        echo '<p class="cmb-setting-description">Show this field group on:</p>';
        echo '<div class="cmb-post-types-list">';
        $allPostTypes = get_post_types(['public' => true], 'objects');
        foreach ($allPostTypes as $ptSlug => $ptObj) {
            $checked = in_array($ptSlug, $postTypes, true) ? ' checked' : '';
            echo '<label class="cmb-pt-checkbox">';
            echo '<input type="checkbox" name="cmb_box_post_types[]" value="' . esc_attr($ptSlug) . '"' . $checked . '>';
            echo ' <span class="dashicons ' . esc_attr(self::getPostTypeIcon($ptSlug)) . '"></span> ';
            echo esc_html($ptObj->labels->singular_name);
            echo '</label>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';

        // Position box
        echo '<div class="postbox">';
        echo '<div class="postbox-header"><h2>Position</h2></div>';
        echo '<div class="inside">';

        echo '<div class="cmb-setting-row">';
        echo '<label for="cmb-context">Context</label>';
        echo '<select name="cmb_box_context" id="cmb-context" class="widefat">';
        foreach (['normal' => 'Normal', 'advanced' => 'Advanced', 'side' => 'Side'] as $val => $label) {
            $sel = ($context === $val) ? ' selected' : '';
            echo '<option value="' . $val . '"' . $sel . '>' . $label . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '<div class="cmb-setting-row">';
        echo '<label for="cmb-priority">Priority</label>';
        echo '<select name="cmb_box_priority" id="cmb-priority" class="widefat">';
        foreach (['high' => 'High', 'default' => 'Default', 'low' => 'Low'] as $val => $label) {
            $sel = ($priority === $val) ? ' selected' : '';
            echo '<option value="' . $val . '"' . $sel . '>' . $label . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        echo '</div>'; // #postbox-container-1
        echo '</div>'; // #post-body
        echo '</div>'; // #poststuff
        echo '</form>';

        // Field type picker modal
        self::renderFieldTypePicker();

        echo '</div>'; // .wrap
    }

    /* ─── Field Row Rendering ─────────────────────────────── */

    private static function renderFieldRow(int $index, array $field): void {
        $type        = $field['type'] ?? 'text';
        $label       = $field['label'] ?? '';
        $fieldId     = $field['id'] ?? '';
        $description = $field['description'] ?? '';
        $required    = !empty($field['required']);
        $placeholder = $field['placeholder'] ?? '';
        $default     = $field['default_value'] ?? ($field['default'] ?? '');
        $options     = $field['options'] ?? '';
        $rows        = $field['rows'] ?? '';
        $min         = $field['min'] ?? '';
        $max         = $field['max'] ?? '';
        $step        = $field['step'] ?? '';
        $postType    = $field['post_type'] ?? 'post';
        $taxonomy    = $field['taxonomy'] ?? 'category';
        $role        = $field['role'] ?? '';
        $width       = $field['width'] ?? '100';
        $repeatable  = !empty($field['repeatable']);
        $collapsed   = $field['collapsed'] ?? true;
        $minRows     = $field['min_rows'] ?? '';
        $maxRows     = $field['max_rows'] ?? '';

        $typeInfo  = self::getFieldTypesFlat()[$type] ?? ['label' => ucfirst($type), 'icon' => 'dashicons-admin-generic'];
        $typeLabel = $typeInfo['label'];
        $typeIcon  = $typeInfo['icon'];

        // Convert options array back to text format for editing
        if (is_array($options)) {
            $lines = [];
            foreach ($options as $k => $v) {
                $lines[] = $k . '|' . $v;
            }
            $options = implode("\n", $lines);
        }

        echo '<div class="cmb-field-row" data-index="' . $index . '" data-type="' . esc_attr($type) . '">';

        // Header
        echo '<div class="cmb-field-row-header">';
        echo '<span class="cmb-field-drag dashicons dashicons-menu"></span>';
        echo '<span class="cmb-field-icon dashicons ' . esc_attr($typeIcon) . '"></span>';
        echo '<span class="cmb-field-row-label">' . ($label ? esc_html($label) : '<em>New Field</em>') . '</span>';
        echo '<span class="cmb-field-row-meta">';
        echo '<span class="cmb-field-row-type">' . esc_html($typeLabel) . '</span>';
        if ($fieldId) {
            echo '<code class="cmb-field-row-id">' . esc_html($fieldId) . '</code>';
        }
        if ($required) {
            echo '<span class="cmb-required-badge">Required</span>';
        }
        echo '</span>';
        echo '<span class="cmb-field-row-actions">';
        echo '<button type="button" class="cmb-field-duplicate" title="Duplicate"><span class="dashicons dashicons-admin-page"></span></button>';
        echo '<button type="button" class="cmb-field-remove" title="Delete"><span class="dashicons dashicons-trash"></span></button>';
        echo '<button type="button" class="cmb-field-toggle"><span class="dashicons dashicons-arrow-down-alt2"></span></button>';
        echo '</span>';
        echo '</div>';

        // Body
        echo '<div class="cmb-field-row-body">';

        // General settings
        $prefix = 'cmb_fields[' . $index . ']';

        echo '<div class="cmb-field-settings-grid">';

        // Label
        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>Field Label</label>';
        echo '<input type="text" name="' . $prefix . '[label]" value="' . esc_attr($label) . '" class="widefat cmb-field-label-input" placeholder="e.g. Author Name">';
        echo '</div>';

        // ID
        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>Field ID <small class="cmb-auto-id">(auto)</small></label>';
        echo '<input type="text" name="' . $prefix . '[id]" value="' . esc_attr($fieldId) . '" class="widefat cmb-field-id-input" placeholder="auto_generated" required>';
        echo '</div>';

        // Type
        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>Field Type</label>';
        echo '<select name="' . $prefix . '[type]" class="widefat cmb-field-type-select">';
        $categories = self::getFieldTypeCategories();
        foreach ($categories as $cat) {
            echo '<optgroup label="' . esc_attr($cat['label']) . '">';
            foreach ($cat['types'] as $tKey => $tInfo) {
                $sel = ($type === $tKey) ? ' selected' : '';
                echo '<option value="' . esc_attr($tKey) . '"' . $sel . '>' . esc_html($tInfo['label']) . '</option>';
            }
            echo '</optgroup>';
        }
        echo '</select>';
        echo '</div>';

        // Description
        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>Description</label>';
        echo '<input type="text" name="' . $prefix . '[description]" value="' . esc_attr($description) . '" class="widefat" placeholder="Help text shown below the field">';
        echo '</div>';

        echo '</div>'; // .cmb-field-settings-grid

        // Type-specific options
        echo '<div class="cmb-type-options">';

        // Placeholder (text, textarea, number, email, url, password)
        echo '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="text,textarea,number,email,url,password">';
        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>Placeholder</label>';
        echo '<input type="text" name="' . $prefix . '[placeholder]" value="' . esc_attr($placeholder) . '" class="widefat">';
        echo '</div>';
        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>Default Value</label>';
        echo '<input type="text" name="' . $prefix . '[default_value]" value="' . esc_attr($default) . '" class="widefat">';
        echo '</div>';
        echo '</div>';

        // Default for date, color, hidden
        echo '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="date,color,hidden">';
        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>Default Value</label>';
        echo '<input type="text" name="' . $prefix . '[default_value_dc]" value="' . esc_attr($default) . '" class="widefat">';
        echo '</div>';
        echo '</div>';

        // Textarea/WYSIWYG rows
        echo '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="textarea,wysiwyg">';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>Rows</label>';
        echo '<input type="number" name="' . $prefix . '[rows]" value="' . esc_attr($rows) . '" class="widefat" placeholder="5" min="1" max="50">';
        echo '</div>';
        echo '</div>';

        // Number min/max/step
        echo '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="number">';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>Min</label>';
        echo '<input type="number" name="' . $prefix . '[min]" value="' . esc_attr($min) . '" class="widefat">';
        echo '</div>';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>Max</label>';
        echo '<input type="number" name="' . $prefix . '[max]" value="' . esc_attr($max) . '" class="widefat">';
        echo '</div>';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>Step</label>';
        echo '<input type="number" name="' . $prefix . '[step]" value="' . esc_attr($step) . '" class="widefat" placeholder="1">';
        echo '</div>';
        echo '</div>';

        // Select/Radio options
        echo '<div class="cmb-type-opt" data-show-for="select,radio">';
        echo '<div class="cmb-fs-row">';
        echo '<label>Options <small>One per line: <code>value|Label</code></small></label>';
        echo '<textarea name="' . $prefix . '[options]" class="widefat cmb-options-textarea" rows="4" placeholder="option1|Option One&#10;option2|Option Two">' . esc_textarea($options) . '</textarea>';
        echo '</div>';
        echo '</div>';

        // Post type for post field
        echo '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="post">';
        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>Post Type</label>';
        echo '<select name="' . $prefix . '[post_type]" class="widefat">';
        $allPt = get_post_types(['public' => true], 'objects');
        foreach ($allPt as $ptSlug => $ptObj) {
            $sel = ($postType === $ptSlug) ? ' selected' : '';
            echo '<option value="' . esc_attr($ptSlug) . '"' . $sel . '>' . esc_html($ptObj->labels->singular_name) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</div>';

        // Taxonomy for taxonomy field
        echo '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="taxonomy">';
        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>Taxonomy</label>';
        echo '<select name="' . $prefix . '[taxonomy]" class="widefat">';
        $allTax = get_taxonomies(['public' => true], 'objects');
        foreach ($allTax as $taxSlug => $taxObj) {
            $sel = ($taxonomy === $taxSlug) ? ' selected' : '';
            echo '<option value="' . esc_attr($taxSlug) . '"' . $sel . '>' . esc_html($taxObj->labels->singular_name) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</div>';

        // User role for user field
        echo '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="user">';
        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>User Role <small>(empty = all)</small></label>';
        echo '<select name="' . $prefix . '[role]" class="widefat">';
        echo '<option value="">All Roles</option>';
        foreach (wp_roles()->roles as $rSlug => $rData) {
            $sel = ($role === $rSlug) ? ' selected' : '';
            echo '<option value="' . esc_attr($rSlug) . '"' . $sel . '>' . esc_html($rData['name']) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</div>';

        // Group settings
        echo '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="group">';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>Min Rows</label>';
        echo '<input type="number" name="' . $prefix . '[min_rows]" value="' . esc_attr($minRows) . '" class="widefat" min="0">';
        echo '</div>';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>Max Rows</label>';
        echo '<input type="number" name="' . $prefix . '[max_rows]" value="' . esc_attr($maxRows) . '" class="widefat" min="0">';
        echo '</div>';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label class="cmb-checkbox-label" style="margin-top:24px">';
        echo '<input type="checkbox" name="' . $prefix . '[collapsed]" value="1"' . ($collapsed ? ' checked' : '') . '>';
        echo ' Collapsed';
        echo '</label>';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // .cmb-type-options

        // Bottom row: required, width, repeatable
        echo '<div class="cmb-field-bottom-row">';

        echo '<label class="cmb-checkbox-label">';
        echo '<input type="checkbox" name="' . $prefix . '[required]" value="1"' . ($required ? ' checked' : '') . '>';
        echo ' Required';
        echo '</label>';

        echo '<label class="cmb-checkbox-label cmb-type-opt-inline" data-hide-for="group,checkbox,hidden">';
        echo '<input type="checkbox" name="' . $prefix . '[repeatable]" value="1"' . ($repeatable ? ' checked' : '') . '>';
        echo ' Repeatable';
        echo '</label>';

        echo '<div class="cmb-width-control">';
        echo '<label>Width</label>';
        echo '<select name="' . $prefix . '[width]">';
        foreach (['100' => '100%', '75' => '75%', '50' => '50%', '33' => '33%', '25' => '25%'] as $wVal => $wLabel) {
            $sel = ($width == $wVal) ? ' selected' : '';
            echo '<option value="' . $wVal . '"' . $sel . '>' . $wLabel . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '</div>'; // .cmb-field-bottom-row

        echo '</div>'; // .cmb-field-row-body
        echo '</div>'; // .cmb-field-row
    }

    /* ─── Field Type Picker Modal ─────────────────────────── */

    private static function renderFieldTypePicker(): void {
        echo '<div class="cmb-type-picker-overlay" id="cmb-type-picker" style="display:none">';
        echo '<div class="cmb-type-picker-modal">';
        echo '<div class="cmb-type-picker-header">';
        echo '<h2>Add Field</h2>';
        echo '<input type="text" class="cmb-type-picker-search" id="cmb-type-search" placeholder="Search field types..." autocomplete="off">';
        echo '<button type="button" class="cmb-type-picker-close" id="cmb-type-picker-close">&times;</button>';
        echo '</div>';
        echo '<div class="cmb-type-picker-body">';

        foreach (self::getFieldTypeCategories() as $cat) {
            echo '<div class="cmb-type-picker-category">';
            echo '<h3>' . esc_html($cat['label']) . '</h3>';
            echo '<div class="cmb-type-picker-grid">';
            foreach ($cat['types'] as $tKey => $tInfo) {
                echo '<button type="button" class="cmb-type-picker-item" data-type="' . esc_attr($tKey) . '">';
                echo '<span class="dashicons ' . esc_attr($tInfo['icon']) . '"></span>';
                echo '<span class="cmb-type-picker-name">' . esc_html($tInfo['label']) . '</span>';
                echo '</button>';
            }
            echo '</div>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    /* ─── Import Modal ────────────────────────────────────── */

    private static function renderImportModal(): void {
        echo '<div class="cmb-import-overlay" id="cmb-import-modal" style="display:none">';
        echo '<div class="cmb-import-modal">';
        echo '<div class="cmb-import-header">';
        echo '<h2>Import Field Groups</h2>';
        echo '<button type="button" class="cmb-import-close" id="cmb-import-close">&times;</button>';
        echo '</div>';
        echo '<form method="post" enctype="multipart/form-data" class="cmb-import-body">';
        wp_nonce_field('cmb_import', 'cmb_import_nonce');
        echo '<input type="hidden" name="cmb_action" value="import">';
        echo '<div class="cmb-import-section">';
        echo '<label class="cmb-import-file-label">';
        echo '<span class="dashicons dashicons-media-default"></span>';
        echo '<span>Choose a JSON file or drag it here</span>';
        echo '<input type="file" name="cmb_import_file" accept=".json" class="cmb-import-file-input">';
        echo '</label>';
        echo '</div>';
        echo '<div class="cmb-import-divider"><span>or</span></div>';
        echo '<div class="cmb-import-section">';
        echo '<label>Paste JSON</label>';
        echo '<textarea name="cmb_import_json" rows="8" class="widefat code" placeholder=\'{"version":"1.0","meta_boxes":{...}}\'></textarea>';
        echo '</div>';
        echo '<div class="cmb-import-actions">';
        echo '<button type="submit" class="button button-primary">Import</button>';
        echo '<button type="button" class="button cmb-import-cancel">Cancel</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
    }

    /* ─── Notices ─────────────────────────────────────────── */

    private static function renderNotices(): void {
        if (!empty($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Field group saved successfully.</p></div>';
        }
        if (!empty($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Field group deleted.</p></div>';
        }
        if (!empty($_GET['duplicated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Field group duplicated.</p></div>';
        }
        if (!empty($_GET['imported'])) {
            $count = intval($_GET['imported']);
            echo '<div class="notice notice-success is-dismissible"><p>Imported ' . $count . ' field group(s) successfully.</p></div>';
        }
        if (!empty($_GET['toggled'])) {
            echo '<div class="notice notice-info is-dismissible"><p>Field group status updated.</p></div>';
        }
        if (!empty($_GET['error'])) {
            $errors = [
                'invalid_json'  => 'Invalid JSON format.',
                'no_data'       => 'No import data provided.',
                'import_failed' => 'Import failed. Check your JSON structure.',
            ];
            $msg = $errors[sanitize_text_field($_GET['error'])] ?? 'An error occurred.';
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($msg) . '</p></div>';
        }
    }

    /* ─── Action Handlers ─────────────────────────────────── */

    public static function handleSave(): void {
        if (empty($_POST['cmb_builder_submit'])) {
            return;
        }
        if (!isset($_POST['cmb_builder_nonce']) || !wp_verify_nonce($_POST['cmb_builder_nonce'], 'cmb_builder_save')) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $editing   = sanitize_text_field($_POST['cmb_editing'] ?? '');
        $id        = $editing ?: sanitize_text_field($_POST['cmb_box_id'] ?? '');
        $title     = sanitize_text_field($_POST['cmb_box_title'] ?? '');
        $postTypes = array_map('sanitize_text_field', $_POST['cmb_box_post_types'] ?? ['post']);
        $context   = sanitize_text_field($_POST['cmb_box_context'] ?? 'advanced');
        $priority  = sanitize_text_field($_POST['cmb_box_priority'] ?? 'default');
        $active    = !empty($_POST['cmb_active']);
        $showRest  = !empty($_POST['cmb_show_in_rest']);

        // Auto-generate ID from title if empty
        if (empty($id) && !empty($title)) {
            $id = sanitize_title($title);
            $id = str_replace('-', '_', $id);
        }

        if (empty($id) || empty($title)) {
            return;
        }

        $fields = [];
        if (!empty($_POST['cmb_fields']) && is_array($_POST['cmb_fields'])) {
            foreach ($_POST['cmb_fields'] as $f) {
                if (empty($f['id'])) {
                    continue;
                }
                $field = [
                    'id'          => sanitize_text_field($f['id']),
                    'type'        => sanitize_text_field($f['type'] ?? 'text'),
                    'label'       => sanitize_text_field($f['label'] ?? ''),
                    'description' => sanitize_text_field($f['description'] ?? ''),
                    'required'    => !empty($f['required']),
                    'placeholder' => sanitize_text_field($f['placeholder'] ?? ''),
                    'default_value' => sanitize_text_field($f['default_value'] ?? ($f['default_value_dc'] ?? '')),
                    'rows'        => intval($f['rows'] ?? 0) ?: '',
                    'min'         => $f['min'] ?? '',
                    'max'         => $f['max'] ?? '',
                    'step'        => $f['step'] ?? '',
                    'post_type'   => sanitize_text_field($f['post_type'] ?? ''),
                    'taxonomy'    => sanitize_text_field($f['taxonomy'] ?? ''),
                    'role'        => sanitize_text_field($f['role'] ?? ''),
                    'width'       => sanitize_text_field($f['width'] ?? '100'),
                    'repeatable'  => !empty($f['repeatable']),
                    'collapsed'   => !empty($f['collapsed']),
                    'min_rows'    => intval($f['min_rows'] ?? 0) ?: '',
                    'max_rows'    => intval($f['max_rows'] ?? 0) ?: '',
                ];

                // Parse options textarea into associative array
                if (!empty($f['options']) && in_array($field['type'], ['select', 'radio'], true)) {
                    $optLines = explode("\n", trim($f['options']));
                    $opts = [];
                    foreach ($optLines as $line) {
                        $line = trim($line);
                        if ($line === '') continue;
                        if (strpos($line, '|') !== false) {
                            [$val, $lbl] = explode('|', $line, 2);
                            $opts[trim($val)] = trim($lbl);
                        } else {
                            $opts[sanitize_title($line)] = $line;
                        }
                    }
                    $field['options'] = $opts;
                }

                // Clean up empty values
                $field = array_filter($field, function($v) {
                    return $v !== '' && $v !== null;
                });
                // Always keep id and type
                $field['id'] = sanitize_text_field($f['id']);
                $field['type'] = sanitize_text_field($f['type'] ?? 'text');

                $fields[] = $field;
            }
        }

        $configs = get_option(self::OPTION_KEY, []);
        $configs[$id] = [
            'title'        => $title,
            'postTypes'    => $postTypes,
            'fields'       => $fields,
            'context'      => $context,
            'priority'     => $priority,
            'active'       => $active,
            'show_in_rest' => $showRest,
        ];
        update_option(self::OPTION_KEY, $configs);

        wp_safe_redirect(admin_url('admin.php?page=cmb-builder&action=edit&id=' . urlencode($id) . '&updated=1'));
        exit;
    }

    public static function handleDelete(): void {
        if (empty($_GET['cmb_delete']) || empty($_GET['page']) || $_GET['page'] !== 'cmb-builder') {
            return;
        }

        $id = sanitize_text_field($_GET['cmb_delete']);
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'cmb_delete_' . $id)) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $configs = get_option(self::OPTION_KEY, []);
        unset($configs[$id]);
        update_option(self::OPTION_KEY, $configs);

        wp_safe_redirect(admin_url('admin.php?page=cmb-builder&deleted=1'));
        exit;
    }

    public static function handleDuplicate(): void {
        if (empty($_GET['cmb_duplicate']) || empty($_GET['page']) || $_GET['page'] !== 'cmb-builder') {
            return;
        }

        $id = sanitize_text_field($_GET['cmb_duplicate']);
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'cmb_duplicate_' . $id)) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $configs = get_option(self::OPTION_KEY, []);
        if (!isset($configs[$id])) {
            return;
        }

        $newId = $id . '_copy';
        $counter = 1;
        while (isset($configs[$newId])) {
            $newId = $id . '_copy_' . $counter;
            $counter++;
        }

        $configs[$newId] = $configs[$id];
        $configs[$newId]['title'] .= ' (Copy)';
        update_option(self::OPTION_KEY, $configs);

        wp_safe_redirect(admin_url('admin.php?page=cmb-builder&duplicated=1'));
        exit;
    }

    public static function handleToggle(): void {
        if (empty($_GET['cmb_toggle']) || empty($_GET['page']) || $_GET['page'] !== 'cmb-builder') {
            return;
        }

        $id = sanitize_text_field($_GET['cmb_toggle']);
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'cmb_toggle_' . $id)) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $configs = get_option(self::OPTION_KEY, []);
        if (!isset($configs[$id])) {
            return;
        }

        $configs[$id]['active'] = !($configs[$id]['active'] ?? true);
        update_option(self::OPTION_KEY, $configs);

        wp_safe_redirect(admin_url('admin.php?page=cmb-builder&toggled=1'));
        exit;
    }

    public static function handleExport(): void {
        if (empty($_GET['cmb_export']) || empty($_GET['page']) || $_GET['page'] !== 'cmb-builder') {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $exportId = sanitize_text_field($_GET['cmb_export']);
        $configs  = get_option(self::OPTION_KEY, []);

        if ($exportId === 'all') {
            if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'cmb_export_all')) {
                return;
            }
            $data = $configs;
            $filename = 'cmb-all-field-groups-' . gmdate('Y-m-d') . '.json';
        } else {
            if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'cmb_export_' . $exportId)) {
                return;
            }
            if (!isset($configs[$exportId])) {
                return;
            }
            $data = [$exportId => $configs[$exportId]];
            $filename = 'cmb-' . $exportId . '-' . gmdate('Y-m-d') . '.json';
        }

        $export = [
            'version'     => '2.0',
            'plugin'      => 'custom-meta-box-builder',
            'exported_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'field_groups' => $data,
        ];

        $json = wp_json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json));
        echo $json;
        exit;
    }

    public static function handleImport(): void {
        if (empty($_POST['cmb_action']) || $_POST['cmb_action'] !== 'import') {
            return;
        }
        if (!isset($_POST['cmb_import_nonce']) || !wp_verify_nonce($_POST['cmb_import_nonce'], 'cmb_import')) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $json = '';
        if (!empty($_FILES['cmb_import_file']['tmp_name'])) {
            $json = file_get_contents($_FILES['cmb_import_file']['tmp_name']);
        } elseif (!empty($_POST['cmb_import_json'])) {
            $json = wp_unslash($_POST['cmb_import_json']);
        }

        if (empty($json)) {
            wp_safe_redirect(admin_url('admin.php?page=cmb-builder&error=no_data'));
            exit;
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_safe_redirect(admin_url('admin.php?page=cmb-builder&error=invalid_json'));
            exit;
        }

        // Support both v1 (meta_boxes) and v2 (field_groups) format
        $groups = $data['field_groups'] ?? $data['meta_boxes'] ?? null;
        if (empty($groups) || !is_array($groups)) {
            wp_safe_redirect(admin_url('admin.php?page=cmb-builder&error=import_failed'));
            exit;
        }

        $configs = get_option(self::OPTION_KEY, []);
        $count   = 0;

        foreach ($groups as $id => $box) {
            if (empty($box['title'])) {
                continue;
            }
            $configs[$id] = [
                'title'        => sanitize_text_field($box['title']),
                'postTypes'    => array_map('sanitize_text_field', $box['postTypes'] ?? ['post']),
                'fields'       => $box['fields'] ?? [],
                'context'      => sanitize_text_field($box['context'] ?? 'advanced'),
                'priority'     => sanitize_text_field($box['priority'] ?? 'default'),
                'active'       => $box['active'] ?? true,
                'show_in_rest' => $box['show_in_rest'] ?? false,
            ];
            $count++;
        }

        update_option(self::OPTION_KEY, $configs);

        wp_safe_redirect(admin_url('admin.php?page=cmb-builder&imported=' . $count));
        exit;
    }

    /* ─── Register Saved Boxes ────────────────────────────── */

    public static function registerSavedBoxes(): void {
        $configs = get_option(self::OPTION_KEY, []);
        if (empty($configs)) {
            return;
        }

        $manager = MetaBoxManager::instance();
        foreach ($configs as $id => $box) {
            // Skip inactive groups
            if (isset($box['active']) && !$box['active']) {
                continue;
            }

            $fields = self::transformFieldsForRegistration($box['fields'] ?? []);

            $manager->add(
                $id,
                $box['title'],
                $box['postTypes'],
                $fields,
                $box['context'] ?? 'advanced',
                $box['priority'] ?? 'default'
            );
        }
    }

    /**
     * Transform admin-stored field format to the format MetaBoxManager expects.
     */
    private static function transformFieldsForRegistration(array $fields): array {
        $transformed = [];
        foreach ($fields as $field) {
            $f = [
                'id'   => $field['id'],
                'type' => $field['type'],
            ];

            if (!empty($field['label']))       $f['label']       = $field['label'];
            if (!empty($field['description'])) $f['description'] = $field['description'];
            if (!empty($field['required']))    $f['required']    = true;
            if (!empty($field['placeholder'])) $f['placeholder'] = $field['placeholder'];
            if (!empty($field['default_value'])) $f['default']   = $field['default_value'];
            if (!empty($field['options']))      $f['options']     = $field['options'];
            if (!empty($field['rows']))         $f['rows']        = (int) $field['rows'];
            if ($field['min'] !== '' && isset($field['min'])) $f['min'] = $field['min'];
            if ($field['max'] !== '' && isset($field['max'])) $f['max'] = $field['max'];
            if (!empty($field['step']))         $f['step']        = $field['step'];
            if (!empty($field['post_type']))    $f['post_type']   = $field['post_type'];
            if (!empty($field['taxonomy']))     $f['taxonomy']    = $field['taxonomy'];
            if (!empty($field['role']))         $f['role']        = $field['role'];
            if (!empty($field['repeatable']))   $f['repeat']      = true;
            if (isset($field['collapsed']))     $f['collapsed']   = (bool) $field['collapsed'];
            if (!empty($field['min_rows']))     $f['min_rows']    = (int) $field['min_rows'];
            if (!empty($field['max_rows']))     $f['max_rows']    = (int) $field['max_rows'];

            $transformed[] = $f;
        }
        return $transformed;
    }

    /* ─── Field Type Definitions ──────────────────────────── */

    private static function getFieldTypeCategories(): array {
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

    private static function getFieldTypesFlat(): array {
        $flat = [];
        foreach (self::getFieldTypeCategories() as $cat) {
            foreach ($cat['types'] as $key => $info) {
                $flat[$key] = $info;
            }
        }
        return $flat;
    }

    /* ─── Helpers ─────────────────────────────────────────── */

    private static function getPostTypeIcon(string $slug): string {
        $icons = [
            'post'       => 'dashicons-admin-post',
            'page'       => 'dashicons-admin-page',
            'attachment' => 'dashicons-admin-media',
        ];
        return $icons[$slug] ?? 'dashicons-admin-generic';
    }

    private static function getTaxonomyList(): array {
        $list = [];
        foreach (get_taxonomies(['public' => true], 'objects') as $slug => $obj) {
            $list[$slug] = $obj->labels->singular_name;
        }
        return $list;
    }

    private static function getRoleList(): array {
        $list = ['all' => 'All Roles'];
        foreach (wp_roles()->roles as $slug => $data) {
            $list[$slug] = $data['name'];
        }
        return $list;
    }
}
