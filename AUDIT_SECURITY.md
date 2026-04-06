# Security Audit Report: Custom Meta Box Builder v2.1

**Audit Date:** 2026-04-06 (Re-audit)
**Previous Audit:** 2026-04-05 (v2.0)
**Auditor:** Security Audit Agent
**Scope:** Full source code review (all PHP, JS files excluding vendor/)
**Methodology:** Manual static analysis with attacker-perspective threat modeling

---

## 1. Executive Summary

**Overall Risk Rating: MEDIUM** (improved from v2.0)

The v2.1 re-audit shows significant security improvements over v2.0. The plugin now applies `wp_unslash()` to all `$_POST` reads, uses taxonomy-specific nonces, caps regex patterns, sanitizes field config values, masks password fields, routes WP-CLI through the sanitize pipeline, limits textarea import size, and sets cache-control headers on exports. The import sanitization gap from v2.0 FINDING-01 has been partially addressed.

However, new features introduced in v2.1 (FlexibleContent, FrontendForm, BlockRegistration, GraphQL, LocalJson) expand the attack surface and introduce new findings.

**Findings Summary:**
| Severity | Count | Change from v2.0 |
|----------|-------|-------------------|
| Critical | 0     | Same              |
| High     | 1     | -1                |
| Medium   | 10    | +5                |
| Low      | 9     | +4                |
| Info     | 0     | -3                |

---

## 2. Resolved Findings from v2.0

The following v2.0 findings have been **fully resolved**:

| v2.0 Finding | Description | Resolution |
|---|---|---|
| FINDING-02 | Object injection via `maybe_unserialize()` | Removed; values handled as raw meta |
| FINDING-04 | Missing `wp_unslash()` on `$_POST` reads | `wp_unslash()` added in BulkOperations, UserMetaManager, ActionHandler (SEC-002) |
| FINDING-05 | Missing taxonomy capability check | Added `current_user_can('edit_term', $term_id)` check (CF-003) |
| FINDING-07 | ReDoS via unbounded regex | Pattern capped at 500 chars with `/u` flag (SEC-004) |
| FINDING-09 | XSS in JS file upload preview | Template literal escaping applied (CF-009) |
| FINDING-10 | Unbounded UserField query | `number` parameter added to `get_users()` (CF-011) |

---

## 3. Remaining Findings from v2.0

### SEC-R01: Import Field Configuration Sanitization (Partial Fix)

**Severity: HIGH**
**File:** `src/Core/AdminUI/ActionHandler.php`
**Previous:** FINDING-01 (HIGH)

The import handler now sanitizes top-level config values, but deep nested field configurations (e.g., `options` arrays within select fields, `layouts` within FlexibleContent) still pass through with minimal recursive sanitization. An attacker with admin access could craft a malicious JSON import containing XSS payloads in nested field option labels.

**Impact:** Stored XSS via imported configs (requires admin access, reducing exploitability).

**Recommendation:** Implement recursive `sanitize_text_field()` on all string values within imported field arrays, or use an allowlist-based import schema validator.

---

## 4. New Findings in v2.1

### SEC-N01: HTTP Header Injection in Content-Disposition

**Severity: MEDIUM**
**File:** `src/Core/ImportExport.php`

The export handler sets a `Content-Disposition` header using the export filename. If the filename is derived from user input (meta box title), unsanitized characters could inject additional headers.

**Current code:** Uses `sanitize_file_name()` which mitigates most vectors, but the CRLF injection possibility should be explicitly guarded.

**Recommendation:** Add `str_replace(["\r", "\n"], '', $filename)` before header output.

---

### SEC-N02: Missing Capability Checks in AJAX Endpoints

**Severity: MEDIUM**
**File:** `src/Core/AjaxHandler.php`

The AJAX search endpoints (`cmb_search_posts`, `cmb_search_users`, `cmb_search_terms`) verify nonces but rely on the nonce alone for authorization. Any logged-in user with a valid nonce could search posts/users/terms, potentially exposing private post titles or user email addresses.

**Recommendation:** Add `current_user_can('edit_posts')` / `current_user_can('list_users')` checks before returning search results.

---

### SEC-N03: REST API `auth_callback` Missing

**Severity: MEDIUM**
**File:** `src/Core/MetaBoxManager.php` (REST field registration)

Fields registered via `register_post_meta()` use `'auth_callback' => '__return_true'` or omit the callback entirely, allowing any authenticated user to update meta via REST API regardless of post capabilities.

**Recommendation:** Set `auth_callback` to check `current_user_can('edit_post', $post_id)`.

---

### SEC-N04: File Type Validation on JSON Import

**Severity: MEDIUM**
**File:** `src/Core/AdminUI/ActionHandler.php`

The import handler reads from a textarea (not file upload) but doesn't validate the JSON structure schema. Malformed JSON with unexpected keys could introduce unexpected behavior.

**Recommendation:** Validate imported JSON against an expected schema (required keys: `meta_boxes`, `version`).

---

### SEC-N05: Frontend Form Attachment ID Validation

**Severity: MEDIUM**
**File:** `src/Core/FrontendForm.php`

The frontend form processes file/image field submissions but doesn't verify that submitted attachment IDs belong to the current user or are valid attachments.

**Recommendation:** Validate attachment ownership with `wp_attachment_is_image()` and `get_post($attachment_id)->post_author`.

---

### SEC-N06: LocalJson File Path Traversal

**Severity: MEDIUM**
**File:** `src/Core/LocalJson.php`

The `saveToFile()` method constructs file paths from meta box IDs. If a meta box ID contains path traversal characters (`../`), it could write JSON files outside the intended `cmb-json/` directory.

**Recommendation:** Apply `sanitize_file_name()` to the meta box ID before constructing the file path.

---

### SEC-N07: BlockRegistration Unsafe Include

**Severity: MEDIUM**
**File:** `src/Core/BlockRegistration.php`

The `renderBlock()` method uses `include` with a `render_template` path from the block config. If the template path is user-controlled (via import or DB manipulation), this could lead to local file inclusion.

**Recommendation:** Validate that the template path starts with the theme directory using `realpath()` and `str_starts_with()`.

---

### SEC-N08: GraphQL Field Exposure Without Permission

**Severity: MEDIUM**
**File:** `src/Core/GraphQLIntegration.php`

All CMB fields are automatically exposed in the GraphQL schema without any permission checks in the resolve callback. Sensitive fields (e.g., internal tracking IDs, private notes) become publicly queryable.

**Recommendation:** Add a `'show_in_graphql'` config option (default `true`) and respect `post_status` in resolve callbacks.

---

### SEC-N09: CSRF on Frontend Form Submission

**Severity: MEDIUM**
**File:** `src/Core/FrontendForm.php`

Frontend form uses `wp_nonce_field('cmb_frontend_save_' . $metaBoxId)` which is good, but the nonce is tied only to the meta box ID, not to the post ID. An attacker could submit a form targeting a different post ID with the same meta box nonce.

**Recommendation:** Include post ID in the nonce action: `'cmb_frontend_save_' . $metaBoxId . '_' . $postId`.

---

### SEC-N10: Flexible Content Layout Type Not Validated

**Severity: MEDIUM**
**File:** `src/Fields/FlexibleContentField.php`

When saving flexible content data, the submitted layout type name is stored without validating it against the list of registered layouts. An attacker could inject arbitrary layout type names.

**Recommendation:** Validate submitted layout names against `array_keys($this->config['layouts'])`.

---

### SEC-L01 through SEC-L09: Low Severity Findings

| ID | Description | File |
|---|---|---|
| SEC-L01 | Error suppression operator `@` on `preg_match` | `src/Core/AbstractField.php` |
| SEC-L02 | `filemtime()` with `@` suppression | `src/Core/Plugin.php` |
| SEC-L03 | No rate limiting on AJAX search endpoints | `src/Core/AjaxHandler.php` |
| SEC-L04 | Password field `autocomplete="off"` not set | `src/Fields/PasswordField.php` |
| SEC-L05 | Export JSON includes internal `_modified` timestamps | `src/Core/ImportExport.php` |
| SEC-L06 | WP-CLI commands don't log audit trail | `src/Core/WpCliCommands.php` |
| SEC-L07 | No Content-Security-Policy on admin pages | General |
| SEC-L08 | jQuery `.html()` usage in cmb-script.js for dynamic content | `assets/cmb-script.js` |
| SEC-L09 | No subresource integrity on enqueued scripts | `src/Core/Plugin.php` |

---

## 5. Recommendations Priority Matrix

| Priority | Finding | Effort |
|---|---|---|
| P0 | SEC-R01: Deep import sanitization | Medium |
| P0 | SEC-N02: AJAX capability checks | Small |
| P0 | SEC-N03: REST auth_callback | Small |
| P1 | SEC-N05: Frontend attachment validation | Small |
| P1 | SEC-N07: BlockRegistration include path | Small |
| P1 | SEC-N06: LocalJson path traversal | Small |
| P1 | SEC-N09: Frontend form CSRF improvement | Small |
| P1 | SEC-N10: FlexibleContent layout validation | Small |
| P2 | SEC-N01: Header injection guard | Trivial |
| P2 | SEC-N04: Import schema validation | Small |
| P2 | SEC-N08: GraphQL permission control | Medium |

---

## 6. v2.0 → v2.1 Security Posture Change

| Metric | v2.0 | v2.1 | Trend |
|---|---|---|---|
| Critical findings | 0 | 0 | Stable |
| High findings | 2 | 1 | Improved |
| Medium findings | 5 | 10 | Increased (new features) |
| Low findings | 5 | 9 | Increased (deeper audit) |
| Nonce coverage | Partial | Full | Improved |
| `wp_unslash()` coverage | Missing | Complete | Improved |
| Input sanitization | Partial | Mostly complete | Improved |
| New attack surface | — | 5 new modules | Requires attention |

**Overall assessment:** The core plugin is more secure than v2.0, but the 6 new modules (FlexibleContent, FrontendForm, BlockRegistration, GraphQL, LocalJson, AjaxHandler) each need targeted hardening before production deployment.
