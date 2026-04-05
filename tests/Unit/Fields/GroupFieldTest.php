<?php

declare(strict_types=1);

namespace Tests\Unit\Fields;

use CMB\Fields\GroupField;
use CMB\Core\FieldFactory;
use Tests\TestCase;

/**
 * Tests for CMB\Fields\GroupField::sanitize().
 *
 * GroupField::render() depends on WordPress functions (get_post, get_the_ID)
 * and FieldRenderer which in turn needs a full WP environment; those paths
 * are out of scope for unit testing and are covered by integration tests.
 * sanitize() is pure PHP logic that we can test in isolation.
 */
final class GroupFieldTest extends TestCase
{
    // ------------------------------------------------------------------
    // sanitize() — basic cases
    // ------------------------------------------------------------------

    public function testSanitizeReturnsEmptyArrayForNonArray(): void
    {
        $field = new GroupField(['id' => 'grp', 'name' => 'grp', 'fields' => []]);

        $this->assertSame([], $field->sanitize('not an array'));
        $this->assertSame([], $field->sanitize(null));
        $this->assertSame([], $field->sanitize(42));
    }

    public function testSanitizeReturnsEmptyArrayForEmptyInput(): void
    {
        $field = new GroupField(['id' => 'grp', 'name' => 'grp', 'fields' => []]);

        $this->assertSame([], $field->sanitize([]));
    }

    public function testSanitizeAppliesTextFieldSanitizationToSubFields(): void
    {
        $field = new GroupField([
            'id'     => 'grp',
            'name'   => 'grp',
            'fields' => [
                ['id' => 'title', 'type' => 'text'],
            ],
        ]);

        $raw = [
            0 => ['title' => '<b>Hello</b>'],
        ];

        $result = $field->sanitize($raw);

        $this->assertArrayHasKey(0, $result);
        $this->assertSame('Hello', $result[0]['title']);
    }

    public function testSanitizeAppliesNumberFieldSanitizationToSubFields(): void
    {
        $field = new GroupField([
            'id'     => 'grp',
            'name'   => 'grp',
            'fields' => [
                ['id' => 'qty', 'type' => 'number'],
            ],
        ]);

        $raw = [
            0 => ['qty' => '7'],
        ];

        $result = $field->sanitize($raw);

        $this->assertSame(7, $result[0]['qty']);
    }

    public function testSanitizeHandlesMultipleRows(): void
    {
        $field = new GroupField([
            'id'     => 'grp',
            'name'   => 'grp',
            'fields' => [
                ['id' => 'name',  'type' => 'text'],
                ['id' => 'score', 'type' => 'number'],
            ],
        ]);

        $raw = [
            0 => ['name' => '<em>Alice</em>', 'score' => '10'],
            1 => ['name' => '<em>Bob</em>',   'score' => '20'],
        ];

        $result = $field->sanitize($raw);

        $this->assertCount(2, $result);
        $this->assertSame('Alice', $result[0]['name']);
        $this->assertSame(10,      $result[0]['score']);
        $this->assertSame('Bob',   $result[1]['name']);
        $this->assertSame(20,      $result[1]['score']);
    }

    public function testSanitizeSkipsNonArrayRows(): void
    {
        $field = new GroupField([
            'id'     => 'grp',
            'name'   => 'grp',
            'fields' => [
                ['id' => 'title', 'type' => 'text'],
            ],
        ]);

        // Index 0 is valid, index 1 is a scalar — should be skipped.
        $raw = [
            0 => ['title' => 'Good row'],
            1 => 'not an array',
        ];

        $result = $field->sanitize($raw);

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayNotHasKey(1, $result);
    }

    public function testSanitizeUsesSanitizeCallbackWhenProvided(): void
    {
        $field = new GroupField([
            'id'     => 'grp',
            'name'   => 'grp',
            'fields' => [
                [
                    'id'                => 'custom',
                    'type'              => 'text',
                    'sanitize_callback' => static fn($v): string => strtoupper((string) $v),
                ],
            ],
        ]);

        $raw    = [0 => ['custom' => 'hello']];
        $result = $field->sanitize($raw);

        $this->assertSame('HELLO', $result[0]['custom']);
    }

    public function testSanitizeFallsBackToSanitizeTextFieldForUnknownSubFieldType(): void
    {
        $field = new GroupField([
            'id'     => 'grp',
            'name'   => 'grp',
            'fields' => [
                ['id' => 'mystery', 'type' => 'totally_unknown_type'],
            ],
        ]);

        $raw    = [0 => ['mystery' => '<script>evil()</script>']];
        $result = $field->sanitize($raw);

        // Falls back to sanitize_text_field which strips tags.
        $this->assertSame('evil()', $result[0]['mystery']);
    }

    public function testSanitizeMissingSubFieldKeyDefaultsToEmptyString(): void
    {
        $field = new GroupField([
            'id'     => 'grp',
            'name'   => 'grp',
            'fields' => [
                ['id' => 'title', 'type' => 'text'],
            ],
        ]);

        // Row does not provide 'title' key.
        $raw    = [0 => []];
        $result = $field->sanitize($raw);

        $this->assertSame('', $result[0]['title']);
    }
}
