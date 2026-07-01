<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Algorithms;

use AndyDefer\AlgoKIT\Collections\BloomFilterCollection;
use AndyDefer\AlgoKIT\Collections\BloomFilterResultCollection;
use AndyDefer\AlgoKIT\Contracts\Algorithms\BloomFilterInterface;
use AndyDefer\AlgoKIT\Records\BloomFilterResultRecord;
use AndyDefer\StorageKit\Contracts\Storage\StorageInterface;

/**
 * Probabilistic membership testing using a Bloom Filter.
 *
 * A Bloom Filter is a space-efficient probabilistic data structure that tests
 * whether an element is a member of a set. It can have false positives but
 * never false negatives.
 *
 * The filter uses multiple hash functions and a bit array. Inserting a value
 * sets multiple bits to 1. Testing a value checks if all corresponding bits
 * are set. If any bit is 0, the value is definitely not in the set.
 *
 * @example
 * $bloom = new BloomFilter($storage);
 * $bloom->insert('https://example.com');
 * $exists = $bloom->exists('https://example.com'); // true
 *
 * @see https://en.wikipedia.org/wiki/Bloom_filter
 */
final class BloomFilter implements BloomFilterInterface
{
    private const DEFAULT_SIZE = 10000;

    private const DEFAULT_HASH_COUNT = 3;

    private int $size;

    private int $hashCount;

    /**
     * @param  StorageInterface  $storage  The storage backend for persistence
     * @param  int  $size  Number of bits in the filter (higher = lower false positives)
     * @param  int  $hashCount  Number of hash functions (higher = lower false positives)
     * @param  string  $key  Unique key for this filter instance in storage
     */
    public function __construct(
        private StorageInterface $storage,
        int $size = self::DEFAULT_SIZE,
        int $hashCount = self::DEFAULT_HASH_COUNT,
        private string $key = 'bloom'
    ) {
        $this->size = $size;
        $this->hashCount = $hashCount;

        if (! $this->storage->exists($this->key)) {
            $this->storage->set($this->key, []);
        }
    }

    /**
     * Inserts a value into the bloom filter.
     *
     * The value is hashed multiple times and the corresponding bits are set to 1.
     *
     * @param  string  $value  The value to insert
     * @param  string|null  $context  Optional context for data isolation
     */
    public function insert(string $value, ?string $context = null): void
    {
        $bits = $this->getBits($context);

        for ($i = 0; $i < $this->hashCount; $i++) {
            $index = $this->hashValue($value, $i);
            $bits[$index] = 1;
        }

        $this->saveBits($bits, $context);
    }

    /**
     * Tests if a value probably exists in the bloom filter.
     *
     * Returns true if all corresponding bits are set (value probably exists).
     * Returns false if any bit is 0 (value definitely does not exist).
     *
     * @param  string  $value  The value to check
     * @param  string|null  $context  Optional context for data isolation
     * @return bool True if probably exists, false if definitely doesn't
     */
    public function exists(string $value, ?string $context = null): bool
    {
        $bits = $this->getBits($context);

        for ($i = 0; $i < $this->hashCount; $i++) {
            $index = $this->hashValue($value, $i);
            if ($bits[$index] === 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Inserts multiple values in batch for better performance.
     *
     * @param  BloomFilterCollection  $collection  Collection of values to insert
     */
    public function insertBatch(BloomFilterCollection $collection): void
    {
        $bitsByContext = [];

        foreach ($collection as $record) {
            $contextKey = $record->context ?? 'global';

            if (! isset($bitsByContext[$contextKey])) {
                $bitsByContext[$contextKey] = $this->getBits($record->context);
            }

            $bits = $bitsByContext[$contextKey];

            for ($i = 0; $i < $this->hashCount; $i++) {
                $index = $this->hashValue($record->value, $i);
                $bits[$index] = 1;
            }

            $bitsByContext[$contextKey] = $bits;
            $this->saveBits($bits, $record->context);
        }

        foreach ($bitsByContext as $contextKey => $bits) {
            $context = $contextKey !== 'global' ? $contextKey : null;
            $this->saveBits($bits, $context);
        }
    }

    /**
     * Tests multiple values for membership in batch.
     *
     * @param  BloomFilterCollection  $collection  Collection of values to check
     * @return BloomFilterResultCollection Collection of membership results
     */
    public function existsBatch(BloomFilterCollection $collection): BloomFilterResultCollection
    {
        $results = new BloomFilterResultCollection;
        $cache = [];

        foreach ($collection as $record) {
            $contextKey = $record->context ?? 'global';

            if (! isset($cache[$contextKey])) {
                $cache[$contextKey] = $this->getBits($record->context);
            }

            $bits = $cache[$contextKey];
            $exists = true;

            for ($i = 0; $i < $this->hashCount; $i++) {
                $index = $this->hashValue($record->value, $i);
                if ($bits[$index] === 0) {
                    $exists = false;
                    break;
                }
            }

            $results->add(new BloomFilterResultRecord($record->value, $exists, $record->context));
        }

        return $results;
    }

    /**
     * Clears all data from the bloom filter.
     *
     * @param  string|null  $context  If provided, clears only data for this context
     */
    public function clear(?string $context = null): void
    {
        if ($context !== null) {
            $this->storage->delete($this->key.'_'.$context);
        } else {
            $this->storage->delete($this->key);
        }
    }

    /**
     * Retrieves the bit array for a given context.
     *
     * @param  string|null  $context  Optional context for data isolation
     * @return array<int, int> The bit array
     */
    private function getBits(?string $context = null): array
    {
        $contextKey = $context !== null ? $this->key.'_'.$context : $this->key;

        if (! $this->storage->exists($contextKey)) {
            $bits = array_fill(0, $this->size, 0);
            $this->storage->set($contextKey, $bits);

            return $bits;
        }

        $bits = $this->storage->get($contextKey);

        if (! is_array($bits) || count($bits) !== $this->size) {
            $bits = array_fill(0, $this->size, 0);
            $this->storage->set($contextKey, $bits);
        }

        return $bits;
    }

    /**
     * Saves the bit array for a given context.
     *
     * @param  array<int, int>  $bits  The bit array to save
     * @param  string|null  $context  Optional context for data isolation
     */
    private function saveBits(array $bits, ?string $context = null): void
    {
        $contextKey = $context !== null ? $this->key.'_'.$context : $this->key;
        $this->storage->set($contextKey, $bits);
    }

    /**
     * Hashes a value with a given seed.
     *
     * @param  string  $value  The value to hash
     * @param  int  $seed  The hash seed
     * @return int The hash index within the bit array
     */
    private function hashValue(string $value, int $seed): int
    {
        return abs(crc32($seed.$value)) % $this->size;
    }
}
