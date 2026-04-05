<?php
use PHPUnit\Framework\TestCase;
use CMB\Core\MetaBoxManager;

final class MetaBoxManagerTest extends TestCase {

    protected function setUp(): void {
        // Reset singleton between tests
        $reflection = new ReflectionClass(MetaBoxManager::class);
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    public function testSingletonReturnsSameInstance(): void {
        $a = MetaBoxManager::instance();
        $b = MetaBoxManager::instance();
        $this->assertSame($a, $b);
    }

    public function testCanAddMetaBox(): void {
        $manager = MetaBoxManager::instance();

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
