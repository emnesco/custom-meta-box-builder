<?php
use PHPUnit\Framework\TestCase;
use CMB\Core\Plugin;

final class PluginTest extends TestCase {
    public function testPluginBoots(): void {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->expectNotToPerformAssertions();
        $plugin->boot();
    }
}
