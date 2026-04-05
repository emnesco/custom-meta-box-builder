/**
 * CMB Builder – Admin UI Scripts
 * Handles field builder interactions, type picker, code generation, and import/export.
 */
(function ($) {
    'use strict';

    var fieldIndex = 0;
    var formDirty = false;

    $(document).ready(function () {
        initFieldIndex();
        initSortable();
        initTypeVisibility();
        bindEvents();
        updateCodePreview();
    });

    /* ─── Initialization ────────────────────────────────── */

    function initFieldIndex() {
        var maxIdx = -1;
        $('.cmb-field-row').each(function () {
            var idx = parseInt($(this).data('index'), 10);
            if (idx > maxIdx) maxIdx = idx;
        });
        fieldIndex = maxIdx + 1;
    }

    function initSortable() {
        $('#cmb-fields-list').sortable({
            handle: '.cmb-field-drag',
            placeholder: 'cmb-field-row cmb-sortable-placeholder',
            tolerance: 'pointer',
            opacity: 0.8,
            update: function () {
                reindexFields();
                markDirty();
            }
        });
    }

    function initTypeVisibility() {
        $('.cmb-field-row').each(function () {
            updateTypeOptions($(this));
        });
        $('.cmb-sub-field-row').each(function () {
            updateSubFieldTypeOptions($(this));
        });
    }

    /* ─── Event Bindings ────────────────────────────────── */

    function bindEvents() {
        // Field row toggle
        $(document).on('click', '.cmb-field-row-header', function (e) {
            if ($(e.target).closest('.cmb-field-row-actions').length) return;
            if ($(e.target).closest('.cmb-field-drag').length) return;
            var $row = $(this).closest('.cmb-field-row');
            $row.toggleClass('open');
        });

        // Field toggle button
        $(document).on('click', '.cmb-field-toggle', function (e) {
            e.stopPropagation();
            $(this).closest('.cmb-field-row').toggleClass('open');
        });

        // Delete field
        $(document).on('click', '.cmb-field-remove', function (e) {
            e.stopPropagation();
            if (!confirm('Remove this field?')) return;
            var $row = $(this).closest('.cmb-field-row');
            $row.slideUp(200, function () {
                $(this).remove();
                reindexFields();
                markDirty();
                toggleEmptyMessage();
            });
        });

        // Duplicate field
        $(document).on('click', '.cmb-field-duplicate', function (e) {
            e.stopPropagation();
            var $row = $(this).closest('.cmb-field-row');
            var $clone = $row.clone(false, false);
            var newIdx = fieldIndex++;

            // Update index
            $clone.attr('data-index', newIdx);
            $clone.removeClass('open');

            // Update input names
            $clone.find('[name]').each(function () {
                var name = $(this).attr('name');
                name = name.replace(/cmb_fields\[\d+\]/, 'cmb_fields[' + newIdx + ']');
                $(this).attr('name', name);
            });

            // Update ID to avoid duplicate
            var $idInput = $clone.find('.cmb-field-id-input');
            if ($idInput.val()) {
                $idInput.val($idInput.val() + '_copy');
            }

            // Update header label
            var label = $clone.find('.cmb-field-label-input').val();
            $clone.find('.cmb-field-row-label').text(label ? label + ' (Copy)' : 'New Field');

            $clone.hide().insertAfter($row).slideDown(200);
            markDirty();
        });

        // Label → auto-generate ID
        $(document).on('input', '.cmb-field-label-input', function () {
            var $row = $(this).closest('.cmb-field-row');
            var $idInput = $row.find('.cmb-field-id-input');
            var label = $(this).val();

            // Update header label
            $row.find('.cmb-field-row-label').text(label || 'New Field');

            // Auto-generate ID (only if the ID looks auto-generated or is empty)
            var currentId = $idInput.val();
            var autoId = slugify(label);
            if (!currentId || currentId === slugify($row.data('prev-label') || '')) {
                $idInput.val(autoId);
                $row.find('.cmb-field-row-id').text(autoId);
            }
            $row.data('prev-label', label);
            markDirty();
        });

        // ID input change → update header
        $(document).on('input', '.cmb-field-id-input', function () {
            var $row = $(this).closest('.cmb-field-row');
            $row.find('.cmb-field-row-id').text($(this).val());
            markDirty();
        });

        // Type change
        $(document).on('change', '.cmb-field-type-select', function () {
            var $row = $(this).closest('.cmb-field-row');
            var type = $(this).val();
            var typeInfo = cmbAdmin.fieldTypes[type] || {};

            $row.attr('data-type', type);
            $row.find('.cmb-field-row-type').text(typeInfo.label || type);
            $row.find('.cmb-field-icon').attr('class', 'cmb-field-icon dashicons ' + (typeInfo.icon || 'dashicons-admin-generic'));

            updateTypeOptions($row);
            markDirty();
        });

        // Required checkbox → update badge
        $(document).on('change', 'input[name$="[required]"]', function () {
            var $row = $(this).closest('.cmb-field-row');
            var $badge = $row.find('.cmb-required-badge');
            if ($(this).is(':checked')) {
                if (!$badge.length) {
                    $row.find('.cmb-field-row-meta').append('<span class="cmb-required-badge">Required</span>');
                }
            } else {
                $badge.remove();
            }
        });

        // Toggle switch text update
        $(document).on('change', '.cmb-toggle-label input', function () {
            var $text = $(this).closest('.cmb-toggle-label').find('.cmb-toggle-text');
            $text.text($(this).is(':checked') ? 'Active' : 'Inactive');
        });

        // Tab switching
        $(document).on('click', '.cmb-editor-tab', function () {
            var tab = $(this).data('tab');
            $('.cmb-editor-tab').removeClass('active');
            $(this).addClass('active');
            $('.cmb-editor-panel').removeClass('active');
            $('#cmb-panel-' + tab).addClass('active');

            if (tab === 'code') {
                updateCodePreview();
            }
        });

        // Add field → open type picker
        $(document).on('click', '#cmb-add-field-trigger', function () {
            $('#cmb-type-picker').show();
            $('#cmb-type-search').val('').focus();
            $('.cmb-type-picker-item').removeClass('hidden');
        });

        // Type picker: select type
        $(document).on('click', '.cmb-type-picker-item', function () {
            var type = $(this).data('type');
            addField(type);
            $('#cmb-type-picker').hide();
        });

        // Type picker: close
        $(document).on('click', '#cmb-type-picker-close', function () {
            $('#cmb-type-picker').hide();
        });
        $(document).on('click', '.cmb-type-picker-overlay', function (e) {
            if ($(e.target).hasClass('cmb-type-picker-overlay')) {
                $('#cmb-type-picker').hide();
            }
        });

        // Type picker: search
        $(document).on('input', '#cmb-type-search', function () {
            var query = $(this).val().toLowerCase();
            $('.cmb-type-picker-item').each(function () {
                var name = $(this).find('.cmb-type-picker-name').text().toLowerCase();
                var type = $(this).data('type');
                var match = name.indexOf(query) !== -1 || type.indexOf(query) !== -1;
                $(this).toggleClass('hidden', !match);
            });
            // Hide empty categories
            $('.cmb-type-picker-category').each(function () {
                var hasVisible = $(this).find('.cmb-type-picker-item:not(.hidden)').length > 0;
                $(this).toggle(hasVisible);
            });
        });

        // Import modal
        $(document).on('click', '#cmb-import-trigger', function () {
            $('#cmb-import-modal').show();
        });
        $(document).on('click', '#cmb-import-close, .cmb-import-cancel', function () {
            $('#cmb-import-modal').hide();
        });
        $(document).on('click', '.cmb-import-overlay', function (e) {
            if ($(e.target).hasClass('cmb-import-overlay')) {
                $('#cmb-import-modal').hide();
            }
        });

        // File input label update
        $(document).on('change', '.cmb-import-file-input', function () {
            var name = this.files && this.files[0] ? this.files[0].name : '';
            if (name) {
                $(this).closest('.cmb-import-file-label').find('span:last').text(name);
            }
        });

        // Copy code
        $(document).on('click', '#cmb-copy-code', function () {
            var code = $('#cmb-code-output code').text();
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code).then(function () {
                    var $btn = $('#cmb-copy-code');
                    $btn.text('Copied!');
                    setTimeout(function () {
                        $btn.html('<span class="dashicons dashicons-clipboard"></span> Copy Code');
                    }, 2000);
                }).catch(function () {
                    // Fallback for older browsers
                    var textarea = document.createElement('textarea');
                    textarea.value = code;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                });
            }
        });

        // Unsaved changes warning
        $(document).on('change input', '#cmb-builder-form :input', function () {
            markDirty();
        });

        $(window).on('beforeunload', function () {
            if (formDirty) {
                return 'You have unsaved changes.';
            }
        });

        // Reset dirty on form submit
        $('#cmb-builder-form').on('submit', function () {
            formDirty = false;
        });

        // Auto-generate box ID from title
        $('#cmb-title-input').on('input', function () {
            var $idField = $('#cmb-box-id');
            if ($idField.prop('readonly')) return;
            var title = $(this).val();
            var id = slugify(title);
            if (!$idField.data('manual')) {
                $idField.val(id);
            }
        });

        $('#cmb-box-id').on('input', function () {
            $(this).data('manual', true);
        });

        // ESC key closes modals
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                $('#cmb-type-picker').hide();
                $('#cmb-import-modal').hide();
            }
        });

        /* ── Sub-Field Events ── */

        // Add sub-field
        $(document).on('click', '.cmb-add-sub-field', function () {
            var parentIndex = $(this).data('parent-index');
            var $list = $(this).siblings('.cmb-sub-fields-list');
            var subIndex = $list.children('.cmb-sub-field-row').length;
            var html = buildSubFieldHtml(parentIndex, subIndex);
            var $row = $(html);
            $list.append($row);
            updateSubFieldTypeOptions($row);
            $row.find('.cmb-sub-field-label-input').focus();
            markDirty();
        });

        // Remove sub-field
        $(document).on('click', '.cmb-sub-field-remove', function (e) {
            e.stopPropagation();
            if (!confirm('Remove this sub-field?')) return;
            var $row = $(this).closest('.cmb-sub-field-row');
            var $list = $row.parent('.cmb-sub-fields-list');
            $row.slideUp(150, function () {
                $(this).remove();
                reindexSubFields($list);
                markDirty();
            });
        });

        // Toggle sub-field body
        $(document).on('click', '.cmb-sub-field-header', function (e) {
            if ($(e.target).closest('.cmb-sub-field-actions').length) return;
            if ($(e.target).closest('.cmb-sub-field-drag').length) return;
            $(this).closest('.cmb-sub-field-row').toggleClass('open');
        });

        // Sub-field label → auto ID
        $(document).on('input', '.cmb-sub-field-label-input', function () {
            var $row = $(this).closest('.cmb-sub-field-row');
            var label = $(this).val();
            $row.find('.cmb-sub-field-label').text(label || 'New Sub-Field');
            var $idInput = $row.find('.cmb-sub-field-id-input');
            var currentId = $idInput.val();
            var autoId = slugify(label);
            if (!currentId || currentId === slugify($row.data('prev-label') || '')) {
                $idInput.val(autoId);
                $row.find('.cmb-sub-field-id-badge').text(autoId);
            }
            $row.data('prev-label', label);
        });

        // Sub-field type change
        $(document).on('change', '.cmb-sub-field-type-select', function () {
            var $row = $(this).closest('.cmb-sub-field-row');
            var type = $(this).val();
            var typeInfo = cmbAdmin.fieldTypes[type] || {};
            $row.attr('data-type', type);
            $row.find('.cmb-sub-field-type-badge').text(typeInfo.label || type);
            $row.find('.cmb-sub-field-icon').attr('class', 'dashicons ' + (typeInfo.icon || 'dashicons-admin-generic') + ' cmb-sub-field-icon');
            updateSubFieldTypeOptions($row);
        });
    }

    /* ─── Add Field ─────────────────────────────────────── */

    function addField(type) {
        var idx = fieldIndex++;
        var typeInfo = cmbAdmin.fieldTypes[type] || { label: type, icon: 'dashicons-admin-generic' };
        var prefix = 'cmb_fields[' + idx + ']';

        var html = '<div class="cmb-field-row open" data-index="' + idx + '" data-type="' + type + '">';

        // Header
        html += '<div class="cmb-field-row-header">';
        html += '<span class="cmb-field-drag dashicons dashicons-menu"></span>';
        html += '<span class="cmb-field-icon dashicons ' + typeInfo.icon + '"></span>';
        html += '<span class="cmb-field-row-label"><em>New Field</em></span>';
        html += '<span class="cmb-field-row-meta">';
        html += '<span class="cmb-field-row-type">' + typeInfo.label + '</span>';
        html += '<code class="cmb-field-row-id"></code>';
        html += '</span>';
        html += '<span class="cmb-field-row-actions">';
        html += '<button type="button" class="cmb-field-duplicate" title="Duplicate"><span class="dashicons dashicons-admin-page"></span></button>';
        html += '<button type="button" class="cmb-field-remove" title="Delete"><span class="dashicons dashicons-trash"></span></button>';
        html += '<button type="button" class="cmb-field-toggle"><span class="dashicons dashicons-arrow-down-alt2"></span></button>';
        html += '</span>';
        html += '</div>';

        // Body
        html += '<div class="cmb-field-row-body">';
        html += '<div class="cmb-field-settings-grid">';

        // Label
        html += '<div class="cmb-fs-row cmb-fs-half">';
        html += '<label>Field Label</label>';
        html += '<input type="text" name="' + prefix + '[label]" class="widefat cmb-field-label-input" placeholder="e.g. Author Name">';
        html += '</div>';

        // ID
        html += '<div class="cmb-fs-row cmb-fs-half">';
        html += '<label>Field ID <small class="cmb-auto-id">(auto)</small></label>';
        html += '<input type="text" name="' + prefix + '[id]" class="widefat cmb-field-id-input" placeholder="auto_generated" required>';
        html += '</div>';

        // Type
        html += '<div class="cmb-fs-row cmb-fs-half">';
        html += '<label>Field Type</label>';
        html += '<select name="' + prefix + '[type]" class="widefat cmb-field-type-select">';
        $.each(cmbAdmin.fieldGroups, function (_, cat) {
            html += '<optgroup label="' + escHtml(cat.label) + '">';
            $.each(cat.types, function (key, info) {
                var sel = (key === type) ? ' selected' : '';
                html += '<option value="' + key + '"' + sel + '>' + escHtml(info.label) + '</option>';
            });
            html += '</optgroup>';
        });
        html += '</select>';
        html += '</div>';

        // Description
        html += '<div class="cmb-fs-row cmb-fs-half">';
        html += '<label>Description</label>';
        html += '<input type="text" name="' + prefix + '[description]" class="widefat" placeholder="Help text shown below the field">';
        html += '</div>';

        html += '</div>'; // .cmb-field-settings-grid

        // Type-specific options
        html += '<div class="cmb-type-options">';

        // Placeholder + Default (text, textarea, number, email, url, password)
        html += '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="text,textarea,number,email,url,password">';
        html += '<div class="cmb-fs-row cmb-fs-half"><label>Placeholder</label>';
        html += '<input type="text" name="' + prefix + '[placeholder]" class="widefat"></div>';
        html += '<div class="cmb-fs-row cmb-fs-half"><label>Default Value</label>';
        html += '<input type="text" name="' + prefix + '[default_value]" class="widefat"></div>';
        html += '</div>';

        // Default for date, color, hidden
        html += '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="date,color,hidden">';
        html += '<div class="cmb-fs-row cmb-fs-half"><label>Default Value</label>';
        html += '<input type="text" name="' + prefix + '[default_value_dc]" class="widefat"></div>';
        html += '</div>';

        // Textarea rows
        html += '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="textarea,wysiwyg">';
        html += '<div class="cmb-fs-row cmb-fs-third"><label>Rows</label>';
        html += '<input type="number" name="' + prefix + '[rows]" class="widefat" placeholder="5" min="1" max="50"></div>';
        html += '</div>';

        // Number min/max/step
        html += '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="number">';
        html += '<div class="cmb-fs-row cmb-fs-third"><label>Min</label>';
        html += '<input type="number" name="' + prefix + '[min]" class="widefat"></div>';
        html += '<div class="cmb-fs-row cmb-fs-third"><label>Max</label>';
        html += '<input type="number" name="' + prefix + '[max]" class="widefat"></div>';
        html += '<div class="cmb-fs-row cmb-fs-third"><label>Step</label>';
        html += '<input type="number" name="' + prefix + '[step]" class="widefat" placeholder="1"></div>';
        html += '</div>';

        // Select/Radio options
        html += '<div class="cmb-type-opt" data-show-for="select,radio">';
        html += '<div class="cmb-fs-row"><label>Options <small>One per line: <code>value|Label</code></small></label>';
        html += '<textarea name="' + prefix + '[options]" class="widefat cmb-options-textarea" rows="4" placeholder="option1|Option One&#10;option2|Option Two"></textarea>';
        html += '</div></div>';

        // Post type for post field
        html += '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="post">';
        html += '<div class="cmb-fs-row cmb-fs-half"><label>Post Type</label>';
        html += '<select name="' + prefix + '[post_type]" class="widefat">';
        $.each(cmbAdmin.postTypes, function (slug, name) {
            html += '<option value="' + slug + '">' + escHtml(name) + '</option>';
        });
        html += '</select></div></div>';

        // Taxonomy
        html += '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="taxonomy">';
        html += '<div class="cmb-fs-row cmb-fs-half"><label>Taxonomy</label>';
        html += '<select name="' + prefix + '[taxonomy]" class="widefat">';
        $.each(cmbAdmin.taxonomies, function (slug, name) {
            html += '<option value="' + slug + '">' + escHtml(name) + '</option>';
        });
        html += '</select></div></div>';

        // User role
        html += '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="user">';
        html += '<div class="cmb-fs-row cmb-fs-half"><label>User Role <small>(empty = all)</small></label>';
        html += '<select name="' + prefix + '[role]" class="widefat"><option value="">All Roles</option>';
        $.each(cmbAdmin.roles, function (slug, name) {
            if (slug === 'all') return;
            html += '<option value="' + slug + '">' + escHtml(name) + '</option>';
        });
        html += '</select></div></div>';

        // Group settings
        html += '<div class="cmb-field-settings-grid cmb-type-opt" data-show-for="group">';
        html += '<div class="cmb-fs-row cmb-fs-third"><label>Min Rows</label>';
        html += '<input type="number" name="' + prefix + '[min_rows]" class="widefat" min="0"></div>';
        html += '<div class="cmb-fs-row cmb-fs-third"><label>Max Rows</label>';
        html += '<input type="number" name="' + prefix + '[max_rows]" class="widefat" min="0"></div>';
        html += '<div class="cmb-fs-row cmb-fs-third"><label class="cmb-checkbox-label" style="margin-top:24px">';
        html += '<input type="checkbox" name="' + prefix + '[collapsed]" value="1" checked> Collapsed';
        html += '</label></div></div>';

        // Group sub-fields area
        html += '<div class="cmb-type-opt cmb-sub-fields-wrap" data-show-for="group">';
        html += '<div class="cmb-sub-fields-header"><label>Sub-Fields</label>';
        html += '<small>Define the fields that appear inside each group row.</small></div>';
        html += '<div class="cmb-sub-fields-list" data-parent-index="' + idx + '"></div>';
        html += '<button type="button" class="button cmb-add-sub-field" data-parent-index="' + idx + '">';
        html += '<span class="dashicons dashicons-plus-alt2"></span> Add Sub-Field</button>';
        html += '</div>';

        html += '</div>'; // .cmb-type-options

        // Bottom row
        html += '<div class="cmb-field-bottom-row">';
        html += '<label class="cmb-checkbox-label">';
        html += '<input type="checkbox" name="' + prefix + '[required]" value="1"> Required</label>';
        html += '<label class="cmb-checkbox-label cmb-type-opt-inline" data-hide-for="group,checkbox,hidden">';
        html += '<input type="checkbox" name="' + prefix + '[repeatable]" value="1"> Repeatable</label>';
        html += '<div class="cmb-width-control"><label>Width</label>';
        html += '<select name="' + prefix + '[width]">';
        html += '<option value="100">100%</option><option value="75">75%</option>';
        html += '<option value="50">50%</option><option value="33">33%</option><option value="25">25%</option>';
        html += '</select></div>';
        html += '</div>';

        html += '</div>'; // .cmb-field-row-body
        html += '</div>'; // .cmb-field-row

        // Remove empty message and append
        $('.cmb-no-fields-msg').remove();
        var $newRow = $(html);
        $('#cmb-fields-list').append($newRow);
        updateTypeOptions($newRow);

        // Scroll to new field
        $('html, body').animate({
            scrollTop: $newRow.offset().top - 100
        }, 300);

        // Focus label input
        $newRow.find('.cmb-field-label-input').focus();
        markDirty();
    }

    /* ─── Type-specific Options Visibility ──────────────── */

    function updateTypeOptions($row) {
        var type = $row.attr('data-type') || $row.find('.cmb-field-type-select').val() || 'text';

        $row.find('.cmb-type-opt').each(function () {
            var showFor = ($(this).data('show-for') || '').split(',');
            var shouldShow = showFor.indexOf(type) !== -1;
            $(this).toggleClass('visible', shouldShow);
        });

        $row.find('.cmb-type-opt-inline').each(function () {
            var hideFor = ($(this).data('hide-for') || '').split(',');
            var shouldShow = hideFor.indexOf(type) === -1;
            $(this).toggleClass('visible', shouldShow);
        });
    }

    /* ─── Reindex Fields ────────────────────────────────── */

    function reindexFields() {
        $('#cmb-fields-list .cmb-field-row').each(function (newIndex) {
            var oldIndex = $(this).data('index');
            $(this).attr('data-index', newIndex).data('index', newIndex);
            $(this).find('[name]').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/cmb_fields\[\d+\]/, 'cmb_fields[' + newIndex + ']'));
                }
            });
        });
        fieldIndex = $('#cmb-fields-list .cmb-field-row').length;
    }

    /* ─── Code Preview ──────────────────────────────────── */

    function updateCodePreview() {
        var $form = $('#cmb-builder-form');
        if (!$form.length) return;

        var title = $('#cmb-title-input').val() || 'My Field Group';
        var boxId = $('#cmb-box-id').val() || 'my_field_group';
        var context = $('[name="cmb_box_context"]').val() || 'advanced';
        var priority = $('[name="cmb_box_priority"]').val() || 'default';

        var postTypes = [];
        $('[name="cmb_box_post_types[]"]:checked').each(function () {
            postTypes.push("'" + $(this).val() + "'");
        });
        if (!postTypes.length) postTypes = ["'post'"];

        var code = "<?php\n";
        code += "/**\n * Register '" + title + "' field group.\n */\n";
        code += "add_action('plugins_loaded', function () {\n";
        code += "    if (!function_exists('add_custom_meta_box')) return;\n\n";
        code += "    add_custom_meta_box(\n";
        code += "        '" + boxId + "',\n";
        code += "        '" + escPhp(title) + "',\n";
        code += "        [" + postTypes.join(', ') + "],\n";
        code += "        [\n";

        // Fields
        var fieldLines = [];
        $('.cmb-field-row').each(function () {
            var $r = $(this);
            var fId = $r.find('.cmb-field-id-input').val();
            var fType = $r.find('.cmb-field-type-select').val() || 'text';
            var fLabel = $r.find('.cmb-field-label-input').val();

            if (!fId) return;

            var parts = [];
            parts.push("'id' => '" + escPhp(fId) + "'");
            parts.push("'type' => '" + fType + "'");
            if (fLabel) parts.push("'label' => '" + escPhp(fLabel) + "'");

            var desc = $r.find('[name$="[description]"]').val();
            if (desc) parts.push("'description' => '" + escPhp(desc) + "'");

            var req = $r.find('[name$="[required]"]').is(':checked');
            if (req) parts.push("'required' => true");

            var ph = $r.find('[name$="[placeholder]"]').val();
            if (ph) parts.push("'placeholder' => '" + escPhp(ph) + "'");

            var def = $r.find('[name$="[default_value]"]').val() || $r.find('[name$="[default_value_dc]"]').val();
            if (def) parts.push("'default' => '" + escPhp(def) + "'");

            var opts = $r.find('.cmb-options-textarea').val();
            if (opts && (fType === 'select' || fType === 'radio')) {
                var optParts = [];
                opts.split('\n').forEach(function (line) {
                    line = line.trim();
                    if (!line) return;
                    var p = line.split('|');
                    if (p.length >= 2) {
                        optParts.push("'" + escPhp(p[0].trim()) + "' => '" + escPhp(p[1].trim()) + "'");
                    } else {
                        optParts.push("'" + escPhp(line) + "' => '" + escPhp(line) + "'");
                    }
                });
                if (optParts.length) {
                    parts.push("'options' => [" + optParts.join(', ') + "]");
                }
            }

            fieldLines.push("            [" + parts.join(', ') + "]");
        });

        code += fieldLines.join(",\n") + "\n";
        code += "        ],\n";
        code += "        '" + context + "',\n";
        code += "        '" + priority + "'\n";
        code += "    );\n";
        code += "});\n";

        $('#cmb-code-output code').text(code);
    }

    /* ─── Helpers ───────────────────────────────────────── */

    function slugify(str) {
        return str.toLowerCase()
            .replace(/[^a-z0-9\s_-]/g, '')
            .replace(/[\s-]+/g, '_')
            .replace(/^_+|_+$/g, '')
            .substring(0, 64);
    }

    function escHtml(str) {
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(str).replace(/[&<>"']/g, function (m) { return map[m]; });
    }

    function escPhp(str) {
        return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    }

    function markDirty() {
        formDirty = true;
    }

    function toggleEmptyMessage() {
        var $list = $('#cmb-fields-list');
        if ($list.find('.cmb-field-row').length === 0) {
            if (!$list.find('.cmb-no-fields-msg').length) {
                $list.append('<div class="cmb-no-fields-msg"><p>No fields yet. Click the button below to add your first field.</p></div>');
            }
        }
    }

    /* ─── Sub-Field Helpers ─────────────────────────────── */

    function buildSubFieldHtml(parentIndex, subIndex) {
        var prefix = 'cmb_fields[' + parentIndex + '][sub_fields][' + subIndex + ']';

        var html = '<div class="cmb-sub-field-row open" data-sub-index="' + subIndex + '" data-type="text">';

        // Header
        html += '<div class="cmb-sub-field-header">';
        html += '<span class="cmb-sub-field-drag dashicons dashicons-menu"></span>';
        html += '<span class="dashicons dashicons-editor-textcolor cmb-sub-field-icon"></span>';
        html += '<span class="cmb-sub-field-label"><em>New Sub-Field</em></span>';
        html += '<span class="cmb-sub-field-type-badge">Text</span>';
        html += '<code class="cmb-sub-field-id-badge"></code>';
        html += '<span class="cmb-sub-field-actions">';
        html += '<button type="button" class="cmb-sub-field-remove" title="Remove"><span class="dashicons dashicons-no-alt"></span></button>';
        html += '</span>';
        html += '</div>';

        // Body
        html += '<div class="cmb-sub-field-body">';
        html += '<div class="cmb-field-settings-grid">';

        html += '<div class="cmb-fs-row cmb-fs-half"><label>Label</label>';
        html += '<input type="text" name="' + prefix + '[label]" class="widefat cmb-sub-field-label-input" placeholder="Field Label"></div>';

        html += '<div class="cmb-fs-row cmb-fs-half"><label>ID <small>(auto)</small></label>';
        html += '<input type="text" name="' + prefix + '[id]" class="widefat cmb-sub-field-id-input" placeholder="auto_generated" required></div>';

        html += '<div class="cmb-fs-row cmb-fs-half"><label>Type</label>';
        html += '<select name="' + prefix + '[type]" class="widefat cmb-sub-field-type-select">';
        $.each(cmbAdmin.fieldGroups, function (_, cat) {
            html += '<optgroup label="' + escHtml(cat.label) + '">';
            $.each(cat.types, function (key, info) {
                if (key === 'group') return; // No nested groups
                var sel = (key === 'text') ? ' selected' : '';
                html += '<option value="' + key + '"' + sel + '>' + escHtml(info.label) + '</option>';
            });
            html += '</optgroup>';
        });
        html += '</select></div>';

        html += '<div class="cmb-fs-row cmb-fs-half"><label>Description</label>';
        html += '<input type="text" name="' + prefix + '[description]" class="widefat"></div>';

        html += '</div>'; // .cmb-field-settings-grid

        // Placeholder & Default
        html += '<div class="cmb-field-settings-grid cmb-sub-type-opt" data-show-for="text,textarea,number,email,url,password">';
        html += '<div class="cmb-fs-row cmb-fs-half"><label>Placeholder</label>';
        html += '<input type="text" name="' + prefix + '[placeholder]" class="widefat"></div>';
        html += '<div class="cmb-fs-row cmb-fs-half"><label>Default Value</label>';
        html += '<input type="text" name="' + prefix + '[default_value]" class="widefat"></div>';
        html += '</div>';

        // Options for select/radio
        html += '<div class="cmb-sub-type-opt" data-show-for="select,radio">';
        html += '<div class="cmb-fs-row"><label>Options <small>One per line: <code>value|Label</code></small></label>';
        html += '<textarea name="' + prefix + '[options]" class="widefat cmb-options-textarea" rows="3" placeholder="option1|Option One&#10;option2|Option Two"></textarea>';
        html += '</div></div>';

        // Required
        html += '<div class="cmb-sub-field-bottom">';
        html += '<label class="cmb-checkbox-label">';
        html += '<input type="checkbox" name="' + prefix + '[required]" value="1"> Required</label>';
        html += '</div>';

        html += '</div>'; // .cmb-sub-field-body
        html += '</div>'; // .cmb-sub-field-row

        return html;
    }

    function reindexSubFields($list) {
        var parentIndex = $list.data('parent-index');
        $list.children('.cmb-sub-field-row').each(function (newIndex) {
            $(this).attr('data-sub-index', newIndex);
            $(this).find('[name]').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    // Replace sub_fields[N] part only
                    $(this).attr('name', name.replace(
                        /cmb_fields\[\d+\]\[sub_fields\]\[\d+\]/,
                        'cmb_fields[' + parentIndex + '][sub_fields][' + newIndex + ']'
                    ));
                }
            });
        });
    }

    function updateSubFieldTypeOptions($row) {
        var type = $row.attr('data-type') || $row.find('.cmb-sub-field-type-select').val() || 'text';
        $row.find('.cmb-sub-type-opt').each(function () {
            var showFor = ($(this).data('show-for') || '').split(',');
            $(this).toggleClass('visible', showFor.indexOf(type) !== -1);
        });
    }

})(jQuery);
