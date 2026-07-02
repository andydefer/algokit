<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\InvertedIndexRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Typed collection for InvertedIndex records.
 */
final class InvertedIndexCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(InvertedIndexRecord::class);
    }
}
