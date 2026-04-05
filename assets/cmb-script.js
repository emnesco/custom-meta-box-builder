(function($) {
    'use strict';

    $(document).ready(function() {

      // Add Row
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
          $clone.insertAfter($lastInput);
        } else {
          $clone = $groupItems.last().clone(false, false);
          $clone = processNestedGroups($clone, nestingLevel, currentItemCount);
          $groupItemsContainer.append($clone);
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

      // Remove Row (with min_rows enforcement)
      $(document).on('click', '.cmb-group > .cmb-group-items > .cmb-group-item > .cmb-group-item-body > .cmb-remove-row', function(event) {
        event.preventDefault();
        const $removeRowButton = $(this);
        const $groupItem = $removeRowButton.closest('.cmb-group-item');
        const $container = $groupItem.parent('.cmb-group-items');

        // Enforce min_rows
        const $addRow = $container.closest('.cmb-input').find('.cmb-add-row').first();
        const minRows = $addRow.data('min-rows') || 0;
        if ($container.children('.cmb-group-item').length <= minRows) {
          return;
        }

        $groupItem.remove();
        updateRowCounts();
      });

      // Toggle group item (click + keyboard)
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

      // Helper: update item count indicators
      function updateRowCounts() {
        $('.cmb-item-count').each(function() {
          const $counter = $(this);
          const $container = $counter.closest('.cmb-input').find('.cmb-group-items').first();
          if ($container.length) {
            $counter.text($container.children('.cmb-group-item').length + ' items');
          }
        });
      }
    });

  })(jQuery);
