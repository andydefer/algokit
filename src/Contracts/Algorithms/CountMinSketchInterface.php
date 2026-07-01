<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Contracts\Algorithms;

use AndyDefer\AlgoKIT\Collections\CountMinSketchCollection;
use AndyDefer\AlgoKIT\Collections\CountMinSketchResultCollection;

/**
 * Interface for Count-Min Sketch probabilistic frequency counter.
 *
 * Count-Min Sketch estimates the frequency of elements in a data stream
 * using sub-linear memory. It supports context isolation for different
 * data categories.
 */
interface CountMinSketchInterface
{
    /**
     * Increments the frequency counter for a given value.
     *
     * @param  string  $value  The value to count
     * @param  string|null  $context  Optional context for data isolation
     */
    public function add(string $value, ?string $context = null): void;

    /**
     * Estimates the frequency of a given value.
     *
     * @param  string  $value  The value to count
     * @param  string|null  $context  Optional context for data isolation
     * @return int The estimated frequency (minimum of all hash buckets)
     */
    public function count(string $value, ?string $context = null): int;

    /**
     * Adds multiple values in batch for better performance.
     *
     * @param  CountMinSketchCollection  $collection  Collection of values to add
     */
    public function addBatch(CountMinSketchCollection $collection): void;

    /**
     * Estimates frequencies for multiple values in batch.
     *
     * @param  CountMinSketchCollection  $collection  Collection of values to count
     * @return CountMinSketchResultCollection Collection of frequency results
     */
    public function countBatch(CountMinSketchCollection $collection): CountMinSketchResultCollection;

    /**
     * Clears all data for this sketch instance.
     *
     * @param  string|null  $context  If provided, clears only data for this context
     */
    public function clear(?string $context = null): void;
}
