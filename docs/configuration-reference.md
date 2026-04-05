# Configuration Reference

[Back to README](../README.md) | [Field Types](field-types.md)

## `add_custom_meta_box()` Parameters

```php
add_custom_meta_box(
    string       $id,         // Unique identifier for the meta box
    string       $title,      // Title displayed in the meta box header
    string|array $postTypes,  // Post type(s) to attach to
    array        $fields      // Array of field definition arrays
): void
```

---

## Field Configuration Keys

Every field is defined as an associative array. The following keys are available:

### Required Keys

| Key | Type | Description |
|---|---|---|
| `id` | `string` | Unique field identifier. Used as the `post_meta` key and the HTML `name` attribute. |
| `type` | `string` | Field type: `text`, `textarea`, `select`, `checkbox`, or `group`. |

### Optional Keys

| Key | Type | Default | Description |
|---|---|---|---|
| `label` | `string` | `''` | Display label shown beside the field. |
| `description` | `string` | `''` | Help text displayed below the field input. |
| `repeat` | `bool` | `false` | Makes the field repeatable — adds "Add Row" / "Remove" controls. |
| `layout` | `string` | `'horizontal'` | Layout mode: `'horizontal'` (label left, input right) or `'inline'` (label above input). |
| `width` | `string` | `''` | Responsive width class: `'w-25'`, `'w-33'`, `'w-50'`, `'w-75'`, or empty for full width. |
| `attributes` | `array` | `[]` | Key-value pairs of extra HTML attributes applied to the input element (e.g., `['placeholder' => 'Enter...', 'maxlength' => '100']`). |
| `options` | `array` | `[]` | **(Select only)** Associative array of `value => label` pairs for the dropdown options. |
| `fields` | `array` | `[]` | **(Group only)** Array of nested field definitions. |
| `collapsed` | `bool` | `false` | **(Group only)** Whether the group starts collapsed. |

---

## Layout Options

### Horizontal (Default)

Label on the left, input on the right. This is the standard WordPress meta box layout.

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

Control how much horizontal space a field occupies within a group or meta box. Fields in the same container will wrap to fill available space using flexbox.

| Class | Width |
|---|---|
| `w-25` | 25% |
| `w-33` | 33.33% |
| `w-50` | 50% |
| `w-75` | 75% |
| *(empty)* | 100% (default) |

On screens narrower than 1495px, all fields collapse to 100% width.

```php
[
    'id'    => 'first_name',
    'type'  => 'text',
    'label' => 'First Name',
    'width' => 'w-50',
],
[
    'id'    => 'last_name',
    'type'  => 'text',
    'label' => 'Last Name',
    'width' => 'w-50',
],
```

---

## Custom Attributes

Any field type that extends `AbstractField` supports the `attributes` key. These are rendered as HTML attributes on the input element.

```php
[
    'id'         => 'website',
    'type'       => 'text',
    'label'      => 'Website URL',
    'attributes' => [
        'placeholder' => 'https://example.com',
        'maxlength'   => '255',
        'pattern'     => 'https?://.+',
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
        'description' => 'The display name of the product.',
        'attributes'  => ['placeholder' => 'e.g. Widget Pro'],
    ],
    [
        'id'    => 'product_description',
        'type'  => 'textarea',
        'label' => 'Description',
        'layout'=> 'inline',
        'attributes' => ['rows' => '4'],
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
        'id'     => 'product_variants',
        'type'   => 'group',
        'label'  => 'Variant',
        'repeat' => true,
        'fields' => [
            ['id' => 'sku',   'type' => 'text', 'label' => 'SKU',   'width' => 'w-50'],
            ['id' => 'price', 'type' => 'text', 'label' => 'Price', 'width' => 'w-50'],
            ['id' => 'notes', 'type' => 'textarea', 'label' => 'Notes'],
        ],
    ],
]);
```

---

## Next Steps

- [Field Types](field-types.md) — details on each field type
- [Groups & Repeaters](groups-and-repeaters.md) — nesting and repeatable fields
- [Extending](extending.md) — build your own field types
