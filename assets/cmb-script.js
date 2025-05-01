// cmb-script.js
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

        let $clone = $groupItems.last().clone(true, true);
  

        if($groupItemsContainer.length === 0) {
          const $inputs =  $repeatedField.find(':input');
          const currentItemCount = $inputs.length;
          const $lastInput = $inputs.last();
          $clone = $inputs.last().clone(true, true);


          $clone = processField($clone, currentItemCount); // Start currentLevel at 1

          $clone.insertAfter($lastInput);
        } else {
          $clone = processNestedGroups($clone, nestingLevel, currentItemCount); // Start currentLevel at 1
  
          $groupItemsContainer.append($clone);
        }





      });


      function processField($clone, currentItemCount){
        return $clone
      }

  
      $(document).on('click', '.cmb-group > .cmb-group-items > .cmb-group-item > .cmb-group-item-body >.cmb-remove-row', function(event) {
        event.preventDefault();
        const $removeRowButton = $(this); // Changed variable name to be more descriptive

        const $repeatedField = $removeRowButton.closest('.cmb-group-item');
        const itemIndex = $repeatedField.parent().children('.cmb-group-item').index($repeatedField);

        console.log('Removed item index:', itemIndex); // Log the index for verification

        $repeatedField.remove();
      })
     
      $(document).on('click', '.cmb-group > .cmb-group-items > .cmb-group-item > .cmb-group-item-header', function(event) {
        event.preventDefault();
        const $toggleButton = $(this); // Changed variable name to be more descriptive

        const $toggleElement = $toggleButton.parent('.cmb-group-item');


        $toggleElement.toggleClass('open');
      })
      



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
  
        const $indexElement = $clone.find('.cmb-group-index');
        if ($indexElement.length) {
          $indexElement.text(parentItemIndex);
        }
  
  
        $clone.find('.cmb-group').each(function() {
          const $nestedGroup = $(this);
          const $groupItemsContainer = $nestedGroup.find('.cmb-group-items').first();
          $groupItemsContainer.children('.cmb-group-item:not(:first)').remove();
          const $nestedGroupItem = $groupItemsContainer.children('.cmb-group-item').first();
  
          const getNestingLevel = $nestedGroup.parents('.cmb-group').length;


          if ($nestedGroupItem.length) {
            processNestedGroups($nestedGroupItem, getNestingLevel, 0 );
          }
        });
  
        return $clone;
      }
  
  
      /**
     * Updates input name attribute for nested groups, targeting replacement by level.
     * @param {string} inputName - Original input name.
     * @param {number} currentLevel - Current nesting level.
     * @param {number} currentItemIndex - Index of the new item.
     * @returns {string} Updated input name.
     */
      function updateNestedGroupName(inputName, nestingLevel, currentItemIndex) {
        const regex = /\[(\d+)\]/g;
        const matches = [];
        let match;
        

        console.log('inputName ' + inputName);
        console.log('nestingLevel ' + nestingLevel);
        console.log('currentItemIndex ' + currentItemIndex);


        // Find all [index] patterns and their positions
        while ((match = regex.exec(inputName)) !== null) {
            matches.push({
                start: match.index,
                end: match.index + match[0].length,
                fullMatch: match[0]
            });
        }
    
        // Check for valid nesting level
        if (nestingLevel >= matches.length || nestingLevel < 0) {
            console.error('Invalid nesting level');
            return inputName;
        }
    
        // Replace the target [index] with new index
        const target = matches[nestingLevel];
        const newSegment = `[${currentItemIndex}]`;
        
        return inputName.slice(0, target.start) + 
               newSegment + 
               inputName.slice(target.end);
    }
  
    });
  
  })(jQuery);


  function replaceSpecificZero(inputName, currentLevel, currentItemIndex) {


      

          // Find the Nth occurrence of [0]

      

      

      }