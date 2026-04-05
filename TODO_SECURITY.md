# TODO: Security Hardening

**Generated:** 2026-04-05
**Source:** Consolidated from AUDIT_SECURITY, AUDIT_WP_STANDARDS

Security hardening tasks beyond the critical fixes (those are in TODO_CRITICAL_FIXES.md).

---

## SEC-001: Mitigate PHP Object Injection via maybe_unserialize()

- **Title:** Object injection risk in FieldRenderer::get_field_value()
- **Description:** `get_field_value()` (lines 224-233) calls `maybe_unserialize()` on meta values. If an attacker can control serialized data in `wp_postmeta` (via another vulnerability or direct DB access), crafted payloads could trigger magic methods (`__wakeup`, `__destruct`) on loaded classes.
- **Root Cause:** Direct use of `maybe_unserialize()` on raw meta data.
- **Proposed Solution:**
  1. Switch from `get_post_meta($post_id)` (bulk, returns raw) to individual `get_post_meta($post_id, $key, true)` calls which handle deserialization through WordPress core safely.
  2. Long-term: migrate complex field storage from PHP serialization to JSON (`json_encode`/`json_decode`).
  3. If serialization is required, validate the unserialized structure against expected types.
- **Affected Files:**
  - `src/Core/FieldRenderer.php` (get_field_value, lines 224-233)
- **Estimated Effort:** 4 hours
- **Priority:** P1
- **Dependencies:** None

---

## SEC-002: Add wp_unslash() to All $_POST/$_GET Reads

- **Title:** Missing wp_unslash() on superglobal access
- **Description:** WordPress adds magic quotes to superglobals. All `$_POST` and `$_GET` values must be passed through `wp_unslash()` before use. Multiple locations lack this.
- **Root Cause:** Inconsistent application of WordPress input handling conventions.
- **Proposed Solution:**
  Add `wp_unslash()` to every `$_POST`/`$_GET` read:
  ```php
  $raw = wp_unslash($_POST[$fieldId] ?? '');
  ```
- **Affected Files:**
  - `src/Core/MetaBoxManager.php` (lines 138, 174)
  - `src/Core/TaxonomyMetaManager.php` (line 95)
  - `src/Core/UserMetaManager.php` (line 65)
  - `src/Core/AdminUI.php` (line 69, and throughout handleSave)
  - `src/Core/BulkOperations.php` (lines 104-109)
- **Estimated Effort:** 3 hours
- **Priority:** P1
- **Dependencies:** None

---

## SEC-003: Make Taxonomy Nonce Term-Specific

- **Title:** Shared nonce across all taxonomy terms enables cross-term manipulation
- **Description:** The nonce uses a single action string `cmb_taxonomy_save` for all taxonomies and all terms. A valid nonce for one term can be replayed to modify any other term's meta.
- **Root Cause:** Generic nonce action string.
- **Proposed Solution:**
  ```php
  // In renderFields/renderAddFields:
  wp_nonce_field('cmb_taxonomy_save_' . $taxonomy . '_' . $term->term_id, 'cmb_taxonomy_nonce');
  // In saveFields:
  wp_verify_nonce($_POST['cmb_taxonomy_nonce'], 'cmb_taxonomy_save_' . $taxonomy . '_' . $termId);
  ```
- **Affected Files:**
  - `src/Core/TaxonomyMetaManager.php` (lines 28, 61, 84)
- **Estimated Effort:** 1 hour
- **Priority:** P1
- **Dependencies:** CF-003

---

## SEC-004: Validate Regex Patterns in Validation Rules

- **Title:** ReDoS vector in pattern validation rule
- **Description:** The `pattern` validation rule passes user-defined regex directly to `preg_match()` without escaping delimiters or validating the pattern. Malformed patterns cause PHP warnings; pathological patterns enable ReDoS.
- **Root Cause:** No validation or escaping of regex patterns.
- **Proposed Solution:**
  ```php
  case 'pattern':
      if ($ruleParam !== null && $value !== '') {
          $escaped = '/' . str_replace('/', '\\/', $ruleParam) . '/';
          $prevLimit = ini_get('pcre.backtrack_limit');
          ini_set('pcre.backtrack_limit', '10000');
          $result = @preg_match($escaped, (string)$value);
          ini_set('pcre.backtrack_limit', $prevLimit);
          if ($result === false) {
              // Invalid pattern -- skip validation, log warning
          } elseif ($result === 0) {
              $errors[] = sprintf('%s format is invalid.', $label);
          }
      }
      break;
  ```
- **Affected Files:**
  - `src/Core/Contracts/Abstracts/AbstractField.php` (validate, line 94)
- **Estimated Effort:** 2 hours
- **Priority:** P1
- **Dependencies:** None

---

## SEC-005: Sanitize Select/Radio Option Values in AdminUI Save

- **Title:** Option keys and labels from textarea not sanitized
- **Description:** When parsing options from textarea in `handleSave()`, the `trim($val)` and `trim($lbl)` values from pipe-delimited lines are stored without `sanitize_text_field()`. Output escaping in field rendering mitigates direct XSS, but defense-in-depth requires input sanitization.
- **Root Cause:** Only the non-pipe path uses `sanitize_title()`.
- **Proposed Solution:**
  ```php
  $opts[sanitize_text_field(trim($val))] = sanitize_text_field(trim($lbl));
  ```
- **Affected Files:**
  - `src/Core/AdminUI.php` (handleSave, lines 900-913)
- **Estimated Effort:** 0.5 hours
- **Priority:** P1
- **Dependencies:** None

---

## SEC-006: Validate Custom Field Type Implements FieldInterface

- **Title:** registerFieldType() only checks class_exists, not interface compliance
- **Description:** `registerFieldType()` accepts any class that exists. If a class doesn't implement `FieldInterface`, calling `sanitize()` or `render()` on it will cause fatal errors.
- **Root Cause:** Missing interface check.
- **Proposed Solution:**
  ```php
  public static function registerFieldType(string $type, string $className): void {
      if (!class_exists($className)) {
          throw new \InvalidArgumentException("Class $className does not exist.");
      }
      if (!is_subclass_of($className, FieldInterface::class)) {
          _doing_it_wrong(__METHOD__, 'Class must implement FieldInterface.', '2.1');
          return;
      }
      self::$customFieldTypes[$type] = $className;
  }
  ```
- **Affected Files:**
  - `src/Core/MetaBoxManager.php` (registerFieldType, lines 21-29)
- **Estimated Effort:** 0.5 hours
- **Priority:** P1
- **Dependencies:** None

---

## SEC-007: Fix Password Field Security Issues

- **Title:** Password field stores plain text and renders value back in HTML
- **Description:** `PasswordField` sanitizes with `sanitize_text_field()` (which alters special characters), stores in plain text in `wp_postmeta`, and renders the stored value back into the `value` attribute (visible in page source).
- **Root Cause:** No encryption or masking logic implemented.
- **Proposed Solution:**
  1. Don't render stored password value back -- use empty value with placeholder "Password is set".
  2. Document that this field is for API key entry, not credential storage.
  3. Consider adding optional encryption via `openssl_encrypt()` / `openssl_decrypt()`.
  4. Use `sanitize_text_field()` replacement that preserves special characters for passwords.
- **Affected Files:**
  - `src/Fields/PasswordField.php` (render and sanitize methods)
- **Estimated Effort:** 3 hours
- **Priority:** P1
- **Dependencies:** None

---

## SEC-008: Add WP-CLI Sanitization Through Field Pipeline

- **Title:** wp cmb set bypasses field sanitization
- **Description:** The `setField()` WP-CLI command stores values directly via `update_post_meta()` without running them through the field's `sanitize()` method.
- **Root Cause:** CLI path wasn't integrated with the field type system.
- **Proposed Solution:**
  Look up field config, instantiate the field class, run sanitization:
  ```php
  $instance = FieldFactory::create($field['type'], $field);
  $value = $instance->sanitize($value);
  update_post_meta($postId, $fieldId, $value);
  ```
- **Affected Files:**
  - `src/Core/WpCliCommands.php` (setField, lines 108-126)
- **Estimated Effort:** 2 hours
- **Priority:** P2
- **Dependencies:** RF-001

---

## SEC-009: Add Import File Size Limit

- **Title:** No size limit on JSON import file
- **Description:** `handleImport()` reads the uploaded file with `file_get_contents()` and calls `json_decode()` without checking size. A multi-MB JSON file could exhaust memory.
- **Root Cause:** No size validation.
- **Proposed Solution:**
  ```php
  $fileSize = $_FILES['cmb_import_file']['size'] ?? 0;
  if ($fileSize > 1024 * 1024) { // 1 MB limit
      wp_die('Import file is too large. Maximum size is 1 MB.');
  }
  ```
- **Affected Files:**
  - `src/Core/AdminUI.php` (handleImport)
  - `src/Core/ImportExport.php` (importFromJson)
- **Estimated Effort:** 0.5 hours
- **Priority:** P2
- **Dependencies:** None

---

## SEC-010: Add Cache-Control Headers to Export Response

- **Title:** Export endpoint has predictable URL pattern
- **Description:** The export handler outputs full field configurations as JSON. If a nonce is leaked, the configuration could be exposed. Adding `Cache-Control: no-store` prevents caching of sensitive data.
- **Root Cause:** No cache control headers set on export response.
- **Proposed Solution:**
  ```php
  header('Cache-Control: no-store, no-cache, must-revalidate');
  header('Pragma: no-cache');
  ```
  Add before the JSON output in `handleExport()`.
- **Affected Files:**
  - `src/Core/AdminUI.php` (handleExport)
- **Estimated Effort:** 0.5 hours
- **Priority:** P2
- **Dependencies:** None

---

## Summary

| ID | Title | Priority | Effort (hrs) |
|----|-------|----------|--------------|
| SEC-001 | Mitigate object injection | P1 | 4 |
| SEC-002 | Add wp_unslash() everywhere | P1 | 3 |
| SEC-003 | Term-specific taxonomy nonce | P1 | 1 |
| SEC-004 | Validate regex patterns | P1 | 2 |
| SEC-005 | Sanitize option values | P1 | 0.5 |
| SEC-006 | Validate field type interface | P1 | 0.5 |
| SEC-007 | Fix password field security | P1 | 3 |
| SEC-008 | WP-CLI sanitization | P2 | 2 |
| SEC-009 | Import file size limit | P2 | 0.5 |
| SEC-010 | Export cache control headers | P2 | 0.5 |
| **Total** | | | **17** |
