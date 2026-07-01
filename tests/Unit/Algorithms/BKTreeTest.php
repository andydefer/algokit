<?php

namespace AndyDefer\AlgoKIT\Tests\Unit\Algorithms;

use AndyDefer\AlgoKIT\Algorithms\BKTree;
use AndyDefer\AlgoKIT\Collections\BKTreeResultCollection;
use AndyDefer\AlgoKIT\Records\BKTreeResultRecord;
use AndyDefer\AlgoKIT\Tests\CacheStorageTestCase;
use AndyDefer\StorageKit\Storage\MemoryStorage;

class BKTreeTest extends CacheStorageTestCase
{
    private BKTree $bkTree;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bkTree = new BKTree($this->getStorage(), 'test_bktree');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_insert_and_search(): void
    {
        $words = ['laravel', 'python', 'php', 'javascript', 'ruby'];

        foreach ($words as $word) {
            $this->bkTree->insert($word);
        }

        $results = $this->bkTree->search('larvel', 2);

        $this->assertInstanceOf(BKTreeResultCollection::class, $results);
        $this->assertNotEmpty($results);

        $firstResult = $results->first();
        $this->assertInstanceOf(BKTreeResultRecord::class, $firstResult);
        $this->assertEquals('laravel', $firstResult->word);
        $this->assertLessThanOrEqual(2, $firstResult->distance);
    }

    public function test_search_with_tolerance(): void
    {
        $words = ['php', 'python', 'perl', 'pascal'];

        foreach ($words as $word) {
            $this->bkTree->insert($word);
        }

        // Tolérance 0 = exact match
        $results = $this->bkTree->search('php', 0);

        $this->assertInstanceOf(BKTreeResultCollection::class, $results);
        $this->assertCount(1, $results);

        $firstResult = $results->first();
        $this->assertInstanceOf(BKTreeResultRecord::class, $firstResult);
        $this->assertEquals('php', $firstResult->word);
        $this->assertEquals(0, $firstResult->distance);
    }

    public function test_search_with_limit(): void
    {
        $words = ['laravel', 'laragon', 'large', 'laptop'];

        foreach ($words as $word) {
            $this->bkTree->insert($word);
        }

        $results = $this->bkTree->search('lara', 3, 2);

        $this->assertInstanceOf(BKTreeResultCollection::class, $results);
        $this->assertCount(2, $results);

        // Vérifier que les résultats ont la bonne distance
        $items = $results->toArray();
        $this->assertLessThanOrEqual(3, $items[0]->distance);
        $this->assertLessThanOrEqual(3, $items[1]->distance);

        // Vérifier que les mots sont parmi les attendus (pas d'ordre spécifique)
        $expectedWords = ['laravel', 'laragon', 'large'];
        $this->assertContains($items[0]->word, $expectedWords);
        $this->assertContains($items[1]->word, $expectedWords);
    }

    public function test_search_non_existent(): void
    {
        $results = $this->bkTree->search('nonexistent', 2);

        $this->assertInstanceOf(BKTreeResultCollection::class, $results);
        $this->assertEmpty($results);
        $this->assertEquals(0, $results->count());
    }

    public function test_clear(): void
    {
        $this->bkTree->insert('laravel');
        $this->assertNotEmpty($this->bkTree->search('larvel', 2));

        $this->bkTree->clear();
        $results = $this->bkTree->search('larvel', 2);
        $this->assertInstanceOf(BKTreeResultCollection::class, $results);
        $this->assertEmpty($results);
    }

    public function test_persistence(): void
    {
        $storage = new MemoryStorage;

        $bkTree1 = new BKTree($storage, 'persistent_bktree');
        $bkTree1->insert('laravel');
        $bkTree1->insert('php');

        $bkTree2 = new BKTree($storage, 'persistent_bktree');
        $results = $bkTree2->search('larvel', 2);

        $this->assertInstanceOf(BKTreeResultCollection::class, $results);
        $this->assertNotEmpty($results);

        $firstResult = $results->first();
        $this->assertInstanceOf(BKTreeResultRecord::class, $firstResult);
        $this->assertEquals('laravel', $firstResult->word);
    }
}
