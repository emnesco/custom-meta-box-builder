# Custom Meta Box Builder — Improvement Plan

A comprehensive plan for bug fixes, feature improvements, new features, and UI/UX enhancements.
Organized into phases with priority and complexity labels.

---

## Table of Contents

- [Phase 1: Critical Bug Fixes](#phase-1-critical-bug-fixes)
- [Phase 2: Security & Data Integrity](#phase-2-security--data-integrity)
- [Phase 3: Improve Existing Features](#phase-3-improve-existing-features)
- [Phase 4: UI/UX Improvements](#phase-4-uiux-improvements)
- [Phase 5: New Field Types](#phase-5-new-field-types)
- [Phase 6: New Features](#phase-6-new-features)
- [Phase 7: Developer Experience](#phase-7-developer-experience)
- [Phase 8: Advanced Features (Future)](#phase-8-advanced-features-future)

---

## Phase 1: Critical Bug Fixes

> These are bugs that cause data loss, broken rendering, or incorrect behavior. Fix first.

### 1.1 ~~CheckboxField & SelectField use `getId()` instead of `getName()`~~ [x]
- **Files:** `src/Fields/CheckboxField.php:10`, `src/Fields/SelectField.php:10`
- **Problem:** Both fields use `$this->getId()` for the HTML `name` attribute. In repeatable groups, `getId()` returns the raw field ID (e.g., `"name"`), while `getName()` returns the correctly resolved name with array indices (e.g., `"group[0][name]"`). This causes **data loss** for checkboxes and selects inside groups.
- **Fix:** Change both to use `$this->getName()`.
- **Priority:** CRITICAL | **Complexity:** Low

### 1.2 ~~Collapsed groups always render as "open"~~ [x]
- **File:** `src/Fields/GroupField.php:20`
- **Problem:** `$collapsed = isset($field['collapsed']) && $field['collapsed'] === true ? 'open' : 'open';` — both branches return `'open'`. The `collapsed` config key has no effect.
- **Fix:** Change first branch to `'collapsed'` or `''` (closed state), and update CSS/JS to handle the closed state.
- **Priority:** HIGH | **Complexity:** Low

### 1.3 ~~Malformed HTML — missing opening `<label>` tag~~ [x]
- **File:** `src/Core/FieldRenderer.php:99`
- **Problem:** `$output .= esc_html($field['label'] ?? '') . '</label>';` — closing `</label>` with no opening tag.
- **Fix:** `$output .= '<label>' . esc_html($field['label'] ?? '') . '</label>';`
- **Priority:** HIGH | **Complexity:** Low

### 1.4 ~~Duplicate `$layout` variable assignment~~ [x]
- **File:** `src/Core/FieldRenderer.php:72` and `:89`
- **Problem:** `$layout` is computed identically on two lines. The first is unused.
- **Fix:** Remove the duplicate at line 72.
- **Priority:** LOW | **Complexity:** Low

### 1.5 ~~SelectField renders duplicate label~~ [x]
- **File:** `src/Fields/SelectField.php:9`
- **Problem:** SelectField renders its own `<label>` inside `render()`, but `FieldRenderer.php:99` also renders a label wrapper. This produces duplicate labels.
- **Fix:** Remove the label from `SelectField::render()` — let FieldRenderer handle it.
- **Priority:** HIGH | **Complexity:** Low

### 1.6 ~~Empty repeatable text fields render no input~~ [x]
- **File:** `src/Fields/TextField.php:11-14`
- **Problem:** When `repeat === true` and `$value` is an empty array `[]`, the `foreach` never executes and no input is rendered. Users cannot add the first value.
- **Fix:** Ensure at least one empty input renders when value array is empty.
- **Priority:** HIGH | **Complexity:** Low

### 1.7 ~~`get_field_value()` returns `array(0)` for empty repeatable fields~~ [x]
- **File:** `src/Core/FieldRenderer.php:118`
- **Problem:** Fallback `array(0)` creates a phantom entry (integer `0`) instead of an empty state.
- **Fix:** Return `[[]]` for groups (one empty group) or `['']` for repeatable scalars.
- **Priority:** MEDIUM | **Complexity:** Low

### 1.8 ~~`processField()` in JS is a no-op~~ [x]
- **File:** `assets/cmb-script.js:49-51`
- **Problem:** Called for non-group repeatable field cloning but does nothing — cloned input names are not updated.
- **Fix:** Implement name attribute updating (increment index or append `[]`).
- **Priority:** HIGH | **Complexity:** Medium

### 1.9 ~~Debug `console.log()` statements in production JS~~ [x]
- **File:** `assets/cmb-script.js:76, 158`
- **Fix:** Remove all `console.log()` calls.
- **Priority:** LOW | **Complexity:** Low

### 1.10 ~~Unused/incomplete `replaceSpecificZero()` function in JS~~ [x]
- **File:** `assets/cmb-script.js:177-188`
- **Fix:** Delete the function.
- **Priority:** LOW | **Complexity:** Low

### 1.11 ~~Variable shadowing in JS~~ [x]
- **File:** `assets/cmb-script.js:21` and `:28`
- **Problem:** `currentItemCount` is declared twice — once in outer scope (line 21) and again inside the `if` block (line 28). The outer is never used.
- **Fix:** Remove the inner redeclaration or consolidate.
- **Priority:** LOW | **Complexity:** Low

**Phase 1 Summary:** All 11 items completed. Fixed CheckboxField/SelectField data loss (getId→getName), collapsed group ternary, malformed label HTML, duplicate layout assignment, SelectField duplicate label, empty repeatable rendering, array(0) fallback, JS processField no-op, console.log removal, dead code removal, variable shadowing.

---

## Phase 2: Security & Data Integrity

### 2.1 ~~Add capability check to save handler~~ [x]
- **File:** `src/Core/MetaBoxManager.php:39-61`
- **Problem:** No `current_user_can('edit_post', $postId)` check before saving.
- **Fix:** Add `if (!current_user_can('edit_post', $postId)) return;` after nonce verification.
- **Priority:** CRITICAL | **Complexity:** Low

### 2.2 ~~Use unique nonce per meta box~~ [x]
- **File:** `src/Core/MetaBoxManager.php:33, 40`
- **Problem:** All meta boxes share the hardcoded nonce `'cmb_nonce'`.
- **Fix:** Use `'cmb_nonce_' . $id` for the nonce action and name.
- **Priority:** HIGH | **Complexity:** Low

### 2.3 ~~Nested group field sanitization is shallow~~ [x]
- **File:** `src/Fields/GroupField.php:71-77`
- **Problem:** `map_deep($value, 'sanitize_text_field')` applies text sanitization to ALL nested values regardless of their field type. Nested select options validation, checkbox sanitization, etc. are all bypassed.
- **Fix:** Implement recursive sanitization that instantiates the correct field class for each sub-field and calls its own `sanitize()` method.
- **Priority:** HIGH | **Complexity:** High

### 2.4 ~~CheckboxField/SelectField `sanitize()` don't handle arrays~~ [x]
- **Files:** `src/Fields/CheckboxField.php:14-16`, `src/Fields/SelectField.php:21-23`
- **Problem:** If used in a repeatable context, `$value` is an array, but `sanitize()` only handles scalars.
- **Fix:** Add `if (is_array($value))` branch with `array_map()`.
- **Priority:** MEDIUM | **Complexity:** Low

### 2.5 ~~Silent failure when field class doesn't exist~~ [x]
- **File:** `src/Core/MetaBoxManager.php:44-45`
- **Problem:** If a field type class doesn't exist, the field is silently skipped during save — data is lost without any indication.
- **Fix:** Log a `_doing_it_wrong()` notice or `error_log()` warning.
- **Priority:** MEDIUM | **Complexity:** Low

### 2.6 ~~Add meta cleanup on post delete~~ [x]
- **Problem:** When a post is deleted, orphaned meta data remains in the database.
- **Fix:** Add `delete_post` hook to `MetaBoxManager::register()` that removes all registered field meta keys.
- **Priority:** MEDIUM | **Complexity:** Medium

### 2.7 ~~Two separate MetaBoxManager instances~~ [x]
- **Files:** `src/Core/Plugin.php:8`, `public-api.php:7`
- **Problem:** `Plugin::boot()` creates one manager, `add_custom_meta_box()` creates another. Both register `add_meta_boxes` and `save_post` hooks independently.
- **Fix:** Use a singleton or share the instance via the global, created once in `Plugin::boot()`.
- **Priority:** HIGH | **Complexity:** Medium

### 2.8 ~~Field config mutation via `repeat_fake` flag~~ [x]
- **File:** `src/Core/MetaBoxManager.php:26-29`
- **Problem:** The original field config array is mutated in place, adding `repeat` and `repeat_fake` keys.
- **Fix:** Clone the field array before modifying.
- **Priority:** LOW | **Complexity:** Low

**Phase 2 Summary:** All 8 items completed. Added current_user_can() check, unique nonce per meta box (cmb_nonce_{id}/cmb_save_{id}), recursive group field sanitization using proper field classes, array handling in Checkbox/Select sanitize(), _doing_it_wrong() logging for missing field classes, delete_post meta cleanup hook, singleton MetaBoxManager shared between Plugin and public API, cloned field config to prevent mutation.

---

## Phase 3: Improve Existing Features

### 3.1 ~~Add `default` value support~~ [x]
- **Problem:** No way to set a default value that shows when post meta is empty.
- **Fix:** Check for `'default'` key in `AbstractField::getValue()` and `FieldRenderer::get_field_value()`.
- **Config:** `'default' => 'Draft'`
- **Priority:** HIGH | **Complexity:** Low

### 3.2 ~~Add meta box `context` and `priority` options~~ [x]
- **File:** `src/Core/MetaBoxManager.php:20`
- **Problem:** `add_meta_box()` is called without context/priority, defaulting to `'advanced'`/`'default'`.
- **Fix:** Accept `'context'` and `'priority'` in the meta box config and pass to `add_meta_box()`.
- **Config:** `add_custom_meta_box($id, $title, $postTypes, $fields, 'side', 'high')`
- **Priority:** HIGH | **Complexity:** Low

### 3.3 ~~Add `required` field validation~~ [x]
- **Problem:** No way to mark fields as required or validate before save.
- **Fix:** Add `'required' => true` config key. Render `required` HTML attribute. Add server-side validation in `saveMetaBoxData()` with admin notices for errors.
- **Priority:** HIGH | **Complexity:** Medium

### 3.4 ~~Add proper HTML `id` attributes and label association~~ [x]
- **File:** `src/Core/FieldRenderer.php:82-84`
- **Problem:** Both `id` and `name` config are set to the resolved name (with brackets), which is invalid as an HTML `id`. Labels have no `for` attribute.
- **Fix:** Generate a sanitized HTML `id` (replace brackets with hyphens), add `for` attribute to labels.
- **Priority:** MEDIUM | **Complexity:** Medium

### 3.5 ~~Add field validation system~~ [x]
- **Problem:** Only sanitization exists, no validation with user-facing error messages.
- **Implementation:**
  - `'validate' => ['required', 'email']` or `'validate' => ['min:3', 'max:100']`
  - Add `validate()` method to `FieldInterface`
  - Collect errors in `saveMetaBoxData()` and show via `admin_notices`
- **Priority:** HIGH | **Complexity:** High

### 3.6 ~~Add `sanitize_callback` override per field~~ [x]
- **Problem:** Sanitization is hardcoded per field type. No way to customize without extending.
- **Fix:** If `'sanitize_callback'` is set in field config, use it instead of the default.
- **Config:** `'sanitize_callback' => 'my_custom_sanitizer'`
- **Priority:** MEDIUM | **Complexity:** Low

### 3.7 ~~Add `max_rows` / `min_rows` for repeaters~~ [x]
- **Problem:** No limit on how many rows can be added to repeatable fields.
- **Fix:** Add config keys, enforce in JS (disable Add button) and PHP (server-side trim).
- **Config:** `'min_rows' => 1, 'max_rows' => 10`
- **Priority:** MEDIUM | **Complexity:** Medium

### 3.8 ~~Performance: bulk meta fetch instead of per-field queries~~ [x]
- **File:** `src/Core/FieldRenderer.php:116-121`
- **Problem:** Each field triggers a separate `get_post_meta()` call. 20 fields = 20 queries.
- **Fix:** Fetch all post meta once with `get_post_meta($post_id)` (no key) and look up from that.
- **Priority:** MEDIUM | **Complexity:** Medium

### 3.9 ~~Add type declarations and null safety~~ [x]
- **Files:** Multiple across `src/`
- **Problem:** Missing return types on `FieldInterface::sanitize()` and `getValue()`, missing parameter types, inconsistent null handling.
- **Fix:** Add PHP 8 type declarations throughout.
- **Priority:** LOW | **Complexity:** Medium

**Phase 3 Summary:** All 9 items completed. Added default value support in AbstractField::getValue(), context/priority params to add_custom_meta_box() and add_meta_box(), required field validation with HTML required attr + red asterisk + server-side validation, proper HTML id generation (cmb-* prefix, bracket-safe) with label for association, full validation system (required/email/url/min/max/numeric/pattern rules) via validate() method on FieldInterface, sanitize_callback override per field, min_rows/max_rows for repeaters (JS enforcement + PHP array_slice), bulk meta fetch via metaCache in FieldRenderer, PHP 8 type declarations (mixed) on FieldInterface and all field classes.

---

## Phase 4: UI/UX Improvements

### 4.1 ~~Fix CSS: toggle indicator icon syntax~~ [x]
- **File:** `assets/cmb-style.css:91-96`
- **Problem:** `content: "\f140" / '' !important;` is invalid CSS syntax.
- **Fix:** `content: "\f140"; font-family: dashicons;`
- **Priority:** HIGH | **Complexity:** Low

### 4.2 ~~Fix CSS: `min-width: 450px` causes mobile overflow~~ [x]
- **File:** `assets/cmb-style.css:12`
- **Problem:** Forces horizontal scroll on screens smaller than 450px.
- **Fix:** Remove `min-width` or move it inside a min-width media query.
- **Priority:** HIGH | **Complexity:** Low

### 4.3 ~~Consolidate duplicate `.cmb-remove-row` styles~~ [x]
- **File:** `assets/cmb-style.css:131-140` and `:156-167`
- **Problem:** Two declarations for same class with conflicting `width` values.
- **Fix:** Merge into one rule block.
- **Priority:** LOW | **Complexity:** Low

### 4.4 ~~Consolidate duplicate `.cmb-add-row` styles~~ [x]
- **File:** `assets/cmb-style.css:152-153` and `:173-185`
- **Problem:** Two declarations for same class.
- **Fix:** Merge into one rule block.
- **Priority:** LOW | **Complexity:** Low

### 4.5 ~~Add delete confirmation dialog~~ [x]
- **File:** `assets/cmb-script.js:151-161`
- **Problem:** Clicking remove instantly deletes a group row with no confirmation.
- **Fix:** Add `if (!confirm('Remove this item?')) return;` before removal.
- **Priority:** HIGH | **Complexity:** Low

### 4.6 ~~Add visual feedback on add/remove~~ [x]
- **Problem:** No animation or feedback when rows are added or removed.
- **Fix:** Use jQuery `fadeIn()`/`slideDown()` for added rows, `fadeOut()` for removed rows.
- **Priority:** MEDIUM | **Complexity:** Low

### 4.7 ~~Add `:focus-visible` styles for keyboard accessibility~~ [x]
- **File:** `assets/cmb-style.css`
- **Problem:** No focus indicators on buttons (`.cmb-add-row`, `.cmb-remove-row`, group headers).
- **Fix:** Add `outline: 2px solid #0073aa;` on `:focus-visible`.
- **Priority:** HIGH | **Complexity:** Low

### 4.8 ~~Make group toggle keyboard-accessible~~ [x]
- **File:** `assets/cmb-script.js:163-171`
- **Problem:** Toggle only works on click. No `tabindex`, no `role="button"`, no Enter/Space key handling.
- **Fix:** Add `tabindex="0"` and `role="button"` to header HTML. Add `keydown` handler for Enter/Space.
- **Priority:** HIGH | **Complexity:** Low

### 4.9 ~~Add `aria-label` to icon-only buttons~~ [x]
- **File:** `src/Fields/GroupField.php:49, 63`
- **Problem:** Remove button (`x`) and toggle indicator have no accessible labels.
- **Fix:** Add `aria-label="Remove item"` and `aria-label="Toggle group"`.
- **Priority:** MEDIUM | **Complexity:** Low

### 4.10 ~~Add `aria-expanded` state to toggle~~ [x]
- **Problem:** Screen readers don't know whether a group is expanded or collapsed.
- **Fix:** Set `aria-expanded="true"` / `"false"` on the header, toggle in JS.
- **Priority:** MEDIUM | **Complexity:** Low

### 4.11 ~~Add empty state message for groups~~ [x]
- **Problem:** When all group items are removed, there's no indication of what to do next.
- **Fix:** Show "No items yet. Click Add Row to begin." when group container is empty.
- **Priority:** LOW | **Complexity:** Low

### 4.12 ~~Add Expand All / Collapse All buttons for groups~~ [x]
- **Problem:** With many group items, toggling each one individually is tedious.
- **Fix:** Add "Expand All" / "Collapse All" links above the group items container.
- **Priority:** LOW | **Complexity:** Low

### 4.13 ~~Add item count indicator~~ [x]
- **Problem:** No way to see how many items exist without expanding/scrolling.
- **Fix:** Show `"3 items"` next to the Add Row button.
- **Priority:** LOW | **Complexity:** Low

### 4.14 ~~Fix `cursor: move` on group index without drag support~~ [x]
- **File:** `assets/cmb-style.css:123`
- **Problem:** The index column shows a drag cursor but dragging doesn't do anything.
- **Fix:** Either implement sortable drag (Phase 6) or change to `cursor: default`.
- **Priority:** LOW | **Complexity:** Low

### 4.15 ~~Add print stylesheet~~ [x]
- **Problem:** Action buttons (Add Row, Remove) appear when printing.
- **Fix:** Add `@media print { .cmb-add-row, .cmb-remove-row { display: none; } }`.
- **Priority:** LOW | **Complexity:** Low

**Phase 4 Summary:** All 15 items completed. Rewrote CSS: fixed toggle icon syntax (dashicons font-family), removed min-width:450px mobile overflow, consolidated duplicate .cmb-remove-row/.cmb-add-row rules, changed cursor:move to cursor:default on group index. Added delete confirmation dialog, slide/fade animations on add/remove, :focus-visible keyboard accessibility styles, keyboard Enter/Space on group toggle headers (role=button, tabindex=0), aria-label on remove button, aria-expanded toggle state, empty state message when all items removed, Expand All/Collapse All links, item count indicator, print stylesheet hiding controls.

---

## Phase 5: New Field Types

### 5.1 ~~Number field~~ [x]
- **HTML:** `<input type="number">` with `min`, `max`, `step` attribute support
- **Sanitize:** `intval()` or `floatval()` based on step
- **Priority:** HIGH | **Complexity:** Low

### 5.2 ~~Email field~~ [x]
- **HTML:** `<input type="email">`
- **Sanitize:** `sanitize_email()`
- **Priority:** HIGH | **Complexity:** Low

### 5.3 ~~URL field~~ [x]
- **HTML:** `<input type="url">`
- **Sanitize:** `esc_url_raw()`
- **Priority:** HIGH | **Complexity:** Low

### 5.4 ~~Radio button field~~ [x]
- **HTML:** `<input type="radio">` set, using same `options` config as SelectField
- **Sanitize:** Options whitelist (same as SelectField)
- **Priority:** HIGH | **Complexity:** Low

### 5.5 ~~Hidden field~~ [x]
- **HTML:** `<input type="hidden">`
- **Sanitize:** `sanitize_text_field()`
- **Render:** No label wrapper needed
- **Priority:** MEDIUM | **Complexity:** Low

### 5.6 ~~Password field~~ [x]
- **HTML:** `<input type="password">`
- **Sanitize:** `sanitize_text_field()`
- **Priority:** LOW | **Complexity:** Low

### 5.7 ~~Date / DateTime picker~~ [x]
- **HTML:** `<input type="date">` / `<input type="datetime-local">` with optional JS picker
- **Sanitize:** Date format validation (ISO 8601)
- **Priority:** HIGH | **Complexity:** Medium

### 5.8 ~~Color picker~~ [x]
- **HTML:** `<input type="color">` or WordPress Iris color picker integration
- **Sanitize:** Hex validation regex
- **JS dependency:** `wp-color-picker` (bundled with WordPress)
- **Priority:** MEDIUM | **Complexity:** Medium

### 5.9 ~~WYSIWYG / Rich Text editor~~ [x]
- **HTML:** `wp_editor()` TinyMCE integration
- **Sanitize:** `wp_kses_post()`
- **Priority:** MEDIUM | **Complexity:** High

### 5.10 ~~File / Image upload (WP Media Library)~~ [x]
- **HTML:** Upload button + preview + hidden input for attachment ID
- **JS dependency:** `wp_enqueue_media()` + media modal
- **Sanitize:** `absint()` (attachment ID)
- **Priority:** HIGH | **Complexity:** High

### 5.11 ~~Post Object selector~~ [x]
- **HTML:** Select2 / AJAX autocomplete for posts
- **Sanitize:** `absint()` (post ID) + `get_post()` existence check
- **JS dependency:** Select2 or custom autocomplete
- **Priority:** MEDIUM | **Complexity:** High

### 5.12 ~~Taxonomy selector~~ [x]
- **HTML:** Checkbox list or Select2 for taxonomy terms
- **Sanitize:** `absint()` (term IDs) + `term_exists()` check
- **Priority:** MEDIUM | **Complexity:** High

### 5.13 ~~User selector~~ [x]
- **HTML:** Select2 / AJAX autocomplete for users
- **Sanitize:** `absint()` (user ID)
- **Priority:** LOW | **Complexity:** High

**Phase 5 Summary:** All 13 field types created. NumberField (int/float, min/max/step), EmailField (sanitize_email), UrlField (esc_url_raw), RadioField (options whitelist, fieldset), HiddenField, PasswordField, DateField (date + datetime-local, ISO 8601 validation), ColorField (hex validation), WysiwygField (wp_editor integration), FileField (WP media library with preview/remove), PostField (select with get_posts query), TaxonomyField (checkbox list or select), UserField (select with role filter). Added file upload JS handlers and wp_enqueue_media() call.

---

## Phase 6: New Features

### 6.1 ~~Sortable / draggable repeater rows~~ [x]
- **Problem:** Users can't reorder group items.
- **Implementation:** jQuery UI Sortable on `.cmb-group-items`, update name indices after sort.
- **Priority:** HIGH | **Complexity:** High

### 6.2 ~~Row title from field value~~ [x]
- **Problem:** Group headers all show the same generic label.
- **Config:** `'row_title_field' => 'name'` — use the value of a sub-field as the header title.
- **Implementation:** JS `change` event listener updates header text dynamically.
- **Priority:** MEDIUM | **Complexity:** Medium

### 6.3 ~~Conditional field display (show/hide logic)~~ [x]
- **Config:**
  ```php
  'conditional' => [
      'field'    => 'payment_method',
      'operator' => '==',
      'value'    => 'card',
  ]
  ```
- **Implementation:** JS event listeners on condition fields, toggle visibility of dependent fields.
- **Priority:** MEDIUM | **Complexity:** High

### 6.4 ~~Tab support within a meta box~~ [x]
- **Config:**
  ```php
  'tabs' => [
      'basic'    => ['label' => 'Basic', 'fields' => [...]],
      'advanced' => ['label' => 'Advanced', 'fields' => [...]],
  ]
  ```
- **Implementation:** New HTML structure with tab headers + tab panels, JS switching.
- **Priority:** MEDIUM | **Complexity:** High

### 6.5 ~~Taxonomy term meta support~~ [x]
- **Problem:** Plugin only works on post types — can't add meta boxes to category/tag edit screens.
- **Implementation:** Hook into `{taxonomy}_edit_form_fields` and `edited_{taxonomy}`.
- **Priority:** LOW | **Complexity:** High

### 6.6 ~~User profile meta support~~ [x]
- **Problem:** Can't add meta boxes to the user profile edit screen.
- **Implementation:** Hook into `edit_user_profile` and `personal_options_update`.
- **Priority:** LOW | **Complexity:** High

### 6.7 ~~Options page support~~ [x]
- **Problem:** Can't use this for global site settings (non-post-specific).
- **Implementation:** Create admin menu pages, use `register_setting()` / `get_option()`.
- **Priority:** LOW | **Complexity:** High

### 6.8 ~~Revision support for meta values~~ [x]
- **Problem:** WordPress revisions don't capture meta box changes.
- **Implementation:** Copy meta on `_wp_put_post_revision`, restore on revision restore.
- **Priority:** LOW | **Complexity:** High

### 6.9 ~~Duplicate / clone item button~~ [x]
- **Problem:** Users can only add blank rows or clone the last row.
- **Fix:** Add a "duplicate" button on each group item that clones that specific item with its values.
- **Priority:** LOW | **Complexity:** Medium

### 6.10 ~~Unsaved changes warning~~ [x]
- **Problem:** No warning when navigating away with unsaved changes in meta box fields.
- **Implementation:** JS `beforeunload` event tracking input changes.
- **Priority:** LOW | **Complexity:** Low

**Phase 6 Summary:** All 10 items completed. Added jQuery UI Sortable drag-and-drop reordering with index updates (6.1), dynamic row titles from sub-field values via data-row-title-field (6.2), conditional field show/hide with ==, !=, contains, empty, !empty operators (6.3), tab support with nav/panel switching in meta boxes via renderTabs() and flattenFields() (6.4), TaxonomyMetaManager for term meta on edit/add screens (6.5), UserMetaManager for user profile fields (6.6), OptionsManager for admin settings pages with register_setting/get_option (6.7), revision meta support with copy-on-revision and restore-on-restore hooks (6.8), duplicate/clone button per group item (6.9), unsaved changes warning via beforeunload (6.10). Added public API helpers: add_custom_taxonomy_meta(), add_custom_user_meta(), add_custom_options_page(). Added CSS for tabs, sortable placeholder, duplicate button. Updated test mocks.

---

## Phase 7: Developer Experience

### 7.1 Add WordPress action/filter hooks
- **Problem:** No way for developers to customize behavior without modifying core files.
- **Hooks to add:**

| Hook | Type | Location | Purpose |
|---|---|---|---|
| `cmb_before_render_field` | action | FieldRenderer::render() | Before field HTML |
| `cmb_after_render_field` | action | FieldRenderer::render() | After field HTML |
| `cmb_before_save_field` | action | MetaBoxManager::saveMetaBoxData() | Before saving |
| `cmb_after_save_field` | action | MetaBoxManager::saveMetaBoxData() | After saving |
| `cmb_sanitize_{type}` | filter | MetaBoxManager::saveMetaBoxData() | Custom sanitization |
| `cmb_field_value` | filter | FieldRenderer::get_field_value() | Modify retrieved value |
| `cmb_field_html` | filter | FieldRenderer::render() | Modify rendered HTML |
| `cmb_meta_box_args` | filter | MetaBoxManager::addMetaBoxes() | Modify add_meta_box args |

- **Priority:** HIGH | **Complexity:** Medium

### 7.2 Field type registration API
- **Problem:** Custom field types work automatically via naming convention, but there's no explicit registration or discovery.
- **Fix:** Add `CMB::register_field_type('my_type', MyField::class)` for custom namespace/class support.
- **Priority:** MEDIUM | **Complexity:** Medium

### 7.3 REST API integration
- **Problem:** Meta box fields are not available via WordPress REST API.
- **Fix:** Use `register_post_meta()` with `'show_in_rest' => true` for each field. Handle complex types with custom REST schema.
- **Config:** `'show_in_rest' => true`
- **Priority:** MEDIUM | **Complexity:** High

### 7.4 Add input validation for field configs at registration time
- **Files:** `public-api.php`, `src/Core/MetaBoxManager.php`
- **Problem:** Invalid field configs (missing `id`, missing `type`, unknown type) fail silently.
- **Fix:** Validate at `add()` time and throw `_doing_it_wrong()` for invalid configs.
- **Priority:** MEDIUM | **Complexity:** Low

### 7.5 WP-CLI commands
- **Commands:**
  - `wp cmb list` — list registered meta boxes
  - `wp cmb get <post_id> <field_id>` — retrieve a field value
  - `wp cmb set <post_id> <field_id> <value>` — set a field value
- **Priority:** LOW | **Complexity:** Medium

### 7.6 Gutenberg sidebar panel support
- **Problem:** Meta boxes appear below the editor in block editor, not in the sidebar.
- **Implementation:** React-based `PluginDocumentSettingPanel` components.
- **Priority:** LOW | **Complexity:** Very High

---

## Phase 8: Advanced Features (Future)

- [ ] Import/Export meta box configurations (JSON)
- [ ] Admin UI for creating meta boxes without code
- [ ] Multi-language field support (per-locale values)
- [ ] Search/filter for large repeater groups (10+ items)
- [ ] Virtual scrolling / lazy loading for very large repeaters
- [ ] Field dependency graph visualization (dev tools)
- [ ] Bulk meta operations for multiple posts

---

## Quick Reference: Priority Matrix

| Priority | Count | Examples |
|---|---|---|
| **CRITICAL** | 3 | CheckboxField/SelectField name bug, capability check, collapsed bug |
| **HIGH** | 18 | Malformed HTML, nonce per box, hooks system, sortable rows, new field types |
| **MEDIUM** | 16 | Validation system, REST API, conditional logic, color picker, WYSIWYG |
| **LOW** | 15 | Print CSS, password field, WP-CLI, Gutenberg, revision support |

---

## Implementation Notes

- **Non-breaking changes:** All Phase 1-4 items are backward-compatible — existing field configs continue to work.
- **New field types (Phase 5):** Adding new classes in `src/Fields/` requires no changes to existing code thanks to the dynamic class resolution.
- **Testing:** Each fix and new feature should include PHPUnit tests. Update `tests/bootstrap.php` with any new WP function mocks needed.
- **Documentation:** Update `docs/` files after each phase. Add new pages for new field types and features.
