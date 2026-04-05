# Extending — Custom Field Types

[Back to README](../README.md) | [Architecture](architecture.md) | [Field Types](field-types.md)

The plugin is designed to be extended. There are two ways to add custom field types.

---

## Method 1: PSR-4 Auto-Discovery

Create a new file in `src/Fields/`. The class name must follow the pattern `{Type}Field` where `{Type}` is the ucfirst version of the type key you will use in config arrays.

**Example:** A `rating` field type → class `RatingField` in `src/Fields/RatingField.php`.

```php
<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class RatingField extends AbstractField {

    public function render(): string {
        $value = $this->getValue() ?? 0;
        $name = esc_attr($this->getName());
        $htmlId = esc_attr($this->config['html_id'] ?? $this->getName());
        $required = $this->requiredAttr();

        $output = '<select id="' . $htmlId . '" name="' . $name . '"' . $required . '>';
        for ($i = 1; $i <= 5; $i++) {
            $sel = ((int)$value === $i) ? ' selected' : '';
            $output .= '<option value="' . $i . '"' . $sel . '>' . $i . ' Star' . ($i > 1 ? 's' : '') . '</option>';
        }
        $output .= '</select>';

        return $output;
    }

    public function sanitize(mixed $value): mixed {
        $val = (int) $value;
        return ($val >= 1 && $val <= 5) ? $val : 1;
    }
}
```

That's it. The PSR-4 autoloader picks it up automatically. Use it:

```php
['id' => 'rating', 'type' => 'rating', 'label' => 'Rating']
```

---

## Method 2: Register from Any Namespace

For field types in a different namespace (e.g., your theme or another plugin), use the registration API:

```php
use CMB\Core\MetaBoxManager;

MetaBoxManager::registerFieldType('my_custom', \MyTheme\Fields\CustomField::class);
```

The class must implement `CMB\Core\Contracts\FieldInterface` (easiest by extending `AbstractField`). Once registered, use `'type' => 'my_custom'` in field configs. Registered types take priority over auto-discovered ones.

---

## What You Get from AbstractField

By extending `AbstractField`, your class inherits:

| Method | What It Does |
|---|---|
| `getName()` | Returns the resolved `name` attribute |
| `getId()` | Returns the field `id` |
| `getLabel()` | Returns the display `label` |
| `getValue()` | Returns the current value, with `default` key fallback |
| `renderAttributes()` | Renders the `attributes` config key as an HTML string |
| `validate(mixed $value): array` | Validates against configured rules (required, email, url, min, max, numeric, pattern) |
| `isRequired(): bool` | Whether the field has `'required' => true` |
| `requiredAttr(): string` | Returns ` required` HTML attribute string if required |

Your class needs to implement:

| Method | What It Must Do |
|---|---|
| `render(): string` | Return the complete HTML for the input element |
| `sanitize(mixed $value): mixed` | Clean the raw `$_POST` value and return the safe version |

The `validate()` method is inherited and works automatically based on config rules. Override it only if you need custom validation logic.

---

## Supporting Repeatable Mode

If your field should support `'repeat' => true`, handle the array value in `render()`:

```php
public function render(): string {
    $value = $this->getValue();
    $attrs = $this->renderAttributes();
    $name = esc_attr($this->getName());
    $output = '';

    if (isset($this->config['repeat']) && $this->config['repeat'] === true) {
        foreach ((array) $value as $v) {
            $output .= '<input type="number" name="' . $name . '" value="' . esc_attr($v ?? '') . '"' . $attrs . '>';
        }
    } else {
        $output .= '<input type="number" name="' . $name . '" value="' . esc_attr($value ?? '') . '"' . $attrs . '>';
    }

    return $output;
}
```

And handle arrays in `sanitize()`:

```php
public function sanitize(mixed $value): mixed {
    if (is_array($value)) {
        return array_map(fn($v) => $this->sanitizeSingle($v), $value);
    }
    return $this->sanitizeSingle($value);
}
```

---

## Using the ArrayAccessibleTrait

For convenience, use the `ArrayAccessibleTrait` to access config values as properties:

```php
use CMB\Core\Traits\ArrayAccessibleTrait;

class RatingField extends AbstractField {
    use ArrayAccessibleTrait;

    public function render(): string {
        $min = $this->min ?? 1;
        $max = $this->max ?? 5;
        // ...
    }
}
```

---

## Adding REST API Support

Your custom field works with REST API automatically. Add `'show_in_rest' => true` to the field config:

```php
[
    'id'           => 'rating',
    'type'         => 'rating',
    'label'        => 'Rating',
    'show_in_rest' => true,
]
```

---

## Adding Gutenberg Support

If the meta box has `'gutenberg_panel' => true`, simple field types (text, number, select, checkbox, textarea) are automatically rendered in the Gutenberg sidebar. Custom types fall back to a text input in the sidebar.

---

## Next Steps

- [Architecture](architecture.md) — understand the full class hierarchy
- [Hooks Reference](hooks.md) — modify behavior without extending classes
- [Testing](testing.md) — write tests for your custom field
