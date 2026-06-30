<?php

namespace AndyDefer\AlgoKIT\Contracts\Algorithms;

use AndyDefer\AlgoKIT\Collections\BKTreeResultCollection;

interface TreeInterface
{
    /**
     * Insère un mot dans l'arbre
     */
    public function insert(string $word): void;

    /**
     * Recherche les mots similaires à un mot donné
     *
     * @param  string  $word  Le mot à rechercher
     * @param  int  $tolerance  La distance maximale autorisée (Levenshtein)
     * @param  int  $limit  Le nombre maximum de résultats
     * @return BKTreeResultCollection Collection des résultats
     */
    public function search(string $word, int $tolerance = 2, int $limit = 10): BKTreeResultCollection;

    /**
     * Vide l'arbre
     */
    public function clear(): void;
}
