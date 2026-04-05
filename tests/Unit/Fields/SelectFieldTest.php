<?php

declare(strict_types=1);

namespace Tests\Unit\Fields;

use CMB\Fields\SelectField;
use Tests\TestCase;

/**
 * Tests for CMB\Fields\SelectField.
 */
final class SelectFieldTest extends TestCase
{
    private function makeField(array $overrides = []): SelectField
    {
        return new SelectField(array_merge([
            'id'      => 'colour',
            'name'    => 'colour',
            'options' => [
                'red'   => 'Red',
                'green' => 'Green',
                'blue'  => 'Blue',
            ],
        ], $overrides));
    }

    // ------------------------------------------------------------------
    // render()
    // ------------------------------------------------------------------

    public function testRenderOutputsSelectElement(): void
    {
        $html = $this->makeField()->render();

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('</select>', $html);
    }

    public function testRenderIncludesNameAttribute(): void
    {
        $html = $this->makeField()->render();

        $this->assertStringContainsString('name="colour"', $html);
    }

    public function testRenderOutputsCorrectNumberOfOptions(): void
    {
        $html = $this->makeField()->render();

        $this->assertSame(4, substr_count($html, '<option')); // 3 options + placeholder
    }

    public function testRenderIncludesOptionValues(): void
    {
        $html = $this->makeField()->render();

        $this->assertStringContainsString('value="red"',   $html);
        $this->assertStringContainsString('value="green"', $html);
        $this->assertStringContainsString('value="blue"',  $html);
    }

    public function testRenderIncludesOptionLabels(): void
    {
        $html = $this->makeField()->render();

        $this->assertStringContainsString('>Red<',   $html);
        $this->assertStringContainsString('>Green<', $html);
        $this->assertStringContainsString('>Blue<',  $html);
    }

    public function testRenderMarksCurrentValueAsSelected(): void
    {
        $field = $this->makeField(['value' => 'green']);

        $html = $field->render();

        // The option for 'green' should carry selected="selected".
        $this->assertMatchesRegularExpression(
            '/value="green"\s+selected="selected"/',
            $html
        );
    }

    public function testRenderDoesNotMarkOtherOptionsAsSelected(): void
    {
        $field = $this->makeField(['value' => 'green']);

        $html = $field->render();

        // red and blue options should not be selected.
        $this->assertDoesNotMatch('/value="red"\s+selected/', $html);
        $this->assertDoesNotMatch('/value="blue"\s+selected/', $html);
    }

    public function testRenderWithNoOptionsOutputsEmptySelect(): void
    {
        $field = new SelectField(['id' => 'empty', 'name' => 'empty', 'options' => []]);

        $html = $field->render();

        $this->assertStringContainsString('<select', $html);
        // Only the placeholder option should be present
        $this->assertSame(1, substr_count($html, '<option'));
    }

    public function testRenderIncludesIdAttributeWhenHtmlIdSet(): void
    {
        $field = $this->makeField(['html_id' => 'colour_select']);

        $html = $field->render();

        $this->assertStringContainsString('id="colour_select"', $html);
    }

    public function testRenderIncludesRequiredAttribute(): void
    {
        $field = $this->makeField(['required' => true]);

        $html = $field->render();

        $this->assertStringContainsString('required', $html);
    }

    // ------------------------------------------------------------------
    // sanitize()
    // ------------------------------------------------------------------

    public function testSanitizeAllowsValidOption(): void
    {
        $field = $this->makeField();

        $this->assertSame('red', $field->sanitize('red'));
    }

    public function testSanitizeRejectsOptionNotInList(): void
    {
        $field = $this->makeField();

        $this->assertSame('', $field->sanitize('purple'));
    }

    public function testSanitizeRejectsEmptyStringWhenNotAnOption(): void
    {
        $field = $this->makeField();

        $this->assertSame('', $field->sanitize(''));
    }

    public function testSanitizeAllowsEmptyStringWhenItIsAnOption(): void
    {
        $field = new SelectField([
            'id'      => 'status',
            'name'    => 'status',
            'options' => ['' => 'None', 'active' => 'Active'],
        ]);

        $this->assertSame('', $field->sanitize(''));
    }

    public function testSanitizeHandlesArrayOfValues(): void
    {
        $field = $this->makeField();

        $result = $field->sanitize(['red', 'purple', 'blue']);

        $this->assertIsArray($result);
        $this->assertContains('red', $result);
        $this->assertNotContains('purple', $result); // 'purple' not in list
        $this->assertContains('blue', $result);
    }

    // Helper: assert pattern does NOT match.
    private function assertDoesNotMatch(string $pattern, string $string, string $message = ''): void
    {
        $this->assertDoesNotMatchRegularExpression($pattern, $string, $message);
    }
}
