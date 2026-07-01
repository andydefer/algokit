<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Record representing a fuzzy search result from BK-Tree.
 *
 * Contains the matching word and its Levenshtein distance from the query.
 */
final class BKTreeResultRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $word,
        public readonly int $distance,
    ) {}
}
