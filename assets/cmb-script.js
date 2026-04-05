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
