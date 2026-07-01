<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\BloomFilterResultRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Typed collection for Bloom Filter membership results.
 *
 * Contains a collection of BloomFilterResultRecord objects.
 */
final class BloomFilterResultCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(BloomFilterResultRecord::class);
    }
}
