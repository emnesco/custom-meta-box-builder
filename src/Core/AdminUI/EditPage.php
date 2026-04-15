<?php
declare(strict_types=1);

/**
 * Admin edit page — renders the meta box configuration editor.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\AdminUI;

defined( 'ABSPATH' ) || exit;

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
        echo '<a href="' . esc_url(admin_url('admin.php?page=cmb-builder')) . '" class="cmb-back-link"><span class="dashicons dashicons-arrow-left-alt2"></span> ' . esc_html__('Field Groups', 'custom-meta-box-builder') . '</a>';
        echo '<h1>' . ($editBox ? esc_html__('Edit Field Group', 'custom-meta-box-builder') : esc_html__('New Field Group', 'custom-meta-box-builder')) . '</h1>';
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
        echo '<input type="text" name="cmb_box_title" id="cmb-title-input" class="cmb-title-input" placeholder="' . esc_attr__('Field Group Title', 'custom-meta-box-builder') . '" value="' . esc_attr($title) . '" required autocomplete="off">';
        echo '</div>';

        // Tabs
        echo '<div class="cmb-editor-tabs">';
        echo '<button type="button" class="cmb-editor-tab active" data-tab="fields"><span class="dashicons dashicons-editor-table"></span> ' . esc_html__('Fields', 'custom-meta-box-builder') . '</button>';
        echo '<button type="button" class="cmb-editor-tab" data-tab="code"><span class="dashicons dashicons-editor-code"></span> ' . esc_html__('PHP Code', 'custom-meta-box-builder') . '</button>';
        echo '</div>';

        // Fields panel
        echo '<div class="cmb-editor-panel active" id="cmb-panel-fields">';
        echo '<div id="cmb-fields-list" class="cmb-fields-sortable">';

        if (!empty($fields)) {
            foreach ($fields as $i => $field) {
                self::renderFieldRow($i, $field);
            }
        } else {
            echo '<div class="cmb-no-fields-msg"><p>' . esc_html__('No fields yet. Click the button below to add your first field.', 'custom-meta-box-builder') . '</p></div>';
        }

        echo '</div>'; // #cmb-fields-list

        echo '<div class="cmb-add-field-area">';
        echo '<button type="button" class="button button-secondary cmb-add-field-btn" id="cmb-add-field-trigger">';
        echo '<span class="dashicons dashicons-plus-alt2"></span> ' . esc_html__('Add Field', 'custom-meta-box-builder');
        echo '</button>';
        echo '</div>';

        echo '</div>'; // #cmb-panel-fields

        // Code panel
        echo '<div class="cmb-editor-panel" id="cmb-panel-code">';
        echo '<div class="cmb-code-panel-header">';
        echo '<p>' . esc_html__('Use this PHP code to register this field group programmatically. Add it to your theme\'s functions.php or a custom plugin.', 'custom-meta-box-builder') . '</p>';
        echo '<button type="button" class="button" id="cmb-copy-code"><span class="dashicons dashicons-clipboard"></span> ' . esc_html__('Copy Code', 'custom-meta-box-builder') . '</button>';
        echo '</div>';
        echo '<pre class="cmb-code-preview" id="cmb-code-output"><code>// ' . esc_html__('Save the field group first to generate code.', 'custom-meta-box-builder') . '</code></pre>';
        echo '</div>'; // #cmb-panel-code

        echo '</div>'; // #post-body-content

        /* ── Sidebar ── */
        echo '<div id="postbox-container-1" class="postbox-container">';

        // Publish box
        echo '<div class="postbox cmb-publish-box">';
        echo '<div class="postbox-header"><h2>' . esc_html__('Publish', 'custom-meta-box-builder') . '</h2></div>';
        echo '<div class="inside">';
        echo '<div class="cmb-status-control">';
        echo '<label class="cmb-toggle-label">';
        echo '<input type="hidden" name="cmb_active" value="0">';
        echo '<input type="checkbox" name="cmb_active" value="1"' . ($isActive ? ' checked' : '') . '>';
        echo '<span class="cmb-toggle-switch"></span>';
        echo '<span class="cmb-toggle-text">' . ($isActive ? esc_html__('Active', 'custom-meta-box-builder') : esc_html__('Inactive', 'custom-meta-box-builder')) . '</span>';
        echo '</label>';
        echo '</div>';
        echo '<div class="cmb-publish-actions">';
        echo '<input type="submit" name="cmb_builder_submit" class="button button-primary button-large" value="' . ($editBox ? esc_attr__('Update', 'custom-meta-box-builder') : esc_attr__('Publish', 'custom-meta-box-builder')) . '">';
        if ($editBox) {
            echo ' <a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=cmb-builder&cmb_delete=' . urlencode($id)), 'cmb_delete_' . $id)) . '" class="submitdelete" onclick="return confirm(\'' . esc_js(__('Delete this field group?', 'custom-meta-box-builder')) . '\')">' . esc_html__('Delete', 'custom-meta-box-builder') . '</a>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';

        // Settings box
        echo '<div class="postbox">';
        echo '<div class="postbox-header"><h2>' . esc_html__('Settings', 'custom-meta-box-builder') . '</h2></div>';
        echo '<div class="inside">';

        // Meta Box ID
        echo '<div class="cmb-setting-row">';
        echo '<label for="cmb-box-id">' . esc_html__('Meta Box ID', 'custom-meta-box-builder') . '</label>';
        if ($action === 'edit') {
            echo '<input type="text" name="cmb_box_id" id="cmb-box-id" class="widefat" value="' . esc_attr($boxId) . '" readonly>';
        } else {
            echo '<div class="cmb-prefixed-input">';
            echo '<span class="cmb-input-prefix">cmb-</span>';
            echo '<input type="text" name="cmb_box_id" id="cmb-box-id" value="" placeholder="e.g. person_details">';
            echo '</div>';
            echo '<p class="description">' . esc_html__('Auto-generated from title if left empty. The cmb- prefix is added automatically.', 'custom-meta-box-builder') . '</p>';
        }
        echo '</div>';

        // Show in REST
        echo '<div class="cmb-setting-row">';
        echo '<label class="cmb-checkbox-label">';
        echo '<input type="hidden" name="cmb_show_in_rest" value="0">';
        echo '<input type="checkbox" name="cmb_show_in_rest" value="1"' . ($showInRest ? ' checked' : '') . '>';
        echo ' ' . esc_html__('Expose in REST API', 'custom-meta-box-builder');
        echo '</label>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        // Location box
        echo '<div class="postbox">';
        echo '<div class="postbox-header"><h2>' . esc_html__('Location', 'custom-meta-box-builder') . '</h2></div>';
        echo '<div class="inside">';
        echo '<p class="cmb-setting-description">' . esc_html__('Show this field group on:', 'custom-meta-box-builder') . '</p>';
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
        echo '<div class="postbox-header"><h2>' . esc_html__('Position', 'custom-meta-box-builder') . '</h2></div>';
        echo '<div class="inside">';

        echo '<div class="cmb-setting-row">';
        echo '<label for="cmb-context">' . esc_html__('Context', 'custom-meta-box-builder') . '</label>';
        echo '<select name="cmb_box_context" id="cmb-context" class="widefat">';
        foreach (['normal' => __('Normal', 'custom-meta-box-builder'), 'advanced' => __('Advanced', 'custom-meta-box-builder'), 'side' => __('Side', 'custom-meta-box-builder')] as $val => $label) {
            $sel = ($context === $val) ? ' selected' : '';
            echo '<option value="' . $val . '"' . $sel . '>' . $label . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '<div class="cmb-setting-row">';
        echo '<label for="cmb-priority">' . esc_html__('Priority', 'custom-meta-box-builder') . '</label>';
        echo '<select name="cmb_box_priority" id="cmb-priority" class="widefat">';
        foreach (['high' => __('High', 'custom-meta-box-builder'), 'default' => __('Default', 'custom-meta-box-builder'), 'low' => __('Low', 'custom-meta-box-builder')] as $val => $label) {
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
        $disabled    = !empty($field['disabled']);
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
        $layout      = $field['layout'] ?? '';
        $repeatable  = !empty($field['repeatable']);
        $collapsed   = $field['collapsed'] ?? true;
        $minRows     = $field['min_rows'] ?? '';
        $maxRows     = $field['max_rows'] ?? '';
        $rowTitleField = $field['row_title_field'] ?? '';
        $searchable  = !empty($field['searchable']);
        $condField   = $field['conditional_field'] ?? '';
        $condOp      = $field['conditional_operator'] ?? '==';
        $condVal     = $field['conditional_value'] ?? '';
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
        echo '<span class="cmb-field-row-label">' . ($label ? esc_html($label) : '<em>' . esc_html__('New Field', 'custom-meta-box-builder') . '</em>') . '</span>';
        echo '<span class="cmb-field-row-meta">';
        echo '<span class="cmb-field-row-type">' . esc_html($typeLabel) . '</span>';
        if ($fieldId) {
            echo '<code class="cmb-field-row-id">' . esc_html($fieldId) . '</code>';
        }
        if ($disabled) {
            echo '<span class="cmb-disabled-badge">' . esc_html__('Disabled', 'custom-meta-box-builder') . '</span>';
        }
        if ($required) {
            echo '<span class="cmb-required-badge">' . esc_html__('Required', 'custom-meta-box-builder') . '</span>';
        }
        echo '</span>';
        echo '<span class="cmb-field-row-actions">';
        echo '<button type="button" class="cmb-field-duplicate" title="' . esc_attr__('Duplicate', 'custom-meta-box-builder') . '"><span class="dashicons dashicons-admin-page"></span></button>';
        echo '<button type="button" class="cmb-field-remove" title="' . esc_attr__('Delete', 'custom-meta-box-builder') . '"><span class="dashicons dashicons-trash"></span></button>';
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
        echo '<label>' . esc_html__('Field Label', 'custom-meta-box-builder') . '</label>';
        echo '<input type="text" name="' . $prefix . '[label]" value="' . esc_attr($label) . '" class="widefat cmb-field-label-input" placeholder="' . esc_attr__('e.g. Author Name', 'custom-meta-box-builder') . '">';
        echo '</div>';

        // ID
        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>' . esc_html__('Field ID', 'custom-meta-box-builder') . ' <small class="cmb-auto-id">(' . esc_html__('auto', 'custom-meta-box-builder') . ')</small></label>';
        echo '<input type="text" name="' . $prefix . '[id]" value="' . esc_attr($fieldId) . '" class="widefat cmb-field-id-input" placeholder="auto_generated" required>';
        echo '</div>';

        // Type
        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>' . esc_html__('Field Type', 'custom-meta-box-builder') . '</label>';
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
        echo '<label>' . esc_html__('Description', 'custom-meta-box-builder') . '</label>';
        echo '<input type="text" name="' . $prefix . '[description]" value="' . esc_attr($description) . '" class="widefat" placeholder="' . esc_attr__('Help text shown below the field', 'custom-meta-box-builder') . '">';
        echo '</div>';

        echo '</div>'; // .cmb-field-settings-grid

        // Type-specific options
        echo '<div class="cmb-type-options">';

        // Placeholder (text, textarea, number, email, url, password)
        echo '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="text,textarea,number,email,url,password">';
        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>' . esc_html__('Placeholder', 'custom-meta-box-builder') . '</label>';
        echo '<input type="text" name="' . $prefix . '[placeholder]" value="' . esc_attr($placeholder) . '" class="widefat">';
        echo '</div>';
        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>' . esc_html__('Default Value', 'custom-meta-box-builder') . '</label>';
        echo '<input type="text" name="' . $prefix . '[default_value]" value="' . esc_attr($default) . '" class="widefat">';
        echo '</div>';
        echo '</div>';

        // Default for date, color, hidden
        echo '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="date,color,hidden">';
        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>' . esc_html__('Default Value', 'custom-meta-box-builder') . '</label>';
        echo '<input type="text" name="' . $prefix . '[default_value_dc]" value="' . esc_attr($default) . '" class="widefat">';
        echo '</div>';
        echo '</div>';

        // Textarea/WYSIWYG rows
        echo '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="textarea,wysiwyg">';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>' . esc_html__('Rows', 'custom-meta-box-builder') . '</label>';
        echo '<input type="number" name="' . $prefix . '[rows]" value="' . esc_attr($rows) . '" class="widefat" placeholder="5" min="1" max="50">';
        echo '</div>';
        echo '</div>';

        // Number min/max/step
        echo '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="number">';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>' . esc_html__('Min', 'custom-meta-box-builder') . '</label>';
        echo '<input type="number" name="' . $prefix . '[min]" value="' . esc_attr($min) . '" class="widefat">';
        echo '</div>';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>' . esc_html__('Max', 'custom-meta-box-builder') . '</label>';
        echo '<input type="number" name="' . $prefix . '[max]" value="' . esc_attr($max) . '" class="widefat">';
        echo '</div>';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>' . esc_html__('Step', 'custom-meta-box-builder') . '</label>';
        echo '<input type="number" name="' . $prefix . '[step]" value="' . esc_attr($step) . '" class="widefat" placeholder="1">';
        echo '</div>';
        echo '</div>';

        // Select/Radio options
        echo '<div class="cmb-type-opt" data-show-for="select,radio">';
        echo '<div class="cmb-fs-row">';
        echo '<label>' . esc_html__('Options', 'custom-meta-box-builder') . ' <small>' . esc_html__('One per line:', 'custom-meta-box-builder') . ' <code>value|Label</code></small></label>';
        echo '<textarea name="' . $prefix . '[options]" class="widefat cmb-options-textarea" rows="4" placeholder="option1|Option One&#10;option2|Option Two">' . esc_textarea($options) . '</textarea>';
        echo '</div>';
        echo '</div>';

        // Post type for post field
        echo '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="post">';
        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>' . esc_html__('Post Type', 'custom-meta-box-builder') . '</label>';
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
        echo '<label>' . esc_html__('Taxonomy', 'custom-meta-box-builder') . '</label>';
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
        echo '<label>' . esc_html__('User Role', 'custom-meta-box-builder') . ' <small>(' . esc_html__('empty = all', 'custom-meta-box-builder') . ')</small></label>';
        echo '<select name="' . $prefix . '[role]" class="widefat">';
        echo '<option value="">' . esc_html__('All Roles', 'custom-meta-box-builder') . '</option>';
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
        echo '<label>' . esc_html__('Min Rows', 'custom-meta-box-builder') . '</label>';
        echo '<input type="number" name="' . $prefix . '[min_rows]" value="' . esc_attr($minRows) . '" class="widefat" min="0">';
        echo '</div>';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>' . esc_html__('Max Rows', 'custom-meta-box-builder') . '</label>';
        echo '<input type="number" name="' . $prefix . '[max_rows]" value="' . esc_attr($maxRows) . '" class="widefat" min="0">';
        echo '</div>';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>' . esc_html__('Row Title Field', 'custom-meta-box-builder') . '</label>';
        echo '<input type="text" name="' . $prefix . '[row_title_field]" value="' . esc_attr($rowTitleField) . '" class="widefat" placeholder="' . esc_attr__('e.g. title', 'custom-meta-box-builder') . '">';
        echo '</div>';
        echo '</div>';

        echo '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="group">';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label class="cmb-checkbox-label" style="margin-top:4px">';
        echo '<input type="checkbox" name="' . $prefix . '[collapsed]" value="1"' . ($collapsed ? ' checked' : '') . '>';
        echo ' ' . esc_html__('Collapsed by default', 'custom-meta-box-builder');
        echo '</label>';
        echo '</div>';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label class="cmb-checkbox-label" style="margin-top:4px">';
        echo '<input type="checkbox" name="' . $prefix . '[searchable]" value="1"' . ($searchable ? ' checked' : '') . '>';
        echo ' ' . esc_html__('Enable search/filter', 'custom-meta-box-builder');
        echo '</label>';
        echo '</div>';
        echo '</div>';

        // Group sub-fields
        echo '<div class="cmb-type-opt cmb-sub-fields-wrap" data-show-for="group">';
        echo '<div class="cmb-sub-fields-header">';
        echo '<label>' . esc_html__('Sub-Fields', 'custom-meta-box-builder') . '</label>';
        echo '<small>' . esc_html__('Define the fields that appear inside each group row.', 'custom-meta-box-builder') . '</small>';
        echo '</div>';
        echo '<div class="cmb-sub-fields-list" data-parent-index="' . $index . '">';
        if (!empty($subFields)) {
            foreach ($subFields as $si => $sf) {
                self::renderSubFieldRow($index, $si, $sf);
            }
        }
        echo '</div>';
        echo '<button type="button" class="button cmb-add-sub-field" data-parent-index="' . $index . '">';
        echo '<span class="dashicons dashicons-plus-alt2"></span> ' . esc_html__('Add Sub-Field', 'custom-meta-box-builder');
        echo '</button>';
        echo '</div>';

        echo '</div>'; // .cmb-type-options

        // Conditional Logic
        echo '<div class="cmb-conditional-row">';
        echo '<div class="cmb-conditional-header">';
        echo '<label class="cmb-checkbox-label">';
        echo '<input type="checkbox" class="cmb-conditional-toggle" ' . ($condField ? 'checked' : '') . '>';
        echo ' ' . esc_html__('Conditional Logic', 'custom-meta-box-builder');
        echo '</label>';
        echo '</div>';
        echo '<div class="cmb-conditional-settings" ' . ($condField ? '' : 'style="display:none"') . '>';
        echo '<div class="cmb-field-settings-grid">';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>' . esc_html__('Show when field', 'custom-meta-box-builder') . '</label>';
        echo '<input type="text" name="' . $prefix . '[conditional_field]" value="' . esc_attr($condField) . '" class="widefat" placeholder="' . esc_attr__('field_id', 'custom-meta-box-builder') . '">';
        echo '</div>';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>' . esc_html__('Operator', 'custom-meta-box-builder') . '</label>';
        echo '<select name="' . $prefix . '[conditional_operator]" class="widefat">';
        foreach (['==' => __('equals', 'custom-meta-box-builder'), '!=' => __('not equals', 'custom-meta-box-builder'), '!empty' => __('is not empty', 'custom-meta-box-builder'), 'empty' => __('is empty', 'custom-meta-box-builder'), 'contains' => __('contains', 'custom-meta-box-builder')] as $opVal => $opLabel) {
            $sel = ($condOp === $opVal) ? ' selected' : '';
            echo '<option value="' . esc_attr($opVal) . '"' . $sel . '>' . esc_html($opLabel) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>' . esc_html__('Value', 'custom-meta-box-builder') . '</label>';
        echo '<input type="text" name="' . $prefix . '[conditional_value]" value="' . esc_attr($condVal) . '" class="widefat" placeholder="' . esc_attr__('value', 'custom-meta-box-builder') . '">';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        // Bottom row: required, width, layout, repeatable
        echo '<div class="cmb-field-bottom-row">';

        echo '<label class="cmb-checkbox-label cmb-disabled-toggle">';
        echo '<input type="checkbox" name="' . $prefix . '[disabled]" value="1"' . ($disabled ? ' checked' : '') . ' class="cmb-field-disabled-input">';
        echo ' ' . esc_html__('Disabled', 'custom-meta-box-builder');
        echo '</label>';

        echo '<label class="cmb-checkbox-label">';
        echo '<input type="checkbox" name="' . $prefix . '[required]" value="1"' . ($required ? ' checked' : '') . '>';
        echo ' ' . esc_html__('Required', 'custom-meta-box-builder');
        echo '</label>';

        echo '<label class="cmb-checkbox-label cmb-type-opt-inline" data-hide-for="group,checkbox,hidden">';
        echo '<input type="checkbox" name="' . $prefix . '[repeatable]" value="1"' . ($repeatable ? ' checked' : '') . '>';
        echo ' ' . esc_html__('Repeatable', 'custom-meta-box-builder');
        echo '</label>';

        echo '<div class="cmb-width-control">';
        echo '<label>' . esc_html__('Width', 'custom-meta-box-builder') . '</label>';
        echo '<select name="' . $prefix . '[width]">';
        foreach (['100' => '100%', '75' => '75%', '50' => '50%', '33' => '33%', '25' => '25%'] as $wVal => $wLabel) {
            $sel = ($width == $wVal) ? ' selected' : '';
            echo '<option value="' . $wVal . '"' . $sel . '>' . $wLabel . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '<div class="cmb-layout-control">';
        echo '<label>' . esc_html__('Layout', 'custom-meta-box-builder') . '</label>';
        echo '<select name="' . $prefix . '[layout]">';
        foreach (['' => __('Horizontal', 'custom-meta-box-builder'), 'inline' => __('Stacked (label on top)', 'custom-meta-box-builder')] as $lVal => $lLabel) {
            $sel = ($layout === $lVal) ? ' selected' : '';
            echo '<option value="' . esc_attr($lVal) . '"' . $sel . '>' . esc_html($lLabel) . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '</div>'; // .cmb-field-bottom-row

        echo '</div>'; // .cmb-field-row-body
        echo '</div>'; // .cmb-field-row
    }

    public static function renderSubFieldRow(int $parentIndex, int $subIndex, array $sf, string $parentPrefix = ''): void {
        $prefix  = $parentPrefix ?: 'cmb_fields[' . $parentIndex . '][sub_fields][' . $subIndex . ']';
        if ($parentPrefix) {
            $prefix = $parentPrefix . '[sub_fields][' . $subIndex . ']';
        }
        $sfType  = $sf['type'] ?? 'text';
        $sfLabel = $sf['label'] ?? '';
        $sfId    = $sf['id'] ?? '';
        $sfDesc  = $sf['description'] ?? '';
        $sfReq   = !empty($sf['required']);
        $sfPh    = $sf['placeholder'] ?? '';
        $sfDef   = $sf['default_value'] ?? ($sf['default'] ?? '');
        $sfOpts  = $sf['options'] ?? '';
        $sfWidth = $sf['width'] ?? '';
        $sfLayout = $sf['layout'] ?? '';
        $sfCondField = $sf['conditional_field'] ?? '';
        $sfCondOp    = $sf['conditional_operator'] ?? '==';
        $sfCondVal   = $sf['conditional_value'] ?? '';

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
        echo '<span class="cmb-sub-field-label">' . ($sfLabel ? esc_html($sfLabel) : '<em>' . esc_html__('New Sub-Field', 'custom-meta-box-builder') . '</em>') . '</span>';
        echo '<span class="cmb-sub-field-type-badge">' . esc_html($typeInfo['label']) . '</span>';
        if ($sfId) {
            echo '<code class="cmb-sub-field-id-badge">' . esc_html($sfId) . '</code>';
        }
        echo '<span class="cmb-sub-field-actions">';
        echo '<button type="button" class="cmb-sub-field-remove" title="' . esc_attr__('Remove', 'custom-meta-box-builder') . '"><span class="dashicons dashicons-no-alt"></span></button>';
        echo '</span>';
        echo '</div>';

        echo '<div class="cmb-sub-field-body">';
        echo '<div class="cmb-field-settings-grid">';

        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>' . esc_html__('Label', 'custom-meta-box-builder') . '</label>';
        echo '<input type="text" name="' . $prefix . '[label]" value="' . esc_attr($sfLabel) . '" class="widefat cmb-sub-field-label-input" placeholder="' . esc_attr__('Field Label', 'custom-meta-box-builder') . '">';
        echo '</div>';

        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>' . esc_html__('ID', 'custom-meta-box-builder') . ' <small>(' . esc_html__('auto', 'custom-meta-box-builder') . ')</small></label>';
        echo '<input type="text" name="' . $prefix . '[id]" value="' . esc_attr($sfId) . '" class="widefat cmb-sub-field-id-input" placeholder="auto_generated" required>';
        echo '</div>';

        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>' . esc_html__('Type', 'custom-meta-box-builder') . '</label>';
        echo '<select name="' . $prefix . '[type]" class="widefat cmb-sub-field-type-select">';
        foreach (Router::getFieldTypeCategories() as $cat) {
            echo '<optgroup label="' . esc_attr($cat['label']) . '">';
            foreach ($cat['types'] as $tKey => $tInfo) {
                $sel = ($sfType === $tKey) ? ' selected' : '';
                echo '<option value="' . esc_attr($tKey) . '"' . $sel . '>' . esc_html($tInfo['label']) . '</option>';
            }
            echo '</optgroup>';
        }
        echo '</select>';
        echo '</div>';

        echo '<div class="cmb-fs-row cmb-fs-half">';
        echo '<label>' . esc_html__('Description', 'custom-meta-box-builder') . '</label>';
        echo '<input type="text" name="' . $prefix . '[description]" value="' . esc_attr($sfDesc) . '" class="widefat">';
        echo '</div>';

        echo '</div>'; // .cmb-field-settings-grid

        // Placeholder & Default (for text-like types)
        echo '<div class="cmb-field-settings-grid cmb-sub-type-opt" data-show-for="text,textarea,number,email,url,password">';
        echo '<div class="cmb-fs-row cmb-fs-half"><label>' . esc_html__('Placeholder', 'custom-meta-box-builder') . '</label>';
        echo '<input type="text" name="' . $prefix . '[placeholder]" value="' . esc_attr($sfPh) . '" class="widefat"></div>';
        echo '<div class="cmb-fs-row cmb-fs-half"><label>' . esc_html__('Default Value', 'custom-meta-box-builder') . '</label>';
        echo '<input type="text" name="' . $prefix . '[default_value]" value="' . esc_attr($sfDef) . '" class="widefat"></div>';
        echo '</div>';

        // Options for select/radio
        echo '<div class="cmb-sub-type-opt" data-show-for="select,radio">';
        echo '<div class="cmb-fs-row"><label>' . esc_html__('Options', 'custom-meta-box-builder') . ' <small>' . esc_html__('One per line:', 'custom-meta-box-builder') . ' <code>value|Label</code></small></label>';
        echo '<textarea name="' . $prefix . '[options]" class="widefat cmb-options-textarea" rows="3">' . esc_textarea($sfOpts) . '</textarea>';
        echo '</div></div>';

        // Conditional Logic for sub-fields
        echo '<div class="cmb-conditional-row cmb-sub-conditional">';
        echo '<div class="cmb-conditional-header">';
        echo '<label class="cmb-checkbox-label">';
        echo '<input type="checkbox" class="cmb-conditional-toggle" ' . ($sfCondField ? 'checked' : '') . '>';
        echo ' ' . esc_html__('Conditional Logic', 'custom-meta-box-builder');
        echo '</label>';
        echo '</div>';
        echo '<div class="cmb-conditional-settings" ' . ($sfCondField ? '' : 'style="display:none"') . '>';
        echo '<div class="cmb-field-settings-grid">';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>' . esc_html__('Show when field', 'custom-meta-box-builder') . '</label>';
        echo '<input type="text" name="' . $prefix . '[conditional_field]" value="' . esc_attr($sfCondField) . '" class="widefat" placeholder="' . esc_attr__('field_id', 'custom-meta-box-builder') . '">';
        echo '</div>';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>' . esc_html__('Operator', 'custom-meta-box-builder') . '</label>';
        echo '<select name="' . $prefix . '[conditional_operator]" class="widefat">';
        foreach (['==' => __('equals', 'custom-meta-box-builder'), '!=' => __('not equals', 'custom-meta-box-builder'), '!empty' => __('is not empty', 'custom-meta-box-builder'), 'empty' => __('is empty', 'custom-meta-box-builder'), 'contains' => __('contains', 'custom-meta-box-builder')] as $opVal => $opLabel) {
            $sel = ($sfCondOp === $opVal) ? ' selected' : '';
            echo '<option value="' . esc_attr($opVal) . '"' . $sel . '>' . esc_html($opLabel) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '<div class="cmb-fs-row cmb-fs-third">';
        echo '<label>' . esc_html__('Value', 'custom-meta-box-builder') . '</label>';
        echo '<input type="text" name="' . $prefix . '[conditional_value]" value="' . esc_attr($sfCondVal) . '" class="widefat" placeholder="' . esc_attr__('value', 'custom-meta-box-builder') . '">';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        // Bottom row: required, width, layout
        echo '<div class="cmb-sub-field-bottom">';
        echo '<label class="cmb-checkbox-label">';
        echo '<input type="checkbox" name="' . $prefix . '[required]" value="1"' . ($sfReq ? ' checked' : '') . '> ' . esc_html__('Required', 'custom-meta-box-builder');
        echo '</label>';

        echo '<div class="cmb-width-control">';
        echo '<label>' . esc_html__('Width', 'custom-meta-box-builder') . '</label>';
        echo '<select name="' . $prefix . '[width]">';
        foreach (['' => __('Auto', 'custom-meta-box-builder'), '100' => '100%', '75' => '75%', '50' => '50%', '33' => '33%', '25' => '25%'] as $wVal => $wLabel) {
            $sel = ($sfWidth === (string)$wVal) ? ' selected' : '';
            echo '<option value="' . esc_attr($wVal) . '"' . $sel . '>' . esc_html($wLabel) . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '<div class="cmb-layout-control">';
        echo '<label>' . esc_html__('Layout', 'custom-meta-box-builder') . '</label>';
        echo '<select name="' . $prefix . '[layout]">';
        foreach (['' => __('Horizontal', 'custom-meta-box-builder'), 'inline' => __('Stacked', 'custom-meta-box-builder')] as $lVal => $lLabel) {
            $sel = ($sfLayout === $lVal) ? ' selected' : '';
            echo '<option value="' . esc_attr($lVal) . '"' . $sel . '>' . esc_html($lLabel) . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '</div>';

        // Nested sub-fields for group-type sub-fields (infinite nesting)
        $nestedSubFields = $sf['sub_fields'] ?? [];
        echo '<div class="cmb-type-opt cmb-sub-fields-wrap cmb-nested-sub-fields" data-show-for="group"' . ($sfType !== 'group' ? ' style="display:none"' : '') . '>';
        echo '<div class="cmb-sub-fields-header">';
        echo '<label>' . esc_html__('Sub-Fields', 'custom-meta-box-builder') . '</label>';
        echo '</div>';
        echo '<div class="cmb-sub-fields-list" data-parent-prefix="' . esc_attr($prefix) . '">';
        if (!empty($nestedSubFields)) {
            foreach ($nestedSubFields as $ni => $nsf) {
                self::renderSubFieldRow($parentIndex, $ni, $nsf, $prefix);
            }
        }
        echo '</div>';
        echo '<button type="button" class="button cmb-add-sub-field" data-parent-prefix="' . esc_attr($prefix) . '">';
        echo '<span class="dashicons dashicons-plus-alt2"></span> ' . esc_html__('Add Sub-Field', 'custom-meta-box-builder');
        echo '</button>';
        echo '</div>';

        echo '</div>'; // .cmb-sub-field-body
        echo '</div>'; // .cmb-sub-field-row
    }

    public static function renderFieldTypePicker(): void {
        echo '<div class="cmb-type-picker-overlay" id="cmb-type-picker" style="display:none">';
        echo '<div class="cmb-type-picker-modal">';
        echo '<div class="cmb-type-picker-header">';
        echo '<h2>' . esc_html__('Add Field', 'custom-meta-box-builder') . '</h2>';
        echo '<input type="text" class="cmb-type-picker-search" id="cmb-type-search" placeholder="' . esc_attr__('Search field types...', 'custom-meta-box-builder') . '" autocomplete="off">';
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
