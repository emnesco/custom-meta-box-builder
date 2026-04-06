<?php
declare(strict_types=1);

/**
 * Option render context implementation.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\RenderContext;

defined( 'ABSPATH' ) || exit;

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
