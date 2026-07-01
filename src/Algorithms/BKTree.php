<?php

namespace AndyDefer\AlgoKIT\Algorithms;

use AndyDefer\AlgoKIT\Collections\BKTreeNodeCollection;
use AndyDefer\AlgoKIT\Collections\BKTreeResultCollection;
use AndyDefer\AlgoKIT\Contracts\Algorithms\TreeInterface;
use AndyDefer\AlgoKIT\Records\BKTreeNodeRecord;
use AndyDefer\AlgoKIT\Records\BKTreeResultRecord;
use AndyDefer\StorageKit\Contracts\Storage\StorageInterface;

class BKTree implements TreeInterface
{
    public function __construct(
        private StorageInterface $storage,
        private string $key = 'bktree'
    ) {
        if (! $this->storage->exists($this->key)) {
            $this->storage->set($this->key, null);
        }
    }

    private function getRoot(): ?BKTreeNodeRecord
    {
        return $this->storage->get($this->key);
    }

    private function saveRoot(?BKTreeNodeRecord $root): void
    {
        $this->storage->set($this->key, $root);
    }

    public function insert(string $word): void
    {
        $root = $this->getRoot();

        if ($root === null) {
            $this->saveRoot(new BKTreeNodeRecord($word, new BKTreeNodeCollection));

            return;
        }

        $this->insertNode($root, $word);
        $this->saveRoot($root);
    }

    private function insertNode(BKTreeNodeRecord $node, string $word): void
    {
        $distance = $this->levenshtein($node->word, $word);

        if ($distance === 0) {
            return;
        }

        // Vérifier si un enfant existe déjà pour ce mot
        $existingChild = $node->children->find(fn (BKTreeNodeRecord $child) => $child->word === $word);
        if ($existingChild !== null) {
            return;
        }

        // Vérifier si un enfant existe à cette distance
        $childAtDistance = null;
        foreach ($node->children as $child) {
            $childDistance = $this->levenshtein($node->word, $child->word);
            if ($childDistance === $distance) {
                $childAtDistance = $child;
                break;
            }
        }

        if ($childAtDistance !== null) {
            $this->insertNode($childAtDistance, $word);
        } else {
            // Ajouter un nouvel enfant
            $newNode = new BKTreeNodeRecord($word, new BKTreeNodeCollection);
            $node->children->add($newNode);
        }
    }

    public function search(string $word, int $tolerance = 2, int $limit = 10): BKTreeResultCollection
    {
        $root = $this->getRoot();
        $results = new BKTreeResultCollection;

        if ($root === null) {
            return $results;
        }

        $this->searchNode($root, $word, $tolerance, $results);

        // Trier les résultats par distance
        $items = $results->toArray();
        usort($items, fn (BKTreeResultRecord $a, BKTreeResultRecord $b) => $a->distance <=> $b->distance);
        $items = array_slice($items, 0, $limit);

        return BKTreeResultCollection::collect($items);
    }

    private function searchNode(BKTreeNodeRecord $node, string $word, int $tolerance, BKTreeResultCollection $results): void
    {
        $distance = $this->levenshtein($node->word, $word);

        if ($distance <= $tolerance) {
            $results->add(new BKTreeResultRecord($node->word, $distance));
        }

        $min = $distance - $tolerance;
        $max = $distance + $tolerance;

        foreach ($node->children as $child) {
            $childDistance = $this->levenshtein($node->word, $child->word);
            if ($childDistance >= $min && $childDistance <= $max) {
                $this->searchNode($child, $word, $tolerance, $results);
            }
        }
    }

    private function levenshtein(string $a, string $b): int
    {
        return \levenshtein($a, $b);
    }

    public function clear(): void
    {
        $this->storage->delete($this->key);
    }
}
