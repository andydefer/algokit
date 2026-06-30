<?php

namespace AndyDefer\AlgoKIT\Contracts\Algorithms;

use AndyDefer\AlgoKIT\Collections\TrieCollection;
use AndyDefer\AlgoKIT\Collections\TrieResultCollection;

interface TrieInterface
{
    /**
     * Insère un mot dans le trie
     */
    public function insert(string $word): void;

    /**
     * Recherche les mots commençant par un préfixe
     *
     * @return TrieResultCollection Collection des mots trouvés
     */
    public function search(string $prefix, int $limit = 10): TrieResultCollection;

    /**
     * Insère plusieurs mots en batch
     */
    public function insertBatch(TrieCollection $collection): void;

    /**
     * Recherche plusieurs préfixes en batch
     *
     * @return array<string, TrieResultCollection>
     */
    public function searchBatch(TrieCollection $collection, int $limit = 10): array;

    /**
     * Vide le trie
     */
    public function clear(): void;
}
