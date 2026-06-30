<?php

namespace AndyDefer\AlgoKIT\Contracts\Algorithms;

use AndyDefer\AlgoKIT\Collections\TopKCollection;
use AndyDefer\AlgoKIT\Collections\TopKResultCollection;

interface TopKInterface
{
    /**
     * Ajoute une valeur avec un incrément
     */
    public function add(string $value, int $increment = 1): void;

    /**
     * Récupère les K éléments les plus fréquents
     *
     * @return TopKResultCollection Collection des résultats
     */
    public function getTop(): TopKResultCollection;

    /**
     * Ajoute plusieurs valeurs en batch
     */
    public function addBatch(TopKCollection $collection): void;

    /**
     * Vide le TopK
     */
    public function clear(): void;
}
