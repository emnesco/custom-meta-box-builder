<?php
/**
 * Interface for render context — abstracts storage and object ID across post/term/user/option contexts.
 *
 * @package CustomMetaBoxBuilder
 * @since   2.0
 */
namespace CMB\Core\RenderContext;

use CMB\Core\Storage\StorageInterface;

interface RenderContextInterface {
    public function getObjectId(): int|string;
    public function getStorage(): StorageInterface;
    public function getContextType(): string; // 'post', 'term', 'user', 'option'
}
