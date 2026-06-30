<?php

namespace AndyDefer\AlgoKIT\Algorithms;

use AndyDefer\AlgoKIT\Collections\CountMinSketchCollection;
use AndyDefer\AlgoKIT\Collections\CountMinSketchResultCollection;
use AndyDefer\AlgoKIT\Contracts\Algorithms\CountMinSketchInterface;
use AndyDefer\AlgoKIT\Records\CountMinSketchResultRecord;
use AndyDefer\AlgoKIT\Storage\StorageInterface;

class CountMinSketch implements CountMinSketchInterface
{
    private int $width;

    private int $depth;

    public function __construct(
        private StorageInterface $storage,
        int $width = 10000,
        int $depth = 5,
        private string $key = 'cms'
    ) {
        $this->width = $width;
        $this->depth = $depth;

        if (! $this->storage->exists($this->key)) {
            $this->storage->set($this->key, array_fill(0, $depth, array_fill(0, $width, 0)));
        }
    }

    private function &getTable(): array
    {
        $table = $this->storage->get($this->key, array_fill(0, $this->depth, array_fill(0, $this->width, 0)));

        return $table;
    }

    private function saveTable(array $table): void
    {
        $this->storage->set($this->key, $table);
    }

    public function add(string $value): void
    {
        $table = $this->getTable();

        for ($i = 0; $i < $this->depth; $i++) {
            $index = $this->hash($value, $i);
            $table[$i][$index]++;
        }

        $this->saveTable($table);
    }

    public function count(string $value): int
    {
        $table = $this->getTable();
        $min = PHP_INT_MAX;

        for ($i = 0; $i < $this->depth; $i++) {
            $index = $this->hash($value, $i);
            $min = min($min, $table[$i][$index]);
        }

        return $min;
    }

    public function addBatch(CountMinSketchCollection $collection): void
    {
        $table = $this->getTable();

        foreach ($collection as $record) {
            for ($i = 0; $i < $this->depth; $i++) {
                $index = $this->hash($record->value, $i);
                $table[$i][$index]++;
            }
        }

        $this->saveTable($table);
    }

    public function countBatch(CountMinSketchCollection $collection): CountMinSketchResultCollection
    {
        $table = $this->getTable();
        $results = new CountMinSketchResultCollection;

        foreach ($collection as $record) {
            $min = PHP_INT_MAX;
            for ($i = 0; $i < $this->depth; $i++) {
                $index = $this->hash($record->value, $i);
                $min = min($min, $table[$i][$index]);
            }
            $results->add(new CountMinSketchResultRecord($record->value, $min));
        }

        return $results;
    }

    private function hash(string $value, int $seed): int
    {
        return abs(crc32($seed.$value)) % $this->width;
    }

    public function clear(): void
    {
        $this->storage->delete($this->key);
    }
}
