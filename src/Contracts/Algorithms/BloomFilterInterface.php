<?php

namespace AndyDefer\AlgoKIT\Contracts\Algorithms;

use AndyDefer\AlgoKIT\Collections\BloomFilterCollection;
use AndyDefer\AlgoKIT\Collections\BloomFilterResultCollection;

interface BloomFilterInterface
{
    /**
     * Insère une valeur dans le filtre
     */
    public function insert(string $value): void;

    /**
     * Vérifie si une valeur existe probablement dans le filtre
     *
     * @return bool True si la valeur existe probablement, false si elle n'existe pas
     */
    public function exists(string $value): bool;

    /**
     * Insère plusieurs valeurs en batch
     */
    public function insertBatch(BloomFilterCollection $collection): void;

    /**
     * Vérifie plusieurs valeurs en batch
     *
     * @return BloomFilterResultCollection Collection des résultats
     */
    public function existsBatch(BloomFilterCollection $collection): BloomFilterResultCollection;

    /**
     * Vide le filtre
     */
    public function clear(): void;
}
