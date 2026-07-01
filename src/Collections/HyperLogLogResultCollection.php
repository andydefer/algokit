<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\HyperLogLogResultRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Typed collection for HyperLogLog cardinality results.
 *
 * Contains a collection of HyperLogLogResultRecord objects.
 */
final class HyperLogLogResultCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(HyperLogLogResultRecord::class);
    }
}
