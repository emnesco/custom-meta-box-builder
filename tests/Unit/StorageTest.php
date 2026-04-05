<?php

declare(strict_types=1);

namespace Tests\Unit;

use Brain\Monkey\Functions;
use CMB\Core\Storage\PostMetaStorage;
use CMB\Core\Storage\StorageInterface;
use CMB\Core\Storage\TermMetaStorage;
use CMB\Core\Storage\UserMetaStorage;
use Tests\TestCase;

/**
 * Tests for the three concrete Storage implementations.
 *
 * We verify:
 *  1. Each class implements StorageInterface.
 *  2. Each method delegates to the correct WordPress function with the
 *     right arguments (stubbed via Brain\Monkey).
 */
final class StorageTest extends TestCase
{
    // ------------------------------------------------------------------
    // Interface contract
    // ------------------------------------------------------------------

    public function testPostMetaStorageImplementsStorageInterface(): void
    {
        $this->assertInstanceOf(StorageInterface::class, new PostMetaStorage());
    }

    public function testTermMetaStorageImplementsStorageInterface(): void
    {
        $this->assertInstanceOf(StorageInterface::class, new TermMetaStorage());
    }

    public function testUserMetaStorageImplementsStorageInterface(): void
    {
        $this->assertInstanceOf(StorageInterface::class, new UserMetaStorage());
    }

    // ------------------------------------------------------------------
    // PostMetaStorage
    // ------------------------------------------------------------------

    public function testPostMetaStorageGetCallsGetPostMeta(): void
    {
        Functions\expect('get_post_meta')
            ->once()
            ->with(42, 'my_key', true)
            ->andReturn('stored value');

        $storage = new PostMetaStorage();
        $result  = $storage->get(42, 'my_key');

        $this->assertSame('stored value', $result);
    }

    public function testPostMetaStorageSetCallsUpdatePostMeta(): void
    {
        Functions\expect('update_post_meta')
            ->once()
            ->with(42, 'my_key', 'new value')
            ->andReturn(true);

        $storage = new PostMetaStorage();
        $result  = $storage->set(42, 'my_key', 'new value');

        $this->assertTrue($result);
    }

    public function testPostMetaStorageDeleteCallsDeletePostMeta(): void
    {
        Functions\expect('delete_post_meta')
            ->once()
            ->with(42, 'my_key')
            ->andReturn(true);

        $storage = new PostMetaStorage();
        $result  = $storage->delete(42, 'my_key');

        $this->assertTrue($result);
    }

    public function testPostMetaStorageGetAllCallsGetPostMetaWithoutKey(): void
    {
        Functions\expect('get_post_meta')
            ->once()
            ->with(42)
            ->andReturn(['my_key' => ['value']]);

        $storage = new PostMetaStorage();
        $result  = $storage->getAll(42);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('my_key', $result);
    }

    public function testPostMetaStorageGetAllReturnsEmptyArrayWhenWpReturnsNonArray(): void
    {
        Functions\expect('get_post_meta')
            ->once()
            ->with(1)
            ->andReturn(false);

        $storage = new PostMetaStorage();
        $result  = $storage->getAll(1);

        $this->assertSame([], $result);
    }

    // ------------------------------------------------------------------
    // TermMetaStorage
    // ------------------------------------------------------------------

    public function testTermMetaStorageGetCallsGetTermMeta(): void
    {
        Functions\expect('get_term_meta')
            ->once()
            ->with(10, 'my_key', true)
            ->andReturn('term value');

        $storage = new TermMetaStorage();
        $result  = $storage->get(10, 'my_key');

        $this->assertSame('term value', $result);
    }

    public function testTermMetaStorageSetCallsUpdateTermMeta(): void
    {
        Functions\expect('update_term_meta')
            ->once()
            ->with(10, 'my_key', 'updated')
            ->andReturn(true);

        $storage = new TermMetaStorage();
        $result  = $storage->set(10, 'my_key', 'updated');

        $this->assertTrue($result);
    }

    public function testTermMetaStorageDeleteCallsDeleteTermMeta(): void
    {
        Functions\expect('delete_term_meta')
            ->once()
            ->with(10, 'my_key')
            ->andReturn(true);

        $storage = new TermMetaStorage();
        $result  = $storage->delete(10, 'my_key');

        $this->assertTrue($result);
    }

    public function testTermMetaStorageGetAllCallsGetTermMetaWithoutKey(): void
    {
        Functions\expect('get_term_meta')
            ->once()
            ->with(10)
            ->andReturn(['slug' => ['value']]);

        $storage = new TermMetaStorage();
        $result  = $storage->getAll(10);

        $this->assertIsArray($result);
    }

    public function testTermMetaStorageGetAllReturnsEmptyArrayWhenWpReturnsNonArray(): void
    {
        Functions\expect('get_term_meta')
            ->once()
            ->with(5)
            ->andReturn(null);

        $storage = new TermMetaStorage();
        $result  = $storage->getAll(5);

        $this->assertSame([], $result);
    }

    // ------------------------------------------------------------------
    // UserMetaStorage
    // ------------------------------------------------------------------

    public function testUserMetaStorageGetCallsGetUserMeta(): void
    {
        Functions\expect('get_user_meta')
            ->once()
            ->with(99, 'my_key', true)
            ->andReturn('user value');

        $storage = new UserMetaStorage();
        $result  = $storage->get(99, 'my_key');

        $this->assertSame('user value', $result);
    }

    public function testUserMetaStorageSetCallsUpdateUserMeta(): void
    {
        Functions\expect('update_user_meta')
            ->once()
            ->with(99, 'my_key', 'updated')
            ->andReturn(true);

        $storage = new UserMetaStorage();
        $result  = $storage->set(99, 'my_key', 'updated');

        $this->assertTrue($result);
    }

    public function testUserMetaStorageDeleteCallsDeleteUserMeta(): void
    {
        Functions\expect('delete_user_meta')
            ->once()
            ->with(99, 'my_key')
            ->andReturn(true);

        $storage = new UserMetaStorage();
        $result  = $storage->delete(99, 'my_key');

        $this->assertTrue($result);
    }

    public function testUserMetaStorageGetAllCallsGetUserMetaWithoutKey(): void
    {
        Functions\expect('get_user_meta')
            ->once()
            ->with(99)
            ->andReturn(['bio' => ['value']]);

        $storage = new UserMetaStorage();
        $result  = $storage->getAll(99);

        $this->assertIsArray($result);
    }

    public function testUserMetaStorageGetAllReturnsEmptyArrayWhenWpReturnsNonArray(): void
    {
        Functions\expect('get_user_meta')
            ->once()
            ->with(7)
            ->andReturn(false);

        $storage = new UserMetaStorage();
        $result  = $storage->getAll(7);

        $this->assertSame([], $result);
    }
}
