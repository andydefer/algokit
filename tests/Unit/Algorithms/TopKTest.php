<?php

namespace AndyDefer\AlgoKIT\Tests\Unit\Algorithms;

use AndyDefer\AlgoKIT\Algorithms\TopK;
use AndyDefer\AlgoKIT\Collections\TopKCollection;
use AndyDefer\AlgoKIT\Collections\TopKResultCollection;
use AndyDefer\AlgoKIT\Records\TopKRecord;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;
use PHPUnit\Framework\TestCase;

class TopKTest extends TestCase
{
    private TopK $topK;

    protected function setUp(): void
    {
        $storage = new MemoryStorage;
        $this->topK = new TopK($storage, 3, 'test_topk');
    }

    public function test_add_and_get_top(): void
    {
        $values = ['php', 'laravel', 'php', 'python', 'laravel', 'php'];

        foreach ($values as $value) {
            $this->topK->add($value);
        }

        $top = $this->topK->getTop();

        $this->assertInstanceOf(TopKResultCollection::class, $top);
        $this->assertCount(3, $top);

        $items = $top->toArray();
        $this->assertEquals('php', $items[0]->value);
        $this->assertEquals(3, $items[0]->count);
        $this->assertEquals('laravel', $items[1]->value);
        $this->assertEquals(2, $items[1]->count);
        $this->assertEquals('python', $items[2]->value);
        $this->assertEquals(1, $items[2]->count);
    }

    public function test_add_with_increment(): void
    {
        $this->topK->add('php', 5);
        $this->topK->add('laravel', 3);
        $this->topK->add('python', 2);

        $top = $this->topK->getTop();

        $this->assertInstanceOf(TopKResultCollection::class, $top);

        $items = $top->toArray();
        $this->assertEquals('php', $items[0]->value);
        $this->assertEquals(5, $items[0]->count);
    }

    public function test_limited_k(): void
    {
        $storage = new MemoryStorage;
        $topK = new TopK($storage, 2, 'small_topk');

        $values = ['a', 'b', 'c', 'a', 'b', 'a'];

        foreach ($values as $value) {
            $topK->add($value);
        }

        $top = $topK->getTop();

        $this->assertInstanceOf(TopKResultCollection::class, $top);
        $this->assertCount(2, $top);

        $items = $top->toArray();
        $this->assertEquals('a', $items[0]->value);
        $this->assertEquals(3, $items[0]->count);
        $this->assertEquals('b', $items[1]->value);
        $this->assertEquals(2, $items[1]->count);
    }

    public function test_get_top_when_empty(): void
    {
        $top = $this->topK->getTop();

        $this->assertInstanceOf(TopKResultCollection::class, $top);
        $this->assertEmpty($top);
        $this->assertEquals(0, $top->count());
    }

    public function test_clear(): void
    {
        $this->topK->add('php');
        $this->topK->add('laravel');

        $this->assertNotEmpty($this->topK->getTop());

        $this->topK->clear();
        $top = $this->topK->getTop();

        $this->assertInstanceOf(TopKResultCollection::class, $top);
        $this->assertEmpty($top);
    }

    public function test_persistence(): void
    {
        $storage = new MemoryStorage;

        $topK1 = new TopK($storage, 3, 'persistent_topk');
        $topK1->add('php');
        $topK1->add('php');
        $topK1->add('laravel');

        $topK2 = new TopK($storage, 3, 'persistent_topk');
        $top = $topK2->getTop();

        $this->assertInstanceOf(TopKResultCollection::class, $top);

        $items = $top->toArray();
        $this->assertEquals('php', $items[0]->value);
        $this->assertEquals(2, $items[0]->count);
        $this->assertEquals('laravel', $items[1]->value);
        $this->assertEquals(1, $items[1]->count);
    }

    public function test_add_batch(): void
    {
        $collection = new TopKCollection;
        $collection->add(new TopKRecord('php', 2));
        $collection->add(new TopKRecord('laravel', 1));
        $collection->add(new TopKRecord('python', 1));
        $collection->add(new TopKRecord('php', 1));

        $this->topK->addBatch($collection);

        $top = $this->topK->getTop();

        $this->assertInstanceOf(TopKResultCollection::class, $top);
        $this->assertCount(3, $top);

        $items = $top->toArray();
        $this->assertEquals('php', $items[0]->value);
        $this->assertEquals(3, $items[0]->count);
        $this->assertEquals('laravel', $items[1]->value);
        $this->assertEquals(1, $items[1]->count);
        $this->assertEquals('python', $items[2]->value);
        $this->assertEquals(1, $items[2]->count);
    }

    public function test_add_batch_with_empty_collection(): void
    {
        $collection = new TopKCollection;
        $this->topK->addBatch($collection);

        $top = $this->topK->getTop();

        $this->assertInstanceOf(TopKResultCollection::class, $top);
        $this->assertEmpty($top);
        $this->assertEquals(0, $top->count());
    }
}
