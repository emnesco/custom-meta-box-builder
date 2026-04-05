<?php
namespace CMB\Core\RenderContext;

use CMB\Core\Storage\StorageInterface;

interface RenderContextInterface {
    public function getObjectId(): int|string;
    public function getStorage(): StorageInterface;
    public function getContextType(): string; // 'post', 'term', 'user', 'option'
}
