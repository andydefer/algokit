<?php

namespace AndyDefer\AlgoKIT\Tests\Unit\Algorithms;

use AndyDefer\AlgoKIT\Algorithms\HyperLogLog;
use AndyDefer\AlgoKIT\Collections\HyperLogLogCollection;
use AndyDefer\AlgoKIT\Collections\HyperLogLogResultCollection;
use AndyDefer\AlgoKIT\Records\HyperLogLogRecord;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;
use PHPUnit\Framework\TestCase;

class HyperLogLogTest extends TestCase
{
    private HyperLogLog $hll;

    protected function setUp(): void
    {
        $storage = new MemoryStorage;
        $this->hll = new HyperLogLog($storage, 8, 'test_hll');
    }

    public function test_add_and_count(): void
    {
        $values = ['a', 'b', 'c', 'd', 'e'];

        foreach ($values as $value) {
            $this->hll->add($value);
        }

        $count = $this->hll->count();
        $this->assertEquals(5, $count);
    }

    public function test_duplicates(): void
    {
        $values = ['a', 'a', 'a', 'b', 'b', 'c'];

        foreach ($values as $value) {
            $this->hll->add($value);
        }

        $count = $this->hll->count();
        $this->assertEquals(3, $count);
    }

    public function test_large_set(): void
    {
        $storage = new MemoryStorage;
        // Précision 16 = 65536 registres, erreur très faible
        $hll = new HyperLogLog($storage, 16, 'test_hll_precise');

        for ($i = 0; $i < 1000; $i++) {
            $value = 'value_'.$i;
            $hll->add($value);
        }

        $count = $hll->count();

        $this->assertGreaterThan(950, $count);
        $this->assertLessThan(1050, $count);
    }

    public function test_clear(): void
    {
        $this->hll->add('a');
        $this->hll->add('b');

        $this->assertGreaterThan(0, $this->hll->count());

        $this->hll->clear();
        $this->assertEquals(0, $this->hll->count());
    }

    public function test_persistence(): void
    {
        $storage = new MemoryStorage;

        $hll1 = new HyperLogLog($storage, 8, 'persistent_hll');
        $hll1->add('a');
        $hll1->add('b');
        $hll1->add('c');

        $hll2 = new HyperLogLog($storage, 8, 'persistent_hll');
        $count = $hll2->count();
        $this->assertEquals(3, $count);
    }

    public function test_add_batch(): void
    {
        $collection = new HyperLogLogCollection;
        $collection->add(new HyperLogLogRecord('a'));
        $collection->add(new HyperLogLogRecord('b'));
        $collection->add(new HyperLogLogRecord('c'));
        $collection->add(new HyperLogLogRecord('a')); // Duplicate

        $this->hll->addBatch($collection);

        $count = $this->hll->count();
        $this->assertEquals(3, $count);
    }

    public function test_add_batch_with_empty_collection(): void
    {
        $collection = new HyperLogLogCollection;
        $this->hll->addBatch($collection);

        $count = $this->hll->count();
        $this->assertEquals(0, $count);
    }

    public function test_count_batch(): void
    {
        $this->hll->add('a');
        $this->hll->add('b');
        $this->hll->add('c');

        $collection = new HyperLogLogCollection;
        $collection->add(new HyperLogLogRecord('a', 'context1'));
        $collection->add(new HyperLogLogRecord('b', 'context2'));
        $collection->add(new HyperLogLogRecord('d', 'context3'));

        $results = $this->hll->countBatch($collection);

        $this->assertInstanceOf(HyperLogLogResultCollection::class, $results);
        $this->assertCount(3, $results);

        $items = $results->toArray();
        $this->assertEquals(3, $items[0]->count);
        $this->assertEquals('context1', $items[0]->context);

        $this->assertEquals(3, $items[1]->count);
        $this->assertEquals('context2', $items[1]->context);

        $this->assertEquals(3, $items[2]->count);
        $this->assertEquals('context3', $items[2]->context);
    }

    public function test_count_batch_with_empty_collection(): void
    {
        $collection = new HyperLogLogCollection;
        $results = $this->hll->countBatch($collection);

        $this->assertInstanceOf(HyperLogLogResultCollection::class, $results);
        $this->assertCount(0, $results);
        $this->assertEmpty($results);
    }
}
