# Frontend (JS/CSS/UI/UX) Audit Report

**Plugin:** Custom Meta Box Builder v2.0  
**Audit Date:** 2026-04-05  
**Auditor:** Agent 5 - Frontend Auditor  
**Scope:** JavaScript architecture, CSS structure, accessibility, Gutenberg integration, responsive design, browser compatibility, UI/UX quality

---

## 1. Executive Summary

The Custom Meta Box Builder demonstrates a **solid foundational frontend implementation** with well-organized CSS, functional JavaScript, and a thoughtful admin UI. The plugin ships raw unminified assets (no build pipeline), follows WordPress admin conventions reasonably well, and provides a polished visual builder experience.

**Key Strengths:**
- Clean CSS architecture with CSS custom properties in the admin stylesheet
- Proper use of WordPress UI patterns (postbox, WP List Table styling)
- Functional Gutenberg sidebar integration using `PluginDocumentSettingPanel`
- Good UX touches: type picker modal, auto-generated IDs, unsaved changes warning, sortable fields
- Responsive breakpoints for both admin builder and front-end meta boxes
- Print stylesheet included

**Critical Gaps:**
- No build/minification pipeline -- raw JS/CSS shipped to production
- Several WCAG 2.1 AA compliance failures (missing ARIA roles, inadequate keyboard navigation, color contrast issues)
- Gutenberg integration is limited to simple field types; groups/repeaters/files excluded
- XSS vector in file upload preview HTML construction
- jQuery-coupled architecture with no modern JS module system
- No internationalization (i18n) in JavaScript strings

**Overall Grade: B-** -- Functional and visually polished, but needs accessibility hardening, a build pipeline, and expanded Gutenberg coverage to match industry standards.

---

## 2. Detailed Findings

### 2.1 JavaScript Architecture

#### 2.1.1 Module Structure -- No Build Pipeline

| Severity | **High** |
|----------|----------|
| Files | All `assets/*.js` files |

The plugin ships three raw JavaScript files with no bundling, transpilation, or minification:
- `cmb-admin.js` (~797 lines) -- Admin builder UI
- `cmb-script.js` (~448 lines) -- Front-end meta box interactions
- `cmb-gutenberg.js` (~127 lines) -- Block editor panel

**Issues:**
- No `package.json`, `webpack.config.js`, or any build tooling
- No minified `.min.js` variants -- full source served in production
- No source maps for debugging
- No tree-shaking or dead code elimination
- All three files use IIFE patterns, which is fine for WP but prevents code sharing

**Recommendation:** Introduce a minimal build pipeline (e.g., `@wordpress/scripts`) to produce minified, versioned assets. ACF, CMB2, and Meta Box all ship minified assets with source maps.

#### 2.1.2 jQuery Dependency

| Severity | **Medium** |
|----------|----------|
| Files | `assets/cmb-admin.js:6`, `assets/cmb-script.js:1` |

Both `cmb-admin.js` and `cmb-script.js` depend entirely on jQuery. While WordPress still bundles jQuery, the ecosystem is moving toward vanilla JS (Gutenberg uses React). The Gutenberg file correctly uses vanilla JS / `wp.element`.

**Impact:** Increases page weight on front-end if jQuery is not otherwise loaded. Creates a hard dependency on a library WordPress may eventually decouple.

#### 2.1.3 Global State Management

| Severity | **Low** |
|----------|----------|
| Files | `assets/cmb-admin.js:8-9`, `assets/cmb-script.js:361` |

State is managed via closure-scoped variables (`fieldIndex`, `formDirty`, `cmbFormDirty`). This is acceptable for the plugin's scope but:
- `cmb-script.js` line 361: `cmbFormDirty` is a simple boolean -- no per-form tracking if multiple meta boxes exist
- `cmb-admin.js` line 8: `fieldIndex` is global to the IIFE -- correct for single-page admin

#### 2.1.4 Event Delegation Pattern

| Severity | **Low** (Positive Finding) |
|----------|----------|
| Files | `assets/cmb-script.js`, `assets/cmb-admin.js` |

Both files consistently use `$(document).on('event', 'selector', handler)` for event delegation. This is the correct pattern for dynamically created elements (cloned rows, added fields). Well done.

#### 2.1.5 XSS Risk in File Upload Preview

| Severity | **Critical** |
|----------|----------|
| File | `assets/cmb-script.js:203-206` |

```javascript
$preview.html('<img src="' + attachment.sizes.thumbnail.url + '" ...>');
// and
$preview.html('<a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a>');
```

The `attachment.url` and `attachment.filename` values are injected into HTML without escaping. While these come from the WP media library (trusted source), this pattern is still vulnerable if:
- A compromised media library returns malicious URLs
- The pattern is copied elsewhere with untrusted data

**Recommendation:** Use DOM construction instead:
```javascript
$preview.empty().append($('<img>').attr('src', url).css({maxWidth:'150px'}));
```

#### 2.1.6 Missing Error Handling

| Severity | **Medium** |
|----------|----------|
| Files | `assets/cmb-script.js:189-192`, `assets/cmb-admin.js:249-259` |

- `cmb-script.js:189`: Silently returns if `wp.media` is undefined -- no user feedback
- `cmb-admin.js:249`: `navigator.clipboard.writeText()` has a `.then()` but no `.catch()` -- clipboard failures silently ignored
- `cmb-gutenberg.js:20-28`: No error boundary around `useSelect` calls -- if store is unavailable, component will crash

**Recommendation:** Add user-visible error messages (WordPress admin notices or inline alerts) when operations fail.

#### 2.1.7 Sortable Initialization Timing

| Severity | **Medium** |
|----------|----------|
| File | `assets/cmb-script.js:225-247` |

Sortable is initialized on page load for existing `.cmb-group-items` elements. However, dynamically added group items (via "Add Row") will NOT have sortable enabled on their nested `.cmb-group-items` containers because `sortable()` is only called once at initialization.

```javascript
// Line 226 - only runs once on page load
$('.cmb-group-items').sortable({...});
```

**Impact:** Nested groups added dynamically will not be sortable.

**Recommendation:** Re-initialize sortable after adding new rows, or use a MutationObserver.

#### 2.1.8 No Debouncing on Conditional Evaluation

| Severity | **Medium** |
|----------|----------|
| File | `assets/cmb-script.js:305-307` |

```javascript
$(document).on('input change', '.cmb-container :input, .cmb-tab-panel :input', function() {
    evaluateConditionals();
});
```

Every keystroke triggers `evaluateConditionals()`, which iterates ALL `[data-conditional-field]` elements and performs DOM lookups. On forms with many conditional fields, this causes layout thrashing.

**Recommendation:** Debounce to 100-150ms or scope evaluation to only conditionals related to the changed field.

---

### 2.2 CSS Structure and Maintainability

#### 2.2.1 CSS Custom Properties (Admin Only)

| Severity | **Low** |
|----------|----------|
| Files | `assets/cmb-admin.css:7-25` vs `assets/cmb-style.css` |

The admin CSS uses CSS custom properties (`--cmb-primary`, `--cmb-border`, etc.) -- excellent for maintainability and theming. However, `cmb-style.css` (front-end) uses hardcoded hex values throughout.

**Recommendation:** Extend CSS custom properties to `cmb-style.css` for consistency, and to allow theme developers to override colors.

#### 2.2.2 Specificity and `!important` Usage

| Severity | **Low** |
|----------|----------|
| Files | `assets/cmb-style.css:292-303`, `assets/cmb-admin.css:777-814` |

There are 8 uses of `!important` in `cmb-style.css` and 6 in `cmb-admin.css`. Most are justified:
- `.cmb-search-hidden { display: none !important; }` -- must override inline styles (line 562)
- `.cmb-remove-row:hover` background/color overrides (lines 296-297)
- Admin button overrides to fight WordPress defaults (lines 797-813)

These are acceptable within WordPress admin context where fighting WP core styles is common.

#### 2.2.3 Asset Versioning

| Severity | **High** |
|----------|----------|
| File | `src/Core/AdminUI.php:40-41` |

```php
wp_enqueue_style('cmb-admin', $baseUrl . 'assets/cmb-admin.css', [], '2.0.0');
wp_enqueue_script('cmb-admin', $baseUrl . 'assets/cmb-admin.js', ['jquery', 'jquery-ui-sortable'], '2.0.0', true);
```

Static version `'2.0.0'` means browser caches won't bust on code changes unless the version is manually incremented. Compare with GutenbergPanel:

```php
// src/Core/GutenbergPanel.php:56-62
wp_enqueue_script('cmb-gutenberg-panel', ..., null, true);
```

The Gutenberg script uses `null` for version, which disables versioning entirely -- WordPress will add its own version, but updates to the file won't bust cache.

**Recommendation:** Use `filemtime()` for cache busting during development, or a build-time hash.

#### 2.2.4 Front-End CSS Enqueue Location

| Severity | **Medium** |
|----------|----------|
| File | `src/Core/AdminUI.php` |

The `cmb-style.css` and `cmb-script.js` front-end assets are not enqueued from `AdminUI.php`. They must be enqueued elsewhere (likely `MetaBoxManager` or `Plugin.php`). Without seeing the enqueue logic for these files, I note:

- `cmb-style.css` is 685 lines unminified -- this is loaded on every post edit screen
- No conditional loading based on whether meta boxes actually exist on the current screen

#### 2.2.5 Print Stylesheet

| Severity | **Low** (Positive Finding) |
|----------|----------|
| File | `assets/cmb-style.css:631-650` |

A print media query is included that hides interactive elements (add buttons, sort handles, actions) and forces all panels/tabs open. This is a nice touch that most competing plugins lack.

---

### 2.3 Accessibility (a11y) - WCAG 2.1 Compliance

#### 2.3.1 Group Item Header -- Good ARIA Pattern

| Severity | **Low** (Positive Finding) |
|----------|----------|
| File | `src/Fields/GroupField.php:52` |

```html
<div class="cmb-group-item-header" role="button" tabindex="0"
     aria-expanded="true/false">
```

The group headers correctly implement:
- `role="button"` for semantic meaning
- `tabindex="0"` for keyboard focusability
- `aria-expanded` state tracking
- Keyboard handler for Enter/Space (`cmb-script.js:155-165`)

This matches WCAG 2.1 SC 4.1.2 (Name, Role, Value).

#### 2.3.2 Missing ARIA on Tab Components

| Severity | **High** |
|----------|----------|
| Files | `assets/cmb-style.css:426-475`, `assets/cmb-script.js:310-322` |

The tab components (`.cmb-tab-nav`, `.cmb-tab-panel`) lack ARIA roles:

**Required ARIA attributes (WCAG 4.1.2):**
```html
<ul class="cmb-tab-nav" role="tablist">
  <li class="cmb-tab-nav-item" role="presentation">
    <a role="tab" aria-selected="true" aria-controls="panel-id" id="tab-id">
  </li>
</ul>
<div class="cmb-tab-panel" role="tabpanel" aria-labelledby="tab-id" id="panel-id">
```

**Missing keyboard navigation (WCAG 2.1.1):**
- No Arrow key navigation between tabs
- No Home/End key support
- Tab panels not connected via `aria-controls`/`aria-labelledby`

The same issue applies to language tabs (`.cmb-lang-tab-nav`).

#### 2.3.3 Missing ARIA Labels on Interactive Elements

| Severity | **High** |
|----------|----------|
| Files | Multiple |

Several interactive elements lack accessible names:

| Element | File | Line | Issue |
|---------|------|------|-------|
| `.cmb-add-row` | `src/Core/FieldRenderer.php` | 145 | No `aria-label`; text content "Add Row" is visible but button is a `<span>` not `<button>` |
| `.cmb-expand-all` / `.cmb-collapse-all` | `src/Core/FieldRenderer.php` | 126-127 | `<a href="#">` used as buttons -- should be `<button>` with `type="button"` |
| `.cmb-file-upload` | `src/Fields/FileField.php` | 33 | Has visible text "Select File" -- acceptable |
| `.cmb-load-more` | `assets/cmb-script.js` | 413 | Dynamically created as `<span>` -- should be `<button>` |
| `.cmb-group-search input` | `src/Core/FieldRenderer.php` | 133 | No `<label>` element; placeholder-only labeling fails WCAG 1.3.1 |
| Toggle indicator | `src/Fields/GroupField.php` | 54 | Correctly marked `aria-hidden="true"` |

#### 2.3.4 Semantic Element Misuse

| Severity | **High** |
|----------|----------|
| Files | `src/Core/FieldRenderer.php:145`, `assets/cmb-script.js:413` |

- `<span class="cmb-add-row">` -- a clickable "button" rendered as `<span>`. Not focusable by default, not announced as a button by screen readers, not activatable via keyboard.
- `<span class="cmb-load-more">` -- same issue, dynamically created in JS.
- `<a href="#" class="cmb-expand-all">` -- anchor used as button. Should be `<button type="button">`.

**Impact:** These elements are invisible to keyboard-only users and screen readers unless they happen to tab to them (which they won't, since `<span>` has no tabindex).

**WCAG failures:** 1.3.1 (Info and Relationships), 2.1.1 (Keyboard), 4.1.2 (Name, Role, Value)

#### 2.3.5 Color Contrast Issues

| Severity | **Medium** |
|----------|----------|
| File | `assets/cmb-style.css:65-70` |

```css
.cmb-field p.cmb-description {
    color: #757575;  /* on white background */
    font-size: 12px;
}
```

- `#757575` on `#ffffff` = 4.6:1 contrast ratio -- passes AA for normal text (4.5:1) but barely
- At 12px font size, this should meet AAA (7:1) for small text -- it fails
- `.cmb-item-count` uses `#787c82` on `#f0f0f1` = ~3.0:1 -- **fails AA** for small text

Similar concern in `cmb-admin.css`:
- `.cmb-field-row-type` (line 401): `var(--cmb-text-secondary)` (#646970) on `var(--cmb-bg)` (#f0f0f1) = ~4.1:1 -- **fails AA** for 11px text

#### 2.3.6 Focus Indicators

| Severity | **Medium** |
|----------|----------|
| File | `assets/cmb-style.css:407-415` |

```css
.cmb-add-row:focus-visible,
.cmb-group-item-actions button:focus-visible,
.cmb-group-item-header:focus-visible,
.cmb-group-actions a:focus-visible {
    outline: 2px solid #2271b1;
    outline-offset: 1px;
}
```

Focus styles are defined for key interactive elements -- this is good. However:
- Tab nav links (`.cmb-tab-nav-item a`) have no custom focus style
- Form inputs use `outline: none` on focus (line 131) but replace with `box-shadow` -- this may not be visible in Windows High Contrast Mode

**Recommendation:** Keep `outline` visible or use `outline: 2px solid transparent` with `box-shadow` as enhancement (transparent outline ensures Windows High Contrast Mode visibility).

#### 2.3.7 Confirm Dialogs Use Native `confirm()`

| Severity | **Low** |
|----------|----------|
| Files | `assets/cmb-script.js:128`, `assets/cmb-admin.js:72` |

Native `confirm()` is accessible (browser-managed), though it blocks the UI thread and cannot be styled. This is acceptable for a WordPress admin plugin.

#### 2.3.8 Checkbox Field Duplicates Label

| Severity | **Medium** |
|----------|----------|
| File | `src/Fields/CheckboxField.php:12` |

```php
return '<label><input type="checkbox" ... />' . esc_html($this->getLabel()) . '</label>';
```

The checkbox field renders its own label text inside the `<label>` wrapper. But `FieldRenderer.php:116` also renders a label in `.cmb-label`:

```php
$output .= '<label for="' . esc_attr($htmlId) . '">' . esc_html($field['label'] ?? '');
```

This means the checkbox has **two labels** -- one in the standard label column and one wrapping the checkbox. The `for` attribute on the outer label may point to a different element. Screen readers will announce the label twice.

**Recommendation:** Have `CheckboxField` render only the `<input>` and rely on the outer `<label for="...">` for labeling, OR suppress the outer label for checkbox types.

#### 2.3.9 Radio/Taxonomy Fieldsets Missing Legend

| Severity | **Medium** |
|----------|----------|
| Files | `src/Fields/RadioField.php:11`, `src/Fields/TaxonomyField.php:40` |

```php
$output = '<fieldset class="cmb-radio-group">';
// No <legend> element
```

`<fieldset>` without `<legend>` fails WCAG 1.3.1. The field label exists in the outer `.cmb-label` div, but screen readers expect `<legend>` inside `<fieldset>`.

**Recommendation:** Add `<legend class="screen-reader-text">` with the field label text, or use `aria-labelledby` pointing to the outer label's `id`.

---

### 2.4 Gutenberg / Block Editor Integration

#### 2.4.1 Limited Field Type Support

| Severity | **High** |
|----------|----------|
| File | `assets/cmb-gutenberg.js:38-104` |

The Gutenberg panel only supports:
- `text` (default)
- `textarea`
- `select`
- `checkbox`
- `number`
- `email`
- `url`

**Missing from Gutenberg:**
- `file` -- no media upload component
- `date` -- no `DatePicker` component
- `color` -- no `ColorPicker` component
- `radio` -- no `RadioControl` component
- `wysiwyg` -- no `RichText` component
- `group` -- no repeater support
- `post` / `taxonomy` / `user` -- no relationship fields

ACF and Meta Box both provide full Gutenberg sidebar parity for most field types.

#### 2.4.2 Deprecated `wp.editPost` API Usage

| Severity | **High** |
|----------|----------|
| File | `assets/cmb-gutenberg.js:5` |

```javascript
var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
```

`wp.editPost` is deprecated in favor of `wp.editor` as of WordPress 6.6. The `PluginDocumentSettingPanel` has been moved to `wp.editor`. The dependency array in `GutenbergPanel.php:59` lists `wp-edit-post` which will still work but triggers deprecation warnings.

**Recommendation:** Update to:
```javascript
var PluginDocumentSettingPanel = wp.editor?.PluginDocumentSettingPanel || wp.editPost.PluginDocumentSettingPanel;
```

#### 2.4.3 No React Error Boundaries

| Severity | **Medium** |
|----------|----------|
| File | `assets/cmb-gutenberg.js:18-105` |

The `CmbField` component has no error boundary. If `useSelect` returns unexpected data or a field config is malformed, the entire sidebar panel crashes with a white screen.

#### 2.4.4 Re-render Performance

| Severity | **Medium** |
|----------|----------|
| File | `assets/cmb-gutenberg.js:20-28` |

```javascript
var postId = useSelect(function(select) {
    return select('core/editor').getCurrentPostId();
});
```

`postId` is fetched in every `CmbField` instance but never used -- it's a wasted subscription that causes re-renders whenever the editor state changes.

**Recommendation:** Remove the unused `postId` selector, or move it to the parent component if needed.

#### 2.4.5 Gutenberg Asset Version is `null`

| Severity | **Medium** |
|----------|----------|
| File | `src/Core/GutenbergPanel.php:62` |

```php
wp_enqueue_script('cmb-gutenberg-panel', ..., null, true);
```

Version set to `null` means WordPress will append its own version query string. File changes won't be reflected in cache until WP itself updates.

---

### 2.5 UI/UX Quality

#### 2.5.1 Admin Builder -- Excellent Visual Design

| Severity | **Low** (Positive Finding) |
|----------|----------|
| File | `assets/cmb-admin.css` |

The admin builder UI is polished:
- Clean type picker modal with search, categorized grid, and smooth animation
- Field rows with drag handles, inline metadata (type badge, ID, required badge)
- Two-column settings layout with sidebar postboxes
- Toggle switch for active/inactive state
- Import/export modals with file drag-and-drop styling
- Code generation panel with dark theme preview

This is on par with ACF's field group editor quality.

#### 2.5.2 Front-End Meta Box -- Good But Improvable

| Severity | **Low** |
|----------|----------|
| File | `assets/cmb-style.css` |

The front-end meta boxes have:
- Clean horizontal label/input layout with proper border treatment
- Width classes (25/33/50/75/100%) with responsive fallback
- Nested group visual hierarchy via gradient differentiation
- Empty state with dashed border
- Sortable placeholder styling

**Missing compared to ACF:**
- No field-level validation feedback (inline error messages below fields)
- No loading/saving state indicators
- No character count for text fields
- No preview for color fields (only native color picker)

#### 2.5.3 No Client-Side Validation Feedback

| Severity | **High** |
|----------|----------|
| Files | `assets/cmb-style.css:418-423`, `src/Core/Contracts/Abstracts/AbstractField.php:48-102` |

The CSS has validation styling:
```css
.cmb-field.cmb-required input:invalid {
    border-color: #cc1818;
    box-shadow: 0 0 0 1px #cc1818;
}
```

But this only uses browser-native `:invalid` pseudo-class. The PHP `validate()` method (AbstractField.php) returns error messages, but there is **no JavaScript equivalent** to show these errors inline before form submission.

**Impact:** Users only see validation errors after a full page round-trip. Modern UX expects inline, real-time validation.

#### 2.5.4 Unsaved Changes Warning

| Severity | **Low** (Positive Finding) |
|----------|----------|
| Files | `assets/cmb-script.js:361-372`, `assets/cmb-admin.js:263-276` |

Both the front-end meta boxes and admin builder implement `beforeunload` warnings for unsaved changes. The form submit handler correctly resets the dirty flag.

#### 2.5.5 Lazy Loading for Large Repeaters

| Severity | **Low** (Positive Finding) |
|----------|----------|
| File | `assets/cmb-script.js:406-433` |

The "Load more" pattern for repeaters with >20 items is a good performance optimization. The implementation is simple and effective.

---

### 2.6 Responsive Design

#### 2.6.1 Front-End Meta Box Responsive Behavior

| Severity | **Low** (Positive Finding) |
|----------|----------|
| File | `assets/cmb-style.css:653-684` |

At `max-width: 782px`:
- Label/input stacks vertically
- Width classes reset to 100%
- Group index column shrinks
- Tab nav wraps

This breakpoint matches WordPress admin's mobile breakpoint. Good alignment.

#### 2.6.2 Admin Builder Responsive Behavior

| Severity | **Low** (Positive Finding) |
|----------|----------|
| File | `assets/cmb-admin.css:1298-1345` |

At `max-width: 960px`:
- Two-column layout collapses to single column
- Settings grid goes to 1 column
- Type picker grid adjusts

At `max-width: 600px`:
- Header stacks
- Field metadata hidden (saves space)
- Action buttons always visible (no hover dependency)

#### 2.6.3 Width Classes Breakpoint May Be Too Aggressive

| Severity | **Low** |
|----------|----------|
| File | `assets/cmb-style.css:22-31` |

```css
@media (max-width: 1495px) {
    .cmb-field.w-25, .cmb-field.w-33, .cmb-field.w-50, .cmb-field.w-75 {
        flex: 1 1 100%;
    }
}
```

1495px is a high breakpoint to force all fields to 100% width. Many laptop screens are 1440px wide, meaning the width classes are effectively non-functional on most laptops.

**Recommendation:** Lower to ~1200px or ~1024px, or use a two-step collapse (e.g., w-25/w-33 to 50% at 1200px, then 100% at 782px).

---

### 2.7 Browser Compatibility

#### 2.7.1 CSS `has()` Selector

| Severity | **Medium** |
|----------|----------|
| File | `assets/cmb-admin.css:545-547` |

```css
.cmb-field-settings-grid:has(.cmb-fs-third) {
    grid-template-columns: 1fr 1fr 1fr;
}
```

`:has()` is supported in Chrome 105+, Safari 15.4+, Firefox 121+. Older browsers (Firefox pre-121, released Dec 2023) will ignore this rule, causing 1/3-width fields to render at 1/2-width instead. This is a graceful degradation scenario but worth noting.

#### 2.7.2 CSS Custom Properties

| Severity | **Low** |
|----------|----------|
| File | `assets/cmb-admin.css:7-25` |

CSS custom properties require IE11+ (which doesn't support them). Since WordPress dropped IE11 support in WP 5.8, this is acceptable.

#### 2.7.3 `navigator.clipboard` API

| Severity | **Low** |
|----------|----------|
| File | `assets/cmb-admin.js:251-259` |

The Clipboard API requires HTTPS and user gesture. The `if (navigator.clipboard)` guard is correct, but there's no fallback (e.g., `document.execCommand('copy')`) for HTTP environments.

#### 2.7.4 ES6 Features Without Transpilation

| Severity | **Medium** |
|----------|----------|
| File | `assets/cmb-script.js` |

The front-end script uses `const`, `let`, arrow functions, and template literals. While these are widely supported, the admin script (`cmb-admin.js`) uses `var` throughout -- inconsistent approach.

Without a Babel transpilation step, the ES6 features in `cmb-script.js` will fail in any legacy browser that a WordPress site might target.

---

### 2.8 Internationalization (i18n)

#### 2.8.1 Hardcoded English Strings in JavaScript

| Severity | **High** |
|----------|----------|
| Files | `assets/cmb-script.js`, `assets/cmb-admin.js` |

All user-facing strings are hardcoded in English:

| String | File | Line |
|--------|------|------|
| `'Remove this item?'` | cmb-script.js | 128 |
| `'No items yet. Click "Add Row" to add one.'` | cmb-script.js | 149 |
| `'Load more (X remaining)'` | cmb-script.js | 413, 429 |
| `'Remove this field?'` | cmb-admin.js | 72 |
| `'New Field'` | cmb-admin.js | 108 |
| `'Select File'` | cmb-script.js | 194 |
| `'You have unsaved changes...'` | cmb-script.js | 370 |
| `'Copied!'` | cmb-admin.js | 255 |
| `' item'` / `' items'` | cmb-script.js | 443 |

**Recommendation:** Use `wp_localize_script()` to pass translated strings, or use `wp.i18n.__()` for Gutenberg context. ACF and CMB2 both internationalize their JavaScript strings.

---

### 2.9 Asset Organization

#### 2.9.1 Flat Asset Directory

| Severity | **Low** |
|----------|----------|
| Directory | `assets/` |

All assets are in a single flat `assets/` directory with no separation between:
- Admin-only files (`cmb-admin.js`, `cmb-admin.css`)
- Front-end files (`cmb-script.js`, `cmb-style.css`)
- Block editor files (`cmb-gutenberg.js`)

**Recommendation:** Consider organizing as:
```
assets/
  admin/
    cmb-admin.js
    cmb-admin.css
  front/
    cmb-script.js
    cmb-style.css
  gutenberg/
    cmb-gutenberg.js
```

Or better yet, use `src/` for source and `dist/` or `build/` for compiled assets.

---

## 3. WCAG 2.1 Compliance Gap Summary

| WCAG Criterion | Status | Details |
|----------------|--------|---------|
| **1.1.1** Non-text Content | PASS | Images have context; decorative icons use CSS |
| **1.3.1** Info and Relationships | FAIL | Missing `<legend>` in fieldsets; tabs lack ARIA roles; `<span>` used as buttons |
| **1.3.5** Identify Input Purpose | PASS | Inputs have appropriate `type` attributes |
| **1.4.1** Use of Color | PASS | Required fields use both color and asterisk |
| **1.4.3** Contrast (Minimum) | FAIL | Description text and badges below 4.5:1 at small sizes |
| **1.4.11** Non-text Contrast | PASS | Borders and focus indicators meet 3:1 |
| **2.1.1** Keyboard | FAIL | `<span>` buttons not keyboard accessible; tabs lack arrow navigation |
| **2.1.2** No Keyboard Trap | PASS | Modal closes on ESC |
| **2.4.3** Focus Order | PASS | Natural DOM order matches visual order |
| **2.4.7** Focus Visible | PARTIAL | Focus styles defined for some elements but not all interactive elements |
| **2.5.2** Pointer Cancellation | PASS | Click events use standard patterns |
| **3.2.2** On Input | PASS | No unexpected context changes on input |
| **3.3.1** Error Identification | FAIL | No client-side validation error messages |
| **3.3.2** Labels or Instructions | PARTIAL | Most fields labeled; search input lacks label |
| **4.1.2** Name, Role, Value | FAIL | Tabs missing role/state; `<span>` buttons missing role |

---

## 4. Comparison with ACF / CMB2 / Meta Box

| Feature | Custom Meta Box Builder | ACF (Free) | CMB2 | Meta Box |
|---------|----------------------|------------|------|----------|
| **Build Pipeline** | None | Webpack | Grunt | Webpack |
| **Minified Assets** | No | Yes | Yes | Yes |
| **Gutenberg Panel** | Basic (7 types) | Full sidebar | No | Full sidebar |
| **ARIA/a11y** | Partial | Good | Minimal | Good |
| **i18n (JS)** | No | Yes (POT) | Yes (POT) | Yes (POT) |
| **Client Validation** | Browser-native only | Inline errors | None | Inline errors |
| **Repeater Sorting** | Yes (jQuery UI) | Yes (jQuery UI) | Yes (jQuery UI) | Yes (jQuery UI) |
| **Conditional Logic** | Yes (5 operators) | Yes (extensive) | No (add-on) | Yes (extensive) |
| **Visual Builder** | Yes (excellent) | Yes (standard) | No | Yes |
| **File Upload Preview** | Basic | Full gallery | Basic | Full gallery |
| **Color Picker** | Native `<input>` | wp-color-picker | wp-color-picker | wp-color-picker |
| **Print Styles** | Yes | No | No | No |
| **Lazy Load Repeaters** | Yes (threshold 20) | No | No | No |
| **Multi-language** | Yes (built-in) | No (WPML add-on) | No | No |

**Verdict:** The plugin's admin builder UI and feature set (multilingual, lazy loading, conditional logic) are competitive. The main gaps vs. competitors are: no build pipeline, limited Gutenberg coverage, and accessibility shortcomings.

---

## 5. Prioritized Recommendations

### Critical (Fix Immediately)
1. **Fix XSS in file upload preview** -- Use DOM construction instead of string HTML injection (`cmb-script.js:203-206`)
2. **Change `<span class="cmb-add-row">` to `<button type="button">`** -- Affects all meta boxes (`FieldRenderer.php:145`)
3. **Add ARIA roles to tab components** -- `role="tablist"`, `role="tab"`, `role="tabpanel"`, `aria-selected`, `aria-controls` (`cmb-script.js:310-322`, `FieldRenderer.php`)

### High Priority
4. **Add build pipeline** -- Minify JS/CSS, add source maps, use `@wordpress/scripts`
5. **Expand Gutenberg field types** -- At minimum add `date`, `color`, `radio`, `file`
6. **Update deprecated `wp.editPost`** -- Migrate to `wp.editor.PluginDocumentSettingPanel`
7. **Internationalize JS strings** -- Use `wp_localize_script()` for translatable strings
8. **Add `<legend>` to `<fieldset>` elements** -- `RadioField.php:11`, `TaxonomyField.php:40`
9. **Add `<label>` to group search input** -- Use `screen-reader-text` class for visual hiding
10. **Fix asset versioning** -- Use `filemtime()` for cache busting

### Medium Priority
11. **Fix sortable initialization for dynamic groups** -- Re-init after "Add Row"
12. **Debounce conditional evaluation** -- Add 100ms debounce
13. **Add client-side inline validation** -- Mirror PHP validation rules in JS
14. **Fix color contrast** -- Darken description text and badge colors
15. **Add clipboard API fallback** -- Use `execCommand('copy')` as fallback
16. **Remove unused `postId` in Gutenberg** -- Performance improvement
17. **Add focus styles for tab nav links** -- Ensure `:focus-visible` coverage
18. **Lower width-class breakpoint** -- From 1495px to ~1024px

### Low Priority
19. **Extend CSS custom properties to front-end stylesheet**
20. **Organize assets into subdirectories**
21. **Fix duplicate label on checkbox fields**
22. **Standardize ES syntax** -- Either all ES6 or all ES5 (currently mixed)
23. **Add loading/saving indicators** -- Spinner or progress feedback

---

## 6. Files Reviewed

| File | Lines | Purpose |
|------|-------|---------|
| `custom-meta-box-builder.php` | 16 | Plugin bootstrap |
| `assets/cmb-admin.js` | 797 | Admin builder JavaScript |
| `assets/cmb-script.js` | 448 | Front-end meta box JavaScript |
| `assets/cmb-gutenberg.js` | 127 | Gutenberg sidebar panel |
| `assets/cmb-admin.css` | 1346 | Admin builder styles |
| `assets/cmb-style.css` | 685 | Front-end meta box styles |
| `src/Core/AdminUI.php` | ~600 | Admin page rendering + asset enqueue |
| `src/Core/GutenbergPanel.php` | 78 | Gutenberg asset registration |
| `src/Core/FieldRenderer.php` | 238 | Field HTML rendering |
| `src/Core/Contracts/Abstracts/AbstractField.php` | 124 | Base field class |
| `src/Fields/TextField.php` | 34 | Text input |
| `src/Fields/TextareaField.php` | 20 | Textarea input |
| `src/Fields/CheckboxField.php` | 21 | Checkbox input |
| `src/Fields/SelectField.php` | 28 | Select dropdown |
| `src/Fields/NumberField.php` | 27 | Number input |
| `src/Fields/EmailField.php` | 20 | Email input |
| `src/Fields/UrlField.php` | 20 | URL input |
| `src/Fields/RadioField.php` | 32 | Radio buttons |
| `src/Fields/HiddenField.php` | 18 | Hidden input |
| `src/Fields/PasswordField.php` | 20 | Password input |
| `src/Fields/DateField.php` | 26 | Date/datetime input |
| `src/Fields/ColorField.php` | 20 | Color picker |
| `src/Fields/WysiwygField.php` | 37 | WYSIWYG editor |
| `src/Fields/FileField.php` | 46 | File upload |
| `src/Fields/PostField.php` | 45 | Post relationship |
| `src/Fields/TaxonomyField.php` | 59 | Taxonomy checklist/select |
| `src/Fields/UserField.php` | 39 | User select |
| `src/Fields/GroupField.php` | 98 | Repeater/group field |

---

*End of Frontend Audit Report*
