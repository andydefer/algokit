<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Contracts\Algorithms;

use AndyDefer\AlgoKIT\Collections\BKTreeResultCollection;

/**
 * Interface for tree-based approximate string matching.
 *
 * Used for fuzzy search and spell correction using Levenshtein distance.
 */
interface TreeInterface
{
    /**
     * Inserts a word into the tree.
     *
     * @param  string  $word  The word to insert
     */
    public function insert(string $word): void;

    /**
     * Searches for words similar to a given word.
     *
     * @param  string  $word  The word to search for
     * @param  int  $tolerance  Maximum Levenshtein distance allowed
     * @param  int  $limit  Maximum number of results to return
     * @return BKTreeResultCollection Collection of similar words with distances
     */
    public function search(string $word, int $tolerance = 2, int $limit = 10): BKTreeResultCollection;

    /**
     * Clears all data from the tree.
     */
    public function clear(): void;
}
