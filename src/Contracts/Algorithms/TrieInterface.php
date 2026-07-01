<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Contracts\Algorithms;

use AndyDefer\AlgoKIT\Collections\TrieCollection;
use AndyDefer\AlgoKIT\Collections\TrieResultCollection;

/**
 * Interface for Trie (prefix tree) autocomplete structure.
 *
 * A Trie stores words in a tree structure where each node represents
 * a character, enabling fast prefix-based searches.
 */
interface TrieInterface
{
    /**
     * Inserts a word into the trie.
     *
     * @param  string  $word  The word to insert
     * @param  string|null  $context  Optional context for data isolation
     */
    public function insert(string $word, ?string $context = null): void;

    /**
     * Searches for words with a given prefix.
     *
     * @param  string  $prefix  The prefix to search for
     * @param  string|null  $context  Optional context for data isolation
     * @param  int  $limit  Maximum number of results to return
     * @return TrieResultCollection Collection of matching words
     */
    public function search(string $prefix, ?string $context = null, int $limit = 10): TrieResultCollection;

    /**
     * Inserts multiple words in batch for better performance.
     *
     * @param  TrieCollection  $collection  Collection of words to insert
     */
    public function insertBatch(TrieCollection $collection): void;

    /**
     * Searches for multiple prefixes in batch.
     *
     * @param  TrieCollection  $collection  Collection of prefixes to search
     * @param  int  $limit  Maximum results per prefix
     * @return array<string, TrieResultCollection> Map of prefix to results
     */
    public function searchBatch(TrieCollection $collection, int $limit = 10): array;

    /**
     * Clears all data from the trie.
     */
    public function clear(): void;
}
