# Field Types

[Back to README](../README.md) | [Configuration Reference](configuration-reference.md)

The plugin ships with five built-in field types. Each is a class in the `CMB\Fields` namespace that extends `AbstractField`.

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
    'attributes' => ['placeholder' => 'e.g. My Post Title'],
]
```

**Repeatable text field:**

```php
[
    'id'     => 'my_tags',
    'type'   => 'text',
    'label'  => 'Tags',
    'repeat' => true,
]
```

When `repeat` is `true`, the field renders one input per stored value and an "Add Row" button appears to add more.

**Sanitization:** Uses `sanitize_text_field()` — strips tags, removes extra whitespace, and encodes special characters.

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

**Sanitization:** Uses `sanitize_textarea_field()` — preserves newlines while stripping dangerous content.

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
        'option_c' => 'Option C',
    ],
]
```

**Sanitization:** Validates the submitted value exists as a key in the `options` array. Returns an empty string if the value is not a valid option.

---

## Checkbox

Renders an `<input type="checkbox">` with its label.

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

**Sanitization:** Accepts `1`, `'1'`, `true`, or `'true'` as truthy. Everything else resolves to `'0'`.

---

## Group

Renders a collapsible container of nested sub-fields. Groups can be repeatable and can contain other groups for unlimited nesting depth.

**Type key:** `group`
**Class:** `CMB\Fields\GroupField`

```php
[
    'id'     => 'my_group',
    'type'   => 'group',
    'label'  => 'Author Info',
    'repeat' => true,
    'fields' => [
        [
            'id'    => 'name',
            'type'  => 'text',
            'label' => 'Author Name',
        ],
        [
            'id'    => 'bio',
            'type'  => 'textarea',
            'label' => 'Author Bio',
        ],
    ],
]
```

See [Groups & Repeaters](groups-and-repeaters.md) for full details on nesting, data storage, and retrieval.

**Sanitization:** Recursively applies `sanitize_text_field()` to all values via `map_deep()`.

---

## Field Type Summary

| Type | HTML Element | Repeatable | Nestable | Sanitizer |
|---|---|---|---|---|
| `text` | `<input type="text">` | Yes | — | `sanitize_text_field()` |
| `textarea` | `<textarea>` | — | — | `sanitize_textarea_field()` |
| `select` | `<select>` | — | — | Options whitelist |
| `checkbox` | `<input type="checkbox">` | — | — | Truthy check |
| `group` | Container `<div>` | Yes | Yes | `map_deep()` + `sanitize_text_field()` |
