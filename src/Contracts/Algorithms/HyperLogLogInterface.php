<?php

namespace AndyDefer\AlgoKIT\Contracts\Algorithms;

use AndyDefer\AlgoKIT\Collections\HyperLogLogCollection;
use AndyDefer\AlgoKIT\Collections\HyperLogLogResultCollection;

interface HyperLogLogInterface
{
    /**
     * Ajoute une valeur au HyperLogLog
     */
    public function add(string $value): void;

    /**
     * Estime le nombre d'éléments uniques
     *
     * @return int Nombre estimé d'éléments uniques
     */
    public function count(): int;

    /**
     * Ajoute plusieurs valeurs en batch
     */
    public function addBatch(HyperLogLogCollection $collection): void;

    /**
     * Estime le nombre d'éléments uniques pour plusieurs collections
     *
     * @return HyperLogLogResultCollection Collection des résultats
     */
    public function countBatch(HyperLogLogCollection $collection): HyperLogLogResultCollection;

    /**
     * Vide le HyperLogLog
     */
    public function clear(): void;
}
