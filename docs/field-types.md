# Field Types

[Back to README](../README.md) | [Configuration Reference](configuration-reference.md)

The plugin ships with 18 built-in field types. Each is a class in the `CMB\Fields` namespace that extends `AbstractField`.

---

## Text

Renders an `<input type="text">` element. Supports repeatable mode.

**Type key:** `text`
**Class:** `CMB\Fields\TextField`

```php
[
    'id'         => 'my_text',
    'type'       => 'text',
    'label'      => 'Title',
    'description'=> 'Enter a short title.',
    'required'   => true,
    'attributes' => ['placeholder' => 'e.g. My Post Title'],
]
```

**Sanitization:** `sanitize_text_field()`

---

## Textarea

Renders a `<textarea>` element.

**Type key:** `textarea`
**Class:** `CMB\Fields\TextareaField`

```php
[
    'id'         => 'my_textarea',
    'type'       => 'textarea',
    'label'      => 'Description',
    'attributes' => ['rows' => '6'],
]
```

**Sanitization:** `sanitize_textarea_field()`

---

## Number

Renders an `<input type="number">` with `min`, `max`, and `step` support.

**Type key:** `number`
**Class:** `CMB\Fields\NumberField`

```php
[
    'id'         => 'quantity',
    'type'       => 'number',
    'label'      => 'Quantity',
    'attributes' => ['min' => '0', 'max' => '1000', 'step' => '1'],
]
```

**Sanitization:** `intval()` or `floatval()` based on the `step` attribute.

---

## Email

Renders an `<input type="email">`.

**Type key:** `email`
**Class:** `CMB\Fields\EmailField`

```php
[
    'id'    => 'contact_email',
    'type'  => 'email',
    'label' => 'Email Address',
]
```

**Sanitization:** `sanitize_email()`

---

## URL

Renders an `<input type="url">`.

**Type key:** `url`
**Class:** `CMB\Fields\UrlField`

```php
[
    'id'    => 'website',
    'type'  => 'url',
    'label' => 'Website URL',
]
```

**Sanitization:** `esc_url_raw()`

---

## Select

Renders a `<select>` dropdown. Requires an `options` key.

**Type key:** `select`
**Class:** `CMB\Fields\SelectField`

```php
[
    'id'      => 'my_select',
    'type'    => 'select',
    'label'   => 'Category',
    'options' => [
        ''         => '— Select —',
        'option_a' => 'Option A',
        'option_b' => 'Option B',
    ],
]
```

**Sanitization:** Validates the submitted value exists in the `options` array.

---

## Radio

Renders a `<fieldset>` with `<input type="radio">` buttons. Uses the same `options` config as Select.

**Type key:** `radio`
**Class:** `CMB\Fields\RadioField`

```php
[
    'id'      => 'alignment',
    'type'    => 'radio',
    'label'   => 'Text Alignment',
    'options' => [
        'left'   => 'Left',
        'center' => 'Center',
        'right'  => 'Right',
    ],
]
```

**Sanitization:** Options whitelist validation.

---

## Checkbox

Renders an `<input type="checkbox">`.

**Type key:** `checkbox`
**Class:** `CMB\Fields\CheckboxField`

```php
[
    'id'    => 'my_checkbox',
    'type'  => 'checkbox',
    'label' => 'Featured Post',
]
```

**Stored value:** `'1'` when checked, `'0'` when unchecked.

---

## Hidden

Renders an `<input type="hidden">`. No label is displayed.

**Type key:** `hidden`
**Class:** `CMB\Fields\HiddenField`

```php
[
    'id'      => 'form_version',
    'type'    => 'hidden',
    'default' => '2.0',
]
```

**Sanitization:** `sanitize_text_field()`

---

## Password

Renders an `<input type="password">`.

**Type key:** `password`
**Class:** `CMB\Fields\PasswordField`

```php
[
    'id'    => 'api_key',
    'type'  => 'password',
    'label' => 'API Key',
]
```

**Sanitization:** `sanitize_text_field()`

---

## Date

Renders an `<input type="date">` or `<input type="datetime-local">` depending on the `format` config.

**Type key:** `date`
**Class:** `CMB\Fields\DateField`

```php
[
    'id'     => 'event_date',
    'type'   => 'date',
    'label'  => 'Event Date',
]

// For datetime:
[
    'id'     => 'event_datetime',
    'type'   => 'date',
    'label'  => 'Event Date & Time',
    'format' => 'datetime-local',
]
```

**Sanitization:** ISO 8601 format validation regex.

---

## Color

Renders an `<input type="color">` color picker.

**Type key:** `color`
**Class:** `CMB\Fields\ColorField`

```php
[
    'id'      => 'brand_color',
    'type'    => 'color',
    'label'   => 'Brand Color',
    'default' => '#2271b1',
]
```

**Sanitization:** Hex color validation (`/^#[a-fA-F0-9]{6}$/`).

---

## WYSIWYG

Renders a WordPress TinyMCE/`wp_editor()` rich text editor.

**Type key:** `wysiwyg`
**Class:** `CMB\Fields\WysiwygField`

```php
[
    'id'    => 'page_content',
    'type'  => 'wysiwyg',
    'label' => 'Page Content',
]
```

**Sanitization:** `wp_kses_post()`

**Note:** Uses `ob_start()`/`ob_get_clean()` to capture `wp_editor()` output.

---

## File

Renders a file/image upload field using the WordPress Media Library.

**Type key:** `file`
**Class:** `CMB\Fields\FileField`

```php
[
    'id'    => 'featured_image',
    'type'  => 'file',
    'label' => 'Featured Image',
]
```

Includes an upload button, image preview, and remove button. The stored value is the attachment ID.

**Sanitization:** `absint()` (attachment ID).

---

## Post

Renders a `<select>` dropdown populated with posts via `get_posts()`.

**Type key:** `post`
**Class:** `CMB\Fields\PostField`

```php
[
    'id'        => 'related_post',
    'type'      => 'post',
    'label'     => 'Related Post',
    'post_type' => 'post',     // Defaults to 'post'
    'query'     => [           // Optional: custom get_posts() args
        'posts_per_page' => 50,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ],
]
```

**Sanitization:** `absint()` (post ID).

---

## Taxonomy

Renders a checkbox list or `<select>` dropdown populated with taxonomy terms.

**Type key:** `taxonomy`
**Class:** `CMB\Fields\TaxonomyField`

```php
[
    'id'       => 'categories',
    'type'     => 'taxonomy',
    'label'    => 'Categories',
    'taxonomy' => 'category',
    'display'  => 'checkbox',  // 'checkbox' (default) or 'select'
]
```

**Sanitization:** `absint()` (term IDs).

---

## User

Renders a `<select>` dropdown populated with WordPress users.

**Type key:** `user`
**Class:** `CMB\Fields\UserField`

```php
[
    'id'    => 'assigned_to',
    'type'  => 'user',
    'label' => 'Assigned To',
    'role'  => 'editor',  // Optional: filter by role
]
```

**Sanitization:** `absint()` (user ID).

---

## Group

Renders a collapsible container of nested sub-fields. Groups are **collapsed by default** and can be repeatable, sortable, and can contain other groups for unlimited nesting depth. Set `'collapsed' => false` to start expanded.

**Type key:** `group`
**Class:** `CMB\Fields\GroupField`

```php
[
    'id'              => 'team_members',
    'type'            => 'group',
    'label'           => 'Team Member',
    'repeat'          => true,
    'collapsed'       => true,
    'row_title_field' => 'name',  // Use sub-field value as row title
    'searchable'      => true,    // Add search filter for 10+ items
    'min_rows'        => 1,
    'max_rows'        => 20,
    'fields'          => [
        ['id' => 'name',  'type' => 'text',     'label' => 'Name'],
        ['id' => 'role',  'type' => 'select',   'label' => 'Role', 'options' => ['dev' => 'Developer', 'design' => 'Designer']],
        ['id' => 'bio',   'type' => 'textarea', 'label' => 'Bio'],
        ['id' => 'photo', 'type' => 'file',     'label' => 'Photo'],
    ],
]
```

See [Groups & Repeaters](groups-and-repeaters.md) for full details.

**Sanitization:** Recursive per-field sanitization using proper field class instances.

---

## Field Type Summary

| Type | HTML Element | Repeatable | Sanitizer |
|---|---|---|---|
| `text` | `<input type="text">` | Yes | `sanitize_text_field()` |
| `textarea` | `<textarea>` | Yes | `sanitize_textarea_field()` |
| `number` | `<input type="number">` | Yes | `intval()`/`floatval()` |
| `email` | `<input type="email">` | Yes | `sanitize_email()` |
| `url` | `<input type="url">` | Yes | `esc_url_raw()` |
| `select` | `<select>` | Yes | Options whitelist |
| `radio` | `<fieldset>` + radio inputs | No | Options whitelist |
| `checkbox` | `<input type="checkbox">` | Yes | Truthy check |
| `hidden` | `<input type="hidden">` | No | `sanitize_text_field()` |
| `password` | `<input type="password">` | No | `sanitize_text_field()` |
| `date` | `<input type="date">` | Yes | ISO 8601 validation |
| `color` | `<input type="color">` | No | Hex validation |
| `wysiwyg` | TinyMCE `wp_editor()` | No | `wp_kses_post()` |
| `file` | Media library upload | No | `absint()` |
| `post` | `<select>` (posts) | No | `absint()` |
| `taxonomy` | Checkbox list / select | No | `absint()` |
| `user` | `<select>` (users) | No | `absint()` |
| `group` | Container `<div>` | Yes | Recursive per-field |
