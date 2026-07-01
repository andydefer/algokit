<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Contracts\Algorithms;

use AndyDefer\AlgoKIT\Collections\BloomFilterCollection;
use AndyDefer\AlgoKIT\Collections\BloomFilterResultCollection;

/**
 * Interface for Bloom Filter probabilistic membership tester.
 *
 * Tests set membership with configurable false-positive probability.
 * Uses multiple hash functions and a bit array for compact storage.
 */
interface BloomFilterInterface
{
    /**
     * Inserts a value into the bloom filter.
     *
     * @param  string  $value  The value to insert
     * @param  string|null  $context  Optional context for data isolation
     */
    public function insert(string $value, ?string $context = null): void;

    /**
     * Tests if a value probably exists in the bloom filter.
     *
     * @param  string  $value  The value to check
     * @param  string|null  $context  Optional context for data isolation
     * @return bool True if probably exists, false if definitely doesn't
     */
    public function exists(string $value, ?string $context = null): bool;

    /**
     * Inserts multiple values in batch for better performance.
     *
     * @param  BloomFilterCollection  $collection  Collection of values to insert
     */
    public function insertBatch(BloomFilterCollection $collection): void;

    /**
     * Tests multiple values for membership in batch.
     *
     * @param  BloomFilterCollection  $collection  Collection of values to check
     * @return BloomFilterResultCollection Collection of membership results
     */
    public function existsBatch(BloomFilterCollection $collection): BloomFilterResultCollection;

    /**
     * Clears all data from the bloom filter.
     *
     * @param  string|null  $context  If provided, clears only data for this context
     */
    public function clear(?string $context = null): void;
}
