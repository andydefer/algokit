<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Algorithms;

use AndyDefer\AlgoKIT\Collections\CountMinSketchCollection;
use AndyDefer\AlgoKIT\Collections\CountMinSketchResultCollection;
use AndyDefer\AlgoKIT\Contracts\Algorithms\CountMinSketchInterface;
use AndyDefer\AlgoKIT\Records\CountMinSketchResultRecord;
use AndyDefer\StorageKit\Contracts\Storage\StorageInterface;

/**
 * Count-Min Sketch for frequency estimation in data streams.
 *
 * A probabilistic data structure that estimates the frequency of elements
 * using sub-linear memory. It uses a matrix of counters and multiple hash
 * functions to track frequencies with bounded error.
 *
 * The estimate is always >= the true frequency (never underestimates).
 * Error is bounded by (width / 2) * depth with probability 1 - e^(-depth).
 *
 * @example
 * $cms = new CountMinSketch($storage);
 * $cms->add('php');
 * $cms->add('php');
 * $frequency = $cms->count('php'); // ~2
 *
 * @see https://en.wikipedia.org/wiki/Count%E2%80%93min_sketch
 */
final class CountMinSketch implements CountMinSketchInterface
{
    private const DEFAULT_WIDTH = 10000;

    private const DEFAULT_DEPTH = 5;

    private int $width;

    private int $depth;

    /**
     * @param  StorageInterface  $storage  The storage backend for persistence
     * @param  int  $width  Number of columns per row (higher = more accurate)
     * @param  int  $depth  Number of hash functions / rows (higher = more accurate)
     * @param  string  $key  Unique key for this sketch instance in storage
     */
    public function __construct(
        private StorageInterface $storage,
        int $width = self::DEFAULT_WIDTH,
        int $depth = self::DEFAULT_DEPTH,
        private string $key = 'cms'
    ) {
        $this->width = $width;
        $this->depth = $depth;

        if (! $this->storage->exists($this->key)) {
            $this->storage->set($this->key, []);
        }

        if (! $this->storage->exists($this->key.'_contexts')) {
            $this->storage->set($this->key.'_contexts', []);
        }
    }

    /**
     * Increments the frequency counter for a given value.
     *
     * The value is hashed multiple times and all corresponding counters are incremented.
     *
     * @param  string  $value  The value to count
     * @param  string|null  $context  Optional context for data isolation
     */
    public function add(string $value, ?string $context = null): void
    {
        $table = $this->getTable($context);

        for ($i = 0; $i < $this->depth; $i++) {
            $index = $this->hashValue($value, $i);
            $this->incrementCounter($table, $i, $index);
        }

        $this->saveTable($table, $context);
    }

    /**
     * Estimates the frequency of a given value.
     *
     * Returns the minimum value among all hash function counters,
     * which provides the best estimate.
     *
     * @param  string  $value  The value to count
     * @param  string|null  $context  Optional context for data isolation
     * @return int The estimated frequency (minimum of all hash buckets)
     */
    public function count(string $value, ?string $context = null): int
    {
        $table = $this->getTable($context);
        $minFrequency = PHP_INT_MAX;

        for ($i = 0; $i < $this->depth; $i++) {
            $index = $this->hashValue($value, $i);
            $frequency = $this->getCounterValue($table, $i, $index);
            $minFrequency = min($minFrequency, $frequency);
        }

        return $minFrequency;
    }

    /**
     * Adds multiple values in batch for better performance.
     *
     * @param  CountMinSketchCollection  $collection  Collection of values to add
     */
    public function addBatch(CountMinSketchCollection $collection): void
    {
        $tables = [];

        foreach ($collection as $record) {
            $contextKey = $record->context ?? 'global';

            if (! isset($tables[$contextKey])) {
                $tables[$contextKey] = $this->getTable($record->context);
            }

            $table = $tables[$contextKey];

            for ($i = 0; $i < $this->depth; $i++) {
                $index = $this->hashValue($record->value, $i);
                $this->incrementCounter($table, $i, $index);
            }

            $tables[$contextKey] = $table;
        }

        foreach ($tables as $contextKey => $table) {
            $context = $contextKey !== 'global' ? $contextKey : null;
            $this->saveTable($table, $context);
        }
    }

    /**
     * Estimates frequencies for multiple values in batch.
     *
     * @param  CountMinSketchCollection  $collection  Collection of values to count
     * @return CountMinSketchResultCollection Collection of frequency results
     */
    public function countBatch(CountMinSketchCollection $collection): CountMinSketchResultCollection
    {
        $results = new CountMinSketchResultCollection;
        $cache = [];

        foreach ($collection as $record) {
            $context = $record->context;
            $contextKey = $context ?? 'global';

            if (! isset($cache[$contextKey])) {
                $cache[$contextKey] = $this->getTable($context);
            }

            $table = $cache[$contextKey];
            $minFrequency = PHP_INT_MAX;

            for ($i = 0; $i < $this->depth; $i++) {
                $index = $this->hashValue($record->value, $i);
                $frequency = $this->getCounterValue($table, $i, $index);
                $minFrequency = min($minFrequency, $frequency);
            }

            $results->add(new CountMinSketchResultRecord(
                $record->value,
                $minFrequency,
                $record->context
            ));
        }

        return $results;
    }

    /**
     * Clears all data for this sketch instance.
     *
     * @param  string|null  $context  If provided, clears only data for this context
     */
    public function clear(?string $context = null): void
    {
        if ($context !== null) {
            $this->storage->delete($this->key.'_'.$context);
            $this->removeContextFromList($context);

            return;
        }

        $this->storage->delete($this->key);

        $contexts = $this->getContextList();
        foreach ($contexts as $contextName) {
            $this->storage->delete($this->key.'_'.$contextName);
        }

        $this->storage->delete($this->key.'_contexts');
    }

    /**
     * Returns the list of tracked contexts.
     *
     * @return array<int, string> List of context names
     */
    private function getContextList(): array
    {
        return $this->storage->get($this->key.'_contexts', []);
    }

    /**
     * Adds a context to the tracked contexts list.
     *
     * @param  string  $context  Context name to track
     */
    private function addContextToList(string $context): void
    {
        $contexts = $this->getContextList();
        if (! in_array($context, $contexts, true)) {
            $contexts[] = $context;
            $this->storage->set($this->key.'_contexts', $contexts);
        }
    }

    /**
     * Removes a context from the tracked contexts list.
     *
     * @param  string  $context  Context name to remove
     */
    private function removeContextFromList(string $context): void
    {
        $contexts = $this->getContextList();
        $contexts = array_filter($contexts, fn ($c) => $c !== $context);
        $this->storage->set($this->key.'_contexts', array_values($contexts));
    }

    /**
     * Retrieves the counter table for a given context.
     *
     * @param  string|null  $context  Optional context for data isolation
     * @return array<int, array<int, int>> The counter table (depth x width)
     */
    private function getTable(?string $context = null): array
    {
        $contextKey = $context !== null ? $this->key.'_'.$context : $this->key;

        if (! $this->storage->exists($contextKey)) {
            $table = $this->createEmptyTable();
            $this->storage->set($contextKey, $table);

            if ($context !== null) {
                $this->addContextToList($context);
            }

            return $table;
        }

        $table = $this->storage->get($contextKey);

        if ($table === null) {
            $table = $this->createEmptyTable();
            $this->storage->set($contextKey, $table);

            if ($context !== null) {
                $this->addContextToList($context);
            }
        }

        return $this->ensureTableDimensions($table);
    }

    /**
     * Saves the counter table for a given context.
     *
     * @param  array<int, array<int, int>>  $table  The counter table to save
     * @param  string|null  $context  Optional context for data isolation
     */
    private function saveTable(array $table, ?string $context = null): void
    {
        $contextKey = $context !== null ? $this->key.'_'.$context : $this->key;
        $this->storage->set($contextKey, $table);
    }

    /**
     * Creates an empty counter table.
     *
     * @return array<int, array<int, int>> Empty table (depth x width)
     */
    private function createEmptyTable(): array
    {
        return array_fill(0, $this->depth, array_fill(0, $this->width, 0));
    }

    /**
     * Ensures the table has the correct dimensions.
     *
     * @param  array<int, array<int, int>>  $table  The table to validate
     * @return array<int, array<int, int>> Validated table with correct dimensions
     */
    private function ensureTableDimensions(array $table): array
    {
        foreach ($table as $rowIndex => $row) {
            if (count($row) < $this->width) {
                $table[$rowIndex] = array_pad($row, $this->width, 0);
            }
        }

        while (count($table) < $this->depth) {
            $table[] = array_fill(0, $this->width, 0);
        }

        return $table;
    }

    /**
     * Hashes a value with a given seed.
     *
     * @param  string  $value  The value to hash
     * @param  int  $seed  The hash seed
     * @return int The hash index within the table width
     */
    private function hashValue(string $value, int $seed): int
    {
        return abs(crc32($seed.$value)) % $this->width;
    }

    /**
     * Increments a counter in the table.
     *
     * @param  array<int, array<int, int>>  $table  The counter table
     * @param  int  $row  The row index
     * @param  int  $column  The column index
     */
    private function incrementCounter(array &$table, int $row, int $column): void
    {
        if (! isset($table[$row][$column])) {
            $table[$row][$column] = 0;
        }

        $table[$row][$column]++;
    }

    /**
     * Gets a counter value from the table.
     *
     * @param  array<int, array<int, int>>  $table  The counter table
     * @param  int  $row  The row index
     * @param  int  $column  The column index
     * @return int The counter value (0 if not set)
     */
    private function getCounterValue(array $table, int $row, int $column): int
    {
        return isset($table[$row][$column]) ? (int) $table[$row][$column] : 0;
    }
}
