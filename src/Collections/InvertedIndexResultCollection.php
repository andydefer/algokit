<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\InvertedIndexResultRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Typed collection for InvertedIndex result records.
 */
final class InvertedIndexResultCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(InvertedIndexResultRecord::class);
    }
}
