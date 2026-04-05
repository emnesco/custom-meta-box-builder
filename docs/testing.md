# Testing

[Back to README](../README.md)

The plugin includes a PHPUnit test suite with WordPress function mocks for isolated unit testing.

## Setup

Install dev dependencies:

```bash
composer install
```

## Running Tests

```bash
./vendor/bin/phpunit
```

Or with verbose output:

```bash
./vendor/bin/phpunit --verbose
```

## Configuration

Tests are configured via [phpunit.xml.dist](../phpunit.xml.dist):

- **Bootstrap:** `tests/bootstrap.php` loads Composer autoload and defines mock WordPress functions
- **Test directory:** `tests/`
- **Colors and verbose mode** enabled by default

## Test Bootstrap

The bootstrap file at `tests/bootstrap.php` mocks core WordPress functions so tests can run without a WordPress installation:

| Mocked Function | Behavior |
|---|---|
| `add_action()` | No-op |
| `add_meta_box()` | No-op |
| `update_post_meta()` | No-op |
| `get_post_meta()` | Returns empty string |
| `checked()` | Returns `checked="checked"` or empty |
| `selected()` | Returns `selected="selected"` or empty |
| `esc_attr()` | Uses `htmlspecialchars()` |
| `esc_html()` | Uses `htmlspecialchars()` |
| `esc_textarea()` | Uses `htmlspecialchars()` |
| `wp_nonce_field()` | No-op |
| `wp_verify_nonce()` | Returns `true` |

## Existing Tests

### PluginTest

Tests that the `Plugin` class can be instantiated and `boot()` runs without errors.

### MetaBoxManagerTest

Tests that `MetaBoxManager::add()` correctly stores meta box definitions with the expected structure (title, post types, fields).

### TextFieldTest

- **Render test** — verifies the HTML output contains an `<input>` with the correct `name` attribute
- **Sanitize test** — verifies that `sanitize()` strips HTML tags (e.g., `<script>alert(1)</script>` becomes `alert(1)`)

## Writing Tests for Custom Fields

Follow the same pattern as `TextFieldTest`:

```php
<?php
use PHPUnit\Framework\TestCase;
use CMB\Fields\NumberField;

final class NumberFieldTest extends TestCase {

    public function testRenderOutputsNumberInput(): void {
        $field = new NumberField([
            'id'    => 'my_number',
            'name'  => 'my_number',
            'label' => 'Count',
            'value' => '42',
        ]);

        $html = $field->render();
        $this->assertStringContainsString('type="number"', $html);
        $this->assertStringContainsString('name="my_number"', $html);
        $this->assertStringContainsString('value="42"', $html);
    }

    public function testSanitizeReturnsInteger(): void {
        $field = new NumberField(['id' => 'test']);
        $this->assertSame(42, $field->sanitize('42'));
        $this->assertSame(0, $field->sanitize('not-a-number'));
    }
}
```

## Tips

- Always provide both `id` and `name` in the config array when testing render output — in production these are set by `FieldRenderer`, but in unit tests you provide them directly.
- Test both valid and invalid/malicious inputs in `sanitize()` tests.
- Use `assertStringContainsString()` for HTML output checks rather than exact string matching, since attribute order may vary.

---

## Next Steps

- [Extending](extending.md) — create custom fields to test
- [Architecture](architecture.md) — understand how the pieces fit together
