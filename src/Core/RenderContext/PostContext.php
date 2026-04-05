<?php
/**
 * Post render context implementation.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Core\RenderContext;

use CMB\Core\Storage\PostMetaStorage;
use CMB\Core\Storage\StorageInterface;

class PostContext implements RenderContextInterface {
    private \WP_Post $post;
    private StorageInterface $storage;

    public function __construct(\WP_Post $post, ?StorageInterface $storage = null) {
        $this->post    = $post;
        $this->storage = $storage ?? new PostMetaStorage();
    }

    public function getObjectId(): int|string {
        return $this->post->ID;
    }

    public function getStorage(): StorageInterface {
        return $this->storage;
    }

    public function getContextType(): string {
        return 'post';
    }

    /**
     * Expose the underlying WP_Post for hooks that expect it.
     */
    public function getPost(): \WP_Post {
        return $this->post;
    }
}
