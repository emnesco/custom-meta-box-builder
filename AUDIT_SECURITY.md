# Security Audit Report: Custom Meta Box Builder v2.0

**Audit Date:** 2026-04-05
**Auditor:** Security Audit Agent
**Scope:** Full source code review (all PHP, JS files excluding vendor/)
**Methodology:** Manual static analysis with attacker-perspective threat modeling

---

## 1. Executive Summary

**Overall Risk Rating: MEDIUM**

The Custom Meta Box Builder plugin demonstrates generally competent security practices for a WordPress plugin. Nonce verification and capability checks are present on all admin action handlers. Output escaping is applied consistently in field rendering. Sanitization callbacks exist for every field type.

However, several vulnerabilities were identified ranging from Medium to Low severity. The most significant issues involve: (1) unsanitized imported field configurations that bypass the normal sanitization pipeline, (2) potential object injection via `maybe_unserialize()`, (3) a ReDoS vector in validation, and (4) missing capability checks in the TaxonomyMetaManager. No Critical vulnerabilities were found.

**Findings Summary:**
| Severity | Count |
|----------|-------|
| Critical | 0 |
| High     | 2 |
| Medium   | 5 |
| Low      | 5 |
| Info     | 3 |

---

## 2. Detailed Vulnerability Findings

---

### FINDING-01: Stored XSS via Unsanitized Imported Field Configurations

**Severity: HIGH**
**CVSS Estimate: 7.2 (High)**
**File:** `src/Core/AdminUI.php`, lines 1151-1165 (handleImport method)
**Function:** `AdminUI::handleImport()`

**Description:**
When importing JSON configurations, the `handleImport()` method sanitizes the top-level `title`, `postTypes`, `context`, and `priority` values, but the `fields` array is stored **verbatim** without any sanitization:

```php
$configs[$id] = [
    'title'        => sanitize_text_field($box['title']),
    'postTypes'    => array_map('sanitize_text_field', $box['postTypes'] ?? ['post']),
    'fields'       => $box['fields'] ?? [],  // <-- NO SANITIZATION
    'context'      => sanitize_text_field($box['context'] ?? 'advanced'),
    // ...
];
```

When these fields are later registered via `registerSavedBoxes()` (line 1175-1199) and rendered through `FieldRenderer::render()`, the `field['type']` value is used to construct CSS class names without escaping:

```php
// FieldRenderer.php, line 114
$output = '<div class="cmb-field ' . $layout . ' cmb-type-' . $field['type'] . ' ' . $repeat . ' ' . $width . ' ' . $required_class . '"' . $conditionalAttrs . '>';
```

The `$field['type']`, `$width`, and `$layout` values are inserted directly into HTML class attributes without `esc_attr()`. While `$field['type']` goes through `transformFieldsForRegistration()` which copies it directly, the `width` field is also copied without sanitization.

**Attack Vector:**
1. Attacker with `manage_options` capability crafts a malicious JSON import file
2. The `fields` array contains a field with `'type' => 'text" onmouseover="alert(1)'` or a malicious `width` value
3. The stored configuration is loaded on every page where the meta box renders
4. Any admin editing a post with this meta box triggers the XSS

**Impact:** Stored XSS affecting all administrators who edit posts with the compromised meta box. In multi-admin environments, this enables privilege escalation or session hijacking.

**Remediation:**
1. Apply the same field sanitization logic used in `handleSave()` to imported fields in `handleImport()`
2. In `FieldRenderer::render()`, escape all dynamic values used in HTML attributes:
   ```php
   $output = '<div class="cmb-field ' . esc_attr($layout) . ' cmb-type-' . esc_attr($field['type']) . ' ' . esc_attr($repeat) . ' ' . esc_attr($width) . ' ' . esc_attr($required_class) . '"' . $conditionalAttrs . '>';
   ```

---

### FINDING-02: PHP Object Injection via maybe_unserialize()

**Severity: HIGH**
**CVSS Estimate: 7.5 (High)**
**File:** `src/Core/FieldRenderer.php`, lines 224-233
**Function:** `FieldRenderer::get_field_value()`

**Description:**
The `get_field_value()` method calls `maybe_unserialize()` on meta values retrieved from the database:

```php
return array_map(function ($v) {
    return is_serialized($v) ? maybe_unserialize($v) : $v;
}, $meta);
// ...
$val = is_serialized($val) ? maybe_unserialize($val) : $val;
```

`maybe_unserialize()` calls PHP's `unserialize()` internally, which is vulnerable to PHP Object Injection (POI) if an attacker can control the serialized data in `wp_postmeta`. While WordPress core uses `maybe_unserialize()` extensively, the risk is elevated here because:

1. The plugin stores complex data structures (group fields, arrays) as serialized meta
2. If any other vulnerability allows writing arbitrary meta values (e.g., SQL injection in another plugin), this becomes an exploitation vector
3. The WP-CLI `cmb set` command (line 119-124 in `WpCliCommands.php`) accepts JSON input and stores it via `update_post_meta()`, which serializes complex values

**Attack Vector:**
1. Attacker compromises a meta value through another vulnerability or direct DB access
2. Crafted serialized payload triggers magic methods (__wakeup, __destruct) on loaded classes
3. Could lead to Remote Code Execution depending on available gadget chains

**Impact:** Potential Remote Code Execution if exploitable gadget chains exist in the WordPress/plugin ecosystem.

**Remediation:**
1. Avoid calling `maybe_unserialize()` directly; use `get_post_meta()` with the `$single` parameter which handles deserialization through WordPress core safely
2. Consider using `json_encode`/`json_decode` instead of PHP serialization for storing complex field values
3. If serialization is required, use WordPress's `wp_unslash()` and validate the unserialized structure

---

### FINDING-03: Missing Capability Check in TaxonomyMetaManager::saveFields()

**Severity: MEDIUM**
**CVSS Estimate: 5.3 (Medium)**
**File:** `src/Core/TaxonomyMetaManager.php`, lines 84-100
**Function:** `TaxonomyMetaManager::saveFields()`

**Description:**
The `saveFields()` method verifies the nonce but does **not** check if the current user has the capability to edit the taxonomy term:

```php
public function saveFields(int $termId): void {
    if (!isset($_POST['cmb_taxonomy_nonce']) || !wp_verify_nonce($_POST['cmb_taxonomy_nonce'], 'cmb_taxonomy_save')) {
        return;
    }
    // NO capability check like: if (!current_user_can('edit_term', $termId)) return;

    foreach ($this->taxonomyBoxes as $fields) {
        foreach ($fields as $field) {
            // ... saves meta directly
        }
    }
}
```

Compare this with `UserMetaManager::saveFields()` which correctly checks `current_user_can('edit_user', $userId)` (line 56) and `MetaBoxManager::saveMetaBoxData()` which checks `current_user_can('edit_post', $postId)` (line 133).

**Attack Vector:**
A low-privileged user who can submit a form with a valid nonce (e.g., through CSRF or if they have limited taxonomy access) could modify term meta they shouldn't have access to.

**Impact:** Unauthorized modification of taxonomy term metadata.

**Remediation:**
Add a capability check:
```php
if (!current_user_can('edit_term', $termId)) {
    return;
}
```

---

### FINDING-04: ReDoS in Validation Pattern Rule

**Severity: MEDIUM**
**CVSS Estimate: 5.3 (Medium)**
**File:** `src/Core/Contracts/Abstracts/AbstractField.php`, lines 93-96
**Function:** `AbstractField::validate()`

**Description:**
The `pattern` validation rule passes a user-defined regex pattern directly to `preg_match()`:

```php
case 'pattern':
    if ($ruleParam !== null && $value !== '' && !preg_match('/' . $ruleParam . '/', (string)$value)) {
        $errors[] = sprintf('%s format is invalid.', $label);
    }
    break;
```

The `$ruleParam` comes from the field configuration (e.g., `'validate' => ['pattern:^[a-z]+$']`). If a developer provides a poorly crafted regex or if an attacker can control field configurations (via the Admin UI import), a Regular Expression Denial of Service (ReDoS) is possible.

Additionally, the pattern is concatenated into the regex delimiter without escaping, meaning a pattern containing `/` would break the regex or could be used for regex injection.

**Attack Vector:**
1. Import a field group with a validation rule like `pattern:(a+)+$`
2. Submit a value like `aaaaaaaaaaaaaaaaaaaab` to the field
3. The catastrophic backtracking causes PHP to hang/timeout

**Impact:** Denial of Service affecting the WordPress admin panel during post saves.

**Remediation:**
1. Escape the regex delimiter: `preg_match('/' . str_replace('/', '\/', $ruleParam) . '/', ...)`
2. Set a PCRE backtrack limit for validation: `ini_set('pcre.backtrack_limit', 10000)` before matching
3. Validate the regex pattern before use with `@preg_match('/' . $ruleParam . '/', '')` error check

---

### FINDING-05: Unescaped HTML Class Attributes in FieldRenderer

**Severity: MEDIUM**
**CVSS Estimate: 4.7 (Medium)**
**File:** `src/Core/FieldRenderer.php`, line 114
**Function:** `FieldRenderer::render()`

**Description:**
Multiple dynamic values are concatenated directly into HTML class attributes without `esc_attr()`:

```php
$output = '<div class="cmb-field ' . $layout . ' cmb-type-' . $field['type'] . ' ' . $repeat . ' ' . $width . ' ' . $required_class . '"' . $conditionalAttrs . '>';
```

The variables `$layout`, `$field['type']`, `$repeat`, `$width`, and `$required_class` are derived from field configuration. While most of these are set internally, `$width` comes from `$field['width']` and `$layout` from `$field['layout']` -- both controlled by field configuration which can be imported without sanitization (see FINDING-01).

Similarly in `renderMultilingualField()` (line 171):
```php
$output = '<div class="cmb-field ' . $layout . ' cmb-type-' . $field['type'] . ' cmb-multilingual">';
```

**Attack Vector:**
A malicious field configuration with `'width' => '100" onclick="alert(1)" class="'` could break out of the class attribute.

**Impact:** Stored XSS in the WordPress admin post editor.

**Remediation:**
Escape all dynamically generated attribute values:
```php
$output = '<div class="cmb-field ' . esc_attr($layout) . ' cmb-type-' . esc_attr($field['type']) . ' ' . esc_attr($repeat) . ' ' . esc_attr($width) . ' ' . esc_attr($required_class) . '"' . $conditionalAttrs . '>';
```

---

### FINDING-06: Shared Nonce Across All Taxonomy Instances

**Severity: MEDIUM**
**CVSS Estimate: 4.3 (Medium)**
**File:** `src/Core/TaxonomyMetaManager.php`, lines 28, 61
**Function:** `TaxonomyMetaManager::renderFields()` and `renderAddFields()`

**Description:**
The nonce uses a single action string `cmb_taxonomy_save` for all taxonomies and all terms:

```php
wp_nonce_field('cmb_taxonomy_save', 'cmb_taxonomy_nonce');
```

This means a valid nonce for saving one taxonomy term's meta can be replayed to save meta on any other term of any taxonomy managed by this plugin. Combined with the missing capability check (FINDING-03), this increases the attack surface.

**Impact:** Cross-term meta manipulation using a single captured nonce.

**Remediation:**
Include the taxonomy name and term ID in the nonce action:
```php
wp_nonce_field('cmb_taxonomy_save_' . $taxonomy . '_' . $term->term_id, 'cmb_taxonomy_nonce');
```

---

### FINDING-07: Option Values Not Sanitized in Select/Radio Import

**Severity: MEDIUM**
**CVSS Estimate: 4.3 (Medium)**
**File:** `src/Core/AdminUI.php`, lines 900-913
**Function:** `AdminUI::handleSave()`

**Description:**
When parsing options from the textarea in `handleSave()`, the option keys and labels are `trim()`-ed but not sanitized:

```php
if (strpos($line, '|') !== false) {
    [$val, $lbl] = explode('|', $line, 2);
    $opts[trim($val)] = trim($lbl);
} else {
    $opts[sanitize_title($line)] = $line;
}
```

The `trim($val)` and `trim($lbl)` values are stored in the options database without `sanitize_text_field()`. When these options are later rendered in `SelectField::render()` and `RadioField::render()`, they are properly escaped with `esc_attr()` and `esc_html()`, mitigating direct XSS. However, storing unsanitized data violates defense-in-depth principles.

**Impact:** Stored unsanitized data in the database; low direct risk due to output escaping.

**Remediation:**
Sanitize option keys and labels:
```php
$opts[sanitize_text_field(trim($val))] = sanitize_text_field(trim($lbl));
```

---

### FINDING-08: Password Field Values Rendered in HTML

**Severity: LOW**
**CVSS Estimate: 3.1 (Low)**
**File:** `src/Fields/PasswordField.php`, line 11
**Function:** `PasswordField::render()`

**Description:**
The password field renders the stored value back into the `value` attribute:

```php
return '<input type="password" name="' . esc_attr($this->getName()) . '"' . $id_attr . ' value="' . esc_attr($value ?? '') . '"' . ...;
```

While the value is properly escaped, rendering stored passwords back into HTML forms is a security anti-pattern. The password value is visible in the page source/DOM and could be exposed through:
- Browser developer tools
- Browser extensions
- Page source caching
- Automated vulnerability scanners

Additionally, `PasswordField::sanitize()` uses `sanitize_text_field()` which is not appropriate for passwords -- it strips tags and may alter the password value.

**Impact:** Information disclosure of stored password values through HTML source inspection.

**Remediation:**
1. Do not render stored password values back; use an empty value with a placeholder indicating a password is set
2. Consider encrypting stored password values using `wp_hash_password()` or a reversible encryption if the value must be retrieved
3. Use a password-appropriate sanitization that preserves special characters

---

### FINDING-09: WP-CLI Commands Bypass Plugin Sanitization

**Severity: LOW**
**CVSS Estimate: 3.3 (Low)**
**File:** `src/Core/WpCliCommands.php`, lines 108-126
**Function:** `WpCliCommands::setField()`

**Description:**
The `wp cmb set` command stores values directly via `update_post_meta()` without running them through the field's sanitization pipeline:

```php
$value = $args[2];
$decoded = json_decode($value, true);
if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
    $value = $decoded;
}
update_post_meta($postId, $fieldId, $value);
```

This bypasses the type-specific `sanitize()` methods that would normally clean input. While WP-CLI access already implies server-level access, this could lead to storing inconsistent or malformed data that causes issues when rendered.

**Impact:** Bypassed sanitization for data stored via CLI; requires server access.

**Remediation:**
Look up the field configuration, instantiate the appropriate field class, and run the sanitization:
```php
$instance = new $fieldClass($field);
$value = $instance->sanitize($value);
```

---

### FINDING-10: Bulk Operations Allow Unbounded Query

**Severity: LOW**
**CVSS Estimate: 3.1 (Low)**
**File:** `src/Core/BulkOperations.php`, lines 120-127
**Function:** `BulkOperations::handleBulkUpdate()`

**Description:**
When no post IDs are specified, the bulk operation queries **all** posts of the selected type with no limit:

```php
$posts = get_posts([
    'post_type' => $postType,
    'posts_per_page' => -1,
    'fields' => 'ids',
    'post_status' => 'any',
]);
```

On sites with large content databases, this could cause memory exhaustion or timeout, effectively creating a Denial of Service condition. The `manage_options` capability requirement limits this to administrators.

**Impact:** Potential DoS through memory exhaustion on large sites; admin-only access.

**Remediation:**
1. Add a configurable batch size limit (e.g., 500 posts per batch)
2. Implement batch processing with pagination
3. Add a confirmation step showing the number of affected posts before execution

---

### FINDING-11: Custom Field Type Registration Allows Arbitrary Class Instantiation

**Severity: LOW**
**CVSS Estimate: 3.7 (Low)**
**File:** `src/Core/MetaBoxManager.php`, lines 21-29, 251-268
**Functions:** `MetaBoxManager::registerFieldType()`, `MetaBoxManager::resolveFieldClass()`

**Description:**
`registerFieldType()` only checks that the class exists via `class_exists()` but does not verify that it implements `FieldInterface`:

```php
public static function registerFieldType(string $type, string $className): void {
    if (!class_exists($className)) { ... }
    self::$customFieldTypes[$type] = $className;
}
```

When `resolveFieldClass()` returns this class, it is instantiated and its `sanitize()` and `render()` methods are called. If a developer registers a class that doesn't implement `FieldInterface`, it could cause fatal errors or, worse, if a malicious class is registered, its constructor or methods could execute arbitrary code.

**Impact:** If an attacker can call `registerFieldType()` (requires PHP code execution), they could register a malicious class. Low real-world risk since it requires existing code execution.

**Remediation:**
Add an interface check:
```php
if (!is_subclass_of($className, FieldInterface::class)) {
    _doing_it_wrong(__METHOD__, 'Class must implement FieldInterface.', '2.1');
    return;
}
```

---

### FINDING-12: Export Endpoint Information Disclosure

**Severity: LOW**
**CVSS Estimate: 2.7 (Low)**
**File:** `src/Core/AdminUI.php`, lines 1069-1110
**Function:** `AdminUI::handleExport()`

**Description:**
The export handler outputs the complete field group configuration as JSON. While it properly checks `manage_options` capability and nonce verification, the exported data includes all field definitions, labels, IDs, options, and structure. This is by design for import/export functionality, but the export URL pattern is predictable:

```
admin.php?page=cmb-builder&cmb_export=all&_wpnonce=...
```

If a nonce value is leaked (e.g., through a logged URL), the full configuration could be exposed.

**Impact:** Exposure of site's custom field structure; requires valid nonce.

**Remediation:**
This is acceptable behavior for an admin-only feature. Consider adding a `Cache-Control: no-store` header to the export response.

---

## 3. Positive Security Findings

The following security practices are correctly implemented:

1. **Nonce Verification:** All admin action handlers (`handleSave`, `handleDelete`, `handleDuplicate`, `handleToggle`, `handleExport`, `handleImport`) verify nonces before processing.

2. **Capability Checks:** Most handlers verify `manage_options` capability. `MetaBoxManager::saveMetaBoxData()` checks `edit_post`. `UserMetaManager::saveFields()` checks `edit_user`.

3. **Output Escaping in Field Rendering:** All field classes (`TextField`, `TextareaField`, `SelectField`, `RadioField`, `CheckboxField`, etc.) properly use `esc_attr()`, `esc_html()`, and `esc_textarea()` for output escaping.

4. **Type-Specific Sanitization:** Each field class implements appropriate sanitization: `sanitize_text_field()` for text, `sanitize_email()` for email, `esc_url_raw()` for URLs, `absint()` for numeric/post/user/taxonomy IDs, `wp_kses_post()` for WYSIWYG, regex validation for colors and dates.

5. **ABSPATH Check:** The main plugin file checks `defined('ABSPATH') || exit;` to prevent direct access.

6. **Safe Redirects:** All redirects use `wp_safe_redirect()`.

7. **File Upload Security:** The `FileField` uses the WordPress Media Library (`wp.media`) rather than direct file uploads, which leverages WordPress's built-in file handling security.

8. **Autosave Check:** `MetaBoxManager::saveMetaBoxData()` properly checks `DOING_AUTOSAVE`.

9. **REST API Registration:** Uses `register_post_meta()` which leverages WordPress core's REST API security.

10. **Admin UI Attribute Escaping:** The AdminUI form rendering extensively uses `esc_attr()` for all form input values.

---

## 4. Client-Side Security Analysis

### JavaScript Files Reviewed:
- `assets/cmb-script.js` -- Frontend field interactions
- `assets/cmb-admin.js` -- Admin builder UI
- `assets/cmb-gutenberg.js` -- Gutenberg panel integration

### Findings:

**JS-01: DOM-Based XSS Risk in File Upload Preview (Low)**
**File:** `assets/cmb-script.js`, lines 201-208

```javascript
if (attachment.type === 'image' && attachment.sizes && attachment.sizes.thumbnail) {
    $preview.html('<img src="' + attachment.sizes.thumbnail.url + '" ...>');
} else {
    $preview.html('<a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a>');
}
```

The `attachment.filename` is inserted via `.html()` without escaping. If a file is uploaded with a name containing HTML/JS (e.g., `"><img src=x onerror=alert(1)>.jpg`), it could execute. However, WordPress's media library sanitizes filenames on upload, mitigating this in practice.

**JS-02: Code Preview PHP Escaping is Incomplete (Info)**
**File:** `assets/cmb-admin.js`, lines 681-683

The `escPhp()` function only escapes backslashes and single quotes:
```javascript
function escPhp(str) {
    return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}
```

This is used for the code preview feature only and the generated code is displayed in a `<pre>` tag (never executed), so the risk is informational.

**JS-03: Gutenberg Panel Uses WordPress Core Components (Positive)**
The Gutenberg integration properly uses WordPress core data store (`wp.data`) and components, inheriting WordPress's built-in security for meta value handling.

---

## 5. Architecture-Level Observations

### 5.1 Data Flow Summary

```
User Input --> $_POST --> nonce check --> capability check --> sanitize() --> update_post_meta()
                                                                              |
Database --> get_post_meta() --> maybe_unserialize() --> render() --> esc_attr/esc_html --> HTML output
```

The forward path (input to storage) is well-protected. The reverse path (storage to output) has the `maybe_unserialize()` concern (FINDING-02) and unescaped class attributes (FINDING-05).

### 5.2 Import Path Bypass

```
JSON Import --> handleImport() --> fields stored without sanitization --> registerSavedBoxes() --> render()
```

This path bypasses the per-field sanitization that the normal `handleSave()` path provides (FINDING-01).

### 5.3 No SQL Injection Risks

The plugin exclusively uses WordPress meta API functions (`get_post_meta`, `update_post_meta`, `delete_post_meta`, `get_term_meta`, `update_term_meta`, `get_user_meta`, `update_user_meta`, `get_posts`, `get_terms`, `get_users`) and does not use `$wpdb` directly. No raw SQL queries were found.

### 5.4 No Direct File System Operations

Except for reading the import file via `file_get_contents($_FILES['cmb_import_file']['tmp_name'])`, the plugin does not perform file system operations. The uploaded import file is a JSON file read from the PHP temp directory, which is acceptable.

---

## 6. Remediation Priority Matrix

| Priority | Finding | Effort | Impact |
|----------|---------|--------|--------|
| 1 | FINDING-01: Import field sanitization bypass | Low | High |
| 2 | FINDING-05: Unescaped HTML class attributes | Low | Medium |
| 3 | FINDING-03: Missing taxonomy capability check | Low | Medium |
| 4 | FINDING-02: Object injection via maybe_unserialize | Medium | High |
| 5 | FINDING-06: Shared taxonomy nonce | Low | Medium |
| 6 | FINDING-04: ReDoS in pattern validation | Low | Medium |
| 7 | FINDING-07: Unsanitized option values | Low | Low |
| 8 | FINDING-08: Password value rendering | Low | Low |
| 9 | FINDING-11: Custom field type interface check | Low | Low |
| 10 | FINDING-09: WP-CLI sanitization bypass | Medium | Low |
| 11 | FINDING-10: Unbounded bulk query | Medium | Low |
| 12 | FINDING-12: Export information disclosure | Low | Low |

---

## 7. Files Audited

### PHP Files (19 files)
- `custom-meta-box-builder.php`
- `public-api.php`
- `src/Core/Plugin.php`
- `src/Core/AdminUI.php`
- `src/Core/MetaBoxManager.php`
- `src/Core/FieldRenderer.php`
- `src/Core/ImportExport.php`
- `src/Core/BulkOperations.php`
- `src/Core/DependencyGraph.php`
- `src/Core/GutenbergPanel.php`
- `src/Core/OptionsManager.php`
- `src/Core/TaxonomyMetaManager.php`
- `src/Core/UserMetaManager.php`
- `src/Core/WpCliCommands.php`
- `src/Core/Contracts/FieldInterface.php`
- `src/Core/Contracts/Abstracts/AbstractField.php`
- `src/Core/Traits/MultiLanguageTrait.php`
- `src/Core/Traits/ArrayAccessibleTrait.php`

### Field Classes (16 files)
- `src/Fields/TextField.php`
- `src/Fields/TextareaField.php`
- `src/Fields/NumberField.php`
- `src/Fields/EmailField.php`
- `src/Fields/UrlField.php`
- `src/Fields/PasswordField.php`
- `src/Fields/HiddenField.php`
- `src/Fields/SelectField.php`
- `src/Fields/RadioField.php`
- `src/Fields/CheckboxField.php`
- `src/Fields/DateField.php`
- `src/Fields/ColorField.php`
- `src/Fields/FileField.php`
- `src/Fields/GroupField.php`
- `src/Fields/WysiwygField.php`
- `src/Fields/PostField.php`
- `src/Fields/TaxonomyField.php`
- `src/Fields/UserField.php`

### JavaScript Files (3 files)
- `assets/cmb-script.js`
- `assets/cmb-admin.js`
- `assets/cmb-gutenberg.js`

### Test Files (3 files)
- `tests/bootstrap.php`
- `tests/MetaBoxManagerTest.php`
- `tests/PluginTest.php`
- `tests/TextFieldTest.php`

---

*End of Security Audit Report*
