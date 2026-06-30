<?php

namespace AndyDefer\AlgoKIT\Contracts\Algorithms;

use AndyDefer\AlgoKIT\Collections\CountMinSketchCollection;
use AndyDefer\AlgoKIT\Collections\CountMinSketchResultCollection;

interface CountMinSketchInterface
{
    /**
     * Ajoute une valeur au sketch
     */
    public function add(string $value): void;

    /**
     * Compte la fréquence approximative d'une valeur
     *
     * @return int Le nombre approximatif d'occurrences
     */
    public function count(string $value): int;

    /**
     * Ajoute plusieurs valeurs en batch
     */
    public function addBatch(CountMinSketchCollection $collection): void;

    /**
     * Compte plusieurs valeurs en batch
     *
     * @return CountMinSketchResultCollection Collection des résultats
     */
    public function countBatch(CountMinSketchCollection $collection): CountMinSketchResultCollection;

    /**
     * Vide le sketch
     */
    public function clear(): void;
}
