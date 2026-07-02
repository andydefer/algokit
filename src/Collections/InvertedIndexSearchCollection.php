<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\InvertedIndexSearchRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Typed collection for InvertedIndex search queries.
 */
final class InvertedIndexSearchCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(InvertedIndexSearchRecord::class);
    }
}
