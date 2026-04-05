# Extending — Custom Field Types

[Back to README](../README.md) | [Architecture](architecture.md) | [Field Types](field-types.md)

The plugin is designed to be extended. Creating a new field type requires a single PHP class.

## Step 1: Create Your Field Class

Create a new file in `src/Fields/`. The class name must follow the pattern `{Type}Field` where `{Type}` is the ucfirst version of the type key you will use in config arrays.

**Example:** A `number` field type → class `NumberField` in `src/Fields/NumberField.php`.

```php
<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class NumberField extends AbstractField {

    public function render(): string {
        $value = $this->getValue();
        $attrs = $this->renderAttributes();

        return '<input type="number" name="' . esc_attr($this->getName()) . '" value="' . esc_attr($value ?? '') . '"' . $attrs . '>';
    }

    public function sanitize($value) {
        return is_numeric($value) ? (int) $value : 0;
    }
}
```

That's it. The PSR-4 autoloader picks it up automatically.

## Step 2: Use It

```php
add_custom_meta_box('cmb-stats', 'Statistics', 'post', [
    [
        'id'         => 'view_count',
        'type'       => 'number',
        'label'      => 'View Count',
        'attributes' => ['min' => '0', 'step' => '1'],
    ],
]);
```

## How It Works

The `FieldRenderer` and `MetaBoxManager` resolve field types dynamically:

```php
$fieldClass = 'CMB\\Fields\\' . ucfirst($field['type']) . 'Field';
```

Any class matching this pattern in the `CMB\Fields` namespace will be instantiated automatically. No registration step is needed.

## What You Get from AbstractField

By extending `AbstractField`, your class inherits:

| Method | What It Does |
|---|---|
| `getName()` | Returns the resolved `name` attribute |
| `getId()` | Returns the field `id` |
| `getLabel()` | Returns the display `label` |
| `getValue()` | Returns the current value (from config/post_meta), or defaults to `null`/`[]` |
| `renderAttributes()` | Renders the `attributes` config key as an HTML string |

Your class only needs to implement:

| Method | What It Must Do |
|---|---|
| `render(): string` | Return the complete HTML for the input element |
| `sanitize($value)` | Clean the raw `$_POST` value and return the safe version |

## Supporting Repeatable Mode

If your field should support `'repeat' => true`, handle the array value in `render()`:

```php
public function render(): string {
    $value = $this->getValue();
    $attrs = $this->renderAttributes();
    $output = '';

    if (isset($this->config['repeat']) && $this->config['repeat'] === true) {
        foreach ((array) $value as $v) {
            $output .= '<input type="number" name="' . esc_attr($this->getName()) . '" value="' . esc_attr($v ?? '') . '"' . $attrs . '>';
        }
    } else {
        $output .= '<input type="number" name="' . esc_attr($this->getName()) . '" value="' . esc_attr($value ?? '') . '"' . $attrs . '>';
    }

    return $output;
}
```

## Using the ArrayAccessibleTrait

For convenience, you can use the `ArrayAccessibleTrait` in your field class to access config values as properties:

```php
use CMB\Core\Traits\ArrayAccessibleTrait;

class NumberField extends AbstractField {
    use ArrayAccessibleTrait;

    public function render(): string {
        // Access config via magic properties
        $min = $this->min ?? 0;
        $max = $this->max ?? 999;
        // ...
    }
}
```

## Example: Color Picker Field

```php
<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class ColorField extends AbstractField {

    public function render(): string {
        $value = $this->getValue() ?? '#000000';

        return '<input type="color" name="' . esc_attr($this->getName()) . '" value="' . esc_attr($value) . '"' . $this->renderAttributes() . '>';
    }

    public function sanitize($value) {
        // Validate hex color
        return preg_match('/^#[a-fA-F0-9]{6}$/', $value) ? $value : '#000000';
    }
}
```

Usage:

```php
[
    'id'    => 'brand_color',
    'type'  => 'color',
    'label' => 'Brand Color',
]
```

---

## Next Steps

- [Architecture](architecture.md) — understand the full class hierarchy
- [Testing](testing.md) — write tests for your custom field
