# Custom Meta Box Builder -- Feature Gap & Product Audit

**Date:** 2026-04-05
**Plugin Version:** 2.0
**Auditor:** Agent 6 (Feature Gap & Product Auditor)
**Competitors Analyzed:** ACF Pro 6.x, CMB2 2.x, Meta Box 5.x

---

## 1. Executive Summary

Custom Meta Box Builder (CMBB) is a well-architected, code-first WordPress meta box plugin with 18 field types, a clean PHP configuration API, and a surprising breadth of features for a v2.0 product -- including repeater groups, conditional logic, tabs, REST API, Gutenberg sidebar, WP-CLI, import/export, multi-language support, and a no-code admin builder.

**Strengths:**
- Clean, declarative PHP array API that is easy to learn
- Good nested group/repeater support with unlimited depth
- Solid foundation: PSR-4, OOP, singleton pattern, proper sanitization
- Features that even some competitors lack: dependency graph visualization, bulk operations, multi-language trait, lazy loading for large repeaters
- 8 developer hooks, field type registration API, extensible architecture

**Weaknesses:**
- Missing 15+ field types that ACF/Meta Box offer (gallery, oEmbed, Google Map, range, time, true/false toggle, button group, link, accordion, image crop, etc.)
- No `get_field()` / `cmb_get_field()` convenience API -- developers must use raw `get_post_meta()` with knowledge of data structure
- No Flexible Content / Layout equivalent (ACF's killer feature)
- No frontend form rendering capability
- No GraphQL support
- Gutenberg integration is minimal (basic sidebar panel, no native block registration, no block field type)
- No custom database table support
- No multisite-specific features
- No AJAX/autocomplete on relational fields (Post, User, Taxonomy load all records in a `<select>`)
- Admin UI builder stores configs in `wp_options` -- not exportable to code, not version-controllable
- Limited location rules -- post type only, no page template / taxonomy / user role / post format rules
- Test coverage is minimal (3 test files)

**Overall Assessment:** CMBB has a solid core and covers ~70% of what a typical developer needs. However, it is missing several features that are considered table-stakes in the ACF/Meta Box ecosystem, and its competitive viability depends on closing the gaps in field types, the value retrieval API, and Gutenberg integration.

---

## 2. Feature Comparison Matrix

Legend: Y = Full support, P = Partial support, N = No support

### 2.1 Field Types

| Field Type | CMBB | ACF Pro | CMB2 | Meta Box |
|---|:---:|:---:|:---:|:---:|
| Text | Y | Y | Y | Y |
| Textarea | Y | Y | Y | Y |
| Number | Y | Y | Y | Y |
| Email | Y | Y | Y | Y |
| URL | Y | Y | Y | Y |
| Password | Y | Y | Y | Y |
| Select | Y | Y | Y | Y |
| Multi-select | N | Y | Y | Y |
| Checkbox (single) | Y | Y | Y | Y |
| Checkbox list (multi) | N | Y | Y | Y |
| Radio | Y | Y | Y | Y |
| Button Group | N | Y | N | Y |
| True/False (toggle) | N | Y | N | Y |
| Hidden | Y | N | Y | Y |
| Range / Slider | N | Y | N | Y |
| Date Picker | Y | Y | Y | Y |
| Time Picker | N | Y | Y | Y |
| Date+Time Picker | Y | Y | Y | Y |
| Color Picker | Y (native) | Y (Iris) | Y (Iris) | Y (Iris+alpha) |
| WYSIWYG | Y | Y | Y | Y |
| File Upload | Y | Y | Y | Y |
| Image (distinct from file) | N | Y | Y | Y |
| Gallery | N | Y | N | Y |
| oEmbed | N | Y | N | Y |
| Post Object | Y (basic select) | Y (AJAX) | Y | Y (AJAX) |
| Page Link | N | Y | N | Y |
| Relationship (bi-directional) | N | Y | N | Y |
| Taxonomy | Y (checkbox/select) | Y (AJAX+tree) | Y | Y |
| User | Y (basic select) | Y (AJAX) | Y | Y (AJAX) |
| Google Map | N | Y | N | Y |
| Link | N | Y | N | N |
| Accordion | N | Y | N | N |
| Tab (as field type) | N | Y | N | Y |
| Group / Repeater | Y | Y | Y | Y |
| Flexible Content | N | Y | N | N |
| Clone | N | Y | N | Y |
| Message / Heading (display) | N | Y | Y | Y |
| Divider / Separator | N | N | Y | Y |
| Custom HTML | N | N | Y | Y |
| Autocomplete | N | N | N | Y |
| Fieldset Text | N | N | Y | N |
| **Total field types** | **18** | **~35** | **~30** | **~40+** |

### 2.2 Core Features

| Feature | CMBB | ACF Pro | CMB2 | Meta Box |
|---|:---:|:---:|:---:|:---:|
| Declarative PHP API | Y | Y | Y | Y |
| GUI Admin Builder | Y (basic) | Y (polished) | N | Y (polished) |
| Repeater / Repeat | Y | Y | Y | Y |
| Nested Groups | Y (unlimited) | Y (unlimited) | Y (2 levels) | Y (unlimited) |
| Flexible Content | N | Y | N | N |
| Tabs (within meta box) | Y | Y | N | Y |
| Conditional Logic | Y (5 operators) | Y (rich) | N | Y (rich) |
| Conditional -- AND/OR groups | N | Y | N | Y |
| Sortable Rows | Y | Y | Y | Y |
| Row Title from Field | Y | Y | N | Y |
| Duplicate Row | Y | Y | N | Y |
| Min/Max Rows | Y | Y | N | Y |
| Default Values | Y | Y | Y | Y |
| Placeholder | Y (via attributes) | Y | Y | Y |
| Description / Instructions | Y | Y | Y | Y |
| Field Validation | Y (7 rules) | Y (rich) | N | Y (rich) |
| Custom Sanitization Callback | Y | Y | Y | Y |
| Wrapper / Width Classes | Y (4 widths) | Y (flexible %) | N | Y (flexible %) |

### 2.3 Location Rules

| Location | CMBB | ACF Pro | CMB2 | Meta Box |
|---|:---:|:---:|:---:|:---:|
| Post Type | Y | Y | Y | Y |
| Page Template | N | Y | Y | Y |
| Post Status | N | Y | N | Y |
| Post Format | N | Y | N | Y |
| Post Category | N | Y | N | Y |
| Post Taxonomy | N | Y | Y | Y |
| Page (specific) | N | Y | N | Y |
| Page Parent | N | Y | N | Y |
| User Role | N | Y | N | Y |
| Menu Item | N | Y | N | Y |
| Widget | N | Y | N | N |
| Comment | N | N | Y | Y |
| Taxonomy Term | Y | Y | Y | Y |
| User Profile | Y | Y | Y | Y |
| Options Page | Y | Y | Y | Y |
| Attachment / Media | N | Y | N | Y |
| Nav Menu | N | Y | N | Y |
| Block | N | Y | N | Y |

### 2.4 Integration & APIs

| Feature | CMBB | ACF Pro | CMB2 | Meta Box |
|---|:---:|:---:|:---:|:---:|
| `get_field()` style API | N | Y | Y (`cmb2_get_*`) | Y (`rwmb_meta()`) |
| REST API | Y (basic) | Y | Y | Y |
| GraphQL (WPGraphQL) | N | Y (plugin) | N | Y (plugin) |
| Gutenberg Sidebar Panel | Y (basic) | Y (native) | P | Y |
| Gutenberg Block Registration | N | Y | N | Y |
| Block Field Type | N | Y | N | Y |
| Frontend Form Rendering | N | Y (acf_form) | Y (cmb2_get_metabox_form) | Y (mb_frontend) |
| WP-CLI | Y | Y | N | N |
| Import/Export (JSON) | Y | Y | N | Y |
| PHP Export (code gen) | N | Y | N | Y |
| Custom Database Tables | N | N | N | Y |
| Multisite Support | N | Y | N | Y |
| Local JSON (file-based sync) | N | Y | N | N |

### 2.5 Developer Experience

| Feature | CMBB | ACF Pro | CMB2 | Meta Box |
|---|:---:|:---:|:---:|:---:|
| Action Hooks | 4 | 20+ | 30+ | 50+ |
| Filter Hooks | 4 | 20+ | 30+ | 50+ |
| Custom Field Type Registration | Y | Y | Y | Y |
| Field Type Auto-Discovery | Y | N | N | N |
| Dependency Graph Visualization | Y | N | N | N |
| Bulk Operations Tool | Y | N | N | N |
| Multi-language (built-in) | Y | N (WPML plugin) | N | N |
| PHPUnit Tests | P (3 files) | Y | Y | Y |
| Lazy Loading (large repeaters) | Y | N | N | N |

---

## 3. Missing Critical Features (Ranked by Importance)

These are features whose absence will cause developers to choose a competitor instead.

### 3.1 CRITICAL -- Value Retrieval API (`get_field()` equivalent)

**Impact: Very High | Effort: Low**

Currently developers must use raw `get_post_meta()` and understand the internal storage format (serialized arrays for groups, multiple meta rows for repeaters). Every major competitor provides a convenience function:
- ACF: `get_field('name', $post_id)` / `the_field()`
- CMB2: `cmb2_get_option()` / `get_post_meta()` with helpers
- Meta Box: `rwmb_meta('field_id')` / `rwmb_the_value()`

**Recommendation:** Add `cmb_get_field($field_id, $post_id)` and `cmb_get_option($field_id, $option_name)` that handle unserialization, group unpacking, and return formatted values. Also add `cmb_the_field()` for echo output in templates.

### 3.2 CRITICAL -- Flexible Content / Layout Field

**Impact: Very High | Effort: High**

ACF's Flexible Content is the single most cited reason agencies choose ACF. It allows content editors to build pages from pre-defined layout blocks (hero, text+image, CTA, gallery grid, etc.). No competitor except ACF offers this natively.

**Recommendation:** Implement a `flexible_content` field type that allows defining multiple "layouts", each with its own set of sub-fields. Users pick which layout to add per row.

### 3.3 CRITICAL -- AJAX-powered Relational Fields

**Impact: High | Effort: Medium**

PostField, UserField, and TaxonomyField load ALL records into a `<select>`. On sites with 10,000+ posts or 500+ users, this will cause performance problems and is unusable UX. ACF and Meta Box use AJAX autocomplete (Select2/choices.js).

**Recommendation:** Integrate Select2 or Choices.js with AJAX endpoints for post, user, and taxonomy fields. Add a `'select2' => true` config option.

### 3.4 CRITICAL -- Richer Location Rules

**Impact: High | Effort: Medium**

CMBB only supports targeting by post type. ACF supports 20+ location rules (page template, taxonomy, user role, specific page, post format, etc.) with AND/OR logic groups. This is essential for real-world projects.

**Recommendation:** Add a location rules system. At minimum support: page template, post category/taxonomy, specific post/page ID, user role, and AND/OR grouping.

### 3.5 CRITICAL -- Multi-select and Checkbox List Fields

**Impact: High | Effort: Low**

There is no way to select multiple options from a select dropdown or render a checkbox group for picking multiple items. These are basic field types every competitor has.

**Recommendation:** Add `'multiple' => true` support to SelectField. Add a CheckboxListField (or extend CheckboxField) for multi-checkbox groups.

### 3.6 CRITICAL -- Frontend Form Rendering

**Impact: High | Effort: High**

ACF's `acf_form()` and CMB2's `cmb2_get_metabox_form()` allow rendering meta box forms on the frontend for user-submitted content, profile editing, etc. This is a very common use case.

**Recommendation:** Add a `cmb_render_form($meta_box_id, $post_id)` function that outputs the meta box form with proper nonce and AJAX save handler.

---

## 4. Missing Nice-to-Have Features

### 4.1 HIGH PRIORITY

| Feature | Effort | Notes |
|---|---|---|
| Image field (separate from file) | Low | Dedicated image field with inline preview, crop, alt text. Currently file field handles both but UX is generic. |
| Gallery field | Medium | Multi-image selection from media library. Very common need. |
| Time picker field | Low | `<input type="time">` -- trivial to add. |
| Range / slider field | Low | `<input type="range">` with min/max/step and value display. |
| Message / Heading field | Low | Display-only field for instructions, headings, or custom HTML within a meta box. No data storage. |
| Divider / Separator field | Low | Visual separator between fields. |
| True/False toggle field | Low | Switch/toggle UI instead of plain checkbox. Better UX for boolean options. |
| Color picker with alpha | Low | Current `<input type="color">` does not support alpha/opacity. Use WordPress Iris or a library. |
| `AND/OR` conditional logic groups | Medium | Current conditional only supports a single condition. Real projects need "show if (A == 1 AND B == 2) OR (C == 3)". |
| Attachment / Media edit screen meta | Low | Hook into `attachment_fields_to_edit` for media library custom fields. |

### 4.2 MEDIUM PRIORITY

| Feature | Effort | Notes |
|---|---|---|
| Gutenberg native block registration | High | Register a custom block that uses CMBB fields, similar to ACF Blocks. |
| GraphQL support (WPGraphQL) | Medium | Expose fields via WPGraphQL schema. Growing demand in headless WordPress. |
| Local JSON sync (ACF-style) | Medium | Save field group configs as JSON files in the theme for version control. |
| PHP code export from Admin UI | Medium | The Admin UI builder cannot export configs as PHP code for embedding in `functions.php`. |
| oEmbed field | Medium | Accepts a URL, renders an oEmbed preview (YouTube, Vimeo, Twitter, etc.). |
| Relationship field (bi-directional) | High | Two-way post relationship. ACF's version auto-creates the reverse link. |
| Page Link field | Low | Like Post field but returns a URL instead of an ID. |
| Comment meta support | Low | Attach fields to comment edit screens. |
| Clone field | High | Reference another field group and reuse its fields inline. Reduces duplication. |

### 4.3 LOW PRIORITY

| Feature | Effort | Notes |
|---|---|---|
| Google Map field | High | Requires Google Maps API key, JS integration. Niche but valued. |
| Custom database tables | Very High | Store meta in custom tables for performance. Meta Box's killer feature. Only needed at scale. |
| Multisite options pages | Medium | Network-wide settings pages. Niche use case. |
| Nav menu item fields | Medium | Add fields to individual menu items. |
| Accordion field (layout) | Medium | Like tabs but with accordion UX. |
| Field type icon in Admin UI | Low | Show icons for each field type in the admin builder. |

---

## 5. Developer Experience (DX) Evaluation

### 5.1 Strengths

- **Clean API surface.** The `add_custom_meta_box()` function signature is straightforward and easy to learn. It is arguably simpler than ACF's `acf_add_local_field_group()` which requires a deeply nested config array.
- **Consistent field config.** All 18 field types use the same config keys (`id`, `type`, `label`, `description`, `required`, etc.). No surprises.
- **Auto-discovery of field types.** Developers can drop a `RatingField.php` into `src/Fields/` and it works immediately. This is unique among competitors.
- **Good documentation.** 9 documentation files covering all features, architecture, hooks, extending, and testing. Better than CMB2's docs.
- **Dependency graph tool.** No competitor offers a visual dependency graph for conditional logic. This is a genuine differentiator.
- **Bulk operations tool.** Another unique feature not found in competitors.

### 5.2 Weaknesses

- **No value retrieval helper.** This is the single biggest DX gap. Developers must know the internal storage format to retrieve values. `get_post_meta($id, 'team_members')` returns serialized arrays that need manual handling. A `cmb_get_field()` wrapper is essential.
- **No `the_field()` template tag.** ACF's template integration is its biggest DX advantage. `the_field('name')` in a template is cleaner than `echo get_post_meta(get_the_ID(), 'name', true)`.
- **8 hooks is too few.** ACF has 20+ hooks, CMB2 has 30+. Missing hooks:
  - No hook for modifying field config at render time (per-field, not per-meta-box)
  - No hook for modifying options in select/radio fields
  - No hook for the admin UI builder
  - No hook for taxonomy/user/options saves (only post meta has hooks)
  - No `cmb_enqueue_scripts` hook for field-specific assets
- **Admin UI builder is disconnected from code.** Configs created in the Admin UI are stored in `wp_options` and cannot be exported as PHP code. Developers cannot iterate from UI to code. ACF solves this with "export to PHP" and Local JSON.
- **REST API is basic.** Only supports `show_in_rest` flag with basic type mapping. No custom REST schema for groups, no rich type definitions, no REST write support validation.
- **Test coverage is very thin.** Only 3 test files (Plugin, MetaBoxManager, TextField). No tests for: GroupField nesting, conditional logic, TaxonomyMetaManager, UserMetaManager, OptionsManager, validation, REST API, import/export, AdminUI. This is a risk for stability.
- **No inline code examples in error messages.** `_doing_it_wrong()` messages do not include a link to documentation or a code example.
- **Assets load on all admin pages.** `cmb-style.css` and `cmb-script.js` are enqueued via `admin_enqueue_scripts` without any screen check. They should only load on screens where meta boxes are rendered.

### 5.3 Recommendations

1. Add `cmb_get_field()`, `cmb_the_field()`, `cmb_get_option()` to `public-api.php`.
2. Add at least 10 more hooks (field config filter, options filter, taxonomy/user save hooks, script enqueue hook).
3. Add "Export to PHP" button in Admin UI.
4. Add screen-conditional asset loading.
5. Increase test coverage to at least 80% of source files.

---

## 6. User Experience (UX) Evaluation

### 6.1 Strengths

- **Keyboard accessibility.** Group headers have `role="button"`, `tabindex="0"`, Enter/Space handling, and `aria-expanded`. Focus-visible styles are present. Remove buttons have `aria-label`. This is better than CMB2.
- **Sortable drag-and-drop.** jQuery UI Sortable with proper index updating. Works well.
- **Delete confirmation.** Remove button requires confirmation. Prevents accidental data loss.
- **Row titles from field values.** Dynamic header titles make large repeater groups navigable.
- **Expand All / Collapse All.** Important for groups with many items.
- **Item count indicator.** Shows "N items" next to Add Row.
- **Empty state message.** Clear guidance when all items are removed.
- **Print stylesheet.** Hides action buttons when printing. Thoughtful detail.
- **Unsaved changes warning.** `beforeunload` protection against accidental navigation.
- **Lazy loading.** Groups with 20+ items auto-paginate. This is unique -- ACF does not do this.

### 6.2 Weaknesses

- **Admin UI builder is basic.** Compared to ACF's drag-and-drop field group editor with live preview, CMBB's admin builder is functional but minimal. It stores configs in `wp_options` with a basic form UI. No field reordering, no drag-and-drop, no field preview, no field duplication.
- **No visual field configuration in Admin UI.** When adding fields in the admin builder, there is no way to set conditional logic, validation rules, repeater options, or advanced config. Only basic type/label/description are available.
- **Color picker uses native `<input type="color">`.** This provides no alpha support and has inconsistent browser UI. ACF and Meta Box use WordPress's Iris color picker or a custom picker with alpha support. The native picker is functional but feels unpolished.
- **Post/User/Taxonomy fields are plain `<select>`.** No search, no AJAX, no grouping, no thumbnails. On sites with many records, these fields are unusable. ACF uses Select2 with AJAX. Meta Box uses its own Select Advanced.
- **WYSIWYG field in repeaters is broken by design.** WordPress `wp_editor()` uses TinyMCE which does not support multiple dynamic instances well. Cloning a WYSIWYG repeater row will produce a broken editor. ACF works around this by re-initializing TinyMCE after clone. CMBB does not appear to handle this.
- **No field-level help tooltips.** `description` is shown below the field. There is no option for a `?` tooltip icon that reveals help text on hover, which ACF supports.
- **Gutenberg panel is basic.** The sidebar panel renders simple controls (TextControl, etc.) but does not support groups, repeaters, conditional logic, or file uploads. It is effectively a read/write interface for simple scalar fields only.
- **No responsive column system.** Width classes (`w-25`, `w-33`, `w-50`, `w-75`) collapse to 100% below 1495px. There is no intermediate breakpoint. ACF provides more flexible column arrangements.

### 6.3 Recommendations

1. Integrate Select2 or Choices.js for relational fields (Post, User, Taxonomy).
2. Add WordPress Iris color picker integration with alpha support.
3. Handle TinyMCE re-initialization in JS after repeater clone.
4. Enhance Admin UI builder with drag-and-drop field ordering, conditional logic UI, and PHP export.
5. Add field-level tooltip (`'tooltip' => 'Help text'`).
6. Improve Gutenberg panel to support groups and file uploads.

---

## 7. Competitive Positioning Recommendations

### 7.1 Where CMBB Can Win

CMBB should not try to be ACF. ACF has 10+ years of ecosystem, thousands of tutorials, and deep Gutenberg integration. Instead, CMBB should position itself as:

**"The developer-first, lightweight alternative to ACF -- with zero lock-in."**

Key differentiators to emphasize:
1. **No vendor lock-in.** Data is stored as standard `post_meta`. No proprietary format. No license key required.
2. **Code-first approach.** Define everything in PHP arrays. Version-controllable. No database-stored config (except Admin UI).
3. **Auto-discovery of custom fields.** Drop a class file and it works. No registration boilerplate.
4. **Built-in features competitors charge for:** Multi-language, bulk operations, dependency graph, lazy loading, import/export.
5. **Lightweight.** No jQuery Select2, no React bundles, no heavy admin UI. Fast on shared hosting.

### 7.2 Priority Roadmap

**Phase 1 -- Table Stakes (Close the critical gaps):**
1. Add `cmb_get_field()` / `cmb_the_field()` value retrieval API
2. Add multi-select and checkbox list fields
3. Add AJAX-powered relational fields (Select2)
4. Add time picker, range, true/false toggle, message, and divider fields
5. Add richer location rules (page template, taxonomy, user role)

**Phase 2 -- Competitive Advantage:**
6. Add Flexible Content field type
7. Add frontend form rendering
8. Add Gutenberg block registration
9. Add "Export to PHP" from Admin UI
10. Improve conditional logic with AND/OR groups

**Phase 3 -- Ecosystem:**
11. Add GraphQL support
12. Add Local JSON sync
13. Add gallery and oEmbed fields
14. Increase hook count to 20+
15. Achieve 80%+ test coverage

### 7.3 Pricing Positioning

The plugin's current feature set is appropriate for a **free core + premium add-ons** model:
- **Free:** All 18 current field types, groups/repeaters, REST API, WP-CLI, import/export, hooks
- **Pro:** Flexible Content, frontend forms, Gutenberg blocks, AJAX relational fields, advanced conditional logic, priority support

This mirrors ACF's model (free ACF vs ACF PRO) and Meta Box's model (free core + 30 premium extensions).

---

## Appendix: Field Count Summary

| Plugin | Total Field Types | Unique / Notable |
|---|---|---|
| **CMBB** | 18 | Dependency graph, bulk ops, multi-language trait, lazy loading |
| **ACF Pro** | ~35 | Flexible Content, Clone, Accordion, Google Map, oEmbed, Link |
| **CMB2** | ~30 | Custom HTML, Fieldset Text, Title field, money field |
| **Meta Box** | 40+ | Autocomplete, Map, Slider, Switch, Button Group, Fieldset, Object Choice |

---

*End of audit report.*
