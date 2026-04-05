<?php

declare(strict_types=1);

namespace Tests\Unit;

use CMB\Core\FieldUtils;
use Tests\TestCase;

/**
 * Tests for CMB\Core\FieldUtils.
 */
final class FieldUtilsTest extends TestCase
{
    // ------------------------------------------------------------------
    // flattenFields() — tabbed structure
    // ------------------------------------------------------------------

    public function testFlattenFieldsWithTabsReturnsFlatArray(): void
    {
        $fields = [
            'tabs' => [
                [
                    'label'  => 'General',
                    'fields' => [
                        ['id' => 'title',   'type' => 'text'],
                        ['id' => 'content', 'type' => 'textarea'],
                    ],
                ],
                [
                    'label'  => 'SEO',
                    'fields' => [
                        ['id' => 'meta_title', 'type' => 'text'],
                    ],
                ],
            ],
        ];

        $flat = FieldUtils::flattenFields($fields);

        $this->assertCount(3, $flat);
        $this->assertSame('title',      $flat[0]['id']);
        $this->assertSame('content',    $flat[1]['id']);
        $this->assertSame('meta_title', $flat[2]['id']);
    }

    public function testFlattenFieldsWithEmptyTabsReturnsSameArray(): void
    {
        // When tabs key is empty, empty() returns true so the method
        // falls through to the non-tabbed branch, returning $fields as-is.
        $fields = ['tabs' => []];

        $flat = FieldUtils::flattenFields($fields);

        $this->assertIsArray($flat);
        $this->assertSame($fields, $flat);
    }

    public function testFlattenFieldsWithTabsThatHaveNoFieldsKey(): void
    {
        $fields = [
            'tabs' => [
                ['label' => 'Empty Tab'],
            ],
        ];

        $flat = FieldUtils::flattenFields($fields);

        $this->assertIsArray($flat);
        $this->assertEmpty($flat);
    }

    // ------------------------------------------------------------------
    // flattenFields() — without tabs (plain field list)
    // ------------------------------------------------------------------

    public function testFlattenFieldsWithoutTabsReturnsOriginalArray(): void
    {
        $fields = [
            ['id' => 'name',  'type' => 'text'],
            ['id' => 'email', 'type' => 'email'],
        ];

        $flat = FieldUtils::flattenFields($fields);

        $this->assertSame($fields, $flat);
    }

    public function testFlattenFieldsWithEmptyArrayReturnsEmptyArray(): void
    {
        $flat = FieldUtils::flattenFields([]);

        $this->assertIsArray($flat);
        $this->assertEmpty($flat);
    }

    public function testFlattenFieldsPreservesExtraTopLevelKeysWhenNoTabs(): void
    {
        $fields = [
            ['id' => 'field_a', 'type' => 'text'],
            ['id' => 'field_b', 'type' => 'number'],
        ];

        $flat = FieldUtils::flattenFields($fields);

        $this->assertCount(2, $flat);
    }
}
