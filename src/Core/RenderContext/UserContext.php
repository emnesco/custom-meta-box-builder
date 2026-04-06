<?php
declare(strict_types=1);

/**
 * User render context implementation.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */

namespace CMB\Core\RenderContext;

defined( 'ABSPATH' ) || exit;

use CMB\Core\Storage\StorageInterface;
use CMB\Core\Storage\UserMetaStorage;

class UserContext implements RenderContextInterface {
    private int $userId;
    private StorageInterface $storage;

    public function __construct(int $userId, ?StorageInterface $storage = null) {
        $this->userId  = $userId;
        $this->storage = $storage ?? new UserMetaStorage();
    }

    public function getObjectId(): int|string {
        return $this->userId;
    }

    public function getStorage(): StorageInterface {
        return $this->storage;
    }

    public function getContextType(): string {
        return 'user';
    }
}
