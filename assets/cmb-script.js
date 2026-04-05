(function($) {
    'use strict';

    $(document).ready(function() {

      $(document).on('click', '.cmb-repeat > .cmb-input > .cmb-add-row', function(event) {
        event.preventDefault();

        const $addRowButton = $(this);
        const $repeatedField = $addRowButton.closest('.cmb-input');
        const $groupItemsContainer = $repeatedField.find('.cmb-group-items').first();
        const $groupItems = $groupItemsContainer.children('.cmb-group-item');
        const $parentGroups = $repeatedField.parents('.cmb-group');
        const nestingLevel = $parentGroups.length;
        const currentItemCount = $groupItems.length;

        let $clone;

        if ($groupItemsContainer.length === 0) {
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
      });

      function processField($clone, currentItemCount) {
        let name = $clone.attr('name');
        if (name) {
          // For bracket-indexed names like field[0], increment the index
          const bracketMatch = name.match(/^(.+)\[(\d+)\]$/);
          if (bracketMatch) {
            $clone.attr('name', bracketMatch[1] + '[' + currentItemCount + ']');
          }
          // For names ending with [], keep as-is (server handles array append)
        }
        $clone.val('');
        return $clone;
      }

      /**
      * Processes nested groups recursively, updating input names based on nesting level.
      * @param {jQuery} $clone - Cloned group item.
      * @param {number} nestingLevel - The current nesting level (starts at 1 for the top-level group).
      * @param {number} parentItemIndex - Index of the new item being added in the parent group.
      * @returns {jQuery} Modified clone.
      */
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

        return $clone;
      }

      /**
      * Updates input name attribute for nested groups, targeting replacement by level.
      * @param {string} inputName - Original input name.
      * @param {number} nestingLevel - Current nesting level.
      * @param {number} currentItemIndex - Index of the new item.
      * @returns {string} Updated input name.
      */
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

      $(document).on('click', '.cmb-group > .cmb-group-items > .cmb-group-item > .cmb-group-item-body > .cmb-remove-row', function(event) {
        event.preventDefault();
        const $removeRowButton = $(this);
        const $repeatedField = $removeRowButton.closest('.cmb-group-item');

        $repeatedField.remove();
      });

      $(document).on('click', '.cmb-group > .cmb-group-items > .cmb-group-item > .cmb-group-item-header', function(event) {
        event.preventDefault();
        const $toggleButton = $(this);
        const $toggleElement = $toggleButton.parent('.cmb-group-item');

        $toggleElement.toggleClass('open');
      });
    });

  })(jQuery);
