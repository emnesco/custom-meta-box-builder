<?php
namespace CMB\Core\RenderContext;

use CMB\Core\Storage\OptionStorage;
use CMB\Core\Storage\StorageInterface;

class OptionContext implements RenderContextInterface {
    private string $pageSlug;
    private StorageInterface $storage;

    public function __construct(string $pageSlug, ?StorageInterface $storage = null) {
        $this->pageSlug = $pageSlug;
        $this->storage  = $storage ?? new OptionStorage();
    }

    public function getObjectId(): int|string {
        return $this->pageSlug;
    }

    public function getStorage(): StorageInterface {
        return $this->storage;
    }

    public function getContextType(): string {
        return 'option';
    }
}
