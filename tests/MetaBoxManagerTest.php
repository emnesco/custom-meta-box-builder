<?php
use PHPUnit\Framework\TestCase;
use CMB\Core\MetaBoxManager;

final class MetaBoxManagerTest extends TestCase {
    public function testCanAddMetaBox(): void {
        $manager = new MetaBoxManager();

        $manager->add('test_box', 'Test Meta Box', ['post'], [
            ['id' => 'test_field', 'type' => 'text', 'label' => 'Test Field']
        ]);

        $reflection = new ReflectionClass($manager);
        $property = $reflection->getProperty('metaBoxes');
        $property->setAccessible(true);
        $metaBoxes = $property->getValue($manager);

        $this->assertArrayHasKey('test_box', $metaBoxes);
        $this->assertSame('Test Meta Box', $metaBoxes['test_box']['title']);
    }
}