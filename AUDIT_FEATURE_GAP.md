# Feature Gap & Product Audit Report

**Plugin:** Custom Meta Box Builder v2.1
**Audit Date:** 2026-04-06 (Re-audit)
**Previous Audit:** 2026-04-05 (v2.0)
**Auditor:** Feature Completeness Agent
**Competitors:** ACF Pro 6.x, CMB2 2.x, Meta Box 5.x

---

## 1. Executive Summary

**Overall Feature Parity: ~80%** (up from ~70% in v2.0)

The v2.1 release closes several critical gaps identified in the v2.0 audit:

**Newly Added (v2.1):**
- FlexibleContentField — layout-based content building (ACF's killer feature)
- FrontendForm — `cmb_render_form()` / `cmb_the_form()` for frontend rendering
- BlockRegistration — `cmb_register_block()` for Gutenberg blocks
- GraphQL integration — auto-registers fields in WPGraphQL schema
- LocalJson sync — version-controlled field group configs
- Expanded Gutenberg sidebar — radio, color, date, toggle, file/image mappings
- 8 new field types: Time, Range, Toggle, Message, Divider, Image, Gallery, CheckboxList
- Public API: `cmb_get_field()`, `cmb_the_field()`, `cmb_get_term_field()`, `cmb_get_user_field()`, `cmb_get_option()`
- AND/OR conditional logic with group-level conditions
- PHP code export
- Color picker with alpha/RGBA support

**Still Missing vs Competitors:**
- Link field, oEmbed field, Google Maps field, Button Group field
- Accordion/Tab field (fields within tabs, not tab-level grouping)
- Clone/Reference field
- Formatted value retrieval (`get_field()` with automatic unpacking)
- Full Gutenberg canvas integration (sidebar-only currently)
- Custom database table support
- Complex GraphQL types (groups map to String)
- WPML/Polylang integration
- Advanced WP-CLI commands

---

## 2. Field Type Comparison Matrix

| Field Type | CMBB v2.1 | ACF Pro | CMB2 | Meta Box |
|---|:---:|:---:|:---:|:---:|
| Text | Y | Y | Y | Y |
| Textarea | Y | Y | Y | Y |
| Number | Y | Y | Y | Y |
| Email | Y | Y | Y | Y |
| URL | Y | Y | Y | Y |
| Password | Y | Y | Y | Y |
| WYSIWYG | Y | Y | Y | Y |
| Select | Y | Y | Y | Y |
| Multi-Select | **Y** | Y | Y | Y |
| Checkbox | Y | Y | Y | Y |
| Checkbox List | **Y** | Y | P | Y |
| Radio | Y | Y | Y | Y |
| Date Picker | Y | Y | Y | Y |
| Time Picker | **Y** | Y | Y | Y |
| Color Picker | **Y+α** | Y | Y | Y |
| Range/Slider | **Y** | Y | N | Y |
| Toggle | **Y** | Y (true/false) | N | Y |
| File Upload | Y | Y | Y | Y |
| Image | **Y** | Y | Y | Y |
| Gallery | **Y** | Y | N | Y |
| Post Object | Y | Y | Y | Y |
| User | Y | Y | Y | Y |
| Taxonomy | Y | Y | Y | Y |
| Group/Repeater | Y | Y | Y | Y |
| Flexible Content | **Y** | Y | N | N |
| Message/Heading | **Y** | Y | Y | Y |
| Divider | **Y** | N | N | Y |
| Tab (within fields) | N | Y | N | Y |
| Accordion | N | Y | N | Y |
| Link | N | Y | N | Y |
| oEmbed | N | Y | Y | Y |
| Google Map | N | Y | N | Y |
| Button Group | N | Y | N | Y |
| Clone/Reference | N | Y | N | Y |
| Autocomplete | N | N | N | Y |
| Sidebar | N | N | N | Y |
| Background | N | N | N | Y |
| **Total** | **26** | **33** | **22** | **35** |

**Field Type Coverage: 26/33 (79% of ACF Pro)**

---

## 3. Feature Comparison Matrix

| Feature | CMBB v2.1 | ACF Pro | CMB2 | Meta Box |
|---|:---:|:---:|:---:|:---:|
| PHP Configuration API | Y | Y | Y | Y |
| Admin UI Builder | Y | Y | N | Y |
| Repeater/Group | Y | Y | Y | Y |
| Flexible Content | **Y** | Y | N | N |
| Conditional Logic | **Y (AND/OR)** | Y | P | Y |
| Location Rules | Y | Y | N | Y |
| REST API | Y | Y | Y | Y |
| Gutenberg Sidebar | **Y (expanded)** | Y | P | Y |
| Gutenberg Blocks | **Y (basic)** | Y | N | Y |
| Frontend Forms | **Y** | Y | Y | Y |
| GraphQL | **Y (basic)** | Y (via add-on) | N | N |
| Local JSON | **Y** | Y | N | N |
| Import/Export | Y | Y | P | Y |
| PHP Export | **Y** | Y | N | Y |
| WP-CLI | Y | Y | P | Y |
| Multilingual Trait | Y | N (via WPML) | N | N |
| Validation (server) | Y | Y | Y | Y |
| Validation (client) | **Y** | Y | P | Y |
| Developer Hooks | Y (12) | Y (30+) | Y (40+) | Y (50+) |
| Custom Tables | N | N | N | Y |
| Settings Pages | Y | Y | Y | Y |
| Term Meta | Y | Y | Y | Y |
| User Meta | Y | Y | Y | Y |
| Bulk Operations | Y | N | N | N |
| Dependency Graph | Y | N | N | N |

---

## 4. Critical Gaps (Priority Order)

### GAP-001: No Formatted Value Retrieval API

**Severity: CRITICAL**
**Status: PARTIALLY ADDRESSED in v2.1**

`cmb_get_field()` exists but returns raw `get_post_meta()` data. Developers must manually handle:
- Group/repeater data unpacking (stored as serialized arrays)
- Image/file fields return IDs, not URLs
- Gallery returns comma-separated IDs, not array of attachment objects
- FlexibleContent returns raw nested arrays

ACF's `get_field()` automatically formats values based on field type, returning ready-to-use data.

**Recommendation:** Add `format()` method to `FieldInterface`. Implement in each field type. Create `cmb_get_field_formatted()` that detects field type and applies formatting.
**Effort:** Small (4-6 hours)

---

### GAP-002: Missing Field Types (7 remaining)

**Severity: HIGH**

| Missing Type | Priority | Effort | Notes |
|---|---|---|---|
| Link | P0 | Small | ACF's link picker — URL, title, target |
| oEmbed | P1 | Medium | Auto-embed preview for URLs |
| Button Group | P1 | Small | Radio-like but with button UI |
| Tab (within fields) | P1 | Medium | Group fields under inline tabs |
| Accordion | P2 | Medium | Collapsible field groups |
| Google Map | P2 | Large | Requires Maps API key config |
| Clone/Reference | P3 | Large | Reference existing field groups |

---

### GAP-003: Incomplete REST API Schema for Complex Types

**Severity: HIGH**

REST API registers fields but complex types (group, flexible content, gallery) are treated as plain strings. No JSON Schema definitions for nested structures. REST clients can't properly parse complex data.

**Recommendation:** Create proper REST schema for each complex type.
**Effort:** Medium (6-8 hours)

---

### GAP-004: Incomplete GraphQL Type Support

**Severity: HIGH**

All complex types map to `String` in GraphQL. Groups should be `ObjectType`, FlexibleContent should use Union types per layout, Gallery should return attachment objects with URLs.

**Recommendation:** Create custom GraphQL type definitions for complex fields.
**Effort:** Medium (8-10 hours)

---

### GAP-005: Gutenberg Integration Gaps

**Severity: HIGH**

Current state: Sidebar-only integration with limited field types. Missing:
- Group/repeater in sidebar
- FlexibleContent in sidebar
- Gallery in sidebar
- WYSIWYG in sidebar
- Conditional logic in sidebar
- Full canvas/inline editing (like ACF)
- Block registration without JS build step works but is basic

**Recommendation:** Prioritize group/repeater support in sidebar. Canvas integration is a major effort (v3.0 milestone).
**Effort:** Large (20-30 hours for sidebar completeness)

---

### GAP-006: Limited Hook Coverage

**Severity: MEDIUM**

CMBB has ~12 hooks vs ACF's 30+, CMB2's 40+, Meta Box's 50+. Missing:
- Per-field-type render hooks (`cmbbuilder_render_{type}`)
- Field value formatting hook
- Pre/post delete hooks
- Admin UI builder hooks
- AJAX endpoint hooks
- Storage abstraction hooks
- Field validation hooks (per-type)

**Recommendation:** Add per-field-type hooks and a `cmbbuilder_format_value` filter.
**Effort:** Small (2-3 hours)

---

### GAP-007: No WPML/Polylang Integration

**Severity: MEDIUM**

The MultiLanguageTrait stores per-locale values as separate meta keys (`field_id_en`, `field_id_fr`). This is incompatible with WPML and Polylang, which use their own translation mechanisms.

**Recommendation:** For WPML compatibility, register fields with `wpml-config.xml`. The existing trait is suitable for custom multilingual solutions only.
**Effort:** Medium (8-10 hours for WPML integration)

---

### GAP-008: Limited WP-CLI Commands

**Severity: LOW**

Only 3 commands: `list`, `get`, `set`. Missing: `get-option`, `get-term`, `get-user`, `export`, `import`, `delete`, bulk operations, JSON/CSV output format.

**Recommendation:** Expand CLI commands to match API coverage.
**Effort:** Small (3-4 hours)

---

### GAP-009: Frontend Form Limitations

**Severity: LOW**

`cmb_render_form()` exists but:
- Complex fields (group, flexible content) may not render properly on frontend
- No AJAX submission support
- No access control (capability checking)
- Error display mechanism unclear
- File upload handling incomplete

**Recommendation:** Test and fix all field types in frontend context. Add `capability` parameter.
**Effort:** Medium (6-8 hours)

---

### GAP-010: LocalJson Limitations

**Severity: LOW**

One-directional sync only. No conflict detection between DB and JSON configs. No version tracking. Unlike ACF's automatic sync with conflict resolution UI.

**Recommendation:** Add `_modified` timestamp comparison and admin notice for conflicts.
**Effort:** Medium (4-6 hours)

---

## 5. Competitive Position

| Dimension | vs ACF Pro | vs CMB2 | vs Meta Box |
|---|---|---|---|
| Field types | 79% | 118% | 74% |
| Admin UI | 90% | N/A (code-only) | 85% |
| Developer API | 70% | 60% | 65% |
| Gutenberg | 50% | 30% | 60% |
| Extensibility | 40% | 70% | 45% |
| Documentation | 30% | 40% | 30% |
| Unique features | Bulk ops, dep graph, multilingual trait | — | — |

**Overall Competitive Position:**
- **vs ACF Pro:** 65% feature parity (up from 55% in v2.0)
- **vs CMB2:** 95% feature parity (up from 80% in v2.0) — exceeds in UI and features
- **vs Meta Box:** 60% feature parity (up from 50% in v2.0)

---

## 6. Recommended Roadmap

### v2.2 (Quick Wins — 2-3 days)
1. Formatted value retrieval API (GAP-001)
2. Link field type (GAP-002)
3. Button Group field type (GAP-002)
4. Per-field-type hooks (GAP-006)
5. ABSPATH guards on all files (WPS-C01)
6. i18n string wrapping (WPS-C03)

### v2.3 (Medium Effort — 1-2 weeks)
1. REST API schema for complex types (GAP-003)
2. GraphQL type definitions (GAP-004)
3. Tab/Accordion field types (GAP-002)
4. WP_Filesystem migration (WPS-C02)
5. WPML compatibility (GAP-007)
6. Expanded WP-CLI (GAP-008)

### v3.0 (Major — 1-2 months)
1. Full Gutenberg canvas integration (GAP-005)
2. oEmbed field with preview (GAP-002)
3. Google Maps field (GAP-002)
4. Clone/Reference fields (GAP-002)
5. Custom database table support
6. Advanced conditional logic (nested, conditional validation)
7. Remove legacy `cmb_` hook prefix

---

## 7. v2.0 → v2.1 Progress Summary

| Metric | v2.0 | v2.1 | Change |
|---|---|---|---|
| Field types | 18 | 26 | +8 |
| Missing field types | 15 | 7 | -8 |
| Public API functions | 4 | 10 | +6 |
| Developer hooks | 8 | 12 | +4 |
| Gutenberg field mappings | 3 | 8 | +5 |
| Unit tests | 3 | 114 | +111 |
| Feature parity vs ACF | 55% | 65% | +10% |
| Feature parity vs CMB2 | 80% | 95% | +15% |
| Competitive readiness | Low | Medium | Improved |
