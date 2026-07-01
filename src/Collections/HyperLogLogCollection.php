<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\HyperLogLogRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Typed collection for HyperLogLog values to add.
 *
 * Contains a collection of HyperLogLogRecord objects.
 */
final class HyperLogLogCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(HyperLogLogRecord::class);
    }
}
