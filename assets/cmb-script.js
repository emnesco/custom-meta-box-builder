(function($) {
    'use strict';

    $(document).ready(function() {

      // === Add Row ===
      $(document).on('click', '.cmb-repeat > .cmb-input > .cmb-add-row', function(event) {
        event.preventDefault();

        const $addRowButton = $(this);
        const $repeatedField = $addRowButton.closest('.cmb-input');

        // Check for flat repeatable rows container
        const $repeatRows = $repeatedField.find('.cmb-repeat-rows').first();
        const $groupItemsContainer = $repeatedField.find('.cmb-group-items').first();

        if ($repeatRows.length) {
          // Flat repeatable field — clone last .cmb-repeat-row
          const $rows = $repeatRows.children('.cmb-repeat-row');
          const maxRows = $addRowButton.data('max-rows');
          if (maxRows && $rows.length >= maxRows) {
            return;
          }

          const $lastRow = $rows.last();
          const $clone = $lastRow.clone(false, false);

          // Clear values in cloned row
          $clone.find(':input').each(function() {
            $(this).val('');
          });
          // Update html id to avoid duplicates
          $clone.find('[id]').each(function() {
            const oldId = $(this).attr('id');
            $(this).attr('id', oldId.replace(/-\d+$/, '') + '-' + $rows.length);
          });

          $clone.hide().appendTo($repeatRows).slideDown(200);

          // Re-initialize color pickers in cloned row
          if ($.fn.wpColorPicker && $clone.find('.cmb-color-picker').length) {
            $clone.find('.cmb-color-picker').wpColorPicker();
          }

          updateRowCounts();
          return;
        }

        if ($groupItemsContainer.length === 0) {
          return;
        }

        // Group repeatable field
        const $groupItems = $groupItemsContainer.children('.cmb-group-item');
        const currentItemCount = $groupItems.length;
        const maxRows = $addRowButton.data('max-rows');

        if (maxRows && currentItemCount >= maxRows) {
          return;
        }

        const $parentGroups = $repeatedField.parents('.cmb-group');
        const nestingLevel = $parentGroups.length;

        // Remove empty state message if present
        $groupItemsContainer.find('.cmb-empty-state').remove();

        const $clone = $groupItems.last().clone(false, false);
        processNestedGroups($clone, nestingLevel, currentItemCount);
        $clone.hide().appendTo($groupItemsContainer).slideDown(200);

        // Re-initialize sortable on nested groups within the new row
        if ($.fn.sortable) {
          $clone.find('.cmb-group-items').sortable({
            handle: '.cmb-sortable-handle, .cmb-group-item-header',
            items: '> .cmb-group-item',
            placeholder: 'cmb-sortable-placeholder',
            tolerance: 'pointer'
          });
        }

        // Re-initialize color pickers in cloned row
        if ($.fn.wpColorPicker && $clone.find('.cmb-color-picker').length) {
          $clone.find('.cmb-color-picker').wpColorPicker();
        }

        updateRowCounts();
      });

      // === Remove flat repeat row ===
      $(document).on('click', '.cmb-repeat-row-remove', function(event) {
        event.preventDefault();
        const $row = $(this).closest('.cmb-repeat-row');
        const $container = $row.parent('.cmb-repeat-rows');
        const $addRow = $container.closest('.cmb-input').find('.cmb-add-row').first();
        const minRows = $addRow.data('min-rows') || 1;

        if ($container.children('.cmb-repeat-row').length <= minRows) {
          return;
        }

        $row.slideUp(200, function() {
          $(this).remove();
          updateRowCounts();
        });
      });

      function processNestedGroups($clone, nestingLevel, parentItemIndex) {
        $clone.find(':input').each(function() {
          const $input = $(this);
          let name = $input.attr('name');

          if (name) {
            const newName = updateNestedGroupName(name, nestingLevel, parentItemIndex);
            $input.attr('name', newName).val('');
          } else {
            $input.val('');
          }
        });

        const $indexElement = $clone.find('.cmb-group-index').first();
        if ($indexElement.length) {
          $indexElement.text(parentItemIndex);
        }

        $clone.find('.cmb-group').each(function() {
          const $nestedGroup = $(this);
          const $nestedGroupItemsContainer = $nestedGroup.find('.cmb-group-items').first();
          $nestedGroupItemsContainer.children('.cmb-group-item:not(:first)').remove();
          const $nestedGroupItem = $nestedGroupItemsContainer.children('.cmb-group-item').first();

          const getNestingLevel = $nestedGroup.parents('.cmb-group').length;

          if ($nestedGroupItem.length) {
            processNestedGroups($nestedGroupItem, getNestingLevel, parentItemIndex);
          }
        });

        // Update aria-expanded on header
        const $header = $clone.children('.cmb-group-item-header');
        $header.attr('aria-expanded', $clone.hasClass('open') ? 'true' : 'false');

        return $clone;
      }

      function updateNestedGroupName(inputName, nestingLevel, currentItemIndex) {
        const regex = /\[(\d+)\]/g;
        const matches = [];
        let match;

        while ((match = regex.exec(inputName)) !== null) {
            matches.push({
                start: match.index,
                end: match.index + match[0].length,
                fullMatch: match[0]
            });
        }

        if (nestingLevel >= matches.length || nestingLevel < 0) {
            return inputName;
        }

        const target = matches[nestingLevel];
        const newSegment = '[' + currentItemIndex + ']';

        return inputName.slice(0, target.start) +
               newSegment +
               inputName.slice(target.end);
      }

      // === Remove Row (with confirmation and min_rows enforcement) ===
      $(document).on('click', '.cmb-remove-row', function(event) {
        event.preventDefault();

        if (!confirm('Remove this item?')) {
          return;
        }

        const $removeRowButton = $(this);
        const $groupItem = $removeRowButton.closest('.cmb-group-item');
        const $container = $groupItem.parent('.cmb-group-items');

        // Enforce min_rows
        const $addRow = $container.closest('.cmb-input').find('.cmb-add-row').first();
        const minRows = $addRow.data('min-rows') || 0;
        if ($container.children('.cmb-group-item').length <= minRows) {
          return;
        }

        $groupItem.slideUp(200, function() {
          $(this).remove();
          updateRowCounts();

          // Show empty state if no items remain
          if ($container.children('.cmb-group-item').length === 0) {
            $container.append('<div class="cmb-empty-state">No items yet. Click "Add Row" to add one.</div>');
          }
        });
      });

      // === Toggle group item (click + keyboard) ===
      $(document).on('click keydown', '.cmb-group-item-header', function(event) {
        if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
          return;
        }
        event.preventDefault();
        const $header = $(this);
        const $toggleElement = $header.parent('.cmb-group-item');

        $toggleElement.toggleClass('open');
        $header.attr('aria-expanded', $toggleElement.hasClass('open') ? 'true' : 'false');
      });

      // === Expand All / Collapse All ===
      $(document).on('click', '.cmb-expand-all', function(event) {
        event.preventDefault();
        const $group = $(this).closest('.cmb-input').find('.cmb-group').first();
        $group.find('.cmb-group-item').addClass('open')
              .children('.cmb-group-item-header').attr('aria-expanded', 'true');
      });

      $(document).on('click', '.cmb-collapse-all', function(event) {
        event.preventDefault();
        const $group = $(this).closest('.cmb-input').find('.cmb-group').first();
        $group.find('.cmb-group-item').removeClass('open')
              .children('.cmb-group-item-header').attr('aria-expanded', 'false');
      });

      // === File Upload (WP Media Library) ===
      $(document).on('click', '.cmb-file-upload', function(event) {
        event.preventDefault();
        var $button = $(this);
        var $target = $($button.data('target'));
        var $wrapper = $button.closest('.cmb-file-field');

        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
          alert('Media library is not available. Please ensure it is loaded on this page.');
          return;
        }

        var frame = wp.media({
          title: 'Select File',
          multiple: false
        });

        frame.on('select', function() {
          var attachment = frame.state().get('selection').first().toJSON();
          $target.val(attachment.id);
          var $preview = $wrapper.find('.cmb-file-preview');
          $preview.empty();
          if (attachment.type === 'image' && attachment.sizes && attachment.sizes.thumbnail) {
            $preview.empty().append(
              $('<img>').attr('src', attachment.sizes.thumbnail.url).css({maxWidth:'150px',maxHeight:'150px'})
            );
          } else {
            $preview.empty().append(
              $('<a>').attr({href: attachment.url, target: '_blank'}).text(attachment.filename)
            );
          }
          $wrapper.find('.cmb-file-remove').show();
        });

        frame.open();
      });

      $(document).on('click', '.cmb-file-remove', function(event) {
        event.preventDefault();
        var $button = $(this);
        var $target = $($button.data('target'));
        var $wrapper = $button.closest('.cmb-file-field');
        $target.val('0');
        $wrapper.find('.cmb-file-preview').empty();
        $button.hide();
      });

      // === Sortable flat repeat rows ===
      if ($.fn.sortable) {
        $('.cmb-repeat-rows').sortable({
          handle: '.cmb-repeat-row-handle',
          items: '> .cmb-repeat-row',
          placeholder: 'cmb-sortable-placeholder',
          tolerance: 'pointer'
        });
      }

      // === Sortable Repeater Rows (6.1) ===
      if ($.fn.sortable) {
        $('.cmb-group-items').sortable({
          handle: '.cmb-sortable-handle, .cmb-group-item-header',
          items: '> .cmb-group-item',
          placeholder: 'cmb-sortable-placeholder',
          tolerance: 'pointer',
          start: function(e, ui) {
            ui.item.data('cmb-start-index', ui.item.index());
          },
          update: function(e, ui) {
            var $container = $(this);
            var oldIndex = ui.item.data('cmb-start-index');
            var newIndex = ui.item.index();
            var start = Math.min(oldIndex, newIndex);
            var end = Math.max(oldIndex, newIndex);
            var parentLevel = $container.parents('.cmb-group').length;

            $container.children('.cmb-group-item').slice(start, end + 1).each(function() {
              var idx = $(this).index();
              $(this).find('.cmb-group-index').first().text(idx);
              $(this).find(':input').each(function() {
                var name = $(this).attr('name');
                if (!name) return;
                var updated = updateNestedGroupName(name, parentLevel, idx);
                $(this).attr('name', updated);
              });
            });
            updateRowCounts();
          }
        });
      }

      // === Row Title from Field Value (6.2) ===
      $(document).on('input change', '.cmb-group-item :input', function() {
        var $input = $(this);
        var $groupItem = $input.closest('.cmb-group-item');
        var $group = $groupItem.closest('.cmb-group');
        var rowTitleField = $group.data('row-title-field');
        if (!rowTitleField) return;

        var fieldId = $input.attr('name');
        if (!fieldId) return;
        // Check if the input corresponds to the row title field
        if (fieldId.indexOf('[' + rowTitleField + ']') === -1 && fieldId !== rowTitleField) return;

        var val = $input.val();
        var $title = $groupItem.find('.cmb-group-item-title').first();
        if ($title.length && val) {
          $title.text(val);
        }
      });

      // === Conditional Field Display (6.3) ===
      function evaluateCondition(rule, $container) {
        var condField = rule.field || '';
        var condOperator = rule.operator || '==';
        var condValue = String(rule.value || '');

        var $source = $container.find('[name="' + condField + '"], [name$="[' + condField + ']"]').first();
        if (!$source.length) return false;

        var sourceVal = $source.is(':checkbox') ? ($source.is(':checked') ? $source.val() : '') : $source.val();
        sourceVal = String(sourceVal || '');

        switch (condOperator) {
          case '==': return sourceVal === condValue;
          case '!=': return sourceVal !== condValue;
          case 'contains': return sourceVal.indexOf(condValue) !== -1;
          case '!empty': return sourceVal !== '';
          case 'empty': return sourceVal === '';
          default: return sourceVal === condValue;
        }
      }

      // Cache DOM selectors for conditional fields to avoid re-querying
      var $conditionalFields = null;
      var $conditionalGroupFields = null;

      function getCachedConditionalFields() {
        if (!$conditionalFields) {
          $conditionalFields = $('[data-conditional-field]');
        }
        return $conditionalFields;
      }

      function getCachedConditionalGroupFields() {
        if (!$conditionalGroupFields) {
          $conditionalGroupFields = $('[data-conditional-groups]');
        }
        return $conditionalGroupFields;
      }

      function invalidateConditionalCache() {
        $conditionalFields = null;
        $conditionalGroupFields = null;
      }

      function evaluateConditionals() {
        // Simple single-condition format
        getCachedConditionalFields().each(function() {
          var $field = $(this);
          var $container = $field.closest('.cmb-container, .cmb-tab-panel, .form-table');
          var show = evaluateCondition({
            field: $field.data('conditional-field'),
            operator: $field.data('conditional-operator'),
            value: $field.data('conditional-value')
          }, $container);

          // FE-L03: Use CSS class for animated show/hide instead of jQuery slideDown/slideUp
          if (show) {
            $field.removeClass('cmb-field-hidden');
          } else {
            $field.addClass('cmb-field-hidden');
          }
        });

        // AND/OR group format
        getCachedConditionalGroupFields().each(function() {
          var $field = $(this);
          var groups = $field.data('conditional-groups');
          var relation = ($field.data('conditional-relation') || 'OR').toUpperCase();
          var $container = $field.closest('.cmb-container, .cmb-tab-panel, .form-table');

          if (!Array.isArray(groups) || !groups.length) return;

          var show = false;
          if (relation === 'AND') {
            show = groups.every(function(group) {
              if (!Array.isArray(group.rules)) return true;
              return group.rules.every(function(rule) {
                return evaluateCondition(rule, $container);
              });
            });
          } else {
            // OR — any group fully matching is enough
            show = groups.some(function(group) {
              if (!Array.isArray(group.rules)) return false;
              return group.rules.every(function(rule) {
                return evaluateCondition(rule, $container);
              });
            });
          }

          // FE-L03: Use CSS class for animated show/hide
          if (show) {
            $field.removeClass('cmb-field-hidden');
          } else {
            $field.addClass('cmb-field-hidden');
          }
        });
      }

      // Run on load and on input change (debounced)
      evaluateConditionals();
      var conditionalTimer;
      $(document).on('input change', '.cmb-container :input, .cmb-tab-panel :input', function() {
        clearTimeout(conditionalTimer);
        conditionalTimer = setTimeout(evaluateConditionals, 250);
      });

      // === Tab Switching (6.4) ===
      function activateTab($link) {
        var $nav = $link.closest('.cmb-tab-nav');
        var $tabs = $nav.closest('.cmb-tabs');
        var tabId = $link.attr('href');

        $nav.find('[role="tab"]').attr({'aria-selected': 'false', 'tabindex': '-1'});
        $link.attr({'aria-selected': 'true', 'tabindex': '0'}).focus();

        $nav.find('.cmb-tab-nav-item').removeClass('cmb-tab-active');
        $link.closest('.cmb-tab-nav-item').addClass('cmb-tab-active');

        $tabs.find('.cmb-tab-panel').removeClass('cmb-tab-panel-active').attr('hidden', '');
        $tabs.find(tabId).addClass('cmb-tab-panel-active').removeAttr('hidden');
      }

      $(document).on('click', '.cmb-tab-nav-item a[role="tab"]', function(event) {
        event.preventDefault();
        activateTab($(this));
      });

      // Arrow key navigation for tabs
      $(document).on('keydown', '[role="tab"]', function(e) {
        var $tabs = $(this).closest('[role="tablist"]').find('[role="tab"]');
        var idx = $tabs.index(this);
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
          e.preventDefault();
          activateTab($tabs.eq((idx + 1) % $tabs.length));
        } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
          e.preventDefault();
          activateTab($tabs.eq((idx - 1 + $tabs.length) % $tabs.length));
        } else if (e.key === 'Home') {
          e.preventDefault();
          activateTab($tabs.first());
        } else if (e.key === 'End') {
          e.preventDefault();
          activateTab($tabs.last());
        }
      });

      // === Duplicate Row (6.9) ===
      $(document).on('click', '.cmb-duplicate-row', function(event) {
        event.preventDefault();
        var $button = $(this);
        var $groupItem = $button.closest('.cmb-group-item');
        var $container = $groupItem.parent('.cmb-group-items');
        var $addRow = $container.closest('.cmb-input').find('.cmb-add-row').first();

        // Enforce max_rows
        var maxRows = $addRow.data('max-rows');
        if (maxRows && $container.children('.cmb-group-item').length >= maxRows) {
          return;
        }

        var $clone = $groupItem.clone(false, false);
        var newIndex = $container.children('.cmb-group-item').length;
        var nestingLevel = $container.parents('.cmb-group').length;

        // Update indices in cloned inputs
        $clone.find(':input').each(function() {
          var name = $(this).attr('name');
          if (name) {
            var updated = updateNestedGroupName(name, nestingLevel, newIndex);
            $(this).attr('name', updated);
          }
        });

        var $indexEl = $clone.find('.cmb-group-index').first();
        if ($indexEl.length) {
          $indexEl.text(newIndex);
        }

        $clone.hide().insertAfter($groupItem).slideDown(200);
        updateRowCounts();
      });

      // === Unsaved Changes Warning (6.10) ===
      var cmbFormDirty = false;
      $(document).on('input change', '.cmb-container :input', function() {
        cmbFormDirty = true;
      });
      $(document).on('submit', 'form', function() {
        cmbFormDirty = false;
      });
      $(window).on('beforeunload', function() {
        if (cmbFormDirty) {
          return 'You have unsaved changes. Are you sure you want to leave?';
        }
      });

      // === Multi-language Tab Switching (8.3) ===
      $(document).on('click', '.cmb-lang-tab-item a', function(event) {
        event.preventDefault();
        var $link = $(this);
        var $tabNav = $link.closest('.cmb-lang-tab-nav');
        var $langTabs = $link.closest('.cmb-lang-tabs');
        var lang = $link.closest('.cmb-lang-tab-item').data('lang');

        $tabNav.find('.cmb-lang-tab-item').removeClass('cmb-lang-tab-active');
        $link.closest('.cmb-lang-tab-item').addClass('cmb-lang-tab-active');

        $langTabs.find('.cmb-lang-panel').removeClass('cmb-lang-panel-active');
        $langTabs.find('.cmb-lang-panel[data-lang="' + lang + '"]').addClass('cmb-lang-panel-active');
      });

      // === Search/Filter for Repeater Groups (8.4) ===
      $(document).on('input', '.cmb-group-search input', function() {
        var query = $(this).val().toLowerCase();
        var $container = $(this).closest('.cmb-input').find('.cmb-group-items').first();

        $container.children('.cmb-group-item').each(function() {
          var $item = $(this);
          var text = $item.text().toLowerCase();
          if (query === '' || text.indexOf(query) !== -1) {
            $item.removeClass('cmb-search-hidden');
          } else {
            $item.addClass('cmb-search-hidden');
          }
        });
      });

      // === Lazy Loading / Virtual Scrolling for Large Repeaters (8.5) ===
      var CMB_LAZY_THRESHOLD = 20;
      $('.cmb-group-items').each(function() {
        var $container = $(this);
        var $items = $container.children('.cmb-group-item');

        if ($items.length > CMB_LAZY_THRESHOLD) {
          $items.slice(CMB_LAZY_THRESHOLD).hide().addClass('cmb-lazy-hidden');
          $container.after('<button type="button" class="cmb-load-more">Load more (' + ($items.length - CMB_LAZY_THRESHOLD) + ' remaining)</button>');
        }
      });

      $(document).on('click', '.cmb-load-more', function() {
        var $btn = $(this);
        var $container = $btn.prev('.cmb-group-items');
        if (!$container.length) {
          $container = $btn.closest('.cmb-input').find('.cmb-group-items').first();
        }
        var $hidden = $container.children('.cmb-lazy-hidden');
        var batch = $hidden.slice(0, CMB_LAZY_THRESHOLD);
        batch.removeClass('cmb-lazy-hidden').slideDown(200);

        var remaining = $hidden.length - batch.length;
        if (remaining > 0) {
          $btn.text('Load more (' + remaining + ' remaining)');
        } else {
          $btn.remove();
        }
      });

      // === Helper: update item count indicators ===
      function updateRowCounts() {
        $('.cmb-item-count').each(function() {
          const $counter = $(this);
          const $input = $counter.closest('.cmb-input');
          const $groupContainer = $input.find('.cmb-group-items').first();
          const $repeatContainer = $input.find('.cmb-repeat-rows').first();
          let count = 0;

          if ($groupContainer.length) {
            count = $groupContainer.children('.cmb-group-item').length;
          } else if ($repeatContainer.length) {
            count = $repeatContainer.children('.cmb-repeat-row').length;
          }

          if (count > 0) {
            $counter.text(count + (count === 1 ? ' item' : ' items'));
          }
        });
      }
      // Run on page load
      updateRowCounts();

      // === Client-Side Validation ===
      function validateField($field) {
        var $input = $field.find(':input:not([type="hidden"])').first();
        if (!$input.length) return true;
        var val = $input.val();
        var errors = [];

        if ($field.data('validate-required') && (!val || val === '')) {
          errors.push('This field is required.');
        }
        if ($field.data('validate-min') && val && val.length < parseInt($field.data('validate-min'))) {
          errors.push('Must be at least ' + $field.data('validate-min') + ' characters.');
        }
        if ($field.data('validate-max') && val && val.length > parseInt($field.data('validate-max'))) {
          errors.push('Must be no more than ' + $field.data('validate-max') + ' characters.');
        }
        if ($field.data('validate-numeric') && val && isNaN(val)) {
          errors.push('Must be a number.');
        }

        $field.find('.cmb-field-error').remove();
        $input.removeAttr('aria-invalid aria-describedby');
        if (errors.length) {
          $field.addClass('cmb-has-error');
          var errorId = 'cmb-error-' + ($input.attr('name') || '').replace(/[\[\]]/g, '-') + '-' + Date.now();
          var $error = $('<p>').addClass('cmb-field-error').attr('id', errorId).text(errors[0]);
          $field.find('.cmb-input').append($error);
          $input.attr('aria-invalid', 'true').attr('aria-describedby', errorId);
          return false;
        }
        $field.removeClass('cmb-has-error');
        return true;
      }

      $(document).on('blur', '.cmb-field[data-validate-required] :input, .cmb-field[data-validate-min] :input, .cmb-field[data-validate-max] :input', function() {
        validateField($(this).closest('.cmb-field'));
      });

      // Disable native HTML5 validation on inputs inside collapsed/hidden group
      // bodies to prevent "invalid form control is not focusable" errors.
      $(document).on('submit', 'form#post', function(e) {
        $(this).find('.cmb-group-item-body').each(function() {
          if ($(this).css('display') === 'none') {
            $(this).find(':input[required], :input[pattern]').each(function() {
              var $input = $(this);
              if ($input.attr('required')) {
                $input.data('cmb-was-required', true).removeAttr('required');
              }
              if ($input.attr('pattern')) {
                $input.data('cmb-was-pattern', $input.attr('pattern')).removeAttr('pattern');
              }
            });
          }
        });

        // Also handle conditional-hidden fields
        $(this).find('.cmb-field-hidden').find(':input[required], :input[pattern]').each(function() {
          var $input = $(this);
          if ($input.attr('required')) {
            $input.data('cmb-was-required', true).removeAttr('required');
          }
          if ($input.attr('pattern')) {
            $input.data('cmb-was-pattern', $input.attr('pattern')).removeAttr('pattern');
          }
        });

        // Run custom validation
        var hasErrors = false;
        $(this).find('.cmb-field[data-validate-required], .cmb-field[data-validate-min], .cmb-field[data-validate-max]').each(function() {
          if (!validateField($(this))) hasErrors = true;
        });
        if (hasErrors) {
          e.preventDefault();
          // Restore validation attrs
          $(this).find(':input').each(function() {
            var $input = $(this);
            if ($input.data('cmb-was-required')) {
              $input.attr('required', '').removeData('cmb-was-required');
            }
            if ($input.data('cmb-was-pattern')) {
              $input.attr('pattern', $input.data('cmb-was-pattern')).removeData('cmb-was-pattern');
            }
          });
          $('html, body').animate({ scrollTop: $('.cmb-has-error').first().offset().top - 50 }, 300);
        }
      });

      // === Flexible Content Field ===

      $(document).on('click', '.cmb-flexible-add-btn', function() {
        var $picker = $(this).siblings('.cmb-flexible-layout-picker');
        var isOpening = !$picker.is(':visible');
        $picker.toggle();

        // FE-M03: Focus trap on open
        if (isOpening) {
          $(this).data('cmb-trigger', true);
          var $first = $picker.find('.cmb-flexible-layout-option').first();
          if ($first.length) $first.focus();
        }
      });

      $(document).on('click', '.cmb-flexible-layout-option', function() {
        const $btn = $(this);
        const layout = $btn.data('layout');
        const $container = $btn.closest('.cmb-flexible-content');
        const $items = $container.find('.cmb-flexible-items').first();
        // FE-H02: Use native <template> element
        const tpl = $container.find('template.cmb-flexible-template[data-layout="' + layout + '"]')[0];

        if (!tpl) return;

        // FE-H02: Clone from <template> element instead of innerHTML
        const newIndex = $items.children('.cmb-flexible-item').length;
        const clone = tpl.content.cloneNode(true);
        // Update {{INDEX}} placeholders in the cloned DOM
        const wrapper = document.createElement('div');
        wrapper.appendChild(clone);
        wrapper.innerHTML = wrapper.innerHTML.replace(/\{\{INDEX\}\}/g, newIndex);
        const $row = $(wrapper).children();
        $row.hide().appendTo($items).slideDown(200);

        // Re-init color pickers
        if ($.fn.wpColorPicker && $row.find('.cmb-color-picker').length) {
          $row.find('.cmb-color-picker').wpColorPicker();
        }

        // Hide picker and restore focus to trigger
        var $picker = $btn.closest('.cmb-flexible-layout-picker');
        $picker.hide();
        var $trigger = $picker.siblings('.cmb-flexible-add-btn');
        $trigger.focus();
        updateRowCounts();
      });

      // Sortable for flexible content items
      if ($.fn.sortable) {
        $('.cmb-flexible-items').sortable({
          handle: '.cmb-sortable-handle',
          items: '> .cmb-flexible-item',
          placeholder: 'cmb-sortable-placeholder',
          tolerance: 'pointer',
          update: function() {
            const $container = $(this);
            $container.children('.cmb-flexible-item').each(function(idx) {
              $(this).find('.cmb-group-index').first().text(idx);
            });
          }
        });
      }

      // === Color Picker Initialization ===
      if ($.fn.wpColorPicker) {
        $('.cmb-color-picker').each(function() {
          var opts = {};
          if ($(this).data('alpha-enabled')) {
            opts.palettes = true;
          }
          $(this).wpColorPicker(opts);
        });
      }

      // === FE-C02: Range field output (replaces inline oninput) ===
      document.addEventListener('input', function(e) {
        if (e.target.matches('input[data-cmb-range-output]')) {
          var output = e.target.nextElementSibling;
          if (output) output.textContent = e.target.value;
        }
      });

      // === FE-H04: Keyboard navigation for group row reorder ===
      $(document).on('click keydown', '.cmb-group-move-up, .cmb-group-move-down', function(e) {
        if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
        e.preventDefault();
        var $btn = $(this);
        var $item = $btn.closest('.cmb-group-item');
        var $container = $item.parent('.cmb-group-items');

        if ($btn.hasClass('cmb-group-move-up') && $item.prev('.cmb-group-item').length) {
          $item.insertBefore($item.prev('.cmb-group-item'));
        } else if ($btn.hasClass('cmb-group-move-down') && $item.next('.cmb-group-item').length) {
          $item.insertAfter($item.next('.cmb-group-item'));
        }

        // Re-index after move
        $container.children('.cmb-group-item').each(function(idx) {
          $(this).find('.cmb-group-index').first().text(idx);
        });
        updateRowCounts();
        $btn.focus();
      });

      // === FE-H06: Escape key to close layout picker modal ===
      // FE-M03: Restore focus to trigger on close
      $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
          var $visiblePicker = $('.cmb-flexible-layout-picker:visible');
          if ($visiblePicker.length) {
            $visiblePicker.hide();
            var $trigger = $visiblePicker.siblings('.cmb-flexible-add-btn');
            if ($trigger.length) $trigger.focus();
          }
        }
      });

      // FE-M03: Focus trap inside layout picker
      $(document).on('keydown', '.cmb-flexible-layout-picker', function(e) {
        if (e.key !== 'Tab') return;
        var $picker = $(this);
        var $focusable = $picker.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (!$focusable.length) return;
        var $first = $focusable.first();
        var $last = $focusable.last();
        if (e.shiftKey) {
          if ($(document.activeElement).is($first)) {
            e.preventDefault();
            $last.focus();
          }
        } else {
          if ($(document.activeElement).is($last)) {
            e.preventDefault();
            $first.focus();
          }
        }
      });

      // === FE-H07: ARIA on FlexibleContent layout picker ===
      $('.cmb-flexible-layout-picker').attr('role', 'listbox');
      $('.cmb-flexible-layout-option').attr('role', 'option');

      // Arrow key navigation for layout picker
      $(document).on('keydown', '.cmb-flexible-layout-option', function(e) {
        var $options = $(this).closest('.cmb-flexible-layout-picker').find('.cmb-flexible-layout-option');
        var idx = $options.index(this);
        if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
          e.preventDefault();
          $options.eq((idx + 1) % $options.length).focus();
        } else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
          e.preventDefault();
          $options.eq((idx - 1 + $options.length) % $options.length).focus();
        }
      });

      // Invalidate conditional cache when rows are added/removed
      $(document).on('click', '.cmb-add-row, .cmb-remove-row', function() {
        invalidateConditionalCache();
      });

      // === Button Group Field ===
      $(document).on('click', '.cmb-button-group-btn', function() {
        const $btn = $(this);
        const $container = $btn.closest('.cmb-button-group-field');
        const $hidden = $container.find('.cmb-button-group-value');
        const val = $btn.data('value');

        $container.find('.cmb-button-group-btn').removeClass('active').attr('aria-pressed', 'false');
        $btn.addClass('active').attr('aria-pressed', 'true');
        $hidden.val(val).trigger('change');
      });

      // === FE-C02: Range Field Output (replaces inline oninput) ===
      document.addEventListener('input', function(e) {
        if (e.target.matches && e.target.matches('input[data-cmb-range-output]')) {
          var output = e.target.nextElementSibling;
          if (output) output.textContent = e.target.value;
        }
      });

      // === FE-C04: Delegated confirm handler for [data-confirm] links ===
      document.addEventListener('click', function(e) {
        var el = e.target.closest('[data-confirm]');
        if (el && !confirm(el.dataset.confirm)) {
          e.preventDefault();
        }
      });

      // === FE-M04: Gallery image move-up / move-down ===
      function updateGalleryIds($gallery) {
        var ids = [];
        $gallery.find('.cmb-gallery-thumb').each(function() {
          ids.push($(this).data('id'));
        });
        $gallery.closest('.cmb-gallery-field').find('.cmb-gallery-ids').val(ids.join(','));
      }

      $(document).on('click', '.cmb-gallery-move-up', function(e) {
        e.preventDefault();
        var $thumb = $(this).closest('.cmb-gallery-thumb');
        var $prev = $thumb.prev('.cmb-gallery-thumb');
        if ($prev.length) {
          $thumb.insertBefore($prev);
          updateGalleryIds($thumb.closest('.cmb-gallery-preview'));
        }
        $(this).focus();
      });

      $(document).on('click', '.cmb-gallery-move-down', function(e) {
        e.preventDefault();
        var $thumb = $(this).closest('.cmb-gallery-thumb');
        var $next = $thumb.next('.cmb-gallery-thumb');
        if ($next.length) {
          $thumb.insertAfter($next);
          updateGalleryIds($thumb.closest('.cmb-gallery-preview'));
        }
        $(this).focus();
      });

    });

  })(jQuery);
