<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Algorithms;

use AndyDefer\AlgoKIT\Collections\HyperLogLogCollection;
use AndyDefer\AlgoKIT\Collections\HyperLogLogResultCollection;
use AndyDefer\AlgoKIT\Contracts\Algorithms\HyperLogLogInterface;
use AndyDefer\AlgoKIT\Records\HyperLogLogResultRecord;
use AndyDefer\StorageKit\Contracts\Storage\StorageInterface;

/**
 * HyperLogLog cardinality estimator.
 *
 * Estimates the number of distinct elements in a dataset using logarithmic memory.
 * The algorithm uses a probabilistic approach with configurable precision.
 * Higher precision = more accurate results but uses more memory.
 *
 * @example
 * $hll = new HyperLogLog($storage);
 * $hll->add('user_123');
 * $hll->add('user_456');
 * $unique = $hll->count(); // Approximate number of unique users
 *
 * @see https://en.wikipedia.org/wiki/HyperLogLog
 */
final class HyperLogLog implements HyperLogLogInterface
{
    /** Default precision (2^16 = 65536 registers) */
    private const DEFAULT_PRECISION = 16;

    /** Alpha constant for bias correction */
    private const ALPHA_CONSTANT = 0.7213;

    /** Alpha denominator for bias correction */
    private const ALPHA_DENOMINATOR = 1.079;

    /** Threshold for small set correction (2.5 * m) */
    private const SMALL_SET_THRESHOLD = 2.5;

    /** @var int Number of registers (2^precision) */
    private int $registerCount;

    /** @var int Precision bits (higher = more accurate) */
    private int $precision;

    /**
     * @param  StorageInterface  $storage  The storage backend for persistence
     * @param  int  $precision  Number of bits for register indexing (4-16, higher = more accurate)
     * @param  string  $key  Unique key for this HyperLogLog instance in storage
     */
    public function __construct(
        private StorageInterface $storage,
        int $precision = self::DEFAULT_PRECISION,
        private string $key = 'hll'
    ) {
        $this->precision = $precision;
        $this->registerCount = 1 << $precision;

        if (! $this->storage->exists($this->key)) {
            $this->storage->set($this->key, []);
        }
    }

    /**
     * Adds a value to the HyperLogLog set.
     *
     * The value is hashed and the corresponding register is updated
     * with the maximum rank observed.
     *
     * @param  string  $value  The value to add
     * @param  string|null  $context  Optional context for data isolation
     */
    public function add(string $value, ?string $context = null): void
    {
        $registers = $this->getRegisters($context);

        $hash = $this->hashValue($value);
        $registerIndex = $this->selectRegisterIndex($hash);
        $hashRemainder = $this->extractHashRemainder($hash);

        $rank = $this->calculateLeadingZeros($hashRemainder) + 1;

        if ($rank > $registers[$registerIndex]) {
            $registers[$registerIndex] = $rank;
            $this->saveRegisters($registers, $context);
        }
    }

    /**
     * Estimates the number of distinct elements.
     *
     * @param  string|null  $context  Optional context to count
     * @return int Estimated cardinality
     */
    public function count(?string $context = null): int
    {
        if ($context !== null) {
            $registers = $this->getRegisters($context);

            return $this->estimateCardinality($registers);
        }

        $total = 0;

        $globalRegisters = $this->getRegisters(null);
        $total += $this->estimateCardinality($globalRegisters);

        foreach ($this->getContextList() as $contextName) {
            $total += $this->count($contextName);
        }

        return $total;
    }

    /**
     * Adds multiple values in batch for better performance.
     *
     * @param  HyperLogLogCollection  $collection  Collection of values to add
     */
    public function addBatch(HyperLogLogCollection $collection): void
    {
        $registersByContext = [];
        $hasPendingChanges = [];

        foreach ($collection as $record) {
            $contextKey = $record->context ?? 'global';

            if (! isset($registersByContext[$contextKey])) {
                $registersByContext[$contextKey] = $this->getRegisters($record->context);
                $hasPendingChanges[$contextKey] = false;
            }

            $registers = &$registersByContext[$contextKey];

            $hash = $this->hashValue($record->value);
            $registerIndex = $this->selectRegisterIndex($hash);
            $hashRemainder = $this->extractHashRemainder($hash);

            $rank = $this->calculateLeadingZeros($hashRemainder) + 1;

            if ($rank > $registers[$registerIndex]) {
                $registers[$registerIndex] = $rank;
                $hasPendingChanges[$contextKey] = true;
            }
        }

        foreach ($registersByContext as $contextKey => $registers) {
            if ($hasPendingChanges[$contextKey]) {
                $context = $contextKey !== 'global' ? $contextKey : null;
                $this->saveRegisters($registers, $context);
            }
        }
    }

    /**
     * Counts distinct elements for multiple contexts in batch.
     *
     * @param  HyperLogLogCollection  $collection  Collection of values with contexts
     * @return HyperLogLogResultCollection Collection of cardinality results
     */
    public function countBatch(HyperLogLogCollection $collection): HyperLogLogResultCollection
    {
        $results = new HyperLogLogResultCollection;

        foreach ($collection as $record) {
            $count = $this->count($record->context);
            $results->add(new HyperLogLogResultRecord($count, $record->context));
        }

        return $results;
    }

    /**
     * Clears all data from the HyperLogLog instance.
     *
     * @param  string|null  $context  If provided, clears only data for this context
     */
    public function clear(?string $context = null): void
    {
        if ($context !== null) {
            $contextKey = $this->key.'_'.$context;
            $this->storage->delete($contextKey);
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
     * Retrieves registers for a given context.
     *
     * @param  string|null  $context  Optional context for data isolation
     * @return array<int, int> Register values
     */
    private function getRegisters(?string $context = null): array
    {
        $contextKey = $context !== null ? $this->key.'_'.$context : $this->key;

        if (! $this->storage->exists($contextKey)) {
            $registers = array_fill(0, $this->registerCount, 0);
            $this->storage->set($contextKey, $registers);

            if ($context !== null) {
                $this->addContextToList($context);
            }

            return $registers;
        }

        $registers = $this->storage->get($contextKey);

        if (! is_array($registers) || count($registers) !== $this->registerCount) {
            $registers = array_fill(0, $this->registerCount, 0);
            $this->storage->set($contextKey, $registers);
        }

        return $registers;
    }

    /**
     * Saves registers for a given context.
     *
     * @param  array<int, int>  $registers  Register values
     * @param  string|null  $context  Optional context for data isolation
     */
    private function saveRegisters(array $registers, ?string $context = null): void
    {
        $contextKey = $context !== null ? $this->key.'_'.$context : $this->key;
        $this->storage->set($contextKey, $registers);
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
     * Estimates cardinality from register values.
     *
     * @param  array<int, int>  $registers  Register values
     * @return int Estimated cardinality
     */
    private function estimateCardinality(array $registers): int
    {
        if (empty($registers) || ! is_array($registers) || count($registers) === 0) {
            return 0;
        }

        $harmonicSum = $this->calculateHarmonicSum($registers);

        if ($harmonicSum == 0) {
            return 0;
        }

        $alpha = self::ALPHA_CONSTANT / (1 + self::ALPHA_DENOMINATOR / $this->registerCount);
        $estimate = $alpha * $this->registerCount * $this->registerCount / $harmonicSum;

        if ($estimate <= self::SMALL_SET_THRESHOLD * $this->registerCount) {
            $estimate = $this->applySmallSetCorrection($registers, $estimate);
        }

        return (int) $estimate;
    }

    /**
     * Calculates the harmonic sum of register values.
     *
     * @param  array<int, int>  $registers  Register values
     * @return float Harmonic sum
     */
    private function calculateHarmonicSum(array $registers): float
    {
        $sum = 0;
        foreach ($registers as $register) {
            $sum += pow(2, -$register);
        }

        return $sum;
    }

    /**
     * Applies small set correction for datasets with few distinct elements.
     *
     * @param  array<int, int>  $registers  Register values
     * @param  float  $estimate  Current estimate
     * @return float Corrected estimate
     */
    private function applySmallSetCorrection(array $registers, float $estimate): float
    {
        $zeroCount = 0;
        foreach ($registers as $register) {
            if ($register === 0) {
                $zeroCount++;
            }
        }

        if ($zeroCount > 0) {
            $estimate = $this->registerCount * log($this->registerCount / $zeroCount);
        }

        return $estimate;
    }

    /**
     * Hashes a value using CRC32.
     *
     * @param  string  $value  The value to hash
     * @return int Hash value
     */
    private function hashValue(string $value): int
    {
        return abs(crc32($value));
    }

    /**
     * Selects the register index from the hash.
     *
     * @param  int  $hash  Hash value
     * @return int Register index
     */
    private function selectRegisterIndex(int $hash): int
    {
        return $hash & ($this->registerCount - 1);
    }

    /**
     * Extracts the hash remainder for rank calculation.
     *
     * @param  int  $hash  Hash value
     * @return int Hash remainder
     */
    private function extractHashRemainder(int $hash): int
    {
        return $hash >> $this->precision;
    }

    /**
     * Calculates the number of leading zeros in a value.
     *
     * @param  int  $value  The value to analyze
     * @return int Number of leading zeros
     */
    private function calculateLeadingZeros(int $value): int
    {
        if ($value === 0) {
            return 32;
        }

        $zeros = 0;
        while (($value & 1) === 0) {
            $zeros++;
            $value >>= 1;
        }

        return $zeros;
    }
}
