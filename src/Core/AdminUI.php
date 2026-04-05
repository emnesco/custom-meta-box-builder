<?php
namespace CMB\Core;

/**
 * Admin UI for creating meta boxes without code (8.2).
 *
 * Stores configurations as a WordPress option and registers them on init.
 */
class AdminUI {
    private const OPTION_KEY = 'cmb_admin_configurations';
    private const AVAILABLE_TYPES = [
        'text', 'textarea', 'number', 'email', 'url', 'radio', 'select',
        'checkbox', 'hidden', 'password', 'date', 'color', 'wysiwyg',
        'file', 'post', 'taxonomy', 'user', 'group',
    ];

    public static function register(): void {
        add_action('admin_menu', [self::class, 'addAdminPage']);
        add_action('admin_init', [self::class, 'handleSave']);
        add_action('admin_init', [self::class, 'handleDelete']);
        add_action('init', [self::class, 'registerSavedBoxes'], 20);
    }

    public static function addAdminPage(): void {
        add_menu_page(
            'Meta Box Builder',
            'Meta Box Builder',
            'manage_options',
            'cmb-builder',
            [self::class, 'renderPage'],
            'dashicons-editor-table',
            80
        );
    }

    public static function renderPage(): void {
        $configs = get_option(self::OPTION_KEY, []);
        $editing = isset($_GET['edit']) ? sanitize_text_field($_GET['edit']) : null;
        $editBox = $editing && isset($configs[$editing]) ? $configs[$editing] : null;

        echo '<div class="wrap">';
        echo '<h1>Meta Box Builder</h1>';

        // List existing
        if (!empty($configs)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>ID</th><th>Title</th><th>Post Types</th><th>Fields</th><th>Actions</th></tr></thead><tbody>';
            foreach ($configs as $id => $box) {
                $fieldCount = count($box['fields'] ?? []);
                echo '<tr>';
                echo '<td>' . esc_html($id) . '</td>';
                echo '<td>' . esc_html($box['title']) . '</td>';
                echo '<td>' . esc_html(implode(', ', $box['postTypes'])) . '</td>';
                echo '<td>' . $fieldCount . ' field(s)</td>';
                echo '<td>';
                echo '<a href="' . esc_url(admin_url('admin.php?page=cmb-builder&edit=' . $id)) . '">Edit</a> | ';
                echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=cmb-builder&delete=' . $id), 'cmb_delete_' . $id)) . '" onclick="return confirm(\'Delete this meta box?\')">Delete</a>';
                echo '</td></tr>';
            }
            echo '</tbody></table>';
        }

        // Add/Edit form
        echo '<h2>' . ($editBox ? 'Edit Meta Box' : 'Add New Meta Box') . '</h2>';
        echo '<form method="post">';
        wp_nonce_field('cmb_builder_save', 'cmb_builder_nonce');
        if ($editing) {
            echo '<input type="hidden" name="cmb_editing" value="' . esc_attr($editing) . '">';
        }

        echo '<table class="form-table">';
        echo '<tr><th><label for="cmb_box_id">Meta Box ID</label></th>';
        echo '<td><input type="text" name="cmb_box_id" id="cmb_box_id" value="' . esc_attr($editing ?? '') . '" class="regular-text"' . ($editing ? ' readonly' : '') . ' required></td></tr>';

        echo '<tr><th><label for="cmb_box_title">Title</label></th>';
        echo '<td><input type="text" name="cmb_box_title" id="cmb_box_title" value="' . esc_attr($editBox['title'] ?? '') . '" class="regular-text" required></td></tr>';

        echo '<tr><th><label for="cmb_box_post_types">Post Types</label></th>';
        echo '<td><input type="text" name="cmb_box_post_types" id="cmb_box_post_types" value="' . esc_attr(implode(',', $editBox['postTypes'] ?? ['post'])) . '" class="regular-text">';
        echo '<p class="description">Comma-separated: post,page,custom_type</p></td></tr>';

        echo '<tr><th><label>Context</label></th>';
        echo '<td><select name="cmb_box_context">';
        foreach (['advanced', 'normal', 'side'] as $ctx) {
            $sel = (($editBox['context'] ?? 'advanced') === $ctx) ? ' selected' : '';
            echo '<option value="' . $ctx . '"' . $sel . '>' . $ctx . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th><label>Priority</label></th>';
        echo '<td><select name="cmb_box_priority">';
        foreach (['default', 'high', 'low'] as $pri) {
            $sel = (($editBox['priority'] ?? 'default') === $pri) ? ' selected' : '';
            echo '<option value="' . $pri . '"' . $sel . '>' . $pri . '</option>';
        }
        echo '</select></td></tr>';
        echo '</table>';

        // Fields
        echo '<h3>Fields</h3>';
        echo '<div id="cmb-builder-fields">';
        $fields = $editBox['fields'] ?? [['id' => '', 'type' => 'text', 'label' => '']];
        foreach ($fields as $i => $f) {
            self::renderFieldRow($i, $f);
        }
        echo '</div>';
        echo '<p><button type="button" class="button" onclick="cmbAddFieldRow()">Add Field</button></p>';

        echo '<p><input type="submit" name="cmb_builder_submit" class="button button-primary" value="Save Meta Box"></p>';
        echo '</form>';

        // Inline JS for add/remove field rows
        echo '<script>
        var cmbFieldIndex = ' . count($fields) . ';
        function cmbAddFieldRow() {
            var container = document.getElementById("cmb-builder-fields");
            var html = cmbFieldRowHtml(cmbFieldIndex);
            container.insertAdjacentHTML("beforeend", html);
            cmbFieldIndex++;
        }
        function cmbRemoveFieldRow(btn) {
            btn.closest(".cmb-builder-field-row").remove();
        }
        function cmbFieldRowHtml(idx) {
            var types = ' . json_encode(self::AVAILABLE_TYPES) . ';
            var opts = types.map(function(t){ return "<option value=\""+t+"\">"+t+"</option>"; }).join("");
            return "<div class=\"cmb-builder-field-row\" style=\"border:1px solid #ddd;padding:10px;margin:5px 0;background:#fafafa\">"
                + "<label>ID: <input type=\"text\" name=\"cmb_fields["+idx+"][id]\" required></label> "
                + "<label>Label: <input type=\"text\" name=\"cmb_fields["+idx+"][label]\"></label> "
                + "<label>Type: <select name=\"cmb_fields["+idx+"][type]\">"+opts+"</select></label> "
                + "<label>Description: <input type=\"text\" name=\"cmb_fields["+idx+"][description]\"></label> "
                + "<button type=\"button\" class=\"button\" onclick=\"cmbRemoveFieldRow(this)\">Remove</button>"
                + "</div>";
        }
        </script>';

        echo '</div>';
    }

    private static function renderFieldRow(int $index, array $field): void {
        echo '<div class="cmb-builder-field-row" style="border:1px solid #ddd;padding:10px;margin:5px 0;background:#fafafa">';
        echo '<label>ID: <input type="text" name="cmb_fields[' . $index . '][id]" value="' . esc_attr($field['id'] ?? '') . '" required></label> ';
        echo '<label>Label: <input type="text" name="cmb_fields[' . $index . '][label]" value="' . esc_attr($field['label'] ?? '') . '"></label> ';
        echo '<label>Type: <select name="cmb_fields[' . $index . '][type]">';
        foreach (self::AVAILABLE_TYPES as $t) {
            $sel = (($field['type'] ?? 'text') === $t) ? ' selected' : '';
            echo '<option value="' . $t . '"' . $sel . '>' . $t . '</option>';
        }
        echo '</select></label> ';
        echo '<label>Description: <input type="text" name="cmb_fields[' . $index . '][description]" value="' . esc_attr($field['description'] ?? '') . '"></label> ';
        echo '<button type="button" class="button" onclick="cmbRemoveFieldRow(this)">Remove</button>';
        echo '</div>';
    }

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

        $id = sanitize_text_field($_POST['cmb_box_id'] ?? '');
        $title = sanitize_text_field($_POST['cmb_box_title'] ?? '');
        $postTypes = array_map('trim', explode(',', sanitize_text_field($_POST['cmb_box_post_types'] ?? 'post')));
        $context = sanitize_text_field($_POST['cmb_box_context'] ?? 'advanced');
        $priority = sanitize_text_field($_POST['cmb_box_priority'] ?? 'default');

        $fields = [];
        if (!empty($_POST['cmb_fields']) && is_array($_POST['cmb_fields'])) {
            foreach ($_POST['cmb_fields'] as $f) {
                if (empty($f['id'])) continue;
                $fields[] = [
                    'id' => sanitize_text_field($f['id']),
                    'type' => sanitize_text_field($f['type'] ?? 'text'),
                    'label' => sanitize_text_field($f['label'] ?? ''),
                    'description' => sanitize_text_field($f['description'] ?? ''),
                ];
            }
        }

        if (empty($id) || empty($title)) {
            return;
        }

        $configs = get_option(self::OPTION_KEY, []);
        $configs[$id] = [
            'title' => $title,
            'postTypes' => $postTypes,
            'fields' => $fields,
            'context' => $context,
            'priority' => $priority,
        ];
        update_option(self::OPTION_KEY, $configs);

        wp_safe_redirect(admin_url('admin.php?page=cmb-builder&updated=1'));
        exit;
    }

    public static function handleDelete(): void {
        if (empty($_GET['delete']) || empty($_GET['page']) || $_GET['page'] !== 'cmb-builder') {
            return;
        }

        $id = sanitize_text_field($_GET['delete']);
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

    /**
     * Register saved meta box configurations on init.
     */
    public static function registerSavedBoxes(): void {
        $configs = get_option(self::OPTION_KEY, []);
        if (empty($configs)) {
            return;
        }

        $manager = MetaBoxManager::instance();
        foreach ($configs as $id => $box) {
            $manager->add(
                $id,
                $box['title'],
                $box['postTypes'],
                $box['fields'],
                $box['context'] ?? 'advanced',
                $box['priority'] ?? 'default'
            );
        }
    }
}
