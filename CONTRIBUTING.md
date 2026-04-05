# Contributing to Custom Meta Box Builder

Thank you for your interest in contributing! This guide covers development setup, coding standards, and the pull request process.

## Development Setup

1. Clone the repository and install dependencies:

```bash
composer install
npm install
```

2. Run tests:

```bash
composer test
# Or directly:
vendor/bin/phpunit --testsuite Unit
```

3. Build minified assets:

```bash
npm run build
```

## Coding Standards

- **PHP:** Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).
- **PHP Version:** Minimum PHP 8.1. Use typed properties, union types, named arguments where appropriate.
- **JavaScript:** ES6+ syntax (`const`, `let`, arrow functions). Wrapped in an IIFE with `'use strict'`.
- **CSS:** Use CSS custom properties (`--cmb-*` variables) for themeable values.

## Adding a New Field Type

1. Create `src/Fields/YourField.php` extending `AbstractField`:

```php
<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;

class YourField extends AbstractField {
    public function render(): string {
        $value = $this->getValue();
        $htmlId = $this->config['html_id'] ?? '';
        $id_attr = $htmlId ? ' id="' . esc_attr($htmlId) . '"' : '';
        return '<input type="text" name="' . esc_attr($this->getName()) . '"'
             . $id_attr . ' value="' . esc_attr($value) . '"'
             . $this->renderAttributes() . $this->requiredAttr() . '>';
    }

    public function sanitize(mixed $value): mixed {
        return sanitize_text_field($value);
    }
}
```

2. Register in `FieldFactory::$defaultTypes` array.

3. Add unit tests in `tests/Unit/Fields/YourFieldTest.php`.

4. Add any required CSS to `assets/cmb-style.css`.

## Writing Tests

- Extend `CMB\Tests\TestCase` which sets up Brain\Monkey for WordPress function mocking.
- Mock WordPress functions with `Functions\expect()` or `Functions\when()`.
- Test both `render()` and `sanitize()` methods.
- Place tests under `tests/Unit/` mirroring the `src/` directory structure.

## Hook Naming

All hooks use dual-prefix firing via `FieldUtils::doAction()` / `FieldUtils::applyFilters()`:
- New prefix: `cmbbuilder_` (primary)
- Legacy prefix: `cmb_` (backward compatibility)

When adding new hooks, use the `FieldUtils` helpers to fire both prefixes.

## Pull Request Checklist

- [ ] Code follows WordPress PHP Coding Standards
- [ ] All existing tests pass (`composer test`)
- [ ] New functionality includes unit tests
- [ ] File-level `@package` docblock present
- [ ] Hooks have PHPDoc annotations with `@since` tag
- [ ] No `var_dump`, `error_log`, or debug statements
- [ ] Assets rebuilt if JS/CSS changed (`npm run build`)

## Reporting Bugs

Open an issue with:
- WordPress version
- PHP version
- Steps to reproduce
- Expected vs actual behavior
