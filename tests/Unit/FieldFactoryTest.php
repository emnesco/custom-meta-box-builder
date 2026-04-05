<?php

declare(strict_types=1);

namespace Tests\Unit;

use CMB\Core\FieldFactory;
use CMB\Core\Contracts\FieldInterface;
use CMB\Fields\TextField;
use CMB\Fields\NumberField;
use CMB\Fields\SelectField;
use Tests\TestCase;

/**
 * Tests for CMB\Core\FieldFactory.
 */
final class FieldFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset static custom types between tests via reflection.
        $ref = new \ReflectionClass(FieldFactory::class);
        $prop = $ref->getProperty('customTypes');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    // ------------------------------------------------------------------
    // resolveClass()
    // ------------------------------------------------------------------

    public function testResolveClassReturnsTextFieldClass(): void
    {
        $this->assertSame(TextField::class, FieldFactory::resolveClass('text'));
    }

    public function testResolveClassReturnsNumberFieldClass(): void
    {
        $this->assertSame(NumberField::class, FieldFactory::resolveClass('number'));
    }

    public function testResolveClassReturnsSelectFieldClass(): void
    {
        $this->assertSame(SelectField::class, FieldFactory::resolveClass('select'));
    }

    public function testResolveClassReturnsNullForUnknownType(): void
    {
        $this->assertNull(FieldFactory::resolveClass('does_not_exist'));
    }

    public function testResolveClassReturnsNullForEmptyType(): void
    {
        $this->assertNull(FieldFactory::resolveClass(''));
    }

    // ------------------------------------------------------------------
    // registerType()
    // ------------------------------------------------------------------

    public function testRegisterTypeAllowsCustomClass(): void
    {
        // Use an existing concrete class as a stand-in for a "custom" type.
        FieldFactory::registerType('my_custom', TextField::class);

        $this->assertSame(TextField::class, FieldFactory::resolveClass('my_custom'));
    }

    public function testRegisterTypeDoesNotRegisterNonExistentClass(): void
    {
        FieldFactory::registerType('ghost', 'CMB\\Fields\\DoesNotExistField');

        $this->assertNull(FieldFactory::resolveClass('ghost'));
    }

    public function testCustomTypesTakePrecedenceOverBuiltIn(): void
    {
        // Register TextField under the 'number' key to override built-in.
        FieldFactory::registerType('number', TextField::class);

        $this->assertSame(TextField::class, FieldFactory::resolveClass('number'));
    }

    // ------------------------------------------------------------------
    // create()
    // ------------------------------------------------------------------

    public function testCreateReturnsFieldInterface(): void
    {
        $field = FieldFactory::create('text', ['id' => 'my_text', 'name' => 'my_text']);

        $this->assertInstanceOf(FieldInterface::class, $field);
        $this->assertInstanceOf(TextField::class, $field);
    }

    public function testCreateReturnsNullForUnknownType(): void
    {
        $this->assertNull(FieldFactory::create('totally_unknown', []));
    }

    public function testCreatePassesConfigToField(): void
    {
        $config = ['id' => 'price', 'name' => 'price', 'label' => 'Price'];
        $field  = FieldFactory::create('number', $config);

        $this->assertNotNull($field);
        $this->assertSame('price', $field->getId());
        $this->assertSame($config, $field->getConfig());
    }
}
