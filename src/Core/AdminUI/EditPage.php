<?php
namespace CMB\Core\AdminUI;

class EditPage {
    public static function renderEditPage(string $action, string $id): void {
        $configs = ActionHandler::getConfigs();
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

        ListPage::renderNotices();

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
            echo ' <span class="dashicons ' . esc_attr(Router::getPostTypeIcon($ptSlug)) . '"></span> ';
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

    public static function renderFieldRow(int $index, array $field): void {
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
        $subFields   = $field['sub_fields'] ?? [];

        $typeInfo  = Router::getFieldTypesFlat()[$type] ?? ['label' => ucfirst($type), 'icon' => 'dashicons-admin-generic'];
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
        $categories = Router::getFieldTypeCategories();
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

        // Group sub-fields
        echo '<div class="cmb-type-opt cmb-sub-fields-wrap" data-show-for="group">';
        echo '<div class="cmb-sub-fields-header">';
        echo '<label>Sub-Fields</label>';
        echo '<small>Define the fields that appear inside each group row.</small>';
        echo '</div>';
        echo '<div class="cmb-sub-fields-list" data-parent-index="' . $index . '">';
        if (!empty($subFields)) {
            foreach ($subFields as $si => $sf) {
                self::renderSubFieldRow($index, $si, $sf);
            }
        }
        echo '</div>';
        echo '<button type="button" class="button cmb-add-sub-field" data-parent-index="' . $index . '">';
        echo '<span class="dashicons dashicons-plus-alt2"></span> Add Sub-Field';
        echo '</button>';
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

    public static function renderSubFieldRow(int $parentIndex, int $subIndex, array $sf): void {
        $prefix  = 'cmb_fields[' . $parentIndex . '][sub_fields][' . $subIndex . ']';
        $sfType  = $sf['type'] ?? 'text';
        $sfLabel = $sf['label'] ?? '';
        $sfId    = $sf['id'] ?? '';
        $sfDesc  = $sf['description'] ?? '';
        $sfReq   = !empty($sf['required']);
        $sfPh    = $sf['placeholder'] ?? '';
        $sfDef   = $sf['default_value'] ?? ($sf['default'] ?? '');
        $sfOpts  = $sf['options'] ?? '';

        $typeInfo = Router::getFieldTypesFlat()[$sfType] ?? ['label' => ucfirst($sfType), 'icon' => 'dashicons-admin-generic'];

        if (is_array($sfOpts)) {
            $lines = [];
            foreach ($sfOpts as $k => $v) {
                $lines[] = $k . '|' . $v;
            }
            $sfOpts = implode("\n", $lines);
        }

        echo '<div class="cmb-sub-field-row" data-sub-index="' . $subIndex . '" data-type="' . esc_attr($sfType) . '">';
        echo '<div class="cmb-sub-field-header">';
        echo '<span class="cmb-sub-field-drag dashicons dashicons-menu"></span>';
        echo '<span class="dashicons ' . esc_attr($typeInfo['icon']) . ' cmb-sub-field-icon"></span>';
        echo '<span class="cmb-sub-field-label">' . ($sfLabel ? esc_html($sfLabel) : '<em>New Sub-Field</em>') . '</span>';
        echo '<span class="cmb-sub-field-type-badge">' . esc_html($typeInfo['label']) . '</span>';
        if ($sfId) {
            echo '<code class="cmb-sub-field-id-badge">' . esc_html($sfId) . '</code>';
        }
        echo '<span class="cmb-sub-field-actions">';
        echo '<button type="button" class="cmb-sub-field-remove" title="Remove"><span class="dashicons dashicons-no-alt"></span></button>';
        echo '</span>';
        echo '</div>';

        echo '<div class="cmb-sub-field-body">';
        echo '<div class="cmb-field-settings-grid">';

        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>Label</label>';
        echo '<input type="text" name="' . $prefix . '[label]" value="' . esc_attr($sfLabel) . '" class="widefat cmb-sub-field-label-input" placeholder="Field Label">';
        echo '</div>';

        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>ID <small>(auto)</small></label>';
        echo '<input type="text" name="' . $prefix . '[id]" value="' . esc_attr($sfId) . '" class="widefat cmb-sub-field-id-input" placeholder="auto_generated" required>';
        echo '</div>';

        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>Type</label>';
        echo '<select name="' . $prefix . '[type]" class="widefat cmb-sub-field-type-select">';
        foreach (Router::getFieldTypeCategories() as $cat) {
            // Skip group from sub-field types (no nested groups)
            echo '<optgroup label="' . esc_attr($cat['label']) . '">';
            foreach ($cat['types'] as $tKey => $tInfo) {
                if ($tKey === 'group') continue;
                $sel = ($sfType === $tKey) ? ' selected' : '';
                echo '<option value="' . esc_attr($tKey) . '"' . $sel . '>' . esc_html($tInfo['label']) . '</option>';
            }
            echo '</optgroup>';
        }
        echo '</select>';
        echo '</div>';

        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>Description</label>';
        echo '<input type="text" name="' . $prefix . '[description]" value="' . esc_attr($sfDesc) . '" class="widefat">';
        echo '</div>';

        echo '</div>'; // .cmb-field-settings-grid

        // Placeholder & Default (for text-like types)
        echo '<div class="cmb-field-settings-grid cmb-sub-type-opt" data-show-for="text,textarea,number,email,url,password">';
        echo '<div class="cmb-fs-row cmb-fs-half"><label>Placeholder</label>';
        echo '<input type="text" name="' . $prefix . '[placeholder]" value="' . esc_attr($sfPh) . '" class="widefat"></div>';
        echo '<div class="cmb-fs-row cmb-fs-half"><label>Default Value</label>';
        echo '<input type="text" name="' . $prefix . '[default_value]" value="' . esc_attr($sfDef) . '" class="widefat"></div>';
        echo '</div>';

        // Options for select/radio
        echo '<div class="cmb-sub-type-opt" data-show-for="select,radio">';
        echo '<div class="cmb-fs-row"><label>Options <small>One per line: <code>value|Label</code></small></label>';
        echo '<textarea name="' . $prefix . '[options]" class="widefat cmb-options-textarea" rows="3">' . esc_textarea($sfOpts) . '</textarea>';
        echo '</div></div>';

        // Required checkbox
        echo '<div class="cmb-sub-field-bottom">';
        echo '<label class="cmb-checkbox-label">';
        echo '<input type="checkbox" name="' . $prefix . '[required]" value="1"' . ($sfReq ? ' checked' : '') . '> Required';
        echo '</label>';
        echo '</div>';

        echo '</div>'; // .cmb-sub-field-body
        echo '</div>'; // .cmb-sub-field-row
    }

    public static function renderFieldTypePicker(): void {
        echo '<div class="cmb-type-picker-overlay" id="cmb-type-picker" style="display:none">';
        echo '<div class="cmb-type-picker-modal">';
        echo '<div class="cmb-type-picker-header">';
        echo '<h2>Add Field</h2>';
        echo '<input type="text" class="cmb-type-picker-search" id="cmb-type-search" placeholder="Search field types..." autocomplete="off">';
        echo '<button type="button" class="cmb-type-picker-close" id="cmb-type-picker-close">&times;</button>';
        echo '</div>';
        echo '<div class="cmb-type-picker-body">';

        foreach (Router::getFieldTypeCategories() as $cat) {
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
}
