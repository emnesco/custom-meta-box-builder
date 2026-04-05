<?php

declare(strict_types=1);

namespace Tests\Unit\Fields;

use CMB\Fields\TextField;
use Tests\TestCase;

/**
 * Tests for CMB\Fields\TextField.
 */
final class TextFieldTest extends TestCase
{
    // ------------------------------------------------------------------
    // render()
    // ------------------------------------------------------------------

    public function testRenderOutputsInputElement(): void
    {
        $field = new TextField(['id' => 'title', 'name' => 'title', 'label' => 'Title']);

        $html = $field->render();

        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="text"', $html);
    }

    public function testRenderIncludesNameAttribute(): void
    {
        $field = new TextField(['id' => 'my_text', 'name' => 'my_text']);

        $html = $field->render();

        $this->assertStringContainsString('name="my_text"', $html);
    }

    public function testRenderIncludesValueAttribute(): void
    {
        $field = new TextField(['id' => 'f', 'name' => 'f', 'value' => 'hello world']);

        $html = $field->render();

        $this->assertStringContainsString('value="hello world"', $html);
    }

    public function testRenderEscapesHtmlEntitiesInValue(): void
    {
        $field = new TextField(['id' => 'f', 'name' => 'f', 'value' => '<script>']);

        $html = $field->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testRenderIncludesIdAttributeWhenHtmlIdSet(): void
    {
        $field = new TextField(['id' => 'f', 'name' => 'f', 'html_id' => 'custom_id']);

        $html = $field->render();

        $this->assertStringContainsString('id="custom_id"', $html);
    }

    public function testRenderDoesNotIncludeIdAttributeWhenHtmlIdAbsent(): void
    {
        $field = new TextField(['id' => 'f', 'name' => 'f']);

        $html = $field->render();

        $this->assertStringNotContainsString(' id=', $html);
    }

    public function testRenderIncludesRequiredAttribute(): void
    {
        $field = new TextField(['id' => 'f', 'name' => 'f', 'required' => true]);

        $html = $field->render();

        $this->assertStringContainsString('required', $html);
    }

    public function testRenderRepeatableFieldOutputsMultipleInputs(): void
    {
        $field = new TextField([
            'id'     => 'tags',
            'name'   => 'tags',
            'repeat' => true,
            'value'  => ['alpha', 'beta'],
        ]);

        $html = $field->render();

        $this->assertSame(2, substr_count($html, '<input'));
        $this->assertStringContainsString('value="alpha"', $html);
        $this->assertStringContainsString('value="beta"', $html);
    }

    public function testRenderRepeatableFieldOutputsOneInputWhenValueEmpty(): void
    {
        $field = new TextField(['id' => 'tags', 'name' => 'tags', 'repeat' => true]);

        $html = $field->render();

        $this->assertSame(1, substr_count($html, '<input'));
    }

    // ------------------------------------------------------------------
    // sanitize()
    // ------------------------------------------------------------------

    public function testSanitizeStripsHtmlTags(): void
    {
        $field = new TextField(['id' => 'f']);

        $result = $field->sanitize('<b>Hello</b>');

        $this->assertSame('Hello', $result);
    }

    public function testSanitizeRemovesScriptTags(): void
    {
        $field = new TextField(['id' => 'f']);

        $result = $field->sanitize('<script>alert(1)</script>');

        $this->assertSame('alert(1)', $result);
    }

    public function testSanitizeLeavesCleanStringUntouched(): void
    {
        $field = new TextField(['id' => 'f']);

        $result = $field->sanitize('Just plain text');

        $this->assertSame('Just plain text', $result);
    }

    public function testSanitizeHandlesArrayOfValues(): void
    {
        $field = new TextField(['id' => 'f']);

        $result = $field->sanitize(['<b>one</b>', '<em>two</em>']);

        $this->assertIsArray($result);
        $this->assertSame('one', $result[0]);
        $this->assertSame('two', $result[1]);
    }

    public function testSanitizeHandlesEmptyString(): void
    {
        $field = new TextField(['id' => 'f']);

        $result = $field->sanitize('');

        $this->assertSame('', $result);
    }
}
