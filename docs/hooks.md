# Hooks Reference

[Back to README](../README.md) | [Architecture](architecture.md)

The plugin provides 8 WordPress hooks (4 actions + 4 filters) for customizing behavior without modifying core files.

---

## Actions

### `cmb_before_render_field`

Fires before a field's HTML is rendered.

```php
do_action('cmb_before_render_field', array $field, WP_Post $post);
```

**Example:**

```php
add_action('cmb_before_render_field', function($field, $post) {
    if ($field['id'] === 'special_field') {
        echo '<div class="my-custom-wrapper">';
    }
}, 10, 2);
```

### `cmb_after_render_field`

Fires after a field's HTML is rendered.

```php
do_action('cmb_after_render_field', array $field, WP_Post $post);
```

### `cmb_before_save_field`

Fires before a field value is saved to the database.

```php
do_action('cmb_before_save_field', string $fieldId, mixed $rawValue, int $postId, array $field);
```

**Example:**

```php
add_action('cmb_before_save_field', function($fieldId, $raw, $postId, $field) {
    // Log field saves
    error_log("Saving field {$fieldId} on post {$postId}");
}, 10, 4);
```

### `cmb_after_save_field`

Fires after a field value is saved to the database.

```php
do_action('cmb_after_save_field', string $fieldId, mixed $sanitizedValue, int $postId, array $field);
```

**Example:**

```php
add_action('cmb_after_save_field', function($fieldId, $value, $postId, $field) {
    // Clear cache after save
    if ($fieldId === 'product_price') {
        delete_transient('product_prices');
    }
}, 10, 4);
```

---

## Filters

### `cmb_meta_box_args`

Modify meta box configuration before it's passed to `add_meta_box()`.

```php
$metaBox = apply_filters('cmb_meta_box_args', array $metaBox, string $id);
```

**Example:**

```php
add_filter('cmb_meta_box_args', function($metaBox, $id) {
    // Force all meta boxes to high priority
    $metaBox['priority'] = 'high';
    return $metaBox;
}, 10, 2);
```

### `cmb_field_value`

Modify a field's value after retrieval from the database.

```php
$value = apply_filters('cmb_field_value', mixed $value, string $fieldKey, int $postId);
```

**Example:**

```php
add_filter('cmb_field_value', function($value, $key, $postId) {
    // Decrypt sensitive fields on retrieval
    if ($key === 'api_key') {
        return my_decrypt($value);
    }
    return $value;
}, 10, 3);
```

### `cmb_field_html`

Modify the rendered HTML for a field.

```php
$html = apply_filters('cmb_field_html', string $html, array $field, WP_Post $post);
```

**Example:**

```php
add_filter('cmb_field_html', function($html, $field, $post) {
    // Add a tooltip to specific fields
    if (!empty($field['tooltip'])) {
        $html .= '<span class="tooltip">' . esc_html($field['tooltip']) . '</span>';
    }
    return $html;
}, 10, 3);
```

### `cmb_sanitize_{type}`

Modify the sanitized value for a specific field type. The `{type}` is the field type key (e.g., `cmb_sanitize_text`, `cmb_sanitize_number`).

```php
$sanitized = apply_filters('cmb_sanitize_text', mixed $sanitized, mixed $raw, array $field, int $postId);
```

**Example:**

```php
add_filter('cmb_sanitize_text', function($sanitized, $raw, $field, $postId) {
    // Force uppercase for specific fields
    if ($field['id'] === 'product_sku') {
        return strtoupper($sanitized);
    }
    return $sanitized;
}, 10, 4);
```

---

## Hook Execution Order

During save:

1. `cmb_before_save_field` (action)
2. Field sanitization (field class or custom callback)
3. `cmb_sanitize_{type}` (filter)
4. Database write
5. `cmb_after_save_field` (action)

During render:

1. `cmb_before_render_field` (action)
2. Field HTML rendering
3. `cmb_field_html` (filter)
4. `cmb_after_render_field` (action)

---

## Next Steps

- [Configuration Reference](configuration-reference.md) — field config options
- [Extending](extending.md) — create custom field types
- [Architecture](architecture.md) — full data flow details
