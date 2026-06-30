<?php

namespace AndyDefer\AlgoKIT\Algorithms;

use AndyDefer\AlgoKIT\Collections\HyperLogLogCollection;
use AndyDefer\AlgoKIT\Collections\HyperLogLogResultCollection;
use AndyDefer\AlgoKIT\Contracts\Algorithms\HyperLogLogInterface;
use AndyDefer\AlgoKIT\Records\HyperLogLogResultRecord;
use AndyDefer\AlgoKIT\Storage\StorageInterface;

class HyperLogLog implements HyperLogLogInterface
{
    private int $m;

    private int $p;

    public function __construct(
        private StorageInterface $storage,
        int $precision = 16,
        private string $key = 'hll'
    ) {
        $this->p = $precision;
        $this->m = 1 << $precision;

        if (! $this->storage->exists($this->key)) {
            $this->storage->set($this->key, array_fill(0, $this->m, 0));
        }
    }

    private function &getRegisters(): array
    {
        $registers = $this->storage->get($this->key, array_fill(0, $this->m, 0));

        return $registers;
    }

    private function saveRegisters(array $registers): void
    {
        $this->storage->set($this->key, $registers);
    }

    public function add(string $value): void
    {
        $registers = $this->getRegisters();

        $hash = $this->hash($value);
        $index = $hash & ($this->m - 1);
        $w = $hash >> $this->p;

        $rank = $this->leadingZeros($w) + 1;

        if ($rank > $registers[$index]) {
            $registers[$index] = $rank;
            $this->saveRegisters($registers);
        }
    }

    public function count(): int
    {
        $registers = $this->getRegisters();

        $sum = 0;
        foreach ($registers as $register) {
            $sum += pow(2, -$register);
        }

        $estimate = (0.7213 / (1 + 1.079 / $this->m)) * $this->m * $this->m / $sum;

        if ($estimate <= 2.5 * $this->m) {
            $zeros = 0;
            foreach ($registers as $register) {
                if ($register === 0) {
                    $zeros++;
                }
            }
            if ($zeros > 0) {
                $estimate = $this->m * log($this->m / $zeros);
            }
        }

        return (int) $estimate;
    }

    public function addBatch(HyperLogLogCollection $collection): void
    {
        $registers = $this->getRegisters();
        $dirty = false;

        foreach ($collection as $record) {
            $hash = $this->hash($record->value);
            $index = $hash & ($this->m - 1);
            $w = $hash >> $this->p;

            $rank = $this->leadingZeros($w) + 1;

            if ($rank > $registers[$index]) {
                $registers[$index] = $rank;
                $dirty = true;
            }
        }

        if ($dirty) {
            $this->saveRegisters($registers);
        }
    }

    public function countBatch(HyperLogLogCollection $collection): HyperLogLogResultCollection
    {
        $results = new HyperLogLogResultCollection;

        // Pour chaque élément, on estime le nombre d'éléments uniques
        // Note: HyperLogLog ne supporte pas nativement le batch counting
        // On retourne le count global pour chaque contexte
        $globalCount = $this->count();

        foreach ($collection as $record) {
            $results->add(new HyperLogLogResultRecord(
                count: $globalCount,
                context: $record->context
            ));
        }

        return $results;
    }

    private function hash(string $value): int
    {
        return abs(crc32($value));
    }

    private function leadingZeros(int $value): int
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

    public function clear(): void
    {
        $this->storage->delete($this->key);
    }
}
