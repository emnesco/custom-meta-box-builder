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
| `add_action()`, `add_meta_box()` | No-op |
| `update_post_meta()`, `delete_post_meta()`, `add_post_meta()` | No-op |
| `get_post_meta()` | Returns empty string |
| `get_term_meta()`, `update_term_meta()` | Returns empty/true |
| `get_user_meta()`, `update_user_meta()` | Returns empty/true |
| `get_option()`, `update_option()` | Returns default/true |
| `register_setting()`, `settings_fields()`, `do_settings_sections()` | No-op |
| `checked()`, `selected()` | Returns checked/selected attribute |
| `esc_attr()`, `esc_html()`, `esc_textarea()` | Uses `htmlspecialchars()` |
| `wp_nonce_field()`, `wp_verify_nonce()` | No-op / returns true |
| `current_user_can()` | Returns true |
| `sanitize_text_field()`, `sanitize_textarea_field()` | `strip_tags()` |
| `sanitize_email()` | `FILTER_SANITIZE_EMAIL` |
| `esc_url_raw()` | `FILTER_SANITIZE_URL` |
| `absint()` | `abs((int)$val)` |
| `wp_kses_post()` | Pass-through |
| `map_deep()` | Recursive callback application |
| `_doing_it_wrong()` | No-op |
| `do_action()`, `apply_filters()` | No-op / pass-through |
| `get_posts()`, `get_users()`, `get_terms()` | Returns empty array |
| `wp_enqueue_style()`, `wp_enqueue_script()`, `wp_enqueue_media()` | No-op |
| `plugin_dir_url()` | Returns static path |
| `register_post_meta()` | No-op |
| `get_post()`, `get_the_ID()` | Returns null/0 |
| `is_serialized()`, `maybe_unserialize()` | Returns false/pass-through |
| `wp_is_post_revision()` | Returns false |
| `get_locale()` | Returns `'en_US'` |
| `admin_url()` | Returns mock URL |

## Existing Tests

### PluginTest

Tests that the `Plugin` class can be instantiated and `boot()` runs without errors.

### MetaBoxManagerTest

Tests that `MetaBoxManager::add()` correctly stores meta box definitions with the expected structure (title, post types, fields). Uses reflection to reset the singleton instance between tests.

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
        $field = new NumberField(['id' => 'test', 'name' => 'test']);
        $this->assertSame(42, $field->sanitize('42'));
        $this->assertSame(0, $field->sanitize('not-a-number'));
    }

    public function testValidateRequired(): void {
        $field = new NumberField([
            'id'       => 'test',
            'name'     => 'test',
            'required' => true,
            'validate' => ['required'],
        ]);

        $errors = $field->validate('');
        $this->assertNotEmpty($errors);

        $errors = $field->validate('42');
        $this->assertEmpty($errors);
    }
}
```

## Tips

- Always provide both `id` and `name` in the config array when testing render output — in production these are set by `FieldRenderer`, but in unit tests you provide them directly.
- Test both valid and invalid/malicious inputs in `sanitize()` tests.
- Use `assertStringContainsString()` for HTML output checks rather than exact string matching, since attribute order may vary.
- Test validation rules by passing `'validate' => ['required', 'min:3']` in the config.
- The MetaBoxManager uses a singleton — reset it between tests using reflection on the `$instance` property.

---

## Next Steps

- [Extending](extending.md) — create custom fields to test
- [Architecture](architecture.md) — understand how the pieces fit together
