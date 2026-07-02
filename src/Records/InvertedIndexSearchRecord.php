<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Record representing a search query for the inverted index.
 */
final class InvertedIndexSearchRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $token,
    ) {}
}
