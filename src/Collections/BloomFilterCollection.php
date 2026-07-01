<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\BloomFilterRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Typed collection for Bloom Filter values to insert.
 *
 * Contains a collection of BloomFilterRecord objects.
 */
final class BloomFilterCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(BloomFilterRecord::class);
    }
}
