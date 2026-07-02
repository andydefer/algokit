<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Tests\Unit\Algorithms;

use AndyDefer\AlgoKIT\Algorithms\InvertedIndex;
use AndyDefer\AlgoKIT\Collections\InvertedIndexCollection;
use AndyDefer\AlgoKIT\Collections\InvertedIndexResultCollection;
use AndyDefer\AlgoKIT\Collections\InvertedIndexSearchCollection;
use AndyDefer\AlgoKIT\Records\InvertedIndexFullRecord;
use AndyDefer\AlgoKIT\Records\InvertedIndexRecord;
use AndyDefer\AlgoKIT\Records\InvertedIndexSearchRecord;
use AndyDefer\AlgoKIT\Records\InvertedIndexStatsRecord;
use AndyDefer\AlgoKIT\Tests\SqliteStorageTestCase;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Utils\StrictAssociative;
use AndyDefer\StorageKit\Storage\MemoryStorage;

final class InvertedIndexTest extends SqliteStorageTestCase
{
    private InvertedIndex $index;

    protected function setUp(): void
    {
        parent::setUp();
        $this->index = new InvertedIndex($this->getStorage(), 'test_inverted_index');
    }

    protected function tearDown(): void
    {
        $this->index->clear();
        parent::tearDown();
    }

    // ============================================================
    // TESTS D'AJOUT ET DE RECHERCHE
    // ============================================================

    public function test_add_and_search(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel', 'framework'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_2',
            'tokens' => ['php', 'python'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_3',
            'tokens' => ['laravel', 'vuejs'],
        ]));

        $results = $this->index->search('php');

        $this->assertInstanceOf(StringTypedCollection::class, $results);
        $this->assertCount(2, $results);
        $this->assertContains('doc_1', $results->toArray());
        $this->assertContains('doc_2', $results->toArray());
    }

    public function test_search_non_existent_token(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel'],
        ]));

        $results = $this->index->search('nonexistent');

        $this->assertInstanceOf(StringTypedCollection::class, $results);
        $this->assertCount(0, $results);
    }

    public function test_add_removes_duplicates_in_same_document(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'php', 'php', 'laravel'],
        ]));

        $results = $this->index->search('php');

        $this->assertCount(1, $results);
        $this->assertContains('doc_1', $results->toArray());
    }

    // ============================================================
    // TESTS DE RECHERCHE PAR LOT
    // ============================================================

    public function test_search_batch(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_2',
            'tokens' => ['php', 'python'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_3',
            'tokens' => ['javascript', 'vuejs'],
        ]));

        $collection = new InvertedIndexSearchCollection;
        $collection->add(InvertedIndexSearchRecord::from(['token' => 'php']));
        $collection->add(InvertedIndexSearchRecord::from(['token' => 'python']));
        $collection->add(InvertedIndexSearchRecord::from(['token' => 'vuejs']));

        $results = $this->index->searchBatch($collection);

        $this->assertInstanceOf(InvertedIndexResultCollection::class, $results);
        $this->assertCount(3, $results);

        $items = $results->toArray();
        $this->assertCount(2, $items[0]->document_ids->toArray());
        $this->assertCount(1, $items[1]->document_ids->toArray());
        $this->assertCount(1, $items[2]->document_ids->toArray());
    }

    public function test_search_batch_empty_collection(): void
    {
        $collection = new InvertedIndexSearchCollection;

        $results = $this->index->searchBatch($collection);

        $this->assertInstanceOf(InvertedIndexResultCollection::class, $results);
        $this->assertCount(0, $results);
    }

    // ============================================================
    // TESTS D'AJOUT PAR LOT
    // ============================================================

    public function test_add_batch(): void
    {
        $collection = new InvertedIndexCollection;
        $collection->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel'],
        ]));
        $collection->add(InvertedIndexRecord::from([
            'document_id' => 'doc_2',
            'tokens' => ['php', 'python'],
        ]));
        $collection->add(InvertedIndexRecord::from([
            'document_id' => 'doc_3',
            'tokens' => ['javascript', 'vuejs'],
        ]));

        $this->index->addBatch($collection);

        $this->assertCount(2, $this->index->search('php')->toArray());
        $this->assertCount(1, $this->index->search('python')->toArray());
        $this->assertCount(1, $this->index->search('vuejs')->toArray());
        $this->assertCount(0, $this->index->search('ruby')->toArray());
    }

    public function test_add_batch_empty_collection(): void
    {
        $collection = new InvertedIndexCollection;

        $this->index->addBatch($collection);

        $this->assertSame(0, $this->index->getTotalTokens());
    }

    public function test_add_batch_with_duplicates(): void
    {
        $collection = new InvertedIndexCollection;
        $collection->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'php', 'laravel'],
        ]));
        $collection->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'python'],
        ]));

        $this->index->addBatch($collection);

        $this->assertCount(1, $this->index->search('php')->toArray());
        $this->assertContains('doc_1', $this->index->search('php')->toArray());
    }

    // ============================================================
    // TESTS DE SUPPRESSION
    // ============================================================

    public function test_remove_document(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_2',
            'tokens' => ['php', 'python'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_3',
            'tokens' => ['javascript', 'vuejs'],
        ]));

        $this->index->remove('doc_1');

        $this->assertCount(1, $this->index->search('php')->toArray());
        $this->assertContains('doc_2', $this->index->search('php')->toArray());
        $this->assertNotContains('doc_1', $this->index->search('php')->toArray());
        $this->assertCount(0, $this->index->search('laravel')->toArray());
    }

    public function test_remove_non_existent_document(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel'],
        ]));

        $this->index->remove('doc_999');

        $this->assertCount(1, $this->index->search('php')->toArray());
    }

    public function test_remove_token(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_2',
            'tokens' => ['php', 'python'],
        ]));

        $this->index->removeToken('php');

        $this->assertCount(0, $this->index->search('php')->toArray());
        $this->assertCount(1, $this->index->search('laravel')->toArray());
        $this->assertCount(1, $this->index->search('python')->toArray());
    }

    public function test_remove_non_existent_token(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel'],
        ]));

        $this->index->removeToken('ruby');

        $this->assertCount(1, $this->index->search('php')->toArray());
        $this->assertCount(1, $this->index->search('laravel')->toArray());
    }

    // ============================================================
    // TESTS DE STATISTIQUES
    // ============================================================

    public function test_get_stats(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_2',
            'tokens' => ['php', 'python'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_3',
            'tokens' => ['javascript', 'vuejs'],
        ]));

        $stats = $this->index->getStats();

        $this->assertInstanceOf(InvertedIndexStatsRecord::class, $stats);
        $this->assertSame(5, $stats->total_tokens);
        $this->assertSame(6, $stats->total_document_entries);
        $this->assertSame(2, $stats->max_token_frequency);
        $this->assertEqualsWithDelta(1.2, $stats->avg_token_frequency, 0.1);
    }

    public function test_get_stats_empty_index(): void
    {
        $stats = $this->index->getStats();

        $this->assertInstanceOf(InvertedIndexStatsRecord::class, $stats);
        $this->assertSame(0, $stats->total_tokens);
        $this->assertSame(0, $stats->total_document_entries);
        $this->assertSame(0, $stats->max_token_frequency);
        $this->assertSame(0.0, $stats->avg_token_frequency);
    }

    // ============================================================
    // TESTS DE DOCUMENT COUNT
    // ============================================================

    public function test_get_document_count(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_2',
            'tokens' => ['php', 'python'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_3',
            'tokens' => ['javascript', 'vuejs'],
        ]));

        $this->assertSame(2, $this->index->getDocumentCount('php'));
        $this->assertSame(1, $this->index->getDocumentCount('python'));
        $this->assertSame(0, $this->index->getDocumentCount('ruby'));
    }

    // ============================================================
    // TESTS DE TOTAL TOKENS
    // ============================================================

    public function test_get_total_tokens(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_2',
            'tokens' => ['php', 'python'],
        ]));

        $total = $this->index->getTotalTokens();

        $this->assertSame(3, $total);
    }

    // ============================================================
    // TESTS DE TOKEN FREQUENCY
    // ============================================================

    public function test_get_token_frequency(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_2',
            'tokens' => ['php', 'python'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_3',
            'tokens' => ['javascript', 'vuejs'],
        ]));

        $this->assertSame(2, $this->index->getTokenFrequency('php'));
        $this->assertSame(1, $this->index->getTokenFrequency('python'));
        $this->assertSame(0, $this->index->getTokenFrequency('ruby'));
    }

    // ============================================================
    // TESTS DE GET ALL TOKENS
    // ============================================================

    public function test_get_all_tokens(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_2',
            'tokens' => ['php', 'python'],
        ]));

        $tokens = $this->index->getAllTokens();

        $this->assertInstanceOf(StringTypedCollection::class, $tokens);
        $this->assertCount(3, $tokens);
        $this->assertContains('php', $tokens->toArray());
        $this->assertContains('laravel', $tokens->toArray());
        $this->assertContains('python', $tokens->toArray());
    }

    // ============================================================
    // TESTS DE GET ALL
    // ============================================================

    public function test_get_all(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_2',
            'tokens' => ['php', 'python'],
        ]));

        $full = $this->index->getAll();

        $this->assertInstanceOf(InvertedIndexFullRecord::class, $full);
        $this->assertInstanceOf(StrictAssociative::class, $full->index);

        $index = $full->index->toArray();
        $this->assertArrayHasKey('php', $index);
        $this->assertArrayHasKey('laravel', $index);
        $this->assertArrayHasKey('python', $index);
        $this->assertCount(2, $index['php']);
        $this->assertCount(1, $index['laravel']);
        $this->assertCount(1, $index['python']);
        $this->assertContains('doc_1', $index['php']);
        $this->assertContains('doc_2', $index['php']);
    }

    // ============================================================
    // TESTS DE GET ALL INDEX VIDE
    // ============================================================

    public function test_get_all_empty_index(): void
    {
        $full = $this->index->getAll();

        $this->assertInstanceOf(InvertedIndexFullRecord::class, $full);
        $this->assertInstanceOf(StrictAssociative::class, $full->index);
        $this->assertEmpty($full->index->toArray());
    }

    // ============================================================
    // TESTS DE CLEAR
    // ============================================================

    public function test_clear(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_2',
            'tokens' => ['php', 'python'],
        ]));

        $this->index->clear();

        $this->assertCount(0, $this->index->search('php')->toArray());
        $this->assertCount(0, $this->index->search('laravel')->toArray());
        $this->assertCount(0, $this->index->search('python')->toArray());
        $this->assertSame(0, $this->index->getTotalTokens());
    }

    // ============================================================
    // TESTS DE PERSISTANCE
    // ============================================================

    public function test_persistence(): void
    {
        $storage = new MemoryStorage;
        $key = 'persistent_inverted_index';

        $index1 = new InvertedIndex($storage, $key);
        $index1->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel'],
        ]));
        $index1->add(InvertedIndexRecord::from([
            'document_id' => 'doc_2',
            'tokens' => ['php', 'python'],
        ]));

        $index2 = new InvertedIndex($storage, $key);
        $results = $index2->search('php');

        $this->assertCount(2, $results);
        $this->assertContains('doc_1', $results->toArray());
        $this->assertContains('doc_2', $results->toArray());

        $storage->clear();
    }

    // ============================================================
    // TESTS DE CAS LIMITES
    // ============================================================

    public function test_search_with_empty_token(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel'],
        ]));

        $results = $this->index->search('');

        $this->assertInstanceOf(StringTypedCollection::class, $results);
        $this->assertCount(0, $results);
    }

    public function test_add_empty_document(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => [],
        ]));

        $this->assertCount(0, $this->index->search('php')->toArray());
        $this->assertSame(0, $this->index->getTotalTokens());
    }

    public function test_add_multiple_documents_with_same_tokens(): void
    {
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_1',
            'tokens' => ['php', 'laravel'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_2',
            'tokens' => ['php', 'laravel'],
        ]));
        $this->index->add(InvertedIndexRecord::from([
            'document_id' => 'doc_3',
            'tokens' => ['php', 'laravel'],
        ]));

        $this->assertCount(3, $this->index->search('php')->toArray());
        $this->assertCount(3, $this->index->search('laravel')->toArray());
        $this->assertSame(2, $this->index->getTotalTokens());
    }

    // ============================================================
    // TESTS DE PERFORMANCE
    // ============================================================

    public function test_performance_with_many_documents(): void
    {
        for ($i = 1; $i <= 100; $i++) {
            $this->index->add(InvertedIndexRecord::from([
                'document_id' => 'doc_'.$i,
                'tokens' => ['common', 'word_'.$i],
            ]));
        }

        $startTime = microtime(true);
        $results = $this->index->search('common');
        $endTime = microtime(true);

        $this->assertCount(100, $results);
        $this->assertLessThan(1.0, $endTime - $startTime);
    }
}
