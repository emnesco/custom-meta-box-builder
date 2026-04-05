# Configuration Reference

[Back to README](../README.md) | [Field Types](field-types.md)

## `add_custom_meta_box()` Parameters

```php
add_custom_meta_box(
    string       $id,                      // Unique identifier for the meta box
    string       $title,                   // Title displayed in the meta box header
    string|array $postTypes,               // Post type(s) to attach to
    array        $fields,                  // Array of field definition arrays
    string       $context = 'advanced',    // 'normal', 'side', or 'advanced'
    string       $priority = 'default'     // 'high', 'default', or 'low'
): void
```

---

## Field Configuration Keys

Every field is defined as an associative array. The following keys are available:

### Required Keys

| Key | Type | Description |
|---|---|---|
| `id` | `string` | Unique field identifier. Used as the `post_meta` key and the HTML `name` attribute. |
| `type` | `string` | Field type: `text`, `textarea`, `number`, `email`, `url`, `select`, `radio`, `checkbox`, `hidden`, `password`, `date`, `color`, `wysiwyg`, `file`, `post`, `taxonomy`, `user`, or `group`. |

### Optional Keys (All Field Types)

| Key | Type | Default | Description |
|---|---|---|---|
| `label` | `string` | `''` | Display label shown beside the field. |
| `description` | `string` | `''` | Help text displayed below the field input. |
| `default` | `mixed` | `''` | Default value when no meta exists. |
| `required` | `bool` | `false` | Marks field as required. Adds HTML `required` attribute and red asterisk. Server-side validation. |
| `repeat` | `bool` | `false` | Makes the field repeatable — adds "Add Row" / "Remove" controls. |
| `layout` | `string` | `'horizontal'` | Layout mode: `'horizontal'` (label left, input right) or `'inline'` (label above input). |
| `width` | `string` | `''` | Responsive width class: `'w-25'`, `'w-33'`, `'w-50'`, `'w-75'`, or empty for full width. |
| `attributes` | `array` | `[]` | Key-value pairs of extra HTML attributes applied to the input element. |
| `sanitize_callback` | `callable` | `null` | Custom sanitization function. Overrides the default field sanitizer. |
| `validate` | `array` | `[]` | Validation rules: `'required'`, `'email'`, `'url'`, `'min:N'`, `'max:N'`, `'numeric'`, `'pattern:REGEX'`. |
| `show_in_rest` | `bool` | `false` | Expose field via WordPress REST API. |
| `multilingual` | `bool` | `false` | Enable per-locale values with language tabs. |
| `locales` | `array` | `['en']` | Locales for multilingual fields (e.g., `['en', 'fr', 'es']`). |

### Conditional Display

| Key | Type | Description |
|---|---|---|
| `conditional` | `array` | Show/hide this field based on another field's value. |

```php
'conditional' => [
    'field'    => 'payment_method',   // Field ID to watch
    'operator' => '==',               // ==, !=, contains, empty, !empty
    'value'    => 'card',             // Value to compare against
]
```

### Repeater Options

| Key | Type | Default | Description |
|---|---|---|---|
| `min_rows` | `int` | `0` | Minimum number of repeater rows (prevents deletion below this). |
| `max_rows` | `int` | `null` | Maximum number of repeater rows (disables Add Row at limit). |

### Select / Radio Options

| Key | Type | Description |
|---|---|---|
| `options` | `array` | Associative array of `value => label` pairs. |

### Group Options

| Key | Type | Default | Description |
|---|---|---|---|
| `fields` | `array` | `[]` | Array of nested field definitions. |
| `collapsed` | `bool` | `true` | Whether the group starts collapsed. Set to `false` to start expanded. |
| `row_title_field` | `string` | `''` | Sub-field ID whose value is displayed as the row title. |
| `searchable` | `bool` | `false` | Adds a search/filter input above group items. |

### Post Field Options

| Key | Type | Default | Description |
|---|---|---|---|
| `post_type` | `string` | `'post'` | Post type for the query. |
| `query` | `array` | `[]` | Additional `get_posts()` arguments. |

### Taxonomy Field Options

| Key | Type | Default | Description |
|---|---|---|---|
| `taxonomy` | `string` | `'category'` | Taxonomy slug. |
| `display` | `string` | `'checkbox'` | Display mode: `'checkbox'` or `'select'`. |

### User Field Options

| Key | Type | Default | Description |
|---|---|---|---|
| `role` | `string` | `''` | Filter users by role. |

### Date Field Options

| Key | Type | Default | Description |
|---|---|---|---|
| `format` | `string` | `'date'` | `'date'` or `'datetime-local'`. |

### Meta Box-Level Options

| Key | Type | Description |
|---|---|---|
| `gutenberg_panel` | `bool` | When `true`, also registers fields as a Gutenberg sidebar panel. |

---

## Tab Support

Organize fields into tabs by using a `'tabs'` key instead of a flat field array:

```php
add_custom_meta_box('cmb-product', 'Product', 'product', [
    'tabs' => [
        'basic' => [
            'label'  => 'Basic Info',
            'fields' => [
                ['id' => 'name',  'type' => 'text',     'label' => 'Name'],
                ['id' => 'price', 'type' => 'number',   'label' => 'Price'],
            ],
        ],
        'details' => [
            'label'  => 'Details',
            'fields' => [
                ['id' => 'description', 'type' => 'textarea', 'label' => 'Description'],
                ['id' => 'sku',         'type' => 'text',     'label' => 'SKU'],
            ],
        ],
    ],
]);
```

---

## Layout Options

### Horizontal (Default)

Label on the left, input on the right.

```php
['layout' => 'horizontal']  // or omit — this is the default
```

### Inline

Label above the input. Useful for wider fields or when space is limited.

```php
['layout' => 'inline']
```

---

## Width Classes

| Class | Width |
|---|---|
| `w-25` | 25% |
| `w-33` | 33.33% |
| `w-50` | 50% |
| `w-75` | 75% |
| *(empty)* | 100% (default) |

On screens narrower than 1495px, all fields collapse to 100% width.

---

## Custom Attributes

Any field type that extends `AbstractField` supports the `attributes` key:

```php
[
    'id'         => 'website',
    'type'       => 'url',
    'label'      => 'Website URL',
    'attributes' => [
        'placeholder' => 'https://example.com',
        'maxlength'   => '255',
    ],
]
```

All attribute values are escaped with `esc_attr()`.

---

## Complete Example

```php
add_custom_meta_box('cmb-product', 'Product Details', 'product', [
    [
        'id'          => 'product_name',
        'type'        => 'text',
        'label'       => 'Product Name',
        'required'    => true,
        'description' => 'The display name of the product.',
    ],
    [
        'id'    => 'product_description',
        'type'  => 'wysiwyg',
        'label' => 'Description',
        'layout'=> 'inline',
    ],
    [
        'id'      => 'product_status',
        'type'    => 'select',
        'label'   => 'Status',
        'width'   => 'w-50',
        'options' => [
            'draft'     => 'Draft',
            'published' => 'Published',
            'archived'  => 'Archived',
        ],
    ],
    [
        'id'    => 'product_featured',
        'type'  => 'checkbox',
        'label' => 'Featured Product',
        'width' => 'w-50',
    ],
    [
        'id'    => 'product_price',
        'type'  => 'number',
        'label' => 'Price',
        'width' => 'w-50',
        'attributes' => ['min' => '0', 'step' => '0.01'],
    ],
    [
        'id'    => 'product_color',
        'type'  => 'color',
        'label' => 'Theme Color',
        'width' => 'w-50',
    ],
    [
        'id'    => 'product_image',
        'type'  => 'file',
        'label' => 'Product Image',
    ],
    [
        'id'              => 'product_variants',
        'type'            => 'group',
        'label'           => 'Variant',
        'repeat'          => true,
        'row_title_field' => 'sku',
        'max_rows'        => 50,
        'fields'          => [
            ['id' => 'sku',   'type' => 'text',   'label' => 'SKU',   'width' => 'w-33'],
            ['id' => 'price', 'type' => 'number', 'label' => 'Price', 'width' => 'w-33'],
            ['id' => 'stock', 'type' => 'number', 'label' => 'Stock', 'width' => 'w-33'],
            ['id' => 'notes', 'type' => 'textarea', 'label' => 'Notes'],
        ],
    ],
], 'normal', 'high');
```

---

## Next Steps

- [Field Types](field-types.md) — details on each field type
- [Groups & Repeaters](groups-and-repeaters.md) — nesting and repeatable fields
- [Hooks Reference](hooks.md) — all actions and filters
- [Advanced Features](advanced-features.md) — tabs, conditional logic, import/export
- [Extending](extending.md) — build your own field types
