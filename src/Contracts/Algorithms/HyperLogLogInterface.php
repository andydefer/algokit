<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Contracts\Algorithms;

use AndyDefer\AlgoKIT\Collections\HyperLogLogCollection;
use AndyDefer\AlgoKIT\Collections\HyperLogLogResultCollection;

/**
 * Interface for HyperLogLog cardinality estimator.
 *
 * Estimates the number of distinct elements in a dataset using
 * logarithmic memory with configurable precision.
 */
interface HyperLogLogInterface
{
    /**
     * Adds a value to the HyperLogLog set.
     *
     * @param  string  $value  The value to add
     * @param  string|null  $context  Optional context for data isolation
     */
    public function add(string $value, ?string $context = null): void;

    /**
     * Estimates the number of distinct elements.
     *
     * @param  string|null  $context  Optional context to count
     * @return int Estimated cardinality
     */
    public function count(?string $context = null): int;

    /**
     * Adds multiple values in batch for better performance.
     *
     * @param  HyperLogLogCollection  $collection  Collection of values to add
     */
    public function addBatch(HyperLogLogCollection $collection): void;

    /**
     * Counts distinct elements for multiple contexts in batch.
     *
     * @param  HyperLogLogCollection  $collection  Collection of values with contexts
     * @return HyperLogLogResultCollection Collection of cardinality results
     */
    public function countBatch(HyperLogLogCollection $collection): HyperLogLogResultCollection;

    /**
     * Clears all data from the HyperLogLog instance.
     *
     * @param  string|null  $context  If provided, clears only data for this context
     */
    public function clear(?string $context = null): void;
}
