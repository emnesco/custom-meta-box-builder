(function($) {
    'use strict';

    $(document).ready(function() {

      // === Add Row ===
      $(document).on('click', '.cmb-repeat > .cmb-input > .cmb-add-row', function(event) {
        event.preventDefault();

        const $addRowButton = $(this);

        // Enforce max_rows
        const maxRows = $addRowButton.data('max-rows');
        const $repeatedField = $addRowButton.closest('.cmb-input');
        const $groupItemsContainer = $repeatedField.find('.cmb-group-items').first();
        const $groupItems = $groupItemsContainer.children('.cmb-group-item');
        const currentItemCount = $groupItems.length;

        if (maxRows && currentItemCount >= maxRows) {
          return;
        }

        const $parentGroups = $repeatedField.parents('.cmb-group');
        const nestingLevel = $parentGroups.length;

        let $clone;

        if ($groupItemsContainer.length === 0) {
          // Flat repeatable field (no group container)
          const $inputs = $repeatedField.find(':input');
          const inputCount = $inputs.length;
          const $lastInput = $inputs.last();
          $clone = $lastInput.clone(false, false);

          $clone = processField($clone, inputCount);
          $clone.hide().insertAfter($lastInput).fadeIn(200);
        } else {
          // Remove empty state message if present
          $groupItemsContainer.find('.cmb-empty-state').remove();

          $clone = $groupItems.last().clone(false, false);
          $clone = processNestedGroups($clone, nestingLevel, currentItemCount);
          $clone.hide().appendTo($groupItemsContainer).slideDown(200);
        }

        updateRowCounts();
      });

      function processField($clone, currentItemCount) {
        let name = $clone.attr('name');
        if (name) {
          const bracketMatch = name.match(/^(.+)\[(\d+)\]$/);
          if (bracketMatch) {
            $clone.attr('name', bracketMatch[1] + '[' + currentItemCount + ']');
          }
        }
        $clone.val('');
        return $clone;
      }

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
      $(document).on('click', '.cmb-group > .cmb-group-items > .cmb-group-item > .cmb-group-item-body > .cmb-remove-row', function(event) {
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
      $(document).on('click keydown', '.cmb-group > .cmb-group-items > .cmb-group-item > .cmb-group-item-header', function(event) {
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
            $preview.html('<img src="' + attachment.sizes.thumbnail.url + '" style="max-width:150px;max-height:150px;">');
          } else {
            $preview.html('<a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a>');
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

      // === Sortable Repeater Rows (6.1) ===
      if ($.fn.sortable) {
        $('.cmb-group-items').sortable({
          handle: '.cmb-sortable-handle, .cmb-group-item-header',
          items: '> .cmb-group-item',
          placeholder: 'cmb-sortable-placeholder',
          tolerance: 'pointer',
          update: function() {
            var $container = $(this);
            $container.children('.cmb-group-item').each(function(newIndex) {
              var $item = $(this);
              $item.find('.cmb-group-index').first().text(newIndex);
              $item.find(':input').each(function() {
                var name = $(this).attr('name');
                if (!name) return;
                var parentLevel = $container.parents('.cmb-group').length;
                var updated = updateNestedGroupName(name, parentLevel, newIndex);
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
      function evaluateConditionals() {
        $('[data-conditional-field]').each(function() {
          var $field = $(this);
          var condField = $field.data('conditional-field');
          var condOperator = $field.data('conditional-operator') || '==';
          var condValue = String($field.data('conditional-value') || '');

          // Find the condition source input within the same container
          var $container = $field.closest('.cmb-container, .cmb-tab-panel, .form-table');
          var $source = $container.find('[name="' + condField + '"], [name$="[' + condField + ']"]').first();
          if (!$source.length) return;

          var sourceVal = $source.is(':checkbox') ? ($source.is(':checked') ? $source.val() : '') : $source.val();
          sourceVal = String(sourceVal || '');

          var show = false;
          switch (condOperator) {
            case '==': show = (sourceVal === condValue); break;
            case '!=': show = (sourceVal !== condValue); break;
            case 'contains': show = (sourceVal.indexOf(condValue) !== -1); break;
            case '!empty': show = (sourceVal !== ''); break;
            case 'empty': show = (sourceVal === ''); break;
            default: show = (sourceVal === condValue);
          }

          if (show) {
            $field.slideDown(200);
          } else {
            $field.slideUp(200);
          }
        });
      }

      // Run on load and on input change
      evaluateConditionals();
      $(document).on('input change', '.cmb-container :input, .cmb-tab-panel :input', function() {
        evaluateConditionals();
      });

      // === Tab Switching (6.4) ===
      $(document).on('click', '.cmb-tab-nav-item a', function(event) {
        event.preventDefault();
        var $link = $(this);
        var $nav = $link.closest('.cmb-tab-nav');
        var $tabs = $nav.closest('.cmb-tabs');
        var tabId = $link.attr('href');

        $nav.find('.cmb-tab-nav-item').removeClass('cmb-tab-active');
        $link.closest('.cmb-tab-nav-item').addClass('cmb-tab-active');

        $tabs.find('.cmb-tab-panel').removeClass('cmb-tab-panel-active');
        $tabs.find(tabId).addClass('cmb-tab-panel-active');
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

      // === Helper: update item count indicators ===
      function updateRowCounts() {
        $('.cmb-item-count').each(function() {
          const $counter = $(this);
          const $container = $counter.closest('.cmb-input').find('.cmb-group-items').first();
          if ($container.length) {
            const count = $container.children('.cmb-group-item').length;
            $counter.text(count + (count === 1 ? ' item' : ' items'));
          }
        });
      }
    });

  })(jQuery);
