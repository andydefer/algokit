<?php

namespace AndyDefer\AlgoKIT\Algorithms;

use AndyDefer\AlgoKIT\Collections\TopKCollection;
use AndyDefer\AlgoKIT\Collections\TopKResultCollection;
use AndyDefer\AlgoKIT\Contracts\Algorithms\TopKInterface;
use AndyDefer\AlgoKIT\Records\TopKResultRecord;
use AndyDefer\AlgoKIT\Storage\StorageInterface;

class TopK implements TopKInterface
{
    private int $k;

    public function __construct(
        private StorageInterface $storage,
        int $k = 10,
        private string $key = 'topk'
    ) {
        $this->k = $k;

        if (! $this->storage->exists($this->key)) {
            $this->storage->set($this->key, ['items' => [], 'counts' => []]);
        }
    }

    private function &getData(): array
    {
        $data = $this->storage->get($this->key, ['items' => [], 'counts' => []]);

        return $data;
    }

    private function saveData(array $data): void
    {
        $this->storage->set($this->key, $data);
    }

    public function add(string $value, int $increment = 1): void
    {
        $data = $this->getData();
        $items = &$data['items'];
        $counts = &$data['counts'];

        if (! isset($counts[$value])) {
            $counts[$value] = 0;
        }

        $counts[$value] += $increment;

        if (! in_array($value, $items)) {
            if (count($items) < $this->k) {
                $items[] = $value;
            } else {
                $minItem = $items[0];
                $minCount = $counts[$minItem];

                foreach ($items as $item) {
                    if ($counts[$item] < $minCount) {
                        $minCount = $counts[$item];
                        $minItem = $item;
                    }
                }

                if ($counts[$value] > $minCount) {
                    $key = array_search($minItem, $items);
                    $items[$key] = $value;
                }
            }
        }

        usort($items, function ($a, $b) use ($counts) {
            return $counts[$b] <=> $counts[$a];
        });

        $this->saveData($data);
    }

    public function getTop(): TopKResultCollection
    {
        $data = $this->getData();
        $results = new TopKResultCollection;

        foreach ($data['items'] as $item) {
            $results->add(new TopKResultRecord($item, $data['counts'][$item]));
        }

        return $results;
    }

    public function addBatch(TopKCollection $collection): void
    {
        foreach ($collection as $record) {
            $this->add($record->value, $record->increment);
        }
    }

    public function clear(): void
    {
        $this->storage->delete($this->key);
    }
}
