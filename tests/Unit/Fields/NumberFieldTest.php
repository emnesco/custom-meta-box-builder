<?php

declare(strict_types=1);

namespace Tests\Unit\Fields;

use CMB\Fields\NumberField;
use Tests\TestCase;

/**
 * Tests for CMB\Fields\NumberField.
 */
final class NumberFieldTest extends TestCase
{
    // ------------------------------------------------------------------
    // render()
    // ------------------------------------------------------------------

    public function testRenderOutputsNumberInput(): void
    {
        $field = new NumberField(['id' => 'qty', 'name' => 'qty']);

        $html = $field->render();

        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="number"', $html);
    }

    public function testRenderIncludesNameAttribute(): void
    {
        $field = new NumberField(['id' => 'price', 'name' => 'price']);

        $html = $field->render();

        $this->assertStringContainsString('name="price"', $html);
    }

    public function testRenderIncludesValueAttribute(): void
    {
        $field = new NumberField(['id' => 'qty', 'name' => 'qty', 'value' => 42]);

        $html = $field->render();

        $this->assertStringContainsString('value="42"', $html);
    }

    public function testRenderIncludesIdAttributeWhenHtmlIdSet(): void
    {
        $field = new NumberField(['id' => 'qty', 'name' => 'qty', 'html_id' => 'qty_input']);

        $html = $field->render();

        $this->assertStringContainsString('id="qty_input"', $html);
    }

    public function testRenderDoesNotIncludeIdWhenHtmlIdAbsent(): void
    {
        $field = new NumberField(['id' => 'qty', 'name' => 'qty']);

        $html = $field->render();

        $this->assertStringNotContainsString(' id=', $html);
    }

    public function testRenderIncludesRequiredAttribute(): void
    {
        $field = new NumberField(['id' => 'qty', 'name' => 'qty', 'required' => true]);

        $html = $field->render();

        $this->assertStringContainsString('required', $html);
    }

    public function testRenderIncludesStepAttributeFromAttributes(): void
    {
        $field = new NumberField([
            'id'         => 'price',
            'name'       => 'price',
            'attributes' => ['step' => '0.01'],
        ]);

        $html = $field->render();

        $this->assertStringContainsString('step="0.01"', $html);
    }

    // ------------------------------------------------------------------
    // sanitize()
    // ------------------------------------------------------------------

    public function testSanitizeCastsStringToInt(): void
    {
        $field = new NumberField(['id' => 'qty']);

        $result = $field->sanitize('7');

        $this->assertSame(7, $result);
    }

    public function testSanitizeReturnsEmptyStringForEmptyInput(): void
    {
        $field = new NumberField(['id' => 'qty']);

        $result = $field->sanitize('');

        $this->assertSame('', $result);
    }

    public function testSanitizeReturnsEmptyStringForNullInput(): void
    {
        $field = new NumberField(['id' => 'qty']);

        $result = $field->sanitize(null);

        $this->assertSame('', $result);
    }

    public function testSanitizeReturnsFloatWhenStepIsDecimal(): void
    {
        $field = new NumberField([
            'id'         => 'price',
            'attributes' => ['step' => '0.01'],
        ]);

        $result = $field->sanitize('3.14');

        $this->assertIsFloat($result);
        $this->assertEqualsWithDelta(3.14, $result, 0.0001);
    }

    public function testSanitizeHandlesArrayOfValues(): void
    {
        $field = new NumberField(['id' => 'qty']);

        $result = $field->sanitize(['3', '7', '']);

        $this->assertIsArray($result);
        $this->assertSame(3,   $result[0]);
        $this->assertSame(7,   $result[1]);
        $this->assertSame('', $result[2]);
    }

    public function testSanitizeTruncatesFloatToIntWhenNoDecimalStep(): void
    {
        $field = new NumberField(['id' => 'qty']);

        $result = $field->sanitize('9.99');

        $this->assertSame(9, $result);
    }
}
