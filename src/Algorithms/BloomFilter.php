<?php

namespace AndyDefer\AlgoKIT\Algorithms;

use AndyDefer\AlgoKIT\Collections\BloomFilterCollection;
use AndyDefer\AlgoKIT\Collections\BloomFilterResultCollection;
use AndyDefer\AlgoKIT\Contracts\Algorithms\BloomFilterInterface;
use AndyDefer\AlgoKIT\Records\BloomFilterResultRecord;
use AndyDefer\AlgoKIT\Storage\StorageInterface;

class BloomFilter implements BloomFilterInterface
{
    private int $size;

    private int $hashCount;

    public function __construct(
        private StorageInterface $storage,
        int $size = 10000,
        int $hashCount = 3,
        private string $key = 'bloom'
    ) {
        $this->size = $size;
        $this->hashCount = $hashCount;

        if (! $this->storage->exists($this->key)) {
            $this->storage->set($this->key, array_fill(0, $size, 0));
        }
    }

    private function &getBits(): array
    {
        $bits = $this->storage->get($this->key, array_fill(0, $this->size, 0));

        return $bits;
    }

    private function saveBits(array $bits): void
    {
        $this->storage->set($this->key, $bits);
    }

    public function insert(string $value): void
    {
        $bits = $this->getBits();

        for ($i = 0; $i < $this->hashCount; $i++) {
            $index = $this->hash($value, $i);
            $bits[$index] = 1;
        }

        $this->saveBits($bits);
    }

    public function exists(string $value): bool
    {
        $bits = $this->getBits();

        for ($i = 0; $i < $this->hashCount; $i++) {
            $index = $this->hash($value, $i);
            if ($bits[$index] === 0) {
                return false;
            }
        }

        return true;
    }

    public function insertBatch(BloomFilterCollection $collection): void
    {
        $bits = $this->getBits();

        foreach ($collection as $record) {
            for ($i = 0; $i < $this->hashCount; $i++) {
                $index = $this->hash($record->value, $i);
                $bits[$index] = 1;
            }
        }

        $this->saveBits($bits);
    }

    public function existsBatch(BloomFilterCollection $collection): BloomFilterResultCollection
    {
        $bits = $this->getBits();
        $results = new BloomFilterResultCollection;

        foreach ($collection as $record) {
            $exists = true;
            for ($i = 0; $i < $this->hashCount; $i++) {
                $index = $this->hash($record->value, $i);
                if ($bits[$index] === 0) {
                    $exists = false;
                    break;
                }
            }
            $results->add(new BloomFilterResultRecord($record->value, $exists));
        }

        return $results;
    }

    private function hash(string $value, int $seed): int
    {
        return abs(crc32($seed.$value)) % $this->size;
    }

    public function clear(): void
    {
        $this->storage->delete($this->key);
    }
}
