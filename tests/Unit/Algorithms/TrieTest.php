<?php

namespace AndyDefer\AlgoKIT\Tests\Unit\Algorithms;

use AndyDefer\AlgoKIT\Algorithms\Trie;
use AndyDefer\AlgoKIT\Collections\TrieCollection;
use AndyDefer\AlgoKIT\Collections\TrieResultCollection;
use AndyDefer\AlgoKIT\Records\TrieRecord;
use AndyDefer\AlgoKIT\Storage\MemoryStorage;
use PHPUnit\Framework\TestCase;

class TrieTest extends TestCase
{
    private Trie $trie;

    protected function setUp(): void
    {
        $storage = new MemoryStorage;
        $this->trie = new Trie($storage, 'test_trie');
    }

    public function test_insert_and_search(): void
    {
        $words = ['laravel', 'laragon', 'large', 'laptop', 'light'];

        foreach ($words as $word) {
            $this->trie->insert($word);
        }

        $results = $this->trie->search('lar');

        $this->assertInstanceOf(TrieResultCollection::class, $results);
        $this->assertCount(3, $results);

        $items = $results->toArray();
        $this->assertEquals('laravel', $items[0]->word);
        $this->assertEquals('laragon', $items[1]->word);
        $this->assertEquals('large', $items[2]->word);
    }

    public function test_search_with_limit(): void
    {
        $words = ['php', 'python', 'perl', 'pascal', 'puppet'];

        foreach ($words as $word) {
            $this->trie->insert($word);
        }

        $results = $this->trie->search('p', 2);

        $this->assertInstanceOf(TrieResultCollection::class, $results);
        $this->assertCount(2, $results);
    }

    public function test_search_empty_prefix(): void
    {
        $results = $this->trie->search('');

        $this->assertInstanceOf(TrieResultCollection::class, $results);
        $this->assertEmpty($results);
    }

    public function test_search_non_existent_prefix(): void
    {
        $this->trie->insert('laravel');
        $results = $this->trie->search('xyz');

        $this->assertInstanceOf(TrieResultCollection::class, $results);
        $this->assertEmpty($results);
    }

    public function test_duplicate_insert(): void
    {
        $this->trie->insert('laravel');
        $this->trie->insert('laravel');

        $results = $this->trie->search('lar');

        $this->assertInstanceOf(TrieResultCollection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals('laravel', $results->first()->word);
    }

    public function test_clear(): void
    {
        $this->trie->insert('laravel');
        $this->assertNotEmpty($this->trie->search('lar'));

        $this->trie->clear();
        $results = $this->trie->search('lar');

        $this->assertInstanceOf(TrieResultCollection::class, $results);
        $this->assertEmpty($results);
    }

    public function test_persistence(): void
    {
        $storage = new MemoryStorage;

        // Premier trie
        $trie1 = new Trie($storage, 'persistent_trie');
        $trie1->insert('laravel');
        $trie1->insert('php');

        // Deuxième trie avec le même storage
        $trie2 = new Trie($storage, 'persistent_trie');
        $results = $trie2->search('l');

        $this->assertInstanceOf(TrieResultCollection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals('laravel', $results->first()->word);
    }

    public function test_insert_batch(): void
    {
        $collection = new TrieCollection;
        $collection->add(new TrieRecord('laravel'));
        $collection->add(new TrieRecord('laragon'));
        $collection->add(new TrieRecord('large'));
        $collection->add(new TrieRecord('laptop'));

        $this->trie->insertBatch($collection);

        $results = $this->trie->search('lar');

        $this->assertInstanceOf(TrieResultCollection::class, $results);
        $this->assertCount(3, $results);

        $items = $results->toArray();
        $this->assertEquals('laravel', $items[0]->word);
        $this->assertEquals('laragon', $items[1]->word);
        $this->assertEquals('large', $items[2]->word);
    }

    public function test_insert_batch_with_empty_collection(): void
    {
        $collection = new TrieCollection;
        $this->trie->insertBatch($collection);

        $results = $this->trie->search('l');
        $this->assertInstanceOf(TrieResultCollection::class, $results);
        $this->assertEmpty($results);
    }

    public function test_search_batch(): void
    {
        $words = ['laravel', 'laragon', 'large', 'laptop', 'php', 'python'];

        foreach ($words as $word) {
            $this->trie->insert($word);
        }

        $collection = new TrieCollection;
        $collection->add(new TrieRecord('lar'));
        $collection->add(new TrieRecord('la'));
        $collection->add(new TrieRecord('p'));
        $collection->add(new TrieRecord('xyz'));

        $results = $this->trie->searchBatch($collection, 5);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('lar', $results);
        $this->assertArrayHasKey('la', $results);
        $this->assertArrayHasKey('p', $results);
        $this->assertArrayHasKey('xyz', $results);

        // Test pour le préfixe 'lar'
        $this->assertInstanceOf(TrieResultCollection::class, $results['lar']);
        $this->assertCount(3, $results['lar']);

        // Test pour le préfixe 'la'
        $this->assertInstanceOf(TrieResultCollection::class, $results['la']);
        $this->assertCount(4, $results['la']);

        // Test pour le préfixe 'p'
        $this->assertInstanceOf(TrieResultCollection::class, $results['p']);
        $this->assertCount(2, $results['p']);

        // Test pour le préfixe 'xyz' (inexistant)
        $this->assertInstanceOf(TrieResultCollection::class, $results['xyz']);
        $this->assertEmpty($results['xyz']);
    }

    public function test_search_batch_with_empty_collection(): void
    {
        $collection = new TrieCollection;
        $results = $this->trie->searchBatch($collection);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }
}
