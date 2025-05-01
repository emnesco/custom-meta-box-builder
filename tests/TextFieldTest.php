<?php
use PHPUnit\Framework\TestCase;
use CMB\Fields\TextField;

final class TextFieldTest extends TestCase {
    public function testRenderTextFieldOutputsInput(): void {
        $field = new TextField([
            'id' => 'my_text',
            'label' => 'My Text Field'
        ]);

        $html = $field->render();
        $this->assertStringContainsString('input', $html);
        $this->assertStringContainsString('name=\"my_text\"', $html);
    }

    public function testSanitizeTextField(): void {
        $field = new TextField(['id' => 'test']);
        $dirty = '<script>alert(1)</script>';
        $clean = $field->sanitize($dirty);
        $this->assertSame('alert(1)', $clean);
    }
}
