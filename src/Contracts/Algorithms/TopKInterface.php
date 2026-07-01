<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Contracts\Algorithms;

use AndyDefer\AlgoKIT\Collections\TopKCollection;
use AndyDefer\AlgoKIT\Collections\TopKResultCollection;

/**
 * Interface for Top-K frequent elements tracker.
 *
 * Maintains the K most frequent elements in a data stream with
 * minimal memory usage using a space-saving algorithm.
 */
interface TopKInterface
{
    /**
     * Adds a value with optional increment.
     *
     * @param  string  $value  The value to add
     * @param  int  $increment  Amount to increment by (default: 1)
     */
    public function add(string $value, int $increment = 1): void;

    /**
     * Returns the K most frequent elements.
     *
     * @return TopKResultCollection Collection of top elements with their counts
     */
    public function getTop(): TopKResultCollection;

    /**
     * Adds multiple values in batch for better performance.
     *
     * @param  TopKCollection  $collection  Collection of values to add
     */
    public function addBatch(TopKCollection $collection): void;

    /**
     * Clears all data from the Top-K tracker.
     */
    public function clear(): void;
}
