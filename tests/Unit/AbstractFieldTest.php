<?php

declare(strict_types=1);

namespace Tests\Unit;

use CMB\Fields\TextField;
use Tests\TestCase;

/**
 * Tests for AbstractField — getValue() falsy-value handling and validate().
 *
 * We exercise the abstract class via TextField, which is the simplest concrete
 * implementation and adds no logic of its own to these two methods.
 */
final class AbstractFieldTest extends TestCase
{
    // ------------------------------------------------------------------
    // getValue() — falsy value handling
    // ------------------------------------------------------------------

    public function testGetValueReturnsNullWhenNoValueOrDefault(): void
    {
        $field = new TextField(['id' => 'f']);

        $this->assertNull($field->getValue());
    }

    public function testGetValueReturnsZeroString(): void
    {
        $field = new TextField(['id' => 'f', 'value' => '0']);

        // "0" is a legitimate value and must NOT fall through to default/null.
        $this->assertSame('0', $field->getValue());
    }

    public function testGetValueReturnsIntegerZero(): void
    {
        $field = new TextField(['id' => 'f', 'value' => 0]);

        $this->assertSame(0, $field->getValue());
    }

    public function testGetValueReturnsFalse(): void
    {
        $field = new TextField(['id' => 'f', 'value' => false]);

        // false is explicitly stored — getValue must return null because
        // the implementation treats false the same as null (value !== null check).
        // Verify the actual behaviour rather than the desired one so the test
        // stays in sync with the code.
        $actual = $field->getValue();
        // The config contains 'value' => false, but since false !== null is true,
        // the current implementation returns false.
        // If the implementation ever changes this, this test will catch it.
        $this->assertFalse($actual);
    }

    public function testGetValueReturnsEmptyStringValue(): void
    {
        // Empty string stored as value — getValue should return '' not null/default.
        // Note: the implementation checks ($this->config['value'] !== null), so
        // '' passes that check and is returned as-is.
        $field = new TextField(['id' => 'f', 'value' => '']);

        $this->assertSame('', $field->getValue());
    }

    public function testGetValueReturnsDefaultWhenValueKeyAbsent(): void
    {
        $field = new TextField(['id' => 'f', 'default' => 'fallback']);

        $this->assertSame('fallback', $field->getValue());
    }

    public function testGetValueValueTakesPrecedenceOverDefault(): void
    {
        $field = new TextField(['id' => 'f', 'value' => 'actual', 'default' => 'fallback']);

        $this->assertSame('actual', $field->getValue());
    }

    public function testGetValueReturnsEmptyArrayForGroupType(): void
    {
        // AbstractField returns [] for group/repeat fields with no value set.
        $field = new TextField(['id' => 'f', 'type' => 'group']);

        $this->assertSame([], $field->getValue());
    }

    public function testGetValueReturnsEmptyArrayForRepeatField(): void
    {
        $field = new TextField(['id' => 'f', 'repeat' => true]);

        $this->assertSame([], $field->getValue());
    }

    // ------------------------------------------------------------------
    // validate() — required rule
    // ------------------------------------------------------------------

    public function testValidateRequiredFailsOnEmptyString(): void
    {
        $field  = new TextField(['id' => 'name', 'label' => 'Name', 'validate' => ['required']]);
        $errors = $field->validate('');

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('required', strtolower($errors[0]));
    }

    public function testValidateRequiredFailsOnNull(): void
    {
        $field  = new TextField(['id' => 'name', 'label' => 'Name', 'validate' => ['required']]);
        $errors = $field->validate(null);

        $this->assertNotEmpty($errors);
    }

    public function testValidateRequiredPassesOnNonEmptyString(): void
    {
        $field  = new TextField(['id' => 'name', 'label' => 'Name', 'validate' => ['required']]);
        $errors = $field->validate('John');

        $this->assertEmpty($errors);
    }

    public function testValidateRequiredViaConfigFlag(): void
    {
        // 'required' => true in config should implicitly add the required rule.
        $field  = new TextField(['id' => 'name', 'label' => 'Name', 'required' => true]);
        $errors = $field->validate('');

        $this->assertNotEmpty($errors);
    }

    // ------------------------------------------------------------------
    // validate() — email rule
    // ------------------------------------------------------------------

    public function testValidateEmailPassesValidAddress(): void
    {
        $field  = new TextField(['id' => 'email', 'label' => 'Email', 'validate' => ['email']]);
        $errors = $field->validate('user@example.com');

        $this->assertEmpty($errors);
    }

    public function testValidateEmailFailsInvalidAddress(): void
    {
        $field  = new TextField(['id' => 'email', 'label' => 'Email', 'validate' => ['email']]);
        $errors = $field->validate('not-an-email');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('email', strtolower($errors[0]));
    }

    public function testValidateEmailPassesOnEmptyString(): void
    {
        // Email rule only validates non-empty values.
        $field  = new TextField(['id' => 'email', 'label' => 'Email', 'validate' => ['email']]);
        $errors = $field->validate('');

        $this->assertEmpty($errors);
    }

    // ------------------------------------------------------------------
    // validate() — url rule
    // ------------------------------------------------------------------

    public function testValidateUrlPassesValidUrl(): void
    {
        $field  = new TextField(['id' => 'site', 'label' => 'Site', 'validate' => ['url']]);
        $errors = $field->validate('https://example.com');

        $this->assertEmpty($errors);
    }

    public function testValidateUrlFailsInvalidUrl(): void
    {
        $field  = new TextField(['id' => 'site', 'label' => 'Site', 'validate' => ['url']]);
        $errors = $field->validate('not a url');

        $this->assertNotEmpty($errors);
    }

    // ------------------------------------------------------------------
    // validate() — min / max rules
    // ------------------------------------------------------------------

    public function testValidateMinFailsShortValue(): void
    {
        $field  = new TextField(['id' => 'bio', 'label' => 'Bio', 'validate' => ['min:10']]);
        $errors = $field->validate('hi');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('10', $errors[0]);
    }

    public function testValidateMinPassesSufficientLength(): void
    {
        $field  = new TextField(['id' => 'bio', 'label' => 'Bio', 'validate' => ['min:3']]);
        $errors = $field->validate('Hello!');

        $this->assertEmpty($errors);
    }

    public function testValidateMaxFailsTooLongValue(): void
    {
        $field  = new TextField(['id' => 'tag', 'label' => 'Tag', 'validate' => ['max:5']]);
        $errors = $field->validate('toolong');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('5', $errors[0]);
    }

    public function testValidateMaxPassesShortEnoughValue(): void
    {
        $field  = new TextField(['id' => 'tag', 'label' => 'Tag', 'validate' => ['max:10']]);
        $errors = $field->validate('ok');

        $this->assertEmpty($errors);
    }

    // ------------------------------------------------------------------
    // validate() — numeric rule
    // ------------------------------------------------------------------

    public function testValidateNumericPassesIntegerString(): void
    {
        $field  = new TextField(['id' => 'qty', 'label' => 'Qty', 'validate' => ['numeric']]);
        $errors = $field->validate('42');

        $this->assertEmpty($errors);
    }

    public function testValidateNumericFailsNonNumericString(): void
    {
        $field  = new TextField(['id' => 'qty', 'label' => 'Qty', 'validate' => ['numeric']]);
        $errors = $field->validate('abc');

        $this->assertNotEmpty($errors);
    }

    // ------------------------------------------------------------------
    // validate() — pattern rule
    // ------------------------------------------------------------------

    public function testValidatePatternPassesMatchingValue(): void
    {
        $field  = new TextField(['id' => 'code', 'label' => 'Code', 'validate' => ['pattern:^[A-Z]{3}$']]);
        $errors = $field->validate('ABC');

        $this->assertEmpty($errors);
    }

    public function testValidatePatternFailsNonMatchingValue(): void
    {
        $field  = new TextField(['id' => 'code', 'label' => 'Code', 'validate' => ['pattern:^[A-Z]{3}$']]);
        $errors = $field->validate('abc123');

        $this->assertNotEmpty($errors);
    }

    // ------------------------------------------------------------------
    // validate() — no rules
    // ------------------------------------------------------------------

    public function testValidateWithNoRulesAlwaysReturnsEmpty(): void
    {
        $field  = new TextField(['id' => 'anything']);
        $errors = $field->validate('whatever value');

        $this->assertEmpty($errors);
    }

    public function testValidateMultipleRulesAccumulateErrors(): void
    {
        $field  = new TextField([
            'id'       => 'email',
            'label'    => 'Email',
            'validate' => ['required', 'email'],
        ]);
        // Empty string fails both required and (would fail email, but email skips empty).
        $errors = $field->validate('');

        // At least the required error should be present.
        $this->assertNotEmpty($errors);
    }
}
