# TODO: Frontend (UI/UX/JS/CSS)

**Generated:** 2026-04-05
**Source:** Consolidated from AUDIT_FRONTEND

UI/UX redesign, JS architecture improvements, and accessibility fixes.

---

## FE-001: Add ARIA Roles to Tab Components

- **Title:** Tab components missing all ARIA roles (WCAG 4.1.2 failure)
- **Description:** `.cmb-tab-nav` and `.cmb-tab-panel` lack `role="tablist"`, `role="tab"`, `role="tabpanel"`, `aria-selected`, `aria-controls`, `aria-labelledby`. No arrow key navigation between tabs. Same issue applies to language tabs.
- **Root Cause:** Tab implementation uses generic divs without semantic markup.
- **Proposed Solution:**
  ```html
  <ul class="cmb-tab-nav" role="tablist">
    <li role="presentation">
      <a role="tab" aria-selected="true" aria-controls="panel-id" id="tab-id">
    </li>
  </ul>
  <div class="cmb-tab-panel" role="tabpanel" aria-labelledby="tab-id" id="panel-id">
  ```
  Add arrow key navigation, Home/End key support in JS.
- **Affected Files:**
  - `src/Core/FieldRenderer.php` (tab rendering)
  - `assets/cmb-script.js` (tab switching, keyboard navigation)
- **Estimated Effort:** 4 hours
- **Priority:** P0
- **Dependencies:** None

---

## FE-002: Add Legend to Fieldset Elements

- **Title:** RadioField and TaxonomyField fieldsets missing `<legend>`
- **Description:** `<fieldset class="cmb-radio-group">` and taxonomy fieldsets have no `<legend>` element. Screen readers expect `<legend>` inside `<fieldset>` (WCAG 1.3.1).
- **Root Cause:** Semantic HTML requirement overlooked.
- **Proposed Solution:**
  ```php
  $output = '<fieldset class="cmb-radio-group">';
  $output .= '<legend class="screen-reader-text">' . esc_html($this->getLabel()) . '</legend>';
  ```
- **Affected Files:**
  - `src/Fields/RadioField.php` (line 11)
  - `src/Fields/TaxonomyField.php` (line 40)
- **Estimated Effort:** 1 hour
- **Priority:** P0
- **Dependencies:** None

---

## FE-003: Add Label to Group Search Input

- **Title:** Search input in repeater groups lacks `<label>` element
- **Description:** The search input uses placeholder-only labeling, which fails WCAG 1.3.1.
- **Root Cause:** Placeholder used instead of label.
- **Proposed Solution:**
  ```php
  $output .= '<label for="cmb-search-' . $htmlId . '" class="screen-reader-text">Search items</label>';
  $output .= '<input id="cmb-search-' . $htmlId . '" type="text" class="cmb-group-search" placeholder="Search items..." />';
  ```
- **Affected Files:**
  - `src/Core/FieldRenderer.php` (line 133)
- **Estimated Effort:** 0.5 hours
- **Priority:** P0
- **Dependencies:** None

---

## FE-004: Fix Duplicate Label on CheckboxField

- **Title:** Checkbox renders its own label AND gets the outer FieldRenderer label
- **Description:** CheckboxField renders `<label><input>Label</label>` inside itself, but FieldRenderer also renders a `<label for="...">` in the `.cmb-label` column. Screen readers announce the label twice.
- **Root Cause:** CheckboxField was designed independently from the shared label rendering.
- **Proposed Solution:**
  Either: suppress the outer label for checkbox type in FieldRenderer, or have CheckboxField render only `<input>` and rely on the outer label.
- **Affected Files:**
  - `src/Fields/CheckboxField.php` (line 12)
  - `src/Core/FieldRenderer.php` (label rendering for checkbox type)
- **Estimated Effort:** 1 hour
- **Priority:** P1
- **Dependencies:** None

---

## FE-005: Fix Color Contrast Issues

- **Title:** Description text and badges fail WCAG AA contrast at small sizes
- **Description:**
  - `.cmb-description`: `#757575` on white = 4.6:1 (barely passes AA, fails AAA at 12px)
  - `.cmb-item-count`: `#787c82` on `#f0f0f1` = ~3.0:1 (fails AA)
  - `.cmb-field-row-type`: `#646970` on `#f0f0f1` = ~4.1:1 (fails AA at 11px)
- **Root Cause:** Colors chosen for aesthetics without contrast verification.
- **Proposed Solution:**
  - Darken `.cmb-description` color to `#595959` (7:1 ratio)
  - Darken `.cmb-item-count` to `#50575e` (4.5:1+ ratio)
  - Increase font size to 13px for small text elements, or darken further
- **Affected Files:**
  - `assets/cmb-style.css` (lines 65-70)
  - `assets/cmb-admin.css` (line 401)
- **Estimated Effort:** 1 hour
- **Priority:** P1
- **Dependencies:** None

---

## FE-006: Update Deprecated Gutenberg API

- **Title:** `wp.editPost.PluginDocumentSettingPanel` deprecated in WP 6.6
- **Description:** The Gutenberg panel uses `wp.editPost` which is deprecated in favor of `wp.editor`. Triggers deprecation warnings in browser console.
- **Root Cause:** Code written before WP 6.6 API changes.
- **Proposed Solution:**
  ```javascript
  var PluginDocumentSettingPanel = (wp.editor && wp.editor.PluginDocumentSettingPanel)
      || wp.editPost.PluginDocumentSettingPanel;
  ```
  Update dependency array from `wp-edit-post` to `wp-editor`.
- **Affected Files:**
  - `assets/cmb-gutenberg.js` (line 5)
  - `src/Core/GutenbergPanel.php` (dependency array, line 59)
- **Estimated Effort:** 1 hour
- **Priority:** P1
- **Dependencies:** None

---

## FE-007: Re-initialize Sortable for Dynamic Groups

- **Title:** Nested groups added dynamically are not sortable
- **Description:** Sortable is initialized once on page load for existing `.cmb-group-items`. Dynamically added group items via "Add Row" won't have sortable on their nested containers.
- **Root Cause:** No re-initialization after DOM changes.
- **Proposed Solution:**
  After cloning a new row, re-initialize sortable on any new `.cmb-group-items` containers within it:
  ```javascript
  $newRow.find('.cmb-group-items').sortable({...});
  ```
  Or use a MutationObserver.
- **Affected Files:**
  - `assets/cmb-script.js` (after row clone, ~line 145)
- **Estimated Effort:** 1 hour
- **Priority:** P1
- **Dependencies:** None

---

## FE-008: Add Client-Side Inline Validation

- **Title:** No JavaScript validation -- errors only shown after page round-trip
- **Description:** PHP `validate()` returns errors, but there's no JS equivalent. Users only see validation errors after form submission. Modern UX expects inline, real-time feedback.
- **Root Cause:** Client-side validation never implemented.
- **Proposed Solution:**
  1. Serialize PHP validation rules to data attributes: `data-validate-required="true"` `data-validate-min="5"` etc.
  2. Add JS validation on `blur` event for each field.
  3. Show inline error messages below fields using `.cmb-field-error` elements.
  4. Prevent form submission if validation errors exist.
- **Affected Files:**
  - `assets/cmb-script.js` (validation module)
  - `assets/cmb-style.css` (error message styling)
  - `src/Core/FieldRenderer.php` (add data-validate attributes)
- **Estimated Effort:** 12 hours
- **Priority:** P1
- **Dependencies:** None

---

## FE-009: Remove Unused postId Selector in Gutenberg

- **Title:** Unused `useSelect` for `postId` causes unnecessary re-renders
- **Description:** `CmbField` component subscribes to `core/editor.getCurrentPostId()` but never uses the value. This causes re-renders on every editor state change.
- **Root Cause:** Development artifact -- postId was likely planned for future use.
- **Proposed Solution:**
  Remove the `useSelect` call for `postId`, or move to parent component if needed later.
- **Affected Files:**
  - `assets/cmb-gutenberg.js` (lines 20-28)
- **Estimated Effort:** 0.5 hours
- **Priority:** P1
- **Dependencies:** None

---

## FE-010: Add Focus Styles for Tab Navigation Links

- **Title:** Tab nav links lack custom focus styles
- **Description:** `.cmb-tab-nav-item a` has no custom `:focus-visible` style. Form inputs use `outline: none` with `box-shadow` replacement, which may not be visible in Windows High Contrast Mode.
- **Root Cause:** Focus styles incomplete.
- **Proposed Solution:**
  ```css
  .cmb-tab-nav-item a:focus-visible {
      outline: 2px solid #2271b1;
      outline-offset: 1px;
  }
  ```
  Use `outline: 2px solid transparent` with `box-shadow` as enhancement for Windows HC Mode.
- **Affected Files:**
  - `assets/cmb-style.css`
  - `assets/cmb-admin.css`
- **Estimated Effort:** 0.5 hours
- **Priority:** P1
- **Dependencies:** None

---

## FE-011: Lower Width Class Breakpoint

- **Title:** Width classes collapse at 1495px -- ineffective on most laptops
- **Description:** `@media (max-width: 1495px)` forces all width classes to 100%. Many laptops are 1440px, meaning width classes are effectively non-functional.
- **Root Cause:** Breakpoint set too high.
- **Proposed Solution:**
  Use two-step collapse:
  ```css
  @media (max-width: 1200px) {
      .cmb-field.w-25, .cmb-field.w-33 { flex: 1 1 50%; }
  }
  @media (max-width: 782px) {
      .cmb-field.w-25, .cmb-field.w-33, .cmb-field.w-50, .cmb-field.w-75 { flex: 1 1 100%; }
  }
  ```
- **Affected Files:**
  - `assets/cmb-style.css` (lines 22-31)
- **Estimated Effort:** 1 hour
- **Priority:** P2
- **Dependencies:** None

---

## FE-012: Extend CSS Custom Properties to Frontend Stylesheet

- **Title:** Admin CSS uses custom properties but frontend uses hardcoded hex values
- **Description:** `cmb-admin.css` uses CSS custom properties (`--cmb-primary`, `--cmb-border`) for theming. `cmb-style.css` uses hardcoded hex values, preventing theme developers from customizing colors.
- **Root Cause:** Frontend CSS written before the admin CSS was modernized.
- **Proposed Solution:**
  Add `:root` custom properties to `cmb-style.css` and replace hardcoded values.
- **Affected Files:**
  - `assets/cmb-style.css`
- **Estimated Effort:** 2 hours
- **Priority:** P2
- **Dependencies:** None

---

## FE-013: Organize Assets into Subdirectories

- **Title:** Flat asset directory mixes admin, frontend, and block editor files
- **Description:** All 5 files are in a flat `assets/` directory.
- **Root Cause:** Simple directory structure from early development.
- **Proposed Solution:**
  ```
  assets/
    admin/cmb-admin.js, cmb-admin.css
    front/cmb-script.js, cmb-style.css
    gutenberg/cmb-gutenberg.js
  ```
  Or use `src/` and `dist/` structure with build pipeline.
- **Affected Files:**
  - `assets/` (reorganize)
  - `src/Core/Plugin.php` (update paths)
  - `src/Core/AdminUI.php` (update paths)
  - `src/Core/GutenbergPanel.php` (update paths)
- **Estimated Effort:** 2 hours
- **Priority:** P2
- **Dependencies:** PERF-008

---

## FE-014: Add Error Handling in JavaScript

- **Title:** Silent failures in JS -- no user feedback on errors
- **Description:**
  - File upload silently returns if `wp.media` is undefined
  - `navigator.clipboard.writeText()` has no `.catch()` handler
  - Gutenberg `useSelect` has no error boundary
- **Root Cause:** Error handling not prioritized.
- **Proposed Solution:**
  1. Show admin notice or inline alert when `wp.media` is unavailable.
  2. Add `.catch()` with fallback `document.execCommand('copy')` for clipboard.
  3. Add React error boundary around Gutenberg panel.
- **Affected Files:**
  - `assets/cmb-script.js` (line 189)
  - `assets/cmb-admin.js` (line 249)
  - `assets/cmb-gutenberg.js` (wrap in error boundary)
- **Estimated Effort:** 3 hours
- **Priority:** P2
- **Dependencies:** None

---

## FE-015: Standardize JavaScript ES Version

- **Title:** Mixed ES5/ES6 syntax across JS files
- **Description:** `cmb-script.js` uses `const`, `let`, arrow functions. `cmb-admin.js` uses `var` throughout. `cmb-gutenberg.js` uses `var`. Inconsistent approach.
- **Root Cause:** Files written at different times without style guide.
- **Proposed Solution:**
  Standardize on ES6+ across all files. If legacy browser support is needed, add Babel transpilation via build pipeline.
- **Affected Files:**
  - `assets/cmb-admin.js`
  - `assets/cmb-gutenberg.js`
- **Estimated Effort:** 4 hours
- **Priority:** P2
- **Dependencies:** PERF-008

---

## Summary

| ID | Title | Priority | Effort (hrs) |
|----|-------|----------|--------------|
| FE-001 | Tab ARIA roles | P0 | 4 |
| FE-002 | Fieldset legends | P0 | 1 |
| FE-003 | Search input label | P0 | 0.5 |
| FE-004 | Checkbox duplicate label | P1 | 1 |
| FE-005 | Color contrast fixes | P1 | 1 |
| FE-006 | Update deprecated Gutenberg API | P1 | 1 |
| FE-007 | Re-init sortable for dynamic groups | P1 | 1 |
| FE-008 | Client-side validation | P1 | 12 |
| FE-009 | Remove unused postId selector | P1 | 0.5 |
| FE-010 | Tab focus styles | P1 | 0.5 |
| FE-011 | Lower width breakpoint | P2 | 1 |
| FE-012 | CSS custom properties for frontend | P2 | 2 |
| FE-013 | Organize asset directories | P2 | 2 |
| FE-014 | JS error handling | P2 | 3 |
| FE-015 | Standardize ES version | P2 | 4 |
| **Total** | | | **34.5** |
