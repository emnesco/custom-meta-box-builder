# Frontend, Accessibility & UI/UX Audit Report

**Plugin:** Custom Meta Box Builder v2.1
**Audit Date:** 2026-04-06 (Re-audit)
**Previous Audit:** 2026-04-05 (v2.0)
**Auditor:** Frontend & Accessibility Agent
**Scope:** JavaScript, CSS, HTML output, ARIA, WCAG 2.1 AA compliance, Gutenberg integration

---

## 1. Executive Summary

**Overall Rating: B** (improved from B- in v2.0)
**WCAG 2.1 AA Compliance: PARTIAL FAIL** (improved but not fully passing)

The v2.1 codebase shows substantial frontend improvements:

**Resolved from v2.0:**
- ARIA tab roles with arrow/Home/End keyboard navigation (FE-001)
- Fieldset legends with screen-reader-text (FE-002, FE-003)
- Hidden input fallback for unchecked checkboxes (FE-004)
- Color contrast improved to WCAG AA (#595959, #50575e) (FE-005)
- Gutenberg panel updated to `wp.editor` with `wp.editPost` fallback (FE-006)
- Sortable + color picker re-init on cloned rows (FE-007)
- Client-side validation with blur handler (FE-008)
- Tab focus-visible styles (FE-010)
- Two-step responsive breakpoints (1200px, 782px) (FE-011)
- CSS custom properties for theming (FE-012)
- `wp.media` alert when unavailable (FE-014)
- ES6+ standardization across all JS files (FE-015)
- Build pipeline with esbuild for minification (DX-001)
- New Gutenberg field mappings: radio, color, date, toggle, file/image (FEAT-016)

**Remaining concerns:**
- Missing ARIA attributes on new v2.1 features (FlexibleContent, language tabs)
- Inline event handlers (`oninput`, `onclick`) in some field types
- Missing alt text on image previews
- Incomplete keyboard navigation for group/repeater headers
- XSS potential via HTML template cloning in JS

**Findings Summary:**
| Severity | Count | Change from v2.0 |
|----------|-------|-------------------|
| Critical | 4     | New feature gaps    |
| High     | 7     | Shifted focus       |
| Medium   | 7     | Reduced             |
| Low      | 4     | Similar             |

---

## 2. Resolved Findings from v2.0

| v2.0 Finding | Resolution |
|---|---|
| No ARIA roles on tabs | `role="tablist"`, `role="tab"`, `role="tabpanel"` with keyboard nav (FE-001) |
| No fieldset legends | Screen-reader-text legends on RadioField, TaxonomyField, group search (FE-002, FE-003) |
| Checkbox label duplicate | Hidden input fallback for unchecked state (FE-004) |
| Color contrast below AA | #757575→#595959, #787c82→#50575e (FE-005) |
| Deprecated Gutenberg API | `wp.editor` with `wp.editPost` fallback (FE-006) |
| Sortable broken on clone | Re-init on cloned rows (FE-007) |
| No client-side validation | Blur handler + form submit prevention (FE-008) |
| No focus-visible on tabs | Added keyboard focus styles (FE-010) |
| Single breakpoint | Two-step responsive at 1200px and 782px (FE-011) |
| Hardcoded CSS colors | CSS custom properties `--cmb-*` (FE-012) |
| No build pipeline | esbuild for JS+CSS minification (DX-001) |
| jQuery only in Gutenberg | ES6+ with wp.element (FE-015) |
| XSS in file upload preview | Template literal escaping (CF-009) |

---

## 3. Critical Findings

### FE-C01: Missing ARIA on Language Tabs

**Severity: Critical**
**File:** `src/Core/Traits/MultiLanguageTrait.php`

The multi-language trait renders language switching tabs without `role="tablist"` / `role="tab"` / `role="tabpanel"` attributes, despite the main meta box tabs now having proper ARIA. This creates an inconsistency.

**Impact:** Screen reader users cannot navigate language tabs. WCAG 2.1 AA failure (4.1.2 Name, Role, Value).

**Recommendation:** Apply the same ARIA pattern used in the main tab system to language tabs.

---

### FE-C02: Inline `oninput` Handler in RangeField

**Severity: Critical**
**File:** `src/Fields/RangeField.php`

```html
<input type="range" oninput="this.nextElementSibling.textContent = this.value">
```

Inline event handlers violate Content Security Policy (CSP) requirements and WordPress coding standards. They also prevent proper JS module management.

**Recommendation:** Move to `addEventListener('input', ...)` in the enqueued JS file.

---

### FE-C03: Missing Alt Text on Image Previews

**Severity: Critical**
**Files:** `src/Fields/ImageField.php`, `src/Fields/GalleryField.php`, `src/Fields/FileField.php`

Image preview `<img>` elements are rendered without `alt` attributes. For decorative images, `alt=""` is required. For meaningful images, a descriptive alt should be provided.

**Impact:** WCAG 2.1 AA failure (1.1.1 Non-text Content).

**Recommendation:** Add `alt=""` for decorative thumbnails, or `alt="<?php echo esc_attr($filename); ?>"` for meaningful previews.

---

### FE-C04: Inline `onclick` Handlers in Admin Pages

**Severity: Critical**
**File:** `src/Core/AdminUI/ListPage.php`

Delete confirmation uses `onclick="return confirm('...')"`. This is a CSP violation and prevents i18n of the confirmation message.

**Recommendation:** Use `data-action="delete"` attributes and handle in enqueued JS with `wp.i18n.__()`.

---

## 4. High Findings

### FE-H01: Missing Labels on FlexibleContent Sub-Fields

**Severity: High**
**File:** `src/Fields/FlexibleContentField.php`

FlexibleContent layout sub-fields render without proper `<label>` associations when cloned via JavaScript template. The `for` attribute references IDs that may not exist yet on cloned elements.

---

### FE-H02: XSS via HTML Template Cloning

**Severity: High**
**File:** `assets/cmb-script.js`

The flexible content and group field cloning mechanism uses `.innerHTML` to clone template rows. If a stored field value contains HTML, it will be interpreted as live markup in the cloned row.

**Recommendation:** Use `template.content.cloneNode(true)` (HTML `<template>` element) or sanitize HTML before insertion.

---

### FE-H03: Missing `aria-invalid` and `aria-describedby`

**Severity: High**
**Files:** All field types with validation

Client-side validation adds visual error indicators but doesn't set `aria-invalid="true"` on the input or link the error message via `aria-describedby`. Screen reader users won't know a field has an error.

**Impact:** WCAG 2.1 AA failure (3.3.1 Error Identification).

---

### FE-H04: No Keyboard Navigation for Group Headers

**Severity: High**
**File:** `src/Fields/GroupField.php`, `assets/cmb-script.js`

Repeater/group rows have collapse/expand and reorder functionality but these controls are not keyboard-accessible. The drag handle has no keyboard alternative (e.g., up/down arrow buttons).

**Impact:** WCAG 2.1 AA failure (2.1.1 Keyboard).

---

### FE-H05: Gutenberg Block Missing Field Types

**Severity: High**
**File:** `assets/cmb-gutenberg.js`

The Gutenberg sidebar now supports radio, color, date, toggle, and file/image (FEAT-016), but still missing: group/repeater, flexible content, gallery, wysiwyg, and multi-select. These render as plain TextControl fallbacks.

---

### FE-H06: No Escape Key Handler for Modals

**Severity: High**
**Files:** `assets/cmb-admin.js` (type picker modal), `assets/cmb-script.js` (layout picker)

Modal dialogs (field type picker, layout picker) don't close on Escape key press. This is a WCAG requirement for modal dialogs (2.1.2 No Keyboard Trap).

---

### FE-H07: FlexibleContent Layout Picker Not Accessible

**Severity: High**
**File:** `assets/cmb-script.js`

The layout picker dropdown doesn't have proper ARIA `role="listbox"` / `role="option"` attributes, and doesn't support keyboard navigation (arrow keys to select layout).

---

## 5. Medium Findings (7)

| ID | Description | File(s) |
|---|---|---|
| FE-M01 | Color picker not respecting `prefers-color-scheme` | `assets/cmb-style.css` |
| FE-M02 | No `prefers-reduced-motion` media query for animations | `assets/cmb-style.css` |
| FE-M03 | Focus trap not implemented in modal dialogs | `assets/cmb-admin.js` |
| FE-M04 | Gallery field drag-and-drop has no keyboard alternative | `assets/cmb-script.js` |
| FE-M05 | Print stylesheet doesn't hide interactive controls | `assets/cmb-style.css` |
| FE-M06 | No `loading="lazy"` on image previews | Image/Gallery field output |
| FE-M07 | CSS specificity issues — some styles use `!important` | `assets/cmb-style.css` |

---

## 6. Low Findings (4)

| ID | Description |
|---|---|
| FE-L01 | No dark mode support for admin meta boxes |
| FE-L02 | Touch target sizes may be too small on mobile (< 44px) |
| FE-L03 | No transition/animation on conditional field show/hide |
| FE-L04 | `console.log()` debug statements left in cmb-script.js |

---

## 7. WCAG 2.1 AA Compliance Checklist

| Criterion | v2.0 | v2.1 | Notes |
|---|---|---|---|
| 1.1.1 Non-text Content | Fail | **Fail** | Missing alt on image previews |
| 1.3.1 Info and Relationships | Fail | Pass | ARIA tabs, fieldset legends |
| 1.4.3 Contrast (Minimum) | Fail | Pass | Colors updated to AA |
| 1.4.11 Non-text Contrast | Pass | Pass | — |
| 2.1.1 Keyboard | Fail | **Partial** | Tabs pass, groups/modals fail |
| 2.1.2 No Keyboard Trap | Pass | **Fail** | Modal missing Escape handler |
| 2.4.3 Focus Order | Fail | Pass | Tab focus styles added |
| 2.4.7 Focus Visible | Fail | Pass | focus-visible styles (FE-010) |
| 3.3.1 Error Identification | Fail | **Fail** | No aria-invalid/describedby |
| 3.3.2 Labels or Instructions | Fail | **Partial** | Main fields pass, FlexibleContent fails |
| 4.1.2 Name, Role, Value | Fail | **Partial** | Main tabs pass, language tabs fail |

**Verdict: PARTIAL FAIL** — 4 criteria still failing, 2 partial. Estimated effort to reach full AA: 6-8 hours.

---

## 8. Frontend Scorecard

| Dimension | v2.0 Score | v2.1 Score | Notes |
|---|---|---|---|
| JavaScript architecture | 4/10 | 7/10 | ES6+, build pipeline, modular |
| CSS architecture | 6/10 | 8/10 | Custom properties, responsive, variables |
| Accessibility (WCAG AA) | 3/10 | 6/10 | Major improvements, still gaps |
| Gutenberg integration | 4/10 | 6/10 | More fields, but still sidebar-only |
| Responsive design | 5/10 | 8/10 | Two-step breakpoints working well |
| Browser compatibility | 7/10 | 7/10 | Same — ES6+ may need polyfills |
| **Overall** | **4.8/10** | **7.0/10** | **Good improvement** |
