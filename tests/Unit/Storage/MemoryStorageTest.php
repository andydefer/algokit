<?php

namespace AndyDefer\AlgoKIT\Tests\Unit\Storage;

use AndyDefer\AlgoKIT\Storage\MemoryStorage;
use PHPUnit\Framework\TestCase;

class MemoryStorageTest extends TestCase
{
    private MemoryStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new MemoryStorage;
    }

    public function test_set_and_get(): void
    {
        $this->storage->set('key1', 'value1');
        $this->assertEquals('value1', $this->storage->get('key1'));
    }

    public function test_get_default(): void
    {
        $this->assertEquals('default', $this->storage->get('nonexistent', 'default'));
        $this->assertNull($this->storage->get('nonexistent'));
    }

    public function test_exists(): void
    {
        $this->assertFalse($this->storage->exists('key1'));

        $this->storage->set('key1', 'value1');
        $this->assertTrue($this->storage->exists('key1'));
    }

    public function test_delete(): void
    {
        $this->storage->set('key1', 'value1');
        $this->assertTrue($this->storage->exists('key1'));

        $this->storage->delete('key1');
        $this->assertFalse($this->storage->exists('key1'));
    }

    public function test_delete_non_existent(): void
    {
        $this->assertFalse($this->storage->delete('nonexistent'));
    }

    public function test_set_array(): void
    {
        $data = ['a' => 1, 'b' => 2];
        $this->storage->set('array', $data);
        $this->assertEquals($data, $this->storage->get('array'));
    }
}
