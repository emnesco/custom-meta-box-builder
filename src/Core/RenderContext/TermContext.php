<?php
declare(strict_types=1);

/**
 * Term render context implementation.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\RenderContext;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Storage\StorageInterface;
use CMB\Core\Storage\TermMetaStorage;

class TermContext implements RenderContextInterface {
    private int $termId;
    private StorageInterface $storage;

    public function __construct(int $termId, ?StorageInterface $storage = null) {
        $this->termId  = $termId;
        $this->storage = $storage ?? new TermMetaStorage();
    }

    public function getObjectId(): int|string {
        return $this->termId;
    }

    public function getStorage(): StorageInterface {
        return $this->storage;
    }

    public function getContextType(): string {
        return 'term';
    }
}
