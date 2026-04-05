# TODO: Features

**Generated:** 2026-04-05
**Source:** Consolidated from AUDIT_FEATURE_GAP

Missing industry-standard features needed for competitive parity with ACF/CMB2/Meta Box.

---

## FEAT-001: Value Retrieval API (get_field / the_field)

- **Title:** Add cmb_get_field() / cmb_the_field() template functions
- **Description:** Currently developers must use raw `get_post_meta()` and understand the internal storage format. Every major competitor provides a convenience function (ACF: `get_field()`, CMB2: `cmb2_get_option()`, Meta Box: `rwmb_meta()`). This is the #1 most impactful missing feature.
- **Root Cause:** Value retrieval API was never designed.
- **Proposed Solution:**
  Add to `public-api.php`:
  ```php
  function cmb_get_field(string $fieldId, ?int $postId = null): mixed {
      $postId = $postId ?: get_the_ID();
      $value = get_post_meta($postId, $fieldId, true);
      return apply_filters('cmb_get_field_value', $value, $fieldId, $postId);
  }
  function cmb_the_field(string $fieldId, ?int $postId = null): void {
      echo esc_html(cmb_get_field($fieldId, $postId));
  }
  function cmb_get_option(string $fieldId, string $optionName): mixed { ... }
  function cmb_get_term_field(string $fieldId, int $termId): mixed { ... }
  function cmb_get_user_field(string $fieldId, int $userId): mixed { ... }
  ```
  Handle group field unpacking, array values, and formatted output.
- **Affected Files:**
  - `public-api.php` (add functions)
  - `docs/getting-started.md` (document API)
- **Estimated Effort:** 8 hours
- **Priority:** P0
- **Dependencies:** None

---

## FEAT-002: Multi-Select Field

- **Title:** Add multiple selection support to SelectField
- **Description:** No way to select multiple options from a dropdown. Basic field type every competitor has.
- **Root Cause:** SelectField only renders single `<select>`.
- **Proposed Solution:**
  Add `'multiple' => true` config option to SelectField:
  ```php
  if ($this->config['multiple'] ?? false) {
      $attrs .= ' multiple';
      $name .= '[]';
  }
  ```
  Update sanitization to handle arrays.
- **Affected Files:**
  - `src/Fields/SelectField.php`
- **Estimated Effort:** 3 hours
- **Priority:** P0
- **Dependencies:** None

---

## FEAT-003: Checkbox List (Multi-Checkbox) Field

- **Title:** Add CheckboxListField for selecting multiple options via checkboxes
- **Description:** Currently CheckboxField is a single boolean toggle. No way to render a group of checkboxes for multi-selection.
- **Root Cause:** Never implemented.
- **Proposed Solution:**
  Create `src/Fields/CheckboxListField.php` that renders a list of checkboxes from `options` config, stores selected values as array.
- **Affected Files:**
  - `src/Fields/CheckboxListField.php` (new)
- **Estimated Effort:** 4 hours
- **Priority:** P0
- **Dependencies:** None

---

## FEAT-004: AJAX-Powered Relational Fields (Select2/Choices.js)

- **Title:** Replace plain <select> with AJAX search for Post/User/Taxonomy fields
- **Description:** PostField, UserField, TaxonomyField load ALL records into a `<select>`. On sites with 10,000+ posts or 500+ users, this is unusable. ACF and Meta Box use AJAX autocomplete.
- **Root Cause:** No AJAX endpoint or JS library integration.
- **Proposed Solution:**
  1. Integrate Select2 or Choices.js library.
  2. Create AJAX endpoint for searching posts/users/terms.
  3. Add `'ajax' => true` config option (default true for >100 items).
  4. Return results with thumbnails/avatars for better UX.
- **Affected Files:**
  - `src/Fields/PostField.php`
  - `src/Fields/UserField.php`
  - `src/Fields/TaxonomyField.php`
  - `src/Core/AjaxHandler.php` (new)
  - `assets/cmb-script.js` (Select2 integration)
  - `assets/cmb-style.css` (Select2 styling)
- **Estimated Effort:** 24 hours
- **Priority:** P0
- **Dependencies:** PERF-008

---

## FEAT-005: Richer Location Rules

- **Title:** Add page template, taxonomy, user role, and specific page location rules
- **Description:** CMBB only supports targeting by post type. ACF supports 20+ location rules with AND/OR logic groups.
- **Root Cause:** Location rules system was never designed.
- **Proposed Solution:**
  Add a `location` config key with rule groups:
  ```php
  'location' => [
      [
          ['param' => 'post_type', 'operator' => '==', 'value' => 'page'],
          ['param' => 'page_template', 'operator' => '==', 'value' => 'template-landing.php'],
      ],
  ]
  ```
  Implement matching logic in MetaBoxManager::addMetaBoxes().
  Minimum support: post_type, page_template, post_category, post_taxonomy, user_role, specific page/post ID.
- **Affected Files:**
  - `src/Core/LocationMatcher.php` (new)
  - `src/Core/MetaBoxManager.php` (addMetaBoxes)
  - `src/Core/AdminUI.php` (location rule UI in builder)
- **Estimated Effort:** 20 hours
- **Priority:** P0
- **Dependencies:** None

---

## FEAT-006: Time Picker Field

- **Title:** Add TimeField for time-only selection
- **Description:** Missing basic field type. `<input type="time">` is trivial to add.
- **Root Cause:** Never implemented.
- **Proposed Solution:**
  Create `src/Fields/TimeField.php`:
  ```php
  class TimeField extends AbstractField {
      public function render(): string {
          return '<input type="time" name="' . esc_attr($this->getName()) . '" value="' . esc_attr($value) . '" />';
      }
      public function sanitize(mixed $value): mixed {
          return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value) ? $value : '';
      }
  }
  ```
- **Affected Files:**
  - `src/Fields/TimeField.php` (new)
- **Estimated Effort:** 1 hour
- **Priority:** P1
- **Dependencies:** None

---

## FEAT-007: Range / Slider Field

- **Title:** Add RangeField with min/max/step and value display
- **Description:** `<input type="range">` with real-time value display. Common field type.
- **Root Cause:** Never implemented.
- **Proposed Solution:**
  Create `src/Fields/RangeField.php` with `min`, `max`, `step` config options and JS value display.
- **Affected Files:**
  - `src/Fields/RangeField.php` (new)
  - `assets/cmb-script.js` (value display handler)
- **Estimated Effort:** 2 hours
- **Priority:** P1
- **Dependencies:** None

---

## FEAT-008: True/False Toggle Field

- **Title:** Add ToggleField with switch UI instead of plain checkbox
- **Description:** Toggle/switch UI for boolean values. Better UX than a plain checkbox for on/off settings.
- **Root Cause:** Never implemented.
- **Proposed Solution:**
  Create `src/Fields/ToggleField.php` with CSS toggle switch styling.
- **Affected Files:**
  - `src/Fields/ToggleField.php` (new)
  - `assets/cmb-style.css` (toggle switch CSS)
- **Estimated Effort:** 3 hours
- **Priority:** P1
- **Dependencies:** None

---

## FEAT-009: Message / Heading Display Field

- **Title:** Add MessageField for instructions and headings within meta boxes
- **Description:** Display-only field for instructions, headings, or custom HTML. No data storage. Every competitor has this.
- **Root Cause:** Never implemented.
- **Proposed Solution:**
  Create `src/Fields/MessageField.php`:
  ```php
  class MessageField extends AbstractField {
      public function render(): string {
          return '<div class="cmb-message">' . wp_kses_post($this->config['content'] ?? '') . '</div>';
      }
      public function sanitize(mixed $value): mixed { return ''; }
      public function getValue(): mixed { return null; }
  }
  ```
- **Affected Files:**
  - `src/Fields/MessageField.php` (new)
  - `assets/cmb-style.css` (message styling)
- **Estimated Effort:** 1 hour
- **Priority:** P1
- **Dependencies:** None

---

## FEAT-010: Divider / Separator Field

- **Title:** Add DividerField for visual separation between fields
- **Description:** Simple horizontal rule between fields. No data storage.
- **Root Cause:** Never implemented.
- **Proposed Solution:**
  Create `src/Fields/DividerField.php` rendering `<hr class="cmb-divider">`.
- **Affected Files:**
  - `src/Fields/DividerField.php` (new)
  - `assets/cmb-style.css` (divider styling)
- **Estimated Effort:** 0.5 hours
- **Priority:** P1
- **Dependencies:** None

---

## FEAT-011: Image Field (Dedicated)

- **Title:** Add ImageField with inline preview and image-specific features
- **Description:** Dedicated image field distinct from generic FileField. Should show inline preview, support crop, and display image dimensions.
- **Root Cause:** FileField handles both but UX is generic.
- **Proposed Solution:**
  Create `src/Fields/ImageField.php` extending FileField with image-specific rendering: inline preview, "Remove" button, optional crop ratio config.
- **Affected Files:**
  - `src/Fields/ImageField.php` (new)
  - `assets/cmb-script.js` (image preview handler)
  - `assets/cmb-style.css` (image preview styling)
- **Estimated Effort:** 6 hours
- **Priority:** P1
- **Dependencies:** None

---

## FEAT-012: Gallery Field

- **Title:** Add GalleryField for multi-image selection
- **Description:** Multi-image selection from media library with drag-to-reorder. Very common need.
- **Root Cause:** Never implemented.
- **Proposed Solution:**
  Create `src/Fields/GalleryField.php` using `wp.media` with `multiple: true` and `frame: 'post'`. Store as comma-separated attachment IDs. Render sortable thumbnail grid.
- **Affected Files:**
  - `src/Fields/GalleryField.php` (new)
  - `assets/cmb-script.js` (gallery handler)
  - `assets/cmb-style.css` (gallery grid styling)
- **Estimated Effort:** 12 hours
- **Priority:** P1
- **Dependencies:** None

---

## FEAT-013: AND/OR Conditional Logic Groups

- **Title:** Support AND/OR groups for conditional field display
- **Description:** Current conditional only supports single condition. Real projects need "show if (A == 1 AND B == 2) OR (C == 3)".
- **Root Cause:** Conditional system designed for single condition only.
- **Proposed Solution:**
  Extend the `conditional` config to support groups:
  ```php
  'conditional' => [
      'relation' => 'OR',
      'groups' => [
          [
              ['field' => 'type', 'operator' => '==', 'value' => 'premium'],
              ['field' => 'status', 'operator' => '!=', 'value' => 'draft'],
          ],
          [
              ['field' => 'override', 'operator' => '==', 'value' => '1'],
          ],
      ],
  ]
  ```
  Update JS `evaluateConditionals()` to handle groups with AND/OR logic.
- **Affected Files:**
  - `src/Core/FieldRenderer.php` (conditional data attributes)
  - `assets/cmb-script.js` (evaluateConditionals)
  - `src/Core/AdminUI.php` (conditional UI in builder)
- **Estimated Effort:** 12 hours
- **Priority:** P1
- **Dependencies:** None

---

## FEAT-014: Flexible Content Field

- **Title:** Add FlexibleContentField -- ACF's killer feature
- **Description:** Allows content editors to build pages from pre-defined layout blocks (hero, text+image, CTA, gallery, etc.). No competitor except ACF offers this natively. The single most cited reason agencies choose ACF.
- **Root Cause:** Complex field type never designed.
- **Proposed Solution:**
  Create `src/Fields/FlexibleContentField.php` that allows defining multiple "layouts", each with its own set of sub-fields. Users pick which layout to add per row. Store as nested array with `acf_fc_layout` key pattern.
- **Affected Files:**
  - `src/Fields/FlexibleContentField.php` (new)
  - `assets/cmb-script.js` (layout picker, row management)
  - `assets/cmb-style.css` (layout styling)
- **Estimated Effort:** 40 hours
- **Priority:** P1
- **Dependencies:** RF-001, FEAT-001

---

## FEAT-015: Frontend Form Rendering

- **Title:** Add cmb_render_form() for frontend meta box forms
- **Description:** ACF's `acf_form()` and CMB2's `cmb2_get_metabox_form()` allow rendering meta box forms on the frontend for user-submitted content, profile editing, etc.
- **Root Cause:** Frontend rendering was never designed.
- **Proposed Solution:**
  Add `cmb_render_form($meta_box_id, $post_id)` that:
  1. Outputs the meta box form HTML with proper nonce
  2. Enqueues necessary assets on frontend
  3. Handles AJAX form submission
  4. Validates and sanitizes submissions
- **Affected Files:**
  - `public-api.php` (cmb_render_form function)
  - `src/Core/FrontendForm.php` (new)
  - `assets/cmb-frontend.js` (new)
  - `assets/cmb-frontend.css` (new)
- **Estimated Effort:** 32 hours
- **Priority:** P1
- **Dependencies:** RF-008, FEAT-001

---

## FEAT-016: Expanded Gutenberg Sidebar Support

- **Title:** Add date, color, radio, file, group field types to Gutenberg panel
- **Description:** Gutenberg panel only supports 7 of 18 field types. Missing: date (DatePicker), color (ColorPicker), radio (RadioControl), file (MediaUpload), group (repeater), post, taxonomy, user.
- **Root Cause:** Only basic field types were mapped.
- **Proposed Solution:**
  Use WordPress Gutenberg components:
  - `DatePicker` for date fields
  - `ColorPicker` for color fields
  - `RadioControl` for radio fields
  - `MediaUpload` for file fields
  Add group field rendering in sidebar (simplified repeater).
- **Affected Files:**
  - `assets/cmb-gutenberg.js` (add component mappings)
  - `src/Core/GutenbergPanel.php` (fieldToJsConfig)
- **Estimated Effort:** 16 hours
- **Priority:** P1
- **Dependencies:** None

---

## FEAT-017: PHP Code Export from Admin UI

- **Title:** Export Admin UI configurations as PHP code
- **Description:** Configs created in Admin UI are stored in `wp_options` and cannot be exported as PHP code for version control. ACF solves this with "Export to PHP".
- **Root Cause:** Export only supports JSON format.
- **Proposed Solution:**
  Add "Export to PHP" button that generates `add_custom_meta_box()` calls:
  ```php
  add_custom_meta_box('my_box', [
      'title' => 'My Box',
      'postTypes' => ['post'],
      'fields' => [ ... ],
  ]);
  ```
- **Affected Files:**
  - `src/Core/AdminUI.php` (add PHP export handler)
  - `assets/cmb-admin.js` (PHP export button)
- **Estimated Effort:** 8 hours
- **Priority:** P1
- **Dependencies:** None

---

## FEAT-018: Color Picker with Alpha Support

- **Title:** Replace native <input type="color"> with WordPress Iris or enhanced picker
- **Description:** Current `<input type="color">` has no alpha support and inconsistent browser UI. ACF and Meta Box use WordPress's Iris color picker or a custom picker with alpha.
- **Root Cause:** Native HTML5 color input used for simplicity.
- **Proposed Solution:**
  Integrate `wp-color-picker` (WordPress bundled) with alpha extension:
  ```php
  wp_enqueue_style('wp-color-picker');
  wp_enqueue_script('wp-color-picker');
  ```
  Add `'alpha' => true` config option.
- **Affected Files:**
  - `src/Fields/ColorField.php`
  - `assets/cmb-script.js` (color picker initialization)
  - `src/Core/Plugin.php` (enqueue wp-color-picker conditionally)
- **Estimated Effort:** 4 hours
- **Priority:** P1
- **Dependencies:** None

---

## FEAT-019: Additional Developer Hooks

- **Title:** Add 10+ more hooks for extensibility
- **Description:** Currently 8 hooks vs 20-50+ in competitors. Missing hooks for: field config modification per-field, options filtering, taxonomy/user/options save events, script enqueue, admin UI builder events.
- **Root Cause:** Hooks added organically without a comprehensive plan.
- **Proposed Solution:**
  Add hooks including:
  - `cmb_field_config` (filter field config before render)
  - `cmb_field_options_{type}` (filter select/radio options)
  - `cmb_before_save_taxonomy_field` / `cmb_after_save_taxonomy_field`
  - `cmb_before_save_user_field` / `cmb_after_save_user_field`
  - `cmb_enqueue_scripts` (for field-specific assets)
  - `cmb_admin_builder_field_settings` (extend builder UI)
  - `cmb_validate_field` (filter validation errors)
  - `cmb_rest_field_schema` (filter REST schema)
- **Affected Files:**
  - `src/Core/FieldRenderer.php`
  - `src/Core/TaxonomyMetaManager.php`
  - `src/Core/UserMetaManager.php`
  - `src/Core/OptionsManager.php`
  - `src/Core/MetaBoxManager.php`
  - `src/Core/AdminUI.php`
  - `docs/hooks.md`
- **Estimated Effort:** 8 hours
- **Priority:** P1
- **Dependencies:** RF-008

---

## FEAT-020: Gutenberg Block Registration

- **Title:** Allow registering custom Gutenberg blocks using CMBB fields
- **Description:** ACF Blocks and Meta Box Blocks allow defining Gutenberg blocks with meta box fields. This is increasingly important as WordPress moves toward full-site editing.
- **Root Cause:** Block registration was never designed.
- **Proposed Solution:**
  Add `cmb_register_block()` API:
  ```php
  cmb_register_block('hero-section', [
      'title' => 'Hero Section',
      'fields' => [...],
      'render_callback' => 'render_hero_block',
  ]);
  ```
- **Affected Files:**
  - `src/Core/BlockRegistration.php` (new)
  - `assets/cmb-block-editor.js` (new)
  - `public-api.php` (cmb_register_block function)
- **Estimated Effort:** 40 hours
- **Priority:** P2
- **Dependencies:** FEAT-016

---

## FEAT-021: GraphQL Support (WPGraphQL)

- **Title:** Expose CMBB fields via WPGraphQL schema
- **Description:** Growing demand in headless WordPress. ACF and Meta Box both offer WPGraphQL integration.
- **Root Cause:** Never implemented.
- **Proposed Solution:**
  Create integration that hooks into WPGraphQL's field registration:
  ```php
  if (class_exists('WPGraphQL')) {
      // Register fields in GraphQL schema
  }
  ```
- **Affected Files:**
  - `src/Core/GraphQLIntegration.php` (new)
- **Estimated Effort:** 16 hours
- **Priority:** P2
- **Dependencies:** FEAT-001

---

## FEAT-022: Local JSON Sync (ACF-style)

- **Title:** Save field group configs as JSON files in the theme for version control
- **Description:** ACF's Local JSON feature saves field groups as JSON files in the active theme. Enables version control of field configurations without database dependency.
- **Root Cause:** Never designed.
- **Proposed Solution:**
  1. On save in Admin UI, also write a JSON file to `theme_dir/cmb-json/`.
  2. On init, compare JSON files with database configs and sync.
  3. Add UI indicator showing sync status.
- **Affected Files:**
  - `src/Core/LocalJson.php` (new)
  - `src/Core/AdminUI.php` (sync indicators)
- **Estimated Effort:** 16 hours
- **Priority:** P2
- **Dependencies:** None

---

## Summary

| ID | Title | Priority | Effort (hrs) |
|----|-------|----------|--------------|
| FEAT-001 | Value retrieval API | P0 | 8 |
| FEAT-002 | Multi-select field | P0 | 3 |
| FEAT-003 | Checkbox list field | P0 | 4 |
| FEAT-004 | AJAX relational fields | P0 | 24 |
| FEAT-005 | Richer location rules | P0 | 20 |
| FEAT-006 | Time picker field | P1 | 1 |
| FEAT-007 | Range/slider field | P1 | 2 |
| FEAT-008 | True/false toggle field | P1 | 3 |
| FEAT-009 | Message/heading field | P1 | 1 |
| FEAT-010 | Divider/separator field | P1 | 0.5 |
| FEAT-011 | Dedicated image field | P1 | 6 |
| FEAT-012 | Gallery field | P1 | 12 |
| FEAT-013 | AND/OR conditional logic | P1 | 12 |
| FEAT-014 | Flexible content field | P1 | 40 |
| FEAT-015 | Frontend form rendering | P1 | 32 |
| FEAT-016 | Expanded Gutenberg sidebar | P1 | 16 |
| FEAT-017 | PHP code export | P1 | 8 |
| FEAT-018 | Color picker with alpha | P1 | 4 |
| FEAT-019 | Additional developer hooks | P1 | 8 |
| FEAT-020 | Gutenberg block registration | P2 | 40 |
| FEAT-021 | GraphQL support | P2 | 16 |
| FEAT-022 | Local JSON sync | P2 | 16 |
| **Total** | | | **276.5** |
