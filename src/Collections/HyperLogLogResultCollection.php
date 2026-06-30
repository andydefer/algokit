<?php

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\HyperLogLogResultRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

final class HyperLogLogResultCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(HyperLogLogResultRecord::class);
    }
}
