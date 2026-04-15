/**
 * CMB Builder – Admin UI Scripts
 * Handles field builder interactions, type picker, code generation, and import/export.
 */
(function ($) {
    'use strict';

    let fieldIndex = 0;
    let formDirty = false;

    $(document).ready(function () {
        initFieldIndex();
        initSortable();
        initTypeVisibility();
        bindEvents();
        updateCodePreview();
    });

    /* ─── Initialization ────────────────────────────────── */

    function initFieldIndex() {
        let maxIdx = -1;
        $('.cmb-field-row').each(function () {
            let idx = parseInt($(this).data('index'), 10);
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
            // Apply disabled visual state on load
            if ($(this).find('.cmb-field-disabled-input').first().is(':checked')) {
                $(this).addClass('cmb-field-is-disabled');
            }
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
            let $row = $(this).closest('.cmb-field-row');
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
            let $row = $(this).closest('.cmb-field-row');
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
            let $row = $(this).closest('.cmb-field-row');
            let $clone = $row.clone(false, false);
            let newIdx = fieldIndex++;

            // Update index
            $clone.attr('data-index', newIdx);
            $clone.removeClass('open');

            // Update input names
            $clone.find('[name]').each(function () {
                let name = $(this).attr('name');
                name = name.replace(/cmb_fields\[\d+\]/, 'cmb_fields[' + newIdx + ']');
                $(this).attr('name', name);
            });

            // Update ID to avoid duplicate
            let $idInput = $clone.find('.cmb-field-id-input');
            if ($idInput.val()) {
                $idInput.val($idInput.val() + '_copy');
            }

            // Update header label
            let label = $clone.find('.cmb-field-label-input').val();
            $clone.find('.cmb-field-row-label').text(label ? label + ' (Copy)' : 'New Field');

            $clone.hide().insertAfter($row).slideDown(200);
            markDirty();
        });

        // Label → auto-generate ID
        $(document).on('input', '.cmb-field-label-input', function () {
            let $row = $(this).closest('.cmb-field-row');
            let $idInput = $row.find('.cmb-field-id-input');
            let label = $(this).val();

            // Update header label
            $row.find('.cmb-field-row-label').text(label || 'New Field');

            // Auto-generate ID (only if the ID looks auto-generated or is empty)
            let currentId = $idInput.val();
            let autoId = slugify(label);
            if (!currentId || currentId === slugify($row.data('prev-label') || '')) {
                $idInput.val(autoId);
                $row.find('.cmb-field-row-id').text(autoId);
            }
            $row.data('prev-label', label);
            markDirty();
        });

        // ID input change → update header
        $(document).on('input', '.cmb-field-id-input', function () {
            let $row = $(this).closest('.cmb-field-row');
            $row.find('.cmb-field-row-id').text($(this).val());
            markDirty();
        });

        // Type change
        $(document).on('change', '.cmb-field-type-select', function () {
            let $row = $(this).closest('.cmb-field-row');
            let type = $(this).val();
            let typeInfo = cmbAdmin.fieldTypes[type] || {};

            $row.attr('data-type', type);
            $row.find('.cmb-field-row-type').text(typeInfo.label || type);
            $row.find('.cmb-field-icon').attr('class', 'cmb-field-icon dashicons ' + (typeInfo.icon || 'dashicons-admin-generic'));

            updateTypeOptions($row);
            markDirty();
        });

        // Required checkbox → update badge
        $(document).on('change', 'input[name$="[required]"]', function () {
            let $row = $(this).closest('.cmb-field-row');
            let $badge = $row.find('.cmb-required-badge');
            if ($(this).is(':checked')) {
                if (!$badge.length) {
                    $row.find('.cmb-field-row-meta').append('<span class="cmb-required-badge">Required</span>');
                }
            } else {
                $badge.remove();
            }
        });

        // Disabled checkbox → update badge and visual state
        $(document).on('change', '.cmb-field-disabled-input', function () {
            let $row = $(this).closest('.cmb-field-row');
            let $badge = $row.find('.cmb-disabled-badge');
            if ($(this).is(':checked')) {
                $row.addClass('cmb-field-is-disabled');
                if (!$badge.length) {
                    $row.find('.cmb-field-row-meta').prepend('<span class="cmb-disabled-badge">Disabled</span>');
                }
            } else {
                $row.removeClass('cmb-field-is-disabled');
                $badge.remove();
            }
        });

        // Toggle switch text update
        $(document).on('change', '.cmb-toggle-label input', function () {
            let $text = $(this).closest('.cmb-toggle-label').find('.cmb-toggle-text');
            $text.text($(this).is(':checked') ? 'Active' : 'Inactive');
        });

        // Tab switching
        $(document).on('click', '.cmb-editor-tab', function () {
            let tab = $(this).data('tab');
            $('.cmb-editor-tab').removeClass('active');
            $(this).addClass('active');
            $('.cmb-editor-panel').removeClass('active');
            $('#cmb-panel-' + tab).addClass('active');

            if (tab === 'code') {
                updateCodePreview();
            }
            if (tab === 'result') {
                updateResultPreview();
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
            let type = $(this).data('type');
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
            let query = $(this).val().toLowerCase();
            $('.cmb-type-picker-item').each(function () {
                let name = $(this).find('.cmb-type-picker-name').text().toLowerCase();
                let type = $(this).data('type');
                let match = name.indexOf(query) !== -1 || type.indexOf(query) !== -1;
                $(this).toggleClass('hidden', !match);
            });
            // Hide empty categories
            $('.cmb-type-picker-category').each(function () {
                let hasVisible = $(this).find('.cmb-type-picker-item:not(.hidden)').length > 0;
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
            let name = this.files && this.files[0] ? this.files[0].name : '';
            if (name) {
                $(this).closest('.cmb-import-file-label').find('span:last').text(name);
            }
        });

        // Copy code
        $(document).on('click', '#cmb-copy-code', function () {
            let code = $('#cmb-code-output code').text();
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code).then(function () {
                    let $btn = $('#cmb-copy-code');
                    $btn.text('Copied!');
                    setTimeout(function () {
                        $btn.html('<span class="dashicons dashicons-clipboard"></span> Copy Code');
                    }, 2000);
                }).catch(function () {
                    // Fallback for older browsers
                    let textarea = document.createElement('textarea');
                    textarea.value = code;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                });
            }
        });

        // Copy result code
        $(document).on('click', '#cmb-copy-result', function () {
            let code = '';
            $('#cmb-result-output .cmb-code-preview code').each(function () {
                code += $(this).text() + '\n\n';
            });
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code.trim()).then(function () {
                    let $btn = $('#cmb-copy-result');
                    $btn.text('Copied!');
                    setTimeout(function () {
                        $btn.html('<span class="dashicons dashicons-clipboard"></span> Copy All');
                    }, 2000);
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
            let $idField = $('#cmb-box-id');
            if ($idField.prop('readonly')) return;
            let title = $(this).val();
            let id = slugify(title);
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

        // Conditional logic toggle
        $(document).on('change', '.cmb-conditional-toggle', function () {
            let $settings = $(this).closest('.cmb-conditional-row').find('.cmb-conditional-settings');
            if ($(this).is(':checked')) {
                $settings.slideDown(150);
            } else {
                $settings.slideUp(150);
                // Clear conditional fields when unchecked
                $settings.find('input[type="text"]').val('');
                $settings.find('select').prop('selectedIndex', 0);
            }
        });

        /* ── Sub-Field Events ── */

        // Add sub-field (supports infinite nesting via data-parent-prefix)
        $(document).on('click', '.cmb-add-sub-field', function () {
            let $list = $(this).siblings('.cmb-sub-fields-list');
            let subIndex = $list.children('.cmb-sub-field-row').length;
            let parentPrefix = $(this).data('parent-prefix') || '';
            let parentIndex = $(this).data('parent-index');
            let prefix;
            if (parentPrefix) {
                prefix = parentPrefix + '[sub_fields][' + subIndex + ']';
            } else {
                prefix = 'cmb_fields[' + parentIndex + '][sub_fields][' + subIndex + ']';
            }
            let html = buildSubFieldHtml(prefix, subIndex);
            let $row = $(html);
            $list.append($row);
            updateSubFieldTypeOptions($row);
            $row.find('> .cmb-sub-field-body .cmb-sub-field-label-input').first().focus();
            markDirty();
        });

        // Remove sub-field
        $(document).on('click', '.cmb-sub-field-remove', function (e) {
            e.stopPropagation();
            if (!confirm('Remove this sub-field?')) return;
            let $row = $(this).closest('.cmb-sub-field-row');
            let $list = $row.parent('.cmb-sub-fields-list');
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
            let $row = $(this).closest('.cmb-sub-field-row');
            let label = $(this).val();
            $row.find('.cmb-sub-field-label').text(label || 'New Sub-Field');
            let $idInput = $row.find('.cmb-sub-field-id-input');
            let currentId = $idInput.val();
            let autoId = slugify(label);
            if (!currentId || currentId === slugify($row.data('prev-label') || '')) {
                $idInput.val(autoId);
                $row.find('.cmb-sub-field-id-badge').text(autoId);
            }
            $row.data('prev-label', label);
        });

        // Sub-field type change
        $(document).on('change', '.cmb-sub-field-type-select', function () {
            let $row = $(this).closest('.cmb-sub-field-row');
            let type = $(this).val();
            let typeInfo = cmbAdmin.fieldTypes[type] || {};
            $row.attr('data-type', type);
            $row.find('> .cmb-sub-field-header .cmb-sub-field-type-badge').text(typeInfo.label || type);
            $row.find('> .cmb-sub-field-header .cmb-sub-field-icon').attr('class', 'dashicons ' + (typeInfo.icon || 'dashicons-admin-generic') + ' cmb-sub-field-icon');
            updateSubFieldTypeOptions($row);
            // Show/hide nested sub-fields area for group type
            let $nested = $row.find('> .cmb-sub-field-body > .cmb-nested-sub-fields');
            if (type === 'group') {
                $nested.show();
            } else {
                $nested.hide();
            }
        });
    }

    /* ─── Add Field ─────────────────────────────────────── */

    function addField(type) {
        let idx = fieldIndex++;
        let typeInfo = cmbAdmin.fieldTypes[type] || { label: type, icon: 'dashicons-admin-generic' };
        let prefix = 'cmb_fields[' + idx + ']';

        let html = '<div class="cmb-field-row open" data-index="' + idx + '" data-type="' + type + '">';

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
                let sel = (key === type) ? ' selected' : '';
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
        let $newRow = $(html);
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
        let type = $row.attr('data-type') || $row.find('.cmb-field-type-select').val() || 'text';

        $row.find('.cmb-type-opt').each(function () {
            let showFor = ($(this).data('show-for') || '').split(',');
            let shouldShow = showFor.indexOf(type) !== -1;
            $(this).toggleClass('visible', shouldShow);
        });

        $row.find('.cmb-type-opt-inline').each(function () {
            let hideFor = ($(this).data('hide-for') || '').split(',');
            let shouldShow = hideFor.indexOf(type) === -1;
            $(this).toggleClass('visible', shouldShow);
        });
    }

    /* ─── Reindex Fields ────────────────────────────────── */

    function reindexFields() {
        $('#cmb-fields-list .cmb-field-row').each(function (newIndex) {
            let oldIndex = $(this).data('index');
            $(this).attr('data-index', newIndex).data('index', newIndex);
            $(this).find('[name]').each(function () {
                let name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/cmb_fields\[\d+\]/, 'cmb_fields[' + newIndex + ']'));
                }
            });
        });
        fieldIndex = $('#cmb-fields-list .cmb-field-row').length;
    }

    /* ─── Code Preview ──────────────────────────────────── */

    function updateCodePreview() {
        let $form = $('#cmb-builder-form');
        if (!$form.length) return;

        let title = $('#cmb-title-input').val() || 'My Field Group';
        let boxId = $('#cmb-box-id').val() || 'my_field_group';
        let context = $('[name="cmb_box_context"]').val() || 'advanced';
        let priority = $('[name="cmb_box_priority"]').val() || 'default';

        let postTypes = [];
        $('[name="cmb_box_post_types[]"]:checked').each(function () {
            postTypes.push("'" + $(this).val() + "'");
        });
        if (!postTypes.length) postTypes = ["'post'"];

        let code = "<?php\n";
        code += "/**\n * Register '" + title + "' field group.\n */\n";
        code += "add_action('plugins_loaded', function () {\n";
        code += "    if (!function_exists('add_custom_meta_box')) return;\n\n";
        code += "    add_custom_meta_box(\n";
        code += "        '" + boxId + "',\n";
        code += "        '" + escPhp(title) + "',\n";
        code += "        [" + postTypes.join(', ') + "],\n";
        code += "        [\n";

        // Fields
        let fieldLines = [];
        $('.cmb-field-row').each(function () {
            let $r = $(this);
            let fId = $r.find('.cmb-field-id-input').val();
            let fType = $r.find('.cmb-field-type-select').val() || 'text';
            let fLabel = $r.find('.cmb-field-label-input').val();

            if (!fId) return;

            let parts = [];
            parts.push("'id' => '" + escPhp(fId) + "'");
            parts.push("'type' => '" + fType + "'");
            if (fLabel) parts.push("'label' => '" + escPhp(fLabel) + "'");

            let desc = $r.find('[name$="[description]"]').val();
            if (desc) parts.push("'description' => '" + escPhp(desc) + "'");

            let req = $r.find('[name$="[required]"]').is(':checked');
            if (req) parts.push("'required' => true");

            let ph = $r.find('[name$="[placeholder]"]').val();
            if (ph) parts.push("'placeholder' => '" + escPhp(ph) + "'");

            let def = $r.find('[name$="[default_value]"]').val() || $r.find('[name$="[default_value_dc]"]').val();
            if (def) parts.push("'default' => '" + escPhp(def) + "'");

            let opts = $r.find('.cmb-options-textarea').val();
            if (opts && (fType === 'select' || fType === 'radio')) {
                let optParts = [];
                opts.split('\n').forEach(function (line) {
                    line = line.trim();
                    if (!line) return;
                    let p = line.split('|');
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

    /* ─── Result Preview ───────────────────────────────── */

    function updateResultPreview() {
        let $form = $('#cmb-builder-form');
        if (!$form.length) return;

        let $output = $('#cmb-result-output');
        let sections = '';
        let hasFields = false;

        // Collect all fields
        $('.cmb-field-row').each(function () {
            let $r = $(this);
            let fId = $r.find('.cmb-field-id-input').val();
            let fType = $r.find('.cmb-field-type-select').val() || 'text';
            let fLabel = $r.find('.cmb-field-label-input').val() || fId;

            if (!fId) return;
            hasFields = true;

            let code = '';
            let hint = '';

            if (fType === 'group') {
                hint = 'Repeater Group';
                code = "<?php\n";
                code += "// Retrieve '" + escPhp(fLabel) + "' group rows\n";
                code += "$" + fId + " = get_post_meta(get_the_ID(), '" + escPhp(fId) + "', true);\n\n";
                code += "if (!empty($" + fId + ") && is_array($" + fId + ")) :\n";
                code += "    foreach ($" + fId + " as $index => $row) :\n";
                // Show sub-fields
                let subFields = [];
                $r.find('.cmb-sub-fields-list').first().children('.cmb-sub-field-row').each(function () {
                    let sfId = $(this).find('.cmb-sub-field-id-input').first().val();
                    let sfType = $(this).find('.cmb-sub-field-type-select').first().val() || 'text';
                    let sfLabel = $(this).find('.cmb-sub-field-label-input').first().val() || sfId;
                    if (sfId) {
                        subFields.push({ id: sfId, type: sfType, label: sfLabel });
                    }
                });
                if (subFields.length) {
                    subFields.forEach(function (sf) {
                        code += "        $" + sf.id + " = $row['" + escPhp(sf.id) + "'] ?? '';\n";
                    });
                    code += "\n        ?>\n";
                    code += "        <div class=\"group-row\">\n";
                    subFields.forEach(function (sf) {
                        if (sf.type === 'image') {
                            code += "            <?php if ($" + sf.id + ") : ?>\n";
                            code += "                <img src=\"<?php echo esc_url(wp_get_attachment_url($" + sf.id + ")); ?>\" alt=\"\">\n";
                            code += "            <?php endif; ?>\n";
                        } else {
                            code += "            <p><strong>" + escHtml(sf.label) + ":</strong> <?php echo esc_html($" + sf.id + "); ?></p>\n";
                        }
                    });
                    code += "        </div>\n";
                    code += "        <?php\n";
                } else {
                    code += "        // Access sub-field values: $row['sub_field_id']\n";
                    code += "        ?>\n";
                    code += "        <div class=\"group-row\">\n";
                    code += "            <!-- Output sub-field values here -->\n";
                    code += "        </div>\n";
                    code += "        <?php\n";
                }
                code += "    endforeach;\n";
                code += "endif;\n";
                code += "?>";
            } else if (fType === 'image') {
                hint = 'Image';
                code = "<?php\n";
                code += "// Retrieve '" + escPhp(fLabel) + "' (attachment ID)\n";
                code += "$" + fId + " = get_post_meta(get_the_ID(), '" + escPhp(fId) + "', true);\n\n";
                code += "if ($" + fId + ") :\n";
                code += "    $image_url = wp_get_attachment_url($" + fId + ");\n";
                code += "    $image_alt = get_post_meta($" + fId + ", '_wp_attachment_image_alt', true);\n";
                code += "    ?>\n";
                code += "    <img src=\"<?php echo esc_url($image_url); ?>\" alt=\"<?php echo esc_attr($image_alt); ?>\">\n";
                code += "<?php endif; ?>";
            } else if (fType === 'gallery') {
                hint = 'Gallery';
                code = "<?php\n";
                code += "// Retrieve '" + escPhp(fLabel) + "' (array of attachment IDs)\n";
                code += "$" + fId + " = get_post_meta(get_the_ID(), '" + escPhp(fId) + "', true);\n\n";
                code += "if (!empty($" + fId + ") && is_array($" + fId + ")) : ?>\n";
                code += "    <div class=\"gallery\">\n";
                code += "        <?php foreach ($" + fId + " as $image_id) : ?>\n";
                code += "            <img src=\"<?php echo esc_url(wp_get_attachment_url($image_id)); ?>\" alt=\"\">\n";
                code += "        <?php endforeach; ?>\n";
                code += "    </div>\n";
                code += "<?php endif; ?>";
            } else if (fType === 'file') {
                hint = 'File';
                code = "<?php\n";
                code += "// Retrieve '" + escPhp(fLabel) + "' (attachment ID)\n";
                code += "$" + fId + " = get_post_meta(get_the_ID(), '" + escPhp(fId) + "', true);\n\n";
                code += "if ($" + fId + ") :\n";
                code += "    $file_url = wp_get_attachment_url($" + fId + ");\n";
                code += "    ?>\n";
                code += "    <a href=\"<?php echo esc_url($file_url); ?>\" download>Download File</a>\n";
                code += "<?php endif; ?>";
            } else if (fType === 'checkbox') {
                hint = 'Checkbox';
                code = "<?php\n";
                code += "// Retrieve '" + escPhp(fLabel) + "' (boolean)\n";
                code += "$" + fId + " = get_post_meta(get_the_ID(), '" + escPhp(fId) + "', true);\n\n";
                code += "if ($" + fId + ") :\n";
                code += "    echo '<span>Yes</span>';\n";
                code += "else :\n";
                code += "    echo '<span>No</span>';\n";
                code += "endif;\n";
                code += "?>";
            } else if (fType === 'select' || fType === 'radio') {
                hint = fType === 'select' ? 'Select' : 'Radio';
                code = "<?php\n";
                code += "// Retrieve '" + escPhp(fLabel) + "' (selected value)\n";
                code += "$" + fId + " = get_post_meta(get_the_ID(), '" + escPhp(fId) + "', true);\n\n";
                code += "if ($" + fId + ") :\n";
                code += "    echo '<span>' . esc_html($" + fId + ") . '</span>';\n";
                code += "endif;\n";
                code += "?>";
            } else if (fType === 'wysiwyg') {
                hint = 'WYSIWYG';
                code = "<?php\n";
                code += "// Retrieve '" + escPhp(fLabel) + "' (HTML content)\n";
                code += "$" + fId + " = get_post_meta(get_the_ID(), '" + escPhp(fId) + "', true);\n\n";
                code += "if ($" + fId + ") :\n";
                code += "    echo wp_kses_post($" + fId + ");\n";
                code += "endif;\n";
                code += "?>";
            } else if (fType === 'url' || fType === 'link') {
                hint = 'URL';
                code = "<?php\n";
                code += "// Retrieve '" + escPhp(fLabel) + "' (URL string)\n";
                code += "$" + fId + " = get_post_meta(get_the_ID(), '" + escPhp(fId) + "', true);\n\n";
                code += "if ($" + fId + ") : ?>\n";
                code += "    <a href=\"<?php echo esc_url($" + fId + "); ?>\"><?php echo esc_url($" + fId + "); ?></a>\n";
                code += "<?php endif; ?>";
            } else if (fType === 'email') {
                hint = 'Email';
                code = "<?php\n";
                code += "// Retrieve '" + escPhp(fLabel) + "'\n";
                code += "$" + fId + " = get_post_meta(get_the_ID(), '" + escPhp(fId) + "', true);\n\n";
                code += "if ($" + fId + ") : ?>\n";
                code += "    <a href=\"mailto:<?php echo esc_attr($" + fId + "); ?>\"><?php echo esc_html($" + fId + "); ?></a>\n";
                code += "<?php endif; ?>";
            } else if (fType === 'date') {
                hint = 'Date';
                code = "<?php\n";
                code += "// Retrieve '" + escPhp(fLabel) + "'\n";
                code += "$" + fId + " = get_post_meta(get_the_ID(), '" + escPhp(fId) + "', true);\n\n";
                code += "if ($" + fId + ") :\n";
                code += "    // Format: adjust date_i18n() format as needed\n";
                code += "    echo esc_html(date_i18n('F j, Y', strtotime($" + fId + ")));\n";
                code += "endif;\n";
                code += "?>";
            } else if (fType === 'color') {
                hint = 'Color';
                code = "<?php\n";
                code += "// Retrieve '" + escPhp(fLabel) + "' (hex color)\n";
                code += "$" + fId + " = get_post_meta(get_the_ID(), '" + escPhp(fId) + "', true);\n\n";
                code += "if ($" + fId + ") : ?>\n";
                code += "    <div style=\"background-color: <?php echo esc_attr($" + fId + "); ?>;\">\n";
                code += "        <?php echo esc_html($" + fId + "); ?>\n";
                code += "    </div>\n";
                code += "<?php endif; ?>";
            } else if (fType === 'number' || fType === 'range') {
                hint = fType === 'number' ? 'Number' : 'Range';
                code = "<?php\n";
                code += "// Retrieve '" + escPhp(fLabel) + "'\n";
                code += "$" + fId + " = get_post_meta(get_the_ID(), '" + escPhp(fId) + "', true);\n\n";
                code += "if ($" + fId + " !== '') :\n";
                code += "    echo intval($" + fId + ");\n";
                code += "endif;\n";
                code += "?>";
            } else if (fType === 'post') {
                hint = 'Post Select';
                code = "<?php\n";
                code += "// Retrieve '" + escPhp(fLabel) + "' (post ID)\n";
                code += "$" + fId + " = get_post_meta(get_the_ID(), '" + escPhp(fId) + "', true);\n\n";
                code += "if ($" + fId + ") :\n";
                code += "    $linked_post = get_post($" + fId + ");\n";
                code += "    if ($linked_post) : ?>\n";
                code += "        <a href=\"<?php echo esc_url(get_permalink($linked_post)); ?>\">\n";
                code += "            <?php echo esc_html($linked_post->post_title); ?>\n";
                code += "        </a>\n";
                code += "    <?php endif;\n";
                code += "endif;\n";
                code += "?>";
            } else if (fType === 'taxonomy') {
                hint = 'Taxonomy';
                code = "<?php\n";
                code += "// Retrieve '" + escPhp(fLabel) + "' (term ID)\n";
                code += "$" + fId + " = get_post_meta(get_the_ID(), '" + escPhp(fId) + "', true);\n\n";
                code += "if ($" + fId + ") :\n";
                code += "    $term = get_term($" + fId + ");\n";
                code += "    if ($term && !is_wp_error($term)) :\n";
                code += "        echo esc_html($term->name);\n";
                code += "    endif;\n";
                code += "endif;\n";
                code += "?>";
            } else if (fType === 'user') {
                hint = 'User';
                code = "<?php\n";
                code += "// Retrieve '" + escPhp(fLabel) + "' (user ID)\n";
                code += "$" + fId + " = get_post_meta(get_the_ID(), '" + escPhp(fId) + "', true);\n\n";
                code += "if ($" + fId + ") :\n";
                code += "    $user = get_userdata($" + fId + ");\n";
                code += "    if ($user) :\n";
                code += "        echo esc_html($user->display_name);\n";
                code += "    endif;\n";
                code += "endif;\n";
                code += "?>";
            } else if (fType === 'oembed') {
                hint = 'oEmbed';
                code = "<?php\n";
                code += "// Retrieve '" + escPhp(fLabel) + "' (embed URL)\n";
                code += "$" + fId + " = get_post_meta(get_the_ID(), '" + escPhp(fId) + "', true);\n\n";
                code += "if ($" + fId + ") :\n";
                code += "    echo wp_oembed_get($" + fId + ");\n";
                code += "endif;\n";
                code += "?>";
            } else {
                // Default: text, textarea, password, hidden, etc.
                hint = escHtml(fType.charAt(0).toUpperCase() + fType.slice(1));
                code = "<?php\n";
                code += "// Retrieve '" + escPhp(fLabel) + "'\n";
                code += "$" + fId + " = get_post_meta(get_the_ID(), '" + escPhp(fId) + "', true);\n\n";
                code += "if ($" + fId + ") :\n";
                code += "    echo esc_html($" + fId + ");\n";
                code += "endif;\n";
                code += "?>";
            }

            sections += '<div class="cmb-result-section">';
            sections += '<div class="cmb-result-section-header">';
            sections += '<span class="dashicons dashicons-editor-code"></span> ';
            sections += escHtml(fLabel || fId);
            sections += ' <small>' + hint + '</small>';
            sections += '</div>';
            sections += '<pre class="cmb-code-preview"><code>' + escHtml(code) + '</code></pre>';
            sections += '</div>';
        });

        if (!hasFields) {
            sections = '<div class="cmb-result-section">';
            sections += '<div class="cmb-result-section-header"><span class="dashicons dashicons-info-outline"></span> Save the field group and add fields to generate retrieval code.</div>';
            sections += '</div>';
        }

        $output.html(sections);
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
        let map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(str).replace(/[&<>"']/g, function (m) { return map[m]; });
    }

    function escPhp(str) {
        return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    }

    function markDirty() {
        formDirty = true;
    }

    function toggleEmptyMessage() {
        let $list = $('#cmb-fields-list');
        if ($list.find('.cmb-field-row').length === 0) {
            if (!$list.find('.cmb-no-fields-msg').length) {
                $list.append('<div class="cmb-no-fields-msg"><p>No fields yet. Click the button below to add your first field.</p></div>');
            }
        }
    }

    /* ─── Sub-Field Helpers ─────────────────────────────── */

    function buildSubFieldHtml(prefix, subIndex) {
        let html = '<div class="cmb-sub-field-row open" data-sub-index="' + subIndex + '" data-type="text">';

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
                let sel = (key === 'text') ? ' selected' : '';
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

        // Conditional Logic
        html += '<div class="cmb-conditional-row cmb-sub-conditional">';
        html += '<div class="cmb-conditional-header"><label class="cmb-checkbox-label">';
        html += '<input type="checkbox" class="cmb-conditional-toggle"> Conditional Logic</label></div>';
        html += '<div class="cmb-conditional-settings" style="display:none">';
        html += '<div class="cmb-field-settings-grid">';
        html += '<div class="cmb-fs-row cmb-fs-third"><label>Show when field</label>';
        html += '<input type="text" name="' + prefix + '[conditional_field]" class="widefat" placeholder="field_id"></div>';
        html += '<div class="cmb-fs-row cmb-fs-third"><label>Operator</label>';
        html += '<select name="' + prefix + '[conditional_operator]" class="widefat">';
        html += '<option value="==">equals</option><option value="!=">not equals</option>';
        html += '<option value="!empty">is not empty</option><option value="empty">is empty</option>';
        html += '<option value="contains">contains</option></select></div>';
        html += '<div class="cmb-fs-row cmb-fs-third"><label>Value</label>';
        html += '<input type="text" name="' + prefix + '[conditional_value]" class="widefat" placeholder="value"></div>';
        html += '</div></div></div>';

        // Bottom row: Required, Width, Layout
        html += '<div class="cmb-sub-field-bottom">';
        html += '<label class="cmb-checkbox-label">';
        html += '<input type="checkbox" name="' + prefix + '[required]" value="1"> Required</label>';
        html += '<div class="cmb-width-control"><label>Width</label>';
        html += '<select name="' + prefix + '[width]">';
        html += '<option value="">Auto</option><option value="100">100%</option><option value="75">75%</option>';
        html += '<option value="50">50%</option><option value="33">33%</option><option value="25">25%</option>';
        html += '</select></div>';
        html += '<div class="cmb-layout-control"><label>Layout</label>';
        html += '<select name="' + prefix + '[layout]">';
        html += '<option value="">Horizontal</option><option value="inline">Stacked</option>';
        html += '</select></div>';
        html += '</div>';

        // Nested sub-fields area (for group type — hidden by default)
        html += '<div class="cmb-type-opt cmb-sub-fields-wrap cmb-nested-sub-fields" data-show-for="group" style="display:none">';
        html += '<div class="cmb-sub-fields-header"><label>Sub-Fields</label></div>';
        html += '<div class="cmb-sub-fields-list" data-parent-prefix="' + escHtml(prefix) + '"></div>';
        html += '<button type="button" class="button cmb-add-sub-field" data-parent-prefix="' + escHtml(prefix) + '">';
        html += '<span class="dashicons dashicons-plus-alt2"></span> Add Sub-Field</button>';
        html += '</div>';

        html += '</div>'; // .cmb-sub-field-body
        html += '</div>'; // .cmb-sub-field-row

        return html;
    }

    function reindexSubFields($list) {
        let parentPrefix = $list.data('parent-prefix') || '';
        let parentIndex = $list.data('parent-index');

        $list.children('.cmb-sub-field-row').each(function (newIndex) {
            $(this).attr('data-sub-index', newIndex);
            // Build the new prefix for this level
            let newPrefix;
            if (parentPrefix) {
                newPrefix = parentPrefix + '[sub_fields][' + newIndex + ']';
            } else {
                newPrefix = 'cmb_fields[' + parentIndex + '][sub_fields][' + newIndex + ']';
            }
            // Only update direct inputs of this sub-field row (not nested ones)
            $(this).find('> .cmb-sub-field-body [name]').not($(this).find('> .cmb-sub-field-body .cmb-sub-field-row [name]')).each(function () {
                let name = $(this).attr('name');
                if (name) {
                    // Replace the last sub_fields[N] segment with the new index
                    let lastBracket = name.lastIndexOf('[sub_fields]');
                    if (lastBracket === -1) return;
                    let afterSubFields = name.substring(lastBracket);
                    let beforeSubFields = name.substring(0, lastBracket);
                    afterSubFields = afterSubFields.replace(/\[sub_fields\]\[\d+\]/, '[sub_fields][' + newIndex + ']');
                    $(this).attr('name', beforeSubFields + afterSubFields);
                }
            });
            // Update nested add-sub-field buttons and lists with new prefix
            $(this).find('> .cmb-sub-field-body > .cmb-nested-sub-fields').each(function () {
                $(this).find('> .cmb-sub-fields-list').data('parent-prefix', newPrefix);
                $(this).find('> .cmb-add-sub-field').data('parent-prefix', newPrefix);
            });
        });
    }

    function updateSubFieldTypeOptions($row) {
        let type = $row.attr('data-type') || $row.find('> .cmb-sub-field-body .cmb-sub-field-type-select').first().val() || 'text';
        // Show/hide type-specific option panels (only direct children, not nested)
        $row.find('> .cmb-sub-field-body > .cmb-sub-type-opt, > .cmb-sub-field-body > .cmb-field-settings-grid.cmb-sub-type-opt').each(function () {
            let showFor = ($(this).data('show-for') || '').split(',');
            $(this).toggleClass('visible', showFor.indexOf(type) !== -1);
        });
        // Show/hide nested sub-fields area for group type
        $row.find('> .cmb-sub-field-body > .cmb-nested-sub-fields').each(function () {
            if (type === 'group') {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

})(jQuery);
